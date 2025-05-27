<?php

/**
 * @author Stepan Zolotarev
 */

declare(strict_types=1);

namespace ServiceBus\Tests\Retry;

use PHPUnit\Framework\TestCase;
use ServiceBus\Common\EntryPoint\Retry\FailureContext;
use ServiceBus\Common\Metadata\ServiceBusMetadata;
use ServiceBus\MessageSerializer\Symfony\SymfonyJsonObjectSerializer;
use ServiceBus\Retry\SimpleRetryStrategy;
use ServiceBus\Storage\Common\DatabaseAdapter;
use ServiceBus\Storage\Common\StorageConfiguration;
use ServiceBus\Storage\Sql\DoctrineDBAL\DoctrineDBALAdapter;
use ServiceBus\Storage\Sql\DoctrineDBAL\DoctrineDBALResultSet;
use ServiceBus\Tests\EntryPoint\EntryPointTestMessage;
use ServiceBus\Tests\TestContext;

use function Amp\Promise\wait;
use function ServiceBus\Common\uuid;
use function ServiceBus\Storage\Sql\fetchOne;

final class SimpleRetryStrategyTest extends TestCase
{
    private DatabaseAdapter $databaseAdapter;
    private SymfonyJsonObjectSerializer $messageSerializer;
    private EntryPointTestMessage $message;
    private TestContext $context;
    private FailureContext $failureContext;

    protected function setUp(): void
    {
        parent::setUp();

        $this->databaseAdapter = new DoctrineDBALAdapter(
            new StorageConfiguration((string)\getenv('TEST_POSTGRES_DSN'))
        );

        wait(
            $this->databaseAdapter->execute(
                'CREATE TABLE IF NOT EXISTS failed_messages
(
    id              uuid constraint failed_messages_pk   primary key,
    message_id      uuid      not null,
    trace_id        uuid      not null,
    message_hash    varchar   not null,
    message_class   varchar   not null,
    message_payload bytea     not null,
    failure_context jsonb     not null,
    recorded_at     timestamp not null,
    recovered_at    timestamp
);'
            )
        );

        $this->messageSerializer = new SymfonyJsonObjectSerializer();
        $this->message = new EntryPointTestMessage(uuid());
        $this->context = new TestContext($this->message);
        $this->failureContext = new FailureContext([$this->message->id => 'some error']);
    }

    /**
     * @test
     */
    public function retry(): void
    {
        $retryStrategy = new SimpleRetryStrategy(
            databaseAdapter: $this->databaseAdapter,
            messageSerializer: $this->messageSerializer,
            maxRetryCount: 10,
            retryDelay: 1
        );

        wait($retryStrategy->retry($this->message, $this->context, $this->failureContext));

        self::assertCount(1, $this->context->messages);
        foreach ($this->context->messages as $key => $contextMessage) {
            self::assertSame($this->message, $contextMessage);
            self::assertArrayHasKey($key, $this->context->withMetadata);
            self::assertSame([
                ServiceBusMetadata::SERVICE_BUS_MESSAGE_RETRY_COUNT => 1,
                ServiceBusMetadata::SERVICE_BUS_MESSAGE_FAILED_IN => $this->message->id,
            ], $this->context->withMetadata[$key]->variables());
        }

        /** @var DoctrineDBALResultSet $resultSet */
        $resultSet = wait(
            $this->databaseAdapter->execute(
                'SELECT * FROM failed_messages WHERE message_id = ? AND trace_id = ?',
                [
                    $this->context->metadata()->messageId(),
                    $this->context->metadata()->traceId(),
                ]
            )
        );
        $data = wait(fetchOne($resultSet));

        self::assertNull($data);
    }

    /**
     * @test
     */
    public function retryWithMaxRetriesReached(): void
    {
        $retryStrategy = new SimpleRetryStrategy(
            databaseAdapter: $this->databaseAdapter,
            messageSerializer: $this->messageSerializer,
            maxRetryCount: 0,
            retryDelay: 1
        );

        wait($retryStrategy->retry($this->message, $this->context, $this->failureContext));

        self::assertCount(0, $this->context->messages);

        /** @var DoctrineDBALResultSet $resultSet */
        $resultSet = wait(
            $this->databaseAdapter->execute(
                'SELECT * FROM failed_messages WHERE message_id = ? AND trace_id = ?',
                [
                    $this->context->metadata()->messageId(),
                    $this->context->metadata()->traceId(),
                ]
            )
        );
        $data = wait(fetchOne($resultSet));

        self::assertIsArray($data);
        self::assertArrayHasKey('message_class', $data);
        self::assertSame($data['message_class'], EntryPointTestMessage::class);
    }

    /**
     * @test
     */
    public function backoff(): void
    {
        $retryStrategy = new SimpleRetryStrategy(
            databaseAdapter: $this->databaseAdapter,
            messageSerializer: $this->messageSerializer,
            maxRetryCount: 0,
            retryDelay: 1
        );

        wait($retryStrategy->backoff($this->message, $this->context, $this->failureContext));

        /** @var DoctrineDBALResultSet $resultSet */
        $resultSet = wait(
            $this->databaseAdapter->execute(
                'SELECT * FROM failed_messages WHERE message_id = ? AND trace_id = ?',
                [
                    $this->context->metadata()->messageId(),
                    $this->context->metadata()->traceId(),
                ]
            )
        );
        $data = wait(fetchOne($resultSet));

        self::assertIsArray($data);
        self::assertArrayHasKey('message_class', $data);
        self::assertSame($data['message_class'], EntryPointTestMessage::class);
    }
}
