#!/bin/sh

chown -R server:server /app
echo "chown attachments done"

sleep 5
cron
su -c 'crontab /app/components/docker/cron_tasks.txt' -s /bin/bash server
echo "Cron tasks started"

sleep 5
su -c 'php /app/yii migrate --interactive=0' -s /bin/bash server
echo "Migration done"

su -c 'mkdir /app/entrypoint/api/uploads/chats' -s /bin/bash root
su -c 'chown server:server /app/entrypoint/api/uploads/chats' -s /bin/bash root
echo "Chats directory created"
su -c 'php yii deploy-prod/all' -s /bin/bash server

su -c 'php yii push-queue/listen &' -s /bin/bash server
echo "Start push-queue"
su -c 'php yii queue/listen &' -s /bin/bash server
echo "Start queue"
#su -c 'php yii deploy-prod/all' -s /bin/bash server
