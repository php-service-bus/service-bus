<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Tests\HttpServer;

use Desperado\ServiceBus\HttpServer\HttpServerConfiguration;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class HttpServerConfigurationTest extends TestCase
{
    /**
     * @test
     *
     * @return void
     */
    public function successCreate(): void
    {
        $config = HttpServerConfiguration::create('localhost', 80, true, __FILE__);

        static::assertEquals('localhost', $config->getHots());
        static::assertEquals(80, $config->getPort());
        static::assertTrue($config->isSecured());
        static::assertEquals(__FILE__, $config->getCertificateFilePath());
    }

    /**
     * @test
     *
     * @return void
     */
    public function createLocalhost(): void
    {
        $config = HttpServerConfiguration::createLocalhost(100);

        static::assertEquals('0.0.0.0', $config->getHots());
        static::assertEquals(100, $config->getPort());
        static::assertFalse($config->isSecured());
        static::assertNull($config->getCertificateFilePath());
    }

    /**
     * @test
     *
     * @return void
     */
    public function createSecuredLocalhost(): void
    {
        $config = HttpServerConfiguration::createSecuredLocalhost(__FILE__, 100);

        static::assertEquals('0.0.0.0', $config->getHots());
        static::assertEquals(100, $config->getPort());
        static::assertTrue($config->isSecured());
        static::assertEquals(__FILE__, $config->getCertificateFilePath());
    }

    /**
     * @test
     * @expectedException \Desperado\ServiceBus\HttpServer\Exceptions\IncorrectHttpServerHostException
     * @expectedExceptionMessage Empty listen host
     *
     * @return void
     */
    public function createWithEmptyHost(): void
    {
        HttpServerConfiguration::create('', 80, true, __FILE__);
    }


    /**
     * @test
     * @expectedException \Desperado\ServiceBus\HttpServer\Exceptions\IncorrectHttpServerPortException
     * @expectedExceptionMessage Incorrect listen port specified ("-1")
     *
     * @return void
     */
    public function createWithWrongPort(): void
    {
        HttpServerConfiguration::create('localhost', -1, true, __FILE__);
    }

    /**
     * @test
     * @expectedException \Desperado\ServiceBus\HttpServer\Exceptions\IncorrectHttpServerCertException
     * @expectedExceptionMessage Certificate file path must be specified
     *
     * @return void
     */
    public function createSecuredWithEmptyCertificate(): void
    {
        HttpServerConfiguration::create('localhost', 80, true, '');
    }

    /**
     * @test
     * @expectedException \Desperado\ServiceBus\HttpServer\Exceptions\IncorrectHttpServerCertException
     * @expectedExceptionMessage Certificate file path not found or not readable ("/tmp/notExistsCertificate")
     *
     * @return void
     */
    public function createSecuredWithNotFoundCertificate(): void
    {
        HttpServerConfiguration::create('localhost', 80, true, '/tmp/notExistsCertificate');
    }
}
