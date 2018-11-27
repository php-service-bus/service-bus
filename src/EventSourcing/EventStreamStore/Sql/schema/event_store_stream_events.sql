CREATE TABLE IF NOT EXISTS event_store_stream_events
(
    id uuid PRIMARY KEY,
    stream_id uuid,
    playhead int NOT NULL,
    event_class varchar NOT NULL,
    payload bytea NOT NULL,
    occured_at timestamp NOT NULL,
    recorded_at timestamp NOT NULL,
    CONSTRAINT event_store_stream_fk FOREIGN KEY (stream_id) REFERENCES event_store_stream (id) ON DELETE CASCADE
);
