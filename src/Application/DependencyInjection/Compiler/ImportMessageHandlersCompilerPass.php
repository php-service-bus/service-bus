<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Application\DependencyInjection\Compiler;

use function ServiceBus\Common\canonicalizeFilesPath;
use function ServiceBus\Common\extractNamespaceFromFile;
use function ServiceBus\Common\searchFiles;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 *
 */
final class ImportMessageHandlersCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     *
     * @throws \LogicException
     * @throws \ServiceBus\Common\Exceptions\FileSystemException
     */
    public function process(ContainerBuilder $container): void
    {
        if (self::enabled($container) === true)
        {
            $excludedFiles = canonicalizeFilesPath(self::getExcludedFiles($container));

            $files = searchFiles(self::getDirectories($container), '/\.php/i');

            $this->registerClasses($container, $files, $excludedFiles);
        }
    }

    /**
     * @psalm-param \Generator<\SplFileInfo> $generator
     * @psalm-param array<int, string>       $excludedFiles
     *
     * @throws \ServiceBus\Common\Exceptions\FileSystemException
     */
    private function registerClasses(ContainerBuilder $container, \Generator $generator, array $excludedFiles): void
    {
        /** @var \SplFileInfo $file */
        foreach ($generator as $file)
        {
            /** @var string $filePath */
            $filePath = $file->getRealPath();

            if (\in_array($filePath, $excludedFiles, true) === false)
            {
                $class = extractNamespaceFromFile($filePath);

                if (
                    $class !== null &&
                    self::isMessageHandler($filePath) === true &&
                    $container->hasDefinition($class) === false
                ) {
                    $container->register($class, $class)->addTag('service_bus.service');
                }
            }
        }
    }

    private static function enabled(ContainerBuilder $container): bool
    {
        return $container->hasParameter('service_bus.auto_import.handlers_enabled') === true
            ? (bool) $container->getParameter('service_bus.auto_import.handlers_enabled')
            : false;
    }

    private static function isMessageHandler(string $filePath): bool
    {
        $fileContent = (string) \file_get_contents($filePath);

        return \strpos($fileContent, '@CommandHandler') !== false ||
            \strpos($fileContent, '@EventListener') !== false;
    }

    /**
     * @psalm-return array<int, string>
     */
    private static function getDirectories(ContainerBuilder $container): array
    {
        /**
         * @psalm-var    array<int, string> $directories
         *
         * @var string[] $directories
         */
        $directories = $container->hasParameter('service_bus.auto_import.handlers_directories') === true
            ? $container->getParameter('service_bus.auto_import.handlers_directories')
            : [];

        return $directories;
    }

    /**
     * @psalm-return array<int, string>
     */
    private static function getExcludedFiles(ContainerBuilder $container): array
    {
        /**
         * @psalm-var    array<int, string> $excludedFiles
         *
         * @var string[] $excludedFiles
         */
        $excludedFiles = $container->hasParameter('service_bus.auto_import.handlers_excluded') === true
            ? $container->getParameter('service_bus.auto_import.handlers_excluded')
            : [];

        return $excludedFiles;
    }
}
