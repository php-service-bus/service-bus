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
    /** @var Reader */
    private $annotationReader;

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
            $handlerAnnotation = $annotation->annotation;

            if (
                ($handlerAnnotation instanceof CommandHandler) === false &&
                ($handlerAnnotation instanceof EventListener) === false
            ) {
                continue;
            }

            /**
             * @var CommandHandler|EventListener $handlerAnnotation
             * @var \ReflectionMethod            $handlerReflectionMethod
             */
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
                $this->createOptions($handlerAnnotation, $isCommandHandler),
                $handlerAnnotation->description
            );

            $factoryMethod = $isCommandHandler === true ? 'createCommandHandler' : 'createEventListener';

            /** @var ServiceMessageHandler $serviceMessageHandler */
            $serviceMessageHandler = ServiceMessageHandler::{$factoryMethod}($handler);

            $collection->attach($serviceMessageHandler);
        }

        return $collection;
    }

    /**
     * Create options.
     *
     * @throws \ServiceBus\Services\Exceptions\InvalidEventType
     */
    private function createOptions(ServicesAnnotationsMarker $annotation, bool $isCommandHandler): DefaultHandlerOptions
    {
        /** @var CommandHandler|EventListener $annotation */
        $factoryMethod = true === $isCommandHandler ? 'createForCommandHandler' : 'createForEventListener';

        /** @var DefaultHandlerOptions $options */
        $options = DefaultHandlerOptions::{$factoryMethod}($annotation->description);

        if ($annotation->validate === true)
        {
            $options = $options->enableValidation($annotation->groups);
        }

        if ((string) $annotation->defaultValidationFailedEvent !== '')
        {
            /**
             * @psalm-suppress TypeCoercion
             * @psalm-suppress PossiblyNullArgument
             */
            $options = $options->withDefaultValidationFailedEvent($annotation->defaultValidationFailedEvent);
        }

        if ((string) $annotation->defaultThrowableEvent !== '')
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
     */
    private function extractMessageClass(array $parameters): string
    {
        if (\count($parameters) === 0)
        {
            throw InvalidHandlerArguments::emptyArguments();
        }

        /** @var \ReflectionParameter $firstArgument */
        $firstArgument = $parameters[0];

        if ($firstArgument->getType() !== null)
        {
            /** @var \ReflectionNamedType $type */
            $type = $firstArgument->getType();

            /** @psalm-var class-string $className */
            $className = $type->getName();

            /** @psalm-suppress RedundantConditionGivenDocblockType */
            if (\class_exists($className) === true)
            {
                return $className;
            }
        }

        throw InvalidHandlerArguments::invalidFirstArgument();
    }
}
