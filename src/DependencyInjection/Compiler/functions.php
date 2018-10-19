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

/**
 * Extract namespace from file
 *
 * @param string $filePath
 *
 * @return string|null
 */
function extractNamespaceFromFile(string $filePath): ?string
{
    $matches = [];

    if(
        false !== \preg_match('#^namespace\s+(.+?);$#sm', \file_get_contents($filePath), $matches) &&
        true === isset($matches[1])
    )
    {
        return \sprintf('%s\\%s',
            $matches[1],
            \pathinfo($filePath)['filename']
        );
    }

    return null;
}

/**
 * Search for files matching the specified regular expression
 *
 * @param array<mixed, string>  $directories
 * @param string $regExp
 *
 * @return \Generator<mixed, \SplFileInfo>
 */
function searchFiles(array $directories, string $regExp): \Generator
{
    foreach($directories as $directory)
    {
        $regexIterator = new \RegexIterator(
            new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($directory)
            ),
            $regExp
        );

        /** @var \SplFileInfo $file */
        foreach($regexIterator as $file)
        {
            yield $file;
        }
    }
}

/**
 * @param array<mixed, string> $paths
 *
 * @return array<int, string>
 */
function canonicalizeFilesPath(array $paths): array
{
    $result = [];

    foreach($paths as $path)
    {
        $result[] = (new \SplFileInfo($path))->getRealPath();
    }

    return $result;
}
