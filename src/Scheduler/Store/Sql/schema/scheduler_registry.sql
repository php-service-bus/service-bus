CREATE TABLE IF NOT EXISTS scheduler_registry
(
    id uuid PRIMARY KEY,
    payload bytea NOT NULL
);