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
use Desperado\ServiceBus\Demo\Customer\Command as CustomerCommands;
use Desperado\ServiceBus\Demo\Customer\Event as CustomerEvents;
use Desperado\ServiceBus\Demo\EmailNotifications\Command\SendEmailCommand;
use Desperado\ServiceBus\Demo\EmailNotifications\Event as EmailNotificationsEvents;

/**
 * @Annotations\Saga(
 *     identityNamespace="Desperado\ServiceBus\Demo\Customer\Identity\CustomerVerificationSagaIdentifier",
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
        /** @var CustomerCommands\StartVerificationSagaCommand $command */

        $this->fire(
            CustomerCommands\SendCustomerVerificationMessageCommand::create([
                'requestId'          => $this->getIdentityAsString(),
                'customerIdentifier' => $command->getCustomerIdentifier()
            ])
        );
    }

    /**
     * @Annotations\SagaEventListener()
     *
     * @param Event\CustomerVerificationTokenReceivedEvent $event
     *
     * @return void
     */
    protected function onCustomerVerificationTokenReceivedEvent(
        CustomerEvents\CustomerVerificationTokenReceivedEvent $event
    ): void
    {
        $this->fire(
            CustomerCommands\ActivateCustomerCommand::create([
                'requestId'  => $event->getRequestId(),
                'identifier' => $event->getIdentifier()
            ])
        );
    }

    /**
     * @Annotations\SagaEventListener()
     *
     * @param Event\CustomerActivatedEvent $event
     *
     * @return void
     */
    protected function onCustomerActivatedEvent(CustomerEvents\CustomerActivatedEvent $event): void
    {
        /** Somewhere here we got the settings for sending a message */

        $this->fire(
            SendEmailCommand::create([
                'requestId' => $this->getIdentityAsString(),
                'fromEmail' => 'source@source.com',
                'toEmail'   => 'destination@destination.com',
                'body'      => \str_repeat('x', 51),
                'subject'   => 'Test subject'
            ])
        );
    }

    /**
     * @Annotations\SagaEventListener()
     *
     * @param Event\CustomerAggregateNotFoundEvent $event
     *
     * @return void
     */
    protected function onCustomerAggregateNotFoundEvent(CustomerEvents\CustomerAggregateNotFoundEvent $event): void
    {
        $this->fail(
            \sprintf('Customer aggregate "%s" not found', $event->getIdentifier())
        );
    }

    /**
     * @Annotations\SagaEventListener()
     *
     * @param EmailNotificationsEvents\EmailSentEvent $event
     *
     * @return void
     */
    protected function onEmailSentEvent(EmailNotificationsEvents\EmailSentEvent $event): void
    {
        unset($event);

        $this->complete('Customer successful registered');
    }
}
