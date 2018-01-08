<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Demo\Customer\Services;

use Desperado\ServiceBus\Annotations;
use Desperado\ServiceBus\Demo\Application\ApplicationContext;
use Desperado\ServiceBus\Demo\Customer\Command as CustomerCommands;
use Desperado\ServiceBus\Services\ServiceInterface;

/**
 * @Annotations\Service()
 */
class RegisterCustomerService implements ServiceInterface
{
    /**
     * @Annotations\CommandHandler
     *
     * @param CustomerCommands\RegisterCustomerCommand $command
     * @param ApplicationContext                       $context
     *
     * @return void
     */
    public function executeRegisterCustomerCommand(
        CustomerCommands\RegisterCustomerCommand $command,
        ApplicationContext $context
    ): void
    {

    }
}
