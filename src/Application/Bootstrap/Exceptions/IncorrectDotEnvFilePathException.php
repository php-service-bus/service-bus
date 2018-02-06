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
 * Incorrect path to ".env" configuration file
 */
class IncorrectDotEnvFilePathException extends \LogicException implements BootstrapExceptionInterface
{
    /**
     * @param string $specifiedFilePath
     */
    public function __construct(string $specifiedFilePath)
    {
        parent::__construct(
            \sprintf(
                'An incorrect path to the ".env" configuration file was specified ("%s")', $specifiedFilePath
            )
        );
    }
}
