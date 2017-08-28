<?php

/**
 * CQRS/Event Sourcing Non-blocking concurrency framework
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @url     https://github.com/mmasiukevich
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ConcurrencyFramework\Domain\Environment;

use Desperado\ConcurrencyFramework\Domain\Environment\Exceptions\InvalidEnvironmentException;

/**
 * Environment
 */
class Environment
{
    public const ENVIRONMENT_PRODUCTION = 'prod';
    public const ENVIRONMENT_SANDBOX = 'dev';
    public const ENVIRONMENT_TESTING = 'test';

    private const LIST = [
        self::ENVIRONMENT_PRODUCTION,
        self::ENVIRONMENT_SANDBOX,
        self::ENVIRONMENT_TESTING
    ];

    /**
     * Application environment
     *
     * @var string
     */
    protected $environment;

    /**
     * Environment constructor.
     *
     * @param string $environment
     *
     * @throws InvalidEnvironmentException
     */
    public function __construct(string $environment)
    {
        $environment = \strtolower($environment);

        if('' === $environment || false === \in_array($environment, self::LIST, true))
        {
            throw new InvalidEnvironmentException(
                \sprintf(
                    'Wrong environment specified ("%s"). Expected choices: %s',
                    $environment, \implode(', ', \array_values(self::LIST))
                )
            );
        }

        $this->environment = $environment;
    }

    /**
     * Is debug environment
     *
     * @return bool
     */
    public function isDebug(): bool
    {
        return self::ENVIRONMENT_PRODUCTION !== $this->environment;
    }

    /**
     * Get string environment representation
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->environment;
    }
}
