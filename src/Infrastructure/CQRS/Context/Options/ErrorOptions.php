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

namespace Desperado\ConcurrencyFramework\Infrastructure\CQRS\Context\Options;

/**
 * Error handler execution options
 */
class ErrorOptions extends AbstractExecutionOptions
{
    /**
     * @inheritdoc
     */
    public function toArray(): array
    {
        return [
            'loggerChannel' => $this->getLoggerChannel()
        ];
    }
}
