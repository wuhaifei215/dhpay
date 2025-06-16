#!/bin/bash
wget --output-document=/dev/null https://napi.dhpay.vip/Pay_Repost_postUrl.html
wget --output-document=/dev/null https://napi.dhpay.vip/Payment_Repost_postUrl.html

#* * * * * /hx/www/dhpay/api/shell/posturl.sh  在 Crontab 中每分钟启动一个循环脚本：