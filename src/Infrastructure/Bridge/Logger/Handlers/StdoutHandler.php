<?php

/**
 * Command Query Responsibility Segregation, Event Sourcing implementation
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @url     https://github.com/mmasiukevich
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\Framework\Infrastructure\Bridge\Logger\Handlers;

use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;

/**
 * Simple console echo handler
 */
class StdoutHandler extends AbstractProcessingHandler
{
    private const COLORS_BY_LEVEL = [
        Logger::DEBUG     => 34,
        Logger::INFO      => 32,
        Logger::NOTICE    => 31,
        Logger::WARNING   => 31,
        Logger::ERROR     => 35,
        Logger::CRITICAL  => 41,
        Logger::ALERT     => 35,
        Logger::EMERGENCY => 41
    ];

    /**
     * {@inheritdoc}
     */
    public function close(): void
    {
        parent::close();
    }

    /**
     * {@inheritdoc}
     */
    protected function write(array $record): void
    {
        $color = isset(self::COLORS_BY_LEVEL[$record['level']])
            ? self::COLORS_BY_LEVEL[$record['level']]
            : 31;

        if('cli' === \PHP_SAPI)
        {
            echo \sprintf(
                '%s[%dm%s%s[0m', \chr(27), $color, $record['formatted'], \chr(27)
            );
        }
    }
}
