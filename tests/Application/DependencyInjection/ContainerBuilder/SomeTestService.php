<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Tests\Application\DependencyInjection\ContainerBuilder;

/**
 *
 */
final class SomeTestService
{
    /** @var string|null  */
    private $env;

    public function __construct(?string $env = null)
    {
        $this->env = $env;
    }

    public function env(): string
    {
        return $this->env;
    }
}
