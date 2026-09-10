# 策略关联产品改造开发文档

## 1. 文档信息

- 文档类型：开发实施方案
- 适用模块：策略路由、收款 API、代付 API、商户策略配置、批量配置
- 关联约束：[strategy-product-constraints.md](./strategy-product-constraints.md)
- 当前状态：待开发

## 2. 现状与问题

当前策略路由以“商户 + 业务类型”为唯一配置：

```text
pay_member_strategy(userid, strategyid, type, status)
唯一键：userid + type
```

路由服务 `StrategyRouteService` 只按商户和业务类型查询策略，因此同一商户无法按不同产品使用不同策略。

当前 API 中：

- 收款 `pay_bankcode` 已表示产品 ID；
- 代付 `bankcode` 已用于查询商户代付产品 `pid`；
- 策略路由调用点位于 `CreateController` 和 `CreateDFController`。

## 3. 目标架构

```text
请求产品编码
    ↓
产品状态与商户开通校验
    ↓
查询 商户 + 产品 的策略
    ↓
校验策略产品、类型、币种
    ↓
金额档位匹配
    ↓
通道可用性与风控
    ↓
返回实际通道
```

目标唯一关系：

```text
userid + productid → strategyid
```

`type` 仍用于区分代收和代付路由流程，但不参与新产品关联的唯一性判断。

## 4. 数据库实施方案

### 4.1 pay_strategy

新增字段：

```sql
ALTER TABLE pay_strategy
  ADD COLUMN productid INT(11) UNSIGNED NULL COMMENT '产品ID'
  AFTER name,
  ADD KEY idx_product_type_status (productid, type, status);
```

新建策略时 productid 必须非空；历史策略允许为空，仅用于兼容。

### 4.2 pay_member_strategy

新增字段并调整索引：

```sql
ALTER TABLE pay_member_strategy
  ADD COLUMN productid INT(11) UNSIGNED NULL COMMENT '产品ID'
  AFTER userid,
  DROP INDEX uk_userid_type,
  ADD UNIQUE KEY uk_userid_productid (userid, productid);
```

说明：

- MySQL 唯一索引允许多条 `productid IS NULL`，因此历史代收/代付记录可以暂时保留；
- 新增或更新的正式配置必须使用非空 productid；
- 现有历史记录通过 `productid IS NULL` 参与兼容回退，不得用于新配置。

如果实际数据库版本不支持该迁移方式，则将历史记录转存到 `pay_member_strategy_legacy`，正式表只保留非空 productid 记录。

### 4.3 pay_strategy_log

```sql
ALTER TABLE pay_strategy_log
  ADD COLUMN productid INT(11) UNSIGNED NULL COMMENT '产品ID'
  AFTER userid,
  ADD KEY idx_productid (productid);
```

### 4.4 数据迁移顺序

1. 备份 `pay_strategy`、`pay_member_strategy`、`pay_strategy_log`。
2. 新增 productid 字段，先允许 NULL。
3. 保留历史策略和历史商户关联，不强行猜测产品。
4. 上线兼容查询逻辑。
5. 由运营逐个补录商户产品策略。
6. 确认历史数据已迁移后，再禁止新增 NULL productid。
7. 最终视情况清理历史回退逻辑。

## 5. 后端代码改造

### 5.1 StrategyRouteService

文件：

```text
api/Application/Common/Service/StrategyRouteService.class.php
```

将路由入口调整为：

```php
routeChannel(
    $userid,
    $productId,
    $amount,
    $type,
    $outTradeNo = '',
    $currency = ''
)
```

推荐保留旧方法兼容入口，内部统一转到新方法。

策略查询规则：

```text
1. pay_member_strategy.userid = 商户
2. pay_member_strategy.productid = 当前产品
3. pay_member_strategy.status = 1
4. 关联策略 status = 1
5. 策略 productid = 当前产品
```

未命中时：

```text
查询历史 productid IS NULL + userid + type
```

历史回退只能在 Service 内实现。

新增校验方法：

```php
validateProduct($productId, $type, $currency)
validateMerchantProduct($userid, $productId, $type)
getMemberStrategyByProduct($userid, $productId, $type)
```

### 5.2 收款 API

文件：

```text
api/Application/Pay/Controller/CreateController.class.php
```

在 `setChannelApiControl()` 中将当前产品编码 `$this->bankcode` 传入路由服务：

```php
$strategyService->routeChannel(
    $this->memberid,
    $this->bankcode,
    $this->pay_amount,
    1,
    $this->orderid,
    $this->currency
);
```

产品校验仍由 `productIsOpen()` 完成，但策略服务必须再次校验，防止其他调用方绕过控制器。

### 5.3 代付 API

文件：

```text
api/Application/Payment/Controller/CreateDFController.class.php
```

在 `channelIsOpen()` 中传入：

```php
$strategyService->routeChannel(
    $this->userid,
    $this->bankcode,
    $money,
    2,
    $out_trade_no,
    $this->currency
);
```

必须保持以下 ID 语义：

- `bankcode`：代付产品 ID；
- `pay_user_pay_for_another.pid`：商户代付产品 ID；
- `pay_pay_for_another.id`：策略最终返回的实际通道 ID。

## 6. 管理端开发任务

当前工作区未包含管理端策略控制器源码，以下任务需在实际管理端项目落地。

### 6.1 策略列表

增加：

- 产品名称、产品编码；
- 策略名称；
- 业务类型；
- 币种；
- 状态；
- 已关联商户数；
- 更新时间。

支持按产品、类型、币种、状态筛选。

### 6.2 新建策略

页面必须采用向导或分步表单：

```text
选择产品
→ 展示产品币种/类型
→ 填写策略名称
→ 配置金额档位
→ 配置通道权重
→ 保存
```

前端和后端都必须校验产品不能为空、策略产品匹配、币种匹配、类型匹配及档位合法性。

### 6.3 编辑策略

允许编辑：

- 名称；
- 状态；
- 金额档位；
- 通道配置。

禁止直接修改已被商户使用策略的产品 ID。更换产品必须新建策略。

### 6.4 商户策略配置

页面流程：

```text
选择商户
→ 选择产品
→ 加载该产品的策略
→ 选择策略
→ 保存
```

保存采用 Upsert：

```text
存在 userid + productid：更新 strategyid
不存在：新增
```

### 6.5 批量配置

接口请求：

```json
{
  "product_id": 7716,
  "strategy_id": 101,
  "merchant_ids": [2, 3, 4]
}
```

处理步骤：

1. 校验产品和策略的关联关系；
2. 校验策略状态；
3. 去重并校验商户列表；
4. 查询商户是否开通产品；
5. 在事务中逐个 Upsert；
6. 返回成功数量、跳过数量和失败明细；
7. 写入管理员操作日志；
8. 清理相关策略缓存。

默认采用部分成功模式，失败商户不能静默忽略。

## 7. 接口契约

### 7.1 产品列表

```text
GET /strategy/products?type=1
```

返回产品 ID、名称、币种、业务类型和状态。

### 7.2 策略列表

```text
GET /strategy/list?product_id=7716&type=1
```

只返回指定产品下可用策略。

### 7.3 保存商户策略

```text
POST /member-strategy/save
```

请求：

```json
{
  "merchant_id": 2,
  "product_id": 7716,
  "strategy_id": 101
}
```

### 7.4 批量保存商户策略

```text
POST /member-strategy/batch-save
```

请求格式见 6.5，返回：

```json
{
  "success_count": 2,
  "skip_count": 1,
  "fail_count": 1,
  "failures": [
    {
      "merchant_id": 4,
      "reason": "商户未开通该产品"
    }
  ]
}
```

## 8. 缓存和日志

缓存 key 改为：

```text
MemberStrategy_{userid}_{productid}_{type}
```

以下操作必须清理缓存：

- 商户产品策略新增、修改、启停；
- 策略启停；
- 策略档位修改；
- 策略通道修改。

路由日志增加 productid，并建议增加：

```text
strategy_source = product | legacy_fallback | none
```

用于统计正式产品策略命中率和历史回退使用情况。

## 9. 测试计划

### 9.1 单元测试

- 同一商户不同产品命中不同策略；
- 同一商户同一产品重复保存只能更新；
- 不同商户可以复用同一策略；
- 产品、策略、币种、类型不匹配时拒绝；
- 商户未开通产品时拒绝；
- 策略关闭时拒绝；
- 历史 NULL productid 策略可以回退；
- 缓存清理后读取最新配置。

### 9.2 API 集成测试

- 收款按 pay_bankcode 命中产品策略；
- 代付按 bankcode 命中产品策略；
- 不同产品之间不串策略；
- 策略通道不可用时沿用原有风控和兜底；
- 产品关闭时不能下单；
- 路由日志包含产品 ID 和策略 ID。

### 9.3 批量配置测试

- 全部新增；
- 全部更新；
- 新增和更新混合；
- 重复商户 ID；
- 商户未开通产品；
- 产品与策略不匹配；
- 数据库异常回滚；
- 并发提交不产生重复关联。

## 10. 上线与回滚

### 上线

1. 执行数据库备份；
2. 执行字段和索引迁移；
3. 发布 Service 兼容代码；
4. 发布收款和代付 API；
5. 发布管理端；
6. 小范围商户灰度配置；
7. 观察策略日志和 API 错误率；
8. 批量迁移历史商户配置。

### 回滚

- 保留旧字段和旧代码兼容入口；
- API 可通过配置开关关闭产品策略路由；
- 关闭后回到原有商户类型策略或默认通道；
- 数据库不立即删除新增字段和历史数据；
- 回滚后保留失败订单和路由日志供排查。

## 11. 交付物

- 数据库迁移 SQL；
- 策略路由 Service 改造；
- 收款 API 改造；
- 代付 API 改造；
- 管理端策略产品选择；
- 商户单个策略配置；
- 商户批量策略配置；
- 缓存清理逻辑；
- 路由日志字段；
- 单元测试和集成测试；
- 历史数据迁移脚本；
- 上线及回滚操作说明。

## 12. 开发前置确认

1. 代收和代付是否都使用统一产品 ID；当前方案按现有 `pid/bankcode` 语义处理。
2. 管理端项目及策略控制器实际所在仓库。
3. 数据库版本是否支持 NULL productid + 唯一索引迁移方案。
4. 批量配置默认采用部分成功模式。
5. 历史商户策略的产品归属由运营补录，不自动猜测。

