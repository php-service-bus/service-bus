<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Tests\Application\Kernel\Stubs;

use Symfony\Component\Validator\Constraints as Assert;

/**
 *
 */
final class WithValidationCommand
{
    /**
     * @Assert\NotBlank()
     */
    private string $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }
}
