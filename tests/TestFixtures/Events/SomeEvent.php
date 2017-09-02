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

namespace Desperado\ConcurrencyFramework\Tests\TestFixtures\Events;

use Desperado\ConcurrencyFramework\Domain\Messages\EventInterface;

/**
 *
 */
class SomeEvent implements EventInterface
{
    /**
     * ID
     *
     * @var string
     */
    public $someEventId;

    /**
     * Some value
     *
     * @var string
     */
    public $someEventValue;
}
