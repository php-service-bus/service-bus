<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Application\EntryPoint;

use Desperado\Domain\Message\AbstractMessage;
use Desperado\Domain\ParameterBag;

/**
 * Entry point message context
 */
class EntryPointContext
{
    /**
     * Received message (unpacked)
     *
     * @var AbstractMessage
     */
    private $message;

    /**
     * Message body
     *
     * @var ParameterBag
     */
    private $headers;

    /**
     * @param AbstractMessage $message
     * @param ParameterBag    $headers
     *
     * @return self
     */
    public static function create(AbstractMessage $message, ParameterBag $headers): self
    {
        $self = new self();

        $self->message = $message;
        $self->headers = $headers;

        return $self;
    }

    /**
     * Get message
     *
     * @return AbstractMessage
     */
    public function getMessage(): AbstractMessage
    {
        return $this->message;
    }

    /**
     * Get message headers
     *
     * @return ParameterBag
     */
    public function getHeaders(): ParameterBag
    {
        return $this->headers;
    }

    /**
     * Close constructor
     */
    private function __construct()
    {

    }
}
