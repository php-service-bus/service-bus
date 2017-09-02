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

namespace Desperado\ConcurrencyFramework\Domain\Pipeline;

/**
 * Pipelines collection
 */
class PipelineCollection
{
    /**
     * Pipelines
     *
     * @var PipelineInterface[]
     */
    private $collection = [];

    /**
     * Add pipeline
     *
     * @param PipelineInterface $pipeline
     *
     * @return PipelineCollection
     */
    public function add(PipelineInterface $pipeline): self
    {
        $this->collection[$pipeline->getName()] = $pipeline;

        return $this;
    }

    /**
     * Has pipeline with specified name
     *
     * @param string $name
     *
     * @return bool
     */
    public function has(string $name): bool
    {
        return isset($this->collection[$name]);
    }

    /**
     * Get specified pipeline
     *
     * @param string $name
     *
     * @return PipelineInterface|null
     */
    public function get(string $name): ?PipelineInterface
    {
        return true === $this->has($name) ? $this->collection[$name] : null;
    }
}
