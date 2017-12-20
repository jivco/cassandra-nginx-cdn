<?php

$csv_file='tv_channels.csv';
$database='dvr';
$app='dvr';
$nssd_log = '/var/log/nss/nssd.log';
$file_cql = '/tmp/genconfig_cql.txt';
$file_nss = '/tmp/genconfig_nss.txt';
$file_watch = '/tmp/genconfig_watch.txt';
$file_check = '/tmp/genconfig_check.txt';
$file_wowza = '/tmp/genconfig_wowza.txt';
$file_wowza_startup = '/tmp/genconfig_wowza_startup.txt';
$file_flussonic = '/tmp/genconfig_flussonic.txt';
$file_udpxy = '/tmp/genconfig_udpxy.txt';
$gpu='1';

const PORT1=30000;
const PORT2=30200;
const OFFSET=60;

//init output files
exec('rm -f '.$file_cql);
exec('rm -f '.$file_nss);
exec('rm -f '.$file_watch);
exec('rm -f '.$file_check);
exec('rm -f '.$file_wowza);
exec('rm -f '.$file_wowza_startup);
exec('rm -f '.$file_flussonic);
exec('rm -f '.$file_udpxy);

//start cycle
$row = 2;
if (($handle = fopen($csv_file, "r")) !== false) {
    $cql_data= "
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
    ";
    $cql_data.= "\n";
    file_put_contents($file_cql, $cql_data, FILE_APPEND);

    $init_data='#!/bin/bash'."\n";
    file_put_contents($file_watch, $init_data, FILE_APPEND);
    file_put_contents($file_check, $init_data, FILE_APPEND);
    file_put_contents($file_wowza, $init_data, FILE_APPEND);
    file_put_contents($file_udpxy, $init_data, FILE_APPEND);

    while (($data = fgetcsv($handle, 1000, ",")) !== false) {
        $num = count($data);
        $row++;

        $tv=$data[2];
        $port=$data[0];

        if (!empty($data[7])) {
            $bitrate1=$data[7];
            $resolution1=$data[5];
        }

        if (!empty($data[8])) {
            $bitrate2=$data[8];
            $resolution2=$data[6];
        }

        if (!empty($data[7])  and $data[9]===$gpu) {
            $cql_data= "
                     CREATE TABLE ".$database.'.'.$app.'_'.$tv.'_'.$bitrate1."_chunk_info (
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
                  ";
            $cql_data.= "\n";
            $cql_data.= "
                    CREATE TABLE ".$database.'.'.$app.'_'.$tv.'_'.$bitrate1."_chunk_content (
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
                  ";
            $cql_data.= "\n";
            $cql_data.= "INSERT INTO dvr_variant_info (app,tv,bitrate,codecs,last_chunk_num,resolution) VALUES ('dvr','".$tv."',".$bitrate1.",'avc1.100.30,mp4a.40.2',1,'".$resolution1."');";
            $cql_data.= "\n";

            $port1=PORT1+$port;
            $nss_data='SERVICE, Name = '.$tv.'-sd1, Class = CLASS_INPUT, Type = INPUT_LIVE, KeepAlive = TRUE, Buffer_size = 2M, SOURCE = "udp://@127.0.0.1:'.$port1.'"'."\n";
            file_put_contents($file_nss, $nss_data, FILE_APPEND);

            $udpxy_data="udpxy -J 239.239.239.239:$port1 -B 2M -p $port1\n";
            file_put_contents($file_udpxy, $udpxy_data, FILE_APPEND);

            if ($data[1]===$app) {
                file_put_contents($file_cql, $cql_data, FILE_APPEND);

                //check ffmpeg
                $suff = $app.'.'.$tv.'.'.$bitrate1.'.'.OFFSET;
                $fifo = '/tmp/ffreport-'.$suff.'.fifo';
                $log = '/tmp/ffreport-'.$suff.'.log';
                $pid = '/var/run/ffmpeg-'.$suff.'.pid';

                $check_data='RESTART=0'."\n";
                $check_data.="cat $log|egrep '(DTS|expired|Bad|5XX|Delay between the first packet and last packet in the muxing queue is|Invalid data)'\n";
                $check_data.='if [ $? -eq 0 ]
                          then
                          RESTART=1
                        fi
                ';
                $check_data.="cat $log|egrep '(\.ts)'\n";
                $check_data.='if [ $? -eq 1 ]
                          then
                          RESTART=1
                        fi
                ';
                $check_data.="cat $nssd_log|grep Timeout|grep \"`date --date='1 minutes ago' +\"%Y/%m/%d %H:%M\"`\"|grep \"'".$tv."-sd1\"\n";
                $check_data.='if [ $? -eq 0 ]
                          then
                          RESTART=1
                        fi
                ';
                $check_data.='if [ "$RESTART" -eq "1" ]
                                          then
                                          kill -9 `cat '.$pid.'`'."\n";
                $check_data.= "mkfifo $fifo\n";
                $check_data.= "((FFREPORT=file=$fifo /root/bin/ffmpeg -re -i http://172.16.21.1/$app/$tv/$bitrate1/".OFFSET.'/chunklist.m3u8 -c copy -f mpegts "udp://@127.0.0.1:'.$port1.'?pkt_size=1316" >/dev/null 2>/dev/null) & echo $! > '.$pid.' &)'."\n";
                $check_data.= 'cat < '.$fifo.' >>'.$log.' &'."\n\n";
                $check_data.='echo "`date` - fmpeg-'.$app.'-'.$tv.'-'.$bitrate1.' restarted">>/tmp/check_ffmpeg.log'."\n";
                $check_data.='fi'."\n";
                $check_data.=">$log\n";
                file_put_contents($file_check, $check_data, FILE_APPEND);

                $watch_data='./watch_playlist_remote_neterra.sh --encoder=http://192.168.8.224/tv/'.$tv.'/'.$bitrate1.'.m3u8 --app='.$app.' --tv='.$tv.' --bitrate='.$bitrate1." &\n";
                file_put_contents($file_watch, $watch_data, FILE_APPEND);
            }
        }

        if (!empty($data[8])  and $data[9]===$gpu) {
            $cql_data = "
                   CREATE TABLE ".$database.'.'.$app.'_'.$tv.'_'.$bitrate2."_chunk_info (
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
                ";
            $cql_data.= "\n";
            $cql_data.= "
                  CREATE TABLE ".$database.'.'.$app.'_'.$tv.'_'.$bitrate2."_chunk_content (
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
                ";
            $cql_data.= "\n";
            $cql_data.= "INSERT INTO dvr_variant_info (app,tv,bitrate,codecs,last_chunk_num,resolution) VALUES ('dvr','".$tv."',".$bitrate2.",'avc1.100.32,mp4a.40.2',1,'".$resolution2."');";
            $cql_data.= "\n";

            $port2=PORT2+$port;
            $nss_data='SERVICE, Name = '.$tv.'-sd2, Class = CLASS_INPUT, Type = INPUT_LIVE, KeepAlive = TRUE, Buffer_size = 2M, SOURCE = "udp://127.0.0.1:'.$port2.'"'."\n";
            file_put_contents($file_nss, $nss_data, FILE_APPEND);

            $udpxy_data="udpxy -J 239.239.239.239:$port2 -B 2M -p $port2\n";
            file_put_contents($file_udpxy, $udpxy_data, FILE_APPEND);

            if ($data[1]===$app) {
                file_put_contents($file_cql, $cql_data, FILE_APPEND);

                //check ffmpeg
                $suff = $app.'.'.$tv.'.'.$bitrate2.'.'.OFFSET;
                $fifo = '/tmp/ffreport-'.$suff.'.fifo';
                $log = '/tmp/ffreport-'.$suff.'.log';
                $pid = '/var/run/ffmpeg-'.$suff.'.pid';

                $check_data='RESTART=0'."\n";
                $check_data.="cat $log|egrep '(DTS|expired|Bad|5XX|Delay between the first packet and last packet in the muxing queue is|Invalid data)'\n";
                $check_data.='if [ $? -eq 0 ]
                          then
                          RESTART=1
                        fi
                ';
                $check_data.="cat $log|egrep '(\.ts)'\n";
                $check_data.='if [ $? -eq 1 ]
                          then
                          RESTART=1
                        fi
                ';
                $check_data.="cat $nssd_log|grep Timeout|grep \"`date --date='1 minutes ago' +\"%Y/%m/%d %H:%M\"`\"|grep \"'".$tv."-sd2\"\n";
                $check_data.='if [ $? -eq 0 ]
                          then
                          RESTART=1
                        fi
                ';
                $check_data.='if [ "$RESTART" -eq "1" ]
                                          then
                                          kill -9 `cat '.$pid.'`'."\n";
                $check_data.= "mkfifo $fifo\n";
                $check_data.= "((FFREPORT=file=$fifo /root/bin/ffmpeg -re -i http://172.16.21.1/$app/$tv/$bitrate2/".OFFSET.'/chunklist.m3u8 -c copy -f mpegts "udp://127.0.0.1:'.$port2.'?pkt_size=1316" >/dev/null 2>/dev/null) & echo $! > '.$pid.' &)'."\n";
                $check_data.= 'cat < '.$fifo.' >>'.$log.' &'."\n\n";
                $check_data.='echo "`date` - fmpeg-'.$app.'-'.$tv.'-'.$bitrate2.' restarted">>/tmp/check_ffmpeg.log'."\n";
                $check_data.='fi'."\n";
                $check_data.=">$log\n";
                file_put_contents($file_check, $check_data, FILE_APPEND);

                $watch_data='./watch_playlist_remote_neterra.sh --encoder=http://192.168.8.224/tv/'.$tv.'/'.$bitrate2.'.m3u8 --app='.$app.' --tv='.$tv.' --bitrate='.$bitrate2." &\n";
                file_put_contents($file_watch, $watch_data, FILE_APPEND);
            }
        }

        if ($data[9]===$gpu) {
            $wowza_data="echo '{\n uri : \"udp://$data[3]\"\n}'>>$data[2].stream\n\n";
            file_put_contents($file_wowza, $wowza_data, FILE_APPEND);
            $wowza_startup_data="<StartupStream>\n<Application>gpu/_definst_</Application>\n<StreamName>$data[2].stream</StreamName>\n<MediaCasterType>rtp</MediaCasterType>\n</StartupStream>\n";
            file_put_contents($file_wowza_startup, $wowza_startup_data, FILE_APPEND);
            $flussonic_data="stream $data[2] {\n url udp://$data[3];\n gop_duration 80;\n transcoder vb=1400k fps=25 vcodec=h264 hw=nvenc profile=high level=4.1 preset=slow x264opts=weightb:bframes=16:keyint=80:min-keyint=80:scenecut=-1 vb=700k  size=360x288 fps=25 vcodec=h264 hw=nvenc profile=high level=4.1 preset=slow x264opts=weightb:bframes=16:keyint=80:min-keyint=80:scenecut=-1 ab=128 ar=48000;\n}\n\n";
            file_put_contents($file_flussonic, $flussonic_data, FILE_APPEND);
        }
    }
    fclose($handle);
}

?>
