CREATE TABLE IF NOT EXISTS sagas_store (
    id UUID,
    identifier_class VARCHAR NOT NULL,
    saga_class VARCHAR NOT NULL,
    payload BYTEA NOT NULL,
    state_id VARCHAR NOT NULL,
    created_at TIMESTAMP NOT NULL,
    expiration_date TIMESTAMP NOT NULL,
    closed_at TIMESTAMP,
    CONSTRAINT saga_identifier PRIMARY KEY (id, identifier_class)
);
