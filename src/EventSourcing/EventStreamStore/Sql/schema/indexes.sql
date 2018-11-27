CREATE UNIQUE INDEX IF NOT EXISTS event_store_snapshots_aggregate ON event_store_snapshots (id, aggregate_id_class);
CREATE UNIQUE INDEX IF NOT EXISTS  event_store_stream_identifier ON event_store_stream (id, identifier_class);
CREATE UNIQUE INDEX IF NOT EXISTS  event_store_stream_events_playhead ON event_store_stream_events (stream_id, playhead);
CREATE INDEX IF NOT EXISTS  event_store_stream_events_stream ON event_store_stream_events (id, stream_id);
