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

namespace Desperado\Framework\Tests\TestFixtures\Commands;

use Desperado\Framework\Domain\Messages\CommandInterface;

/**
 *
 */
class SomeCommand implements CommandInterface
{
    /**
     * ID
     *
     * @var string
     */
    public $id;

    /**
     * Payload
     *
     * @var string
     */
    public $payload;
}
