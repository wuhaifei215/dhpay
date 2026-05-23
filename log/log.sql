/*
 Navicat Premium Dump SQL

 Source Server         : 日志（南）
 Source Server Type    : MySQL
 Source Server Version : 50744 (5.7.44-log)
 Source Host           : 56.124.98.63:3306
 Source Schema         : logmarsdb

 Target Server Type    : MySQL
 Target Server Version : 50744 (5.7.44-log)
 File Encoding         : 65001

 Date: 23/05/2026 15:11:00
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for custom_add_log
-- ----------------------------
DROP TABLE IF EXISTS `custom_add_log`;
CREATE TABLE `custom_add_log`  (
  `id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT '日志主键',
  `title` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '模块标题',
  `method` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '方法名称',
  `user_id` bigint(20) NULL DEFAULT NULL COMMENT '用户id',
  `order_id` varchar(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '订单号',
  `oper_param` varchar(2000) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '请求参数',
  `json_result` varchar(2000) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '返回参数',
  `oper_ip` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '主机地址',
  `create_time` datetime NULL DEFAULT NULL COMMENT '创建时间',
  `business_type` int(11) NULL DEFAULT 0 COMMENT '业务类型（0其它 1下单 2回调）',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '入款日志' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of custom_add_log
-- ----------------------------

-- ----------------------------
-- Table structure for custom_gateway_log
-- ----------------------------
DROP TABLE IF EXISTS `custom_gateway_log`;
CREATE TABLE `custom_gateway_log`  (
  `id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `user_id` bigint(20) NULL DEFAULT NULL COMMENT '用户id',
  `oper_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '请求URL',
  `oper_ip` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '主机地址',
  `method` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '方法名称',
  `request_method` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '请求方式',
  `oper_param` varchar(2000) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '请求参数',
  `json_result` varchar(2000) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '返回参数',
  `error_msg` varchar(2000) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '错误消息',
  `oper_time` datetime NULL DEFAULT NULL COMMENT '操作时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 66 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = 'api网关拦截日志' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of custom_gateway_log
-- ----------------------------

-- ----------------------------
-- Table structure for custom_notify_log
-- ----------------------------
DROP TABLE IF EXISTS `custom_notify_log`;
CREATE TABLE `custom_notify_log`  (
  `id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `user_id` int(20) NULL DEFAULT NULL COMMENT '用户id',
  `order_id` varchar(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '订单号',
  `out_trade_id` varchar(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '下游订单号',
  `type` int(11) NULL DEFAULT 0 COMMENT '业务类型（0入款 1出款）',
  `status` char(1) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '0' COMMENT '操作状态（0正常 1异常）',
  `oper_param` varchar(2000) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '请求参数',
  `json_result` varchar(2000) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '返回参数',
  `create_time` datetime NULL DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 32 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '回调日志' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of custom_notify_log
-- ----------------------------

-- ----------------------------
-- Table structure for custom_payment_log
-- ----------------------------
DROP TABLE IF EXISTS `custom_payment_log`;
CREATE TABLE `custom_payment_log`  (
  `id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT '日志主键',
  `title` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '模块标题',
  `user_id` bigint(20) NULL DEFAULT NULL COMMENT '用户id',
  `order_id` varchar(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '订单号',
  `out_trade_id` varchar(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '下游订单号',
  `business_type` int(11) NULL DEFAULT 0 COMMENT '业务类型（0其它 1下单 2回调）',
  `method` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '方法名称',
  `request_method` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '请求方式',
  `operator_type` int(11) NULL DEFAULT 0 COMMENT '操作类别（0其它 1后台 2Api）',
  `oper_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '请求URL',
  `oper_ip` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '主机地址',
  `oper_param` varchar(2000) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '请求参数',
  `json_result` varchar(2000) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '返回参数',
  `status` int(11) NULL DEFAULT 0 COMMENT '操作状态（0正常 1异常）',
  `error_msg` varchar(2000) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '错误消息',
  `cost_time` bigint(20) NULL DEFAULT 0 COMMENT '消耗时间',
  `create_time` datetime NULL DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '入款日志' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of custom_payment_log
-- ----------------------------

-- ----------------------------
-- Table structure for custom_receipt_log
-- ----------------------------
DROP TABLE IF EXISTS `custom_receipt_log`;
CREATE TABLE `custom_receipt_log`  (
  `id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT '日志主键',
  `title` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '模块标题',
  `user_id` bigint(20) NULL DEFAULT NULL COMMENT '用户id',
  `order_id` varchar(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '订单号',
  `out_trade_id` varchar(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT '下游订单号',
  `business_type` int(11) NULL DEFAULT 0 COMMENT '业务类型（0其它 1下单 2回调）',
  `method` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '方法名称',
  `request_method` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '请求方式',
  `operator_type` int(11) NULL DEFAULT 0 COMMENT '操作类别（0其它 1后台 2Api）',
  `oper_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '请求URL',
  `oper_ip` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '主机地址',
  `oper_param` varchar(2000) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '请求参数',
  `json_result` varchar(2000) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '返回参数',
  `status` int(11) NULL DEFAULT 0 COMMENT '操作状态（0正常 1异常）',
  `error_msg` varchar(2000) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '错误消息',
  `cost_time` bigint(20) NULL DEFAULT 0 COMMENT '消耗时间',
  `create_time` datetime NULL DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 467 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '入款日志' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for pay_admin_log
-- ----------------------------
DROP TABLE IF EXISTS `pay_admin_log`;
CREATE TABLE `pay_admin_log`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `ip` char(30) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '操作者IP地址',
  `node` char(200) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '当前操作节点',
  `user_id` int(11) NOT NULL DEFAULT 0 COMMENT '操作人用户ID',
  `username` varchar(32) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '用户名',
  `action` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '操作行为',
  `type` tinyint(3) NOT NULL DEFAULT 0 COMMENT '操作类型 1：查看 2：新增 3：修改 4：删除 5：导出',
  `content` text CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '操作内容描述',
  `create_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '管理员操作日志表' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of pay_admin_log
-- ----------------------------

-- ----------------------------
-- Table structure for pay_member
-- ----------------------------
DROP TABLE IF EXISTS `pay_member`;
CREATE TABLE `pay_member`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '用户名',
  `password` varchar(32) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '密码',
  `groupid` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户组',
  `salt` varchar(10) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '密码随机字符',
  `parentid` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '代理ID',
  `agent_cate` int(11) NOT NULL DEFAULT 0 COMMENT '代理级别',
  `balance_php` decimal(15, 4) UNSIGNED NOT NULL DEFAULT 0.0000 COMMENT '菲律宾可用余额',
  `blockedbalance_php` decimal(15, 4) UNSIGNED NOT NULL DEFAULT 0.0000 COMMENT '菲律宾冻结可用余额',
  `balance_inr` decimal(20, 4) UNSIGNED NOT NULL DEFAULT 0.0000 COMMENT '越南可用余额',
  `blockedbalance_inr` decimal(20, 4) UNSIGNED NOT NULL DEFAULT 0.0000 COMMENT '越南冻结可用余额',
  `email` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `activate` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `regdatetime` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `activatedatetime` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `realname` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '姓名',
  `sex` tinyint(1) NOT NULL DEFAULT 1 COMMENT '性别',
  `birthday` int(11) NOT NULL DEFAULT 0,
  `sfznumber` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `mobile` varchar(15) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '联系电话',
  `qq` varchar(15) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT 'QQ',
  `address` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '联系地址',
  `paypassword` varchar(32) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '支付密码',
  `authorized` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '1 已认证 0 未认证 2 待审核',
  `apidomain` varchar(500) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '授权访问域名',
  `apikey` varchar(32) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT 'APIKEY',
  `status` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '状态 1激活 0未激活',
  `receiver` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '台卡显示的收款人信息',
  `unit_paying_number` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '单位时间已交易次数',
  `unit_paying_amount` decimal(15, 4) UNSIGNED NOT NULL DEFAULT 0.0000 COMMENT '单位时间已交易金额',
  `unit_frist_paying_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '单位时间已交易的第一笔时间',
  `last_paying_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '当天最后一笔已交易时间',
  `paying_money` decimal(15, 4) UNSIGNED NOT NULL DEFAULT 0.0000 COMMENT '当天已交易金额',
  `login_ip` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '登录IP',
  `last_error_time` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '最后登录错误时间',
  `login_error_num` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '错误登录次数',
  `google_auth` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否开启谷歌身份验证登录',
  `df_api` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否开启代付API',
  `open_charge` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否开启充值功能',
  `df_domain` text CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '代付域名报备',
  `df_auto_check` tinyint(1) NOT NULL DEFAULT 1 COMMENT '代付API自动审核',
  `google_secret_key` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '谷歌密钥',
  `df_ip` text CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '代付域名报备IP',
  `session_random` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT 'session随机字符串',
  `df_charge_type` tinyint(1) NOT NULL DEFAULT 0 COMMENT '代付API扣除手续费方式，0：从到账金额里扣，1：从商户余额里扣',
  `last_login_time` int(11) NOT NULL DEFAULT 0 COMMENT '最后登录时间',
  `pfa_channel` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT ' ' COMMENT '指定代付通道',
  `telegram_id` varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '绑定的telegram群ID',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 20 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of pay_member
-- ----------------------------

SET FOREIGN_KEY_CHECKS = 1;
