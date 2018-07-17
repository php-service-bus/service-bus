<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Transport\AmqpExt;

use Desperado\ServiceBus\Transport\Exceptions\InvalidConnectionParameters;

/**
 * Amqp configuration details
 */
final class AmqpConfiguration
{
    private const DEFAULT_SCHEMA       = 'amqp';
    private const DEFAULT_HOST         = 'localhost';
    private const DEFAULT_PORT         = 5672;
    private const DEFAULT_USERNAME     = 'guest';
    private const DEFAULT_PASSWORD     = 'guest';
    private const DEFAULT_TIMEOUT      = 1;
    private const DEFAULT_VIRTUAL_HOST = '/';

    /**
     * Connection DSN parameters bag
     *
     * Created from array with keys:
     *
     * [
     *     'scheme'   => 'amqp',
     *     'user'     => 'guest',
     *     'password' => 'guest',
     *     'host'     => 'localhost',
     *     'port'     => 5672,
     *     'vhost'    => '/',
     *     'timeout'  => 1
     * ]
     *
     * @var array
     */
    private $data;

    /**
     * @param string $connectionDSN
     *
     * @return self
     *
     * @throws \Desperado\ServiceBus\Transport\Exceptions\InvalidConnectionParameters
     */
    public static function create(string $connectionDSN): self
    {
        $self       = new self();
        $self->data = self::extractConnectionParameters($connectionDSN);

        return $self;
    }

    /**
     * @noinspection PhpDocMissingThrowsInspection
     *
     * @return self
     */
    public static function createLocalhost(): self
    {
        $self = new self();

        /** @noinspection PhpUnhandledExceptionInspection */
        $self->data = self::extractConnectionParameters(
            'amqp://guest:guest@localhost:5672'
        );

        return $self;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return \sprintf(
            '%s://%s:%s@%s:%s?vhost=%s&timeout=%d',
            $this->data['scheme'],
            $this->data['user'],
            $this->data['password'],
            $this->data['host'],
            $this->data['port'],
            $this->data['vhost'],
            $this->data['timeout']
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
     * @return array
     *
     * @throws \Desperado\ServiceBus\Transport\Exceptions\InvalidConnectionParameters
     */
    private static function extractConnectionParameters(string $connectionDSN): array
    {
        $connectionParts = self::parseUrl($connectionDSN);
        $queryParts      = self::parseQuery($connectionParts['query'] ?? '');

        return [
            'scheme'   => $connectionParts['scheme'] ?? self::DEFAULT_SCHEMA,
            'host'     => $connectionParts['host'] ?? self::DEFAULT_HOST,
            'port'     => $connectionParts['port'] ?? self::DEFAULT_PORT,
            'user'     => $connectionParts['user'] ?? self::DEFAULT_USERNAME,
            'password' => $connectionParts['pass'] ?? self::DEFAULT_PASSWORD,
            'timeout'  => $queryParts['timeout'] ?? self::DEFAULT_TIMEOUT,
            'vhost'    => $queryParts['vhost'] ?? self::DEFAULT_VIRTUAL_HOST,
        ];
    }

    /**
     * Parse connection DSN parts
     *
     * @param string $url
     *
     * @return array
     *
     * @throws \Desperado\ServiceBus\Transport\Exceptions\InvalidConnectionParameters
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
     * @return array
     */
    private static function parseQuery(string $query): array
    {
        $output = [];

        \parse_str($query, $output);

        return $output;
    }

    /**
     * Close constructor
     */
    private function __construct()
    {

    }
}
