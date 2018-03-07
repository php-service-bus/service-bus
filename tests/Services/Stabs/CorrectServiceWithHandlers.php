<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Tests\Services\Stabs;

use Desperado\ServiceBus\Annotations;
use Desperado\ServiceBus\ServiceInterface;
use Desperado\ServiceBus\Tests\TestApplicationContext;
use React\Promise\FulfilledPromise;
use React\Promise\Promise;
use React\Promise\PromiseInterface;

/**
 * @Annotations\Services\Service(loggerChannel="test")
 */
class CorrectServiceWithHandlers implements ServiceInterface
{
    /**
     * @Annotations\Services\CommandHandler()
     *
     * @param TestServiceCommand     $command
     * @param TestApplicationContext $context
     *
     * @return void
     */
    public function executeTestServiceCommand(
        TestServiceCommand $command,
        TestApplicationContext $context
    ): void
    {
        /** /tmp/executeTestServiceCommand.lock */
        \file_put_contents(\sys_get_temp_dir() . '/executeTestServiceCommand.lock', '1');
    }

    /**
     * @Annotations\Services\EventHandler(
     *     loggerChannel="eventLogChannel"
     * )
     *
     * @param TestServiceEvent       $event
     * @param TestApplicationContext $context
     *
     * @return PromiseInterface
     */
    public function whenTestServiceEvent(
        TestServiceEvent $event,
        TestApplicationContext $context
    ): PromiseInterface
    {
        return new FulfilledPromise();
    }

    /**
     * @Annotations\Services\EventHandler(
     *     loggerChannel="eventLogChannel"
     * )
     *
     * @param TestServiceEvent       $event
     * @param TestApplicationContext $context
     *
     * @return void
     */
    public function whenTestServiceEvent2(
        TestServiceEvent $event,
        TestApplicationContext $context
    ): void
    {

    }

    /**
     * @Annotations\Services\EventHandler(
     *     loggerChannel="eventLogChannel"
     * )
     *
     * @param TestServiceEvent       $event
     * @param TestApplicationContext $context
     *
     * @return Promise
     */
    public function whenTestServiceEvent3(
        TestServiceEvent $event,
        TestApplicationContext $context
    ): Promise
    {
        return new FulfilledPromise();
    }
}
