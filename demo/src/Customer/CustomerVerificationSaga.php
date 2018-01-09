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

use Desperado\Domain\Message\AbstractCommand;
use Desperado\Saga\AbstractSaga;
use Desperado\Saga\Annotations;

/**
 * @Annotations\Saga(
 *     identityNamespace="Desperado\ServiceBus\Demo\Customer\Identity\CustomerRegistrationSagaIdentifier",
 *     containingIdentityProperty="requestId",
 *     expireDateModifier="+2 days"
 * )
 */
class CustomerVerificationSaga extends AbstractSaga
{
    /**
     * Start verification saga
     *
     * @param AbstractCommand $command
     *
     * @return void
     */
    public function start(AbstractCommand $command): void
    {

    }
}
