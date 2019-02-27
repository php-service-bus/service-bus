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
use Monolog\Formatter\NormalizerFormatter;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;

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
     * @param string $host
     * @param int    $port
     * @param bool   $gzipMessage
     * @param int    $level
     * @param bool   $bubble
     */
    public function __construct(
        string $host = '0.0.0.0',
        int $port = 514,
        bool $gzipMessage = false,
        $level = Logger::DEBUG,
        $bubble = true
    ) {
        parent::__construct($level, $bubble);

        $this->host        = $host;
        $this->port        = $port;
        $this->gzipMessage = $gzipMessage;
    }

    /**
     * {@inheritdoc}
     *
     * @return void
     */
    protected function write(array $record): void
    {
        try
        {
            $body = \json_encode($record);

            // @codeCoverageIgnoreStart
            if (false === \is_string($body))
            {
                return;
            }
            // @codeCoverageIgnoreEnd

            if (true === $this->gzipMessage)
            {
                /** @noinspection UnnecessaryCastingInspection */
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
     * {@inheritdoc}
     */
    public function getFormatter(): NormalizerFormatter
    {
        return new Formatter();
    }

    /**
     * @throws \RuntimeException Could not connect
     *
     * @return ResourceOutputStream
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
     * @param string $host
     * @param int    $port
     *
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
