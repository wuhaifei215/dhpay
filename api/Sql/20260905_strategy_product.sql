-- 策略按产品路由迁移（MySQL 5.7/8.0）
-- 执行前必须备份 pay_strategy、pay_member_strategy、pay_strategy_log。
-- 历史关联保留 productid=NULL，由服务层兼容回退；禁止猜测历史产品归属。

ALTER TABLE `pay_strategy`
  ADD COLUMN `productid` INT(11) UNSIGNED NULL COMMENT '产品ID' AFTER `name`,
  ADD KEY `idx_product_type_status` (`productid`, `type`, `status`);

ALTER TABLE `pay_member_strategy`
  ADD COLUMN `productid` INT(11) UNSIGNED NULL COMMENT '产品ID' AFTER `userid`,
  DROP INDEX `uk_userid_type`,
  ADD UNIQUE KEY `uk_userid_productid` (`userid`, `productid`);

ALTER TABLE `pay_strategy_log`
  ADD COLUMN `productid` INT(11) UNSIGNED NULL COMMENT '产品ID' AFTER `userid`,
  ADD COLUMN `strategy_source` VARCHAR(20) NOT NULL DEFAULT 'none' COMMENT 'product/legacy_fallback/none' AFTER `currency`,
  ADD KEY `idx_productid` (`productid`);

-- 上线后核对：正式产品关联不得为空，且策略、关联的产品必须一致。
SELECT ms.id, ms.userid, ms.productid, ms.strategyid
FROM pay_member_strategy ms
LEFT JOIN pay_strategy s ON s.id = ms.strategyid
WHERE ms.productid IS NOT NULL
  AND (s.id IS NULL OR s.productid <> ms.productid);

-- 回滚只回滚索引约束，新增字段和日志数据应保留以便排查：
-- ALTER TABLE pay_member_strategy DROP INDEX uk_userid_productid, ADD UNIQUE KEY uk_userid_type (userid, type);
