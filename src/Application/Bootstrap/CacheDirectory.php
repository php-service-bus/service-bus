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

use Desperado\ServiceBus\Application\Bootstrap\Exceptions\IncorrectCacheDirectoryFilePathException;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Cache directory
 */
final class CacheDirectory
{
    /**
     * Basic utility to manipulate the file system
     *
     * @var Filesystem
     */
    private $filesystem;

    /**
     * Cache directory path
     *
     * @var string
     */
    private $cacheDirectoryPath;

    /**
     * @param string $cacheDirectoryPath
     */
    public function __construct(string $cacheDirectoryPath)
    {
        $this->filesystem = new Filesystem();
        $this->cacheDirectoryPath = \rtrim($cacheDirectoryPath, '/');
    }

    /**
     * Get directory path
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->cacheDirectoryPath;
    }

    /**
     * Prepare directory
     *
     * @return void
     *
     * @throws IncorrectCacheDirectoryFilePathException
     */
    public function prepare(): void
    {
        try
        {
            if('' === $this->cacheDirectoryPath)
            {
                throw new \InvalidArgumentException('Empty cache directory path');
            }

            if(false === $this->filesystem->exists($this->cacheDirectoryPath))
            {
                $this->filesystem->mkdir($this->cacheDirectoryPath);
            }

            $this->filesystem->chmod($this->cacheDirectoryPath, 0775, \umask());
        }
        catch(\Throwable $throwable)
        {
            throw new IncorrectCacheDirectoryFilePathException($this->cacheDirectoryPath, $throwable);
        }
    }
}
