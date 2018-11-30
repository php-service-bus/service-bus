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
     * @inheritdoc
     *
     * @throws \Exception
     */
    public function process(ContainerBuilder $container): void
    {
        if(true === self::enabled($container))
        {
            $excludedFiles = canonicalizeFilesPath(self::getExcludedFiles($container));

            $files = searchFiles(self::getDirectories($container), '/\.php/i');

            $this->registerClasses($container, $files, $excludedFiles);
        }
    }

    /**
     * @param ContainerBuilder         $container
     * @param \Generator<\SplFileInfo> $generator
     * @param array<int, string>       $excludedFiles
     *
     * @return void
     */
    private function registerClasses(ContainerBuilder $container, \Generator $generator, array $excludedFiles): void
    {
        /**
         * @var \SplFileInfo $file
         */
        foreach($generator as $file)
        {
            $filePath = $file->getRealPath();

            if(true === \in_array($filePath, $excludedFiles, true))
            {
                continue;
            }

            $class = extractNamespaceFromFile($filePath);

            if(
                null !== $class &&
                true === self::isMessageHandler($filePath) &&
                false === $container->hasDefinition($class)
            )
            {
                $container->register($class, $class)->addTag('service_bus.service');
            }
        }
    }

    /**
     * @param ContainerBuilder $container
     *
     * @return bool
     */
    private static function enabled(ContainerBuilder $container): bool
    {
        return true === $container->hasParameter('service_bus.auto_import.handlers_enabled')
            ? (bool) $container->getParameter('service_bus.auto_import.handlers_enabled')
            : false;
    }

    /**
     * @param string $filePath
     *
     * @return bool
     */
    private static function isMessageHandler(string $filePath): bool
    {
        $fileContent = \file_get_contents($filePath);

        return false !== \strpos($fileContent, '@CommandHandler') ||
            false !== \strpos($fileContent, '@EventListener');
    }

    /**
     * @param ContainerBuilder $container
     *
     * @return array<int, string>
     */
    private static function getDirectories(ContainerBuilder $container): array
    {
        /** @var array<int, string> $directories */
        $directories = true === $container->hasParameter('service_bus.auto_import.handlers_directories')
            ? $container->getParameter('service_bus.auto_import.handlers_directories')
            : [];

        return $directories;
    }

    /**
     * @param ContainerBuilder $container
     *
     * @return array<int, string>
     */
    private static function getExcludedFiles(ContainerBuilder $container): array
    {
        /** @var array<int, string> $excludedFiles */
        $excludedFiles = true === $container->hasParameter('service_bus.auto_import.handlers_excluded')
            ? $container->getParameter('service_bus.auto_import.handlers_excluded')
            : [];

        /** @var array<int, string> $directories */
        $directories = \array_merge($excludedFiles, [
            __FILE__,
            __DIR__ . '/../../../src/Storage/functions.php',
            __DIR__ . '/../../../src/DependencyInjection/Compiler/functions.php',
            __DIR__ . '/../../../src/Storage/SQL/queryBuilderFunctions.php',
            __DIR__ . '/../../../src/Common/commonFunctions.php',
            __DIR__ . '/../../../src/Common/datetimeFunctions.php',
            __DIR__ . '/../../../src/Common/reflectionFunctions.php',
        ]);

        return $directories;
    }
}
