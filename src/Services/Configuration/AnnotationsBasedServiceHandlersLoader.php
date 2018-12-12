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

namespace Desperado\ServiceBus\Services\Configuration;

use Desperado\ServiceBus\Infrastructure\AnnotationsReader\Annotation;
use Desperado\ServiceBus\Infrastructure\AnnotationsReader\AnnotationCollection;
use Desperado\ServiceBus\Infrastructure\AnnotationsReader\AnnotationsReader;
use Desperado\ServiceBus\Infrastructure\AnnotationsReader\DefaultAnnotationsReader;
use Desperado\ServiceBus\MessageHandlers\Handler;
use Desperado\ServiceBus\MessageHandlers\HandlerCollection;
use Desperado\ServiceBus\MessageHandlers\HandlerOptions;
use Desperado\ServiceBus\Services\Annotations\CommandHandler;
use Desperado\ServiceBus\Services\Annotations\EventListener;
use Desperado\ServiceBus\Services\Annotations\ServicesAnnotationsMarker;

/**
 * Getting a list of command and event handlers
 */
final class AnnotationsBasedServiceHandlersLoader implements ServiceHandlersLoader
{
    /**
     * @var AnnotationsReader
     */
    private $annotationReader;

    /**
     * @param AnnotationsReader $annotationReader
     */
    public function __construct(AnnotationsReader $annotationReader = null)
    {
        $this->annotationReader = $annotationReader ?? new DefaultAnnotationsReader();
    }

    /**
     * @inheritdoc
     */
    public function load(object $service): HandlerCollection
    {
        $collection = new HandlerCollection();

        /** @var \Desperado\ServiceBus\Infrastructure\AnnotationsReader\Annotation $annotation */
        foreach($this->loadMethodLevelAnnotations($service) as $annotation)
        {
            /** @var CommandHandler|EventListener $handlerAnnotation */
            $handlerAnnotation = $annotation->annotationObject();

            /** @var \ReflectionMethod $handlerReflectionMethod */
            $handlerReflectionMethod = $annotation->reflectionMethod();

            $factoryMethod = $handlerAnnotation instanceof CommandHandler ? 'commandHandler' : 'eventListener';

            /**
             * @var Handler                      $handler
             * @var CommandHandler|EventListener $handlerAnnotation
             */
            $handler = Handler::{$factoryMethod}(
                $this->createOptions($handlerAnnotation),
                $handlerReflectionMethod
            );

            $collection->push($handler);
        }

        return $collection;
    }

    /**
     * Create options
     *
     * @param ServicesAnnotationsMarker $annotation
     *
     * @return HandlerOptions
     */
    private function createOptions(ServicesAnnotationsMarker $annotation): HandlerOptions
    {
        /** @var CommandHandler|EventListener $annotation */

        $options = new HandlerOptions();

        if(true === $annotation->validate)
        {
            $options->enableValidation($annotation->groups);
        }

        if('' !== (string) $annotation->defaultValidationFailedEvent)
        {
            $options->useDefaultValidationFailedEvent((string) $annotation->defaultValidationFailedEvent);
        }

        if('' !== (string) $annotation->defaultThrowableEvent)
        {
            $options->useDefaultThrowableEvent((string) $annotation->defaultThrowableEvent);
        }

        return $options;
    }

    /**
     * Load a list of annotations for message handlers
     *
     * @param object $service
     *
     * @return AnnotationCollection
     */
    private function loadMethodLevelAnnotations(object $service): AnnotationCollection
    {
        return $this->annotationReader
            ->extract(\get_class($service))
            ->filter(
                static function(Annotation $annotation): ?Annotation
                {
                    if($annotation->annotationObject() instanceof ServicesAnnotationsMarker)
                    {
                        return $annotation;
                    }

                    return null;
                }
            )
            ->methodLevelAnnotations();
    }
}
