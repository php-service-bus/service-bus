<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Infrastructure\Logger\Handlers\Graylog;

use Amp\ByteStream\ResourceOutputStream;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;
use function ServiceBus\Common\jsonEncode;

/**
 * Graylog UDP handler.
 *
 * @codeCoverageIgnore
 */
final class UdpHandler extends AbstractProcessingHandler
{
    private string                $host;
    private int                   $port;
    private ?ResourceOutputStream $outputStream = null;
    private bool                  $gzipMessage;

    /**
     * @param int|string $level
     */
    public function __construct(
        string $host = '0.0.0.0',
        int $port = 514,
        bool $gzipMessage = false,
        $level = Logger::DEBUG,
        bool $bubble = true
    ) {
        parent::__construct($level, $bubble);

        $this->host        = $host;
        $this->port        = $port;
        $this->gzipMessage = $gzipMessage;

        $this->formatter = new Formatter();
    }

    /**
     * {@inheritdoc}
     */
    protected function write(array $record): void
    {
        try
        {
            $body = jsonEncode($record);

            if (true === $this->gzipMessage)
            {
                $body = (string) \gzcompress($body);
            }

            $this->outputStream()->write($body);
        }
        // @codeCoverageIgnoreStart
        catch (\Throwable $throwable)
        {
            /** Not interest */
        }
        // @codeCoverageIgnoreEnd
    }

    /**
     * @throws \RuntimeException Could not connect
     */
    private function outputStream(): ResourceOutputStream
    {
        if (null === $this->outputStream)
        {
            $this->outputStream = new ResourceOutputStream(self::createStream($this->host, $this->port), 65000);
        }

        return $this->outputStream;
    }

    /**
     * @throws \RuntimeException Could not connect
     *
     * @return resource
     */
    private static function createStream(string $host, int $port)
    {
        $uri    = \sprintf('udp://%s:%d', $host, $port);
        $stream = @\stream_socket_client($uri, $errno, $errstr, 0, \STREAM_CLIENT_CONNECT);

        if (false === $stream)
        {
            throw new \RuntimeException(\sprintf('Could not connect to %s', $uri));
        }

        return $stream;
    }
}
