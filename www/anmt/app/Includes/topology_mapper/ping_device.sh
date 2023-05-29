#!/bin/bash

server="$1"

ping -c 1 $server > /dev/null

if [ $? -eq 0 ]
then
    echo "1"
else
    echo "0"
fi
