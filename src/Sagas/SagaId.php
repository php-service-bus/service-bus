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

namespace Desperado\ServiceBus\Sagas;

use function Desperado\ServiceBus\Common\uuid;
use Desperado\ServiceBus\Sagas\Exceptions\InvalidSagaIdentifier;

/**
 * Base saga id class
 */
abstract class SagaId
{
    /**
     * Identifier
     *
     * @var string
     */
    private $id;

    /**
     * Saga class
     *
     * @var string
     */
    private $sagaClass;

    /**
     * @param string $sagaClass
     *
     * @return self
     */
    public static function new(string $sagaClass): self
    {
        return new static(uuid(), $sagaClass);
    }

    /**
     * @param string $id
     * @param string $sagaClass
     *
     * @throws \Desperado\ServiceBus\Sagas\Exceptions\InvalidSagaIdentifier
     * @throws \Desperado\ServiceBus\Sagas\Exceptions\InvalidSagaIdentifier
     */
    final public function __construct(string $id, string $sagaClass)
    {
        if('' === $id)
        {
            throw new InvalidSagaIdentifier('The saga identifier can\'t be empty');
        }

        if('' === $sagaClass || false === \class_exists($sagaClass))
        {
            throw new InvalidSagaIdentifier(
                \sprintf('Invalid saga class specified ("%s")', $sagaClass)
            );
        }

        $this->id        = $id;
        $this->sagaClass = $sagaClass;
    }

    /**
     * @return string
     */
    final public function __toString(): string
    {
        return $this->id;
    }

    /**
     * @param SagaId $id
     *
     * @return bool
     */
    public function equals(SagaId $id): bool
    {
        return $this->id === (string) $id;
    }

    /**
     * Get saga class
     *
     * @return string
     */
    final public function sagaClass(): string
    {
        return $this->sagaClass;
    }
}
