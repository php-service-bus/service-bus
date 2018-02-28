<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\HttpServer;

/**
 * HTTP response dto
 */
class HttpResponse
{
    /**
     * Response code
     *
     * @var int
     */
    private $code;

    /**
     * Response headers
     *
     * @var array
     */
    private $headers = [];

    /**
     * Response body
     *
     * @var string|null
     */
    private $body;

    /**
     * @param int         $code
     * @param array       $headers
     * @param string|null $body
     */
    public function __construct(int $code = 200, array $headers = [], ?string $body = null)
    {
        $this->code = $code;
        $this->headers = $headers;
        $this->body = $body;
    }

    /**
     * Get response code
     *
     * @return int
     */
    public function getCode(): int
    {
        return $this->code;
    }

    /**
     * Get response headers
     *
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Get response body
     *
     * @return string|null
     */
    public function getBody(): ?string
    {
        return $this->body;
    }
}
