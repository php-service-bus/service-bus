<?php

/**
 * CQRS/Event Sourcing Non-blocking concurrency framework
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @url     https://github.com/mmasiukevich
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ConcurrencyFramework\Infrastructure\Annotation;

/**
 * Base annotations class
 */
abstract class AbstractAnnotation
{
    /**
     * @param array $data
     */
    public final function __construct(array $data)
    {
        foreach($data as $key => $value)
        {
            $this->$key = $value;
        }
    }

    /**
     * Get unknown property
     *
     * @param string $name
     *
     * @return void
     *
     * @throws \BadMethodCallException
     */
    public function __get(string $name): void
    {
        throw new \BadMethodCallException(
            sprintf('Unknown property "%s" on annotation "%s"', $name, \get_class($this))
        );
    }

    /**
     * Set unknown property
     *
     * @param string $name
     * @param mixed  $value
     *
     * @return void
     *
     * @throws \BadMethodCallException
     */
    public function __set(string $name, $value): void
    {
        throw new \BadMethodCallException(
            sprintf('Unknown property "%s" on annotation "%s"', $name, \get_class($this))
        );
    }
}
