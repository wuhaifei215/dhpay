-- ============================================
-- 策略路由功能后台菜单配置
-- 执行时间: 2026-04-10
-- 说明：菜单结构采用简洁的两级结构
-- ============================================

-- 表前缀根据实际情况修改，以下默认使用 pay_

-- ----------------------------
-- 第一步：添加顶级菜单
-- ----------------------------
-- 菜单名称：策略路由
-- 对应控制器：StrategyController
-- ----------------------------
INSERT INTO `pay_auth_rule` (`menu_name`, `title`, `type`, `status`, `pid`, `is_menu`, `sorts`) VALUES
('Strategy/index', '策略路由', 1, 1, 31, 0, 40);

-- 获取刚插入的菜单ID（假设插入成功，返回的主键ID为 N）
-- 请将下面 SQL 中的 {PARENT_ID} 替换为实际插入的ID
SET @parent_id = 31;

-- ----------------------------
-- 第二步：添加子菜单
-- ----------------------------
-- 说明：所有子菜单的 menu_name 格式为 Controller/action
--       通过 URL 参数 ?type=1 或 ?type=2 区分代收和代付
-- ----------------------------

-- 子菜单1：代收策略（type=1）
INSERT INTO `pay_auth_rule` (`menu_name`, `title`, `type`, `status`, `pid`, `is_menu`, `sorts`) VALUES
('Strategy/collection', '入金策略', 1, 1, @parent_id, 1, 3);

-- 子菜单2：代付策略（type=2）
INSERT INTO `pay_auth_rule` (`menu_name`, `title`, `type`, `status`, `pid`, `is_menu`, `sorts`) VALUES
('Strategy/withdrawal', '代付策略', 1, 1, @parent_id, 1, 2);

-- 子菜单3：商户策略分配
INSERT INTO `pay_auth_rule` (`menu_name`, `title`, `type`, `status`, `pid`, `is_menu`, `sorts`) VALUES
('Strategy/assignMember', '商户策略分配', 1, 1, @parent_id, 0, 4);

-- 子菜单4：路由日志
INSERT INTO `pay_auth_rule` (`menu_name`, `title`, `type`, `status`, `pid`, `is_menu`, `sorts`) VALUES
('Strategy/routeLog', '策略路由日志', 1, 1, @parent_id, 1, 1);


-- ============================================
-- 替代方案：如果上面的子菜单ID获取有问题，使用以下完整SQL
-- ============================================
/*

-- 1. 先插入顶级菜单
INSERT INTO `pay_auth_rule` (`menu_name`, `title`, `type`, `status`, `pid`, `is_menu`, `sorts`) VALUES
('Strategy/index', '策略路由', 1, 1, 0, 1, 40);

-- 2. 假设顶级菜单ID是 50（请根据实际情况调整），插入子菜单
INSERT INTO `pay_auth_rule` (`menu_name`, `title`, `type`, `status`, `pid`, `is_menu`, `sorts`) VALUES
('Strategy/collection', '代收策略', 1, 1, 50, 1, 1);

INSERT INTO `pay_auth_rule` (`menu_name`, `title`, `type`, `status`, `pid`, `is_menu`, `sorts`) VALUES
('Strategy/withdrawal', '代付策略', 1, 1, 50, 1, 2);

INSERT INTO `pay_auth_rule` (`menu_name`, `title`, `type`, `status`, `pid`, `is_menu`, `sorts`) VALUES
('Strategy/assignMember', '商户策略分配', 1, 1, 50, 1, 3);

INSERT INTO `pay_auth_rule` (`menu_name`, `title`, `type`, `status`, `pid`, `is_menu`, `sorts`) VALUES
('Strategy/routeLog', '路由日志', 1, 1, 50, 1, 4);

*/