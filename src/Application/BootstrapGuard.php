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

namespace Desperado\Framework\Application;

use Desperado\Domain\Environment\Environment;
use Desperado\Framework\Exceptions\EntryPointException;

/**
 * Validate environment values
 */
class BootstrapGuard
{
    /**
     * Assert file/directory exists and readable
     *
     * @param string $absolutePath
     * @param string $exceptionMessage
     *
     * @return void
     *
     * @throws EntryPointException
     */
    public static function guardPath(string $absolutePath, string $exceptionMessage): void
    {
        if(
            '' === (string) $absolutePath ||
            false === \file_exists($absolutePath) ||
            false === \is_readable($absolutePath)
        )
        {
            throw new EntryPointException($exceptionMessage);
        }
    }

    /**
     * Assert environment is valid
     *
     * @param string $environment
     *
     * @return void
     *
     * @throws EntryPointException
     */
    public static function guardEnvironment(string $environment): void
    {
        if(
            '' === $environment ||
            false === \in_array($environment, Environment::LIST, true)
        )
        {
            throw new EntryPointException(
                \sprintf(
                    'Invalid environment specified ("%s"). Supported: %s',
                    $environment,
                    \implode(', ', Environment::LIST)

                )
            );
        }
    }

    /**
     * Assert entry point specified
     *
     * @param string $entryPointName
     *
     * @return void
     */
    public static function guardEntryPointName(string $entryPointName): void
    {
        if('' === $entryPointName)
        {
            throw new EntryPointException(
                'Entry point name must be specified (see APP_ENTRY_POINT_NAME environment variable)'
            );
        }
    }

    /**
     * @codeCoverageIgnore
     */
    private function __construct()
    {

    }
}
