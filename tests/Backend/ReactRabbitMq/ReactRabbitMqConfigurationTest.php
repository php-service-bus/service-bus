<?php

/**
 * CQRS/Event Sourcing framework
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @url     https://github.com/mmasiukevich
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\Framework\Tests\Backend\ReactRabbitMq;

use Desperado\Framework\Backend\ReactRabbitMq\ReactRabbitMqConfiguration;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class ReactRabbitMqConfigurationTest extends TestCase
{
    /**
     * @test
     * @expectedException \Desperado\Framework\Backend\InvalidDaemonConfigurationException
     * @expectedExceptionMessage Can't parse specified connection DSN (///example.org:80)
     *
     * @return void
     */
    public function invalidConnectionDsnFormat(): void
    {
        new ReactRabbitMqConfiguration('///example.org:80', []);
    }

    /**
     * @test
     * @expectedException \Desperado\Framework\Backend\InvalidDaemonConfigurationException
     * @expectedExceptionMessage Connection DSN can't be empty
     *
     * @return void
     */
    public function emptyConnectionDSN(): void
    {
        new ReactRabbitMqConfiguration('', []);
    }

    /**
     * @test
     *
     * @return void
     */
    public function validConnectionDSN(): void
    {
        $connectionDSN = 'amqp://admin:admin123@localhost:5672?vhost=/&timeout=1';

        $configuration = new ReactRabbitMqConfiguration($connectionDSN, ['pre_fetch_size' => 0]);

        static::assertEquals($connectionDSN, (string) $configuration);
        static::assertEquals(5, $configuration->getQosConfig()->get('pre_fetch_count'));
    }

    /**
     * @test
     * @expectedException \Desperado\Framework\Backend\InvalidDaemonConfigurationException
     * @expectedExceptionMessage "pre_fetch_size" value must be greater or equals than zero
     *
     * @return void
     */
    public function wrongPreFetchSize(): void
    {
        $connectionDSN = 'amqp://admin:admin123@localhost:5672?vhost=/&timeout=1';

        new ReactRabbitMqConfiguration($connectionDSN, ['pre_fetch_size' => -1]);
    }
}
