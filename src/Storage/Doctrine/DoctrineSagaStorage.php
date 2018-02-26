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

use Desperado\ServiceBus\Saga\Identifier\AbstractSagaIdentifier;
use Desperado\ServiceBus\Saga\Storage\SagaStorageInterface;
use Desperado\ServiceBus\Saga\Storage\StoredSaga;
use Doctrine\DBAL\Connection;

/**
 * Doctrine2 saga storage
 */
final class DoctrineSagaStorage implements SagaStorageInterface
{
    /**
     * Doctrine2 connection
     *
     * @var Connection
     */
    private $connection;

    /**
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @inheritdoc
     */
    public function save(StoredSaga $storedSaga): void
    {
        try
        {
            $this->connection
                ->createQueryBuilder()
                ->insert(SchemaBuilder::TABLE_NAME_SAGAS)
                ->values([
                        'id'               => '?',
                        'identifier_class' => '?',
                        'saga_class'       => '?',
                        'payload'          => '?',
                        'state_id'         => '?',
                        'created_at'       => '?',
                        'closed_at'        => '?'
                    ]
                )
                ->setParameters([
                    $storedSaga->getIdentifier(),
                    $storedSaga->getIdentifierNamespace(),
                    $storedSaga->getSagaNamespace(),
                    $storedSaga->getPayload(),
                    $storedSaga->getState(),
                    $storedSaga->getCreatedAt()->toString('Y-m-d H:i:s'),
                    true === $storedSaga->isClosed()
                        ? $storedSaga->getClosedAt()->toString('Y-m-d H:i:s')
                        : null
                ])
                ->execute();
        }
        catch(\Throwable $throwable)
        {
            throw DoctrineExceptionConverter::convert($throwable);
        }
    }

    /**
     * @inheritdoc
     */
    public function update(StoredSaga $storedSaga): void
    {
        try
        {
            $this->connection
                ->createQueryBuilder()
                ->update(SchemaBuilder::TABLE_NAME_SAGAS)
                ->set('payload', '?')
                ->set('state_id', '?')
                ->set('created_at', '?')
                ->set('closed_at', '?')
                ->where('id = ?')
                ->andWhere('identifier_class = ?')
                ->setParameters([
                    $storedSaga->getPayload(),
                    $storedSaga->getState(),
                    $storedSaga->getCreatedAt()->toString('Y-m-d H:i:s'),
                    true === $storedSaga->isClosed()
                        ? $storedSaga->getClosedAt()->toString('Y-m-d H:i:s')
                        : null,
                    $storedSaga->getIdentifier(),
                    $storedSaga->getIdentifierNamespace()
                ])
                ->execute();
        }
        catch(\Throwable $throwable)
        {
            throw DoctrineExceptionConverter::convert($throwable);
        }
    }

    /**
     * @inheritdoc
     */
    public function load(AbstractSagaIdentifier $id): ?StoredSaga
    {
        try
        {
            $row = $this->connection
                ->createQueryBuilder()
                ->select('s.*')
                ->from(SchemaBuilder::TABLE_NAME_SAGAS, 's')
                ->where('s.id = ?')
                ->andWhere('s.identifier_class = ?')
                ->setParameters([$id->toString(), $id->getIdentityClass()])
                ->execute()
                ->fetch();

            if(false !== $row && 0 !== \count($row))
            {
                return StoredSaga::restore(
                    $row['saga_class'],
                    $row['id'],
                    $row['identifier_class'],
                    $row['payload'],
                    (int) $row['state_id'],
                    $row['created_at'],
                    '' !== (string) $row['closed_at']
                        ? $row['closed_at']
                        : null
                );
            }

            return null;
        }
        catch(\Throwable $throwable)
        {
            throw DoctrineExceptionConverter::convert($throwable);
        }
    }

    /**
     * @inheritdoc
     */
    public function remove(AbstractSagaIdentifier $id): void
    {
        try
        {
            $this->connection
                ->createQueryBuilder()
                ->delete(SchemaBuilder::TABLE_NAME_SAGAS)
                ->where('id = ?')
                ->andWhere('identifier_class = ?')
                ->setParameters([$id->toString(), $id->getIdentityClass()])
                ->execute();
        }
        catch(\Throwable $throwable)
        {
            throw DoctrineExceptionConverter::convert($throwable);
        }
    }
}
