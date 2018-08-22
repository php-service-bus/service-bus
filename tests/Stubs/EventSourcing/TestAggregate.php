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

namespace Desperado\ServiceBus\Tests\Stubs\EventSourcing;

use Desperado\ServiceBus\EventSourcing\Aggregate;
use Desperado\ServiceBus\Tests\Stubs\Messages\FirstEventWithKey;
use Desperado\ServiceBus\Tests\Stubs\Messages\SecondEventWithKey;

/**
 *
 */
final class TestAggregate extends Aggregate
{
    /**
     * @var string|null
     */
    private $firstValue;

    /**
     * @var string|null
     */
    private $secondValue;

    /**
     * @param string $value
     *
     * @return void
     */
    public function firstAction(string $value): void
    {
        $this->raise(new FirstEventWithKey($value));
    }

    /**
     * @param string $value
     *
     * @return void
     */
    public function secondAction(string $value): void
    {
        $this->raise(new SecondEventWithKey($value));
    }

    /**
     * @return string|null
     */
    public function firstValue(): ?string
    {
        return $this->firstValue;
    }

    /**
     * @return string|null
     */
    public function secondValue(): ?string
    {
        return $this->secondValue;
    }

    /**
     * @noinspection PhpUnusedPrivateMethodInspection
     *
     * @param SecondEventWithKey $event
     *
     * @return void
     */
    private function onSecondEventWithKey(SecondEventWithKey $event): void
    {
        $this->secondValue = $event->key();
    }

    /**
     * @noinspection PhpUnusedPrivateMethodInspection
     *
     * @param FirstEventWithKey $event
     *
     * @return void
     */
    private function onFirstEventWithKey(FirstEventWithKey $event): void
    {
        $this->firstValue = $event->key();
    }
}