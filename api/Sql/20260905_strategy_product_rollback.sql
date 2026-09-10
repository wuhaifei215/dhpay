-- 产品策略代码回滚后的数据库兼容处理。
-- 注意：只有确认每个商户每种类型最多一条记录后，才能恢复旧唯一索引。

SELECT userid, type, COUNT(*) AS row_count
FROM pay_member_strategy
GROUP BY userid, type
HAVING COUNT(*) > 1;

-- 上述查询无结果后再执行：
-- ALTER TABLE pay_member_strategy
--   DROP INDEX uk_userid_productid,
--   ADD UNIQUE KEY uk_userid_type (userid, type);

-- productid 与 strategy_source 字段不删除，确保回滚可恢复且日志可审计。
