<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation)
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Tests\Application\Kernel\Stubs;

use ServiceBus\Common\Messages\Command;
use Symfony\Component\Validator\Constraints as Assert;

/**
 *
 */
final class WithValidationRulesCommand implements Command
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
