<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Tests\Saga\Events;

use Desperado\Domain\DateTime;
use Desperado\Domain\Uuid;
use Desperado\ServiceBus\AbstractSaga;
use Desperado\ServiceBus\Saga\Metadata\SagaMetadata;
use Desperado\ServiceBus\Saga\SagaState;
use Desperado\ServiceBus\Sagas\Events\SagaCreatedEvent;
use Desperado\ServiceBus\Sagas\Events\SagaStatusWasChangedEvent;
use Desperado\ServiceBus\Tests\Saga\TestSaga;
use Desperado\ServiceBus\Tests\Saga\TestSagaIdentifier;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class SagaEventsTest extends TestCase
{
    /**
     * @var TestSagaIdentifier
     */
    private $id;

    /**
     * @var AbstractSaga
     */
    private $saga;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->id = new TestSagaIdentifier(Uuid::v4(), TestSaga::class);
        $this->saga = new TestSaga(
            $this->id,
            SagaMetadata::create(
                TestSaga::class,
                '+1 day',
                $this->id->getIdentityClass(),
                'property'
            )
        );
    }

    /**
     * @inheritdoc
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->id, $this->saga);
    }

    /**
     * @test
     * @dataProvider inspectPropertiesDataProvider
     *
     * @param string $eventNamespace
     * @param array  $expectedProperties
     *
     * @return void
     */
    public function inspectProperties(string $eventNamespace, array $expectedProperties): void
    {
        $reflectionClass = new \ReflectionClass($eventNamespace);
        $reflectionProperties = $reflectionClass->getProperties();

        static::assertCount(\count($expectedProperties), $reflectionProperties);

        foreach($reflectionProperties as $reflectionProperty)
        {
            static::assertArrayHasKey($reflectionProperty->getName(), $expectedProperties);

            $propertyDocument = $reflectionProperty->getDocComment();
            $expectedVarHint = \sprintf('@var %s', $expectedProperties[$reflectionProperty->getName()]);

            static::assertTrue(false !== \strpos($propertyDocument, $expectedVarHint));

            $accessorName = \sprintf('get%s', \ucfirst($reflectionProperty->getName()));

            static::assertTrue(\method_exists($eventNamespace, $accessorName));
        }
    }

    /**
     * @test
     *
     * @return void
     */
    public function sagaStatusWasChangedEventToCompleted(): void
    {
        $event = SagaStatusWasChangedEvent::completed($this->saga, __CLASS__);

        static::assertEquals($this->id->toString(), $event->getId());
        static::assertEquals($this->id->getIdentityClass(), $event->getIdentifierNamespace());
        static::assertEquals(\get_class($this->saga), $event->getSagaNamespace());
        static::assertEquals($this->saga->getState()->getStatusCode(), $event->getPreviousStatusId());
        static::assertEquals(SagaState::STATUS_COMPLETED, $event->getNewStatusId());
        static::assertEquals(__CLASS__, $event->getDescription());
        static::assertNotEmpty($event->getDatetime());
        static::assertInstanceOf(DateTime::class, DateTime::fromString($event->getDatetime()));
    }

    /**
     * @test
     *
     * @return void
     */
    public function sagaStatusWasChangedEventToFailed(): void
    {
        $event = SagaStatusWasChangedEvent::failed($this->saga, __CLASS__);

        static::assertEquals($this->id->toString(), $event->getId());
        static::assertEquals($this->id->getIdentityClass(), $event->getIdentifierNamespace());
        static::assertEquals(\get_class($this->saga), $event->getSagaNamespace());
        static::assertEquals($this->saga->getState()->getStatusCode(), $event->getPreviousStatusId());
        static::assertEquals(SagaState::STATUS_FAILED, $event->getNewStatusId());
        static::assertEquals(__CLASS__, $event->getDescription());
        static::assertNotEmpty($event->getDatetime());
        static::assertInstanceOf(DateTime::class, DateTime::fromString($event->getDatetime()));
    }

    /**
     * @test
     *
     * @return void
     */
    public function sagaStatusWasChangedEventToExpired(): void
    {
        $event = SagaStatusWasChangedEvent::expired($this->saga);

        static::assertEquals($this->id->toString(), $event->getId());
        static::assertEquals($this->id->getIdentityClass(), $event->getIdentifierNamespace());
        static::assertEquals(\get_class($this->saga), $event->getSagaNamespace());
        static::assertEquals($this->saga->getState()->getStatusCode(), $event->getPreviousStatusId());
        static::assertEquals(SagaState::STATUS_EXPIRED, $event->getNewStatusId());
        static::assertNotEmpty($event->getDatetime());
        static::assertInstanceOf(DateTime::class, DateTime::fromString($event->getDatetime()));
    }

    /**
     * @test
     *
     * @return void
     */
    public function sagaCreatedEvent(): void
    {
        $event = SagaCreatedEvent::new($this->saga, '+1 day');

        static::assertEquals($this->id->toString(), $event->getId());
        static::assertEquals($this->id->getIdentityClass(), $event->getIdentifierNamespace());
        static::assertEquals($this->id->getSagaNamespace(), $event->getSagaNamespace());
        static::assertEquals(\get_class($this->saga), $event->getSagaNamespace());
        static::assertInstanceOf(DateTime::class, DateTime::fromString($event->getCreatedAt()));
        static::assertInstanceOf(DateTime::class, DateTime::fromString($event->getExpireDate()));
    }

    /**
     * @return array
     */
    public function inspectPropertiesDataProvider(): array
    {
        return [
            [
                SagaCreatedEvent::class,
                [
                    'id'                  => 'string',
                    'identifierNamespace' => 'string',
                    'sagaNamespace'       => 'string',
                    'createdAt'           => 'string',
                    'expireDate'          => 'string'
                ]
            ],
            [
                SagaStatusWasChangedEvent::class,
                [
                    'id'                  => 'string',
                    'identifierNamespace' => 'string',
                    'sagaNamespace'       => 'string',
                    'previousStatusId'    => 'int',
                    'newStatusId'         => 'int',
                    'datetime'            => 'string',
                    'description'         => 'string|null',
                ]
            ]
        ];
    }
}
