<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <contacts@desperado.dev>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace ServiceBus\Tests\Context;

use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use ServiceBus\Context\KernelContext;
use ServiceBus\Context\KernelContextFactory;
use ServiceBus\Endpoint\EndpointRouter;
use ServiceBus\Endpoint\MessageDeliveryEndpoint;
use ServiceBus\Endpoint\Options\DefaultDeliveryOptionsFactory;
use ServiceBus\EntryPoint\ReceivedMessageMetadata;
use function ServiceBus\Common\uuid;

/**
 *
 */
final class KernelContextFactoryTest extends TestCase
{
    /**
     * @test
     */
    public function build(): void
    {
        $endpoint = new MessageDeliveryEndpoint(
            'testing',
            new ContextTestTransport(),
            new ContextTestDestination()
        );

        $factory = new KernelContextFactory(
            new EndpointRouter($endpoint),
            new DefaultDeliveryOptionsFactory(),
            new NullLogger()
        );

        $context = $factory->create(
            new \stdClass(),
            [],
            new ReceivedMessageMetadata(uuid(), uuid(), [])
        );

        self::assertInstanceOf(KernelContext::class, $context);
    }
}
