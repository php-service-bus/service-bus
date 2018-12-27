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

namespace Desperado\ServiceBus\Infrastructure\Logger\Handlers\Graylog;

use Amp\ByteStream\ResourceOutputStream;
use Monolog\Formatter\NormalizerFormatter;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;

/**
 *
 */
final class GelfUdpHandler extends AbstractProcessingHandler
{
    /**
     * @var ResourceOutputStream
     */
    private $outputStream;

    /**
     * @var bool
     */
    private $gzipMessage;

    /**
     * @param string $host
     * @param int    $port
     * @param bool   $gzipMessage
     * @param int    $level
     * @param bool   $bubble
     *
     * @throws \RuntimeException Could not connect
     */
    public function __construct(string $host, int $port, bool $gzipMessage = false, $level = Logger::DEBUG, $bubble = true)
    {
        parent::__construct($level, $bubble);

        $this->outputStream = new ResourceOutputStream(self::createStream($host, $port));
        $this->gzipMessage  = $gzipMessage;
    }

    /**
     * @inheritdoc
     *
     * @return void
     *
     * @throws \Amp\ByteStream\ClosedException
     */
    protected function write(array $record): void
    {
        $body = \json_encode($record, \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE);

        if(true === $this->gzipMessage)
        {
            $body = \gzcompress($body);
        }

        $this->outputStream->write($body);
    }

    /**
     * @inheritdoc
     */
    public function getFormatter(): NormalizerFormatter
    {
        return new GelfFormatter();
    }

    /**
     * @param string $host
     * @param int    $port
     *
     * @return resource
     *
     * @throws \RuntimeException Could not connect
     */
    private static function createStream(string $host, int $port)
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
