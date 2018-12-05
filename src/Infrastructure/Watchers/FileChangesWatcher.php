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

namespace Desperado\ServiceBus\Infrastructure\Watchers;

use function Amp\ByteStream\buffer;
use function Amp\call;
use Amp\Process\Process;
use Amp\Promise;

/**
 * File change monitoring
 */
final class FileChangesWatcher
{
    /**
     * Path to the directory
     *
     * @var string
     */
    private $directory;

    /**
     * Previous hash of files in the directory
     *
     * @var string|null
     */
    private $previousHash;

    /**
     * @param string $directory
     */
    public function __construct(string $directory)
    {
        $this->directory = $directory;
    }

    /**
     * Compare hashes
     * If returned false, the files have not been changed. Otherwise, return true
     *
     * @psalm-suppress MixedTypeCoercion Incorrect resolving the value of the promise
     *
     * @return Promise<bool>
     */
    public function compare(): Promise
    {
        /** @psalm-suppress InvalidArgument Incorrect psalm unpack parameters (...$args) */
        return call(
            function(): \Generator
            {
                /** @var string $bufferContent */
                $bufferContent = yield from self::execute($this->directory);

                $hash = self::extractHash($bufferContent);

                /** A runtime error has occurred */
                if(null === $hash)
                {
                    return false;
                }

                /** New hash is different from previously received */
                if(null !== $this->previousHash && $this->previousHash !== $hash)
                {
                    return true;
                }

                $this->previousHash = $hash;

                return false;
            }
        );
    }

    /**
     * Execute calculate hashes
     *
     * @psalm-suppress InvalidReturnType Incorrect resolving the value of the generator
     *
     * @param string $directory
     *
     * @return \Generator<string>
     */
    private static function execute(string $directory): \Generator
    {
        try
        {
            $process = new Process(
                \sprintf(
                    'find %s -name \'*.php\' \( -exec sha1sum "$PWD"/{} \; -o -print \) | sha1sum',
                    $directory
                )
            );

            yield $process->start();

            /** @var string $bufferContent */
            $bufferContent = yield buffer($process->getStdout());

            return $bufferContent;
        }
        catch(\Throwable $throwable)
        {
            return '';
        }
    }

    /**
     * Get a hash from the stdOut
     *
     * @param string $response
     *
     * @return string|null
     */
    private static function extractHash(string $response): ?string
    {
        $parts = \array_map('trim', \explode(' ', $response));

        return $parts[0] ?? null;
    }
}
