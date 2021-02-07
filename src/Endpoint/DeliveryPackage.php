<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <contacts@desperado.dev>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 0);

namespace ServiceBus\Endpoint;

use ServiceBus\Common\Context\OutcomeMessageMetadata;
use ServiceBus\Common\Endpoint\DeliveryOptions;

/**
 * @psalm-immutable
 */
final class DeliveryPackage
{
    /**
     * @psalm-readonly
     *
     * @var object
     */
    public $message;

    /**
     * @psalm-readonly
     *
     * @var DeliveryOptions
     */
    public $options;

    /**
     * @psalm-readonly
     *
     * @var OutcomeMessageMetadata
     */
    public $metadata;

    public function __construct(object $message, DeliveryOptions $options, OutcomeMessageMetadata $metadata)
    {
        $this->message  = $message;
        $this->options  = $options;
        $this->metadata = $metadata;
    }
}
