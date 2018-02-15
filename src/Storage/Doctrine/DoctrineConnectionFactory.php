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

use Desperado\ServiceBus\Storage\Exceptions\StorageConfigurationException;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Create doctrine2 connection
 */
class DoctrineConnectionFactory
{
    /**
     * @param string               $connectionDSN
     * @param bool                 $createTables automatically create tables
     * @param LoggerInterface|null $logger
     *
     * @return Connection
     *
     * @throws StorageConfigurationException
     * @throws \Exception
     */
    public static function create(
        string $connectionDSN,
        bool $createTables = true,
        LoggerInterface $logger = null
    ): Connection
    {
        try
        {
            $configuration = new Configuration();
            $configuration->setSQLLogger(
                new DoctrineQueryLogger(
                    $logger ?? new NullLogger()
                )
            );

            $connection = DriverManager::getConnection(
                ['url' => $connectionDSN],
                $configuration
            );
        }
        catch(\Throwable $throwable)
        {
            throw new StorageConfigurationException($throwable->getMessage(), 0, $throwable);
        }

        if(true === $createTables)
        {
            (new SchemaBuilder($connection))->updateSchema();
        }

        return $connection;
    }

    /**
     * Close constructor
     *
     * @codeCoverageIgnore
     */
    private function __construct()
    {

    }
}
