<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\HttpServer;

use Desperado\ServiceBus\HttpServer\Exceptions as ConfigurationExceptions;

/**
 * Http server configuration
 */
final class HttpServerConfiguration
{
    private const DEFAULT_HOST = '0.0.0.0';
    private const DEFAULT_PORT = 1337;

    /**
     * Listen host
     *
     * @var string
     */
    private $hots;

    /**
     * Listen port
     *
     * @var int
     */
    private $port;

    /**
     * Is secured server
     * If active, you must specify the correct path to the certificate file
     *
     * @var bool
     */
    private $secured;

    /**
     * Absolute path to certificate file
     *
     * @var string|null
     */
    private $certificateFilePath;

    /**
     * @param string      $host
     * @param int         $port
     * @param bool        $secured
     * @param null|string $certificateFilePath
     *
     * @return self
     *
     * @throws ConfigurationExceptions\IncorrectHttpServerPortException
     * @throws ConfigurationExceptions\IncorrectHttpServerHostException
     * @throws ConfigurationExceptions\IncorrectHttpServerCertException
     */
    public static function create(
        string $host = self::DEFAULT_HOST,
        int $port = self::DEFAULT_PORT,
        bool $secured = false,
        ?string $certificateFilePath = null
    ): self
    {
        return new self($host, $port, $secured, $certificateFilePath);
    }

    /**
     * Non-secured localhost
     *
     * @param int $port
     *
     * @return self
     *
     * @throws ConfigurationExceptions\HttpServerConfigurationException
     */
    public static function createLocalhost(int $port = self::DEFAULT_PORT): self
    {
        return new self(
            self::DEFAULT_HOST,
            $port
        );
    }

    /**
     * Create secured localhost
     *
     * @param string $certificateFilePath
     * @param int    $port
     *
     * @return self
     *
     * @throws ConfigurationExceptions\HttpServerConfigurationException
     */
    public static function createSecuredLocalhost(string $certificateFilePath, int $port = self::DEFAULT_PORT): self
    {
        return new self(
            self::DEFAULT_HOST,
            $port,
            true,
            $certificateFilePath
        );
    }

    /**
     * Get listen host
     *
     * @return string
     */
    public function getHots(): string
    {
        return $this->hots;
    }

    /**
     * Get listen port
     *
     * @return int
     */
    public function getPort(): int
    {
        return $this->port;
    }

    /**
     * Get secured server flag
     *
     * @return bool
     */
    public function isSecured(): bool
    {
        return $this->secured;
    }

    /**
     * Get certificate file path
     *
     * @return string|null
     */
    public function getCertificateFilePath(): ?string
    {
        return $this->certificateFilePath;
    }

    /**
     * @param string      $host
     * @param int         $port
     * @param bool        $secured
     * @param null|string $certificateFilePath
     *
     * @throws ConfigurationExceptions\IncorrectHttpServerHostException
     * @throws ConfigurationExceptions\IncorrectHttpServerPortException
     * @throws ConfigurationExceptions\IncorrectHttpServerCertException
     */
    private function __construct(
        string $host = self::DEFAULT_HOST,
        int $port = self::DEFAULT_PORT,
        bool $secured = false,
        ?string $certificateFilePath = null
    )
    {
        $this->hots = $host;
        $this->port = $port;
        $this->secured = $secured;
        $this->certificateFilePath = $certificateFilePath;

        ConfigurationGuard::validateHost($host);
        ConfigurationGuard::validatePort($port);

        if(true === $secured)
        {
            ConfigurationGuard::validateCertificatePath($certificateFilePath);
        }
    }
}
