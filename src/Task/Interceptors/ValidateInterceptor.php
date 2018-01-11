<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Task\Interceptors;

use Desperado\Domain\Message\AbstractMessage;
use Desperado\ServiceBus\Services\Handlers;
use Desperado\ServiceBus\MessageProcessor\AbstractExecutionContext;
use Desperado\ServiceBus\Task\Interceptors\Contract\MessageValidationFailedEvent;
use Desperado\ServiceBus\Task\TaskInterface;
use Psr\Log\LogLevel;
use React\Promise\PromiseInterface;
use Symfony\Component\Validator;

/**
 * Validate message before execution
 */
class ValidateInterceptor implements TaskInterface
{
    /**
     * Executed task
     *
     * @var TaskInterface
     */
    private $task;

    /**
     * Validation handler
     *
     * @var Validator\Validator\ValidatorInterface
     */
    private $validator;

    /**
     * @param TaskInterface                          $task
     * @param Validator\Validator\ValidatorInterface $validator
     */
    public function __construct(TaskInterface $task, Validator\Validator\ValidatorInterface $validator)
    {
        $this->task = $task;
        $this->validator = $validator;
    }

    /**
     * @inheritdoc
     */
    public function getOptions(): Handlers\Messages\AbstractMessageExecutionParameters
    {
        return $this->task->getOptions();
    }

    /**
     * @inheritdoc
     */
    public function __invoke(
        AbstractMessage $message,
        AbstractExecutionContext $context,
        array $additionalArguments = []
    ): PromiseInterface
    {
        $violations = new Validator\ConstraintViolationList();

        $this->validateMessage($message, $violations);

        /** All constraints passed */
        if(0 === $violations->count())
        {
            return \call_user_func_array($this->task, [$message, $context, $additionalArguments]);
        }

        $this->processViolations($message, $violations, $context);

        return null;
    }

    /**
     * Handle violations
     *
     * @param AbstractMessage                            $message
     * @param Validator\ConstraintViolationListInterface $violations
     * @param AbstractExecutionContext                   $context
     *
     * @return void
     */
    private function processViolations(
        AbstractMessage $message,
        Validator\ConstraintViolationListInterface $violations,
        AbstractExecutionContext $context
    ): void
    {
        $errors = [];

        foreach($violations as $violation)
        {
            $this->logViolation($message, $violation, $context);

            $errors[$violation->getPropertyPath()][] = $violation->getMessage();
        }

        $this->deliveryErrors($message, $errors, $context);
    }

    /**
     * Publish events with violations
     *
     * @param AbstractMessage          $message
     * @param array                    $errors
     * @param AbstractExecutionContext $context
     *
     * @return void
     */
    protected function deliveryErrors(AbstractMessage $message, array $errors, AbstractExecutionContext $context): void
    {
        $event = MessageValidationFailedEvent::create([
            'messageNamespace' => \get_class($message),
            'violations'       => $errors
        ]);

        $context->delivery($event);
    }

    /**
     * Push info to log
     *
     * @param AbstractMessage                        $message
     * @param Validator\ConstraintViolationInterface $constraintViolation
     * @param AbstractExecutionContext               $context
     *
     * @return void
     */
    private function logViolation(
        AbstractMessage $message,
        Validator\ConstraintViolationInterface $constraintViolation,
        AbstractExecutionContext $context
    ): void
    {
        $context->logContextMessage(
            $message,
            \sprintf(
                'Validation error for message "%s". Property: "%s"; Error message: "%s"',
                \get_class($message), $constraintViolation->getPropertyPath(), $constraintViolation->getMessage()
            ),
            LogLevel::ERROR
        );
    }

    /**
     * Validate message
     *
     * @todo: recursive?
     *
     * @param AbstractMessage                            $message
     * @param Validator\ConstraintViolationListInterface $violations
     *
     * @return void
     */
    private function validateMessage(AbstractMessage $message, Validator\ConstraintViolationListInterface $violations): void
    {
        $violations->addAll(
            $this->validator->validate($message)
        );

        foreach(\get_object_vars($message) as $key => $dto)
        {
            if(true === \is_object($message))
            {
                $violations->addAll($this->validator->validate([$key => $dto]));
            }
        }
    }
}
