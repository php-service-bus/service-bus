<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\MessageBus\Task;

use function Amp\call;
use Amp\Promise;
use Amp\Success;
use Desperado\ServiceBus\Contract\Event\MessageValidationFailedEvent;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\ValidatorBuilder;
use Desperado\Contracts\Common\Message;
use Desperado\ServiceBus\Kernel\ApplicationContext;

/**
 * Execute task with validation checks
 */
final class ValidateMessageTask implements Task
{
    /**
     * @var Task
     */
    private $taskHandler;

    /**
     * @var ValidatorInterface
     */
    private $validator;

    /**
     * @var array<mixed, string>
     */
    private $validationGroups;

    /**
     * @param Task  $taskHandler
     * @param array $validationGroups
     */
    public function __construct(Task $taskHandler, array $validationGroups)
    {
        $this->taskHandler      = $taskHandler;
        $this->validationGroups = $validationGroups;

        $this->validator = (new ValidatorBuilder())
            ->enableAnnotationMapping()
            ->getValidator();
    }

    /**
     * @inheritdoc
     */
    public function __invoke(Message $message, ApplicationContext $context): Promise
    {
        $taskHandler      = $this->taskHandler;
        $validator        = $this->validator;
        $validationGroups = $this->validationGroups;

        /** @psalm-suppress InvalidArgument Incorrect psalm unpack parameters (...$args) */
        return call(
            static function(Message $message, ApplicationContext $context) use (
                $taskHandler, $validator, $validationGroups
            ): \Generator
            {
                /** @var ConstraintViolationList $violations */
                $violations = $validator->validate($message, null, $validationGroups);

                if(0 !== \count($violations))
                {
                    yield $context->delivery(
                        self::createFailedEvent($message, $context, $violations)
                    );

                    return yield new Success();
                }

                return yield $taskHandler($message, $context);
            },
            $message,
            $context
        );
    }

    /**
     * @param Message                 $message
     * @param ApplicationContext      $context
     * @param ConstraintViolationList $violationList
     *
     * @return MessageValidationFailedEvent
     */
    private static function createFailedEvent(
        Message $message,
        ApplicationContext $context,
        ConstraintViolationList $violationList
    ): MessageValidationFailedEvent
    {
        $event = MessageValidationFailedEvent::create($message, $context->incomingEnvelope()->normalized());

        foreach($violationList as $violation)
        {
            /** @var \Symfony\Component\Validator\ConstraintViolation $violation */

            $event->addViolation($violation->getPropertyPath(), $violation->getMessage());
        }

        return $event;
    }
}
