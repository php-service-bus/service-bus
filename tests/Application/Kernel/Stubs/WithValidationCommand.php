<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation)
 * Supports Saga pattern and Event Sourcing
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Tests\Application\Kernel\Stubs;

use Desperado\ServiceBus\Common\Contract\Messages\Command;
use Symfony\Component\Validator\Constraints as Assert;

/**
 *
 */
final class WithValidationCommand implements Command
{
    /**
     * @Assert\NotBlank()
     *
     * @var string
     */
    private $value;

    /**
     * @param string $value
     */
    public function __construct(string $value)
    {
        $this->value = $value;
    }
}
