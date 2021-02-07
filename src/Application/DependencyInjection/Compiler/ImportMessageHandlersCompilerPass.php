<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <contacts@desperado.dev>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 0);

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
    public function process(ContainerBuilder $container): void
    {
        if (self::enabled($container))
        {
            $excludedFiles = canonicalizeFilesPath(self::getExcludedFiles($container));

            $projectFilesIterator = searchFiles(self::getDirectories($container), '/\.php/i');

            /**
             * @psalm-suppress MixedArgumentTypeCoercion
             */
            $this->registerClasses(
                container: $container,
                projectFilesIterator: $projectFilesIterator,
                excludedFiles: $excludedFiles
            );
        }
    }

    /**
     * @psalm-param \Generator<\SplFileInfo> $projectFilesIterator
     * @psalm-param array<int, string>       $excludedFiles
     *
     * @throws \ServiceBus\Common\Exceptions\FileSystemException
     */
    private function registerClasses(
        ContainerBuilder $container,
        \Generator $projectFilesIterator,
        array $excludedFiles
    ): void {
        /** @var \SplFileInfo $file */
        foreach ($projectFilesIterator as $file)
        {
            /** @var string $filePath */
            $filePath = $file->getRealPath();

            if (\in_array($filePath, $excludedFiles, true) === false)
            {
                $class = extractNamespaceFromFile($filePath);

                if (
                    $class !== null &&
                    self::isMessageHandler($filePath) &&
                    $container->hasDefinition($class) === false
                ) {
                    $container->register($class, $class)->addTag('service_bus.service');
                }
            }
        }
    }

    private static function enabled(ContainerBuilder $container): bool
    {
        return $container->hasParameter('service_bus.auto_import.handlers_enabled')
            ? (bool) $container->getParameter('service_bus.auto_import.handlers_enabled')
            : false;
    }

    private static function isMessageHandler(string $filePath): bool
    {
        $fileContent = (string) \file_get_contents($filePath);

        return \str_contains($fileContent, '@CommandHandler') || \str_contains($fileContent, '@EventListener');
    }

    /**
     * @psalm-return array<int, string>
     */
    private static function getDirectories(ContainerBuilder $container): array
    {
        /**
         * @var string[]                  $directories
         * @psalm-var  array<int, string> $directories
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
         * @var string[]                 $excludedFiles
         * @psalm-var array<int, string> $excludedFiles
         */
        $excludedFiles = $container->hasParameter('service_bus.auto_import.handlers_excluded')
            ? $container->getParameter('service_bus.auto_import.handlers_excluded')
            : [];

        return $excludedFiles;
    }
}
