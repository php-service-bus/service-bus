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

namespace Desperado\ServiceBus\HttpClient;

/**
 *
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
     *
     * @return self
     */
    public static function post(string $url, $body): self
    {
        return new self('POST', $url, [], $body);
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
     * Get request http method
     *
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * Get endpoint URL
     *
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * Get request headers
     *
     * @return array
     */
    public function getHeaders(): array
    {
        return null !== $this->body ? $this->body->getHeaders() : $this->headers;
    }

    /**
     * Get request payload
     *
     * @return FormBody|string|null
     */
    public function getBody()
    {
        return $this->body;
    }
}
