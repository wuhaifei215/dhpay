#!/bin/bash
for i in {1..60}; do
  cd /www/wwwroot/api && php cli.php NotifyDF
  #>> /www/wwwroot/api/Data/cli_notify.log 2>&1
  sleep 1
done

#* * * * * /hx/www/dhpay/api/shell/notifydf.sh  在 Crontab 中每分钟启动一个循环脚本：