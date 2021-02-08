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

use ServiceBus\Common\Context\ValidationViolation;
use ServiceBus\Common\Context\ValidationViolations;
use ServiceBus\Common\EntryPoint\Retry\RetryStrategy;
use function ServiceBus\Common\invokeReflectionMethod;
use Amp\Promise;
use ServiceBus\Common\Context\ServiceBusContext;
use ServiceBus\Common\MessageExecutor\MessageExecutor;
use ServiceBus\Services\Configuration\DefaultHandlerOptions;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Executing message validation.
 */
final class MessageValidationExecutor implements MessageExecutor
{
    /**
     * @var MessageExecutor
     */
    private $executor;

    /**
     * @var ValidatorInterface
     */
    private $validator;

    /**
     * @var DefaultHandlerOptions
     */
    private $options;

    public function __construct(
        MessageExecutor $executor,
        DefaultHandlerOptions $options,
        ValidatorInterface $validator
    )
    {
        $this->executor  = $executor;
        $this->options   = $options;
        $this->validator = $validator;
    }

    public function id(): string
    {
        return $this->executor->id();
    }

    public function retryStrategy(): ?RetryStrategy
    {
        return $this->executor->retryStrategy();
    }

    public function __invoke(object $message, ServiceBusContext $context): Promise
    {
        /** @var ConstraintViolationList $violations */
        $violations = $this->validator->validate(
            value: $message,
            groups: $this->options->validationGroups
        );

        if(\count($violations) !== 0)
        {
            self::bindViolations($violations, $context);
        }

        return ($this->executor)($message, $context);
    }

    /**
     * Bind violations to context.
     */
    private static function bindViolations(ConstraintViolationList $violations, ServiceBusContext $context): void
    {
        /** @var ValidationViolation[] $errors */
        $errors = [];

        /** @var \Symfony\Component\Validator\ConstraintViolation $violation */
        foreach($violations as $violation)
        {
            $errors[] = new ValidationViolation($violation->getPropertyPath(), (string) $violation->getMessage());
        }

        try
        {
            invokeReflectionMethod(
                object: $context,
                methodName: 'validationFailed',
                parameters: new ValidationViolations($errors)
            );
        }
            // @codeCoverageIgnoreStart
        catch(\Throwable)
        {
            /** No exceptions can happen */
        }
        // @codeCoverageIgnoreEnd
    }
}
