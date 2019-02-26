<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation)
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

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
     * @var \ServiceBus\ArgumentResolvers\ArgumentResolver[]
     */
    private $argumentResolvers;

    /**
     * @var ValidatorInterface
     */
    private $validator;

    /**
     * @noinspection PhpDocMissingThrowsInspection
     *
     * @psalm-param  array<string, \ServiceBus\ArgumentResolvers\ArgumentResolver> $argumentResolvers
     *
     * @param \ServiceBus\ArgumentResolvers\ArgumentResolver[] $argumentResolvers
     * @param ValidatorInterface|null                          $validator
     */
    public function __construct(array $argumentResolvers, ?ValidatorInterface $validator = null)
    {
        if(null === $validator)
        {
            /** @noinspection PhpUnhandledExceptionInspection */
            $validator = (new ValidatorBuilder())
                ->enableAnnotationMapping()
                ->getValidator();
        }

        $this->argumentResolvers = $argumentResolvers;
        $this->validator         = $validator;
    }

    /**
     * @inheritDoc
     */
    public function create(MessageHandler $messageHandler): MessageExecutor
    {
        /** @var \ServiceBus\Services\Configuration\DefaultHandlerOptions $options */
        $options = $messageHandler->options;

        $messageExecutor = new DefaultMessageExecutor(
            $messageHandler->closure,
            $messageHandler->arguments,
            $options,
            $this->argumentResolvers
        );

        if(true === $options->validationEnabled)
        {
            $messageExecutor = new MessageValidationExecutor($messageExecutor, $options, $this->validator);
        }

        return $messageExecutor;
    }
}
