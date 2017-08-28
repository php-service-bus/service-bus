CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

CREATE TABLE IF NOT EXISTS event_store_streams
(
  id        UUID PRIMARY KEY,
  aggregate VARCHAR(150) NOT NULL,
  is_closed BOOLEAN DEFAULT FALSE
);
CREATE UNIQUE INDEX event_streams_identity
  ON event_store_streams (id, aggregate);
COMMENT ON TABLE event_store_streams IS 'Event streams list';

CREATE TABLE IF NOT EXISTS event_store_events
(
  id           UUID PRIMARY KEY,
  stream_id    UUID          NOT NULL,
  aggregate_id VARCHAR(150)  NOT NULL,
  playhead     INT DEFAULT 0 NOT NULL,
  occurred_at  TIMESTAMP     NOT NULL,
  recorded_at  TIMESTAMP     NOT NULL,
  payload      JSONB         NOT NULL,
  CONSTRAINT events_stream_key FOREIGN KEY (stream_id) REFERENCES event_store_streams (id) ON DELETE CASCADE ON UPDATE CASCADE
);
CREATE INDEX event_stream_id
  ON event_store_events (stream_id);
COMMENT ON TABLE event_store_events IS 'Event list';
