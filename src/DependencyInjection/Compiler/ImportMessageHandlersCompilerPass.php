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
    private $excludedFiles;

    /**
     * @param array $directories
     * @param array $excludedFiles
     */
    public function __construct(array $directories, array $excludedFiles)
    {
        $this->directories   = $directories;
        $this->excludedFiles = \array_merge($excludedFiles, [
            __FILE__,
            __DIR__ . '/../../../src/Storage/functions.php',
            __DIR__ . '/../../../src/DependencyInjection/Compiler/functions.php',
            __DIR__ . '/../../../src/Storage/SQL/queryBuilderFunctions.php',
            __DIR__ . '/../../../src/Common/commonFunctions.php',
            __DIR__ . '/../../../src/Common/datetimeFunctions.php',
            __DIR__ . '/../../../src/Common/reflectionFunctions.php',
        ]);
    }

    /**
     * @inheritdoc
     *
     * @throws \Exception
     */
    public function process(ContainerBuilder $container): void
    {
        $excludedFiles = canonicalizeFilesPath($this->excludedFiles);

        foreach(searchFiles($this->directories, '/\.php/i') as $file)
        {
            /** @var \SplFileInfo $file */

            $filePath = $file->getRealPath();

            if(true === \in_array($filePath, $excludedFiles, true))
            {
                continue;
            }

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
                if(false === $container->hasDefinition($class))
                {
                    $container
                        ->register($class, $class)
                        ->addTag('service_bus.service');
                }
            }
        }
    }
}
