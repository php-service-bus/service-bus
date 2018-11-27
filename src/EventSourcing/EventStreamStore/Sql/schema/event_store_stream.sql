CREATE TABLE IF NOT EXISTS event_store_stream
(
    id uuid PRIMARY KEY,
    identifier_class varchar NOT NULL,
    aggregate_class varchar NOT NULL,
    created_at timestamp NOT NULL,
    closed_at timestamp
);
