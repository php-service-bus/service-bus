<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\HttpServer\Context;

use Desperado\Domain\Message\AbstractMessage;
use Desperado\Domain\ParameterBag;
use Desperado\ServiceBus\Transport\Context\IncomingMessageContextInterface;
use Desperado\ServiceBus\Transport\Message\Message;
use Psr\Http\Message\RequestInterface;

/**
 * Http request
 */
class HttpIncomingContext implements IncomingMessageContextInterface
{
    /**
     * Message DTO
     *
     * @var Message
     */
    private $message;

    /**
     * Message object
     *
     * @var AbstractMessage
     */
    private $unpackedMessage;

    /**
     * @param RequestInterface $request
     * @param AbstractMessage  $message
     * @param string           $serializedMessage
     * @param string           $entryPointName
     *
     * @return HttpIncomingContext
     */
    public static function fromRequest(
        RequestInterface $request,
        AbstractMessage $message,
        string $serializedMessage,
        string $entryPointName
    ): self
    {
        $self = new self();

        $self->unpackedMessage = $message;
        $self->message = Message::create(
            $serializedMessage,
            new ParameterBag($request->getHeaders()),
            $entryPointName,
            $entryPointName
        );

        return $self;
    }

    /**
     * @inheritdoc
     */
    public function getReceivedMessage(): Message
    {
        return $this->message;
    }

    /**
     * Get message object
     *
     * @return AbstractMessage
     */
    public function getUnpackedMessage(): AbstractMessage
    {
        return $this->unpackedMessage;
    }

    /**
     * Close constructor
     */
    private function __construct()
    {

    }
}
