<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation)
 * Supports Saga pattern and Event Sourcing
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Application;

use function Amp\call;
use Amp\Deferred;
use Amp\Loop;
use Amp\Promise;
use Desperado\ServiceBus\Common\Contract\Messages\Message;
use Psr\Log\LoggerInterface;

/**
 *
 */
final class DefaultMessageExecutor implements MessageExecutor
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    public function process(Message $message, KernelContext $context, array $handlers): Promise
    {
        $logger   = $this->logger;
        $deferred = new Deferred();

        Loop::defer(
            static function() use ($deferred, $message, $context, $handlers, $logger): \Generator
            {
                $messageClass = \get_class($message);

                if(0 === \count($handlers))
                {
                    $logger->debug('There are no handlers configured for the message "{messageClass}"', [
                        'messageClass' => $messageClass,
                        'operationId'  => $context->operationId()
                    ]);

                    $deferred->resolve();

                    return;
                }

                $errors = 0;

                foreach($handlers as $handler)
                {
                    /** @var \Desperado\ServiceBus\MessageHandlers\Handler $handler */

                    try
                    {
                        yield call($handler, $message, $context);
                    }
                    catch(\Throwable $throwable)
                    {
                        $errors++;

                        $logger->error(
                            'When executing the message "{messageClass}" errors occurred: "{throwableMessage}"', [
                                'operationId'    => $context->operationId(),
                                'messageClass'   => $messageClass,
                                'throwableMessage' => $throwable->getMessage(),
                                'throwablePoint' => \sprintf('%s:%d', $throwable->getFile(), $throwable->getLine())
                            ]
                        );
                    }
                }

                0 !== $errors
                    ? $deferred->fail(new \RuntimeException('Execution completed with errors'))
                    : $deferred->resolve();
            }
        );

        return $deferred->promise();
    }
}
