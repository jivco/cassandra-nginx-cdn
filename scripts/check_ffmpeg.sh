#!/bin/bash

RESTART=0
cat /tmp/ffreport-dvr.bnt1.650000.60.log|egrep '(DTS|expired|Bad|5XX|Delay between the first packet and last packet in the muxing queue is|Invalid data)'
if [ $? -eq 0 ]
then
  RESTART=1
fi

cat /tmp/ffreport-dvr.bnt1.650000.60.log|egrep '(\.ts)'
if [ $? -eq 1 ]
then
  RESTART=1
fi

cat /var/log/nss/nssd.log|grep Timeout|grep "`date --date='1 minutes ago' +"%Y/%m/%d %H:%M"`"|grep "'bnt1-sd1"
if [ $? -eq 0 ]
then
  RESTART=1
fi

if [ "$RESTART" -eq "1" ]
then
  kill -9 `cat /var/run/ffmpeg-dvr.bnt1.650000.60.pid`
  mkfifo /tmp/ffreport-dvr.bnt1.650000.60.fifo
  ((FFREPORT=file=/tmp/ffreport-dvr.bnt1.650000.60.fifo /root/bin/ffmpeg -re -i 'http://172.16.21.1/dvr/bnt1/650000/60/chunklist.m3u8?h=12345678' -c copy -f mpegts "udp://239.239.239.239:30001?pkt_size=1316" >/dev/null 2>/dev/null) & echo $! > /var/run/ffmpeg-dvr.bnt1.650000.60.pid &)
  cat < /tmp/ffreport-dvr.bnt1.650000.60.fifo >>/tmp/ffreport-dvr.bnt1.650000.60.log &
  
  echo "`date` - fmpeg-dvr-bnt1-650000 restarted">>/tmp/check_ffmpeg.log
fi
>/tmp/ffreport-dvr.bnt1.650000.60.log
