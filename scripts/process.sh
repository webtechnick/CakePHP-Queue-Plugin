#!/bin/bash
DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
cd $DIR/..
cake queue.queue process >> /tmp/queue_process_outpt.log
