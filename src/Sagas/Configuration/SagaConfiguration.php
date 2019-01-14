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

namespace Desperado\ServiceBus\Sagas\Configuration;

/**
 *
 */
final class SagaConfiguration
{
    /**
     * @var SagaMetadata
     */
    private $metaData;

    /**
     * @var \SplObjectStorage<\Desperado\ServiceBus\MessageHandlers\Handler>
     */
    private $handlerCollection;

    /**
     * @param SagaMetadata                                                     $metaData
     * @param \SplObjectStorage<\Desperado\ServiceBus\MessageHandlers\Handler> $handlerCollection
     */
    public function __construct(SagaMetadata $metaData, \SplObjectStorage $handlerCollection)
    {
        $this->metaData          = $metaData;
        $this->handlerCollection = $handlerCollection;
    }

    /**
     * @return SagaMetadata
     */
    public function metaData(): SagaMetadata
    {
        return $this->metaData;
    }

    /**
     * @return \SplObjectStorage<\Desperado\ServiceBus\MessageHandlers\Handler>
     */
    public function handlerCollection(): \SplObjectStorage
    {
        return $this->handlerCollection;
    }
}
