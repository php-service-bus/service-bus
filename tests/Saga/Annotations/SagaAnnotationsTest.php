<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Tests\Saga\Annotations;

use Desperado\ServiceBus\Annotations\Sagas\Saga;
use Desperado\ServiceBus\Annotations\Sagas\SagaEventListener;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class SagaAnnotationsTest extends TestCase
{
    /**
     * @test
     * @dataProvider checkPropertiesDataProvider
     *
     * @param string $sagaNamespace
     * @param array  $expectedProperties
     *
     * @return void
     */
    public function checkProperties(string $sagaNamespace, array $expectedProperties): void
    {
        $reflectionClass = new \ReflectionClass($sagaNamespace);
        $reflectionProperties = $reflectionClass->getProperties();

        static::assertCount(\count($expectedProperties), $reflectionProperties);

        foreach($reflectionProperties as $reflectionProperty)
        {
            static::assertTrue(\in_array($reflectionProperty->getName(), $expectedProperties, true), $reflectionProperty->getName());
        }
    }

    /**
     * @return array
     */
    public function checkPropertiesDataProvider(): array
    {
        return [
            [Saga::class, ['identifierNamespace', 'containingIdentifierProperty', 'expireDateModifier']],
            [SagaEventListener::class, ['containingIdentifierProperty']]
        ];
    }
}
