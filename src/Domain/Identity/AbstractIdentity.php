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

namespace Desperado\Framework\Domain\Identity;

/**
 * Base identity class
 */
abstract class AbstractIdentity implements IdentityInterface
{
    /**
     * Identity
     *
     * @var string|integer
     */
    private $id;

    /**
     * @param int|string $id
     */
    final public function __construct($id)
    {
        $this->id = $id;
    }

    /**
     * @inheritdoc
     *
     * @return string
     */
    final public function toString(): string
    {
        return (string) $this->id;
    }

    /**
     * @inheritdoc
     */
    final public function toCompositeIndex(): string
    {
        return \sprintf('%s:%s', \get_class($this), $this->toString());
    }

    /**
     * @inheritdoc
     */
    final public function __toString(): string
    {
        return $this->toString();
    }
}
