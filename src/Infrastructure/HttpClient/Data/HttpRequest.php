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

namespace Desperado\ServiceBus\Infrastructure\HttpClient\Data;

use Desperado\ServiceBus\Infrastructure\HttpClient\FormBody;

/**
 * Http request data
 *
 * @codeCoverageIgnore
 */
final class HttpRequest
{
    /**
     * Http method
     *
     * @var string
     */
    private $method;

    /**
     * Request URL
     *
     * @var string
     */
    private $url;

    /**
     * Request headers
     *
     * @var array
     */
    private $headers;

    /**
     * Request payload
     *
     * @var FormBody|string|null
     */
    private $body;

    /**
     * @param string $url
     * @param array  $queryParameters
     * @param array  $headers
     *
     * @return self
     */
    public static function get(string $url, array $queryParameters = [], array $headers = []): self
    {
        $url = \sprintf('%s?%s', \rtrim($url, '?'), \http_build_query($queryParameters));

        return new self('GET', \rtrim($url, '?'), $headers);
    }

    /**
     * @param string          $url
     * @param FormBody|string $body
     * @param array           $headers
     *
     * @return self
     */
    public static function post(string $url, $body, array $headers = []): self
    {
        return new self('POST', $url, $headers, $body);
    }

    /**
     * @param string               $method
     * @param string               $url
     * @param array                $headers
     * @param FormBody|string|null $body
     */
    private function __construct(string $method, string $url, array $headers = [], $body = null)
    {
        $this->method  = $method;
        $this->url     = $url;
        $this->headers = $headers;
        $this->body    = $body;
    }

    /**
     * Is POST request
     *
     * @return bool
     */
    public function isPost(): bool
    {
        return 'POST' === $this->method;
    }

    /**
     * Receive request http method
     *
     * @return string
     */
    public function httpMethod(): string
    {
        return $this->method;
    }

    /**
     * Receive endpoint URL
     *
     * @return string
     */
    public function url(): string
    {
        return $this->url;
    }

    /**
     * Receive request headers
     *
     * @return array
     */
    public function headers(): array
    {
        if($this->body instanceof FormBody)
        {
            return $this->body->headers();
        }

        return $this->headers;
    }

    /**
     * Receive request body
     *
     * @return FormBody|string|null
     */
    public function body()
    {
        return $this->body;
    }
}
