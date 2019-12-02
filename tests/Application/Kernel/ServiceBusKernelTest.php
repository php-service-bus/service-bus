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

use function Amp\Promise\wait;
use function ServiceBus\Common\readReflectionPropertyValue;
use function ServiceBus\Common\uuid;
use function ServiceBus\Tests\removeDirectory;
use Amp\Loop;
use Monolog\Handler\TestHandler;
use PHPinnacle\Ridge\Channel;
use PHPUnit\Framework\TestCase;
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
    /**
     * @var ServiceBusKernel
     */
    private $kernel;

    /**
     * @var Transport
     */
    private $transport;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var string
     */
    private $cacheDirectory;

    /**
     * @var TestHandler
     */
    private $logHandler;

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

        $topic = AmqpExchange::direct('test_topic');
        $queue = AmqpQueue::default('test_queue');

        wait($this->kernel->createQueue($queue, QueueBind::create($topic, 'tests')));

        /** @var Transport $transport */
        $transport = readReflectionPropertyValue($this->kernel, 'transport');

        $this->logHandler = $this->container->get(TestHandler::class);
        $this->transport  = $transport;
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        try
        {
            /** @var Channel $channel */
            $channel = readReflectionPropertyValue($this->transport, 'channel');

            wait($channel->exchangeDelete('test_topic'));
            wait($channel->queueDelete('test_queue'));

            wait($this->transport->disconnect());

            removeDirectory($this->cacheDirectory);

            unset($this->kernel, $this->container, $this->cacheDirectory, $this->logHandler);
        }
        catch (\Throwable $throwable)
        {
        }
    }

    /**
     * @test
     *
     * @throws \Throwable
     *
     * @return void
     */
    public function listenMessageWithNoHandlers(): void
    {
        $this->sendMessage(new CommandWithPayload('payload'));

        Loop::run(
            function(): \Generator
            {
                yield $this->kernel->run(AmqpQueue::default('test_queue'));
            }
        );

        $messages = \array_map(
            static function(array $entry): string
            {
                return $entry['message'];
            },
            $this->logHandler->getRecords()
        );

        static::assertContains('There are no handlers configured for the message "{messageClass}"', $messages);
    }

    /**
     * @test
     *
     * @throws \Throwable
     *
     * @return void
     */
    public function listenFailedMessageExecution(): void
    {
        $this->sendMessage(new TriggerThrowableCommand());

        Loop::run(
            function(): \Generator
            {
                yield $this->kernel->run(AmqpQueue::default('test_queue'));
            }
        );

        $messages = \array_map(
            static function(array $entry): string
            {
                return $entry['message'];
            },
            $this->logHandler->getRecords()
        );

        static::assertContains(\sprintf('%s::handleWithThrowable', KernelTestService::class), $messages);
    }

    /**
     * @test
     *
     * @throws \Throwable
     *
     * @return void
     */
    public function successExecutionWithResponseMessage(): void
    {
        $this->sendMessage(new TriggerResponseEventCommand());

        wait($this->kernel->run(AmqpQueue::default('test_queue')));
    }

    /**
     * @test
     *
     * @throws \Throwable
     *
     * @return void
     */
    public function contextLogging(): void
    {
        $this->sendMessage(new SecondEmptyCommand());

        Loop::run(
            function(): \Generator
            {
                yield $this->kernel->run(AmqpQueue::default('test_queue'));
            }
        );

        $messages = \array_map(
            static function(array $entry): string
            {
                return $entry['message'];
            },
            $this->logHandler->getRecords()
        );

        static::assertContains('test exception message', $messages);
    }

    /**
     * @test
     *
     * @throws \Throwable
     *
     * @return void
     */
    public function withFailedValidation(): void
    {
        $this->sendMessage(new WithValidationCommand(''));

        Loop::run(
            function(): \Generator
            {
                yield $this->kernel->run(AmqpQueue::default('test_queue'));
            }
        );

        $entries = \array_filter(
            \array_map(
                static function(array $entry): ?array
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
    }

    /**
     * @test
     *
     * @throws \Throwable
     *
     * @return void
     */
    public function enableWatchers(): void
    {
        $this->kernel->monitorLoopBlock();
        $this->kernel->enableGarbageCleaning();
        $this->kernel->useDefaultStopSignalHandler();
        $this->kernel->stopAfter(60);
        $this->kernel->stopWhenFilesChange(__DIR__);
    }

    /**
     * @test
     *
     * @throws \Throwable
     *
     * @return void
     */
    public function processMessageWithValidationFailure(): void
    {
        $this->sendMessage(new WithValidationRulesCommand(''));

        wait($this->kernel->run(AmqpQueue::default('test_queue')));
    }

    /**
     * @test
     *
     * @throws \Throwable
     *
     * @return void
     */
    public function processMessageWithSpecifiedThrowableEvent(): void
    {
        $this->sendMessage(new TriggerThrowableCommandWithResponseEvent());

        wait($this->kernel->run(AmqpQueue::default('test_queue')));
    }

    /**
     * @param object $message
     * @param array  $headers
     *
     * @throws \Throwable
     *
     * @return void
     */
    private function sendMessage(object $message, array $headers = []): void
    {
        $encoder = new SymfonyMessageSerializer();

        $promise = $this->transport->send(
            OutboundPackage::create(
                $encoder->encode($message),
                $headers,
                new AmqpTransportLevelDestination('test_topic', 'tests'),
                uuid()
            )
        );

        wait($promise);
    }
}
