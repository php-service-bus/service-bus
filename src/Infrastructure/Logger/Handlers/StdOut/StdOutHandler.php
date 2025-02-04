<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <contacts@desperado.dev>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types=0);

namespace ServiceBus\Infrastructure\Logger\Handlers\StdOut;

use Amp\ByteStream\ResourceOutputStream;
use Monolog\Formatter\FormatterInterface;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Level;
use Monolog\Logger;
use Monolog\LogRecord;

/**
 * Console output handler.
 *
 * @codeCoverageIgnore
 */
final class StdOutHandler extends AbstractProcessingHandler
{
    /**
     * @var ResourceOutputStream
     */
    private $streamWriter;

    public function __construct(Level $level = Level::Debug, bool $bubble = true, ?FormatterInterface $formatter = null)
    {
        parent::__construct($level, $bubble);

        $this->formatter = $formatter ?? new LineFormatter();

        $this->streamWriter = new ResourceOutputStream(
            stream: \STDOUT,
            chunkSize: 50000
        );
    }

    protected function write(LogRecord $record): void
    {
        try {
            /** @phpstan-ignore cast.string */
            $this->streamWriter->write((string) $record->formatted);
        }
        // @codeCoverageIgnoreStart
        catch (\Throwable) {
            /** Not interest */
        }
        // @codeCoverageIgnoreEnd
    }
}
