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

namespace Desperado\Framework\Infrastructure\CQRS\Context;

use Desperado\Framework\Domain\ParameterBag;

/**
 * Messages delivery options container
 */
class DeliveryOptions
{
    /**
     * Destination exchange
     *
     * @var string|null
     */
    private $destination;

    /**
     * Headers bag
     *
     * @var ParameterBag
     */
    private $headers;

    /**
     * @param string|null       $destination
     * @param ParameterBag|null $headersBag
     */
    public function __construct(string $destination = null, ParameterBag $headersBag = null)
    {
        $this->destination = $destination;
        $this->headers = $headersBag ?? new ParameterBag();
    }

    /**
     * Has specified destination
     *
     * @return bool
     */
    public function destinationSpecified(): bool
    {
        return '' !== (string) $this->destination;
    }

    /**
     * Change delivery destination
     *
     * @param string $destination
     *
     * @return DeliveryOptions
     */
    public function changeDestination(string $destination): self
    {
        return new self($destination, $this->headers);
    }

    /**
     * Get headers
     *
     * @return ParameterBag
     */
    public function getHeaders(): ParameterBag
    {
        return $this->headers;
    }

    /**
     * Get message destination
     *
     * @return string
     */
    public function getDestination(): string
    {
        return (string) $this->destination;
    }
}
