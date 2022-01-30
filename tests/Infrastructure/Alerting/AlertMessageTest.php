<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <contacts@desperado.dev>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace ServiceBus\Tests\Infrastructure\Alerting;

use PHPUnit\Framework\TestCase;
use ServiceBus\Infrastructure\Alerting\AlertMessage;

/**
 *
 */
final class AlertMessageTest extends TestCase
{
    /**
     * @test
     */
    public function create(): void
    {
        $message = new AlertMessage('Some {keyword} example', ['{keyword}' => 'root']);

        self::assertSame('Some root example', $message->content);
    }
}
