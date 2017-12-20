#!/bin/bash

while true; do

  ffprobe -timeout 5 -hide_banner -v quiet udp://239.255.100.1:30001 || { echo 'restart ffmpeg'; sleep 3; }

done
