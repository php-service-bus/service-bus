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
use Desperado\ServiceBus\Marshal\Normalizer\Normalizer;
use Desperado\ServiceBus\Marshal\Serializer\ArraySerializer;

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
     * @param ArraySerializer $serializer
     * @param Normalizer      $normalizer
     * @param Denormalizer    $denormalizer
     */
    public function __construct(ArraySerializer $serializer, Normalizer $normalizer, Denormalizer $denormalizer)
    {
        $this->serializer   = $serializer;
        $this->normalizer   = $normalizer;
        $this->denormalizer = $denormalizer;
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
