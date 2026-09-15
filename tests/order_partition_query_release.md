# 代收/代付分表在线查询保护

## 目标

避免后台或商户在线列表对大量日分表执行 `COUNT`、汇总及 `UNION ALL`，导致从库 SQL 在 PHP 请求结束后仍长时间运行、复制延迟并连带影响订单处理。

## 强制约束

- 代收系统订单号必须以 `YYYYMMDD` 开头；精确查询仅访问对应的 `pay_orderMMDD`，切分日前访问历史表 `pay_order`。
- 代付系统订单号必须为“大写年份字母 + YYYYMMDD + 流水号”；精确查询仅访问对应的 `pay_wttklistMMDD`，切分日前访问历史表 `pay_wttklist`。
- 非精确在线列表最多查询 7 个自然日，超过范围提示使用异步导出。
- 在线查询使用 MySQL `MAX_EXECUTION_TIME(30000)`；模型将外部传入值限制在 60 秒以内。
- 商户代收查询必须保留 `pay_memberid`，商户代付查询必须保留 `userid`；`dhpay2` 的 `currency` 条件也必须保留。
- 精确订单号场景的页面汇总复用单表结果，不再额外执行跨表统计。
- 模型新增参数均为可选参数，未传参数的支付、回调、CLI、Telegram 和导出调用保持原行为。
- 不修改通用数据库驱动、不关闭全局读写分离、不调整数据库或主从配置。

## 修改范围

四套应用均更新：

- `glht/api/open/user` 的 `OrderModel.class.php`。
- `glht/api/open/user` 的 `WttklistModel.class.php`。
- `glht` 后台的订单、冻结订单和代付在线列表。
- `glht` 与独立 `user` 的商户订单、代付在线列表。

共 14 个业务 PHP 文件，另含本文档和专项测试。

## 行为变化

- 精确系统订单号查询由多表查询变为单表查询。
- 在线日期范围超过 7 天时直接拒绝，不再发送 SQL。
- 在线 SQL 超过 30 秒由 MySQL 主动终止。
- 代付日期范围不再额外生成结束日期后的分表。
- Session 写锁在重查询前释放；关闭后页面使用预先保存的 UID 或 `$this->fans`，不得再次依赖 Session 写入。

## 验证

```bash
php -n tests/order_partition_query_test.php
php -l glht/Application/Common/Model/OrderModel.class.php
php -l glht/Application/Common/Model/WttklistModel.class.php
```

发布前还需检查全部 14 个业务 PHP 文件的语法，并执行 `git diff --check`。

## 发布与回滚

发布顺序：先发布四套应用的两个模型，再发布后台/商户控制器，最后平滑重载 PHP-FPM/OPcache。不能先发布控制器，否则旧模型没有 `findByOrderId()`。

回滚顺序相反：先回滚控制器，再回滚模型。

上线后抽查当天列表、精确代收/代付订单、7 天查询、8 天拒绝、切分日前历史订单、商户隔离、冻结订单，并观察从库慢日志和复制延迟。
