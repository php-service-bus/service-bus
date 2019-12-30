<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Tests\Application\Kernel;

use Amp\Delayed;
use Amp\Promise;
use PHPUnit\Framework\TestCase;
use ServiceBus\Tests\Application\Kernel\Stubs\SuccessResponseEvent;
use ServiceBus\Tests\Stubs\Messages\ExecutionFailed;
use ServiceBus\Tests\Stubs\Messages\ValidationFailed;
use function ServiceBus\Common\readReflectionPropertyValue;
use function ServiceBus\Common\uuid;
use function ServiceBus\Tests\removeDirectory;
use Amp\Loop;
use Monolog\Handler\TestHandler;
use PHPinnacle\Ridge\Channel;
use Psr\Container\ContainerInterface;
use ServiceBus\Application\Bootstrap;
use ServiceBus\Application\DependencyInjection\Extensions\ServiceBusExtension;
use ServiceBus\Application\ServiceBusKernel;
use ServiceBus\MessageSerializer\Symfony\SymfonyMessageSerializer;
use ServiceBus\Tests\Application\Kernel\Stubs\KernelTestExtension;
use ServiceBus\Tests\Application\Kernel\Stubs\KernelTestService;
use ServiceBus\Tests\Application\Kernel\Stubs\TriggerResponseEventCommand;
use ServiceBus\Tests\Application\Kernel\Stubs\TriggerThrowableCommand;
use ServiceBus\Tests\Application\Kernel\Stubs\TriggerThrowableCommandWithResponseEvent;
use ServiceBus\Tests\Application\Kernel\Stubs\WithValidationCommand;
use ServiceBus\Tests\Application\Kernel\Stubs\WithValidationRulesCommand;
use ServiceBus\Tests\Stubs\Messages\CommandWithPayload;
use ServiceBus\Tests\Stubs\Messages\SecondEmptyCommand;
use ServiceBus\Transport\Amqp\AmqpExchange;
use ServiceBus\Transport\Amqp\AmqpQueue;
use ServiceBus\Transport\Amqp\AmqpTransportLevelDestination;
use ServiceBus\Transport\Common\Package\OutboundPackage;
use ServiceBus\Transport\Common\QueueBind;
use ServiceBus\Transport\Common\Transport;
use ServiceBus\Transport\Module\PhpInnacleTransportModule;

/**
 *
 */
final class ServiceBusKernelTest extends TestCase
{
    /** @var ServiceBusKernel */
    private $kernel;

    /** @var Transport */
    private $transport;

    /** @var ContainerInterface */
    private $container;

    /** @var string */
    private $cacheDirectory;

    /** @var TestHandler */
    private $logHandler;

    /** @var AmqpExchange */
    private $topic;

    /** @var AmqpQueue */
    private $queue;

    /**
     * {@inheritdoc}
     *
     * @throws \Throwable
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->cacheDirectory = \sys_get_temp_dir() . '/kernel_test';

        if (false === \file_exists($this->cacheDirectory))
        {
            \mkdir($this->cacheDirectory);
        }

        $bootstrap = Bootstrap::withDotEnv(__DIR__ . '/Stubs/.env')
            ->useCustomCacheDirectory($this->cacheDirectory)
            ->addExtensions(new ServiceBusExtension(), new KernelTestExtension())
            ->applyModules(new PhpInnacleTransportModule(
                (string) \getenv('TRANSPORT_CONNECTION_DSN'),
                'test_topic',
                'tests'
            ));

        $this->container = $bootstrap->boot();

        $this->kernel = new ServiceBusKernel($this->container);

        $this->kernel
            ->enableGarbageCleaning()
            ->monitorLoopBlock()
            ->stopWhenFilesChange(__DIR__);

        /** @var Transport $transport */
        $transport = readReflectionPropertyValue($this->kernel, 'transport');

        $this->logHandler = $this->container->get(TestHandler::class);
        $this->transport  = $transport;

        $this->topic = AmqpExchange::direct('test_topic');
        $this->queue = AmqpQueue::default('test_queue');
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        Loop::run(
            function (): \Generator
            {
                /** @var Channel|null $channel */
                $channel = readReflectionPropertyValue($this->transport, 'channel');

                if ($channel === null)
                {
                    Loop::stop();

                    return;
                }

                yield $channel->exchangeDelete('test_topic');
                yield $channel->queueDelete('test_queue');

                yield $this->transport->disconnect();

                removeDirectory($this->cacheDirectory);

                unset($this->kernel, $this->container, $this->cacheDirectory, $this->logHandler);

                Loop::stop();
            }
        );
    }

    /**
     * @test
     *
     * @throws \Throwable
     */
    public function listenMessageWithNoHandlers(): void
    {
        Loop::run(
            function (): \Generator
            {
                yield $this->kernel->createQueue($this->queue, new QueueBind($this->topic, 'tests'));
                yield $this->sendMessage(new CommandWithPayload('payload'));
                yield $this->kernel->run(AmqpQueue::default('test_queue'));

                yield new Delayed(2000);

                $messages = \array_map(
                    static function (array $entry): string
                    {
                        return $entry['message'];
                    },
                    $this->logHandler->getRecords()
                );

                static::assertContains(
                    \sprintf('There are no handlers configured for the message "%s"', CommandWithPayload::class),
                    $messages
                );

                Loop::stop();
            }
        );
    }

    /**
     * @test
     *
     * @throws \Throwable
     */
    public function listenFailedMessageExecution(): void
    {
        Loop::run(
            function (): \Generator
            {
                yield $this->kernel->createQueue($this->queue, new QueueBind($this->topic, 'tests'));
                yield $this->sendMessage(new TriggerThrowableCommand());
                yield $this->kernel->run(AmqpQueue::default('test_queue'));

                yield new Delayed(2000);

                $messages = \array_map(
                    static function (array $entry): string
                    {
                        return $entry['message'];
                    },
                    $this->logHandler->getRecords()
                );

                static::assertContains(\sprintf('%s::handleWithThrowable', KernelTestService::class), $messages);

                Loop::stop();
            }
        );
    }

    /**
     * @test
     *
     * @throws \Throwable
     */
    public function successExecutionWithResponseMessage(): void
    {
        Loop::run(
            function (): \Generator
            {
                yield $this->kernel->createQueue($this->queue, new QueueBind($this->topic, 'tests'));
                yield $this->sendMessage(new TriggerResponseEventCommand());
                yield $this->kernel->run(AmqpQueue::default('test_queue'));

                yield new Delayed(2000);

                $messages = \array_map(
                    static function (array $entry): string
                    {
                        return $entry['message'];
                    },
                    $this->logHandler->getRecords()
                );

                static::assertContains(
                    \sprintf('Send message "%s" to "application"', SuccessResponseEvent::class),
                    $messages
                );

                Loop::stop();
            }
        );
    }

    /**
     * @test
     *
     * @throws \Throwable
     */
    public function contextLogging(): void
    {
        Loop::run(
            function (): \Generator
            {
                yield $this->kernel->createQueue($this->queue, new QueueBind($this->topic, 'tests'));
                yield $this->sendMessage(new SecondEmptyCommand());
                yield $this->kernel->run(AmqpQueue::default('test_queue'));

                yield new Delayed(2000);

                $messages = \array_map(
                    static function (array $entry): string
                    {
                        return $entry['message'];
                    },
                    $this->logHandler->getRecords()
                );

                static::assertContains('test exception message', $messages);

                Loop::stop();
            }
        );
    }

    /**
     * @test
     *
     * @throws \Throwable
     */
    public function withFailedValidation(): void
    {
        Loop::run(
            function (): \Generator
            {
                yield $this->kernel->createQueue($this->queue, new QueueBind($this->topic, 'tests'));
                yield $this->sendMessage(new WithValidationCommand(''));
                yield $this->kernel->run(AmqpQueue::default('test_queue'));

                yield new Delayed(2000);

                $entries = \array_filter(
                    \array_map(
                        static function (array $entry): ?array
                        {
                            if (true === isset($entry['context']['violations']))
                            {
                                return $entry;
                            }

                            return null;
                        },
                        $this->logHandler->getRecords()
                    )
                );

                static::assertCount(1, $entries);

                $entry = \reset($entries);

                static::assertFalse($entry['context']['isValid']);
                static::assertSame(['This value should not be blank.'], $entry['context']['violations']['value']);

                Loop::stop();
            }
        );
    }

    /**
     * @test
     *
     * @throws \Throwable
     */
    public function enableWatchers(): void
    {
        Loop::run(
            function (): void
            {
                $this->kernel->monitorLoopBlock();
                $this->kernel->enableGarbageCleaning();
                $this->kernel->useDefaultStopSignalHandler();
                $this->kernel->stopAfter(60);
                $this->kernel->stopWhenFilesChange(__DIR__);

                Loop::stop();
            }
        );
    }

    /**
     * @test
     *
     * @throws \Throwable
     */
    public function processMessageWithValidationFailure(): void
    {
        Loop::run(
            function (): \Generator
            {
                yield $this->kernel->createQueue($this->queue, new QueueBind($this->topic, 'tests'));
                yield $this->sendMessage(new WithValidationRulesCommand(''));
                yield $this->kernel->run(AmqpQueue::default('test_queue'));

                yield new Delayed(2000);

                $messages = \array_map(
                    static function (array $entry): string
                    {
                        return $entry['message'];
                    },
                    $this->logHandler->getRecords()
                );

                static::assertContains(
                    \sprintf('Send message "%s" to "application"', ValidationFailed::class),
                    $messages
                );

                Loop::stop();
            }
        );
    }

    /**
     * @test
     *
     * @throws \Throwable
     */
    public function processMessageWithSpecifiedThrowableEvent(): void
    {
        Loop::run(
            function (): \Generator
            {
                yield $this->kernel->createQueue($this->queue, new QueueBind($this->topic, 'tests'));
                yield $this->sendMessage(new TriggerThrowableCommandWithResponseEvent());
                yield $this->kernel->run(AmqpQueue::default('test_queue'));

                yield new Delayed(2000);

                $messages = \array_map(
                    static function (array $entry): string
                    {
                        return $entry['message'];
                    },
                    $this->logHandler->getRecords()
                );

                static::assertContains(
                    \sprintf('Send message "%s" to "application"', ExecutionFailed::class),
                    $messages
                );

                Loop::stop();
            }
        );
    }

    private function sendMessage(object $message, array $headers = []): Promise
    {
        $encoder = new SymfonyMessageSerializer();

        return $this->transport->send(
            new OutboundPackage(
                $encoder->encode($message),
                $headers,
                new AmqpTransportLevelDestination('test_topic', 'tests'),
                uuid()
            )
        );
    }
}
