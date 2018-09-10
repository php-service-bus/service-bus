CREATE TABLE event_sourcing_indexes
(
    index_tag varchar NOT NULL,
    value_key varchar NOT NULL,
    value_data varchar NOT NULL,
    CONSTRAINT event_sourcing_indexes_pk PRIMARY KEY (index_tag, value_key)
);
