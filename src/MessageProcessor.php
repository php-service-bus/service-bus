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

use Desperado\CQRS\Context\HttpRequestContextInterface;
use Desperado\CQRS\MessageBus;
use Desperado\Domain\Message\AbstractQueryMessage;
use Desperado\Domain\Message\MessageInterface;
use Desperado\EventSourcing\EventSourcingService;
use Desperado\Framework\Application\AbstractApplicationContext;
use Desperado\Infrastructure\Bridge\Router\Exceptions\HttpException;
use Desperado\Saga\Service\SagaService;
use function React\Promise\all;
use React\Promise\FulfilledPromise;
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
     * @param MessageInterface           $message
     * @param AbstractApplicationContext $context
     *
     * @return PromiseInterface
     */
    public function execute(MessageInterface $message, AbstractApplicationContext $context): PromiseInterface
    {
        return $this->createHandleMessagePromise($message, $context);
    }

    /**
     * Create message execution promise
     *
     * @param MessageInterface           $message
     * @param AbstractApplicationContext $context
     *
     * @return PromiseInterface
     */
    private function createHandleMessagePromise(
        MessageInterface $message,
        AbstractApplicationContext $context
    ): PromiseInterface
    {
        return new Promise(
            function($resolve, $reject) use ($message, $context)
            {
                try
                {
                    /** Handle message */
                    $promise = $this->messageBus->handle($message, $context);

                    if(null === $promise || false === ($promise instanceof PromiseInterface))
                    {
                        $promise = new FulfilledPromise($promise);
                    }

                    $promise->then(
                        function() use ($message, $context, $resolve, $reject)
                        {
                            try
                            {
                                $promise = all([
                                    $this->eventSourcingService->commitAll($context),
                                    $this->sagaService->commitAll($context),
                                ]);

                                $promise
                                    ->then(
                                        $resolve,
                                        function(\Throwable $throwable) use ($reject, $context, $message)
                                        {
                                            $context->logContextThrowable($message, $throwable);

                                            $reject($throwable);
                                        }
                                    );
                            }
                            catch(\Throwable $throwable)
                            {
                                $context->logContextThrowable($message, $throwable);

                                $reject($throwable);
                            }
                        },
                        function(\Throwable $throwable) use ($message, $context, $reject)
                        {
                            if(
                                $message instanceof AbstractQueryMessage &&
                                $context instanceof HttpRequestContextInterface
                            )
                            {
                                /** @var \Throwable|HttpException $throwable */

                                $isHttpException = $throwable instanceof HttpException;
                                $httpResponseCode = true === $isHttpException
                                    ? $throwable->getHttpCode()
                                    : 500;

                                $httpExceptionMessage = true === $isHttpException
                                    ? $throwable->getResponseMessage()
                                    : 'Application error';


                                $context->sendResponse(
                                    $message,
                                    $httpResponseCode,
                                    $httpExceptionMessage
                                );
                            }

                            $reject($throwable);
                        }
                    );
                }
                catch(\Throwable $throwable)
                {
                    $context->logContextThrowable($message, $throwable);

                    $reject($throwable);
                }
            }
        );
    }
}
