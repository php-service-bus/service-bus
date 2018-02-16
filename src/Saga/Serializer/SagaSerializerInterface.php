<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Saga\Serializer;

use Desperado\ServiceBus\AbstractSaga;

/**
 * Saga serializer interface
 */
interface SagaSerializerInterface
{
    /**
     * Serialize saga
     *
     * @param AbstractSaga $saga
     *
     * @return string
     */
    public function serialize(AbstractSaga $saga): string;

    /**
     * Unserialize saga
     *
     * @param string $serializedSaga
     *
     * @return AbstractSaga
     */
    public function unserialize(string $serializedSaga): AbstractSaga;
}
