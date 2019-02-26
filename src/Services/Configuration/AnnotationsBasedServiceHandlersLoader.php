<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation)
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Services\Configuration;

use ServiceBus\AnnotationsReader\Annotation;
use ServiceBus\AnnotationsReader\AnnotationCollection;
use ServiceBus\AnnotationsReader\AnnotationsReader;
use ServiceBus\AnnotationsReader\DoctrineAnnotationsReader;
use ServiceBus\Common\MessageHandler\MessageHandler;
use ServiceBus\Services\Annotations\CommandHandler;
use ServiceBus\Services\Annotations\EventListener;
use ServiceBus\Services\Annotations\ServicesAnnotationsMarker;
use ServiceBus\Services\Exceptions\UnableCreateClosure;

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
     *
     * @throws \ServiceBus\AnnotationsReader\Exceptions\ParserConfigurationError
     */
    public function __construct(AnnotationsReader $annotationReader = null)
    {
        $this->annotationReader = $annotationReader ?? new DoctrineAnnotationsReader(null, ['psalm']);
    }

    /**
     * @inheritdoc
     */
    public function load(object $service): \SplObjectStorage
    {
        $collection = new \SplObjectStorage();

        /** @var \ServiceBus\AnnotationsReader\Annotation $annotation */
        foreach($this->loadMethodLevelAnnotations($service) as $annotation)
        {
            /** @var CommandHandler|EventListener $handlerAnnotation */
            $handlerAnnotation = $annotation->annotationObject;

            /** @var \ReflectionMethod $handlerReflectionMethod */
            $handlerReflectionMethod = $annotation->reflectionMethod;

            /** @psalm-var \Closure(object, \ServiceBus\Common\Context\ServiceBusContext):\Amp\Promise|null $closure */
            $closure = $handlerReflectionMethod->getClosure($service);

            if(null === $closure)
            {
                throw new UnableCreateClosure(
                    \sprintf(
                        'Unable to create a closure for the "%s" method',
                        $annotation->reflectionMethod ? $annotation->reflectionMethod->getName() : 'n\a'
                    )
                );
            }

            $isCommandHandler = $handlerAnnotation instanceof CommandHandler;

            /**
             * @var \ReflectionMethod            $handlerReflectionMethod
             * @var MessageHandler               $handler
             * @var CommandHandler|EventListener $handlerAnnotation
             */
            $handler = MessageHandler::create(
                $closure,
                $handlerReflectionMethod,
                $this->createOptions($handlerAnnotation, $isCommandHandler)
            );

            $factoryMethod = true === $isCommandHandler ? 'createCommandHandler' : 'createEventListener';

            /** @var ServiceMessageHandler $serviceMessageHandler */
            $serviceMessageHandler = ServiceMessageHandler::{$factoryMethod}($handler);

            $collection->attach($serviceMessageHandler);
        }

        /** @psalm-var \SplObjectStorage<\ServiceBus\Services\Configuration\ServiceMessageHandler, string> $collection */

        return $collection;
    }

    /**
     * Create options
     *
     * @param ServicesAnnotationsMarker $annotation
     *
     * @return DefaultHandlerOptions
     *
     * @throws \ServiceBus\Services\Exceptions\InvalidEventType
     */
    private function createOptions(ServicesAnnotationsMarker $annotation, bool $isCommandHandler): DefaultHandlerOptions
    {
        /** @var CommandHandler|EventListener $annotation */

        $factoryMethod = true === $isCommandHandler ? 'createForCommandHandler' : 'createForEventListener';

        /** @var DefaultHandlerOptions $options */
        $options = DefaultHandlerOptions::{$factoryMethod}();

        if(true === $annotation->validate)
        {
            $options = $options->enableValidation($annotation->groups);
        }

        if('' !== (string) $annotation->defaultValidationFailedEvent)
        {
            /** @psalm-suppress PossiblyNullArgument */
            $options = $options->withDefaultValidationFailedEvent($annotation->defaultValidationFailedEvent);
        }

        if('' !== (string) $annotation->defaultThrowableEvent)
        {
            /** @psalm-suppress PossiblyNullArgument */
            $options = $options->withDefaultThrowableEvent($annotation->defaultThrowableEvent);
        }

        return $options;
    }

    /**
     * Load a list of annotations for message handlers
     *
     * @param object $service
     *
     * @return AnnotationCollection
     *
     * @throws \ServiceBus\AnnotationsReader\Exceptions\ParseAnnotationFailed
     */
    private function loadMethodLevelAnnotations(object $service): AnnotationCollection
    {
        return $this->annotationReader
            ->extract(\get_class($service))
            ->filter(
                static function(Annotation $annotation): ?Annotation
                {
                    if($annotation->annotationObject instanceof ServicesAnnotationsMarker)
                    {
                        return $annotation;
                    }

                    return null;
                }
            )
            ->methodLevelAnnotations();
    }
}
