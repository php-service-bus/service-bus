<?php

/**
 * Command Query Responsibility Segregation, Event Sourcing implementation
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @url     https://github.com/mmasiukevich
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\Framework\Infrastructure\Bridge\HttpClient;

use Desperado\Framework\Domain\ParameterBag;

/**
 * Http request DTO
 */
class HttpRequest
{
    /**
     * Endpoint URL
     *
     * @var string
     */
    private $url;

    /**
     * Request data
     *
     * @var string|array|null
     */
    private $parameters;

    /**
     * Headers bag
     *
     * @var ParameterBag
     */
    private $headers;

    /**
     * @param string            $url
     * @param array|null|string $parameters
     * @param ParameterBag      $headers
     */
    public function __construct(string $url, $parameters = null, ParameterBag $headers = null)
    {
        $this->url = $url;
        $this->parameters = $parameters;
        $this->headers = $headers ?? new ParameterBag();
    }

    /**
     * Get request URL
     *
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * Get request parameters
     *
     * @return array|null|string
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * Get headers bag
     *
     * @return ParameterBag
     */
    public function getHeaders(): ParameterBag
    {
        return $this->headers;
    }
}
