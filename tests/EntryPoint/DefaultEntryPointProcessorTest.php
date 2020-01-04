<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Tests\EntryPoint;

use Monolog\Handler\TestHandler;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use ServiceBus\EntryPoint\DefaultEntryPointProcessor;
use ServiceBus\EntryPoint\IncomingMessageDecoder;
use ServiceBus\MessageExecutor\DefaultMessageExecutor;
use ServiceBus\MessageSerializer\Symfony\SymfonyMessageSerializer;
use ServiceBus\MessagesRouter\Router;
use ServiceBus\Services\Configuration\DefaultHandlerOptions;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use function Amp\Promise\wait;
use function ServiceBus\Common\jsonEncode;
use function ServiceBus\Tests\filterLogMessages;

/**
 *
 */
final class DefaultEntryPointProcessorTest extends TestCase
{
    /** @var TestHandler */
    private $logHandler;

    /** @var LoggerInterface */
    private $logger;

    /** @var EntryPointTestContextFactory */
    private $contextFactory;

    /** @var IncomingMessageDecoder */
    private $messageDecoder;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->logHandler = new TestHandler();
        $this->logger     = new Logger('tests', [$this->logHandler]);

        $this->contextFactory = new EntryPointTestContextFactory($this->logger);

        $containerBuilder = new ContainerBuilder();
        $containerBuilder->set('default_serializer', new SymfonyMessageSerializer());

        $this->messageDecoder = new IncomingMessageDecoder(
            ['service_bus.decoder.default_handler' => 'default_serializer'],
            $containerBuilder
        );
    }

    /** @test */
    public function decodeFailed(): void
    {
        $processor = new DefaultEntryPointProcessor(
            $this->messageDecoder,
            $this->contextFactory,
            null,
            $this->logger
        );

        wait($processor->handle(new EntryPointTestIncomingPackage('qwerty')));

        static::assertContains('Failed to denormalize the message', filterLogMessages($this->logHandler));
    }

    /** @test */
    public function withoutHandlers(): void
    {
        $processor = new DefaultEntryPointProcessor(
            $this->messageDecoder,
            $this->contextFactory,
            null,
            $this->logger
        );

        wait($processor->handle(new EntryPointTestIncomingPackage(self::serialize(new EntryPointTestMessage('id')))));

        static::assertContains(
            'There are no handlers configured for the message "{messageClass}"',
            filterLogMessages($this->logHandler)
        );
    }

    /** @test */
    public function withFailedHandler(): void
    {
        $router = new Router();

        $closure = \Closure::fromCallable(
            static function (): void
            {
                throw new \RuntimeException('Some message execution failed');
            }
        );

        $executor = new DefaultMessageExecutor(
            $closure,
            new \SplObjectStorage(),
            DefaultHandlerOptions::createForCommandHandler(),
            [],
            $this->logger
        );

        $router->registerHandler(EntryPointTestMessage::class, $executor);

        $processor = new DefaultEntryPointProcessor(
            $this->messageDecoder,
            $this->contextFactory,
            $router,
            $this->logger
        );

        wait($processor->handle(new EntryPointTestIncomingPackage(self::serialize(new EntryPointTestMessage('id')))));

        static::assertContains('Some message execution failed', filterLogMessages($this->logHandler));
    }

    /** @test */
    public function successExecution(): void
    {
        $variable = 'processing';

        $router = new Router();

        $closure = \Closure::fromCallable(
            static function () use (&$variable): void
            {
                $variable = 'handled';
            }
        );

        $executor = new DefaultMessageExecutor(
            $closure,
            new \SplObjectStorage(),
            DefaultHandlerOptions::createForCommandHandler(),
            [],
            $this->logger
        );

        $router->registerHandler(EntryPointTestMessage::class, $executor);

        $processor = new DefaultEntryPointProcessor(
            $this->messageDecoder,
            $this->contextFactory,
            $router,
            $this->logger
        );

        wait($processor->handle(new EntryPointTestIncomingPackage(self::serialize(new EntryPointTestMessage('id')))));

        static::assertSame('handled', $variable);
    }

    private static function serialize(object $message): string
    {
        return jsonEncode([
            'namespace' => \get_class($message),
            'message'   => $message->jsonSerialize()
        ]);
    }
}
