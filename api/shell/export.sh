#!/bin/bash
cd /hx/www/dhpay/open; php cli.php Export
#>> /hx/www/dhpay/open/Data/cli_export.log

#*/10 * * * * /hx/www/dhpay/api/shell/export.sh  在 Crontab 中每10分钟启动一个循环脚本：