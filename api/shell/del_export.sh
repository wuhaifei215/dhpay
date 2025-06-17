#!/bin/bash
find /hx/www/dhpay/open/Uploads/download -type d -mmin +480 -exec rm -rf {} +

#0 * * * * /hx/www/dhpay/api/shell/notifydf.sh  在 Crontab 中每分钟启动一个循环脚本： 0：分钟字段为 0，表示每小时的第 0 分钟（即整点）执行。