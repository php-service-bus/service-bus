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

namespace Desperado\ServiceBus\MessageHandlers;

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
