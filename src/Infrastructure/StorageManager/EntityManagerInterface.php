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
     * @return AbstractEntityRepository
     */
    public function getRepository(): AbstractEntityRepository;
}
