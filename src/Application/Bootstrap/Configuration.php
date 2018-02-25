<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Application\Bootstrap;

use Desperado\Domain\Environment\Environment;
use Desperado\ServiceBus\Application\Bootstrap\Exceptions\ServiceBusConfigurationException;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Dotenv\Dotenv;

/**
 * Application configuration
 */
class Configuration
{
    protected const DOTENV_ENV_KEY = 'APP_ENVIRONMENT';
    protected const DOTENV_ENTRY_POINT_NAME_KEY = 'APP_ENTRY_POINT_NAME';

    /**
     * Application environment key
     *
     * @Assert\NotBlank(
     *     message="Application environment must be specified"
     * )
     * @Assert\Choice(
     *     choices={"prod", "dev", "test"},
     *     message="This environment is incorrect. Acceptable variations: prod, dev, test"
     * )
     *
     * @var string
     */
    private $environment;

    /**
     * Application entry point name
     *
     * @Assert\NotBlank(
     *     message="Entry point name must be specified"
     * )
     * @Assert\Length(
     *      min = 3,
     *      max = 15,
     *      minMessage = "Entry point name must be at least {{ limit }} characters long",
     *      maxMessage = "Entry point name cannot be longer than {{ limit }} characters"
     * )
     *
     * @var string
     */
    private $entryPointName;

    /**
     * Load configuration from file ".env"
     *
     * @param string $environmentFilePath
     *
     * @return self
     *
     * @throws ServiceBusConfigurationException
     */
    public static function loadDotEnv(string $environmentFilePath): self
    {
        try
        {
            (new Dotenv())->load($environmentFilePath);
        }
        catch(\Throwable $throwable)
        {
            throw new ServiceBusConfigurationException(
                \sprintf('Can\'t initialize DotEnv component with error "%s"', $throwable->getMessage()),
                $throwable->getCode(),
                $throwable
            );
        }

        $self = new self();

        $self->environment = (string) \getenv(self::DOTENV_ENV_KEY);
        $self->entryPointName = (string) \getenv(self::DOTENV_ENTRY_POINT_NAME_KEY);

        return $self;
    }

    /**
     * Get the configuration as an array
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'service_bus.entry_point_name' => $this->entryPointName,
            'service_bus.environment'      => $this->environment
        ];
    }

    /**
     * Get environment key
     *
     * @return Environment
     */
    public function getEnvironment(): Environment
    {
        return new Environment($this->environment);
    }

    /**
     * Get entry point name
     *
     * @return string
     */
    public function getEntryPointName(): string
    {
        return $this->entryPointName;
    }

    /**
     * Close constructor
     */
    private function __construct()
    {

    }
}
