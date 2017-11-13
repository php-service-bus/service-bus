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
use Desperado\Framework\Application\AbstractApplicationContext;
use Desperado\Framework\Events as FrameworkEvents;
use Desperado\Infrastructure\Bridge\Router\Exceptions\HttpException;
use React\Promise\FulfilledPromise;
use React\Promise\Promise;
use React\Promise\PromiseInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

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
     * Framework event dispatcher
     *
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @param EventDispatcherInterface $dispatcher
     * @param MessageBus               $messageBus
     */
    public function __construct(
        EventDispatcherInterface $dispatcher,
        MessageBus $messageBus
    )
    {
        $this->eventDispatcher = $dispatcher;
        $this->messageBus = $messageBus;

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
                $this->eventDispatcher->dispatch(
                    FrameworkEventsInterface::BEFORE_MESSAGE_EXECUTION,
                    new FrameworkEvents\OnMessageExecutionStartedEvent($message, $context)
                );

                try
                {
                    $messageStartTime = \microtime(true);

                    /** Handle message */
                    $promise = $this->messageBus->handle($message, $context);

                    if(null === $promise || false === ($promise instanceof PromiseInterface))
                    {
                        $promise = new FulfilledPromise($promise);
                    }

                    $promise->then(
                        function() use ($message, $context, $resolve, $messageStartTime)
                        {
                            try
                            {
                                $this->eventDispatcher->dispatch(
                                    FrameworkEventsInterface::AFTER_MESSAGE_EXECUTION,
                                    new FrameworkEvents\OnMessageExecutionFinishedEvent(
                                        $message,
                                        $context,
                                        \microtime(true) - $messageStartTime
                                    )
                                );

                                $resolve();
                            }
                            catch(\Throwable $throwable)
                            {
                                $context->logContextThrowable($message, $throwable);
                            }
                        },
                        function(\Throwable $throwable) use ($message, $context, $reject)
                        {
                            $this->eventDispatcher->dispatch(
                                FrameworkEventsInterface::MESSAGE_EXECUTION_FAILED,
                                new FrameworkEvents\OnMessageExecutionFailedEvent($message, $context, $throwable)
                            );

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
                    $this->eventDispatcher->dispatch(
                        FrameworkEventsInterface::MESSAGE_EXECUTION_FAILED,
                        new FrameworkEvents\OnMessageExecutionFailedEvent($message, $context, $throwable)
                    );

                    $reject($throwable);
                }
            }
        );
    }
}
