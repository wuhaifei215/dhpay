#!/bin/bash
# 仅清理保留超过 7 天的成品 ZIP，临时分片由 worker 管理。
task_root="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/../.." && pwd)" || exit 1
task_download="$task_root/open/Uploads/download"
[ -d "$task_download" ] || exit 0
find "$task_download" -maxdepth 2 -type f -name '*.zip' -mmin +10080 -delete
