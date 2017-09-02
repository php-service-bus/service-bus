<?php

/**
 * Command Query Responsibility Segregation, Event Sourcing implementation
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @url     https://github.com/mmasiukevich
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\Framework\Infrastructure\EventSourcing\Storage\Configuration;

/**
 * Storage config DTO
 */
class StorageConfigurationConfig
{
    /**
     * Storage driver
     *
     * @var string
     */
    private $driver;

    /**
     * Host data
     *
     * @var StorageHost
     */
    private $host;

    /**
     * Auth data
     *
     * @var StorageAuth
     */
    private $auth;

    /**
     * Storage options
     *
     * @var StorageOptions
     */
    private $options;

    /**
     * Database name
     *
     * @var string
     */
    private $database;

    /**
     * @param string         $driver
     * @param StorageHost    $host
     * @param StorageAuth    $auth
     * @param string         $database
     * @param StorageOptions $options
     */
    public function __construct(string $driver, StorageHost $host, StorageAuth $auth, string $database, StorageOptions $options)
    {
        $this->driver = $driver;
        $this->host = $host;
        $this->auth = $auth;
        $this->options = $options;
        $this->database = $database;
    }

    /**
     * Create from DSN
     *
     * Example:
     *
     *  - amphpPgSql:localhost:5432?user=postgres&password=123456789&dbname=temp&encoding=UTF-8
     *  - inMemory:?
     *
     * @param string $connectionDSN
     *
     * @return StorageConfigurationConfig
     */
    public static function fromDSN(string $connectionDSN): self
    {
        $parameters = StorageConnectionDsnParser::parse($connectionDSN);

        return new self(
            $parameters->getAsString('driver'),
            new StorageHost($parameters->getAsString('host'), $parameters->getAsInt('port')),
            new StorageAuth($parameters->getAsString('user'), $parameters->getAsString('password')),
            $parameters->getAsString('dbname'),
            new StorageOptions($parameters->getAsString('encoding'))
        );
    }

    /**
     * Get driver
     *
     * @return string
     */
    public function getDriver(): string
    {
        return $this->driver;
    }

    /**
     * Get host data
     *
     * @return StorageHost
     */
    public function getHost(): StorageHost
    {
        return $this->host;
    }

    /**
     * Get auth data
     *
     * @return StorageAuth
     */
    public function getAuth(): StorageAuth
    {
        return $this->auth;
    }

    /**
     * Get options
     *
     * @return StorageOptions
     */
    public function getOptions(): StorageOptions
    {
        return $this->options;
    }

    /**
     * Get database name
     *
     * @return string
     */
    public
    function getDatabase()
    {
        return $this->database;
    }
}
