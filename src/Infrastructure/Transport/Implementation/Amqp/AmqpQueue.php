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

namespace Desperado\ServiceBus\Infrastructure\Transport\Implementation\Amqp;

use Desperado\ServiceBus\Infrastructure\Transport\Queue;

/**
 * Queue details
 */
final class AmqpQueue implements Queue
{
    private const AMQP_DURABLE     = 2;
    private const AMQP_PASSIVE     = 4;
    private const AMQP_EXCLUSIVE   = 8;
    private const AMQP_AUTO_DELETE = 16;

    /**
     * The queue name MAY be empty, in which case the server MUST create a new queue with a unique generated name and
     * return this to the client in the Declare-Ok method. Queue names starting with "amq." are reserved for
     * pre-declared and standardised queues. The client MAY declare a queue starting with "amq." if the passive option
     * is set, or the queue already exists. Error code: access-refused The queue name can be empty, or a sequence of
     * these characters: letters, digits, hyphen, underscore, period, or colon.
     *
     * @var string
     */
    private $name;

    /**
     * If set, the server will reply with Declare-Ok if the queue already exists with the same name, and raise an
     * error if not. The client can use this to check whether a queue exists without modifying the server state. When
     * set, all other method fields except name and no-wait are ignored. A declare with both passive and no-wait has
     * no effect. Arguments are compared for semantic equivalence.
     *
     * The client MAY ask the server to assert that a queue exists without creating the queue if not. If the queue does
     * not exist, the server treats this as a failure
     *
     * If not set and the queue exists, the server MUST check that the existing queue has the same values for durable,
     * exclusive, auto-delete, and arguments fields. The server MUST respond with Declare-Ok if the requested queue
     * matches these fields, and MUST raise a channel exception if not
     *
     * @var bool
     */
    private $passive = false;

    /**
     * If set when creating a new queue, the queue will be marked as durable. Durable queues remain active when a
     * server restarts. Non-durable queues (transient queues) are purged if/when a server restarts. Note that durable
     * queues do not necessarily hold persistent messages, although it does not make sense to send persistent messages
     * to a transient queue.
     *
     * The server MUST recreate the durable queue after a restart.
     * The server MUST support both durable and transient queues.
     *
     * @var bool
     */
    private $durable = false;

    /**
     * Exclusive queues may only be accessed by the current connection, and are deleted when that connection closes.
     * Passive declaration of an exclusive queue by other connections are not allowed.
     *
     * The server MUST support both exclusive (private) and non-exclusive (shared) queues.
     * The client MAY NOT attempt to use a queue that was declared as exclusive by another still-open connection. Error
     * code
     *
     * @var bool
     */
    private $exclusive = false;

    /**
     * If set, the queue is deleted when all consumers have finished using it. The last consumer can be cancelled
     * either explicitly or because its channel is closed. If there was no consumer ever on the queue, it won't be
     * deleted. Applications can explicitly delete auto-delete queues using the Delete method as normal.
     *
     * The server MUST ignore the auto-delete field if the queue already exists.
     *
     * @var bool
     */
    private $autoDelete = false;

    /**
     * @see http://www.rabbitmq.com/amqp-0-9-1-reference.html#domain.table
     *
     * @var array
     */
    private $arguments = [];

    /**
     * Queue flags
     *
     * @var int
     */
    private $flags = 0;

    /**
     * @param string $name
     * @param bool   $durable
     */
    public function __construct(string $name, bool $durable = false)
    {
        $this->name = $name;

        if(true === $durable)
        {
            $this->makeDurable();
        }
    }

    /**
     * @param string $name
     * @param bool   $durable
     *
     * @return self
     */
    public static function default(string $name, bool $durable = false): self
    {
        return new self($name, $durable);
    }

    /**
     * Create delayed queue
     *
     * @see https://github.com/rabbitmq/rabbitmq-delayed-message-exchange
     *
     * @param string       $name
     * @param AmqpExchange $toExchange
     *
     * @return self
     */
    public static function delayed(string $name, AmqpExchange $toExchange): self
    {
        $self = new self($name, true);

        $self->arguments['x-dead-letter-exchange'] = (string) $toExchange;

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
    public function makePassive(): self
    {
        if(false === $this->isPassive())
        {
            $this->passive = true;
            $this->flags   += self::AMQP_PASSIVE;
        }

        return $this;
    }

    /**
     * @return bool
     */
    public function isPassive(): bool
    {
        return $this->passive;
    }

    /**
     * @return $this
     */
    public function makeExclusive(): self
    {
        if(false === $this->isExclusive())
        {
            $this->exclusive = true;
            $this->flags     += self::AMQP_EXCLUSIVE;
        }

        return $this;
    }

    /**
     * @return bool
     */
    public function isExclusive(): bool
    {
        return $this->exclusive;
    }

    /**
     * @return $this
     */
    public function makeDurable(): self
    {
        if(false === $this->isDurable())
        {
            $this->durable = true;
            $this->flags   += self::AMQP_DURABLE;
        }

        return $this;
    }

    /**
     * @return bool
     */
    public function isDurable(): bool
    {
        return $this->durable;
    }

    /**
     * @return $this
     */
    public function enableAutoDelete(): self
    {
        if(false === $this->autoDeleteEnabled())
        {
            $this->autoDelete = true;
            $this->flags      += self::AMQP_AUTO_DELETE;
        }

        return $this;
    }

    /**
     * @return bool
     */
    public function autoDeleteEnabled(): bool
    {
        return $this->autoDelete;
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
     * Receive queue flags
     *
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
}
