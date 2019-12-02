<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Services\Configuration;

use ServiceBus\AnnotationsReader\Annotation\MethodLevel;
use ServiceBus\AnnotationsReader\DoctrineReader;
use ServiceBus\AnnotationsReader\Reader;
use ServiceBus\Common\MessageHandler\MessageHandler;
use ServiceBus\Services\Annotations\CommandHandler;
use ServiceBus\Services\Annotations\EventListener;
use ServiceBus\Services\Annotations\ServicesAnnotationsMarker;
use ServiceBus\Services\Exceptions\InvalidHandlerArguments;
use ServiceBus\Services\Exceptions\UnableCreateClosure;

/**
 * Getting a list of command and event handlers.
 */
final class AnnotationsBasedServiceHandlersLoader implements ServiceHandlersLoader
{
    private Reader $annotationReader;

    public function __construct(Reader $annotationReader = null)
    {
        $this->annotationReader = $annotationReader ?? new DoctrineReader(null, ['psalm']);
    }

    /**
     * {@inheritdoc}
     */
    public function load(object $service): \SplObjectStorage
    {
        $collection = new \SplObjectStorage();

        /** @var MethodLevel $annotation */
        foreach ($this->loadMethodLevelAnnotations($service) as $annotation)
        {
            /** @var CommandHandler|EventListener $handlerAnnotation */
            $handlerAnnotation = $annotation->annotation;

            /** @var \ReflectionMethod $handlerReflectionMethod */
            $handlerReflectionMethod = $annotation->reflectionMethod;

            /** @psalm-var \Closure(object, \ServiceBus\Common\Context\ServiceBusContext):\Amp\Promise|null $closure */
            $closure = $handlerReflectionMethod->getClosure($service);

            if ($closure === null)
            {
                throw new UnableCreateClosure(
                    \sprintf(
                        'Unable to create a closure for the "%s" method',
                         $annotation->reflectionMethod->getName()
                    )
                );
            }

            $isCommandHandler = $handlerAnnotation instanceof CommandHandler;

            /**
             * @var \ReflectionMethod            $handlerReflectionMethod
             * @var MessageHandler               $handler
             * @var CommandHandler|EventListener $handlerAnnotation
             */
            $handler = new MessageHandler(
                $this->extractMessageClass($handlerReflectionMethod->getParameters()),
                $closure,
                $handlerReflectionMethod,
                $this->createOptions($handlerAnnotation, $isCommandHandler)
            );

            $factoryMethod = true === $isCommandHandler ? 'createCommandHandler' : 'createEventListener';

            /** @var ServiceMessageHandler $serviceMessageHandler */
            $serviceMessageHandler = ServiceMessageHandler::{$factoryMethod}($handler);

            $collection->attach($serviceMessageHandler);
        }

        return $collection;
    }

    /**
     * Create options.
     *
     * @param ServicesAnnotationsMarker $annotation
     * @param bool                      $isCommandHandler
     *
     * @throws \ServiceBus\Services\Exceptions\InvalidEventType
     *
     * @return DefaultHandlerOptions
     */
    private function createOptions(ServicesAnnotationsMarker $annotation, bool $isCommandHandler): DefaultHandlerOptions
    {
        /** @var CommandHandler|EventListener $annotation */
        $factoryMethod = true === $isCommandHandler ? 'createForCommandHandler' : 'createForEventListener';

        /** @var DefaultHandlerOptions $options */
        $options = DefaultHandlerOptions::{$factoryMethod}();

        if (true === $annotation->validate)
        {
            $options = $options->enableValidation($annotation->groups);
        }

        if ('' !== (string) $annotation->defaultValidationFailedEvent)
        {
            /**
             * @psalm-suppress TypeCoercion
             * @psalm-suppress PossiblyNullArgument
             */
            $options = $options->withDefaultValidationFailedEvent($annotation->defaultValidationFailedEvent);
        }

        if ('' !== (string) $annotation->defaultThrowableEvent)
        {
            /**
             * @psalm-suppress TypeCoercion
             * @psalm-suppress PossiblyNullArgument
             */
            $options = $options->withDefaultThrowableEvent($annotation->defaultThrowableEvent);
        }

        return $options;
    }

    /**
     * Load a list of annotations for message handlers.
     *
     * @throws \ServiceBus\AnnotationsReader\Exceptions\ParseAnnotationFailed
     *
     * @return \SplObjectStorage
     */
    private function loadMethodLevelAnnotations(object $service): \SplObjectStorage
    {
        /** @psalm-var class-string $serviceClass */
        $serviceClass = \get_class($service);

        return $this->annotationReader
            ->extract($serviceClass)
            ->methodLevelCollection;
    }

    /**
     * @psalm-return class-string
     *
     * @param \ReflectionParameter[] $parameters
     *
     * @throws \ServiceBus\Services\Exceptions\InvalidHandlerArguments
     *
     * @return string
     */
    private function extractMessageClass(array $parameters): string
    {
        if (0 === \count($parameters))
        {
            throw InvalidHandlerArguments::emptyArguments();
        }

        /** @var \ReflectionParameter $firstArgument */
        $firstArgument = $parameters[0];

        if (null !== $firstArgument->getType())
        {
            /** @var \ReflectionNamedType $type */
            $type = $firstArgument->getType();

            /** @psalm-var class-string $className */
            $className = $type->getName();

            /** @psalm-suppress RedundantConditionGivenDocblockType */
            if (true === \class_exists($className))
            {
                return $className;
            }
        }

        throw InvalidHandlerArguments::invalidFirstArgument();
    }
}
