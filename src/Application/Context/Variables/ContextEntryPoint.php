<?php

/**
 * Command Query Responsibility Segregation, Event Sourcing implementation
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @url     https://github.com/mmasiukevich
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ConcurrencyFramework\Application\Context\Variables;

use Desperado\ConcurrencyFramework\Domain\Environment\Environment;

/**
 * Entry point context DTO
 */
class ContextEntryPoint
{
    /**
     * Entry point name
     *
     * @var string
     */
    private $name;

    /**
     * Application environment
     *
     * @var Environment
     */
    private $environment;

    /**
     * @param string      $name
     * @param Environment $environment
     */
    public function __construct(string $name, Environment $environment)
    {
        $this->name = $name;
        $this->environment = $environment;
    }

    /**
     * Get entry point name
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get current environment
     *
     * @return Environment
     */
    public function getEnvironment(): Environment
    {
        return $this->environment;
    }
}
