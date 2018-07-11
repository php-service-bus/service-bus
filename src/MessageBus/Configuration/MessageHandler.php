<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\MessageBus\Configuration;

use Desperado\Contracts\Common\Message;

/**
 * Message handler data
 */
final class MessageHandler
{
    private const TYPE_COMMAND_HANDLER = 'commandHandler';
    private const TYPE_EVENT_LISTENER  = 'eventListener';

    /**
     * Handler type
     *
     * @var string
     */
    private $type;

    /**
     * Execution options
     *
     * @var MessageHandlerOptions
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
     * @var MessageHandlerArgumentCollection
     */
    private $argumentCollection;

    /**
     * Handler return declaration
     *
     * @var MessageHandlerReturnDeclaration|null
     */
    private $returnDeclaration;

    /**
     * @param MessageHandlerOptions $options
     * @param \ReflectionMethod     $reflectionMethod
     *
     * @return self
     */
    public static function commandHandler(MessageHandlerOptions $options, \ReflectionMethod $reflectionMethod): self
    {
        return new self(self::TYPE_COMMAND_HANDLER, $options, $reflectionMethod);
    }

    /**
     * @param MessageHandlerOptions $options
     * @param \ReflectionMethod     $reflectionMethod
     *
     * @return self
     */
    public static function eventListener(MessageHandlerOptions $options, \ReflectionMethod $reflectionMethod): self
    {
        return new self(self::TYPE_EVENT_LISTENER, $options, $reflectionMethod);
    }

    /**
     * @return string|null
     */
    public function messageClass(): ?string
    {
        foreach($this->arguments() as $argument)
        {
            /** @var MessageHandlerArgument $argument */

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
     * @param object $service
     *
     * @return \Closure
     */
    public function toClosure(object $service): \Closure
    {
        /** @var \Closure $closure */
        $closure = $this->reflectionMethod->getClosure($service);

        return $closure;
    }

    /**
     * Receive execution options
     *
     * @return MessageHandlerOptions
     */
    public function options(): MessageHandlerOptions
    {
        return $this->options;
    }

    /**
     * Its a command handler annotation
     *
     * @return bool
     */
    public function isCommandHandler(): bool
    {
        return self::TYPE_COMMAND_HANDLER === $this->type;
    }

    /**
     * Its a event listener annotation
     *
     * @return bool
     */
    public function isEventListener(): bool
    {
        return self::TYPE_EVENT_LISTENER === $this->type;
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
    public function hasParameters(): bool
    {
        return 0 !== \count($this->argumentCollection);
    }

    /**
     * Receive method parameters
     *
     * @return MessageHandlerArgumentCollection
     */
    public function arguments(): MessageHandlerArgumentCollection
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
     * @return MessageHandlerReturnDeclaration|null
     */
    public function returnTypeDeclaration(): ?MessageHandlerReturnDeclaration
    {
        return $this->returnDeclaration;
    }

    /**
     * @param string                $type
     * @param MessageHandlerOptions $options
     * @param \ReflectionMethod     $reflectionMethod
     */
    private function __construct(string $type, MessageHandlerOptions $options, \ReflectionMethod $reflectionMethod)
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
     * @return MessageHandlerArgumentCollection
     */
    private static function extractArguments(\ReflectionMethod $reflectionMethod): MessageHandlerArgumentCollection
    {
        $argumentCollection = new MessageHandlerArgumentCollection();

        foreach($reflectionMethod->getParameters() as $parameter)
        {
            $argumentCollection->push(new MessageHandlerArgument($parameter));
        }

        return $argumentCollection;
    }

    /**
     * @param \ReflectionMethod $reflectionMethod
     *
     * @return MessageHandlerReturnDeclaration|null
     */
    private static function extractReturnDeclaration(\ReflectionMethod $reflectionMethod): ?MessageHandlerReturnDeclaration
    {
        if(null !== $reflectionMethod->getReturnType())
        {
            /** @var \ReflectionType $returnDeclaration */
            $returnDeclaration = $reflectionMethod->getReturnType();

            return new MessageHandlerReturnDeclaration($returnDeclaration);
        }

        return null;
    }
}
