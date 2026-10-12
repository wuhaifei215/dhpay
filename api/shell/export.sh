#!/bin/bash
# 计划任务以 www 用户执行；程序内部持有共享锁。
task_root="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/../.." && pwd)" || exit 1
cd -- "$task_root/open" || exit 1
exec php cli.php Export
