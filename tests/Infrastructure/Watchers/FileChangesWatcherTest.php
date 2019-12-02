<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Tests\Infrastructure\Watchers;

use function Amp\Promise\wait;
use PHPUnit\Framework\TestCase;
use ServiceBus\Infrastructure\Watchers\FileChangesWatcher;

/**
 *
 */
final class FileChangesWatcherTest extends TestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        @\unlink(__DIR__ . '/testFile.php');

        parent::setUp();
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        @\unlink(__DIR__ . '/testFile.php');

        parent::tearDown();
    }

    /**
     * @test
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
