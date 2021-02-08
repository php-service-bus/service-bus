<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <contacts@desperado.dev>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 0);

namespace ServiceBus\EntryPoint\Retry;

use Amp\Promise;
use ServiceBus\Common\Context\ServiceBusContext;
use ServiceBus\Common\EntryPoint\Retry\FailureContext;
use ServiceBus\Common\EntryPoint\Retry\RetryStrategy;
use ServiceBus\Context\DeliveryMessageMetadata;
use ServiceBus\Metadata\ServiceBusMetadata;
use ServiceBus\Storage\Common\DatabaseAdapter;
use function Amp\call;
use function Amp\delay;
use function ServiceBus\Common\jsonEncode;
use function ServiceBus\Common\now;
use function ServiceBus\Common\uuid;
use function ServiceBus\Storage\Sql\insertQuery;

/**
 *
 */
final class SimpleRetryStrategy implements RetryStrategy
{
    /**
     * @var DatabaseAdapter
     */
    private $databaseAdapter;

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
        int $maxRetryCount,
        int $retryDelay
    )
    {
        $this->databaseAdapter = $databaseAdapter;
        $this->maxRetryCount   = $maxRetryCount;
        $this->retryDelay      = $retryDelay;
    }

    public function retry(object $message, ServiceBusContext $context, FailureContext $details): Promise
    {
        $currentRetryCount = self::currentRetryCount($context) + 1;

        if($currentRetryCount <= $this->maxRetryCount)
        {
            return call(
                function() use ($message, $context, $details, $currentRetryCount): \Generator
                {
                    try
                    {
                        $messageExecutors = \implode(',', \array_keys($details->executors));
                        $outcomeMetadata  = DeliveryMessageMetadata::create([
                            ServiceBusMetadata::SERVICE_BUS_MESSAGE_RETRY_COUNT => $currentRetryCount,
                            ServiceBusMetadata::SERVICE_BUS_MESSAGE_FAILED_IN   => $messageExecutors,
                            ServiceBusMetadata::SERVICE_BUS_TRACE_ID            => self::traceId($context)
                        ]);

                        yield delay($this->retryDelay * 1000);

                        yield $context->delivery(
                            message: $message,
                            withMetadata: $outcomeMetadata
                        );
                    }
                    catch(\Throwable $throwable)
                    {
                        yield $this->backoff(
                            message: $message,
                            context: $context,
                            details: $details
                        );
                    }
                }
            );
        }

        return $this->backoff(
            message: $message,
            context: $context,
            details: $details
        );
    }

    public function backoff(object $message, ServiceBusContext $context, FailureContext $details): Promise
    {
        return call(
            function() use ($message, $context, $details): \Generator
            {
                try
                {
                    $insertQuery = insertQuery('failed_messages', [
                        'id'              => uuid(),
                        'message_id'      => $context->metadata()->messageId(),
                        'trace_id'        => self::traceId($context),
                        'message_class'   => \get_class($message),
                        'message_payload' => \gzcompress(\base64_encode(\serialize($message)), 7),
                        'failure_context' => jsonEncode(\get_object_vars($details)),
                        'recorded_at'     => now()->format('c'),
                    ]);

                    $compiledQuery = $insertQuery->compile();

                    yield $this->databaseAdapter->execute(
                        queryString: $compiledQuery->sql(),
                        parameters: $compiledQuery->params()
                    );
                }
                catch(\Throwable $throwable)
                {

                }
            }
        );
    }

    private static function traceId(ServiceBusContext $context): ?string
    {
        $traceId = $context->metadata()->get(ServiceBusMetadata::SERVICE_BUS_TRACE_ID);

        return $traceId !== null ? (string) $traceId : null;
    }

    private static function currentRetryCount(ServiceBusContext $context): int
    {
        return (int) ($context->metadata()->get(ServiceBusMetadata::SERVICE_BUS_MESSAGE_RETRY_COUNT, 0));
    }
}
