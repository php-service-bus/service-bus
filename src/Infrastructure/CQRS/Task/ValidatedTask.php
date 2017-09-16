<?php

/**
 * Command Query Responsibility Segregation, Event Sourcing implementation
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @url     https://github.com/mmasiukevich
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\Framework\Infrastructure\CQRS\Task;

use Desperado\Framework\Domain\Context\ContextInterface;
use Desperado\Framework\Domain\Messages\MessageInterface;
use Desperado\Framework\Domain\Task\TaskInterface;
use Desperado\Framework\Infrastructure\CQRS\Context\DeliveryContextInterface;
use Desperado\Framework\Infrastructure\CQRS\Context\DeliveryOptions;
use Desperado\Framework\Infrastructure\CQRS\Task\Contract\IncomeMessageValidationFailedEvent;
use Symfony\Component\Validator;

/**
 * Validated task
 */
class ValidatedTask extends AbstractTask
{
    /**
     * Original task
     *
     * @var TaskInterface
     */
    private $originalTask;

    /**
     * Validation handler
     *
     * @var Validator\Validator\ValidatorInterface
     */
    private $validator;

    /**
     * @param TaskInterface                          $originalTask
     * @param Validator\Validator\ValidatorInterface $validator
     */
    public function __construct(TaskInterface $originalTask, Validator\Validator\ValidatorInterface $validator)
    {
        $this->originalTask = $originalTask;
        $this->validator = $validator;

        parent::__construct($originalTask->getOptions());
    }

    /**
     * @inheritdoc
     */
    public function __invoke(MessageInterface $message, ContextInterface $context): ?TaskInterface
    {
        $this->appendOptions($context);

        /** @var Validator\ConstraintViolationListInterface $violations */
        $violations = $this->validator->validate($message);

        if(0 !== \count($violations))
        {
            $logger = $this->getLogger($message, $context);

            $event = new IncomeMessageValidationFailedEvent();
            $event->message = \get_class($message);

            foreach($violations as $violation)
            {
                /** @var Validator\ConstraintViolationInterface $violation */

                $event->violations[$violation->getPropertyPath()][] = $violation->getMessage();

                $logger->error(
                    \sprintf(
                        'Validation error for message "%s". Property: "%s"; Error message: "%s"',
                        $event->message, $violation->getPropertyPath(), $violation->getMessage()
                    )
                );
            }

            if($context instanceof DeliveryContextInterface)
            {
                $context->publish($event, new DeliveryOptions());
            }

            return null;
        }

        return \call_user_func_array($this->originalTask, [$message, $context]);
    }
}
