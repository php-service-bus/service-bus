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

use Desperado\Domain\Uuid;
use Desperado\EventSourcing\Examples\CustomerIndexIdentity;
use Desperado\ServiceBus\Annotations;
use Desperado\ServiceBus\Demo\Application\ApplicationContext;
use Desperado\ServiceBus\Demo\Customer\Command as CustomerCommands;
use Desperado\ServiceBus\Demo\Customer\CustomerAggregate;
use Desperado\ServiceBus\Demo\Customer\CustomerEmailIndex;
use Desperado\ServiceBus\Demo\Customer\Identity\CustomerAggregateIdentifier;
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
        $context
            ->getEventSourcingService()
            ->obtainIndex(CustomerEmailIndex::class)
            ->then(
                function(CustomerEmailIndex $customerEmailIndex) use ($command, $context)
                {
                    /** new customer */
                    if(false === $customerEmailIndex->hasIdentifier($command->getEmail()))
                    {
                        $customerIdentifier = new CustomerAggregateIdentifier(Uuid::new());

                        $context
                            ->getEventSourcingService()
                            ->createAggregate($customerIdentifier)
                            ->then(
                                function(CustomerAggregate $aggregate) use ($customerEmailIndex, $customerIdentifier, $command)
                                {
                                    $aggregate->registerCustomer($command);

                                    $customerEmailIndex->store($command->getEmail(), $customerIdentifier);
                                },
                                $context->getContextThrowableCallableLogger($command)
                            );
                    }
                },
                $context->getContextThrowableCallableLogger($command)
            );
    }
}
