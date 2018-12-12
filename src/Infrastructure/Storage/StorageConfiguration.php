<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation)
 * Supports Saga pattern and Event Sourcing
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Infrastructure\Storage;

/**
 * Adapter configuration for storage
 */
final class StorageConfiguration
{
    /**
     * Original DSN
     *
     * @var string
     */
    public $originalDSN;

    /**
     * Scheme
     *
     * @var string|null
     */
    public $scheme;

    /**
     * Database host
     *
     * @var string|null
     */
    public $host;

    /**
     * Database port
     *
     * @var int|null
     */
    public $port;

    /**
     * Database user
     *
     * @var string|null
     */
    public $username;

    /**
     * Database user password
     *
     * @var string|null
     */
    public $password;

    /**
     * Database name
     *
     * @var string|null
     */
    public $databaseName;

    /**
     * Connection encoding
     *
     * @var string
     */
    public $encoding;

    /**
     * All query parameters
     *
     * @var array
     */
    public $queryParameters = [];

    /**
     * @param string $connectionDSN DSN examples:
     *                              - inMemory: sqlite:///:memory:
     *                              - AsyncPostgreSQL: pgsql://user:password@host:port/database
     *
     * @return self
     */
    public static function fromDSN(string $connectionDSN): self
    {
        $preparedDSN = \preg_replace('#^((?:pdo_)?sqlite3?):///#', '$1://localhost/', $connectionDSN);
        $parsedDSN   = \parse_url($preparedDSN);
        $self        = new self();

        $queryString = (string) ($parsedDSN['query'] ?? 'charset=UTF-8');

        \parse_str($queryString, $self->queryParameters);

        $self->originalDSN  = $connectionDSN;
        /** @psalm-suppress MixedAssignment */
        $self->scheme       = $parsedDSN['scheme'] ?? null;
        /** @psalm-suppress MixedAssignment */
        $self->host         = $parsedDSN['host'] ?? null;
        /** @psalm-suppress MixedAssignment */
        $self->port         = $parsedDSN['port'] ?? null;
        /** @psalm-suppress MixedAssignment */
        $self->username     = $parsedDSN['user'] ?? null;
        /** @psalm-suppress MixedAssignment */
        $self->password     = $parsedDSN['pass'] ?? null;
        $self->databaseName = $parsedDSN['path'] ? \ltrim((string) $parsedDSN['path'], '/') : null;
        /** @psalm-suppress MixedAssignment */
        $self->encoding     = $self->queryParameters['charset'] ?? 'UTF-8';

        return $self;
    }

    /**
     * Has specified credentials
     *
     * @return bool
     */
    public function hasCredentials(): bool
    {
        return '' !== (string) $this->username || '' !== (string) $this->password;
    }

    /**
     * Close constructor
     */
    private function __construct()
    {

    }
}
