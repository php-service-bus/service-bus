CREATE TABLE IF NOT EXISTS event_store_snapshots
(
    id uuid PRIMARY KEY,
    aggregate_id_class varchar NOT NULL,
    aggregate_class varchar NOT NULL,
    version int NOT NULL,
    payload bytea NOT NULL,
    created_at timestamp NOT NULL,
    CONSTRAINT event_store_snapshots_fk FOREIGN KEY (id) REFERENCES event_store_stream (id) ON DELETE CASCADE
);
