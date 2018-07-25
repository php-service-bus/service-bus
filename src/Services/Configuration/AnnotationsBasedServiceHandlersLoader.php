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

use Desperado\ServiceBus\AnnotationsReader\Annotation;
use Desperado\ServiceBus\AnnotationsReader\AnnotationCollection;
use Desperado\ServiceBus\AnnotationsReader\AnnotationsReader;
use Desperado\ServiceBus\MessageBus\MessageHandler\Handler;
use Desperado\ServiceBus\MessageBus\MessageHandler\HandlerCollection;
use Desperado\ServiceBus\MessageBus\MessageHandler\HandlerOptions;
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
    public function __construct(AnnotationsReader $annotationReader)
    {
        $this->annotationReader = $annotationReader;
    }

    /**
     * @inheritdoc
     */
    public function load(object $service): HandlerCollection
    {
        $collection = new HandlerCollection();

        foreach($this->loadMethodLevelAnnotations($service) as $annotation)
        {
            /**
             * @var \Desperado\ServiceBus\AnnotationsReader\Annotation $annotation
             * @var CommandHandler|EventListener                       $handlerAnnotation
             */
            $handlerAnnotation = $annotation->annotationObject();

            /** @var @var \ReflectionMethod $handlerReflectionMethod $handlerReflectionMethod */
            $handlerReflectionMethod = $annotation->reflectionMethod();

            $factoryMethod = $handlerAnnotation instanceof CommandHandler ? 'commandHandler' : 'eventListener';

            $collection->push(
                Handler::{$factoryMethod}(
                    $this->createOptions($handlerAnnotation),
                    $handlerReflectionMethod
                )
            );
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

        if(true === $annotation->validationEnabled())
        {
            $options->enableValidation($annotation->validationGroups());
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
