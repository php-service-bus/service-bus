<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation)
 * Supports Saga pattern and Event Sourcing
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Transport\Amqp\Bunny;

use function Amp\call;
use Amp\Coroutine;
use Amp\Failure as AmpFailurePromise;
use Amp\Loop as AmpLoop;
use Amp\Loop;
use Amp\Promise as AmpPromise;
use Amp\Success;
use Bunny\AbstractClient;
use Desperado\ServiceBus\Transport\Amqp\AmqpConnectionConfiguration;
use Psr\Log\NullLogger;
use React\Promise\Deferred as ReactDeferred;
use React\Promise\PromiseInterface as ReactPromise;
use Bunny\Protocol\HeartbeatFrame;
use Bunny\Protocol\MethodConnectionStartFrame;
use Bunny\Protocol\MethodConnectionTuneFrame;
use Bunny\Async\Client;
use Bunny\ClientStateEnum;
use Bunny\Exception\ClientException;
use Psr\Log\LoggerInterface;

/**
 * The library (jakubkulhan/bunny) architecture does not allow to expand its functionality correctly
 *
 * @todo: Prepare a pull request including fixes
 */
final class AmqpBunnyClient extends Client
{
    /**
     * @var AmqpConnectionConfiguration
     */
    private $configuration;

    /**
     * Read from stream watcher
     *
     * @var string|null
     */
    private $readWatcher;

    /**
     * Write to stream watcher
     *
     * @var string|null
     */
    private $writeWatcher;

    /**
     * Heartbeat watcher
     *
     * @var string|null
     */
    private $heartbeatWatcher;

    /**
     * @noinspection PhpMissingParentConstructorInspection
     *
     * @param AmqpConnectionConfiguration $configuration
     * @param LoggerInterface|null        $log
     */
    public function __construct(AmqpConnectionConfiguration $configuration, LoggerInterface $log = null)
    {
        $this->configuration = $configuration;

        $parameters = [
            'async'     => true,
            'host'      => $configuration->host(),
            'port'      => $configuration->port(),
            'vhost'     => $configuration->virtualHost(),
            'user'      => $configuration->user(),
            'password'  => $configuration->password(),
            'timeout'   => $configuration->timeout(),
            'heartbeat' => $configuration->heartbeatInterval()
        ];

        AbstractClient::__construct($parameters, $log ?? new NullLogger());

        $this->init();
    }

    /**
     * @inheritdoc
     *
     * @psalm-suppress ImplementedReturnTypeMismatch
     *
     * @return AmpPromise<null>
     */
    public function connect(): AmpPromise
    {
        if($this->state !== ClientStateEnum::NOT_CONNECTED)
        {
            return new AmpFailurePromise(
                new ClientException('Client already connected/connecting')
            );
        }

        $this->onConnecting();
        $this->writeProtocolHeaders();
        $this->addReadableWatcher();

        return new Coroutine($this->doConnect());
    }

    /**
     * @inheritdoc
     *
     * @psalm-suppress ImplementedReturnTypeMismatch
     *
     * @return AmpPromise<null>
     */
    public function disconnect($replyCode = 0, $replyText = ''): AmpPromise
    {
        /** @psalm-suppress InvalidArgument Incorrect psalm unpack parameters (...$args) */
        return call(
            function(int $replyCode, string $replyText): \Generator
            {
                if($this->state !== ClientStateEnum::CONNECTED)
                {
                    return new Success();
                }

                $this->onDisconnecting();

                if($replyCode === 0)
                {
                    foreach($this->channels as $channel)
                    {
                        yield $channel->close($replyCode, $replyText);
                    }
                }

                if(!empty($this->channels))
                {
                    throw new \LogicException('All channels have to be closed by now');
                }

                yield $this->connectionClose($replyCode, $replyText, 0, 0);

                $this->cancelReadWatcher();
                $this->cancelWriteWatcher();
                $this->cancelHeartbeatWatcher();

                $this->closeStream();
                $this->init();

                return yield new Success();
            },
            $replyCode, $replyText
        );
    }

    /**
     * @inheritdoc
     *
     * @return AmpPromise<null>
     */
    public function onHeartbeat(): AmpPromise
    {
        /** @psalm-suppress InvalidArgument Incorrect psalm unpack parameters (...$args) */
        return call(
            function(): \Generator
            {
                $currentTime = \microtime(true);

                /** @var float|null $lastWrite */
                $lastWrite = $this->lastWrite;

                if(null === $lastWrite)
                {
                    $lastWrite = $currentTime;
                }

                /** @var float $nextHeartbeat */
                $nextHeartbeat = $lastWrite + $this->configuration->heartbeatInterval();

                if($currentTime >= $nextHeartbeat)
                {
                    $this->writer->appendFrame(new HeartbeatFrame(), $this->writeBuffer);

                    yield $this->flushWriteBuffer();

                    $this->addHeartbeatTimer();
                }
            }
        );
    }

    /**
     * @inheritdoc
     */
    protected function flushWriteBuffer(): ReactPromise
    {
        if($this->flushWriteBufferPromise)
        {
            return $this->flushWriteBufferPromise;
        }

        $deferred = new ReactDeferred();

        $this->writeWatcher = AmpLoop::onWritable(
            $this->getStream(),
            function() use ($deferred): void
            {
                try
                {
                    $this->write();

                    if($this->writeBuffer->isEmpty())
                    {
                        $this->cancelWriteWatcher();

                        $this->flushWriteBufferPromise = null;
                        $deferred->resolve(true);
                    }

                }
                catch(\Exception $e)
                {
                    $this->cancelWriteWatcher();

                    $this->flushWriteBufferPromise = null;
                    $deferred->reject($e);
                }
            }
        );

        return $this->flushWriteBufferPromise = $deferred->promise();
    }

    /**
     * Execute a callback when a stream resource becomes readable or is closed for reading
     *
     * @return void
     */
    private function addReadableWatcher(): void
    {
        $this->readWatcher = AmpLoop::onReadable(
            $this->getStream(),
            function(): void
            {
                $this->onDataAvailable();
            }
        );
    }

    /**
     * Execute connect
     *
     * @return \Generator<null>
     */
    private function doConnect(): \Generator
    {
        yield $this->flushWriteBuffer();

        /** @var MethodConnectionStartFrame $start */
        $start = yield $this->awaitConnectionStart();

        yield $this->authResponse($start);

        /** @var MethodConnectionTuneFrame $tune */
        $tune = yield $this->awaitConnectionTune();

        $this->frameMax = $tune->frameMax;

        if($tune->channelMax > 0)
        {
            $this->channelMax = $tune->channelMax;
        }

        $this->connectionTuneOk($tune->channelMax, $tune->frameMax, $this->options['heartbeat']);

        yield $this->connectionOpen($this->options['vhost']);

        $this->onConnected();
    }

    /**
     * Add timer for heartbeat
     *
     * @return void
     */
    private function addHeartbeatTimer(): void
    {
        /** @var float $seconds */
        $seconds = $this->options['heartbeat'];

        $this->heartbeatWatcher = AmpLoop::repeat(
            (int) ($seconds * 1000),
            function(): \Generator
            {
                yield $this->onHeartbeat();
            }
        );
    }

    /**
     * @return void
     */
    private function cancelHeartbeatWatcher(): void
    {
        if(null !== $this->heartbeatWatcher)
        {
            AmpLoop::cancel($this->heartbeatWatcher);

            $this->heartbeatWatcher = null;
        }
    }

    /**
     * @return void
     */
    private function cancelReadWatcher(): void
    {
        if(null !== $this->readWatcher)
        {
            Loop::cancel($this->readWatcher);

            $this->readWatcher = null;
        }
    }

    /**
     * @return void
     */
    private function cancelWriteWatcher(): void
    {
        if(null !== $this->writeWatcher)
        {
            Loop::cancel($this->writeWatcher);

            $this->writeWatcher = null;
        }
    }

    /**
     * Connection started
     *
     * @return void
     */
    private function onConnecting(): void
    {
        $this->state = ClientStateEnum::DISCONNECTING;
    }

    /**
     * Successful connected
     *
     * @return void
     */
    private function onConnected(): void
    {
        $this->state = ClientStateEnum::CONNECTED;

        $this->addHeartbeatTimer();
    }

    /**
     * Disconnect started
     *
     * @return void
     */
    private function onDisconnecting(): void
    {
        $this->state = ClientStateEnum::DISCONNECTING;

        $this->cancelHeartbeatWatcher();
    }

    /**
     * Add protocol version header
     *
     * @return void
     */
    private function writeProtocolHeaders(): void
    {
        $this->writer->appendProtocolHeader($this->writeBuffer);
    }
}
