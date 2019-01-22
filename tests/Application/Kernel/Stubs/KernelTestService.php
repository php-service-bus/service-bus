<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation)
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Tests\Application\Kernel\Stubs;

use ServiceBus\Common\Context\ServiceBusContext;
use ServiceBus\Context\KernelContext;
use ServiceBus\Services\Annotations\CommandHandler;
use ServiceBus\Tests\Stubs\Messages\SecondEmptyCommand;

/**
 *
 */
final class KernelTestService
{

    /**
     * @CommandHandler()
     *
     * @param TriggerThrowableCommand $command
     * @param ServiceBusContext       $context
     *
     * @return void
     *
     * @throws \RuntimeException
     */
    public function handleWithThrowable(
        /** @noinspection PhpUnusedParameterInspection */
        TriggerThrowableCommand $command,
        ServiceBusContext $context
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
     *     defaultValidationFailedEvent="ServiceBus\Tests\Stubs\Messages\ValidationFailed"
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
     *     defaultThrowableEvent="ServiceBus\Tests\Stubs\Messages\ExecutionFailed"
     * )
     *
     * @param TriggerThrowableCommandWithResponseEvent $command
     * @param KernelContext                            $context
     *
     * @return void
     *
     * @throws \RuntimeException
     */
    public function handleWithSpecifiedThrowableEvent(TriggerThrowableCommandWithResponseEvent $command, KernelContext $context): void
    {
        [$command, $context];

        throw new \RuntimeException('abube');
    }
}
