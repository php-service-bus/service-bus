<?php /** @noinspection PhpUnhandledExceptionInspection */

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <contacts@desperado.dev>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Tests\Endpoint;

use PHPUnit\Framework\TestCase;
use ServiceBus\Endpoint\EndpointRouter;

/**
 *
 */
final class EndpointRouterTest extends TestCase
{
    /**
     * @test
     */
    public function match(): void
    {
        $defaultEndpoint = new NullEndpoint();
        $anotherEndpoint = new NullEndpoint();

        $router = new EndpointRouter($defaultEndpoint);
        $router->registerRoutes([FirstEmptyMessage::class, SecondEmptyMessage::class], $defaultEndpoint);
        $router->registerRoutes([FirstEmptyMessage::class], $anotherEndpoint);

        self::assertCount(2, $router->route(FirstEmptyMessage::class));
        self::assertCount(1, $router->route(SecondEmptyMessage::class));
    }
}
