<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Demo\Customer;

use Desperado\Domain\Identity\IdentityInterface;
use Desperado\EventSourcing\AbstractAggregateRoot;
use Desperado\EventSourcing\IndexInterface;
use Desperado\ServiceBus\Demo\Customer\Identity\CustomerEmailIndexIdentity;

/**
 * Index stores the relationship email address of the user and his identity
 */
class CustomerEmailIndex extends AbstractAggregateRoot implements IndexInterface
{
    /**
     * @inheritdoc
     *
     * @return IdentityInterface
     */
    public static function getIndexIdentifier(): IdentityInterface
    {
        return new CustomerEmailIndexIdentity('1dea6550-a1ae-4cc2-a403-89b51816ed61');
    }
}
