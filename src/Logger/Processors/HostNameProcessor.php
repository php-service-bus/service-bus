<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Logger\Processors;

/**
 * Add current host name
 */
final class HostNameProcessor
{
    /**
     * @param  array $record
     *
     * @return array
     */
    public function __invoke(array $record)
    {
        $record['extra']['host_name'] = \gethostname();

        return $record;
    }
}
