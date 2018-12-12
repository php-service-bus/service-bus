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

namespace Desperado\ServiceBus\Tests\Application\Kernel\Stubs;

use Desperado\ServiceBus\Application\KernelContext;
use Desperado\ServiceBus\Services\Annotations\CommandHandler;
use Desperado\ServiceBus\Tests\Stubs\Messages\SecondEmptyCommand;

/**
 *
 */
final class KernelTestService
{

    /**
     * @CommandHandler()
     *
     * @param TriggerThrowableCommand $command
     * @param KernelContext           $context
     *
     * @return void
     *
     * @throws \RuntimeException
     */
    public function handleWithThrowable(
        /** @noinspection PhpUnusedParameterInspection */
        TriggerThrowableCommand $command,
        KernelContext $context
    ): void
    {
        throw new \RuntimeException(__METHOD__);
    }

    /**
     * @CommandHandler()
     *
     * @param TriggerResponseEventCommand $command
     * @param KernelContext               $context
     *
     * @return \Generator
     */
    public function handleWithSuccessResponse(
        /** @noinspection PhpUnusedParameterInspection */
        TriggerResponseEventCommand $command,
        KernelContext $context
    ): \Generator
    {
        yield $context->delivery(new SuccessResponseEvent());
    }

    /**
     * @CommandHandler(validate=true)
     *
     * @param SecondEmptyCommand $command
     * @param KernelContext      $context
     *
     * @return void
     */
    public function testContextLogging(
        SecondEmptyCommand $command,
        KernelContext $context
    ): void
    {
        $context->logContextMessage('Test message', ['qwerty' => \get_class($command)]);
        $context->logContextThrowable(new \RuntimeException('test exception message'));
    }

    /**
     * @CommandHandler(validate=true)
     *
     * @param WithValidationCommand $command
     * @param KernelContext         $context
     *
     * @return void
     */
    public function withFailedValidation(
        WithValidationCommand $command,
        KernelContext $context
    ): void
    {
        $context->logContextMessage(\get_class($command), [
            'isValid'    => $context->isValid(),
            'violations' => $context->violations()
        ]);
    }

    /**
     * @CommandHandler(
     *     validate=true,
     *     defaultValidationFailedEvent="Desperado\ServiceBus\Tests\Stubs\Messages\ValidationFailed"
     * )
     *
     * @param WithValidationRulesCommand $command
     * @param KernelContext              $context
     *
     * @return void
     */
    public function validateWithErrorAndSpecifiedEvent(WithValidationRulesCommand $command, KernelContext $context): void
    {

    }

    /**
     * @CommandHandler(
     *     defaultThrowableEvent="Desperado\ServiceBus\Tests\Stubs\Messages\ExecutionFailed"
     * )
     *
     * @param TriggerThrowableCommandWithResponseEvent $command
     * @param KernelContext                            $context
     *
     * @return void
     */
    public function handleWithSpecifiedThrowableEvent(TriggerThrowableCommandWithResponseEvent $command, KernelContext $context): void
    {
        [$command, $context];

        throw new \RuntimeException('abube');
    }
}
