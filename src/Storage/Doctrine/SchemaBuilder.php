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

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;

/**
 * Creating tables
 */
final class SchemaBuilder
{
    public const TABLE_NAME_SAGAS = 'sagas_store';
    public const TABLE_NAME_SCHEDULER = 'scheduler_store';

    /**
     * Connection instance
     *
     * @var Connection
     */
    private $connection;

    /**
     * Schema object
     *
     * @var Schema
     */
    private $schema;

    /**
     * Schema manager
     *
     * @var AbstractSchemaManager
     */
    private $schemaManager;

    /**
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
        $this->schema = new Schema();
        $this->schemaManager = $connection->getSchemaManager();
    }

    /**
     * Execute update schema
     *
     * @return void
     *
     * @throws \Exception
     */
    public function updateSchema(): void
    {
        $this->processSagasTable();
        $this->processSchedulerTable();

        $queries = $this->schema->toSql($this->connection->getDatabasePlatform());

        if(0 !== \count($queries))
        {
            $this->connection->transactional(
                function() use ($queries)
                {
                    // @codeCoverageIgnoreStart
                    if('pdo_pgsql' === $this->connection->getDriver()->getName())
                    {
                        $this->connection->executeQuery('CREATE EXTENSION IF NOT EXISTS "uuid-ossp";');
                    }
                    // @codeCoverageIgnoreEnd

                    foreach($queries as $query)
                    {
                        $this->connection->executeQuery($query);
                    }
                }
            );
        }
    }

    /**
     * Create `sagas_store` table object
     *
     * @return void
     *
     * @throws \Exception
     */
    private function processSagasTable(): void
    {
        if(false === $this->schemaManager->tablesExist([self::TABLE_NAME_SAGAS]))
        {
            $sagasTable = $this->schema->createTable(self::TABLE_NAME_SAGAS);

            $sagasTable->addColumn('id', Type::GUID, ['Notnull' => 1]);
            $sagasTable->addColumn('identifier_class', Type::STRING, ['Notnull' => 1, 'Length' => 255]);
            $sagasTable->addColumn('saga_class', Type::STRING, ['Notnull' => 1, 'Length' => 255]);
            $sagasTable->addColumn('payload', Type::TEXT, ['Notnull' => 1]);
            $sagasTable->addColumn('state_id', Type::SMALLINT, ['Notnull' => 1]);
            $sagasTable->addColumn('created_at', Type::DATETIME, ['Default' => 'now()']);
            $sagasTable->addColumn('closed_at', Type::DATETIME, ['Notnull' => 0]);

            $sagasTable->setPrimaryKey(['id', 'identifier_class'], 'saga_identifier');

            $sagasTable->addIndex(['state_id'], 'saga_state');
            $sagasTable->addIndex(['state_id', 'closed_at'], 'saga_closed_index');
        }
    }

    /**
     * Create `scheduler_store` table object
     *
     * @return void
     *
     * @throws \Exception
     */
    private function processSchedulerTable(): void
    {
        if(false === $this->schemaManager->tablesExist([self::TABLE_NAME_SCHEDULER]))
        {
            $schedulerTable = $this->schema->createTable(self::TABLE_NAME_SCHEDULER);

            $schedulerTable->addColumn('id', Type::GUID, ['Notnull' => 1]);
            $schedulerTable->addColumn('data', Type::BINARY, ['Notnull' => 1]);

            $schedulerTable->setPrimaryKey(['id'], 'scheduler_identifier');
        }
    }
}
