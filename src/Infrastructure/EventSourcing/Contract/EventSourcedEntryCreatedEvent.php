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

namespace Desperado\ConcurrencyFramework\Infrastructure\EventSourcing\Contract;

use Desperado\ConcurrencyFramework\Domain\Messages\EventInterface;

/**
 * Event sourced entry created event
 */
class EventSourcedEntryCreatedEvent implements EventInterface
{
    /**
     * Identity
     *
     * @var string
     */
    public $id;

    /**
     * Identity type
     *
     * @var string
     */
    public $type;

    /**
     * Created datetime
     *
     * @var string
     */
    public $createdAt;
}
