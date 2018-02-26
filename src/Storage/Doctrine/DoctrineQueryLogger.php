<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Storage\Doctrine;

use Doctrine\DBAL\Logging\SQLLogger;
use Psr\Log\LoggerInterface;

/**
 * Log sql queries
 */
final class DoctrineQueryLogger implements SQLLogger
{
    /**
     * Logger instance
     *
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Executed SQL queries.
     *
     * @var array
     */
    public $queries;

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->queries = [];
    }

    /**
     * @inheritdoc
     */
    public function startQuery($sql, array $params = null, array $types = null): void
    {
        $this->queries[\sha1($sql)] = [
            'sql'       => $sql,
            'params'    => $params,
            'startTime' => \microtime(true)
        ];
    }

    /**
     * @inheritdoc
     */
    public function stopQuery(): void
    {
        foreach($this->queries as $key => $queryData)
        {
            $this->logger->debug(
                \sprintf(
                    'Query: "%s" (with parameters: "%s"). Execution time: "%s"',
                    \str_replace(['  ', \PHP_EOL], '', \trim($queryData['sql'])),
                    \implode('; ', $this->prepareParameters($queryData['params'] ?? [])),
                    \round(\microtime(true) - $queryData['startTime'], 4)
                )
            );

            unset($this->queries[$key]);
        }
    }

    /**
     * Prepare query parameters
     *
     * @param array $params
     *
     * @return array
     */
    private function prepareParameters(array $params): array
    {
        $result = [];

        foreach($params as $key => $value)
        {
            if($value instanceof \DateTime)
            {
                $value = $value->format('c');
            }

            $result[$key] = (string) $value;
        }

        return $result;
    }
}
