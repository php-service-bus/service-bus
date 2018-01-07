<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace Desperado\ServiceBus\Tests\Transport\RabbitMqTransport;

use Desperado\ServiceBus\Transport\RabbitMqTransport\RabbitMqTransportConfig;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class RabbitMqTransportConfigTest extends TestCase
{

    /**
     * @test
     * @expectedException \Desperado\ServiceBus\Transport\Exceptions\IncorrectTransportConfigurationException
     * @expectedExceptionMessage Can't parse specified connection DSN (///example.org:80)
     *
     * @return void
     */
    public function invalidConnectionDsnFormat(): void
    {
        RabbitMqTransportConfig::createFromDSN('///example.org:80');
    }

    /**
     * @test
     * @expectedException \Desperado\ServiceBus\Transport\Exceptions\IncorrectTransportConfigurationException
     * @expectedExceptionMessage Connection DSN can't be empty
     *
     * @return void
     */
    public function emptyConnectionDSN(): void
    {
        RabbitMqTransportConfig::createFromDSN('');
    }

    /**
     * @test
     *
     * @return void
     */
    public function validConnectionDSN(): void
    {
        $connectionDSN = 'amqp://admin:admin123@localhost:5672?vhost=/&timeout=1';

        $configuration = RabbitMqTransportConfig::createFromDSN($connectionDSN, ['pre_fetch_size' => 0]);

        static::assertEquals($connectionDSN, (string)$configuration);
        static::assertEquals(5, $configuration->getQosConfig()->get('pre_fetch_count'));
    }

    /**
     * @test
     *
     * @return void
     */
    public function successCreateLocalhost(): void
    {
        $configuration = RabbitMqTransportConfig::createLocalhost(
            'testUsername',
            'testPassword',
            ['pre_fetch_size' => 10]
        );

        static::assertEquals('testUsername', $configuration->getConnectionConfig()->get('user'));
        static::assertEquals('testPassword', $configuration->getConnectionConfig()->get('password'));
        static::assertEquals(10, $configuration->getQosConfig()->get('pre_fetch_size'));
    }

    /**
     * @test
     * @expectedException \Desperado\ServiceBus\Transport\Exceptions\IncorrectTransportConfigurationException
     * @expectedExceptionMessage "pre_fetch_size" value must be greater or equals than zero
     *
     * @return void
     */
    public function wrongPreFetchSize(): void
    {
        $connectionDSN = 'amqp://admin:admin123@localhost:5672?vhost=/&timeout=1';

        RabbitMqTransportConfig::createFromDSN($connectionDSN, ['pre_fetch_size' => -1]);
    }
}
