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

namespace Desperado\Framework\Tests\TestFixtures\Events;

use Desperado\Framework\Domain\Messages\EventInterface;

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
