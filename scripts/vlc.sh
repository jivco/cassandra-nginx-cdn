#!/bin/bash
cvlc http://172.16.21.1/dvr/bbc_world/1428000/60/chunklist.m3u8 --sout '#duplicate{dst=udp{mux=ts,dst=127.0.0.1:40000}}'
