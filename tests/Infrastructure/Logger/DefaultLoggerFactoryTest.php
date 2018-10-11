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

namespace Desperado\ServiceBus\Tests\Infrastructure\Logger;

use Desperado\ServiceBus\Environment;
use Desperado\ServiceBus\Infrastructure\Logger\DefaultLoggerFactory;
use PHPUnit\Framework\TestCase;

/**
 *
 */
final class DefaultLoggerFactoryTest extends TestCase
{
    /**
     * @test
     *
     * @return void
     */
    public function build(): void
    {
        $logger = DefaultLoggerFactory::build('qwerty', Environment::prod(), 'info');

        static::assertEquals('qwerty', $logger->getName());

        static::assertNotEmpty($logger->getHandlers());
        /** @noinspection PhpParamsInspection */
        static::assertCount(1, $logger->getHandlers());

        static::assertNotEmpty($logger->getProcessors());
        /** @noinspection PhpParamsInspection */
        static::assertCount(4, $logger->getProcessors());

    }
}
