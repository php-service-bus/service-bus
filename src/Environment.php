<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation)
 * Supports Saga pattern and Event Sourcing
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus;

/**
 * Environment Value object
 */
final class Environment
{
    private const ENVIRONMENT_PRODUCTION = 'prod';
    private const ENVIRONMENT_SANDBOX    = 'dev';
    private const ENVIRONMENT_TESTING    = 'test';

    private const LIST                   = [
        self::ENVIRONMENT_PRODUCTION,
        self::ENVIRONMENT_SANDBOX,
        self::ENVIRONMENT_TESTING
    ];

    /**
     * Application environment
     *
     * @var string
     */
    private $environment;

    /**
     * @return self
     */
    public static function test(): self
    {
        $self              = new self();
        $self->environment = self::ENVIRONMENT_TESTING;

        return $self;
    }

    /**
     * @return self
     */
    public static function dev(): self
    {
        $self              = new self();
        $self->environment = self::ENVIRONMENT_SANDBOX;

        return $self;
    }

    /**
     * @return self
     */
    public static function prod(): self
    {
        $self              = new self();
        $self->environment = self::ENVIRONMENT_PRODUCTION;

        return $self;
    }

    /**
     * @param string $environment
     *
     * @return Environment
     *
     * @throws \LogicException
     */
    public static function create(string $environment): self
    {
        $environment = \strtolower($environment);

        self::validateEnvironment($environment);

        $self              = new self();
        $self->environment = $environment;

        return $self;
    }

    /**
     * Is this environment for debugging?
     *
     * @return bool
     */
    public function isDebug(): bool
    {
        return self::ENVIRONMENT_PRODUCTION !== $this->environment;
    }

    /**
     * Is this environment for testing?
     *
     * @return bool
     */
    public function isTesting(): bool
    {
        return self::ENVIRONMENT_TESTING === $this->environment;
    }

    /**
     * Is this environment for production usage?
     *
     * @return bool
     */
    public function isProduction(): bool
    {
        return self::ENVIRONMENT_PRODUCTION === $this->environment;
    }

    /**
     * Is this environment for development usage?
     *
     * @return bool
     */
    public function isDevelopment(): bool
    {
        return self::ENVIRONMENT_SANDBOX === $this->environment;
    }

    /**
     * Is environments equals
     *
     * @param Environment $environment
     *
     * @return bool
     */
    public function equals(Environment $environment): bool
    {
        return $this->environment === $environment->environment;
    }

    /**
     * Get a textual representation of the current environment
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->environment;
    }

    /**
     * Validate the specified environment
     *
     * @param string $specifiedEnvironment
     *
     * @return void
     *
     * @throws \LogicException
     */
    private static function validateEnvironment(string $specifiedEnvironment): void
    {
        if('' === $specifiedEnvironment ||
            false === \in_array($specifiedEnvironment, self::LIST, true)
        )
        {
            throw new \LogicException(
                \sprintf(
                    'Provided incorrect value of the environment: "%s". Allowable values: %s',
                    $specifiedEnvironment, \implode(', ', \array_values(self::LIST))
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