<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Saga\Metadata;

/**
 * Configured sagas collection
 */
class SagasMetadataCollection
{
    /**
     * Sagas metadata
     *
     * [
     *     'someSagaNamespace' => object SagaMetadata
     * ]
     *
     * @var SagaMetadata[]
     */
    private $collection;

    /**
     * @param array $sagasMetadata
     *
     * @return SagasMetadataCollection
     */
    public static function create(array $sagasMetadata = []): self
    {
        $self = new self();

        if(0 !== \count($sagasMetadata))
        {
            foreach($sagasMetadata as $sagaMetadata)
            {
                $self->add($sagaMetadata);
            }
        }

        return $self;
    }

    /**
     * Store metadata
     *
     * @param SagaMetadata $sagaMetadata
     *
     * @return void
     */
    public function add(SagaMetadata $sagaMetadata): void
    {
        $this->collection[$sagaMetadata->getSagaNamespace()] = $sagaMetadata;
    }

    /**
     * Has metadata
     *
     * @param string $sagaNamespace
     *
     * @return bool
     */
    public function has(string $sagaNamespace): bool
    {
        return isset($this->collection[$sagaNamespace]);
    }

    /**
     * Get saga metadata
     *
     * @param string $sagaNamespace
     *
     * @return SagaMetadata|null
     */
    public function get(string $sagaNamespace): ?SagaMetadata
    {
        return true === $this->has($sagaNamespace)
            ? $this->collection[$sagaNamespace]
            : null;
    }

    private function __construct()
    {
        $this->collection = [];
    }
}
