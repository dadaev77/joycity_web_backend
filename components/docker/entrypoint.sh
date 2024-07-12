#!/bin/sh
cron
echo "Dev server stated"

while [ ! -d /app/components/docker ]; do sleep 1; done && crontab /app/components/docker/cron_tasks.txt
