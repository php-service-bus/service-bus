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

namespace Desperado\ServiceBus\Sagas\Configuration;

use Desperado\ServiceBus\Infrastructure\AnnotationsReader\Annotation;
use Desperado\ServiceBus\Infrastructure\AnnotationsReader\AnnotationCollection;
use Desperado\ServiceBus\Infrastructure\AnnotationsReader\AnnotationsReader;
use Desperado\ServiceBus\Infrastructure\AnnotationsReader\DefaultAnnotationsReader;
use Desperado\ServiceBus\Common\Contract\Messages\Event;
use Desperado\ServiceBus\MessageHandlers\Handler;
use Desperado\ServiceBus\MessageHandlers\HandlerCollection;
use Desperado\ServiceBus\SagaProvider;
use Desperado\ServiceBus\Sagas\Annotations\SagaAnnotationMarker;
use Desperado\ServiceBus\Sagas\Annotations\SagaEventListener;
use Desperado\ServiceBus\Sagas\Annotations\SagaHeader;
use Desperado\ServiceBus\Sagas\Configuration\Exceptions\InvalidSagaConfiguration;
use Desperado\ServiceBus\Sagas\Exceptions\InvalidSagaEventListenerMethod;

/**
 * Annotations based saga listeners loader
 */
final class AnnotationsBasedSagaConfigurationLoader implements SagaConfigurationLoader
{
    /**
     * @var AnnotationsReader
     */
    private $annotationReader;

    /**
     * @var SagaProvider
     */
    private $sagaProvider;

    /**
     * @param SagaProvider           $sagaProvider
     * @param AnnotationsReader|null $annotationReader
     */
    public function __construct(SagaProvider $sagaProvider, AnnotationsReader $annotationReader = null)
    {
        $this->sagaProvider     = $sagaProvider;
        $this->annotationReader = $annotationReader ?? new DefaultAnnotationsReader();
    }

    /**
     * @inheritdoc
     */
    public function load(string $sagaClass): SagaConfiguration
    {
        try
        {
            $annotations = $this->annotationReader
                ->extract($sagaClass)
                ->filter(
                    static function(Annotation $annotation): ?Annotation
                    {
                        return $annotation->annotationObject() instanceof SagaAnnotationMarker ? $annotation : null;
                    }
                );

            $sagaMetadata = self::createSagaMetadata(
                $sagaClass,
                self::searchSagaHeader($sagaClass, $annotations)
            );

            $handlersCollection = new HandlerCollection();

            $this->collectSagaEventHandlers($handlersCollection, $annotations, $sagaMetadata);

            return new SagaConfiguration($sagaMetadata, $handlersCollection);
        }
        catch(InvalidSagaConfiguration $exception)
        {
            throw $exception;
        }
        catch(\Throwable $throwable)
        {
            throw new InvalidSagaConfiguration($throwable->getMessage(), $throwable->getCode(), $throwable);
        }
    }

    /**
     * Collect a collection of saga event handlers
     *
     * @param HandlerCollection    $handlerCollection
     * @param AnnotationCollection $annotationCollection
     * @param SagaMetadata         $sagaMetadata
     *
     * @return void
     *
     * @throws \Desperado\ServiceBus\Sagas\Exceptions\InvalidSagaEventListenerMethod
     * @throws \ReflectionException
     */
    private function collectSagaEventHandlers(
        HandlerCollection $handlerCollection,
        AnnotationCollection $annotationCollection,
        SagaMetadata $sagaMetadata
    ): void
    {
        $methodAnnotations = $annotationCollection->filter(
            static function(Annotation $annotation): ?Annotation
            {
                return $annotation->annotationObject() instanceof SagaEventListener ? $annotation : null;
            }
        );

        /** @var Annotation $methodAnnotation */
        foreach($methodAnnotations as $methodAnnotation)
        {
            $handlerCollection->push($this->createSagaEventHandler($methodAnnotation, $sagaMetadata));
        }
    }

    /**
     * Create a saga event handler
     *
     * @param Annotation   $annotation
     * @param SagaMetadata $sagaMetadata
     *
     * @return Handler
     *
     * @throws \Desperado\ServiceBus\Sagas\Exceptions\InvalidSagaEventListenerMethod
     * @throws \ReflectionException
     */
    private function createSagaEventHandler(Annotation $annotation, SagaMetadata $sagaMetadata): Handler
    {
        /** @var SagaEventListener $listenerAnnotation */
        $listenerAnnotation = $annotation->annotationObject();

        $listenerOptions = true === $listenerAnnotation->hasContainingIdProperty()
            ? SagaListenerOptions::withCustomContainingIdentifierProperty(
                (string) $listenerAnnotation->containingIdProperty(),
                $sagaMetadata
            )
            : SagaListenerOptions::withGlobalOptions($sagaMetadata);

        $handlerService          = new SagaEventListenerProcessor($listenerOptions, $this->sagaProvider);
        $handlerReflectionMethod = new \ReflectionMethod($handlerService, 'execute');

        /** @var \ReflectionMethod $eventListenerReflectionMethod */
        $eventListenerReflectionMethod = $annotation->reflectionMethod();

        /** @var \Closure $executionClosure */
        $executionClosure = $handlerReflectionMethod->getClosure($handlerService);

        return Handler::sagaListener(
            $this->extractEventClass($eventListenerReflectionMethod),
            $handlerReflectionMethod,
            $executionClosure
        );
    }

    /**
     * Search for an event class among method arguments
     *
     * @param \ReflectionMethod $reflectionMethod
     *
     * @return string
     *
     * @throws \Desperado\ServiceBus\Sagas\Exceptions\InvalidSagaEventListenerMethod
     */
    private function extractEventClass(\ReflectionMethod $reflectionMethod): string
    {
        $reflectionParameters = $reflectionMethod->getParameters();

        if(1 === \count($reflectionParameters))
        {
            $firstArgumentClass = true === isset($reflectionParameters[0]) && null !== $reflectionParameters[0]->getClass()
                ? $reflectionParameters[0]->getClass()
                : null;

            if(null !== $firstArgumentClass && true === $firstArgumentClass->isSubclassOf(Event::class))
            {
                /** @var \ReflectionClass $reflectionClass */
                $reflectionClass = $reflectionParameters[0]->getClass();

                return $reflectionClass->getName();
            }

            throw InvalidSagaEventListenerMethod::wrongEventArgument($reflectionMethod);
        }

        throw InvalidSagaEventListenerMethod::tooManyArguments($reflectionMethod);
    }

    /**
     * Collect metadata information
     *
     * @param string     $sagaClass
     * @param SagaHeader $sagaHeader
     *
     * @return SagaMetadata
     *
     * @throws \Desperado\ServiceBus\Sagas\Configuration\Exceptions\InvalidSagaConfiguration
     */
    private static function createSagaMetadata(string $sagaClass, SagaHeader $sagaHeader): SagaMetadata
    {
        if(
            false === $sagaHeader->hasIdClass() ||
            false === \class_exists($sagaHeader->idClass())
        )
        {
            throw new InvalidSagaConfiguration(
                \sprintf(
                    'In the meta data of the saga "%s", an incorrect value of the "idClass"', $sagaClass
                )
            );
        }

        return new SagaMetadata(
            $sagaClass,
            $sagaHeader->idClass(),
            $sagaHeader->containingIdProperty(),
            $sagaHeader->expireDateModifier()
        );
    }

    /**
     * Search saga header information
     *
     * @param string               $sagaClass
     * @param AnnotationCollection $annotationCollection
     *
     * @return SagaHeader
     *
     * @throws \Desperado\ServiceBus\Sagas\Configuration\Exceptions\InvalidSagaConfiguration
     */
    private static function searchSagaHeader(string $sagaClass, AnnotationCollection $annotationCollection): SagaHeader
    {
        /** @var \Desperado\ServiceBus\Infrastructure\AnnotationsReader\Annotation $annotation */
        foreach($annotationCollection->classLevelAnnotations() as $annotation)
        {
            $annotationObject = $annotation->annotationObject();

            if($annotationObject instanceof SagaHeader)
            {
                return $annotationObject;
            }
        }

        throw new InvalidSagaConfiguration(
            \sprintf(
                'Could not find class-level annotation "%s" in "%s"',
                SagaHeader::class,
                $sagaClass
            )
        );
    }
}
