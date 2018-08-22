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

use Desperado\ServiceBus\Application\Bootstrap;
use Desperado\ServiceBus\Application\ServiceBusKernel;
use Desperado\ServiceBus\Common\Contract\Messages\Message;
use function Desperado\ServiceBus\Common\removeDirectory;
use Desperado\ServiceBus\DependencyInjection\Extensions\ServiceBusExtension;
use Desperado\ServiceBus\OutboundMessage\Destination;
use Desperado\ServiceBus\Storage\SQL\DoctrineDBAL\DoctrineDBALAdapter;
use Desperado\ServiceBus\Tests\Application\Kernel\Stubs\KernelTestExtension;
use Desperado\ServiceBus\Tests\Stubs\Messages\CommandWithPayload;
use Desperado\ServiceBus\Tests\Stubs\Messages\FirstEmptyEvent;
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
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var string
     */
    private $cacheDirectory;

    /**
     * @inheritdoc
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
    }

    /**
     * @inheritdoc
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        VirtualTransportBuffer::reset();

        removeDirectory($this->cacheDirectory);

        unset($this->kernel, $this->cacheDirectory);
    }

    /**
     * @test
     *
     * @return ServiceBusKernel
     *
     * @throws \Throwable
     */
    public function simpleCreate(): ServiceBusKernel
    {
        $kernel = new ServiceBusKernel($this->container);

        $topic = new VirtualTopic('test_topic');
        $queue = new VirtualQueue('test_queue');

        $defaultOutboundDestination = new Destination('test_topic', 'test_key');
        $customOutboundDestination  = new Destination('custom_test_topic', 'custom_test_key');

        $kernel
            ->transportConfigurator()
            ->createTopic($topic)
            ->addQueue($queue, new QueueBind($topic, 'test_key'))
            ->addDefaultDestinations($defaultOutboundDestination)
            ->registerCustomMessageDestinations(FirstEmptyEvent::class, $customOutboundDestination);

        return $kernel;
    }

    /**
     * @test
     * @depends simpleCreate
     *
     * @param ServiceBusKernel $kernel
     *
     * @return void
     */
    public function listenMessageWithNoHandlers(ServiceBusKernel $kernel): void
    {
        $this->sendMessage(new CommandWithPayload('payload'));

        $kernel->listen(new VirtualQueue('test_queue'));
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
