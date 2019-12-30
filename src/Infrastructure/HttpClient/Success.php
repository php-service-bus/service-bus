<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Infrastructure\HttpClient;

/**
 * @psalm-immutable
 */
final class Success implements Either
{
    /** @var int */
    public $resultCode;

    /** @var string */
    public $requestPayload;

    /** @var string */
    public $responseBody;

    /** @var string */
    public $description;

    public function __construct(int $resultCode, string $requestPayload, string $responseBody, string $description)
    {
        $this->resultCode     = $resultCode;
        $this->requestPayload = $requestPayload;
        $this->responseBody   = $responseBody;
        $this->description    = $description;
    }
}
