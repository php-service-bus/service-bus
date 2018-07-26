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

namespace Desperado\ServiceBus\EventSourcing\EventStreamStore\Transformer;

use Desperado\ServiceBus\Common\Contract\Messages\Event;
use Desperado\ServiceBus\Marshal\Denormalizer\Denormalizer;
use Desperado\ServiceBus\Marshal\Denormalizer\SymfonyPropertyDenormalizer;
use Desperado\ServiceBus\Marshal\Normalizer\Normalizer;
use Desperado\ServiceBus\Marshal\Normalizer\SymfonyPropertyNormalizer;
use Desperado\ServiceBus\Marshal\Serializer\ArraySerializer;
use Desperado\ServiceBus\Marshal\Serializer\SymfonyJsonSerializer;

/**
 *
 */
final class DefaultEventSerializer implements AggregateEventSerializer
{
    /**
     * @var ArraySerializer
     */
    private $serializer;

    /**
     * @var Normalizer
     */
    private $normalizer;

    /**
     * @var Denormalizer
     */
    private $denormalizer;

    /**
     * @param ArraySerializer|null $serializer
     * @param Normalizer|null      $normalizer
     * @param Denormalizer|null    $denormalizer
     */
    public function __construct(
        ArraySerializer $serializer = null,
        Normalizer $normalizer = null,
        Denormalizer $denormalizer = null
    )
    {
        $this->serializer   = $serializer ?? new SymfonyJsonSerializer();
        $this->normalizer   = $normalizer ?? new SymfonyPropertyNormalizer();
        $this->denormalizer = $denormalizer ?? new SymfonyPropertyDenormalizer();
    }

    /**
     * @inheritdoc
     */
    public function serialize(Event $event): string
    {
        return $this->serializer->serialize(
            $this->normalizer->normalize($event)
        );
    }

    /**
     * @psalm-suppress MoreSpecificReturnType
     * @psalm-suppress LessSpecificReturnStatement
     *
     * @inheritdoc
     */
    public function unserialize(string $eventClass, string $payload): Event
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->denormalizer->denormalize(
            $eventClass,
            $this->serializer->unserialize($payload)
        );
    }
}
