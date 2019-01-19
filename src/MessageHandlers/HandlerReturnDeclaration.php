<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation)
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\MessageHandlers;

use Amp\Promise;

/**
 * Handler return declaration
 */
final class HandlerReturnDeclaration
{
    /**
     * @var \ReflectionType
     */
    private $reflectionType;

    /**
     * @param \ReflectionType $reflectionType
     */
    public function __construct(\ReflectionType $reflectionType)
    {
        $this->reflectionType = $reflectionType;
    }

    /**
     * @return bool
     */
    public function isVoid(): bool
    {
        return 'void' === $this->reflectionType->getName();
    }

    /**
     * @return bool
     */
    public function isGenerator(): bool
    {
        return \Generator::class === $this->reflectionType->getName();
    }

    /**
     * @return bool
     */
    public function isPromise(): bool
    {
        return Promise::class === $this->reflectionType->getName();
    }
}
