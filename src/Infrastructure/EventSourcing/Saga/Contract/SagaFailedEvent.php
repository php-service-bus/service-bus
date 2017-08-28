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

namespace Desperado\ConcurrencyFramework\Infrastructure\EventSourcing\Saga\Contract;

use Desperado\ConcurrencyFramework\Domain\Messages\EventInterface;

/**
 * Saga is failed
 */
class SagaFailedEvent implements EventInterface
{
    /**
     * Closed at datetime
     *
     * @var string
     */
    public $closedAt;

    /**
     * Fail reason
     *
     * @var string
     */
    public $reason;
}
