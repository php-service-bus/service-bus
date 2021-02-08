<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <contacts@desperado.dev>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 0);

namespace ServiceBus\MessageExecutor;

use ServiceBus\Common\MessageExecutor\MessageExecutor;
use ServiceBus\Common\MessageExecutor\MessageExecutorFactory;
use ServiceBus\Common\MessageHandler\MessageHandler;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\ValidatorBuilder;

/**
 *
 */
final class DefaultMessageExecutorFactory implements MessageExecutorFactory
{
    /**
     * @psalm-var array<string, \ServiceBus\ArgumentResolvers\ArgumentResolver>
     *
     * @var \ServiceBus\ArgumentResolvers\ArgumentResolver[]
     */
    private $argumentResolvers;

    /**
     * @var ValidatorInterface
     */
    private $validator;

    /**
     * @psalm-param array<string, \ServiceBus\ArgumentResolvers\ArgumentResolver> $argumentResolvers
     */
    public function __construct(array $argumentResolvers, ?ValidatorInterface $validator = null)
    {
        if($validator === null)
        {
            /** @psalm-suppress TooManyArguments */
            $validator = (new ValidatorBuilder())
                ->enableAnnotationMapping(true)
                ->getValidator();
        }

        $this->argumentResolvers = $argumentResolvers;
        $this->validator         = $validator;
    }

    public function create(MessageHandler $messageHandler): MessageExecutor
    {
        /** @var \ServiceBus\Services\Configuration\DefaultHandlerOptions $options */
        $options = $messageHandler->options;

        $messageExecutor = new DefaultMessageExecutor(
            handlerHash: $messageHandler->hash(),
            closure: $messageHandler->closure,
            arguments: $messageHandler->arguments,
            options: $options,
            argumentResolvers: $this->argumentResolvers
        );

        if($options->validationEnabled)
        {
            $messageExecutor = new MessageValidationExecutor(
                executor: $messageExecutor,
                options: $options,
                validator: $this->validator
            );
        }

        if($options->executionTimeout !== null)
        {
            $messageExecutor = new TimeLimitedExecutor(
                executor: $messageExecutor,
                options: $options
            );
        }

        return $messageExecutor;
    }
}
