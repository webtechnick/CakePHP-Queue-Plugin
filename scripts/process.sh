#!/bin/bash
DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
cd $DIR/..
Console/cake queue.queue process >> /tmp/queue_process_outpt.log
