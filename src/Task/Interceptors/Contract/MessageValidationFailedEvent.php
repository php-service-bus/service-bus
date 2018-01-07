<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Task\Interceptors\Contract;

use Desperado\Domain\Message\AbstractEvent;

/**
 * Message validation failed
 */
class MessageValidationFailedEvent extends AbstractEvent
{
    /**
     * Message namespace
     *
     * @var string
     */
    protected $messageNamespace;

    /**
     * Violations
     *
     * [
     *    'propertyKey' => [
     *        0 => [
     *            'reasonMessage'
     *        ],
     *        ....
     *    ],
     *    ...
     * ]
     *
     * @var array
     */
    protected $violations = [];

    /**
     * Get message namespace
     *
     * @return string
     */
    public function getMessageNamespace(): string
    {
        return $this->messageNamespace;
    }

    /**
     * Get violations collection
     *
     * @return array
     */
    public function getViolations(): array
    {
        return $this->violations;
    }
}
