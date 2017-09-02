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

namespace Desperado\Framework\Infrastructure\EventSourcing\Aggregate\Contract;

use Desperado\Framework\Domain\Messages\EventInterface;

/**
 * Aggregate event stream stored event
 */
class AggregateEventStreamStored implements EventInterface
{
    /**
     * Aggregate identity
     *
     * @var string
     */
    public $id;

    /**
     * Aggregate identity namespace
     *
     * @var string
     */
    public $type;

    /**
     * Aggregate namespace
     *
     * @var string
     */
    public $aggregate;

    /**
     * Version
     *
     * @var int
     */
    public $version;
}
