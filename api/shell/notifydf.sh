#!/bin/bash
for i in {1..60}; do
  cd /hx/www/dhpay/api; php cli.php NotifyDF >> /hx/www/dhpay/api/Data/cli_notifydf.log 2>&1
  sleep 1
done

#* * * * * /hx/www/dhpay/api/shell/notifydf.sh  在 Crontab 中每分钟启动一个循环脚本：