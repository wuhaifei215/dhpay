-- ============================================
-- 策略路由菜单配置
-- 执行时间: 2026-05-29
-- ============================================

-- 获取父级菜单ID (假设在支付管理下面)
SET @parent_id = (
    SELECT id FROM pay_auth_rule 
    WHERE menu_name = 'Channel/#'
    LIMIT 1
);

-- 如果没有找到父级菜单，使用默认的支付管理菜单ID
-- 请根据实际情况调整父级菜单ID
SET @parent_id = IFNULL(@parent_id, 31);

-- 插入策略路由主菜单
INSERT INTO `pay_auth_rule` (`menu_name`, `title`, `type`, `status`, `pid`, `is_menu`, `sorts`)
VALUES ('Strategy/index', '策略路由', 1, 1, @parent_id, 1, 50);

-- 获取刚刚插入的主菜单ID
SET @strategy_menu_id = LAST_INSERT_ID();

-- 插入子菜单
INSERT INTO `pay_auth_rule` (`menu_name`, `title`, `type`, `status`, `pid`, `is_menu`, `sorts`) VALUES
('Strategy/collection', '代收策略', 1, 1, @strategy_menu_id, 1, 1),
('Strategy/withdrawal', '代付策略', 1, 1, @strategy_menu_id, 1, 2),
('Strategy/memberList', '商户策略管理', 1, 1, @strategy_menu_id, 1, 3),
('Strategy/batchAssign', '批量分配策略', 1, 1, @strategy_menu_id, 0, 4),
('Strategy/routeLog', '策略路由日志', 1, 1, @strategy_menu_id, 1, 5),
('Strategy/clearCache', '清除策略缓存', 1, 1, @strategy_menu_id, 0, 6),
('Strategy/edit', '编辑策略', 1, 1, @strategy_menu_id, 0, 7),
('Strategy/del', '删除策略', 1, 1, @strategy_menu_id, 0, 8),
('Strategy/tierList', '策略档位列表', 1, 1, @strategy_menu_id, 0, 9),
('Strategy/editTier', '编辑策略档位', 1, 1, @strategy_menu_id, 0, 10),
('Strategy/delTier', '删除策略档位', 1, 1, @strategy_menu_id, 0, 11),
('Strategy/assignMember', '商户策略分配', 1, 1, @strategy_menu_id, 0, 12),
('Strategy/saveBatchAssign', '保存批量分配', 1, 1, @strategy_menu_id, 0, 13);

-- ============================================
-- 手动添加菜单的方式（如果上面的SQL不起作用）
-- ============================================
-- 请在后台管理系统的菜单管理中添加以下菜单：
--
-- 父级菜单：
-- 名称：策略路由
-- 控制器/方法：Strategy/index
-- 类型：菜单
--
-- 子菜单：
-- 代收策略    - Strategy/collection
-- 代付策略    - Strategy/withdrawal
-- 商户策略管理 - Strategy/memberList
-- 批量分配策略 - Strategy/batchAssign
-- 策略路由日志 - Strategy/routeLog
-- ============================================
