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

namespace Desperado\ServiceBus\MessageHandlers;

use Desperado\ServiceBus\Common\Contract\Messages\Message;

/**
 * Message handler representation
 */
final class Handler
{
    private const TYPE_COMMAND_HANDLER = 'commandHandler';
    private const TYPE_EVENT_LISTENER  = 'eventListener';
    private const TYPE_SAGA_LISTENER   = 'sagaListener';

    /**
     * Handler type
     *
     * @var string
     */
    private $type;

    /**
     * Execution options
     *
     * @var HandlerOptions
     */
    private $options;

    /**
     * Handler reflection method
     *
     * @var \ReflectionMethod
     */
    private $reflectionMethod;

    /**
     * Collection of arguments to the message handler
     *
     * @var HandlerArgumentCollection
     */
    private $argumentCollection;

    /**
     * Handler return declaration
     *
     * @var HandlerReturnDeclaration|null
     */
    private $returnDeclaration;

    /**
     * Prepared message class
     * Currently used exclusively for the configuration of sagas
     *
     * @var string|null
     */
    private $preparedMessageClass;

    /**
     * Prepared execution Closure
     * Currently used exclusively for the configuration of sagas
     *
     * @var \Closure|null
     */
    private $executionClosure;

    /**
     * @param HandlerOptions    $options
     * @param \ReflectionMethod $reflectionMethod
     *
     * @return self
     */
    public static function commandHandler(HandlerOptions $options, \ReflectionMethod $reflectionMethod): self
    {
        return new self(self::TYPE_COMMAND_HANDLER, $options, $reflectionMethod);
    }

    /**
     * @param HandlerOptions    $options
     * @param \ReflectionMethod $reflectionMethod
     *
     * @return self
     */
    public static function eventListener(HandlerOptions $options, \ReflectionMethod $reflectionMethod): self
    {
        return new self(self::TYPE_EVENT_LISTENER, $options, $reflectionMethod);
    }

    /**
     * @param string            $messageClass
     * @param \ReflectionMethod $reflectionMethod
     * @param \Closure          $executionClosure
     *
     * @return self
     */
    public static function sagaListener(
        string $messageClass,
        \ReflectionMethod $reflectionMethod,
        \Closure $executionClosure
    ): self
    {
        $self = new self(self::TYPE_SAGA_LISTENER, new HandlerOptions, $reflectionMethod);

        $self->preparedMessageClass = $messageClass;
        $self->executionClosure     = $executionClosure;

        return $self;
    }

    /**
     * Is a handler form command
     *
     * @return bool
     */
    public function isCommandHandler(): bool
    {
        return self::TYPE_COMMAND_HANDLER === $this->type;
    }

    /**
     * @return string|null
     */
    public function messageClass(): ?string
    {
        if(null !== $this->preparedMessageClass)
        {
            /** @var string $preparedMessageClass */
            $preparedMessageClass = $this->preparedMessageClass;

            return $preparedMessageClass;
        }

        /** @var HandlerArgument $argument */
        foreach($this->arguments() as $argument)
        {
            if(true === $argument->isA(Message::class))
            {
                return $argument->className();
            }
        }

        return null;
    }

    /**
     * Receive method as closure
     *
     * @param object|null $object If an object is not specified, it is assumed that the closure for the operation was
     *                            added earlier (@see Handler::$executionClosure)
     *
     * @return \Closure
     *
     * @throws \LogicException
     */
    public function toClosure(object $object = null): \Closure
    {
        if(null === $object)
        {
            if($this->executionClosure instanceof \Closure)
            {
                return $this->executionClosure;
            }

            throw new \LogicException(
                'If an object is not specified, it is assumed that the closure for the operation was added earlier (@see Handler::$executionClosure)'
            );
        }

        /** @var \Closure $closure*/
        $closure = $this->reflectionMethod->getClosure($object);

        return $closure;
    }

    /**
     * Receive execution options
     *
     * @return HandlerOptions
     */
    public function options(): HandlerOptions
    {
        return $this->options;
    }

    /**
     * Receive handler method name
     *
     * @return string
     */
    public function methodName(): string
    {
        return $this->reflectionMethod->getName();
    }

    /**
     * Handler has parameters
     *
     * @return bool
     */
    public function hasArguments(): bool
    {
        return 0 !== \count($this->argumentCollection);
    }

    /**
     * Receive method parameters
     *
     * @return HandlerArgumentCollection
     */
    public function arguments(): HandlerArgumentCollection
    {
        return $this->argumentCollection;
    }

    /**
     * Has specified return type declaration
     *
     * @return bool
     */
    public function hasReturnDeclaration(): bool
    {
        return null !== $this->returnDeclaration;
    }

    /**
     * Receive return type return declaration data
     *
     * @return HandlerReturnDeclaration|null
     */
    public function returnTypeDeclaration(): ?HandlerReturnDeclaration
    {
        return $this->returnDeclaration;
    }

    /**
     * @param string            $type
     * @param HandlerOptions    $options
     * @param \ReflectionMethod $reflectionMethod
     */
    private function __construct(string $type, HandlerOptions $options, \ReflectionMethod $reflectionMethod)
    {
        $this->type             = $type;
        $this->options          = $options;
        $this->reflectionMethod = $reflectionMethod;

        $this->argumentCollection = self::extractArguments($reflectionMethod);
        $this->returnDeclaration  = self::extractReturnDeclaration($reflectionMethod);
    }

    /**
     * @param \ReflectionMethod $reflectionMethod
     *
     * @return HandlerArgumentCollection
     */
    private static function extractArguments(\ReflectionMethod $reflectionMethod): HandlerArgumentCollection
    {
        $argumentCollection = new HandlerArgumentCollection();

        foreach($reflectionMethod->getParameters() as $parameter)
        {
            $argumentCollection->push(new HandlerArgument($parameter));
        }

        return $argumentCollection;
    }

    /**
     * @param \ReflectionMethod $reflectionMethod
     *
     * @return HandlerReturnDeclaration|null
     */
    private static function extractReturnDeclaration(\ReflectionMethod $reflectionMethod): ?HandlerReturnDeclaration
    {
        if(null !== $reflectionMethod->getReturnType())
        {
            /** @var \ReflectionType $returnDeclaration */
            $returnDeclaration = $reflectionMethod->getReturnType();

            return new HandlerReturnDeclaration($returnDeclaration);
        }

        return null;
    }
}
