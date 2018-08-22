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

namespace Desperado\ServiceBus\Tests\Stubs\Transport;

/**
 *
 */
final class VirtualTransportBuffer
{
    /**
     * @var array
     */
    private $messages = [];

    /**
     * @var null|self
     */
    private static $instance;

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
     * @return void
     */
    public static function reset(): void
    {
        self::$instance = null;
    }

    /**
     * @param string $messagePayload
     * @param array  $headers
     *
     * @return void
     */
    public function add(string $messagePayload, array $headers = []): void
    {
        $this->messages[] = [$messagePayload, $headers];
    }

    /**
     * @return bool
     */
    public function has(): bool
    {
        return 0 !== \count($this->messages);
    }

    /**
     * @return array
     */
    public function extract(): array
    {
        return \array_shift($this->messages);
    }

    public function __wakeup()
    {

    }

    private function __construct()
    {

    }

    private function __clone()
    {

    }
}