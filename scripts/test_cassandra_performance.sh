#!/bin/bash

HOST=192.168.7.152
COUNT=1000

DVR_HOURS=4

echo "Start: " `date`


declare -a STREAMS

STREAMS[0]='bbc_world|778000' 
STREAMS[1]='bbc_entertainment|778000' 
STREAMS[2]='mtv_rocks|778000' 
STREAMS[3]='fln_eng|778000' 
STREAMS[4]='bbc_world|1428000' 
STREAMS[5]='bbc_entertainment|1428000' 
STREAMS[6]='mtv_rocks|1428000' 
STREAMS[7]='fln_eng|1428000' 
STREAMS[8]='abmoteurs|3192000' 
STREAMS[9]='abmoteurs|942000' 
STREAMS[10]='abmoteurs|1692000' 

STREAMS_NUM=$((${#STREAMS[@]}-1))

#echo ${STREAMS[*]}

echo "Mean time per transaction(MTPT) | Transactions per second (TPS) | TPS/MTPT | Last transaction time (LTT)"

TIME=0
ERRORS=0

for i in `seq 1 $COUNT`;
do

  STREAMID=`shuf -i 0-$STREAMS_NUM -n 1 -z --random-source=/dev/urandom`

  TV=`echo "${STREAMS[$STREAMID]}"| awk -F '|' '{print $1}'`
  BITRATE=`echo "${STREAMS[$STREAMID]}"| awk -F '|' '{print $2}'`

  CHUNK_DUR=`curl -s http://$HOST/dvr/$TV/$BITRATE/60/playlist.m3u8|tail -n2|grep EXTINF|awk -F ":" '{print $2}'|tr -d ","|tr -d "\n"`
  LAST_CHUNK=`curl -s http://$HOST/dvr/$TV/$BITRATE/60/playlist.m3u8|tail -n1|awk -F "_" '{print $3}'|awk -F "." '{print $1}'|tr -d "\n"`

  FIRST_CHUNK=`echo "$LAST_CHUNK-$DVR_HOURS*3600/$CHUNK_DUR+100"|bc`
  CHUNK=`shuf -i $FIRST_CHUNK-$LAST_CHUNK -n 1 -z --random-source=/dev/urandom`

  #echo $TV
  #echo $BITRATE
  #echo $CHUNK_DUR
  #echo $LAST_CHUNK
  #echo $FIRST_CHUNK

  DATE1=$(($(date +%s%N)/1000000))
  curl -s -I http://$HOST/dvr/$TV/$BITRATE/60/media_b$BITRATE\_$CHUNK.ts|grep HTTP|grep "200 OK" >/dev/null
  
  if [ "$?" -ne "0" ]; then
    ((ERRORS++))
  fi

  DATE2=$(($(date +%s%N)/1000000))
  QRY_TIME=`echo "$DATE2-$DATE1"|bc`
  #echo "TV BBITRATE CHUNK/NO TF: $TV/$BITRATE/$CHUNK/$QRY_TIME"

  TIME=`echo "$TIME + $QRY_TIME"|bc`

  MEAN=`echo "$TIME / $COUNT"|bc`

  if [ "$MEAN" -gt "0" ]; then
    TPS=`echo "1000 / $MEAN"|bc`
    TPSMTPT=`echo "$TPS / $MEAN"|bc -l`
  fi
  
  DONE=`echo "$i*100/$COUNT"|bc`
  echo -en "\rDone: $DONE% | Errors: $ERRORS | MTPT: $MEAN ms | TPS: $TPS | TPS/MTPT=$TPSMTPT | LTT: $QRY_TIME ms   "
  
   
done 

echo ""
echo "End: " `date`
