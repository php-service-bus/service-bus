<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <contacts@desperado.dev>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 0);

namespace ServiceBus;

/**
 * Application environment.
 */
final class Environment
{
    private const ENVIRONMENT_PRODUCTION = 'prod';

    private const ENVIRONMENT_SANDBOX = 'dev';

    private const ENVIRONMENT_TESTING = 'test';

    private const LIST                = [
        self::ENVIRONMENT_PRODUCTION,
        self::ENVIRONMENT_SANDBOX,
        self::ENVIRONMENT_TESTING,
    ];

    /**
     * @psalm-var non-empty-string
     *
     * @var string
     */
    private $environment;

    /**
     * Creating a test environment.
     */
    public static function test(): self
    {
        return new self(self::ENVIRONMENT_TESTING);
    }

    /**
     * Creating a develop environment.
     */
    public static function dev(): self
    {
        return new self(self::ENVIRONMENT_SANDBOX);
    }

    /**
     * Creating a production environment.
     */
    public static function prod(): self
    {
        return new self(self::ENVIRONMENT_PRODUCTION);
    }

    /**
     * Creating the specified environment.
     *
     * @psalm-param non-empty-string $environment
     *
     * @throws \LogicException The value of the environment is not specified, or is incorrect
     */
    public static function create(string $environment): self
    {
        $environment = \strtolower($environment);

        self::validateEnvironment($environment);

        return new self($environment);
    }

    /**
     * Is this environment for debugging?
     */
    public function isDebug(): bool
    {
        return self::ENVIRONMENT_PRODUCTION !== $this->environment;
    }

    /**
     * Is this environment for testing?
     */
    public function isTesting(): bool
    {
        return self::ENVIRONMENT_TESTING === $this->environment;
    }

    /**
     * Is this environment for production usage?
     */
    public function isProduction(): bool
    {
        return self::ENVIRONMENT_PRODUCTION === $this->environment;
    }

    /**
     * Is this environment for development usage?
     */
    public function isDevelopment(): bool
    {
        return self::ENVIRONMENT_SANDBOX === $this->environment;
    }

    /**
     * Is environments equals.
     */
    public function equals(Environment $environment): bool
    {
        return $this->environment === $environment->environment;
    }

    /**
     * Get a textual representation of the current environment.
     *
     * @psalm-return non-empty-string
     */
    public function toString(): string
    {
        return $this->environment;
    }

    /**
     * Validate the specified environment.
     *
     * @throws \LogicException The value of the environment is not specified, or is incorrect
     */
    private static function validateEnvironment(string $specifiedEnvironment): void
    {
        if (\in_array($specifiedEnvironment, self::LIST, true) === false)
        {
            throw new \LogicException(
                \sprintf(
                    'Provided incorrect value of the environment: "%s". Allowable values: %s',
                    $specifiedEnvironment,
                    \implode(', ', \array_values(self::LIST))
                )
            );
        }
    }

    /**
     * @psalm-param non-empty-string $environment
     */
    private function __construct(string $environment)
    {
        $this->environment = $environment;
    }
}
