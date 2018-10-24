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

namespace Desperado\ServiceBus\Tests\Infrastructure\Watchers;

use function Amp\Promise\wait;
use Desperado\ServiceBus\Infrastructure\Watchers\FileChangesWatcher;
use PHPUnit\Framework\TestCase;

/**
 *
 */
final class FileChangesWatcherTest extends TestCase
{
    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        @\unlink(__DIR__ . '/testFile.php');

        parent::setUp();
    }

    /**
     * @inheritdoc
     */
    protected function tearDown(): void
    {
        @\unlink(__DIR__ . '/testFile.php');

        parent::tearDown();
    }

    /**
     * @test
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function notChanged(): void
    {
        static::assertFalse(wait((new FileChangesWatcher(__DIR__))->compare()));
        static::assertFalse(wait((new FileChangesWatcher(__DIR__))->compare()));
        static::assertFalse(wait((new FileChangesWatcher(__DIR__))->compare()));
    }

    /**
     * @test
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function changed(): void
    {
        $watcher = new FileChangesWatcher(__DIR__);

        static::assertFalse(wait($watcher->compare()));

        \file_put_contents(__DIR__ . '/testFile.php', '');

        static::assertTrue(wait($watcher->compare()));
    }
}
