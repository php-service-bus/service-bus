<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <contacts@desperado.dev>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types=0);

namespace ServiceBus\Retry;

use Amp\Promise;
use ServiceBus\Common\Context\ServiceBusContext;
use ServiceBus\Common\EntryPoint\Retry\FailureContext;
use ServiceBus\Common\EntryPoint\Retry\RetryStrategy;
use ServiceBus\Common\Metadata\ServiceBusMetadata;
use ServiceBus\Context\DeliveryMessageMetadata;
use ServiceBus\MessageSerializer\ObjectSerializer;
use ServiceBus\Storage\Common\DatabaseAdapter;
use function Amp\call;
use function Amp\delay;
use function ServiceBus\Common\now;
use function ServiceBus\Common\throwableDetails;
use function ServiceBus\Common\uuid;
use function ServiceBus\Storage\Sql\insertQuery;

final class SimpleRetryStrategy implements RetryStrategy
{
    /**
     * @var DatabaseAdapter
     */
    private $databaseAdapter;

    /**
     * @var ObjectSerializer
     */
    private $messageSerializer;

    /**
     * @var int
     */
    private $maxRetryCount;

    /**
     * Retry delay (in seconds)
     *
     * @var int
     */
    private $retryDelay;

    public function __construct(
        DatabaseAdapter $databaseAdapter,
        ObjectSerializer $messageSerializer,
        int $maxRetryCount,
        int $retryDelay
    ) {
        $this->databaseAdapter   = $databaseAdapter;
        $this->messageSerializer = $messageSerializer;
        $this->maxRetryCount     = $maxRetryCount;
        $this->retryDelay        = $retryDelay;
    }

    public function retry(object $message, ServiceBusContext $context, FailureContext $details): Promise
    {
        return call(
            function () use ($message, $context, $details): \Generator
            {
                $currentRetryCount = self::currentRetryCount($context) + 1;

                if ($currentRetryCount <= $this->maxRetryCount)
                {
                    $delay = $this->retryDelay * 1000;

                    try
                    {
                        $messageExecutors = \implode(',', \array_keys($details->executors));
                        $outcomeMetadata  = DeliveryMessageMetadata::create(
                            traceId: $context->metadata()->traceId(),
                            variables: [
                                ServiceBusMetadata::SERVICE_BUS_MESSAGE_RETRY_COUNT => $currentRetryCount,
                                ServiceBusMetadata::SERVICE_BUS_MESSAGE_FAILED_IN   => $messageExecutors,
                            ]
                        );

                        $context->logger()->info(
                            'Resending an `{messageClass}` message to the queue wit delay `{delay}`',
                            [
                                'messageClass'   => \get_class($message),
                                'delay'          => $delay,
                                'failureContext' => $details->executors
                            ]
                        );

                        /** @todo: transport level delay */
                        yield delay($delay);

                        yield $context->delivery(
                            message: $message,
                            withMetadata: $outcomeMetadata
                        );
                    }
                    catch (\Throwable $throwable)
                    {
                        $context->logger()->error(
                            '`{messageClass}` message resending error: {throwableMessage}',
                            [
                                \array_merge(throwableDetails($throwable), ['messageClass' => \get_class($message)])
                            ]
                        );

                        yield $this->backoff(
                            message: $message,
                            context: $context,
                            details: $details
                        );
                    }

                    return;
                }

                $context->logger()->info(
                    'Number of retries exceeded for `{messageClass}` message',
                    [
                        'messageClass'   => \get_class($message),
                        'failureContext' => $details->executors
                    ]
                );

                yield $this->backoff(
                    message: $message,
                    context: $context,
                    details: $details
                );
            }
        );
    }

    public function backoff(object $message, ServiceBusContext $context, FailureContext $details): Promise
    {
        return call(
            function () use ($message, $context, $details): \Generator
            {
                $context->logger()->info(
                    'Saving `{messageClass}` message that could not be processed to spare storage',
                    [
                        'messageClass'   => \get_class($message),
                        'failureContext' => $details->executors
                    ]
                );

                $messagePayload = (string) \gzcompress($this->messageSerializer->encode($message));
                $messageHash    = \sha1($messagePayload);

                try
                {
                    $insertQuery = insertQuery('failed_messages', [
                        'id'              => uuid(),
                        'message_id'      => $context->metadata()->messageId(),
                        'trace_id'        => $context->metadata()->traceId(),
                        'message_hash'    => $messageHash,
                        'message_class'   => \get_class($message),
                        'message_payload' => $messagePayload,
                        'failure_context' => $this->messageSerializer->encode($details),
                        'recorded_at'     => now()->format('c'),
                    ]);

                    $compiledQuery = $insertQuery->compile();

                    /** @psalm-suppress MixedArgumentTypeCoercion */
                    yield $this->databaseAdapter->execute(
                        queryString: $compiledQuery->sql(),
                        parameters: $compiledQuery->params()
                    );
                }
                catch (\Throwable $throwable)
                {
                    $context->logger()->critical(
                        'Error saving `{messageClass}` message to spare storage: {throwableMessage}',
                        \array_merge(
                            throwableDetails($throwable),
                            [
                                'messageClass'   => \get_class($message),
                                'message_hash'   => $messageHash,
                                'messagePayload' => $messagePayload,
                                'headers'        => $context->headers(),
                                'metadata'       => $context->metadata()->variables()
                            ]
                        )
                    );
                }
            }
        );
    }

    private static function currentRetryCount(ServiceBusContext $context): int
    {
        return (int) ($context->metadata()->get(ServiceBusMetadata::SERVICE_BUS_MESSAGE_RETRY_COUNT, 0));
    }
}
