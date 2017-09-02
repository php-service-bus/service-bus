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

namespace Desperado\Framework\Domain;

/**
 * Key/value collection
 */
class ParameterBag implements \IteratorAggregate, \Countable
{
    /**
     * List of parameters
     *
     * @var array
     */
    protected $parameters;

    /**
     * @param array $parameters
     */
    public function __construct(array $parameters = [])
    {
        $this->parameters = $parameters;
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        yield from $this->parameters;
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        return \count($this->parameters);
    }

    /**
     * Get all key/value pairs
     *
     * @return array
     */
    public function all(): array
    {
        return $this->parameters;
    }

    /**
     * Returns a parameter as string by name
     *
     * @param string $key
     * @param string $default
     *
     * @return string
     */
    public function getAsString(string $key, string $default = ''): string
    {
        return (string) $this->get($key, $default);
    }

    /**
     * Returns a parameter as integer by name
     *
     * @param string $key
     * @param int    $default
     *
     * @return int
     */
    public function getAsInt(string $key, int $default = 0): int
    {
        return (int) $this->get($key, $default);
    }

    /**
     * Returns a parameter by name
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    public function get(string $key, $default)
    {
        return true === $this->has($key) ? $this->parameters[$key] : $default;
    }

    /**
     * Returns true if the parameter is defined
     *
     * @param string $key
     *
     * @return bool
     */
    public function has(string $key): bool
    {
        return \array_key_exists($key, $this->parameters);
    }

    /**
     * Removes a parameter
     *
     * @param string $key
     *
     * @return void
     */
    public function remove(string $key): void
    {
        unset($this->parameters[$key]);
    }

    /**
     * Set parameter value
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return $this
     */
    public function set(string $key, $value): self
    {
        $this->parameters[$key] = $value;

        return $this;
    }
}
