<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <contacts@desperado.dev>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 0);

namespace ServiceBus\Services\Configuration;

use ServiceBus\Common\MessageHandler\MessageHandlerOptions;

/**
 * Execution options.
 *
 * @psalm-immutable
 */
final class DefaultHandlerOptions implements MessageHandlerOptions
{
    /**
     * Is this an event listener?
     *
     * @psalm-readonly
     *
     * @var bool
     */
    public $isEventListener;

    /**
     * Is this a command handler?
     *
     * @psalm-readonly
     *
     * @var bool
     */
    public $isCommandHandler;

    /**
     * Validation enabled.
     *
     * @psalm-readonly
     *
     * @var bool
     */
    public $validationEnabled;

    /**
     * Validation groups.
     *
     * @psalm-readonly
     * @psalm-var array<array-key, string>
     *
     * @var array
     */
    public $validationGroups;

    /**
     * Execution timeout (in seconds).
     *
     * @psalm-readonly
     *
     * @var int|null
     */
    public $executionTimeout;

    /**
     * Message description.
     * Will be added to the log when the method is called.
     *
     * @psalm-readonly
     *
     * @var string|null
     */
    public $description;

    public static function createForEventListener(?string $withDescription = null): self
    {
        return new self(
            isEventListener: true,
            isCommandHandler: false,
            validationEnabled: false,
            validationGroups: [],
            executionTimeout: null,
            description: $withDescription,
        );
    }

    public static function createForCommandHandler(?string $withDescription = null): self
    {
        return new self(
            isEventListener: false,
            isCommandHandler: true,
            validationEnabled: false,
            validationGroups: [],
            executionTimeout: null,
            description: $withDescription,
        );
    }

    /**
     * @psalm-param array<array-key, string> $validationGroups
     */
    public function enableValidation(array $validationGroups = []): self
    {
        return new self(
            isEventListener: $this->isEventListener,
            isCommandHandler: $this->isCommandHandler,
            validationEnabled: true,
            validationGroups: $validationGroups,
            executionTimeout: $this->executionTimeout,
            description: $this->description,
        );
    }

    public function limitExecutionTime(int $executionTimeout): self {
        return new self(
            isEventListener: $this->isEventListener,
            isCommandHandler: $this->isCommandHandler,
            validationEnabled: $this->validationEnabled,
            validationGroups: $this->validationGroups,
            executionTimeout: $executionTimeout,
            description: $this->description,
        );
    }

    /**
     * @psalm-param  array<array-key, string> $validationGroups
     */
    private function __construct(
        bool $isEventListener,
        bool $isCommandHandler,
        bool $validationEnabled = false,
        array $validationGroups = [],
        int|null $executionTimeout = null,
        ?string $description = null
    )
    {
        $this->isEventListener   = $isEventListener;
        $this->isCommandHandler  = $isCommandHandler;
        $this->validationEnabled = $validationEnabled;
        $this->validationGroups  = $validationGroups;
        $this->executionTimeout  = $executionTimeout;
        $this->description       = $description;
    }
}
