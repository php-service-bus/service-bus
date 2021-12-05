<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <contacts@desperado.dev>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 0);

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
     * @psalm-param non-empty-string $host
     * @psalm-param Logger::DEBUG | Logger::INFO | Logger::NOTICE | Logger::WARNING | Logger::ERROR | Logger::CRITICAL | Logger::ALERT | Logger::EMERGENCY $level
     */
    public function __construct(
        string $host = '0.0.0.0',
        int $port = 514,
        bool $gzipMessage = false,
        int $level = Logger::DEBUG,
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

            if ($this->gzipMessage)
            {
                $body = (string) \gzcompress($body);
            }

            $this->outputStream()->write($body);
        }
        // @codeCoverageIgnoreStart
        catch (\Throwable)
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
        if ($this->outputStream === null)
        {
            $this->outputStream = new ResourceOutputStream(
                stream: self::createStream($this->host, $this->port),
                chunkSize: 65000
            );
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
        $stream = @\stream_socket_client(
            $uri,
            $errno,
            $errstr,
            0
        );

        if ($stream === false)
        {
            throw new \RuntimeException(\sprintf('Could not connect to %s', $uri));
        }

        return $stream;
    }
}
