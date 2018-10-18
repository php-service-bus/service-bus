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

use Desperado\ServiceBus\MessageHandlers\HandlerCollection;

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
     * @var HandlerCollection
     */
    private $handlerCollection;

    /**
     * @param SagaMetadata      $metaData
     * @param HandlerCollection $handlerCollection
     */
    public function __construct(SagaMetadata $metaData, HandlerCollection $handlerCollection)
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
     * @return HandlerCollection
     */
    public function handlerCollection(): HandlerCollection
    {
        return $this->handlerCollection;
    }
}
