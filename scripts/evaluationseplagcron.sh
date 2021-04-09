
#!/bin/bash

export APP_MODE='development'
#export SESSIONS_SAVE_PATH='tcp://IP:PORT?auth=PASSWORD'

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
CDIR=$( pwd )
cd $DIR

while [ true ]; do
    ./evaluationseplag.sh
    sleep 30
done
