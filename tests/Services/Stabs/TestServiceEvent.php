<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Tests\Services\Stabs;

use Desperado\Domain\Message\AbstractEvent;
use Symfony\Component\Validator\Constraints as Assert;

/**
 *
 */
class TestServiceEvent extends AbstractEvent
{
    /**
     * @Assert\NotBlank()
     *
     * @var string
     */
    protected $scalar;

    /**
     * @Assert\NotNull()
     *
     * @var FirstDTO
     */
    protected $object;
}
