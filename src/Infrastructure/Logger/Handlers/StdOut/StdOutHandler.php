<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation)
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Infrastructure\Logger\Handlers\StdOut;

use Amp\ByteStream\ResourceOutputStream;
use Amp\Log\ConsoleFormatter;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;

/**
 * Console output handler
 */
final class StdOutHandler extends AbstractProcessingHandler
{
    /**
     * @var ResourceOutputStream
     */
    private $streamWriter;

    /**
     * @param int  $level
     * @param bool $bubble
     */
    public function __construct(int $level = Logger::DEBUG, bool $bubble = true)
    {
        parent::__construct($level, $bubble);

        $this->streamWriter = new ResourceOutputStream(\STDOUT, 50000);
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
        $this->streamWriter->write((string) $record['formatted']);
    }

    /**
     * @inheritdoc
     */
    public function getFormatter(): LineFormatter
    {
        return new ConsoleFormatter();
    }
}
