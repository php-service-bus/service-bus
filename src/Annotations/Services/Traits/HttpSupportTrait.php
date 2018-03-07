<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Annotations\Services\Traits;

/**
 *
 */
trait HttpSupportTrait
{
    /**
     * Http request route
     *
     * @var string|null
     */
    protected $route;

    /**
     * Http request method
     *
     * @var string|null
     */
    protected $method;

    /**
     * Get http request route
     *
     * @return string|null
     */
    public function getRoute(): ?string
    {
        return null !== $this->route
            ? \rtrim($this->route, '/')
            : null;
    }

    /**
     * Get http request method
     *
     * @return string|null
     */
    public function getMethod(): ?string
    {
        return null !== $this->method ? \strtoupper($this->method) : null;
    }
}
