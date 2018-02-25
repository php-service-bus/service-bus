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

use Symfony\Component\Filesystem\Filesystem;

/**
 * Cache directory instance
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
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(string $cacheDirectoryPath)
    {
        $cacheDirectoryPath = \rtrim($cacheDirectoryPath, '/');

        if('' === $cacheDirectoryPath)
        {
            throw new \InvalidArgumentException($cacheDirectoryPath);
        }

        $this->filesystem = new Filesystem();
        $this->cacheDirectoryPath = $cacheDirectoryPath;
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
     * @throws \Symfony\Component\Filesystem\Exception\IOException
     */
    public function prepare(): void
    {
        if(false === $this->filesystem->exists($this->cacheDirectoryPath))
        {
            $this->filesystem->mkdir($this->cacheDirectoryPath);
        }

        $this->filesystem->chmod($this->cacheDirectoryPath, 0775, \umask());
    }
}
