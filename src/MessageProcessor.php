<?php

/**
 * CQRS/Event Sourcing framework
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @url     https://github.com/mmasiukevich
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\Framework;

use Desperado\CQRS\MessageBus;
use Desperado\Domain\Message\AbstractMessage;
use Desperado\EventSourcing\Service\EventSourcingService;
use Desperado\Framework\Application\AbstractApplicationContext;
use Desperado\Saga\Service\SagaService;
use React\Promise\Promise;
use React\Promise\PromiseInterface;

/**
 * Message execution processor
 */
class MessageProcessor
{
    /**
     * Message bus
     *
     * @var MessageBus
     */
    private $messageBus;

    /**
     * Event sourcing service
     *
     * @var EventSourcingService
     */
    private $eventSourcingService;

    /**
     * Sagas service
     *
     * @var SagaService
     */
    private $sagaService;

    /**
     * @param MessageBus           $messageBus
     * @param EventSourcingService $eventSourcingService
     * @param SagaService          $sagaService
     */
    public function __construct(
        MessageBus $messageBus,
        EventSourcingService $eventSourcingService,
        SagaService $sagaService
    )
    {
        $this->messageBus = $messageBus;
        $this->eventSourcingService = $eventSourcingService;
        $this->sagaService = $sagaService;
    }

    /**
     * Execute message
     *
     * @param AbstractMessage           $message
     * @param AbstractApplicationContext $context
     *
     * @return PromiseInterface
     */
    public function execute(AbstractMessage $message, AbstractApplicationContext $context): PromiseInterface
    {
        return $this->createHandleMessagePromise($message, $context);
    }

    /**
     * Create message execution promise
     *
     * @param AbstractMessage           $message
     * @param AbstractApplicationContext $context
     *
     * @return PromiseInterface
     */
    private function createHandleMessagePromise(
        AbstractMessage $message,
        AbstractApplicationContext $context
    ): PromiseInterface
    {
        return new Promise(
            function($resolve, $reject) use ($message, $context)
            {
                try
                {
                    $this->messageBus->handle($message, $context);

                    $this->sagaService->commitAll($context);
                    $this->eventSourcingService->commitAll($context);

                    $resolve();
                }
                catch(\Throwable $throwable)
                {
                    $reject($throwable);
                }
            }
        );
    }
}
