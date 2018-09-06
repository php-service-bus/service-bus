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

namespace Desperado\ServiceBus\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 *
 */
final class ImportMessageHandlersCompilerPass implements CompilerPassInterface
{
    /**
     * @var array<mixed, string>
     */
    private $directories;

    /**
     * @var array<mixed, string>
     */
    private $excludedClasses;

    /**
     * @param array $directories
     * @param array $excludedClasses
     */
    public function __construct(array $directories, array $excludedClasses)
    {
        $this->directories     = $directories;
        $this->excludedClasses = $excludedClasses;
        /** ignore current file */
        $this->excludedClasses[] = __CLASS__;
    }

    /**
     * @inheritdoc
     *
     * @throws \Exception
     */
    public function process(ContainerBuilder $container): void
    {
        foreach(searchFiles($this->directories, '/\.php/i') as $file)
        {
            $filePath    = (string) $file;
            $fileContent = \file_get_contents($filePath);

            $class = extractNamespaceFromFile($filePath);

            if(null === $class)
            {
                continue;
            }

            if(
                false !== \strpos($fileContent, '@CommandHandler') ||
                false !== \strpos($fileContent, '@EventListener')
            )
            {
                if(
                    false === $container->hasDefinition($class) &&
                    false === \in_array($class, $this->excludedClasses, true)
                )
                {
                    $container
                        ->register($class, $class)
                        ->addTag('service_bus.service');
                }
            }
        }
    }
}
