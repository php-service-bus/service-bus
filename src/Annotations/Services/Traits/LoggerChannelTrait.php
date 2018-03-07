<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Annotations\Services\Traits;

/**
 *
 */
trait LoggerChannelTrait
{
    /**
     * Logger channel
     *
     * @var string|null
     */
    protected $loggerChannel;

    /**
     * @inheritdoc
     */
    public function getLoggerChannel(): ?string
    {
        return $this->loggerChannel;
    }
}
