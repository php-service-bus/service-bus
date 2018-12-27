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

namespace Desperado\ServiceBus\Infrastructure\Logger\Gelf;

use Amp\ByteStream\ResourceOutputStream;
use Gelf\IMessagePublisher;
use Gelf\Message;

/**
 * Asynchronous sending messages to Graylog
 */
final class StreamPublisher implements IMessagePublisher
{
    /**
     * @var ResourceOutputStream
     */
    private $outputStream;

    /**
     * @var bool
     */
    private $gzipContent;

    /**
     * @param string $host
     * @param int    $port
     * @param bool   $gzipContent
     */
    public function __construct(string $host, int $port, bool $gzipContent = false)
    {
        $this->outputStream = new ResourceOutputStream($this->createStream($host, $port));
        $this->gzipContent  = $gzipContent;
    }

    /**
     * @inheritDoc
     *
     * @return void
     *
     * @throws \Amp\ByteStream\ClosedException
     */
    public function publish(Message $message): void
    {
        $messageBody = $this->compressMessage($message);

        $this->outputStream->write($messageBody);
    }

    /**
     * @param Message $message
     *
     * @return string
     */
    private function compressMessage(Message $message): string
    {
        $content = \json_encode($message->toArray());

        if(true === $this->gzipContent)
        {
            $content = \gzcompress($content);
        }

        return $content;
    }

    /**
     * @param string $host
     * @param int    $port
     *
     * @return resource
     *
     * @throws \RuntimeException Could not connect
     */
    private function createStream(string $host, int $port)
    {
        $uri = \sprintf('udp://%s:%d', $host, $port);

        $stream = @\stream_socket_client($uri, $errno, $errstr, 0, \STREAM_CLIENT_CONNECT);

        if(false === $stream)
        {
            throw new \RuntimeException(\sprintf('Could not connect to %s:%d', $host, $port));
        }

        \stream_set_blocking($stream, false);
        \stream_set_write_buffer($stream, 0);


        return $stream;
    }
}
