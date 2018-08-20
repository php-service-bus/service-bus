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

namespace Desperado\ServiceBus\Tests\Services\Configuration\Stubs;

use Amp\Promise;
use Desperado\ServiceBus\Application\KernelContext;
use Desperado\ServiceBus\Services\Annotations\CommandHandler;
use Desperado\ServiceBus\Services\Annotations\EventListener;

/**
 *
 */
final class ServiceWithCorrectMessageHandlers
{
    /**
     * @CommandHandler(
     *     validate=true,
     *     groups={"qwerty", "root"}
     * )
     *
     * @param SomeCommand $command
     *
     * @return void
     */
    public function handle(SomeCommand $command, KernelContext $context): void
    {

    }

    /**
     * @EventListener()
     *
     * @param SomeEvent     $event
     * @param KernelContext $context
     *
     * @return Promise
     */
    public function firstEventListener(SomeEvent $event, KernelContext $context): Promise
    {

    }


    /**
     * @EventListener()
     *
     * @param SomeEvent     $event
     * @param KernelContext $context
     *
     * @return \Generator
     */
    public function secondEventListener(SomeEvent $event, KernelContext $context): \Generator
    {

    }

    /**
     * @SomeAnotherMethodLevelAnnotation
     *
     * @param SomeCommand   $command
     * @param KernelContext $context
     *
     * @return void
     */
    public function ignoredMethod(SomeCommand $command, KernelContext $context): void
    {

    }
}
