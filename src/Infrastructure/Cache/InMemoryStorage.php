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

namespace Desperado\ServiceBus\Infrastructure\Cache;

/**
 * In memory data storage
 */
final class InMemoryStorage
{
    /**
     * @var self
     */
    private static $instance;

    /**
     * @var array<string, mixed>
     */
    private $storage = [];

    /**
     * @var array<string, int>
     */
    private $expires = [];

    /**
     * @return self
     */
    public static function instance(): self
    {
        if(null === self::$instance)
        {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Reset instance
     *
     * @return void
     */
    public function reset(): void
    {
        self::$instance = null;
    }

    /**
     * Remove stored entries
     *
     * @return void
     */
    public function clear(): void
    {
        $this->storage = [];
        $this->expires = [];
    }

    /**
     * Has stored entry
     *
     * @param string $key
     *
     * @return bool
     */
    public function has(string $key): bool
    {
        return isset($this->expires[$key]);
    }

    /**
     * Receive stored value
     *
     * @param string $key
     *
     * @return mixed
     */
    public function get(string $key)
    {
        if(true === isset($this->expires[$key]))
        {
            $expired = -1 === $this->expires[$key] ? false : \time() > $this->expires[$key];

            if(true === $expired)
            {
                $this->remove($key);

                return null;
            }

            return $this->storage[$key];
        }

        return null;
    }

    /**
     * Remove stored value
     *
     * @param string $key
     *
     * @return void
     */
    public function remove(string $key): void
    {
        unset($this->storage[$key], $this->expires[$key]);
    }

    /**
     * Store specified value
     *
     * @param string $key
     * @param mixed  $value
     * @param int    $ttl
     *
     * @return void
     */
    public function push(string $key, $value, int $ttl = 0): void
    {
        $this->storage[$key] = $value;
        $this->expires[$key] = 0 < $ttl ? \time() + $ttl : -1;
    }

    private function __construct()
    {

    }

    /**
     * @codeCoverageIgnore
     */
    private function __clone()
    {

    }
}
