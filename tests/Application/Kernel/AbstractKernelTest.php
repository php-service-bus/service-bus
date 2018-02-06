<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Tests\Application\Kernel;

use Desperado\Domain\ParameterBag;
use Desperado\Infrastructure\Bridge\AnnotationsReader\DoctrineAnnotationsReader;
use Desperado\Saga\Service\SagaService;
use Desperado\ServiceBus\Application\EntryPoint\EntryPointContext;
use Desperado\ServiceBus\Application\Kernel\AbstractKernel;
use Desperado\ServiceBus\MessageBus\MessageBusBuilder;
use Desperado\ServiceBus\Services\AnnotationsExtractor;
use Desperado\ServiceBus\Services\AutowiringServiceLocator;
use Desperado\ServiceBus\Tests\Services\Stabs\TestServiceCommand;
use Desperado\ServiceBus\Tests\Services\Stabs\TestServiceEvent;
use Desperado\ServiceBus\Tests\TestApplicationContext;
use Desperado\ServiceBus\Tests\TestContainer;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use React\Promise\FulfilledPromise;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 *
 */
class AbstractKernelTest extends TestCase
{
    /**
     * @var AbstractKernel
     */
    private $kernel;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $container = new TestContainer();
        $eventDispatcher = new EventDispatcher();
        $autowiringServiceLocator = new AutowiringServiceLocator($container, []);
        $annotationsReader = new DoctrineAnnotationsReader();
        $serviceHandlersExtractor = new AnnotationsExtractor(
            $annotationsReader,
            $autowiringServiceLocator
        );

        $logger = new NullLogger();
        $builder = new MessageBusBuilder($serviceHandlersExtractor, $eventDispatcher, $logger);

        /** @var SagaService $sagaService */
        $sagaService = self::getMockBuilder(SagaService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->kernel = new TestKernel(
            $builder,
            $sagaService,
            $eventDispatcher,
            new NullLogger()
        );
    }

    /**
     * @inheritdoc
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        unset(
            $this->eventDispatcher,
            $this->container,
            $this->autowiringServiceLocator,
            $this->annotationsReader,
            $this->serviceHandlersExtractor
        );
    }

    /**
     * @test
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function handleSuccessMessage(): void
    {
        $executionContext = new TestApplicationContext();
        $entryPointContext = EntryPointContext::create(
            TestServiceCommand::create([]),
            new ParameterBag()
        );

        $result = $this->kernel->handle($entryPointContext, $executionContext);

        static::assertInstanceOf(
            FulfilledPromise::class,
            $result
        );

        $result->then(
            function($value)
            {
                static::assertNotNull($value);
                static::assertFileExists(\sys_get_temp_dir() . '/executeTestServiceCommand.lock');
            },
            function(\Throwable $throwable)
            {
                $this->fail($throwable->getMessage());
            }
        );

        @\unlink(\sys_get_temp_dir() . '/executeTestServiceCommand.lock');
    }

    /**
     * @test
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function testSuccessAndFailedMessage(): void
    {
        $executionContext = new TestApplicationContext();
        $entryPointContext = EntryPointContext::create(
            TestServiceEvent::create([]),
            new ParameterBag()
        );

        $result = $this->kernel->handle($entryPointContext, $executionContext);

        static::assertInstanceOf(
            FulfilledPromise::class,
            $result
        );

        $result
            ->then(
                function($value)
                {
                    static::assertNull($value);
                },
                function(\Throwable $throwable)
                {
                    $this->fail($throwable->getMessage());
                }
            );
    }
}