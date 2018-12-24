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

namespace Desperado\ServiceBus\Tests\Application\Kernel;

use function Amp\Promise\wait;
use Bunny\Channel;
use Desperado\ServiceBus\Application\Bootstrap;
use Desperado\ServiceBus\Application\ServiceBusKernel;
use Desperado\ServiceBus\Common\Contract\Messages\Message;
use function Desperado\ServiceBus\Common\readReflectionPropertyValue;
use function Desperado\ServiceBus\Common\removeDirectory;
use Desperado\ServiceBus\DependencyInjection\Extensions\ServiceBusExtension;
use Desperado\ServiceBus\Infrastructure\MessageSerialization\Symfony\SymfonyMessageSerializer;
use Desperado\ServiceBus\Infrastructure\Storage\SQL\DoctrineDBAL\DoctrineDBALAdapter;
use Desperado\ServiceBus\Infrastructure\Transport\Implementation\Amqp\AmqpExchange;
use Desperado\ServiceBus\Infrastructure\Transport\Implementation\Amqp\AmqpQueue;
use Desperado\ServiceBus\Infrastructure\Transport\Implementation\Amqp\AmqpTransportLevelDestination;
use Desperado\ServiceBus\Infrastructure\Transport\Package\OutboundPackage;
use Desperado\ServiceBus\Infrastructure\Transport\QueueBind;
use Desperado\ServiceBus\Tests\Application\Kernel\Stubs\KernelTestExtension;
use Desperado\ServiceBus\Tests\Application\Kernel\Stubs\KernelTestService;
use Desperado\ServiceBus\Tests\Application\Kernel\Stubs\TriggerResponseEventCommand;
use Desperado\ServiceBus\Tests\Application\Kernel\Stubs\TriggerThrowableCommand;
use Desperado\ServiceBus\Tests\Application\Kernel\Stubs\TriggerThrowableCommandWithResponseEvent;
use Desperado\ServiceBus\Tests\Application\Kernel\Stubs\WithValidationCommand;
use Desperado\ServiceBus\Tests\Application\Kernel\Stubs\WithValidationRulesCommand;
use Desperado\ServiceBus\Tests\Stubs\Messages\CommandWithPayload;
use Desperado\ServiceBus\Tests\Stubs\Messages\ExecutionFailed;
use Desperado\ServiceBus\Tests\Stubs\Messages\SecondEmptyCommand;
use Desperado\ServiceBus\Tests\Stubs\Messages\ValidationFailed;
use Monolog\Handler\TestHandler;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

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
     * @inheritdoc
     *
     * @throws \Throwable
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->cacheDirectory = \sys_get_temp_dir() . '/kernel_test';

        if(false === \file_exists($this->cacheDirectory))
        {
            \mkdir($this->cacheDirectory);
        }

        $bootstrap = Bootstrap::withDotEnv(__DIR__ . '/Stubs/.env');

        $bootstrap->useCustomCacheDirectory($this->cacheDirectory);
        $bootstrap->addExtensions(new ServiceBusExtension(), new KernelTestExtension());
        $bootstrap->useSqlStorage(DoctrineDBALAdapter::class, \getenv('DATABASE_CONNECTION_DSN'));
        $bootstrap->useRabbitMqTransport(
            \getenv('TRANSPORT_CONNECTION_DSN'),
            'test_topic',
            'tests'
        );

        $this->container = $bootstrap->boot();

        $this->kernel = new ServiceBusKernel($this->container);

        $topic = AmqpExchange::direct('test_topic');
        $queue = new AmqpQueue('test_queue');

        wait($this->kernel->transport()->createQueue($queue, new QueueBind($topic, 'tests')));

        $this->logHandler = $this->container->get(TestHandler::class);
    }

    /**
     * @inheritdoc
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        try
        {
            /** @var Channel $channel */
            $channel = readReflectionPropertyValue($this->kernel->transport(), 'channel');

            wait($channel->exchangeDelete('test_topic'));
            wait($channel->queueDelete('test_queue'));

            wait($this->kernel->transport()->disconnect());

            removeDirectory($this->cacheDirectory);

            unset($this->kernel, $this->container, $this->cacheDirectory, $this->logHandler);
        }
        catch(\Throwable $throwable)
        {

        }
    }

    /**
     * @test
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function listenMessageWithNoHandlers(): void
    {
        $this->sendMessage(new CommandWithPayload('payload'));

        wait($this->kernel->entryPoint()->listen(new AmqpQueue('test_queue')));

        $records = $this->logHandler->getRecords();

        static::assertNotEmpty($records);
        static::assertCount(6, $records);

        $latest = \end($records);

        static::assertEquals(
            'There are no handlers configured for the message "{messageClass}"',
            $latest['message']
        );

        static::assertEquals($latest['context']['messageClass'], CommandWithPayload::class);
    }

    /**
     * @test
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function listenFailedMessageExecution(): void
    {
        $this->sendMessage(new TriggerThrowableCommand());

        wait($this->kernel->entryPoint()->listen(new AmqpQueue('test_queue')));

        $records = $this->logHandler->getRecords();

        static::assertNotEmpty($records);
        static::assertCount(6, $records);

        $latest = \end($records);
        \reset($records);

        static::assertEquals(\sprintf('%s::handleWithThrowable', KernelTestService::class), $latest['message']);
    }

    /**
     * @test
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function successExecutionWithResponseMessage(): void
    {
        $this->sendMessage(new TriggerResponseEventCommand());

        wait($this->kernel->entryPoint()->listen(new AmqpQueue('test_queue')));
    }

    /**
     * @test
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function contextLogging(): void
    {
        $this->sendMessage(new SecondEmptyCommand());

        wait($this->kernel->entryPoint()->listen(new AmqpQueue('test_queue')));

        $records = $this->logHandler->getRecords();

        $messageEntry = \end($records);
        \reset($records);

        static::assertEquals('test exception message', $messageEntry['message']);
    }

    /**
     * @test
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function withFailedValidation(): void
    {
        $this->sendMessage(new WithValidationCommand(''));

        wait($this->kernel->entryPoint()->listen(new AmqpQueue('test_queue')));

        $records = $this->logHandler->getRecords();

        $messageEntry = \end($records);
        \reset($records);

        static::assertFalse($messageEntry['context']['isValid']);
        static::assertEquals(['This value should not be blank.'], $messageEntry['context']['violations']['value']);
    }

    /**
     * @test
     *
     * @return void
     *
     * @throws \Throwable
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
     * @return void
     *
     * @throws \Throwable
     */
    public function processMessageWithValidationFailure(): void
    {
        $this->sendMessage(new WithValidationRulesCommand(''));

        wait($this->kernel->entryPoint()->listen(new AmqpQueue('test_queue')));
    }

    /**
     * @test
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function processMessageWithSpecifiedThrowableEvent(): void
    {
        $this->sendMessage(new TriggerThrowableCommandWithResponseEvent());

        wait($this->kernel->entryPoint()->listen(new AmqpQueue('test_queue')));
    }

    /**
     * @param Message $message
     * @param array   $headers
     *
     * @return void
     *
     * @throws \Throwable
     */
    private function sendMessage(Message $message, array $headers = []): void
    {
        $encoder = new SymfonyMessageSerializer();

        $promise = $this->kernel->transport()->send(
            new OutboundPackage(
                $encoder->encode($message),
                $headers,
                new AmqpTransportLevelDestination('test_topic', 'tests')
            )
        );

        wait($promise);
    }
}
