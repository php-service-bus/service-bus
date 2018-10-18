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

namespace Desperado\ServiceBus\Tests\Infrastructure\AnnotationsReader\Stubs;

/**
 * @Annotation
 * @Target("METHOD")
 */
final class TestMethodLevelAnnotation
{
    /**
     * @var string
     */
    private $property;

    /**
     * @param array<string, mixed> $data
     *
     * @throws \RuntimeException
     */
    public function __construct(array $data)
    {
        foreach($data as $key => $value)
        {
            $this->{$key} = $value;
        }
    }

    /**
     * @return string
     */
    public function property(): string
    {
        return $this->property;
    }
}
