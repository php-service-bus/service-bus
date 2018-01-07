<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Application\Exceptions;

/**
 * Incorrect path to the root directory
 */
class IncorrectRootDirectoryPathException extends \LogicException implements BootstrapExceptionInterface
{
    /**
     * @param string $specifiedDirectoryPath
     */
    public function __construct(string $specifiedDirectoryPath)
    {
        parent::__construct(
            \sprintf(
                'The path to the root of the application is not correct ("%s")', $specifiedDirectoryPath
            )
        );
    }
}
