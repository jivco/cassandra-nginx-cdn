#!/bin/bash

TV=btv
PORT=30015
BITRATE=1428000
APP=dvr
OFFSET=60

SUFF=$APP.$TV.$BITRATE.$OFFSET

FIFO=/tmp/ffreport-$SUFF.fifo
LOG=/tmp/ffreport-$SUFF.log
PID=/var/run/ffmpeg-$SUFF.pid

mkfifo $LOG

((FFREPORT=file=$FIFO /root/bin/ffmpeg -re -i http://172.16.21.1/$APP/$TV/$BITRATE/$OFFSET/chunklist.m3u8 -c copy -f mpegts "udp://127.0.0.1:$PORT?pkt_size=1316" >/dev/null 2>/dev/null) & echo $! > $PID &)

cat < $FIFO >>$LOG &
