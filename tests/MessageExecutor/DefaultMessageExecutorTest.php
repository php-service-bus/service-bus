<?php /** @noinspection PhpUnhandledExceptionInspection */

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Tests\MessageExecutor;

use Monolog\Handler\TestHandler;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use ServiceBus\ArgumentResolvers\ArgumentResolver;
use ServiceBus\ArgumentResolvers\ContextArgumentResolver;
use ServiceBus\ArgumentResolvers\MessageArgumentResolver;
use ServiceBus\MessageExecutor\DefaultMessageExecutor;
use ServiceBus\Services\Configuration\DefaultHandlerOptions;
use ServiceBus\Tests\Services\Configuration\EmptyMessage;
use ServiceBus\Tests\TestContext;
use function Amp\Promise\wait;
use function ServiceBus\Tests\filterLogMessages;

/**
 *
 */
final class DefaultMessageExecutorTest extends TestCase
{
    /** @var TestHandler */
    private $logHandler;

    /** @var LoggerInterface */
    private $logger;

    /** @var ArgumentResolver[] */
    private $argumentResolvers;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->logHandler        = new TestHandler();
        $this->logger            = new Logger('tests', [$this->logHandler]);
        $this->argumentResolvers = [
            new MessageArgumentResolver(),
            new ContextArgumentResolver()
        ];
    }

    /** @test */
    public function simpleExecute(): void
    {
        $variable = 'qwerty';
        $closure  = \Closure::fromCallable(
            static function () use (&$variable): void
            {
                $variable = 'handled';
            }
        );

        $processor = new DefaultMessageExecutor(
            $closure,
            new \SplObjectStorage(),
            DefaultHandlerOptions::createForCommandHandler('some description'),
            $this->argumentResolvers
        );

        $context = new TestContext();

        wait($processor(new EmptyMessage(), $context));

        static::assertSame('handled', $variable);

        $messages = filterLogMessages($context->testLogHandler);

        static::assertContains('some description', $messages);
    }

    /** @test */
    public function executeWithHandleThrowable(): void
    {
        $context = new TestContext();

        $closure = \Closure::fromCallable(
            static function (): void
            {
                throw new \LogicException('ups...');
            }
        );

        $options = DefaultHandlerOptions::createForCommandHandler();
        $options = $options->withDefaultThrowableEvent(TestMessageExecutionFailed::class);

        $processor = new DefaultMessageExecutor(
            $closure,
            new \SplObjectStorage(),
            $options,
            $this->argumentResolvers
        );

        wait($processor(new EmptyMessage(), $context));

        static::assertArrayHasKey(TestMessageExecutionFailed::class, $context->messages);

        /** @var TestMessageExecutionFailed $event */
        $event = $context->messages[TestMessageExecutionFailed::class];

        static::assertSame('ups...', $event->errorMessage());
    }

    /** @test */
    public function executionWithUnhandledThrowable(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectDeprecationMessage('ups...');

        $closure = \Closure::fromCallable(
            static function (): void
            {
                throw new \LogicException('ups...');
            }
        );

        $processor = new DefaultMessageExecutor(
            $closure,
            new \SplObjectStorage(),
            DefaultHandlerOptions::createForCommandHandler('some description'),
            $this->argumentResolvers
        );

        wait($processor(new EmptyMessage(), new TestContext()));
    }
}
