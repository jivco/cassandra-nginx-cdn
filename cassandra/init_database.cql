ALTER KEYSPACE "system_auth" WITH REPLICATION = {'class' : 'NetworkTopologyStrategy', 'dc1' : 1, 'dc2' : 1};
CREATE ROLE root WITH PASSWORD = '12345678' AND SUPERUSER = true AND LOGIN = true;
ALTER ROLE cassandra WITH PASSWORD='87654321' AND SUPERUSER=false;

CREATE ROLE nginx WITH PASSWORD = '123456' AND LOGIN = true;
CREATE ROLE php WITH PASSWORD = '12345' AND LOGIN = true;
CREATE ROLE dvr_readonly WITH PASSWORD = '1234567';
CREATE ROLE dvr_write WITH PASSWORD = '1234567';
GRANT SELECT ON KEYSPACE dvr TO dvr_readonly;
GRANT SELECT ON KEYSPACE dvr TO dvr_write;
GRANT MODIFY ON KEYSPACE dvr TO dvr_write;
GRANT dvr_readonly TO nginx;
GRANT dvr_write TO php;


CREATE KEYSPACE dvr WITH replication = {'class': 'NetworkTopologyStrategy', 'dc1': '1', 'dc2': '1'}  AND durable_writes = false;

CREATE TABLE dvr.dvr_bnt1_778000_chunk_info (
    fake int,
    time_id timeuuid,
    chunk_duration float,
    chunk_name text,
    PRIMARY KEY (fake, time_id)
) WITH CLUSTERING ORDER BY (time_id ASC)
    AND bloom_filter_fp_chance = 0.01
    AND caching = {'keys': 'ALL', 'rows_per_partition': 'NONE'}
    AND comment = ''
    AND compaction = {'class': 'org.apache.cassandra.db.compaction.TimeWindowCompactionStrategy', 'compaction_window_size': '30', 'compaction_window_unit': 'MINUTES', 'max_threshold': '32', 'min_threshold': '4'}
    AND compression = {'enabled': 'false'}
    AND crc_check_chance = 1.0
    AND dclocal_read_repair_chance = 0.1
    AND default_time_to_live = 43200
    AND gc_grace_seconds = 86400
    AND max_index_interval = 2048
    AND memtable_flush_period_in_ms = 0
    AND min_index_interval = 128
    AND read_repair_chance = 0.0
    AND speculative_retry = '99PERCENTILE';

CREATE TABLE dvr.dvr_bnt1_778000_chunk_content (
    chunk_name text PRIMARY KEY,
    chunk_content blob
) WITH bloom_filter_fp_chance = 0.01
    AND caching = {'keys': 'ALL', 'rows_per_partition': 'NONE'}
    AND comment = ''
    AND compaction = {'class': 'org.apache.cassandra.db.compaction.TimeWindowCompactionStrategy', 'compaction_window_size': '30', 'compaction_window_unit': 'MINUTES', 'max_threshold': '32', 'min_threshold': '4'}
    AND compression = {'enabled': 'false'}
    AND crc_check_chance = 1.0
    AND dclocal_read_repair_chance = 0.1
    AND default_time_to_live = 43200
    AND gc_grace_seconds = 60
    AND max_index_interval = 2048
    AND memtable_flush_period_in_ms = 0
    AND min_index_interval = 128
    AND read_repair_chance = 0.0
    AND speculative_retry = '99PERCENTILE';

CREATE TABLE dvr.dvr_bnt1_1428000_chunk_info (
    fake int,
    time_id timeuuid,
    chunk_duration float,
    chunk_name text,
    PRIMARY KEY (fake, time_id)
) WITH CLUSTERING ORDER BY (time_id ASC)
    AND bloom_filter_fp_chance = 0.01
    AND caching = {'keys': 'ALL', 'rows_per_partition': 'NONE'}
    AND comment = ''
    AND compaction = {'class': 'org.apache.cassandra.db.compaction.TimeWindowCompactionStrategy', 'compaction_window_size': '30', 'compaction_window_unit': 'MINUTES', 'max_threshold': '32', 'min_threshold': '4'}
    AND compression = {'enabled': 'false'}
    AND crc_check_chance = 1.0
    AND dclocal_read_repair_chance = 0.1
    AND default_time_to_live = 43200
    AND gc_grace_seconds = 86400
    AND max_index_interval = 2048
    AND memtable_flush_period_in_ms = 0
    AND min_index_interval = 128
    AND read_repair_chance = 0.0
    AND speculative_retry = '99PERCENTILE';

CREATE TABLE dvr.dvr_bnt1_1428000_chunk_content (
    chunk_name text PRIMARY KEY,
    chunk_content blob
) WITH bloom_filter_fp_chance = 0.01
    AND caching = {'keys': 'ALL', 'rows_per_partition': 'NONE'}
    AND comment = ''
    AND compaction = {'class': 'org.apache.cassandra.db.compaction.TimeWindowCompactionStrategy', 'compaction_window_size': '30', 'compaction_window_unit': 'MINUTES', 'max_threshold': '32', 'min_threshold': '4'}
    AND compression = {'enabled': 'false'}
    AND crc_check_chance = 1.0
    AND dclocal_read_repair_chance = 0.1
    AND default_time_to_live = 43200
    AND gc_grace_seconds = 60
    AND max_index_interval = 2048
    AND memtable_flush_period_in_ms = 0
    AND min_index_interval = 128
    AND read_repair_chance = 0.0
    AND speculative_retry = '99PERCENTILE';

CREATE TABLE dvr.dvr_variant_info (
    app text,
    tv text,
    bitrate int,
    codecs text,
    last_chunk_num int,
    resolution text,
    PRIMARY KEY (app, tv, bitrate)
) WITH CLUSTERING ORDER BY (tv ASC, bitrate ASC)
    AND bloom_filter_fp_chance = 0.01
    AND caching = {'keys': 'ALL', 'rows_per_partition': 'NONE'}
    AND comment = ''
    AND compaction = {'class': 'org.apache.cassandra.db.compaction.TimeWindowCompactionStrategy', 'compaction_window_size': '30', 'compaction_window_unit': 'MINUTES', 'max_threshold': '32', 'min_threshold': '4'}
    AND compression = {'enabled': 'false'}
    AND crc_check_chance = 1.0
    AND dclocal_read_repair_chance = 0.1
    AND default_time_to_live = 0
    AND gc_grace_seconds = 86400
    AND max_index_interval = 2048
    AND memtable_flush_period_in_ms = 0
    AND min_index_interval = 128
    AND read_repair_chance = 0.0
    AND speculative_retry = '99PERCENTILE';


INSERT INTO dvr_variant_info (app,tv,bitrate,codecs,last_chunk_num,resolution) VALUES ('dvr','bnt1',1428000,'avc1.100.32,mp4a.40.2',1,'720x572');
INSERT INTO dvr_variant_info (app,tv,bitrate,codecs,last_chunk_num,resolution) VALUES ('dvr','bnt1',778000,'avc1.100.30,mp4a.40.2',1,'640x360');
