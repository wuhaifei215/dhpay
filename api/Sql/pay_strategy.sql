-- ============================================
-- 策略路由功能数据库表
-- 执行时间: 2026-04-10
-- ============================================

-- ----------------------------
-- 1. 策略主表
-- ----------------------------
DROP TABLE IF EXISTS `pay_strategy`;
CREATE TABLE `pay_strategy` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL COMMENT '策略名称',
  `type` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1:代收 2:代付',
  `status` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '状态 0关闭 1开启',
  `createtime` DATETIME DEFAULT NULL,
  `updatetime` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_type_status` (`type`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='策略主表';

-- ----------------------------
-- 2. 策略金额档位表
-- ----------------------------
DROP TABLE IF EXISTS `pay_strategy_tier`;
CREATE TABLE `pay_strategy_tier` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `strategyid` INT(11) UNSIGNED NOT NULL COMMENT '策略ID',
  `tier_name` VARCHAR(50) DEFAULT NULL COMMENT '档位名称，如：小额、大额',
  `tier_min` DECIMAL(12,2) NOT NULL COMMENT '档位最小金额',
  `tier_max` DECIMAL(12,2) NOT NULL COMMENT '档位最大金额',
  `channel_ids` VARCHAR(500) DEFAULT NULL COMMENT '通道ID列表，逗号分隔或JSON权重配置',
  `channel_config` TEXT COMMENT '通道配置JSON，格式: [{"channel_id":1,"weight":10},{"channel_id":2,"weight":5}]',
  `sort` INT(11) DEFAULT 100 COMMENT '排序，数字越小越优先匹配',
  `status` TINYINT(1) DEFAULT 1 COMMENT '档位状态 0关闭 1开启',
  `createtime` DATETIME DEFAULT NULL,
  `updatetime` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_strategyid` (`strategyid`),
  KEY `idx_tier_range` (`tier_min`, `tier_max`),
  KEY `idx_sort` (`sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='策略金额档位表';

-- ----------------------------
-- 3. 商户策略关联表
-- ----------------------------
DROP TABLE IF EXISTS `pay_member_strategy`;
CREATE TABLE `pay_member_strategy` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `userid` INT(11) UNSIGNED NOT NULL COMMENT '商户ID',
  `strategyid` INT(11) UNSIGNED NOT NULL COMMENT '策略ID',
  `type` TINYINT(1) NOT NULL COMMENT '1:代收 2:代付',
  `status` TINYINT(1) DEFAULT 1 COMMENT '是否启用 0不启用 1启用',
  `createtime` DATETIME DEFAULT NULL,
  `updatetime` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_userid_type` (`userid`, `type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='商户策略关联表';

-- ----------------------------
-- 4. 策略通道日志表（可选，用于调试和问题排查）
-- ----------------------------
DROP TABLE IF EXISTS `pay_strategy_log`;
CREATE TABLE `pay_strategy_log` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `userid` INT(11) UNSIGNED DEFAULT NULL COMMENT '商户ID',
  `out_trade_no` varchar(100) NOT NULL COMMENT '外部订单号',
  `type` TINYINT(1) DEFAULT NULL COMMENT '1:代收 2:代付',
  `amount` DECIMAL(12,2) DEFAULT NULL COMMENT '交易金额',
  `strategyid` INT(11) UNSIGNED DEFAULT NULL COMMENT '匹配到的策略ID',
  `tier_id` INT(11) UNSIGNED DEFAULT NULL COMMENT '匹配到的档位ID',
  `channel_id` INT(11) UNSIGNED DEFAULT NULL COMMENT '最终分配的通道ID',
  `match_result` VARCHAR(50) DEFAULT NULL COMMENT '匹配结果：strategy_match/tier_match/channel_found/default_fallback',
  `createtime` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_userid_type` (`userid`, `type`),
  KEY `idx_createtime` (`createtime`),
  KEY `idx_out_trade_no` (`out_trade_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='策略路由日志表';

-- ============================================
-- 预置数据
-- ============================================

-- 代收策略
INSERT INTO `pay_strategy` (`id`, `name`, `type`, `status`, `createtime`) VALUES
(1, '代收策略-默认', 1, 1, NOW());

-- 代收金额档位
INSERT INTO `pay_strategy_tier` (`strategyid`, `tier_name`, `tier_min`, `tier_max`, `sort`, `createtime`) VALUES
(1, '小额档', 5.00, 100.00, 1, NOW()),
(1, '中额档', 101.00, 4999.00, 2, NOW()),
(1, '大额档', 5000.00, 15000.00, 3, NOW()),
(1, '超额档', 15001.00, 50000.00, 4, NOW());

-- 代付策略
INSERT INTO `pay_strategy` (`id`, `name`, `type`, `status`, `createtime`) VALUES
(2, '代付策略-默认', 2, 1, NOW());

-- 代付金额档位
INSERT INTO `pay_strategy_tier` (`strategyid`, `tier_name`, `tier_min`, `tier_max`, `sort`, `createtime`) VALUES
(2, '小额档', 10.00, 100.00, 1, NOW()),
(2, '中额档', 101.00, 4999.00, 2, NOW()),
(2, '大额档', 5000.00, 15000.00, 3, NOW());
