<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Infrastructure\Logger\Handlers\StdOut;

use Amp\ByteStream\ResourceOutputStream;
use Monolog\Formatter\FormatterInterface;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;

/**
 * Console output handler.
 *
 * @codeCoverageIgnore
 */
final class StdOutHandler extends AbstractProcessingHandler
{
    /** @var ResourceOutputStream */
    private $streamWriter;

    public function __construct(int $level = Logger::DEBUG, bool $bubble = true, ?FormatterInterface $formatter = null)
    {
        parent::__construct($level, $bubble);

        $this->formatter = $formatter ?? new StdOutFormatter();

        $this->streamWriter = new ResourceOutputStream(\STDOUT, 50000);
    }

    /**
     * {@inheritdoc}
     */
    protected function write(array $record): void
    {
        try
        {
            $this->streamWriter->write((string) $record['formatted']);
        }
        // @codeCoverageIgnoreStart
        catch (\Throwable $throwable)
        {
            /** Not interest */
        }
        // @codeCoverageIgnoreEnd
    }
}
