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
    private $originalDSN;

    /**
     * Scheme
     *
     * @var string|null
     */
    private $scheme;

    /**
     * Database host
     *
     * @var string|null
     */
    private $host;

    /**
     * Database port
     *
     * @var int|null
     */
    private $port;

    /**
     * Database user
     *
     * @var string|null
     */
    private $username;

    /**
     * Database user password
     *
     * @var string|null
     */
    private $password;

    /**
     * Database name
     *
     * @var string|null
     */
    private $databaseName;

    /**
     * Connection encoding
     *
     * @var string
     */
    private $encoding;

    /**
     * All query parameters
     *
     * @var array
     */
    private $queryParameters = [];

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
        $self->scheme       = isset($parsedDSN['scheme']) ? (string) $parsedDSN['scheme'] : null;
        $self->host         = isset($parsedDSN['host']) ? (string) $parsedDSN['host'] : null;
        $self->port         = isset($parsedDSN['port']) ? (int) $parsedDSN['port'] : null;
        $self->username     = isset($parsedDSN['user']) ? (string) $parsedDSN['user'] : null;
        $self->password     = isset($parsedDSN['pass']) ? (string) $parsedDSN['pass'] : null;
        $self->databaseName = isset($parsedDSN['path']) ? \ltrim((string) $parsedDSN['path'], '/') : null;
        $self->encoding     = isset($self->queryParameters['charset']) ? (string) $self->queryParameters['charset'] : 'UTF-8';

        return $self;
    }

    /**
     * Get original specified DSN
     *
     * @return string
     */
    public function originalDSN(): string
    {
        return $this->originalDSN;
    }

    /**
     * Get scheme
     *
     * @return string|null
     */
    public function scheme(): ?string
    {
        return $this->scheme;
    }

    /**
     * Get query parameters
     *
     * @return array
     */
    public function queryParameters(): array
    {
        return $this->queryParameters;
    }

    /**
     * Get database host
     *
     * @return string
     */
    public function host(): string
    {
        return (string) $this->host;
    }

    /**
     * Get database port
     *
     * @return int|null
     */
    public function port(): ?int
    {
        return $this->port;
    }

    /**
     * Get database username
     *
     * @return string|null
     */
    public function username(): ?string
    {
        return $this->username;
    }

    /**
     * Get database user password
     *
     * @return string|null
     */
    public function password(): ?string
    {
        return $this->password;
    }

    /**
     * Get database name
     *
     * @return string|null
     */
    public function databaseName(): ?string
    {
        return $this->databaseName;
    }

    /**
     * Get encoding
     *
     * @return string
     */
    public function encoding(): string
    {
        return $this->encoding;
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
