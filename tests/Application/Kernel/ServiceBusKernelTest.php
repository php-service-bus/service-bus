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

use Amp\Promise;
use function Amp\Promise\wait;
use Amp\Success;
use Desperado\ServiceBus\Application\Bootstrap;
use Desperado\ServiceBus\Application\ServiceBusKernel;
use Desperado\ServiceBus\Common\Contract\Messages\Message;
use function Desperado\ServiceBus\Common\removeDirectory;
use Desperado\ServiceBus\DependencyInjection\Extensions\ServiceBusExtension;
use Desperado\ServiceBus\OutboundMessage\Destination;
use Desperado\ServiceBus\Storage\SQL\DoctrineDBAL\DoctrineDBALAdapter;
use Desperado\ServiceBus\Tests\Application\Kernel\Stubs\FailedMessageSendMarkerEvent;
use Desperado\ServiceBus\Tests\Application\Kernel\Stubs\KernelTestExtension;
use Desperado\ServiceBus\Tests\Application\Kernel\Stubs\SuccessResponseEvent;
use Desperado\ServiceBus\Tests\Application\Kernel\Stubs\TriggerFailedResponseMessageSendCommand;
use Desperado\ServiceBus\Tests\Application\Kernel\Stubs\TriggerResponseEventCommand;
use Desperado\ServiceBus\Tests\Application\Kernel\Stubs\TriggerThrowableCommand;
use Desperado\ServiceBus\Tests\Stubs\Messages\CommandWithPayload;
use Desperado\ServiceBus\Tests\Stubs\Messages\FirstEmptyEvent;
use Desperado\ServiceBus\Tests\Stubs\Messages\SecondEmptyCommand;
use Desperado\ServiceBus\Tests\Stubs\Transport\VirtualQueue;
use Desperado\ServiceBus\Tests\Stubs\Transport\VirtualTopic;
use Desperado\ServiceBus\Tests\Stubs\Transport\VirtualTransportBuffer;
use Desperado\ServiceBus\Transport\Marshal\Encoder\JsonMessageEncoder;
use Desperado\ServiceBus\Transport\QueueBind;
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

        $this->container = $bootstrap->boot();

        $this->kernel = new ServiceBusKernel($this->container);

        $topic = new VirtualTopic('test_topic');
        $queue = new VirtualQueue('test_queue');

        $defaultOutboundDestination = new Destination('test_topic', 'test_key');
        $customOutboundDestination  = new Destination('custom_test_topic', 'custom_test_key');

        $this->kernel
            ->transportConfigurator()
            ->createTopic($topic)
            ->addQueue($queue)
            ->bindQueue(new QueueBind($queue, $topic, 'test_key'))
            ->addDefaultDestinations($defaultOutboundDestination)
            ->registerCustomMessageDestinations(FirstEmptyEvent::class, $customOutboundDestination);

        $this->logHandler = $this->container->get(TestHandler::class);
    }

    /**
     * @inheritdoc
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        VirtualTransportBuffer::reset();

        removeDirectory($this->cacheDirectory);

        unset($this->kernel, $this->container, $this->cacheDirectory, $this->logHandler);
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

        $this->kernel->listen(new VirtualQueue('test_queue'));

        $records = $this->logHandler->getRecords();

        static::assertNotEmpty($records);
        static::assertCount(4, $records);

        $latest = \end($records);

        static::assertEquals(
            'There are no handlers configured for the message "Desperado\\ServiceBus\\Tests\\Stubs\\Messages\\CommandWithPayload"',
            $latest['message']
        );
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

        $this->kernel->disableMessagesPayloadLogging();
        $this->kernel->listen(new VirtualQueue('test_queue'));
        $records = $this->logHandler->getRecords();

        static::assertNotEmpty($records);
        static::assertCount(3, $records);

        $latest = \end($records);

        static::assertEquals(
            'Desperado\\ServiceBus\\Tests\\Application\\Kernel\\Stubs\\KernelTestService::handleWithThrowable',
            $latest['message']
        );
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

        $this->kernel->disableMessagesPayloadLogging();

        $this->kernel->listen(new VirtualQueue('test_queue'));
        $records = $this->logHandler->getRecords();

        static::assertNotEmpty($records);
        static::assertCount(3, $records);

        $latest = \end($records);

        static::assertEquals(
            'Sending a "{messageClass}" message to "{destinationTopic}/{destinationRoutingKey}"',
            $latest['message']
        );

        static::assertEquals(
            [
                'messageClass'          => SuccessResponseEvent::class,
                'destinationTopic'      => 'test_topic',
                'destinationRoutingKey' => 'test_key',
                'headers'               => [
                    'x-message-class'         => SuccessResponseEvent::class,
                    'x-created-after-message' => TriggerResponseEventCommand::class,
                    'x-hostname'              => \gethostname()
                ]
            ],
            $latest['context']
        );
    }

    /**
     * @test
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function failedResponseDelivery(): void
    {
        $this->sendMessage(new TriggerFailedResponseMessageSendCommand());

        $this->kernel->listen(new VirtualQueue('test_queue'));
        $records = $this->logHandler->getRecords();

        static::assertNotEmpty($records);
        static::assertCount(7, $records);

        $messageEntry = $records[\count($records) - 2];

        /** @see VirtualPublisher::send() */
        static::assertEquals(
            'Error sending message "{messageClass}" to broker: "{throwableMessage}"',
            $messageEntry['message']
        );

        unset($messageEntry['context']['throwablePoint']);

        static::assertEquals(
            [
                'messageClass'     => FailedMessageSendMarkerEvent::class,
                'throwableMessage' => 'shit happens'
            ],
            $messageEntry['context']
        );
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

        $this->kernel->listen(new VirtualQueue('test_queue'));
        $records = $this->logHandler->getRecords();

        static::assertNotEmpty($records);
        static::assertCount(5, $records);

        $messageEntry = \end($records);
        \reset($records);

        static::assertEquals('test exception message', $messageEntry['message']);

        $messageEntry = $records[\count($records) - 2];

        static::assertEquals('Test message', $messageEntry['message']);
    }

    /**
     * @param Message $message
     * @param array   $headers
     *
     * @return void
     */
    private function sendMessage(Message $message, array $headers = []): void
    {
        $encoder = new JsonMessageEncoder();

        VirtualTransportBuffer::instance()->add(
            $encoder->encode($message),
            $headers
        );
    }
}
