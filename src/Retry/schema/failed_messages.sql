CREATE TABLE IF NOT EXISTS failed_messages
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
);

CREATE index IF NOT EXISTS failed_messages_message_hash on failed_messages (message_hash);
CREATE index IF NOT EXISTS failed_messages_message_id on failed_messages (message_id);
CREATE index IF NOT EXISTS failed_messages_recovered_at on failed_messages (recorded_at);