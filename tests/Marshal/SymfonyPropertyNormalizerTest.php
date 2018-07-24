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

namespace Desperado\ServiceBus\Tests\Marshal;

use Desperado\ServiceBus\Marshal\Normalizer\SymfonyPropertyNormalizer;
use Desperado\ServiceBus\Marshal\Denormalizer\SymfonyPropertyDenormalizer;
use PHPUnit\Framework\TestCase;

/**
 *
 */
final class SymfonyPropertyNormalizerTest extends TestCase
{
    /**
     * @test
     *
     * @return void
     */
    public function withClosedConstructor(): void
    {
        $object = WithClosedConstructor::create('value');

        $normalized = (new SymfonyPropertyNormalizer())->normalize($object);

        static::assertEquals(['key' => 'value'], $normalized);

        $result = (new SymfonyPropertyDenormalizer())->denormalize(WithClosedConstructor::class, $normalized);

        static::assertEquals($object, $result);
    }

    /**
     * @test
     * @expectedException \RuntimeException
     * @expectedExceptionMessage The type of the "property" attribute for class
     *                           "Desperado\ServiceBus\Tests\Normalizer\WithWrongType" must be one of "string"
     *                           ("integer" given)
     *
     * @return void
     */
    public function withWrongType(): void
    {
        $object     = WithWrongType::create(1000);
        $normalizer = new SymfonyPropertyNormalizer();

        (new SymfonyPropertyDenormalizer())->denormalize(WithWrongType::class, $normalizer->normalize($object));
    }
}
