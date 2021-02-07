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

/**
 * @psalm-immutable
 */
#[\Attribute(\Attribute::TARGET_METHOD)]
final class EventListener
{
    /**
     * Event validation configuration.
     *
     * @psalm-readonly
     *
     * @var WithValidation|null
     */
    public $validation;

    /**
     * Message description.
     * Will be added to the log when the method is called.
     *
     * @psalm-readonly
     *
     * @var string|null
     */
    public $description;

    public function __construct(?WithValidation $validation = null, ?string $description = null)
    {
        $this->validation  = $validation;
        $this->description = $description;
    }
}
