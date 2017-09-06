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

namespace Desperado\Framework\Infrastructure\Bridge\ORM;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;

/**
 * Base repository class
 */
abstract class AbstractRepository
{
    public function __construct($em, ClassMetadata $class)
    {

    }
}