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

namespace Desperado\ServiceBus\MessageBus\Processor;

use function Amp\call;
use Amp\Promise;
use Desperado\ServiceBus\Application\KernelContext;
use Desperado\ServiceBus\Common\Contract\Messages\Message;
use Desperado\ServiceBus\MessageBus\Contracts\MessageValidationFailed;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\ValidatorBuilder;

/**
 * Executing message validation
 */
final class ValidationMessageProcessor implements Processor
{
    /**
     * @var Processor
     */
    private $processor;

    /**
     * @var ValidatorInterface
     */
    private $validator;

    /**
     * @var array<mixed, string>
     */
    private $validationGroups;

    /**
     * @param Processor $processor
     * @param array     $validationGroups
     */
    public function __construct(Processor $processor, array $validationGroups)
    {
        $this->processor        = $processor;
        $this->validationGroups = $validationGroups;

        $this->validator = (new ValidatorBuilder())
            ->enableAnnotationMapping()
            ->getValidator();
    }

    /**
     * @inheritdoc
     */
    public function __invoke(Message $message, KernelContext $context): Promise
    {
        $processor        = $this->processor;
        $validator        = $this->validator;
        $validationGroups = $this->validationGroups;

        /** @psalm-suppress InvalidArgument Incorrect psalm unpack parameters (...$args) */
        return call(
            static function(Message $message, KernelContext $context) use (
                $processor, $validator, $validationGroups
            ): \Generator
            {
                /** @var ConstraintViolationList $violations */
                $violations = $validator->validate($message, null, $validationGroups);

                if(0 !== \count($violations))
                {
                    yield $context->delivery(
                        self::createFailedEvent($message, $context, $violations)
                    );

                    return null;
                }

                return yield $processor($message, $context);
            },
            $message,
            $context
        );
    }

    /**
     * @param Message                 $message
     * @param KernelContext           $context
     * @param ConstraintViolationList $violationList
     *
     * @return MessageValidationFailed
     */
    private static function createFailedEvent(
        Message $message,
        KernelContext $context,
        ConstraintViolationList $violationList
    ): MessageValidationFailed
    {
        /** @fix me */
        $event = MessageValidationFailed::create($message, []);

        foreach($violationList as $violation)
        {
            /** @var \Symfony\Component\Validator\ConstraintViolation $violation */

            $event->addViolation($violation->getPropertyPath(), $violation->getMessage());
        }

        return $event;
    }
}
