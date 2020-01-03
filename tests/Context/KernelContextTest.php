<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Tests\Context;

use Monolog\Handler\TestHandler;
use Monolog\Logger;
use Monolog\Processor\PsrLogMessageProcessor;
use PHPUnit\Framework\TestCase;
use ServiceBus\Context\KernelContext;
use ServiceBus\Endpoint\EndpointRouter;
use ServiceBus\Endpoint\MessageDeliveryEndpoint;
use ServiceBus\Endpoint\Options\DefaultDeliveryOptions;
use ServiceBus\Endpoint\Options\DefaultDeliveryOptionsFactory;
use ServiceBus\Transport\Common\Package\OutboundPackage;
use function Amp\Promise\wait;

/**
 *
 */
final class KernelContextTest extends TestCase
{
    /** @var TestHandler */
    private $logHandler;

    /** @var ContextTestTransport */
    private $transport;

    /** @var KernelContext */
    private $context;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->logHandler = new TestHandler();
        $this->logHandler->pushProcessor(new PsrLogMessageProcessor());

        $this->transport = new ContextTestTransport();

        $this->context = new KernelContext(
            new ContextTestIncomingPackage('message_id', 'trace_id', '{}', ['key' => 'value']),
            new EmptyMessage(),
            new EndpointRouter(
                $endpoint = new MessageDeliveryEndpoint(
                    'testing',
                    $this->transport,
                    new ContextTestDestination()
                )
            ),
            new DefaultDeliveryOptionsFactory(),
            new Logger('tests', [$this->logHandler])
        );
    }

    /** @test */
    public function simpleCheck(): void
    {
        static::assertTrue($this->context->isValid());
        static::assertCount(0, $this->context->violations());
        static::assertSame('message_id', $this->context->operationId());
        static::assertSame('trace_id', $this->context->traceId());
        static::assertSame(['key' => 'value'], $this->context->headers());
    }

    /** @test */
    public function delivery(): void
    {
        wait($this->context->delivery(new EmptyMessage(), DefaultDeliveryOptions::create()));

        /** @var OutboundPackage $outboundPackage */
        $outboundPackage = $this->transport->outboundPackageCollection[\array_key_first($this->transport->outboundPackageCollection)];

        static::assertSame(
            '{"message":[],"namespace":"ServiceBus\\\\Tests\\\\Context\\\\EmptyMessage"}',
            $outboundPackage->payload
        );

        static::assertArrayHasKey('X-SERVICE-BUS-ENCODER', $outboundPackage->headers);
        static::assertSame('service_bus.encoder.default_handler', $outboundPackage->headers['X-SERVICE-BUS-ENCODER']);
    }

    /** @test */
    public function returnMessage(): void
    {
        wait($this->context->return(1));

        /** @var OutboundPackage $outboundPackage */
        $outboundPackage = $this->transport->outboundPackageCollection[\array_key_first($this->transport->outboundPackageCollection)];

        static::assertSame(
            '{"message":[],"namespace":"ServiceBus\\\\Tests\\\\Context\\\\EmptyMessage"}',
            $outboundPackage->payload
        );
    }

    /** @test */
    public function logContextMessage(): void
    {
        $this->context->logContextMessage('some {placeholder} message', [
            'placeholder'   => 'success',
            'additionalKey' => 'value'
        ]);

        $logEntries = $this->logHandler->getRecords();

        $latest = \end($logEntries);

        static::assertSame('some success message', $latest['message']);
        static::assertSame(
            [
                'placeholder'   => 'success',
                'additionalKey' => 'value',
                'traceId'       => 'trace_id',
                'packageId'     => 'message_id'
            ],
            $latest['context']
        );
    }

    /** @test */
    public function logContextThrowable(): void
    {
        $this->context->logContextThrowable(new \LogicException('qwerty'), ['additionalKey' => 'value']);

        $logEntries = $this->logHandler->getRecords();

        $latest = \end($logEntries);

        static::assertSame('qwerty', $latest['message']);
        static::assertArrayHasKey('throwablePoint', $latest['context']);
        static::assertArrayHasKey('additionalKey', $latest['context']);
    }
}
