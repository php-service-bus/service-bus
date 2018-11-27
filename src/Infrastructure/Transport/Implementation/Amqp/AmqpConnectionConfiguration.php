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

namespace Desperado\ServiceBus\Infrastructure\Transport\Implementation\Amqp;

use Desperado\ServiceBus\Infrastructure\Transport\Exceptions\InvalidConnectionParameters;

/**
 * Amqp connection details
 */
final class AmqpConnectionConfiguration
{
    private const DEFAULT_SCHEMA             = 'amqp';
    private const DEFAULT_HOST               = 'localhost';
    private const DEFAULT_PORT               = 5672;
    private const DEFAULT_USERNAME           = 'guest';
    private const DEFAULT_PASSWORD           = 'guest';
    private const DEFAULT_TIMEOUT            = 1;
    private const DEFAULT_HEARTBEAT_INTERVAL = 60.0;
    private const DEFAULT_VIRTUAL_HOST       = '/';

    /**
     * Connection DSN parameters bag
     *
     * Created from array with keys:
     *
     * @var array{
     *    scheme:string,
     *    user:string,
     *    password:string,
     *    host:string,
     *    port:int,
     *    vhost:string,
     *    timeout:float,
     *    heartbeat:float
     * }
     */
    private $data;

    /**
     * @param string $connectionDSN DSN example: amqp://user:password@host:port
     *
     * @throws \Desperado\ServiceBus\Infrastructure\Transport\Exceptions\InvalidConnectionParameters Incorrect DSN
     */
    public function __construct(string $connectionDSN)
    {
        $this->data = self::extractConnectionParameters($connectionDSN);
    }

    /**
     * @noinspection PhpDocMissingThrowsInspection
     *
     * @return self
     */
    public static function createLocalhost(): self
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        return new self('amqp://guest:guest@localhost:5672');
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return \sprintf(
            '%s://%s:%s@%s:%s?vhost=%s&timeout=%d&heartbeat=%.2f',
            $this->data['scheme'],
            $this->data['user'],
            $this->data['password'],
            $this->data['host'],
            $this->data['port'],
            $this->data['vhost'],
            $this->data['timeout'],
            $this->data['heartbeat']
        );
    }

    /**
     * Receive connection timeout
     *
     * @return float
     */
    public function timeout(): float
    {
        return (float) $this->data['timeout'];
    }

    /**
     * Receive heartbeat interval
     *
     * @return float
     */
    public function heartbeatInterval(): float
    {
        return (float) $this->data['heartbeat'];
    }

    /**
     * Get virtual host path
     *
     * @return string
     */
    public function virtualHost(): string
    {
        return $this->data['vhost'];
    }

    /**
     * Receive connection username
     *
     * @return string
     */
    public function user(): string
    {
        return $this->data['user'];
    }

    /**
     * Receive connection password
     *
     * @return string
     */
    public function password(): string
    {
        return $this->data['password'];
    }

    /**
     * Receive connection host
     *
     * @return string
     */
    public function host(): string
    {
        return $this->data['host'];
    }

    /**
     * Receive connection port
     *
     * @return int
     */
    public function port(): int
    {
        return $this->data['port'];
    }

    /**
     * @param string $connectionDSN
     *
     * @return array{scheme:string, user:string, password:string, host:string, port:int, vhost:string, timeout:float,
     *                              heartbeat:float}
     *
     * @throws \Desperado\ServiceBus\Infrastructure\Transport\Exceptions\InvalidConnectionParameters Incorrect DSN
     */
    private static function extractConnectionParameters(string $connectionDSN): array
    {
        $queryString = (string) ($connectionParts['query'] ?? '');

        $connectionParts = self::parseUrl($connectionDSN);
        $queryParts      = self::parseQuery($queryString);

        return [
            'scheme'    => (string) ($connectionParts['scheme'] ?? self::DEFAULT_SCHEMA),
            'host'      => (string) ($connectionParts['host'] ?? self::DEFAULT_HOST),
            'port'      => (int) ($connectionParts['port'] ?? self::DEFAULT_PORT),
            'user'      => (string) ($connectionParts['user'] ?? self::DEFAULT_USERNAME),
            'password'  => (string) ($connectionParts['pass'] ?? self::DEFAULT_PASSWORD),
            'timeout'   => (float) ($queryParts['timeout'] ?? self::DEFAULT_TIMEOUT),
            'vhost'     => (string) ($queryParts['vhost'] ?? self::DEFAULT_VIRTUAL_HOST),
            'heartbeat' => (float) ($queryParts['heartbeat'] ?? self::DEFAULT_HEARTBEAT_INTERVAL),
        ];
    }

    /**
     * Parse connection DSN parts
     *
     * @param string $url
     *
     * @return array
     *
     * @throws \Desperado\ServiceBus\Infrastructure\Transport\Exceptions\InvalidConnectionParameters Incorrect DSN
     */
    private static function parseUrl(string $url): array
    {
        if('' === $url)
        {
            throw new InvalidConnectionParameters('Connection DSN can\'t be empty');
        }

        $parsedParts = \parse_url($url);

        if(false !== $parsedParts)
        {
            return $parsedParts;
        }

        throw new InvalidConnectionParameters(
            \sprintf('Can\'t parse specified connection DSN (%s)', $url)
        );
    }

    /**
     * Parse url query parts
     *
     * @param string $query
     *
     * @return array<string, string|int|float>
     */
    private static function parseQuery(string $query): array
    {
        $output = [];

        \parse_str($query, $output);

        /** @var array<string, string|int|float> $output */

        return $output;
    }
}
