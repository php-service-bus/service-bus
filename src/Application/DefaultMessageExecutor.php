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
                if(0 === \count($handlers))
                {
                    $logger->debug('There are no handlers configured for the message "{messageClass}"', [
                        'messageClass' => \get_class($message),
                        'operationId'  => $context->operationId()
                    ]);

                    $deferred->resolve();

                    return;
                }

                $errors = [];

                foreach($handlers as $handler)
                {
                    /** @var \Desperado\ServiceBus\MessageHandlers\Handler $handler */

                    try
                    {
                        yield call($handler, $message, $context);
                    }
                    catch(\Throwable $throwable)
                    {
                        $errors[] = \sprintf(
                            '%s (%s:%d)',
                            $throwable->getMessage(), $throwable->getFile(), $throwable->getLine()
                        );
                    }
                }

                if(0 !== \count($errors))
                {
                    $deferred->fail(
                        new \RuntimeException(
                            \sprintf(
                                'When executing the message "%s" errors occurred: %s',
                                \get_class($message),
                                \implode('; ', $errors)
                            )
                        )
                    );
                }
                else
                {
                    $deferred->resolve();
                }
            }
        );

        return $deferred->promise();
    }
}
