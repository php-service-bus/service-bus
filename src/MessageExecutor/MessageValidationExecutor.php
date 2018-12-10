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

namespace Desperado\ServiceBus\MessageExecutor;

use Amp\Failure;
use Amp\Promise;
use function Desperado\ServiceBus\Common\invokeReflectionMethod;
use Desperado\ServiceBus\MessageHandlers\HandlerOptions;
use Psr\Log\LogLevel;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Desperado\ServiceBus\Application\KernelContext;
use Desperado\ServiceBus\Common\Contract\Messages\Message;

/**
 * Executing message validation
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
     * Execution options
     *
     * @var HandlerOptions
     */
    private $options;

    /**
     * @param MessageExecutor    $executor
     * @param HandlerOptions     $options
     * @param ValidatorInterface $validator
     */
    public function __construct(MessageExecutor $executor, HandlerOptions $options, ValidatorInterface $validator)
    {
        $this->executor  = $executor;
        $this->options   = $options;
        $this->validator = $validator;
    }

    /**
     * @inheritdoc
     */
    public function __invoke(Message $message, KernelContext $context): Promise
    {
        try
        {
            /** @var ConstraintViolationList $violations */
            $violations = $this->validator->validate($message, null, $this->options->validationGroups());
        }
        catch(\Throwable $throwable)
        {
            return new Failure($throwable);
        }

        if(0 !== \count($violations))
        {
            self::bindViolations($violations, $context);

            /** If a validation error event class is specified, then we abort the execution */
            if(true === $this->options->hasDefaultValidationFailedEvent())
            {
                $context->logContextMessage(
                    'Error validation, sending an error event and stopping message processing',
                    ['eventClass' => $this->options->defaultValidationFailedEvent()],
                    LogLevel::DEBUG
                );

                return self::publishViolations((string) $this->options->defaultValidationFailedEvent(), $context);
            }
        }

        return ($this->executor)($message, $context);
    }

    /**
     * Publish failed event
     *
     * @param string        $eventClass
     * @param KernelContext $context
     *
     * @return Promise
     */
    private static function publishViolations(string $eventClass, KernelContext $context): Promise
    {
        /** @var \Desperado\ServiceBus\Services\Contracts\ValidationFailedEvent $event */
        $event = \forward_static_call_array([$eventClass, 'create'], [$context->traceId(), $context->violations()]);

        return $context->delivery($event);
    }

    /**
     * Bind violations to context
     *
     * @param ConstraintViolationList $violations
     * @param KernelContext           $context
     *
     * @return void
     */
    private static function bindViolations(ConstraintViolationList $violations, KernelContext $context): void
    {
        $errors = [];

        /** @var \Symfony\Component\Validator\ConstraintViolation $violation */
        foreach($violations as $violation)
        {
            $errors[$violation->getPropertyPath()][] = $violation->getMessage();
        }

        try
        {
            invokeReflectionMethod($context, 'validationFailed', $errors);
        }
            // @codeCoverageIgnoreStart
        catch(\Throwable $throwable)
        {
            /** No exceptions can happen */
        }
        // @codeCoverageIgnoreEnd
    }
}
