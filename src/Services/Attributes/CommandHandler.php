<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <contacts@desperado.dev>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types=0);

namespace ServiceBus\Services\Attributes;

use ServiceBus\Services\Attributes\Options\HasCancellation;
use ServiceBus\Services\Attributes\Options\HasDescription;
use ServiceBus\Services\Attributes\Options\HasValidation;
use ServiceBus\Services\Attributes\Options\WithCancellation;
use ServiceBus\Services\Attributes\Options\WithValidation;

/**
 * @psalm-immutable
 */
#[\Attribute(\Attribute::TARGET_METHOD)]
final class CommandHandler implements HasDescription, HasValidation, HasCancellation
{
    /**
     * Command validation configuration.
     *
     * @var WithValidation|null
     */
    private $validation;

    /**
     * Message description.
     * Will be added to the log when the method is called.
     *
     * @var string|null
     */
    private $description;

    /**
     * Execution cancellation configuration.
     *
     * @var WithCancellation
     */
    private $cancellation;

    /**
     * @psalm-param list<non-empty-string> $validationGroups
     * @psalm-param positive-int|int $executionTimeout
     */
    public function __construct(
        ?string $description = null,
        bool $validationEnabled = false,
        array $validationGroups = [],
        ?int $executionTimeout = null
    ) {
        $this->description  = $description;
        $this->validation   = $validationEnabled ? new WithValidation($validationGroups) : null;
        $this->cancellation = $executionTimeout !== null && $executionTimeout > 0
            ? new WithCancellation($executionTimeout)
            : WithCancellation::default();
    }

    public function cancellation(): WithCancellation
    {
        return $this->cancellation;
    }

    public function description(): ?string
    {
        return $this->description;
    }

    public function validation(): ?WithValidation
    {
        return $this->validation;
    }
}
