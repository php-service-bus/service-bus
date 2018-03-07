<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\DependencyInjection\Traits;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection;

/**
 *
 */
trait LoadServicesTrait
{
    /**
     * Load all service descriptions from the specified directory
     *
     * @param string                               $resourceDirectory
     * @param DependencyInjection\ContainerBuilder $container
     *
     * @return void
     *
     * @throws \Exception
     */
    protected static function loadFromDirectory(string $resourceDirectory, DependencyInjection\ContainerBuilder $container): void
    {
        $regexIterator = new \RegexIterator(
            new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($resourceDirectory)
            )
            , '/\.xml/i'
        );

        $loader = new DependencyInjection\Loader\XmlFileLoader($container, new FileLocator());

        foreach($regexIterator as $xmlFile)
        {
            $loader->load((string) $xmlFile);
        }
    }
}
