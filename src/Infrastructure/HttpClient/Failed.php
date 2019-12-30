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
final class Failed implements Either
{
    /** @var int */
    public $resultCode;

    /** @var string|null */
    public $requestPayload;

    /** @var string|null */
    public $responseBody;

    /**
     * This is an internal request related error.
     *
     * @var bool
     */
    public $isInternalError = false;

    /**
     * This is a server side request processing error.
     *
     * @var bool
     */
    public $isServerError = false;

    /**
     * This is an error associated with invalid request parameters.
     *
     * @var bool
     */
    public $isClientError = false;

    /** @var string */
    public $description;

    /**
     * This is an error associated with invalid request parameters.
     */
    public static function client(string $description, int $resultCode, string $requestPayload, string $responseBody): self
    {
        return new self(false, true, false, $description, $resultCode, $requestPayload, $responseBody);
    }

    /**
     * This is a server side request processing error.
     */
    public static function server(string $description, int $resultCode, string $requestPayload, string $responseBody): self
    {
        return new self(false, false, true, $description, $resultCode, $requestPayload, $responseBody);
    }

    /**
     * This is an internal request related error.
     */
    public static function internal(string $description): self
    {
        return new self(true, false, false, $description, 400, null, null);
    }

    private function __construct(
        bool $isInternalError,
        bool $isClientError,
        bool $isServerError,
        string $description,
        int $resultCode,
        ?string $requestPayload,
        ?string $responseBody
    ) {
        $this->isInternalError = $isInternalError;
        $this->isClientError   = $isClientError;
        $this->isServerError   = $isServerError;
        $this->description     = $description;
        $this->resultCode      = $resultCode;
        $this->requestPayload  = $requestPayload;
        $this->responseBody    = $responseBody;
    }
}
