<?php /** @noinspection PhpUndefinedClassInspection */

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <contacts@desperado.dev>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 0);

namespace ServiceBus\Services\Attributes;

use ServiceBus\Services\Attributes\Options\HasDescription;
use ServiceBus\Services\Attributes\Options\HasValidation;
use ServiceBus\Services\Attributes\Options\WithValidation;

/**
 * @psalm-immutable
 */
#[\Attribute(\Attribute::TARGET_METHOD)]
final class EventListener implements HasDescription, HasValidation
{
    /**
     * Event validation configuration.
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
     * @psalm-param array<string, string> $validationGroups
     */
    public function __construct(
        ?string $description = null,
        bool $validationEnabled = false,
        array $validationGroups = [],
    ) {
        $this->validation   = $validationEnabled ? new WithValidation($validationGroups) : null;
        $this->description = $description;
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
