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

namespace Desperado\Framework\Infrastructure\StorageManager;

use Desperado\Framework\Infrastructure\Bridge\ORM\AbstractEntityRepository;

/**
 *
 */
interface EntityManagerInterface extends StorageManagerInterface
{
    /**
     * Get entity repository instance
     *
     * @param string $entityNamespace
     *
     * @return AbstractEntityRepository
     */
    public function getRepository(string $entityNamespace): AbstractEntityRepository;

    /**
     * Persist entity
     *
     * @param object $entityObject
     *
     * @return void
     */
    public function persist($entityObject): void;

    /**
     * Save changes to database
     *
     * @param object   $entityObject
     * @param callable $onSuccess function() {}
     * @param callable $onFailed  function(\Throwable $throwable) {}
     *
     * @return void
     */
    public function flush($entityObject, callable $onSuccess, callable $onFailed): void;
}
