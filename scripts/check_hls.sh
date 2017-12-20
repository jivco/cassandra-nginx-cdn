#!/bin/bash
tsdecrypt -I 127.0.0.1:40000 -s 127.0.0.1 -H 0 -J 0 -O file:///dev/null > hlslog.txt 2>&1
