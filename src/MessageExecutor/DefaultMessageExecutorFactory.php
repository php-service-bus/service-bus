<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <contacts@desperado.dev>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types=0);

namespace ServiceBus\MessageExecutor;

use ServiceBus\ArgumentResolver\ChainArgumentResolver;
use ServiceBus\Common\MessageExecutor\MessageExecutor;
use ServiceBus\Common\MessageExecutor\MessageExecutorFactory;
use ServiceBus\Common\MessageHandler\MessageHandler;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\ValidatorBuilder;

final class DefaultMessageExecutorFactory implements MessageExecutorFactory
{
    /**
     * @var ChainArgumentResolver
     */
    private $argumentResolver;

    /**
     * @var ValidatorInterface
     */
    private $validator;

    public function __construct(ChainArgumentResolver $argumentResolver, ?ValidatorInterface $validator = null)
    {
        if ($validator === null)
        {
            /** @psalm-suppress TooManyArguments */
            $validator = (new ValidatorBuilder())
                ->enableAnnotationMapping()
                ->getValidator();
        }

        $this->argumentResolver = $argumentResolver;
        $this->validator        = $validator;
    }

    public function create(MessageHandler $messageHandler): MessageExecutor
    {
        /** @var \ServiceBus\Services\Configuration\DefaultHandlerOptions $options */
        $options = $messageHandler->options;

        /** @psalm-var non-empty-string $handlerHash */
        $handlerHash = \sha1(
            \sprintf('%s:%s', $messageHandler->messageClass, $messageHandler->methodName)
        );

        $messageExecutor = new DefaultMessageExecutor(
            handlerHash: $handlerHash,
            closure: $messageHandler->closure,
            arguments: $messageHandler->arguments,
            options: $options,
            argumentResolver: $this->argumentResolver
        );

        if ($options->validationEnabled)
        {
            $messageExecutor = new MessageValidationExecutor(
                executor: $messageExecutor,
                options: $options,
                validator: $this->validator
            );
        }

        if ($options->executionTimeout !== null)
        {
            $messageExecutor = new TimeLimitedExecutor(
                executor: $messageExecutor,
                options: $options
            );
        }

        return $messageExecutor;
    }
}
