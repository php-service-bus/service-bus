<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Tests\HttpServer;

use Desperado\ServiceBus\HttpServer\HttpResponse;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class HttpResponseTest extends TestCase
{
    /**
     * @test
     *
     * @return void
     */
    public function create(): void
    {
        $response = new  HttpResponse(200, ['key' => 'value'], 'response body');

        static::assertEquals(200, $response->getCode());
        static::assertEquals(['key' => 'value'], $response->getHeaders());
        static::assertEquals('response body', $response->getBody());
    }
}
