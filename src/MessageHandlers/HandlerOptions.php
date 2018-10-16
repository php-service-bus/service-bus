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

namespace Desperado\ServiceBus\MessageHandlers;

/**
 * Execution options
 */
final class HandlerOptions
{
    /**
     * Validation enabled
     *
     * @var bool
     */
    private $validationEnabled = false;

    /**
     * Validation groups
     *
     * @var array
     */
    private $validationGroups = [];

    /**
     * @param array $validationGroups
     *
     * @return void
     */
    public function enableValidation(array $validationGroups = []): void
    {
        $this->validationEnabled = true;
        $this->validationGroups  = $validationGroups;
    }

    /**
     * Is validation enabled
     *
     * @return bool
     */
    public function validationEnabled(): bool
    {
        return $this->validationEnabled;
    }

    /**
     * Receive validation groups
     *
     * @return array
     */
    public function validationGroups(): array
    {
        return $this->validationGroups;
    }
}
