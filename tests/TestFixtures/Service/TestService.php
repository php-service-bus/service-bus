<?php

/**
 * CQRS/Event Sourcing Non-blocking concurrency framework
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @url     https://github.com/mmasiukevich
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ConcurrencyFramework\Tests\TestFixtures\Service;

use Desperado\ConcurrencyFramework\Infrastructure\CQRS\Context\LocalContext;
use Desperado\ConcurrencyFramework\Tests\TestFixtures\Commands\SomeCommand;
use Desperado\ConcurrencyFramework\Tests\TestFixtures\Events\SomeEvent;

/**
 *
 */
class TestService
{
    public static function somePublicStatic(): void
    {

    }

    /**
     *
     *
     * @param SomeEvent    $event
     * @param LocalContext $context
     *
     * @return void
     */
    public function whenSomeEvent(SomeEvent $event, LocalContext $context): void
    {
        self::somePublicStatic();
    }

    /**
     *
     *
     * @param SomeCommand  $command
     * @param LocalContext $context
     *
     * @return void
     */
    public function someCommand(SomeCommand $command, LocalContext $context): void
    {
        $this->someProtectedMethod();
    }

    /**
     * @return void
     */
    protected function someProtectedMethod(): void
    {
        $this->somePrivateMethod();
    }

    /**
     * @return void
     */
    private function somePrivateMethod(): void
    {

    }
}
