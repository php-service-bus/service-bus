<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Transport\RabbitMqTransport;

use Desperado\Domain\ParameterBag;
use Desperado\ServiceBus\Transport\Exceptions as TransportExceptions;

/**
 * Configuration of RabbitMQ transport
 */
class RabbitMqTransportConfig
{
    private const DEFAULT_SCHEMA = 'amqp';
    private const DEFAULT_HOST = 'localhost';
    private const DEFAULT_PORT = 5672;
    private const DEFAULT_USERNAME = 'guest';
    private const DEFAULT_PASSWORD = 'guest';
    private const DEFAULT_TIMEOUT = 1;
    private const DEFAULT_VIRTUAL_HOST = '/';

    private const DEFAULT_QOS_PRE_FETCH_SIZE = 0;
    private const DEFAULT_QOS_PRE_FETCH_COUNT = 5;
    private const DEFAULT_QOS_GLOBAL = false;

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
     * @var ParameterBag
     */
    private $connectionConfig;

    /**
     * QOS settings
     *
     * @see http://www.rabbitmq.com/consumer-prefetch.html
     *
     * Created from array with keys:
     *
     * [
     *     'pre_fetch_size'  => 0,
     *     'pre_fetch_count' => 5,
     *     'global'          => false,
     * ]
     *
     * @var ParameterBag
     */
    private $qosConfig;

    /**
     * Create transport configuration for localhost
     *
     * Qos settings example:
     *
     * [
     *      'pre_fetch_count'  => 1,
     *      'pre_fetch_size'   => 0,
     *      'global' => false
     * ]
     *
     * @see http://www.rabbitmq.com/consumer-prefetch.html
     *
     * @param string $username by default 'guest'
     * @param string $password by default 'guest'
     * @param array  $qosSettings
     *
     * @return RabbitMqTransportConfig
     *
     * @throws TransportExceptions\IncorrectTransportConfigurationException
     */
    public static function createLocalhost(
        string $username = self::DEFAULT_USERNAME,
        string $password = self::DEFAULT_PASSWORD,
        array $qosSettings = []
    ): self
    {
        return new self(
            \sprintf('amqp://%s:%s@localhost:5672', $username, $password),
            $qosSettings
        );
    }

    /**
     * Connection DSN example: amqp://admin:admin@localhost:5672
     *
     * Qos settings example:
     *
     * [
     *      'pre_fetch_count'  => 1,
     *      'pre_fetch_size'   => 0,
     *      'global' => false
     * ]
     *
     * @see http://www.rabbitmq.com/consumer-prefetch.html
     *
     * @param string $connectionDSN
     * @param array  $qosSettings
     *
     * @return RabbitMqTransportConfig
     *
     * @throws TransportExceptions\IncorrectTransportConfigurationException
     */
    public static function createFromDSN(string $connectionDSN, array $qosSettings = []): self
    {
        return new self($connectionDSN, $qosSettings);
    }

    /**
     * Get connection configuration
     *
     * @return ParameterBag
     */
    public function getConnectionConfig(): ParameterBag
    {
        return $this->connectionConfig;
    }

    /**
     * Get QOS settings
     *
     * @return ParameterBag
     */
    public function getQosConfig(): ParameterBag
    {
        return $this->qosConfig;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return \sprintf(
            '%s://%s:%s@%s:%s?vhost=%s&timeout=%d',
            $this->connectionConfig->get('scheme'),
            $this->connectionConfig->get('user'),
            $this->connectionConfig->get('password'),
            $this->connectionConfig->get('host'),
            $this->connectionConfig->get('port'),
            $this->connectionConfig->get('vhost'),
            $this->connectionConfig->get('timeout')
        );
    }

    /**
     * Extract QOS config
     *
     * @param array $qosConfigData
     *
     * @return array
     *
     * @throws TransportExceptions\IncorrectTransportConfigurationException
     */
    private function extractQosConfiguration(array $qosConfigData): array
    {
        $intFilter = function($value, string $field)
        {
            $value = (int) $value;

            if(0 > $value)
            {
                throw new TransportExceptions\IncorrectTransportConfigurationException(
                    \sprintf('"%s" value must be greater or equals than zero', $field)
                );
            }

            return $value;
        };

        $fetchSize = $qosConfigData['pre_fetch_size'] ?? self::DEFAULT_QOS_PRE_FETCH_SIZE;
        $fetchCount = $qosConfigData['pre_fetch_count'] ?? self::DEFAULT_QOS_PRE_FETCH_COUNT;
        $globalFlag = $qosConfigData['global'] ?? self::DEFAULT_QOS_GLOBAL;

        return [
            'pre_fetch_size'  => $intFilter($fetchSize, 'pre_fetch_size'),
            'pre_fetch_count' => $intFilter($fetchCount, 'pre_fetch_count'),
            'global'          => (bool) $globalFlag
        ];
    }

    /**
     * @param string $connectionDSN
     *
     * @return array
     *
     * @throws TransportExceptions\IncorrectTransportConfigurationException
     */
    private function extractConnectionParameters(string $connectionDSN): array
    {
        $connectionParts = new ParameterBag($this->parseUrl($connectionDSN));
        $queryParts = new ParameterBag(
            $this->parseQuery(
                $connectionParts->get('query', '')
            )
        );

        return [
            'scheme'   => $connectionParts->getAsString('scheme', self::DEFAULT_SCHEMA),
            'host'     => $connectionParts->getAsString('host', self::DEFAULT_HOST),
            'port'     => $connectionParts->getAsInt('port', self::DEFAULT_PORT),
            'user'     => $connectionParts->getAsString('user', self::DEFAULT_USERNAME),
            'password' => $connectionParts->getAsString('pass', self::DEFAULT_PASSWORD),
            'timeout'  => $queryParts->getAsInt('timeout', self::DEFAULT_TIMEOUT),
            'vhost'    => $queryParts->getAsString('vhost', self::DEFAULT_VIRTUAL_HOST)
        ];
    }

    /**
     * Parse connection DSN parts
     *
     * @param string $url
     *
     * @return array
     *
     * @throws TransportExceptions\IncorrectTransportConfigurationException
     */
    private function parseUrl(string $url): array
    {
        if('' === $url)
        {
            throw new TransportExceptions\IncorrectTransportConfigurationException('Connection DSN can\'t be empty');
        }

        $parsedParts = \parse_url($url);

        if(false !== $parsedParts)
        {
            return $parsedParts;
        }

        throw new TransportExceptions\IncorrectTransportConfigurationException(
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
    private function parseQuery(string $query): array
    {
        $output = [];

        \parse_str($query, $output);

        return $output;
    }

    /**
     * Connection DSN example: amqp://admin:admin@localhost:5672
     *
     * Qos settings example:
     *
     * [
     *      'pre_fetch_count'  => 1,
     *      'pre_fetch_size'   => 0,
     *      'global' => false
     * ]
     *
     * @param string $connectionDSN
     * @param array  $qosSettings
     *
     * @throws TransportExceptions\IncorrectTransportConfigurationException
     */
    private function __construct(string $connectionDSN, array $qosSettings)
    {
        $this->connectionConfig = new ParameterBag($this->extractConnectionParameters($connectionDSN));
        $this->qosConfig = new ParameterBag($this->extractQosConfiguration($qosSettings));
    }
}
