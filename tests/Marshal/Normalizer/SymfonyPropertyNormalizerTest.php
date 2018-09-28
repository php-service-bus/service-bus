<?php /** @noinspection UnnecessaryAssertionInspection */

/**
 * PHP Service Bus (publish-subscribe pattern implementation)
 * Supports Saga pattern and Event Sourcing
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Tests\Marshal\Normalizer;

use Desperado\ServiceBus\Marshal\Normalizer\SymfonyPropertyNormalizer;
use Desperado\ServiceBus\Marshal\Denormalizer\SymfonyPropertyDenormalizer;
use Desperado\ServiceBus\Tests\Marshal\Normalizer\Stubs\WithClosedConstructor;
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

        static::assertEquals(['key' => 'value', 'someSecondKey' => null], $normalized);

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
        $object     = new class(100500)
        {
            /** @var string */
            private $property;

            public function __construct(int $qwerty)
            {
                $this->property = $qwerty;
            }

            public function property(): string
            {
                return (string) $this->property;
            }
        };
        $normalizer = new SymfonyPropertyNormalizer();

        (new SymfonyPropertyDenormalizer())->denormalize(\get_class($object), $normalizer->normalize($object));
    }

    /**
     * @test
     *
     * @return void
     */
    public function snakeCase(): void
    {
        /** @var WithClosedConstructor $result */
        $result = (new SymfonyPropertyDenormalizer())->denormalize(
            WithClosedConstructor::class,
            ['key' => 'value', 'some_second_key' => '100500']
        );

        static::assertEquals(
            '100500',
            static::readAttribute($result, 'someSecondKey')
        );
    }

    /**
     * @test
     *
     * @return void
     */
    public function withEmptyClass(): void
    {
        $object = new class()
        {

        };

        $result = (new SymfonyPropertyDenormalizer())->denormalize(
            \get_class($object), (new SymfonyPropertyNormalizer())->normalize($object)
        );

        /** @noinspection UnnecessaryAssertionInspection */
        static::assertInstanceOf(\get_class($object), $result);
    }

    /**
     * @test
     * @expectedException \Desperado\ServiceBus\Marshal\Normalizer\Exceptions\NormalizationFailed
     *
     * @return void
     */
    public function unSupportedNormalization(): void
    {
        (new SymfonyPropertyNormalizer())->normalize($this);
    }

    /**
     * @test
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function datetimeNormalize(): void
    {
        $datetime = new \DateTimeImmutable('NOW');

        $object = new class($datetime)
        {
            /**
             * @var \DateTimeImmutable
             */
            private $datetime;

            /**
             * @param \DateTimeImmutable $datetime
             */
            public function __construct(\DateTimeImmutable $datetime)
            {
                $this->datetime = $datetime;
            }

            public function datetime(): \DateTimeImmutable
            {
                return $this->datetime;
            }
        };

        $normalized = (new SymfonyPropertyNormalizer())->normalize($object);

        $denormalized = (new SymfonyPropertyDenormalizer())->denormalize(\get_class($object), $normalized);

        static::assertEquals(
            $datetime->format('Y-m-md H:i:s'),
            $denormalized->datetime()->format('Y-m-md H:i:s')
        );
    }
}
