<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation)
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Infrastructure\Logger\Handlers\Graylog;

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
     * @var string
     */
    private $host;

    /**
     * @var int
     */
    private $port;

    /**
     * @var ResourceOutputStream|null
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
     */
    public function __construct(string $host, int $port, bool $gzipMessage = false, $level = Logger::DEBUG, $bubble = true)
    {
        parent::__construct($level, $bubble);

        $this->host        = $host;
        $this->port        = $port;
        $this->gzipMessage = $gzipMessage;
    }

    /**
     * @inheritdoc
     *
     * @return void
     *
     * @throws \Amp\ByteStream\ClosedException
     * @throws \RuntimeException Could not connect
     */
    protected function write(array $record): void
    {
        $body = \json_encode($record);

        if(true === $this->gzipMessage)
        {
            $body = \gzcompress($body);
        }

        $this->outputStream()->write($body);
    }

    /**
     * @inheritdoc
     */
    public function getFormatter(): NormalizerFormatter
    {
        return new GelfFormatter();
    }

    /**
     * @return ResourceOutputStream
     *
     * @throws \RuntimeException Could not connect
     */
    private function outputStream(): ResourceOutputStream
    {
        if(null === $this->outputStream)
        {
            $this->outputStream = new ResourceOutputStream(self::createStream($this->host, $this->port), 65000);
        }

        return $this->outputStream;
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
            throw new \RuntimeException(\sprintf('Could not connect to %s', $uri));
        }

        return $stream;
    }
}
