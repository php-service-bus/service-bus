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

namespace Desperado\ServiceBus\Transport\AmqpExt;

use Desperado\ServiceBus\Transport\Topic;

/**
 * Exchange details
 */
final class AmqpTopic implements Topic
{
    private const TYPE_FANOUT = \AMQP_EX_TYPE_FANOUT;
    private const TYPE_DIRECT = \AMQP_EX_TYPE_DIRECT;
    private const TYPE_TOPIC  = \AMQP_EX_TYPE_TOPIC;

    /** Plugin rabbitmq_delayed_message_exchange */
    private const TYPE_DELAYED = 'x-delayed-message';

    /**
     * The exchange name consists of a non-empty sequence of these characters: letters, digits, hyphen, underscore,
     * period, or colon
     *
     * @var string
     */
    private $name;

    /**
     * Exchange type
     *
     * - fanout
     * - direct
     * - topic
     * - x-delayed-message
     *
     * @var string
     */
    private $type;

    /**
     *  If set, the server will reply with Declare-Ok if the exchange already exists with the same name, and raise an
     *  error if not. The client can use this to check whether an exchange exists without modifying the server state.
     *  When set, all other method fields except name and no-wait are ignored. A declare with both passive and no-wait
     *  has no effect. Arguments are compared for semantic equivalence.
     *
     * If set, and the exchange does not already exist, the server MUST raise a channel exception with reply code 404
     * (not found). If not set and the exchange exists, the server MUST check that the existing exchange has the same
     * values for type, durable, and arguments fields. The server MUST respond with Declare-Ok if the requested
     * exchange matches these fields, and MUST raise a channel exception if not.
     *
     * @var bool
     */
    private $passive = false;

    /**
     * If set when creating a new exchange, the exchange will be marked as durable. Durable exchanges remain active
     * when a server restarts. Non-durable exchanges (transient exchanges) are purged if/when a server restarts.
     *
     * @var bool
     */
    private $durable = false;

    /**
     * @see https://www.rabbitmq.com/amqp-0-9-1-reference.html#domain.table
     *
     * @var array
     */
    private $arguments = [];

    /**
     * Exchange flags
     *
     * @var int
     */
    private $flags = 0;

    /**
     * @param string $name
     *
     * @return self
     */
    public static function fanout(string $name): self
    {
        return new self($name, self::TYPE_FANOUT);
    }

    /**
     * @param string $name
     *
     * @return self
     */
    public static function direct(string $name): self
    {
        return new self($name, self::TYPE_DIRECT);
    }

    /**
     * @param string $name
     *
     * @return self
     */
    public static function topic(string $name): self
    {
        return new self($name, self::TYPE_TOPIC);
    }

    /**
     * @param string $name
     *
     * @return self
     */
    public static function delayed(string $name): self
    {
        $self                              = new self($name, self::TYPE_DELAYED);
        $self->arguments['x-delayed-type'] = 'fanout';

        return $self;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->name;
    }

    /**
     * @return $this
     */
    public function passive(): self
    {
        $this->passive = true;
        $this->flags   += \AMQP_PASSIVE;

        return $this;
    }

    /**
     * @return $this
     */
    public function durable(): self
    {
        $this->durable = true;
        $this->flags   += \AMQP_DURABLE;

        return $this;
    }

    /**
     * @param array $arguments
     *
     * @return $this
     */
    public function wthArguments(array $arguments): self
    {
        $this->arguments = \array_merge($this->arguments, $arguments);

        return $this;
    }

    /**
     * @return string
     */
    public function type(): string
    {
        return $this->type;
    }

    /**
     * @return int
     */
    public function flags(): int
    {
        return $this->flags;
    }

    /**
     * @return array
     */
    public function arguments(): array
    {
        return $this->arguments;
    }

    /**
     * @param string $name
     * @param string $type
     */
    private function __construct(string $name, string $type)
    {
        $this->name = $name;
        $this->type = $type;
    }
}
