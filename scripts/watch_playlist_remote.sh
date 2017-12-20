#!/bin/bash

###########################
# Get last chunk from HLS #
# playlist and send it to #
# a php script for import #
# to Cassandra database   #
#                         #
# ztodorov@neterra.net    #
# v0.1 01.06.2017         #
###########################

# Usage
function usage() {
  echo "Usage: ./watch_playlist_remote.sh --encoder=192.168.0.5 --app=dvr --tv=bnt1 --bitrate=1428000"
  echo "--encoder    >>>>   encoder ip address"
  echo "--app        >>>>   application name"
  echo "--tv         >>>>   tv name"
  echo "--bitrate    >>>>   bitrate"
  exit 1
}


# Get command line parameters
for i in "$@"
do
case $i in

    --encoder=*)
    ENCODER="${i#*=}"
    shift # past argument=value
    ;;
    --app=*)
    APP="${i#*=}"
    shift # past argument=value
    ;;
    --tv=*)
    TV="${i#*=}"
    shift # past argument=value
    ;;
    --bitrate=*)
    BITRATE="${i#*=}"
    shift # past argument=value
    ;;
    --default)
    DEFAULT=YES
    shift # past argument with no value
    ;;
    *)
            # unknown option
    ;;
esac
done

# Check for all needed command line parameters
if [[ -z "${ENCODER}" ]]; then
  echo "ERROR: encoder ip address is not set"
  usage
fi

if [[ -z "${APP}" ]]; then
  echo "ERROR: application name is not set"
  usage
fi

if [[ -z "${TV}" ]]; then
  echo "ERROR: tv name is not set"
  usage
fi

if [[ -z "${BITRATE}" ]]; then
  echo "ERROR: bitrate is not set"
  usage
fi

URL=http://$ENCODER:1935/cdn/ngrp:$TV.stream_all
PLAYLIST_URL=$URL/chunklist_b$BITRATE.m3u8

PLAYLIST_PATH=/tmp
PLAYLIST_SUFFIX=m3u8
PLAYLIST=$PLAYLIST_PATH/$APP.$TV.$BITRATE.$PLAYLIST_SUFFIX
INSERT_CASSANDRA_PHP=/root/cassandra/write_chunks_in_cassandra.php
PHP_BIN=/usr/bin/php
NEW=$APP.$TV.$BITRATE.new.md5
LAST=$APP.$TV.$BITRATE.last.md5

touch $PLAYLIST_PATH/$LAST

while true; do

  curl -s -sH 'Accept-encoding: gzip' --compressed $PLAYLIST_URL|tail -n2>$PLAYLIST
  md5sum $PLAYLIST>$PLAYLIST_PATH/$NEW
  diff $PLAYLIST_PATH/$NEW $PLAYLIST_PATH/$LAST

  if [ $? -eq 1 ]; then
    cp $PLAYLIST_PATH/$NEW $PLAYLIST_PATH/$LAST
    LAST_MEDIA=$(tail -n 2 $PLAYLIST|tr -d "\n")
    LAST_MEDIA_INFO=(${LAST_MEDIA//,/ })
    CHUNK_DURATION=${LAST_MEDIA_INFO[0]#*:}
    CHUNK_FILENAME=${LAST_MEDIA_INFO[1]}
    wget -O $PLAYLIST_PATH/$CHUNK_FILENAME $URL/$CHUNK_FILENAME
    $PHP_BIN $INSERT_CASSANDRA_PHP $APP $TV $BITRATE $PLAYLIST_PATH $CHUNK_FILENAME $CHUNK_DURATION
    rm $PLAYLIST_PATH/$CHUNK_FILENAME
  fi
  sleep 1
done
