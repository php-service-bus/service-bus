<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Tests\Stubs\Messages;

use ServiceBus\Services\Contracts\ValidationFailedEvent;

/**
 *
 */
final class ValidationFailed implements ValidationFailedEvent
{
    /** @var string */
    private $correlationId;

    /**
     * List of validate violations.
     *
     * [
     *    'propertyPath' => [
     *        0 => 'some message',
     *        ....
     *    ]
     * ]
     *
     * @psalm-var array<string, array<int, string>>
     *
     * @var array
     */
    private $violations;

    /**
     * {@inheritdoc}
     */
    public static function create(string $correlationId, array $violations): ValidationFailedEvent
    {
        $self = new self();

        $self->correlationId = $correlationId;
        $self->violations    = $violations;

        return $self;
    }

    /**
     * {@inheritdoc}
     */
    public function correlationId(): string
    {
        return $this->correlationId;
    }

    /**
     * {@inheritdoc}
     */
    public function violations(): array
    {
        return $this->violations;
    }
}
