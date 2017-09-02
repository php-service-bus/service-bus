<?php

/**
 * Command Query Responsibility Segregation, Event Sourcing implementation
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @url     https://github.com/mmasiukevich
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\Framework\Domain\Pipeline;

use Desperado\Framework\Domain\Context\ContextInterface;
use Desperado\Framework\Domain\Messages\MessageInterface;

/**
 * Pipeline entry DTO
 */
class PipelineEntry
{
    /**
     * Message
     *
     * @var MessageInterface
     */
    private $message;

    /**
     * Context
     *
     * @var ContextInterface
     */
    private $context;

    /**
     * @param MessageInterface $message
     * @param ContextInterface $context
     */
    public function __construct(MessageInterface $message, ContextInterface $context)
    {
        $this->message = $message;
        $this->context = $context;
    }

    /**
     * Get message
     *
     * @return MessageInterface
     */
    public function getMessage(): MessageInterface
    {
        return $this->message;
    }

    /**
     * Get context
     *
     * @return ContextInterface
     */
    public function getContext(): ContextInterface
    {
        return $this->context;
    }
}
