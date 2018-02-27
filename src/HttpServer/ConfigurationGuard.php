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
 * Validate configuration helper
 */
final class ConfigurationGuard
{
    /**
     * Validate specified host
     *
     * @param string $host
     *
     * @return void
     *
     * @throws ConfigurationExceptions\IncorrectHttpServerHostException
     */
    public static function validateHost(string $host): void
    {
        if('' === $host)
        {
            throw new ConfigurationExceptions\IncorrectHttpServerHostException('Empty listen host');
        }
    }

    /**
     * Validate specified port
     *
     * @param int $port
     *
     * @return void
     *
     * @throws ConfigurationExceptions\IncorrectHttpServerPortException
     */
    public static function validatePort(int $port): void
    {
        if(0 >= $port)
        {
            throw new ConfigurationExceptions\IncorrectHttpServerPortException(
                \sprintf('Incorrect listen port specified ("%s")', $port)
            );
        }
    }

    /**
     * Process validate certificate file path
     *
     * @param string $certificateFilePath
     *
     * @return void
     *
     * @throws ConfigurationExceptions\IncorrectHttpServerCertException
     */
    public static function validateCertificatePath(string $certificateFilePath): void
    {
        if('' === $certificateFilePath)
        {
            throw new ConfigurationExceptions\IncorrectHttpServerCertException('Certificate file path must be specified');
        }

        if(false === \file_exists($certificateFilePath) || false === \is_readable($certificateFilePath))
        {
            throw new ConfigurationExceptions\IncorrectHttpServerCertException(
                \sprintf(
                    'Certificate file path not found or not readable ("%s")', $certificateFilePath
                )
            );
        }
    }

    /**
     * Close constructor
     */
    private function __construct()
    {

    }
}
