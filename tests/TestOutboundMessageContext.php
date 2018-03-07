<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Tests;

use Desperado\Domain\Message\AbstractCommand;
use Desperado\Domain\Message\AbstractEvent;
use Desperado\Domain\Message\AbstractMessage;
use Desperado\Domain\MessageSerializer\MessageSerializerInterface;
use Desperado\ServiceBus\HttpServer\Context\HttpIncomingContext;
use Desperado\ServiceBus\HttpServer\HttpResponse;
use Desperado\ServiceBus\Transport\Context\IncomingMessageContextInterface;
use Desperado\ServiceBus\Transport\Context\OutboundMessageContextInterface;
use Desperado\ServiceBus\Transport\Message\MessageDeliveryOptions;
use Psr\Http\Message\RequestInterface;

/**
 *
 */
class TestOutboundMessageContext implements OutboundMessageContextInterface
{
    /**
     * @var AbstractMessage
     */
    private $storage = [];

    /**
     * @inheritdoc
     */
    public static function fromIncoming(
        IncomingMessageContextInterface $incomingMessageContext,
        MessageSerializerInterface $messageSerializer
    ): self
    {
        return new self();
    }

    public function __construct()
    {
        $this->storage = new \SplObjectStorage();
    }

    /**
     * @inheritdoc
     */
    public function responseBind(): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public static function fromHttpRequest(
        RequestInterface $request,
        HttpIncomingContext $incomingMessageContext,
        MessageSerializerInterface $messageSerializer
    ): self
    {
        return new self();
    }

    /**
     * @inheritdoc
     */
    public function bindResponse(HttpResponse $response): void
    {

    }

    /**
     * @inheritdoc
     */
    public function getResponseData(): ?HttpResponse
    {
        return new HttpResponse();
    }

    /**
     * @inheritdoc
     */
    public function httpSessionStarted(): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function publish(AbstractEvent $event, MessageDeliveryOptions $messageDeliveryOptions): void
    {
        $this->storage->attach($event);
    }

    /**
     * @inheritdoc
     */
    public function send(AbstractCommand $command, MessageDeliveryOptions $messageDeliveryOptions): void
    {
        $this->storage->attach($command);
    }

    /**
     * @inheritdoc
     */
    public function getToPublishMessages(): \SplObjectStorage
    {
        return $this->storage;
    }
}
