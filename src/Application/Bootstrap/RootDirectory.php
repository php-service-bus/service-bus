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

use Desperado\ServiceBus\Application\Bootstrap\Exceptions\IncorrectRootDirectoryPathException;

/**
 * Root directory
 */
final class RootDirectory
{
    /**
     * Path to directory
     *
     * @var string
     */
    private $path;

    /**
     * @param string $path
     */
    public function __construct(string $path)
    {
        $this->path = \rtrim($path, '/');
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->path;
    }

    /**
     * Validate specified path
     *
     * @return void
     *
     * @throws IncorrectRootDirectoryPathException
     */
    public function validate(): void
    {
        if('' === $this->path || false === \is_dir($this->path) || false === \is_readable($this->path))
        {
            throw new IncorrectRootDirectoryPathException($this->path);
        }
    }
}
