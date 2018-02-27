<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Tests\Services;

use Desperado\Infrastructure\Bridge\AnnotationsReader\AnnotationsReaderInterface;
use Desperado\Infrastructure\Bridge\AnnotationsReader\DoctrineAnnotationsReader;
use Desperado\Infrastructure\Bridge\Router\FastRouterBridge;
use Desperado\ServiceBus\Services\AnnotationsExtractor;
use Desperado\ServiceBus\Services\AutowiringServiceLocator;
use Desperado\ServiceBus\Services\Handlers\MessageHandlerData;
use Desperado\ServiceBus\ServiceInterface;
use Desperado\ServiceBus\Tests\Services\Stabs;
use Desperado\ServiceBus\Tests\TestApplicationContext;
use Desperado\ServiceBus\Tests\TestContainer;
use PHPUnit\Framework\TestCase;
use Desperado\ServiceBus\Annotations;
use Psr\Log\NullLogger;

/**
 *
 */
class AnnotationsExtractorTest extends TestCase
{
    /**
     * @var AnnotationsReaderInterface
     */
    private $annotationsReader;

    /**
     * @var AutowiringServiceLocator
     */
    private $autowiringServiceLocator;

    /**
     * @var AnnotationsExtractor
     */
    private $extractor;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->autowiringServiceLocator = new AutowiringServiceLocator(new TestContainer(), []);
        $this->annotationsReader = new DoctrineAnnotationsReader();
        $this->extractor = new AnnotationsExtractor(
            $this->annotationsReader,
            $this->autowiringServiceLocator,
            new FastRouterBridge(),
            new NullLogger()
        );
    }

    /**
     * @inheritdoc
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->annotationsReader, $this->autowiringServiceLocator, $this->extractor);
    }

    /**
     * @test
     *
     * @return void
     */
    public function successExtractServiceLoggerChannel(): void
    {
        $channel = $this->extractor->extractServiceLoggerChannel(new Stabs\CorrectServiceWithHandlers());

        static::assertNotEmpty($channel);
        static::assertEquals('test', $channel);
    }

    /**
     * @test
     *
     * @return void
     */
    public function successExtractHandlers(): void
    {
        $handlers = $this->extractor->extractHandlers(new Stabs\CorrectServiceWithHandlers());

        static::assertCount(4, $handlers);

        /** @var MessageHandlerData $commandHandler */
        $commandHandler = \iterator_to_array($handlers->getIterator())[0];

        static::assertEquals(Stabs\TestServiceCommand::class, $commandHandler->getMessageClassNamespace());
        static::assertEmpty($commandHandler->getAutowiringServices());
        static::assertEmpty($commandHandler->getExecutionOptions()->getLoggerChannel());
        static::assertInstanceOf(\Closure::class, $commandHandler->getMessageHandler());

        /** @var MessageHandlerData $eventHandler */
        $eventHandler = \iterator_to_array($handlers->getIterator())[1];

        static::assertEquals(Stabs\TestServiceEvent::class, $eventHandler->getMessageClassNamespace());
        static::assertEmpty($eventHandler->getAutowiringServices());
        static::assertEquals('eventLogChannel', $eventHandler->getExecutionOptions()->getLoggerChannel());
        static::assertInstanceOf(\Closure::class, $eventHandler->getMessageHandler());
    }

    /**
     * @test
     * @expectedException \Desperado\ServiceBus\Services\Exceptions\ServiceClassAnnotationNotFoundException
     * @expectedExceptionMessage The class level annotation is not found in the
     *                           "Desperado\ServiceBus\Tests\Services\Stabs\ServiceWithoutClassAnnotation" service. It
     *                           is necessary to add the annotation "Desperado\ServiceBus\Annotations\Service"
     *
     * @return void
     */
    public function classAnnotationNotSpecified(): void
    {
        $this->extractor->extractServiceLoggerChannel(new Stabs\ServiceWithoutClassAnnotation());
    }

    /**
     * @test
     *
     * @return void
     */
    public function successExtractWithAutoWiringServices(): void
    {
        $container = new TestContainer(['some_service_key' => new Stabs\SomeAutoWiringProvider()]);

        $autowiringServiceLocator = new AutowiringServiceLocator(
            $container, [Stabs\SomeAutoWiringProvider::class => 'some_service_key']
        );

        $extractor = new AnnotationsExtractor(
            $this->annotationsReader,
            $autowiringServiceLocator,
            new FastRouterBridge(),
            new NullLogger()
        );

        $handlers = $extractor->extractHandlers(
            new class() implements ServiceInterface
            {
                /**
                 * @Annotations\Services\CommandHandler()
                 *
                 * @param Stabs\TestServiceCommand     $command
                 * @param TestApplicationContext       $context
                 * @param Stabs\SomeAutoWiringProvider $autoWiringProvider
                 *
                 * @return void
                 */
                public function executeTestServiceCommand(
                    Stabs\TestServiceCommand $command,
                    TestApplicationContext $context,
                    Stabs\SomeAutoWiringProvider $autoWiringProvider
                ): void
                {

                }
            }
        );

        /** @var MessageHandlerData $commandHandler */
        $commandHandler = \iterator_to_array($handlers->getIterator())[0];

        static::assertEquals(Stabs\TestServiceCommand::class, $commandHandler->getMessageClassNamespace());
        static::assertNotEmpty($commandHandler->getAutowiringServices());
        static::assertCount(1, $commandHandler->getAutowiringServices());
        static::assertEmpty($commandHandler->getExecutionOptions()->getLoggerChannel());
        static::assertInstanceOf(\Closure::class, $commandHandler->getMessageHandler());
        static::assertInstanceOf(Stabs\SomeAutoWiringProvider::class, $commandHandler->getAutowiringServices()[0]);
    }

    /**
     * @test
     * @expectedException \Desperado\ServiceBus\Services\Exceptions\InvalidHandlerArgumentException
     * @expectedExceptionMessage The service for the specified class
     *                           ("Desperado\ServiceBus\Tests\Services\Stabs\SomeAutoWiringProvider") was not described
     *                           in the dependency container
     *
     * @return void
     */
    public function failedExtractWithNonRegisteredAutoWiringService(): void
    {
        $this->extractor->extractHandlers(
            new class() implements ServiceInterface
            {
                /**
                 * @Annotations\Services\CommandHandler()
                 *
                 * @param Stabs\TestServiceCommand     $command
                 * @param TestApplicationContext       $context
                 * @param Stabs\SomeAutoWiringProvider $autoWiringProvider
                 *
                 * @return void
                 */
                public function executeTestServiceCommand(
                    Stabs\TestServiceCommand $command,
                    TestApplicationContext $context,
                    Stabs\SomeAutoWiringProvider $autoWiringProvider
                ): void
                {

                }
            }
        );
    }

    /**
     * @test
     * @expectedException \Desperado\ServiceBus\Services\Exceptions\InvalidHandlerArgumentException
     * @expectedExceptionMessage You have requested a non-existent service "some_service_key".
     *
     * @return void
     */
    public function failedExtractWithIncorrectRegisteredAutoWiringService(): void
    {
        $container = new TestContainer();
        $autowiringServiceLocator = new AutowiringServiceLocator(
            $container, [Stabs\SomeAutoWiringProvider::class => 'some_service_key']
        );

        $extractor = new AnnotationsExtractor(
            $this->annotationsReader,
            $autowiringServiceLocator,
            new FastRouterBridge(),
            new NullLogger()
        );

        $extractor->extractHandlers(
            new class() implements ServiceInterface
            {
                /**
                 * @Annotations\Services\CommandHandler()
                 *
                 * @param Stabs\TestServiceCommand     $command
                 * @param TestApplicationContext       $context
                 * @param Stabs\SomeAutoWiringProvider $autoWiringProvider
                 *
                 * @return void
                 */
                public function executeTestServiceCommand(
                    Stabs\TestServiceCommand $command,
                    TestApplicationContext $context,
                    Stabs\SomeAutoWiringProvider $autoWiringProvider
                ): void
                {

                }
            }
        );
    }

    /**
     * @test
     * @expectedException \Desperado\ServiceBus\Services\Exceptions\InvalidHandlerArgumentException
     * @expectedExceptionMessage The 2 argument to the handler
     *                           "Desperado\ServiceBus\Tests\Services\Stabs\TestServiceIncorrectAutoWiringArgument:executeTestServiceCommand"
     *                           should be of the type "object"
     *
     * @return void
     */
    public function failedExtractWithIncorrectAutoWiringArgType(): void
    {
        $this->extractor->extractHandlers(
            new class() implements ServiceInterface
            {
                /**
                 * @Annotations\Services\CommandHandler()
                 *
                 * @param Stabs\TestServiceCommand $command
                 * @param TestApplicationContext   $context
                 * @param string                   $someParameter
                 *
                 * @return void
                 */
                public function executeTestServiceCommand(
                    Stabs\TestServiceCommand $command,
                    TestApplicationContext $context,
                    string $someParameter
                ): void
                {

                }
            }
        );
    }

    /**
     * @test
     * @expectedException \Desperado\ServiceBus\Services\Exceptions\InvalidHandlerArgumentsCountException
     *
     * @return void
     */
    public function failedExtractWithEmptyHandlerArgs(): void
    {
        $this->extractor->extractHandlers(
            new class() implements ServiceInterface
            {
                /**
                 * @Annotations\Services\CommandHandler()
                 *
                 * @return void
                 */
                public function executeTestServiceCommand(): void
                {

                }
            }
        );
    }

    /**
     * @test
     * @expectedException \Desperado\ServiceBus\Services\Exceptions\InvalidHandlerArgumentException
     *
     * @return void
     */
    public function failedExtractWithWrongHandlerArgumentsOrder(): void
    {
        $this->extractor->extractHandlers(
            new class() implements ServiceInterface
            {
                /**
                 * @Annotations\Services\CommandHandler()
                 *
                 * @param TestApplicationContext   $context
                 * @param Stabs\TestServiceCommand $command
                 *
                 * @return void
                 */
                public function executeTestServiceCommand(
                    TestApplicationContext $context,
                    Stabs\TestServiceCommand $command
                ): void
                {

                }
            }
        );
    }

    /**
     * @test
     * @expectedException \Desperado\ServiceBus\Services\Exceptions\InvalidHandlerArgumentException
     *
     * @return void
     */
    public function failedExtractWithMissedContext(): void
    {
        $this->extractor->extractHandlers(
            new class() implements ServiceInterface
            {
                /**
                 * @Annotations\Services\CommandHandler()
                 *
                 * @param Stabs\TestServiceCommand $command
                 * @param \stdClass                $class
                 *
                 * @return void
                 */
                public function executeTestServiceCommand(Stabs\TestServiceCommand $command, \stdClass $class): void
                {

                }
            }
        );
    }

    /**
     * @test
     * @expectedException \Desperado\ServiceBus\Services\Exceptions\IncorrectReturnTypeDeclarationException
     *
     * @return void
     */
    public function failedExtractWithWrongReturnDeclarationType(): void
    {
        $this->extractor->extractHandlers(
            new class() implements ServiceInterface
            {
                /**
                 * @Annotations\Services\CommandHandler()
                 *
                 * @param Stabs\TestServiceCommand $command
                 * @param TestApplicationContext   $context
                 *
                 * @return string
                 */
                public function executeTestServiceCommand(
                    Stabs\TestServiceCommand $command,
                    TestApplicationContext $context
                ): string
                {

                }
            }
        );
    }
}
