<?php /** @noinspection PhpUnhandledExceptionInspection */

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Tests\Application;

use Amp\Loop;
use PHPUnit\Framework\TestCase;
use ServiceBus\Application\Bootstrap;
use ServiceBus\Application\DependencyInjection\Compiler\Logger\LoggerCompilerPass;
use ServiceBus\Application\DependencyInjection\Compiler\Logger\StdOutLoggerCompilerPass;
use ServiceBus\Application\ServiceBusKernel;
use ServiceBus\Common\Module\ServiceBusModule;
use ServiceBus\Transport\Common\DeliveryDestination;
use ServiceBus\Transport\Common\Queue;
use ServiceBus\Transport\Common\Topic;
use ServiceBus\Transport\Common\Transport;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use function ServiceBus\Tests\removeDirectory;

/**
 *
 */
final class ServiceBusKernelTest extends TestCase
{
    /** @var string */
    private $cacheDirectory;

    /** @var Bootstrap */
    private $bootstrap;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->cacheDirectory = \sys_get_temp_dir() . '/kernel_test';

        if (\file_exists($this->cacheDirectory) === false)
        {
            \mkdir($this->cacheDirectory);
        }

        $this->bootstrap = Bootstrap::create('kernelTest', 'test');
        $this->bootstrap->useCustomCacheDirectory($this->cacheDirectory);

        $this->bootstrap->applyModules(
            new class() implements ServiceBusModule
            {
                public function boot(ContainerBuilder $containerBuilder): void
                {
                    $containerBuilder->setDefinition(
                        Transport::class,
                        new Definition(ServiceBusKernelTestTransport::class)
                    );

                    $containerBuilder->setDefinition(
                        DeliveryDestination::class,
                        new Definition(ServiceBusKernelTestTransportDestination::class)
                    );
                }
            }
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        removeDirectory($this->cacheDirectory);

        unset($this->cacheDirectory);
    }

    /** @test */
    public function simpleConfigure(): void
    {
        $kernel = new ServiceBusKernel($this->bootstrap->boot());

        Loop::run(
            static function () use ($kernel): \Generator
            {
                $queue = new class() implements Queue
                {
                    public function toString(): string
                    {
                        return 'kernelQueue';
                    }
                };

                yield $kernel->createQueue($queue);

                yield $kernel->createTopic(
                    new class() implements Topic
                    {
                        public function toString(): string
                        {
                            return 'kernelTopic';
                        }
                    }
                );

                $kernel->monitorLoopBlock();
                $kernel->enableGarbageCleaning();
                $kernel->useDefaultStopSignalHandler();
                $kernel->stopAfter(2);

                yield $kernel->run($queue);
            }
        );
    }
}
