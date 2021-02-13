<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <contacts@desperado.dev>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 0);

namespace ServiceBus\Services\Configuration;

use ServiceBus\AnnotationsReader\Attribute\MethodLevel;
use ServiceBus\AnnotationsReader\AttributesReader;
use ServiceBus\AnnotationsReader\Reader;
use ServiceBus\Common\MessageHandler\MessageHandler;
use ServiceBus\Services\Attributes\CommandHandler;
use ServiceBus\Services\Attributes\EventListener;
use ServiceBus\Services\Attributes\Options\HasCancellation;
use ServiceBus\Services\Attributes\Options\HasValidation;
use ServiceBus\Services\Exceptions\InvalidHandlerArguments;
use ServiceBus\Services\Exceptions\UnableCreateClosure;

/**
 *
 */
final class AttributeServiceHandlersLoader implements ServiceHandlersLoader
{
    /**
     * @var Reader
     */
    private $attributesReader;

    public function __construct(AttributesReader $attributesReader)
    {
        $this->attributesReader = $attributesReader;
    }

    public function load(object $service): \SplObjectStorage
    {
        /** @psalm-var \SplObjectStorage<ServiceMessageHandler, int> $messageProcessors */
        $messageProcessors = new \SplObjectStorage();

        foreach ($this->readMethodLevelAttributes($service) as $methodLevelAnnotation)
        {
            /** @var CommandHandler|EventListener $attribute */
            $attribute = $methodLevelAnnotation->attribute;

            $isCommandHandler = $attribute instanceof CommandHandler;
            $handlerClosure   = $this->buildClosure(
                reflectionMethod: $methodLevelAnnotation->reflectionMethod,
                service: $service
            );

            $processorDescription = $attribute->description();

            $handlerOptions = $this->createOptions(
                attribute: $attribute,
                isCommandHandler: $isCommandHandler,
                description: $processorDescription
            );

            $handler = new MessageHandler(
                messageClass: $this->extractMessageClass($methodLevelAnnotation->reflectionMethod->getParameters()),
                closure: $handlerClosure,
                reflectionMethod: $methodLevelAnnotation->reflectionMethod,
                options: $handlerOptions,
                description: $processorDescription
            );

            $factoryMethod = $isCommandHandler ? 'createCommandHandler' : 'createEventListener';

            /** @var ServiceMessageHandler $serviceMessageHandler */
            $serviceMessageHandler = ServiceMessageHandler::{$factoryMethod}($handler);

            $messageProcessors->attach($serviceMessageHandler);
        }

        return $messageProcessors;
    }

    private function createOptions(
        EventListener|CommandHandler $attribute,
        bool $isCommandHandler,
        ?string $description
    ): DefaultHandlerOptions {
        $factoryMethod = $isCommandHandler ? 'createForCommandHandler' : 'createForEventListener';

        /** @var DefaultHandlerOptions $options */
        $options = DefaultHandlerOptions::{$factoryMethod}($description);

        if ($attribute instanceof HasValidation)
        {
            $validationConfiguration = $attribute->validation();

            if ($validationConfiguration !== null)
            {
                $options = $options->enableValidation($validationConfiguration->groups);
            }
        }

        if ($attribute instanceof HasCancellation)
        {
            /** @psalm-suppress PossiblyUndefinedMethod */
            $options = $options->limitExecutionTime($attribute->cancellation()->timeout);
        }

        return $options;
    }

    /**
     * @param \ReflectionParameter[] $parameters
     *
     * @psalm-return class-string
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
            if (\class_exists($className))
            {
                return $className;
            }
        }

        throw InvalidHandlerArguments::invalidFirstArgument();
    }

    /**
     * @psalm-return \Closure(object, \ServiceBus\Common\Context\ServiceBusContext):\Amp\Promise<void>
     */
    private function buildClosure(\ReflectionMethod $reflectionMethod, object $service): \Closure
    {
        /** @psalm-var \Closure(object, \ServiceBus\Common\Context\ServiceBusContext):\Amp\Promise<void>|null $closure */
        $closure = $reflectionMethod->getClosure($service);

        // @codeCoverageIgnoreStart
        if ($closure === null)
        {
            throw new UnableCreateClosure(
                \sprintf(
                    'Unable to create a closure for the "%s" method',
                    $reflectionMethod->getName()
                )
            );
        }

        // @codeCoverageIgnoreEnd

        return $closure;
    }

    /**
     * @return MethodLevel[]
     */
    private function readMethodLevelAttributes(object $service): array
    {
        $result = [];

        $readAttributes = $this->attributesReader->extract(\get_class($service));

        /** @var MethodLevel $methodLevelAttribute */
        foreach ($readAttributes->methodLevelCollection as $methodLevelAttribute)
        {
            if ($this->supports($methodLevelAttribute->attribute))
            {
                $result[] = $methodLevelAttribute;
            }
        }

        return $result;
    }

    private function supports(object $attribute): bool
    {
        return $attribute instanceof CommandHandler || $attribute instanceof EventListener;
    }
}
