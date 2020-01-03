<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\MessageExecutor;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
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

    /** @var ValidatorInterface */
    private $validator;

    /** @var LoggerInterface */
    private $logger;

    /**
     * @psalm-param array<string, \ServiceBus\ArgumentResolvers\ArgumentResolver> $argumentResolvers
     */
    public function __construct(
        array $argumentResolvers,
        ?LoggerInterface $logger = null,
        ?ValidatorInterface $validator = null
    ) {
        if (null === $validator)
        {
            $validator = (new ValidatorBuilder())
                ->enableAnnotationMapping()
                ->getValidator();
        }

        $this->argumentResolvers = $argumentResolvers;
        $this->logger            = $logger ?? new NullLogger();
        $this->validator         = $validator;
    }

    /**
     * {@inheritdoc}
     */
    public function create(MessageHandler $messageHandler): MessageExecutor
    {
        /** @var \ServiceBus\Services\Configuration\DefaultHandlerOptions $options */
        $options = $messageHandler->options;

        $messageExecutor = new DefaultMessageExecutor(
            $messageHandler->closure,
            $messageHandler->arguments,
            $options,
            $this->argumentResolvers,
            $this->logger
        );

        if ($options->validationEnabled === true)
        {
            $messageExecutor = new MessageValidationExecutor($messageExecutor, $options, $this->validator);
        }

        return $messageExecutor;
    }
}
