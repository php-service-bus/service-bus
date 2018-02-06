<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Application\Bootstrap\Exceptions;

/**
 * The path to the cache directory is not correct
 */
class IncorrectCacheDirectoryFilePathException extends \LogicException implements BootstrapExceptionInterface
{
    /**
     * @param string          $specifiedCacheDirectoryPath
     * @param \Throwable|null $throwable
     */
    public function __construct(string $specifiedCacheDirectoryPath, \Throwable $throwable = null)
    {
        parent::__construct(
            \sprintf(
                'The path to the cache directory is not correct ("%s"). The directory must exist and be writable',
                $specifiedCacheDirectoryPath
            ),
            0,
            $throwable
        );
    }
}
