<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <contacts@desperado.dev>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Tests\EntryPoint;

use Amp\Loop;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use Monolog\Processor\PsrLogMessageProcessor;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use ServiceBus\AnnotationsReader\AttributesReader;
use ServiceBus\ArgumentResolvers\ContainerArgumentResolver;
use ServiceBus\ArgumentResolvers\ContextArgumentResolver;
use ServiceBus\ArgumentResolvers\MessageArgumentResolver;
use ServiceBus\EntryPoint\DefaultEntryPointProcessor;
use ServiceBus\EntryPoint\EntryPoint;
use ServiceBus\EntryPoint\EntryPointProcessor;
use ServiceBus\EntryPoint\IncomingMessageDecoder;
use ServiceBus\MessageExecutor\DefaultMessageExecutorFactory;
use ServiceBus\MessageSerializer\Symfony\SymfonySerializer;
use ServiceBus\MessagesRouter\Router;
use ServiceBus\Services\Configuration\AttributeServiceHandlersLoader;
use ServiceBus\Services\Configuration\ServiceMessageHandler;
use ServiceBus\Transport\Common\Queue;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use function ServiceBus\Tests\filterLogMessages;

/**
 *
 */
final class EntryPointTest extends TestCase
{
    /**
     * @var TestHandler
     */
    private $logHandler;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var EntryPointProcessor
     */
    private $entryPointProcessor;

    /**
     * @var Queue
     */
    private $queue;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->logHandler = new TestHandler();
        $this->logger     = new Logger('tests', [$this->logHandler]);

        $this->logger->pushProcessor(new PsrLogMessageProcessor());

        $containerBuilder = new ContainerBuilder();
        $containerBuilder->set('default_serializer', new SymfonySerializer());
        $containerBuilder->set(EntryPointTestDependency::class, new EntryPointTestDependency);

        $messageDecoder = new IncomingMessageDecoder(
            ['service_bus.decoder.default_handler' => 'default_serializer'],
            $containerBuilder
        );

        $handlers = (new AttributeServiceHandlersLoader(new AttributesReader()))->load(new EntryPointTestService());

        $messageExecutorsFactory = new DefaultMessageExecutorFactory(
            [
                new MessageArgumentResolver(),
                new ContextArgumentResolver(),
                new ContainerArgumentResolver($containerBuilder)
            ]
        );

        $messageRouter = new Router();

        /** @var ServiceMessageHandler $handler */
        foreach ($handlers as $handler)
        {
            $messageRouter->registerHandler(
                $handler->messageHandler->messageClass,
                $messageExecutorsFactory->create($handler->messageHandler)
            );
        }

        $this->entryPointProcessor = new DefaultEntryPointProcessor(
            $messageDecoder,
            new EntryPointTestContextFactory($this->logger),
            $messageRouter,
            $this->logger
        );

        $this->queue = new class() implements Queue
        {
            public function toString(): string
            {
                return 'testing';
            }
        };
    }

    /** @test */
    public function listenWithNoMessages(): void
    {
        $entryPoint = new EntryPoint(
            new EntryPointTestTransport(),
            $this->entryPointProcessor,
            $this->logger
        );

        Loop::run(
            function () use ($entryPoint): void
            {
                $entryPoint->listen($this->queue);
                $entryPoint->stop();
            }
        );

        self::assertContains('Subscriber stop command received', filterLogMessages($this->logHandler));
    }

    /** @test */
    public function listWithToMatchMessages(): void
    {
        $messages = [];

        for ($i = 1; $i <= 100; $i++)
        {
            $messages[] = new EntryPointTestMessage((string) $i);
        }

        $entryPoint = new EntryPoint(
            new EntryPointTestTransport($messages),
            $this->entryPointProcessor,
            $this->logger,
            2,
            10
        );

        Loop::run(
            function () use ($entryPoint): \Generator
            {
                yield $entryPoint->listen($this->queue);

                $entryPoint->stop();
            }
        );

        $logMessages = filterLogMessages($this->logHandler);
;
        self::assertContains('handled', $logMessages);
        self::assertContains('The maximum number of tasks has been reached', $logMessages);
    }

    /** @test */
    public function listenWithFailedExecution(): void
    {
        $entryPoint = new EntryPoint(
            new EntryPointTestTransport([new EntryPointTestMessage('throw')]),
            $this->entryPointProcessor,
            $this->logger
        );

        Loop::run(
            function () use ($entryPoint): \Generator
            {
                yield $entryPoint->listen($this->queue);
                $entryPoint->stop();
            }
        );

        self::assertContains('ups...', filterLogMessages($this->logHandler));
    }

    /** @test */
    public function listenWithAwaitUnfinishedTasks(): void
    {
        $entryPoint = new EntryPoint(
            new EntryPointTestTransport([new EntryPointTestMessage('await')]),
            $this->entryPointProcessor,
            $this->logger
        );

        Loop::run(
            function () use ($entryPoint): \Generator
            {
                yield $entryPoint->listen($this->queue);
                $entryPoint->stop();
            }
        );

        self::assertContains('Waiting for the completion of all tasks taken', filterLogMessages($this->logHandler));
    }

    /** @test */
    public function listenWithExecutionTimeout(): void
    {
        $entryPoint = new EntryPoint(
            new EntryPointTestTransport([new EntryPointTestMessage('await')]),
            $this->entryPointProcessor,
            $this->logger,
            null,
            null,
            1500
        );

        Loop::run(
            function () use ($entryPoint): \Generator
            {
                yield $entryPoint->listen($this->queue);
                $entryPoint->stop();
            }
        );

        foreach (filterLogMessages($this->logHandler) as $message)
        {
            if (\strpos($message, 'operation was cancelled') !== false)
            {
                return;
            }
        }

        self::fail('Timeout expected');
    }
}
