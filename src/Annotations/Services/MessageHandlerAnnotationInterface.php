<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Annotations\Services;

/**
 * An annotation interface that points to a message handler
 */
interface MessageHandlerAnnotationInterface
{
    /**
     * Get specific logger channel
     *
     * @return string|null
     */
    public function getLoggerChannel(): ?string;
}
