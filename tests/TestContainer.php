<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Tests;

use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

/**
 *
 */
class TestContainer implements ContainerInterface
{
    /**
     * @var array
     */
    private $storage;

    /**
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        $this->storage = $data;
    }

    /**
     * @inheritdoc
     */
    public function get($id)
    {
        if(false === $this->has($id))
        {
            throw new ServiceNotFoundException($id);
        }

        return $this->storage[$id] ?? null;
    }

    /**
     * @inheritdoc
     */
    public function has($id): bool
    {
        return isset($this->storage[$id]);
    }
}
