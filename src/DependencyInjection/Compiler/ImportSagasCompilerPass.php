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

use Desperado\ServiceBus\Sagas\Saga;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 *  All sagas from the specified directories will be registered automatically
 */
final class ImportSagasCompilerPass implements CompilerPassInterface
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
     * @param array<mixed, string> $directories
     * @param array<mixed, string> $excludedFiles
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
        $foundSagas    = [];
        $excludedFiles = canonicalizeFilesPath($this->excludedFiles);

        foreach(searchFiles($this->directories, '/\.php/i') as $file)
        {
            /** @var \SplFileInfo $file */

            $filePath = $file->getRealPath();

            if(true === \in_array($filePath, $excludedFiles, true))
            {
                continue;
            }

            $class = extractNamespaceFromFile((string) $file);

            if(null !== $class && true === \is_a($class, Saga::class, true))
            {
                $foundSagas[] = $class;
            }
        }

        self::updateParameters($container, $foundSagas);
    }

    /**
     * @param ContainerBuilder     $container
     * @param array<mixed, string> $foundSagas
     *
     * @return void
     */
    private static function updateParameters(ContainerBuilder $container, array $foundSagas): void
    {
        if(0 !== \count($foundSagas))
        {
            /** @var array<mixed, string> $registeredSagas */
            $registeredSagas = true === $container->hasParameter('service_bus.sagas_map')
                ? $container->getParameter('service_bus.sagas_map')
                : [];

            $container->setParameter(
                'service_bus.sagas_map',
                \array_merge($registeredSagas, $foundSagas)
            );
        }
    }
}
