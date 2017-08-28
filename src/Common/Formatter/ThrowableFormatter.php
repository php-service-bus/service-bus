<?php

/**
 * CQRS/Event Sourcing Non-blocking concurrency framework
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @url     https://github.com/mmasiukevich
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ConcurrencyFramework\Common\Formatter;

/**
 * Format Throwable to string representation
 */
class ThrowableFormatter
{
    /**
     * Format Throwable to string representation
     *
     * @param \Throwable $throwable
     *
     * @return string
     */
    public static function toString(\Throwable $throwable): string
    {
        $previousText = '';

        if($previous = $throwable->getPrevious())
        {
            do
            {
                $previousText .= \sprintf(
                    ', %s(code: %s %s at %s:%s',
                    \get_class($previous),
                    $previous->getCode(), $previous->getMessage(),
                    $previous->getFile(), $previous->getLine()
                );
            }
            while($previous = $previous->getPrevious());
        }

        $result = \sprintf(
            '[object] (%s(code: %s): %s at %s:%s%s)',
            \get_class($throwable),
            $throwable->getCode(), $throwable->getMessage(),
            $throwable->getFile(), $throwable->getLine(),
            $previousText
        );

        return $result;
    }

    /**
     * Close constructor
     *
     * @codeCoverageIgnore
     */
    private function __construct()
    {

    }
}
