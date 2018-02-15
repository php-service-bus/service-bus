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

use Desperado\ServiceBus\Sagas\Events\SagaCreatedEvent;
use Desperado\ServiceBus\Sagas\Events\SagaStatusWasChangedEvent;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class SagaEventsTest extends TestCase
{
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
