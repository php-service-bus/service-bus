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

namespace Desperado\Framework\Tests\Domain\Event;

use Desperado\Framework\Domain\DateTime;
use Desperado\Framework\Domain\Event\DomainEvent;
use Desperado\Framework\Domain\Uuid;
use Desperado\Framework\Tests\TestFixtures\Events\SomeEvent;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class DomainEventTest extends TestCase
{
    /**
     * @test
     *
     * @return void
     */
    public function createDomainEvent(): void
    {
        $domainEvent = DomainEvent::new(new SomeEvent(), -100);

        static::assertInternalType('object', $domainEvent);
        static::assertInstanceOf(DomainEvent::class, $domainEvent);

        static::assertEquals(-100, $domainEvent->getPlayhead());
        static::assertNotNull($domainEvent->getOccurredAt());
        static::assertNotEmpty($domainEvent->getId());
        static::assertTrue(Uuid::isValid($domainEvent->getId()));
        static::assertNull($domainEvent->getRecordedAt());
    }

    /**
     * @test
     *
     * @return void
     */
    public function restoreDomainEvent(): void
    {
        $domainEvent = DomainEvent::restore(
            Uuid::new(),
            new SomeEvent(),
            -100,
            DateTime::now(),
            DateTime::now()
        );

        static::assertEquals(-100, $domainEvent->getPlayhead());
        static::assertNotNull($domainEvent->getOccurredAt());
        static::assertNotEmpty($domainEvent->getId());
        static::assertTrue(Uuid::isValid($domainEvent->getId()));
        static::assertNotNull($domainEvent->getRecordedAt());
    }
}
