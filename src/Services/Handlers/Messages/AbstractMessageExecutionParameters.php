<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Services\Handlers\Messages;

/**
 * Base class of message processing parameters
 */
abstract class AbstractMessageExecutionParameters
{
    /**
     * Logger channel
     *
     * @var string|null
     */
    private $loggerChannel;

    /**
     * @param string $loggerChannel
     */
    public function __construct(string $loggerChannel)
    {
        $this->loggerChannel = $loggerChannel;
    }

    /**
     * Get message-specific logger channel
     *
     * @return string
     */
    public function getLoggerChannel(): ?string
    {
        return $this->loggerChannel;
    }
}
