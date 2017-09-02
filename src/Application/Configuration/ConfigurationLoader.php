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

namespace Desperado\ConcurrencyFramework\Application\Configuration;

use Desperado\ConcurrencyFramework\Application\Configuration\Exceptions;
use Desperado\ConcurrencyFramework\Domain\ParameterBag;
use Desperado\ConcurrencyFramework\Infrastructure\Bridge\YamlParser\Exceptions\ParseYamlException;
use Desperado\ConcurrencyFramework\Infrastructure\Bridge\YamlParser\YamlParser;
use Symfony\Component\Dotenv\Dotenv;

/**
 * Configuration loader
 */
class ConfigurationLoader
{
    /**
     * DotEnv file path
     *
     * @var string
     */
    private $dotEnvFilePath;

    /**
     * Parameters file path
     *
     * @var string
     */
    private $configFilePath;

    /**
     * @param string $dotEnvFilePath
     * @param string $configFilePath
     *
     * @throws Exceptions\InvalidConfigurationFilePathException
     */
    public function __construct(string $dotEnvFilePath, string $configFilePath)
    {
        if(false === self::isFileReadable($configFilePath))
        {
            throw new Exceptions\InvalidConfigurationFilePathException(
                \sprintf(
                    'Configuration file not exists or not readable (specified path: "%s")',
                    $configFilePath
                )
            );
        }

        if(false === self::isFileReadable($dotEnvFilePath))
        {
            throw new Exceptions\InvalidConfigurationFilePathException(
                \sprintf(
                    'DotEnv file not exists or not readable (specified path: "%s")',
                    $dotEnvFilePath
                )
            );
        }

        $this->dotEnvFilePath = $dotEnvFilePath;
        $this->configFilePath = $configFilePath;
    }

    /**
     * Load configuration parameters
     *
     * @return ParameterBag
     *
     * @throws Exceptions\InvalidConfigurationFileContentException
     */
    public function loadParameters(): ParameterBag
    {
        try
        {
            $this->initDotEnv();

            $configFileContent = $this->prepareConfigurationFileVariables(
                $this->loadConfigurationFileContent()
            );

            return new ParameterBag(
                YamlParser::parse($configFileContent)
            );
        }
        catch(ParseYamlException $exception)
        {
            throw new Exceptions\InvalidConfigurationFileContentException(
                $exception->getMessage(),
                $exception->getCode(),
                $exception
            );
        }
    }

    /**
     * Prepare configuration variables (from Environment values)
     *
     * @param string $configFileContent
     *
     * @return string
     */
    private function prepareConfigurationFileVariables(string $configFileContent): string
    {
        return \str_replace(
            \array_map(
                function(string $eachKey)
                {
                    return \sprintf('{%s}', $eachKey);
                },
                \array_keys($_ENV)
            ),
            \array_values($_ENV),
            $configFileContent
        );
    }

    /**
     * Load configuration file content
     *
     * @return string
     */
    private function loadConfigurationFileContent(): string
    {
        return \file_get_contents($this->configFilePath);
    }

    /**
     * Init DotEnv component
     */
    private function initDotEnv(): void
    {
        (new Dotenv())->load($this->dotEnvFilePath);
    }

    /**
     * Is readable file
     *
     * @param string $filePath
     *
     * @return bool
     */
    private static function isFileReadable(string $filePath): bool
    {
        return
            '' !== $filePath &&
            true === \file_exists($filePath) &&
            true === \is_readable($filePath);
    }
}
