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
use Desperado\Domain\Transport\Context\IncomingMessageContextInterface;
use Desperado\Domain\Transport\Context\OutboundMessageContextInterface;
use Desperado\Domain\Transport\Message\MessageDeliveryOptions;
use Desperado\ServiceBus\HttpServer\Context\HttpIncomingContext;
use Desperado\ServiceBus\HttpServer\HttpResponse;
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

    }

    /**
     * @inheritdoc
     */
    public static function fromHttpRequest(
        RequestInterface $request,
        HttpIncomingContext $incomingMessageContext,
        MessageSerializerInterface $messageSerializer
    )
    {

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

    }

    /**
     * @inheritdoc
     */
    public function httpSessionStarted(): bool
    {

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
