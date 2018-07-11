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
 * Add some key=>value pairs to log record
 */
final class ExtraDataProcessor
{
    /**
     * Some key=>value pairs to log record
     *
     * @var array<string, mixed>
     */
    private $data;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(array $data)
    {
        $this->data = \array_filter($data);
    }

    /**
     * @param  array $record
     *
     * @return array
     */
    public function __invoke(array $record)
    {
        foreach($this->data as $key => $value)
        {
            $record['extra'][$key] = $value;
        }

        return $record;
    }
}
