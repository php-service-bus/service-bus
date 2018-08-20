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

namespace Desperado\ServiceBus\Tests\EventSourcing\Stubs;

use Desperado\ServiceBus\EventSourcing\Aggregate;

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
        $this->raise(new SomeFirstVersionEvent($value));
    }

    /**
     * @param string $value
     *
     * @return void
     */
    public function secondAction(string $value): void
    {
        $this->raise(new SomeSecondVersionEvent($value));
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
     * @param SomeSecondVersionEvent $event
     *
     * @return void
     */
    private function onSomeSecondVersionEvent(SomeSecondVersionEvent $event): void
    {
        $this->secondValue = $event->someField();
    }

    /**
     * @noinspection PhpUnusedPrivateMethodInspection
     *
     * @param SomeFirstVersionEvent $event
     *
     * @return void
     */
    private function onSomeFirstVersionEvent(SomeFirstVersionEvent $event): void
    {
        $this->firstValue = $event->key();
    }
}