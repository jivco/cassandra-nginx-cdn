#!/bin/bash
gst-launch-1.0 -v souphttpsrc location=http://127.0.0.1:8000/stream/1080p.m3u8 ! hlsdemux ! tsdemux name=demux \
demux. ! queue ! aacparse ! mpegtsmux alignment=7 name=mux ! udpsink port=30000 host=239.255.1.2 \
demux. ! queue ! h264parse ! mux.
