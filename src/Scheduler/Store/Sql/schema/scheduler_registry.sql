CREATE TABLE IF NOT EXISTS scheduler_registry
(
    id uuid PRIMARY KEY,
    processing_date timestamp NOT NULL,
    command bytea NOT NULL
);