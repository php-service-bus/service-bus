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

use Doctrine\Common\Annotations\AnnotationRegistry;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use ServiceBus\ArgumentResolvers\ArgumentResolver;
use ServiceBus\ArgumentResolvers\ContextArgumentResolver;
use ServiceBus\ArgumentResolvers\MessageArgumentResolver;
use ServiceBus\MessageExecutor\DefaultMessageExecutor;
use ServiceBus\MessageExecutor\MessageValidationExecutor;
use ServiceBus\Services\Configuration\DefaultHandlerOptions;
use ServiceBus\Tests\Services\Configuration\EmptyMessage;
use ServiceBus\Tests\TestContext;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\ValidatorBuilder;
use function Amp\Promise\wait;

/**
 *
 */
final class MessageValidationExecutorTest extends TestCase
{
    /** @var TestHandler */
    private $logHandler;

    /** @var LoggerInterface */
    private $logger;

    /** @var ArgumentResolver[] */
    private $argumentResolvers;

    /** @var ValidatorInterface */
    private $validator;

    /**
     * @inheritDoc
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        AnnotationRegistry::registerLoader('class_exists');
    }

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

        $this->validator = (new ValidatorBuilder())
            ->enableAnnotationMapping()
            ->getValidator();
    }

    /** @test */
    public function executeWithError(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectDeprecationMessage('ups...');

        $options = DefaultHandlerOptions::createForCommandHandler();
        $closure = \Closure::fromCallable(
            static function(): void
            {
                throw new \LogicException('ups...');
            }
        );

        $processor = new MessageValidationExecutor(
            new DefaultMessageExecutor(
                $closure,
                new \SplObjectStorage(),
                $options,
                $this->argumentResolvers,
                $this->logger
            ),
            $options,
            $this->validator
        );

        wait($processor(new EmptyMessage(), new TestContext()));
    }

    /** @test */
    public function executeWithoutValidationFailedEvent(): void
    {
        $context = new TestContext();

        $options = DefaultHandlerOptions::createForCommandHandler();
        $closure = \Closure::fromCallable(
            static function(): void
            {

            }
        );

        $message = new class() {
            /** @\Symfony\Component\Validator\Constraints\NotNull */
            public $value;
        };

        $processor = new MessageValidationExecutor(
            new DefaultMessageExecutor(
                $closure,
                new \SplObjectStorage(),
                $options,
                $this->argumentResolvers,
                $this->logger
            ),
            $options,
            $this->validator
        );

        wait($processor($message, $context));

        static::assertFalse($context->isValid());
        static::assertSame(['value' => ['This value should not be null.']], $context->violations());
    }

    /** @test */
    public function deliveryValidationFailedMessage(): void
    {
        $context = new TestContext();

        $options = DefaultHandlerOptions::createForCommandHandler();
        $options = $options->withDefaultValidationFailedEvent(TestMessageValidationFailed::class);

        $closure = \Closure::fromCallable(
            static function(): void
            {

            }
        );

        $message = new class() {
            /** @\Symfony\Component\Validator\Constraints\NotNull */
            public $value;
        };

        $processor = new MessageValidationExecutor(
            new DefaultMessageExecutor(
                $closure,
                new \SplObjectStorage(),
                $options,
                $this->argumentResolvers,
                $this->logger
            ),
            $options,
            $this->validator
        );

        wait($processor($message, $context));

        static::assertCount(1, $context->messages);

        static::assertArrayHasKey(TestMessageValidationFailed::class, $context->messages);

        /** @var TestMessageValidationFailed $event */
        $event = $context->messages[TestMessageValidationFailed::class];

        static::assertSame(['value' => ['This value should not be null.']], $event->violations());
    }
}
