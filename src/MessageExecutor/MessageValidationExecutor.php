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

use Amp\Failure;
use Amp\Promise;
use Psr\Log\LogLevel;
use ServiceBus\Common\Context\ServiceBusContext;
use ServiceBus\Common\MessageExecutor\MessageExecutor;
use function ServiceBus\Common\invokeReflectionMethod;
use ServiceBus\Common\Messages\Message;
use ServiceBus\Services\Configuration\DefaultHandlerOptions;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

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
     * @var DefaultHandlerOptions
     */
    private $options;

    /**
     * @param MessageExecutor       $executor
     * @param DefaultHandlerOptions $options
     * @param ValidatorInterface    $validator
     */
    public function __construct(MessageExecutor $executor, DefaultHandlerOptions $options, ValidatorInterface $validator)
    {
        $this->executor  = $executor;
        $this->options   = $options;
        $this->validator = $validator;
    }

    /**
     * @inheritdoc
     */
    public function __invoke(Message $message, ServiceBusContext $context): Promise
    {
        try
        {
            /** @var ConstraintViolationList $violations */
            $violations = $this->validator->validate($message, null, $this->options->validationGroups);
        }
        catch(\Throwable $throwable)
        {
            return new Failure($throwable);
        }

        if(0 !== \count($violations))
        {
            self::bindViolations($violations, $context);

            /** If a validation error event class is specified, then we abort the execution */
            if(null !== $this->options->defaultValidationFailedEvent)
            {
                $context->logContextMessage(
                    'Error validation, sending an error event and stopping message processing',
                    ['eventClass' => $this->options->defaultValidationFailedEvent],
                    LogLevel::DEBUG
                );

                return self::publishViolations((string) $this->options->defaultValidationFailedEvent, $context);
            }
        }

        return ($this->executor)($message, $context);
    }

    /**
     * Publish failed event
     *
     * @param string            $eventClass
     * @param ServiceBusContext $context
     *
     * @return Promise
     */
    private static function publishViolations(string $eventClass, ServiceBusContext $context): Promise
    {
        /**
         * @noinspection VariableFunctionsUsageInspection
         * @var \ServiceBus\Services\Contracts\ValidationFailedEvent $event
         */
        $event = \forward_static_call_array([$eventClass, 'create'], [$context->traceId(), $context->violations()]);

        return $context->delivery($event);
    }

    /**
     * Bind violations to context
     *
     * @param ConstraintViolationList $violations
     * @param ServiceBusContext       $context
     *
     * @return void
     */
    private static function bindViolations(ConstraintViolationList $violations, ServiceBusContext $context): void
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
