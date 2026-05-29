-- MySQL dump 10.13  Distrib 5.7.44, for Linux (x86_64)
--
-- Host: localhost    Database: marsdb
-- ------------------------------------------------------
-- Server version	5.7.44-log

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `pay_admin`
--

DROP TABLE IF EXISTS `pay_admin`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_admin` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '管理员ID',
  `username` varchar(50) NOT NULL COMMENT '后台用户名',
  `password` varchar(32) NOT NULL COMMENT '后台用户密码',
  `groupid` tinyint(1) unsigned DEFAULT '0' COMMENT '用户组',
  `createtime` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `google_secret_key` varchar(128) NOT NULL DEFAULT '' COMMENT '谷歌令牌密钥',
  `mobile` varchar(255) NOT NULL DEFAULT '' COMMENT '手机号码',
  `session_random` varchar(50) NOT NULL DEFAULT '' COMMENT 'session随机字符串',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=44 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_admin`
--

LOCK TABLES `pay_admin` WRITE;
/*!40000 ALTER TABLE `pay_admin` DISABLE KEYS */;
INSERT INTO `pay_admin` VALUES (43,'admin','4e54f1ae4d53d241cb6a8a7826f5809f',1,1779954797,'','','');
/*!40000 ALTER TABLE `pay_admin` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_admin_log`
--

DROP TABLE IF EXISTS `pay_admin_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_admin_log` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ip` char(30) NOT NULL DEFAULT '' COMMENT '操作者IP地址',
  `node` char(200) NOT NULL DEFAULT '' COMMENT '当前操作节点',
  `user_id` int(11) NOT NULL DEFAULT '0' COMMENT '操作人用户ID',
  `username` varchar(32) NOT NULL DEFAULT '' COMMENT '用户名',
  `action` varchar(200) NOT NULL DEFAULT '' COMMENT '操作行为',
  `type` tinyint(3) NOT NULL DEFAULT '0' COMMENT '操作类型 1：查看 2：新增 3：修改 4：删除 5：导出',
  `content` text NOT NULL COMMENT '操作内容描述',
  `create_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=91 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='管理员操作日志表';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_admin_log`
--

LOCK TABLES `pay_admin_log` WRITE;
/*!40000 ALTER TABLE `pay_admin_log` DISABLE KEYS */;
INSERT INTO `pay_admin_log` VALUES (1,'203.160.68.85','admin/system/savebase',11,'hxaz','更改基本设置',3,'更改基本设置','2026-05-20 16:08:19'),(2,'203.160.68.85','admin/system/savebase',11,'hxaz','更改基本设置',3,'更改基本设置','2026-05-20 16:08:19'),(3,'203.160.68.85','admin/system/savebase',11,'hxaz','更改基本设置成功',3,'更改基本设置成功1','2026-05-20 16:08:19'),(4,'203.160.68.85','admin/admin/addadmin',11,'hxaz','添加管理员',2,'添加管理员','2026-05-20 16:08:52'),(5,'203.160.68.85','admin/admin/addadmin',11,'hxaz','添加管理员成功',2,'添加管理员max成功','2026-05-20 16:08:52'),(6,'203.160.68.85','admin/admin/addadmin',11,'hxaz','添加管理员权限成功',2,'添加管理员max权限成功','2026-05-20 16:08:52'),(7,'131.226.97.163','admin/agency/index',42,'max','查看包网机构列表',1,'查看包网机构列表','2026-05-20 16:14:43'),(8,'131.226.97.163','admin/withdrawal/dfapply',42,'max','访问提现申请页面',1,'表单提交方式','2026-05-20 16:17:45'),(9,'131.226.97.163','admin/withdrawal/dfapply',42,'max','访问提现申请页面',1,'表单提交方式','2026-05-20 16:17:53'),(10,'131.226.97.163','admin/user/index',42,'max','查看用户列表',1,'查看用户列表','2026-05-20 16:58:42'),(11,'131.226.97.163','admin/user/index',42,'max','查看用户列表',1,'查看用户列表','2026-05-20 16:59:15'),(12,'131.226.97.163','admin/user/index',42,'max','查看用户列表',1,'查看用户列表','2026-05-20 16:59:16'),(13,'131.226.97.163','admin/user/index',42,'max','查看用户列表',1,'查看用户列表','2026-05-20 16:59:18'),(14,'203.160.86.56','admin/user/index',11,'hxwx','查看用户列表',1,'查看用户列表','2026-05-23 03:45:50'),(15,'203.160.86.56','admin/channel/saveeditsupplier',11,'hxwx','保存编辑供应商',3,'保存编辑供应商','2026-05-23 03:51:09'),(16,'203.160.86.56','admin/channel/saveeditsupplier',11,'hxwx','保存供应商信息成功',3,'保存供应商（6）信息成功（费率：0.015%）','2026-05-23 03:51:09'),(17,'203.160.86.56','admin/user/index',11,'hxwx','查看用户列表',1,'查看用户列表','2026-05-23 04:08:45'),(18,'203.160.86.56','admin/user/saveuser',11,'hxwx','保存编辑用户级别',3,'保存编辑用户级别','2026-05-23 04:09:02'),(19,'203.160.86.56','admin/user/saveuser',11,'hxwx','编辑用户级别成功',3,'编辑用户（2）级别成功','2026-05-23 04:09:02'),(20,'203.160.86.56','admin/user/index',11,'hxwx','查看用户列表',1,'查看用户列表','2026-05-23 04:09:02'),(21,'203.160.86.56','admin/channel/saveeditsupplier',11,'hxwx','保存编辑供应商',3,'保存编辑供应商','2026-05-23 06:29:34'),(22,'203.160.86.56','admin/channel/saveeditsupplier',11,'hxwx','添加供应商信息成功',3,'添加供应商（7）信息成功（费率：0%）','2026-05-23 06:29:34'),(23,'203.160.86.56','admin/channel/saveeditaccount',11,'hxwx','保存子账户',3,'保存子账户','2026-05-23 06:30:00'),(24,'203.160.86.56','admin/channel/saveeditaccount',11,'hxwx','成功添加子账户',3,'成功添加子账户--9','2026-05-23 06:30:00'),(25,'203.160.86.56','admin/channel/saveeditsupplier',11,'hxwx','保存编辑供应商',3,'保存编辑供应商','2026-05-23 06:35:17'),(26,'203.160.86.56','admin/channel/saveeditsupplier',11,'hxwx','添加供应商信息成功',3,'添加供应商（8）信息成功（费率：0%）','2026-05-23 06:35:17'),(27,'203.160.86.56','admin/channel/saveeditaccount',11,'hxwx','保存子账户',3,'保存子账户','2026-05-23 06:35:55'),(28,'203.160.86.56','admin/channel/saveeditaccount',11,'hxwx','成功添加子账户',3,'成功添加子账户--10','2026-05-23 06:35:55'),(29,'203.160.86.56','admin/channel/editaccountstatus',11,'hxwx','编辑子账户状态',3,'编辑子账户状态','2026-05-23 06:35:57'),(30,'203.160.86.56','admin/channel/editaccountstatus',11,'hxwx','开启子账户',3,'开启子账户','2026-05-23 06:35:57'),(31,'203.160.86.56','admin/channel/editaccountcontrol',11,'hxwx','编辑子账户',3,'编辑子账户','2026-05-23 06:35:59'),(32,'203.160.86.56','admin/channel/saveeditaccount',11,'hxwx','保存子账户',3,'保存子账户','2026-05-23 06:36:15'),(33,'203.160.86.56','admin/channel/saveeditaccount',11,'hxwx','成功保存子账户',3,'成功保存子账户--9','2026-05-23 06:36:15'),(34,'203.160.86.56','admin/user/index',11,'hxwx','查看用户列表',1,'查看用户列表','2026-05-23 07:04:12'),(35,'203.160.86.56','admin/user/saveuserproduct',11,'hxwx','保存编辑用户通道',3,'保存编辑用户通道','2026-05-23 07:04:17'),(36,'203.160.86.56','admin/user/saveuserproduct',11,'hxwx','添加用户通道',2,'添加用户（2）的通道','2026-05-23 07:04:17'),(37,'203.160.86.56','admin/user/index',11,'hxwx','查看用户列表',1,'查看用户列表','2026-05-23 07:04:18'),(38,'203.160.86.56','admin/payforanother/editstatus',11,'hxwx','设置通道(11)关闭',3,'设置通道(11)关闭','2026-05-23 07:04:29'),(39,'203.160.86.56','admin/payforanother/editstatus',11,'hxwx','设置通道(11)成功',3,'设置通道(11)成功','2026-05-23 07:04:29'),(40,'203.160.86.56','admin/payforanother/editstatus',11,'hxwx','设置通道(12)关闭',3,'设置通道(12)关闭','2026-05-23 07:04:30'),(41,'203.160.86.56','admin/payforanother/editstatus',11,'hxwx','设置通道(12)成功',3,'设置通道(12)成功','2026-05-23 07:04:30'),(42,'203.160.86.56','admin/user/index',11,'hxwx','查看用户列表',1,'查看用户列表','2026-05-23 07:04:33'),(43,'203.160.86.56','admin/user/index',11,'hxwx','查看用户列表',1,'查看用户列表','2026-05-23 07:55:37'),(44,'203.160.86.56','admin/user/saveuserproduct',11,'hxwx','保存编辑用户通道',3,'保存编辑用户通道','2026-05-23 07:55:43'),(45,'203.160.86.56','admin/user/saveuserproduct',11,'hxwx','添加用户通道',2,'添加用户（2）的通道','2026-05-23 07:55:43'),(46,'203.160.86.56','admin/user/index',11,'hxwx','查看用户列表',1,'查看用户列表','2026-05-23 07:55:44'),(47,'203.160.86.56','admin/user/index',11,'hxwx','查看用户列表',1,'查看用户列表','2026-05-23 08:08:31'),(48,'203.160.86.56','admin/user/saveuserchannel',11,'hxwx','保存编辑用户代付通道',3,'保存编辑用户代付通道','2026-05-23 08:08:41'),(49,'203.160.86.56','admin/user/saveuserchannel',11,'hxwx','修改用户通道',3,'修改用户（2）的通道','2026-05-23 08:08:41'),(50,'203.160.86.56','admin/user/index',11,'hxwx','查看用户列表',1,'查看用户列表','2026-05-23 08:08:42'),(51,'203.160.86.56','admin/user/index',11,'hxwx','查看用户列表',1,'查看用户列表','2026-05-23 08:09:49'),(52,'203.160.86.56','admin/user/index',11,'hxwx','查看用户列表',1,'查看用户列表','2026-05-23 08:10:24'),(53,'203.160.86.56','admin/user/savewithdrawal',11,'hxwx','保存提现规则',3,'保存提现规则','2026-05-23 08:10:32'),(54,'203.160.86.56','admin/user/savewithdrawal',11,'hxwx','修改提现规则成功',3,'修改用户（2）提现规则成功','2026-05-23 08:10:32'),(55,'203.160.86.56','admin/user/index',11,'hxwx','查看用户列表',1,'查看用户列表','2026-05-23 08:10:33'),(56,'203.160.86.56','admin/user/index',11,'hxwx','查看用户列表',1,'查看用户列表','2026-05-23 08:13:11'),(57,'203.160.86.56','admin/user/index',11,'hxwx','查看用户列表',1,'查看用户列表','2026-05-23 08:19:57'),(58,'203.160.86.56','admin/withdrawal/editwtstatus',11,'hxwx','保存委托提现',3,'保存委托提现','2026-05-23 08:28:24'),(59,'175.176.31.29','admin/user/index',42,'max','查看用户列表',1,'查看用户列表','2026-05-23 17:45:44'),(60,'175.176.31.29','admin/user/saveuser',42,'max','保存编辑用户级别',3,'保存编辑用户级别','2026-05-23 17:46:34'),(61,'175.176.31.29','admin/user/saveuser',42,'max','保存编辑用户级别',3,'保存编辑用户级别','2026-05-23 17:46:37'),(62,'175.176.31.29','admin/user/saveuser',42,'max','保存编辑用户级别',3,'保存编辑用户级别','2026-05-23 17:47:21'),(63,'175.176.31.29','admin/user/saveuser',42,'max','保存编辑用户级别',3,'保存编辑用户级别','2026-05-23 17:47:23'),(64,'175.176.31.29','admin/user/saveuser',42,'max','保存编辑用户级别',3,'保存编辑用户级别','2026-05-23 17:47:32'),(65,'175.176.31.29','admin/user/saveuser',42,'max','创建用户成功',2,'创建用户（3）成功','2026-05-23 17:47:33'),(66,'175.176.31.29','admin/user/index',42,'max','查看用户列表',1,'查看用户列表','2026-05-23 17:47:36'),(67,'175.176.31.29','admin/user/savewithdrawal',42,'max','保存提现规则',3,'保存提现规则','2026-05-23 17:49:00'),(68,'175.176.31.29','admin/user/savewithdrawal',42,'max','保存提现规则',3,'保存提现规则','2026-05-23 17:49:04'),(69,'175.176.31.29','admin/user/savewithdrawal',42,'max','保存提现规则',3,'保存提现规则','2026-05-23 17:49:21'),(70,'175.176.31.29','admin/user/savewithdrawal',42,'max','保存提现规则',3,'保存提现规则','2026-05-23 17:49:48'),(71,'175.176.31.29','admin/user/savewithdrawal',42,'max','修改提现规则成功',3,'修改用户（2）提现规则成功','2026-05-23 17:49:48'),(72,'175.176.31.29','admin/user/index',42,'max','查看用户列表',1,'查看用户列表','2026-05-23 17:49:49'),(73,'175.176.31.29','admin/user/editstatus',42,'max','用户状态切换',3,'用户状态切换','2026-05-23 17:49:57'),(74,'175.176.31.29','admin/user/editstatus',42,'max','用户状态切换',3,'切换(3)用户状态','2026-05-23 17:49:57'),(75,'175.176.31.29','admin/user/index',42,'max','查看用户列表',1,'查看用户列表','2026-05-23 17:49:58'),(76,'175.176.31.29','admin/user/editauthoize',42,'max','保存用户认证',3,'保存用户认证','2026-05-23 17:50:06'),(77,'175.176.31.29','admin/user/editauthoize',42,'max','保存用户认证成功',3,'保存用户(3)认证成功','2026-05-23 17:50:06'),(78,'175.176.31.29','admin/user/index',42,'max','查看用户列表',1,'查看用户列表','2026-05-23 17:50:07'),(79,'175.176.31.29','admin/channel/saveeditsupplier',42,'max','保存编辑供应商',3,'保存编辑供应商','2026-05-23 17:51:33'),(80,'175.176.31.29','admin/channel/saveeditsupplier',42,'max','保存供应商信息成功',3,'保存供应商（8）信息成功（费率：0.008%）','2026-05-23 17:51:33'),(81,'175.176.31.29','admin/channel/saveeditsupplier',42,'max','保存编辑供应商',3,'保存编辑供应商','2026-05-23 17:51:43'),(82,'175.176.31.29','admin/channel/saveeditsupplier',42,'max','保存供应商信息成功',3,'保存供应商（7）信息成功（费率：0.008%）','2026-05-23 17:51:43'),(83,'175.176.31.29','admin/user/index',42,'max','查看用户列表',1,'查看用户列表','2026-05-23 17:54:26'),(84,'175.176.31.29','admin/user/changeuser',42,'max','商户登陆',3,'商户登陆','2026-05-23 17:54:59'),(85,'175.176.31.29','admin/user/changeuser',42,'max','商户登陆',3,'(enzo)商户登陆','2026-05-23 17:54:59'),(86,'175.176.31.29','admin/user/changeuser',42,'max','商户登陆',3,'商户登陆','2026-05-23 17:55:17'),(87,'175.176.31.29','admin/user/changeuser',42,'max','商户登陆',3,'(enzo)商户登陆','2026-05-23 17:55:17'),(88,'203.160.68.186','admin/admin/addadmin',11,'hxwx','添加管理员',2,'添加管理员','2026-05-28 07:53:17'),(89,'203.160.68.186','admin/admin/addadmin',11,'hxwx','添加管理员成功',2,'添加管理员admin成功','2026-05-28 07:53:17'),(90,'203.160.68.186','admin/admin/addadmin',11,'hxwx','添加管理员权限成功',2,'添加管理员admin权限成功','2026-05-28 07:53:17');
/*!40000 ALTER TABLE `pay_admin_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_agency`
--

DROP TABLE IF EXISTS `pay_agency`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_agency` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL COMMENT '用户名',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '状态 1激活 0未激活',
  `currency` varchar(10) NOT NULL COMMENT '国家货币',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='用户所属包网机构';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_agency`
--

LOCK TABLES `pay_agency` WRITE;
/*!40000 ALTER TABLE `pay_agency` DISABLE KEYS */;
INSERT INTO `pay_agency` VALUES (1,'YQ',0,'PKR'),(2,'天成',0,'PHP'),(3,'澎湃',0,'PHP'),(4,'熊猫',0,'PHP'),(5,'WG',0,'PHP'),(6,'黑名单',0,'PHP'),(7,'白名单',0,'PHP');
/*!40000 ALTER TABLE `pay_agency` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_apimoney`
--

DROP TABLE IF EXISTS `pay_apimoney`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_apimoney` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL DEFAULT '0',
  `payapiid` int(11) DEFAULT NULL,
  `money` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `freezemoney` decimal(15,3) NOT NULL DEFAULT '0.000' COMMENT '冻结金额',
  `status` smallint(6) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_apimoney`
--

LOCK TABLES `pay_apimoney` WRITE;
/*!40000 ALTER TABLE `pay_apimoney` DISABLE KEYS */;
/*!40000 ALTER TABLE `pay_apimoney` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_area_city`
--

DROP TABLE IF EXISTS `pay_area_city`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_area_city` (
  `city_id` int(11) NOT NULL AUTO_INCREMENT,
  `province_code` varchar(10) DEFAULT NULL,
  `code` varchar(10) DEFAULT NULL COMMENT '城市编号',
  `name` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`city_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=430 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_area_city`
--

LOCK TABLES `pay_area_city` WRITE;
/*!40000 ALTER TABLE `pay_area_city` DISABLE KEYS */;
INSERT INTO `pay_area_city` VALUES (1,'24','2510','松原市'),(2,'26','2610','哈尔滨市'),(3,'26','2640','齐齐哈尔市'),(4,'26','2660','鸡西市'),(5,'26','2670','鹤岗市'),(6,'26','2680','双鸭山市'),(7,'26','2690','大庆市'),(8,'26','2710','伊春市'),(9,'26','2720','佳木斯市'),(10,'26','2740','七台河市'),(11,'26','2750','牡丹江市'),(12,'26','2760','绥化市'),(13,'26','2780','黑河市'),(14,'26','2790','大兴安岭地区'),(15,'29','2901','松江区'),(16,'29','2902','青浦区'),(17,'29','2903','奉贤区'),(18,'29','2904','浦东新区'),(19,'29','2905','金山区'),(20,'29','2906','闵行区'),(21,'29','2907','黄浦区'),(22,'29','2908','徐汇区'),(23,'29','2909','长宁区'),(24,'29','2911','静安区'),(25,'29','2912','普陀区'),(26,'29','2913','闸北区'),(27,'29','2914','虹口区'),(28,'29','2915','杨浦区'),(29,'29','2916','宝山区'),(30,'29','2917','嘉定区'),(31,'29','2929','崇明县'),(32,'30','3010','南京市'),(33,'30','3020','无锡市'),(34,'30','3030','徐州市'),(35,'30','3040','常州市'),(36,'30','3050','苏州市'),(37,'30','3060','南通市'),(38,'30','3070','连云港市'),(39,'30','3080','淮安市'),(40,'30','3110','盐城市'),(41,'30','3120','扬州市'),(42,'30','3140','镇江市'),(43,'30','3160','泰州市'),(44,'30','3180','宿迁市'),(45,'33','3310','杭州市'),(46,'33','3320','宁波市'),(47,'33','3330','温州市'),(48,'33','3350','嘉兴市'),(49,'33','3360','湖州市'),(50,'33','3370','绍兴市'),(51,'33','3380','金华市'),(52,'33','3410','衢州市'),(53,'33','3420','舟山市'),(54,'33','3430','丽水市'),(55,'33','3450','台州市'),(56,'36','3610','合肥市'),(57,'36','3620','芜湖市'),(58,'36','3630','蚌埠市'),(59,'36','3640','淮南市'),(60,'36','3650','马鞍山市'),(61,'36','3660','淮北市'),(62,'36','3670','铜陵市'),(63,'36','3680','安庆市'),(64,'36','3710','黄山市'),(65,'36','3720','阜阳市'),(66,'36','3740','宿州市'),(67,'36','3750','滁州市'),(68,'36','3760','六安市'),(69,'36','3770','宣城市'),(70,'36','3790','池州市'),(71,'36','3810','亳州市'),(72,'39','3910','福州市'),(73,'39','3930','厦门市'),(74,'39','3940','莆田市'),(75,'39','3950','三明市'),(76,'39','3970','泉州市'),(77,'39','3990','漳州市'),(78,'39','4010','南平市'),(79,'39','4030','宁德市'),(80,'39','4050','龙岩市'),(81,'42','4210','南昌市'),(82,'42','4220','景德镇市'),(83,'42','4230','萍乡市'),(84,'42','4240','九江市'),(85,'42','4260','新余市'),(86,'42','4270','鹰潭市'),(87,'42','4280','赣州市'),(88,'42','4310','宜春市'),(89,'42','4330','上饶市'),(90,'42','4350','吉安市'),(91,'42','4370','抚州市'),(92,'45','4510','济南市'),(93,'45','4520','青岛市'),(94,'45','4530','淄博市'),(95,'45','4540','枣庄市'),(96,'45','4550','东营市'),(97,'45','4560','烟台市'),(98,'45','4580','潍坊市'),(99,'45','4610','济宁市'),(100,'45','4630','泰安市'),(101,'45','4650','威海市'),(102,'45','4660','滨州市'),(103,'45','4680','德州市'),(104,'45','4710','聊城市'),(105,'45','4730','临沂市'),(106,'45','4750','菏泽市'),(107,'45','4770','日照市'),(108,'49','4910','郑州市'),(109,'49','4920','开封市'),(110,'49','4930','洛阳市'),(111,'49','4950','平顶山市'),(112,'49','4960','安阳市'),(113,'49','4970','鹤壁市'),(114,'49','4980','新乡市'),(115,'49','5010','焦作市'),(116,'49','5020','濮阳市'),(117,'49','5030','许昌市'),(118,'49','5040','漯河市'),(119,'49','5050','三门峡市'),(120,'10','1021','东城区'),(121,'10','1022','西城区'),(122,'10','1023','朝阳区'),(123,'10','1024','丰台区'),(124,'10','1025','石景山区'),(125,'10','1026','海淀区'),(126,'10','1027','密云县'),(127,'10','1028','延庆县'),(128,'10','1029','门头沟区'),(129,'10','1031','通州区'),(130,'10','1032','顺义区'),(131,'10','1033','昌平区'),(132,'10','1034','大兴区'),(133,'10','1035','平谷区'),(134,'10','1036','房山区'),(135,'10','1039','怀柔区'),(136,'11','1121','宁河县'),(137,'11','1123','静海县'),(138,'11','1124','北辰区'),(139,'11','1125','蓟县'),(140,'11','1126','宝坻区'),(141,'11','1127','滨海新区'),(142,'11','1128','和平区'),(143,'11','1129','河东区'),(144,'11','1131','河西区'),(145,'11','1132','南开区'),(146,'11','1133','河北区'),(147,'11','1134','红桥区'),(148,'11','1135','东丽区'),(149,'11','1136','西青区'),(150,'11','1137','津南区'),(151,'11','1138','武清区'),(152,'12','1210','石家庄市'),(153,'12','1240','唐山市'),(154,'12','1260','秦皇岛市'),(155,'12','1270','邯郸市'),(156,'12','1310','邢台市'),(157,'12','1340','保定市'),(158,'12','1380','张家口市'),(159,'12','1410','承德市'),(160,'12','1430','沧州市'),(161,'12','1460','廊坊市'),(162,'12','1480','衡水市'),(163,'16','1610','太原市'),(164,'16','1620','大同市'),(165,'16','1650','阳泉市'),(166,'16','1660','长治市'),(167,'16','1680','晋城市'),(168,'16','1690','朔州市'),(169,'16','1710','忻州市'),(170,'16','1730','吕梁市'),(171,'16','1750','晋中市'),(172,'16','1770','临汾市'),(173,'16','1810','运城市'),(174,'19','1910','呼和浩特市'),(175,'19','1920','包头市'),(176,'19','1930','乌海市'),(177,'19','1940','赤峰市'),(178,'19','1960','呼伦贝尔市'),(179,'19','1980','兴安盟'),(180,'19','1990','通辽市'),(181,'19','2010','锡林郭勒盟'),(182,'19','2030','乌兰察布市'),(183,'19','2050','鄂尔多斯市'),(184,'19','2070','巴彦淖尔市'),(185,'19','2080','阿拉善盟'),(186,'22','2210','沈阳市'),(187,'22','2220','大连市'),(188,'22','2230','鞍山市'),(189,'22','2240','抚顺市'),(190,'22','2250','本溪市'),(191,'22','2260','丹东市'),(192,'22','2270','锦州市'),(193,'22','2280','营口市'),(194,'22','2290','阜新市'),(195,'22','2310','辽阳市'),(196,'22','2320','盘锦市'),(197,'22','2330','铁岭市'),(198,'22','2340','朝阳市'),(199,'22','2360','葫芦岛市'),(200,'24','2410','长春市'),(201,'24','2420','吉林市'),(202,'24','2430','四平市'),(203,'24','2440','辽源市'),(204,'24','2450','通化市'),(205,'24','2460','白山市'),(206,'24','2470','白城市'),(207,'24','2490','延边朝鲜族自治州'),(208,'49','5060','商丘市'),(209,'49','5080','周口市'),(210,'49','5110','驻马店市'),(211,'49','5130','南阳市'),(212,'49','5150','信阳市'),(213,'52','5210','武汉市'),(214,'52','5220','黄石市'),(215,'52','5230','十堰市'),(216,'52','5250','宜昌市'),(217,'52','5270','随州市'),(218,'52','5280','襄阳市'),(219,'52','5310','鄂州市'),(220,'52','5320','荆门市'),(221,'52','5330','黄冈市'),(222,'52','5350','孝感市'),(223,'52','5360','咸宁市'),(224,'52','5370','荆州市'),(225,'52','5410','恩施土家族苗族自治州'),(226,'55','5510','长沙市'),(227,'55','5520','株洲市'),(228,'55','5530','湘潭市'),(229,'55','5540','衡阳市'),(230,'55','5550','邵阳市'),(231,'55','5570','岳阳市'),(232,'55','5580','常德市'),(233,'55','5590','张家界市'),(234,'55','5610','益阳市'),(235,'55','5620','娄底市'),(236,'55','5630','郴州市'),(237,'55','5650','永州市'),(238,'55','5670','怀化市'),(239,'55','5690','湘西土家族苗族自治州'),(240,'58','5810','广州市'),(241,'58','5820','韶关市'),(242,'58','5840','深圳市'),(243,'58','5850','珠海市'),(244,'58','5860','汕头市'),(245,'58','5880','佛山市'),(246,'58','5890','江门市'),(247,'58','5910','湛江市'),(248,'58','5920','茂名市'),(249,'58','5950','惠州市'),(250,'58','5960','梅州市'),(251,'58','5970','汕尾市'),(252,'58','5980','河源市'),(253,'58','5990','阳江市'),(254,'58','6010','清远市'),(255,'58','6020','东莞市'),(256,'58','6030','中山市'),(257,'58','6040','潮州市'),(258,'58','6050','揭阳市'),(259,'58','6060','云浮市'),(260,'61','6110','南宁市'),(261,'61','6140','柳州市'),(262,'61','6170','桂林市'),(263,'61','6210','梧州市'),(264,'61','6230','北海市'),(265,'61','6240','玉林市'),(266,'61','6260','百色市'),(267,'61','6280','河池市'),(268,'61','6310','钦州市'),(269,'61','6320','防城港市'),(270,'61','6330','贵港市'),(271,'61','6340','贺州市'),(272,'61','6350','来宾市'),(273,'61','6360','崇左市'),(274,'64','6410','海口市'),(275,'64','6420','三亚市'),(276,'64','6440','三沙市'),(277,'65','6510','成都市'),(278,'65','6550','自贡市'),(279,'65','6560','攀枝花市'),(280,'65','6570','泸州市'),(281,'65','6580','德阳市'),(282,'65','6590','绵阳市'),(283,'65','6610','广元市'),(284,'65','6620','遂宁市'),(285,'65','6630','内江市'),(286,'65','6650','乐山市'),(287,'65','6710','宜宾市'),(288,'65','6730','南充市'),(289,'65','6750','达州市'),(290,'65','6770','雅安市'),(291,'65','6790','阿坝藏族羌族自治州'),(292,'65','6810','甘孜藏族自治州'),(293,'65','6840','凉山彝族自治州'),(294,'65','6670','眉山市'),(295,'65','6690','广安市'),(296,'65','6870','巴中市'),(297,'65','6880','资阳市'),(298,'69','6901','大足区'),(299,'69','6902','合川区'),(300,'69','6903','万州区'),(301,'69','6904','涪陵区'),(302,'69','6905','渝中区'),(303,'69','6906','九龙坡区'),(304,'69','6907','南岸区'),(305,'69','6908','北碚区'),(306,'69','6909','綦江区'),(307,'69','6911','渝北区'),(308,'69','6912','巴南区'),(309,'69','6913','黔江区'),(310,'69','6914','潼南区'),(311,'69','6915','铜梁区'),(312,'69','6916','长寿区'),(313,'69','6917','江津区'),(314,'69','6918','荣昌区'),(315,'69','6919','璧山区'),(316,'69','6921','梁平县'),(317,'69','6922','城口县'),(318,'69','6923','丰都县'),(319,'69','6924','垫江县'),(320,'69','6925','武隆县'),(321,'69','6926','忠县'),(322,'69','6927','开县'),(323,'69','6928','云阳县'),(324,'69','6929','奉节县'),(325,'69','6931','巫山县'),(326,'69','6932','巫溪县'),(327,'69','6933','石柱土家族自治县'),(328,'69','6934','秀山土家族苗族自治县'),(329,'69','6935','酉阳土家族苗族自治县'),(330,'69','6936','彭水苗族土家族自治县'),(331,'69','6937','沙坪坝区'),(332,'69','6938','江北区'),(333,'69','6939','大渡口区'),(334,'69','6941','永川区'),(335,'69','6942','南川区'),(336,'70','7004','贵安新区'),(337,'70','7010','贵阳市'),(338,'70','7020','六盘水市'),(339,'70','7030','遵义市'),(340,'70','7050','铜仁市'),(341,'70','7070','黔西南布依族苗族自治州'),(342,'70','7090','毕节市'),(343,'70','7110','安顺市'),(344,'70','7130','黔东南苗族侗族自治州'),(345,'70','7150','黔南布依族苗族自治州'),(346,'73','7310','昆明市'),(347,'73','7340','昭通市'),(348,'73','7360','曲靖市'),(349,'73','7380','楚雄彝族自治州'),(350,'73','7410','玉溪市'),(351,'73','7430','红河哈尼族彝族自治州'),(352,'73','7450','文山壮族苗族自治州'),(353,'73','7470','普洱市'),(354,'73','7490','西双版纳傣族自治州'),(355,'73','7510','大理白族自治州'),(356,'73','7530','保山市'),(357,'73','7540','德宏傣族景颇族自治州'),(358,'73','7550','丽江市'),(359,'73','7560','怒江傈僳族自治州'),(360,'73','7570','迪庆藏族自治州'),(361,'73','7580','临沧市'),(362,'77','7710','拉萨市'),(363,'77','7720','昌都市'),(364,'77','7740','山南市'),(365,'77','7760','日喀则市'),(366,'77','7790','那曲地区'),(367,'77','7810','阿里地区'),(368,'77','7830','林芝市'),(369,'79','7904','西咸新区'),(370,'79','7910','西安市'),(371,'79','7920','铜川市'),(372,'79','7930','宝鸡市'),(373,'79','7950','咸阳市'),(374,'79','7970','渭南市'),(375,'79','7990','汉中市'),(376,'79','8010','安康市'),(377,'79','8030','商洛市'),(378,'79','8040','延安市'),(379,'79','8060','榆林市'),(380,'82','8210','兰州市'),(381,'82','8220','嘉峪关市'),(382,'82','8230','金昌市'),(383,'82','8240','白银市'),(384,'82','8250','天水市'),(385,'82','8260','酒泉市'),(386,'82','8270','张掖市'),(387,'82','8280','武威市'),(388,'82','8290','定西市'),(389,'82','8310','陇南市'),(390,'82','8330','平凉市'),(391,'82','8340','庆阳市'),(392,'82','8360','临夏回族自治州'),(393,'82','8380','甘南藏族自治州'),(394,'85','8510','西宁市'),(395,'85','8520','海东市'),(396,'85','8540','海北藏族自治州'),(397,'85','8550','黄南藏族自治州'),(398,'85','8560','海南藏族自治州'),(399,'85','8570','果洛藏族自治州'),(400,'85','8580','玉树藏族自治州'),(401,'85','8590','海西蒙古族藏族自治州'),(402,'87','8710','银川市'),(403,'87','8720','石嘴山市'),(404,'87','8730','吴忠市'),(405,'87','8740','固原市'),(406,'87','8750','中卫市'),(407,'88','8810','乌鲁木齐市'),(408,'88','8820','克拉玛依市'),(409,'88','8830','吐鲁番市'),(410,'88','8840','哈密市'),(411,'88','8850','昌吉回族自治州'),(412,'88','8870','博尔塔拉蒙古自治州'),(413,'88','8880','巴音郭楞蒙古自治州'),(414,'88','8910','阿克苏地区'),(415,'88','8930','克孜勒苏柯尔克孜自治州'),(416,'88','8940','喀什地区'),(417,'88','8960','和田地区'),(418,'88','8980','伊犁哈萨克自治州'),(419,'88','9010','塔城地区'),(420,'88','9020','阿勒泰地区'),(421,'88','9028','石河子市（自治区直辖）'),(422,'88','9041','阿拉尔市（自治区直辖）'),(423,'88','9042','图木舒克市（自治区直辖）'),(424,'88','9043','五家渠市（自治区直辖）'),(425,'88','9044','北屯市（自治区直辖）'),(426,'88','9045','双河市（自治区直辖）'),(427,'88','9046','可克达拉市(自治区直辖)'),(428,'88','8890','铁门关市（自治区直辖）'),(429,'88','9047','昆玉市(自治区直辖)');
/*!40000 ALTER TABLE `pay_area_city` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_area_province`
--

DROP TABLE IF EXISTS `pay_area_province`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_area_province` (
  `province_id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(10) DEFAULT NULL,
  `name` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`province_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_area_province`
--

LOCK TABLES `pay_area_province` WRITE;
/*!40000 ALTER TABLE `pay_area_province` DISABLE KEYS */;
INSERT INTO `pay_area_province` VALUES (2,'26','黑龙江省'),(3,'29','上海市'),(4,'30','江苏省'),(5,'33','浙江省'),(6,'36','安徽省'),(7,'39','福建省'),(8,'42','江西省'),(9,'45','山东省'),(10,'49','河南省'),(11,'10','北京市'),(12,'11','天津市'),(13,'12','河北省'),(14,'16','山西省'),(15,'19','内蒙古自治区'),(16,'22','辽宁省'),(17,'24','吉林省'),(18,'52','湖北省'),(19,'55','湖南省'),(20,'58','广东省'),(21,'61','广西壮族自治区'),(22,'64','海南省'),(23,'65','四川省'),(24,'69','重庆市'),(25,'70','贵州省'),(26,'73','云南省'),(27,'77','自治区'),(28,'79','陕西省'),(29,'82','甘肃省'),(30,'85','青海省'),(31,'87','宁夏回族自治区'),(32,'88','新疆维吾尔族自治区');
/*!40000 ALTER TABLE `pay_area_province` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_article`
--

DROP TABLE IF EXISTS `pay_article`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_article` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `catid` smallint(6) unsigned NOT NULL DEFAULT '0' COMMENT '分类ID',
  `groupid` tinyint(1) NOT NULL DEFAULT '0' COMMENT '分组  0：所有 1：商户 2：代理',
  `title` varchar(300) NOT NULL COMMENT '标题',
  `content` text COMMENT '内容',
  `createtime` int(11) unsigned NOT NULL DEFAULT '0',
  `description` varchar(255) NOT NULL COMMENT '描述',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '1显示 0 不显示',
  `updatetime` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_article`
--

LOCK TABLES `pay_article` WRITE;
/*!40000 ALTER TABLE `pay_article` DISABLE KEYS */;
INSERT INTO `pay_article` VALUES (1,1,0,'Welcome to Mars system !','',1503808262,'Welcome to Mars system !',1,1637135077);
/*!40000 ALTER TABLE `pay_article` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_attachment`
--

DROP TABLE IF EXISTS `pay_attachment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_attachment` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `userid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '商户编号',
  `filename` varchar(100) NOT NULL,
  `path` varchar(255) NOT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=49 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_attachment`
--

LOCK TABLES `pay_attachment` WRITE;
/*!40000 ALTER TABLE `pay_attachment` DISABLE KEYS */;
INSERT INTO `pay_attachment` VALUES (46,2,'6-140F316125V44.jpg','Uploads/verifyinfo/59a2b65cd9877.jpg'),(47,2,'6-140F316132J02.jpg','Uploads/verifyinfo/59a2b65cea2ec.jpg'),(48,2,'242dd42a2834349b88359f1eccea15ce36d3be7e.jpg','Uploads/verifyinfo/59a2b65d0816c.jpg');
/*!40000 ALTER TABLE `pay_attachment` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_auth_error_log`
--

DROP TABLE IF EXISTS `pay_auth_error_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_auth_error_log` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `auth_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0：商户登录 1：后台登录 2：商户短信验证 3：后台短信验证 4：谷歌令牌验证 5：支付密码验证 ',
  `uid` int(11) NOT NULL DEFAULT '0' COMMENT '用户ID',
  `ctime` int(11) NOT NULL DEFAULT '0' COMMENT '时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_auth_error_log`
--

LOCK TABLES `pay_auth_error_log` WRITE;
/*!40000 ALTER TABLE `pay_auth_error_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `pay_auth_error_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_auth_group`
--

DROP TABLE IF EXISTS `pay_auth_group`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_auth_group` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `title` char(100) NOT NULL DEFAULT '',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `is_manager` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '1需要验证权限 0 不需要验证权限',
  `rules` varchar(500) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_auth_group`
--

LOCK TABLES `pay_auth_group` WRITE;
/*!40000 ALTER TABLE `pay_auth_group` DISABLE KEYS */;
INSERT INTO `pay_auth_group` VALUES (1,'超级管理员',1,0,'1,2,3,51,54,126,7,8,60,61,62,9,63,64,65,66,10,67,68,69,138,11,131,12,79,80,81,82,83,84,85,86,87,88,89,90,91,93,94,95,96,97,98,99,100,101,120,15,92,17,132,18,19,71,75,20,72,74,180,181,183,22,127,130,157,159,125,141,142,143,144,27,30,103,106,107,119,148,171,158,169,206,170,28,108,129,104,105,109,110,111,128,31,33,32,34,205,202,203,204,38,23,114,115,42,172,161,182,184,163,164'),(2,'管理员',1,0,'1,11,131,12,79,80,81,82,83,84,85,86,87,88,89,90,91,93,94,95,96,97,98,99,100,101,120,149,150,155,156,173,174,175,178,179,185,186,187,188,189,190,191,192,193,194,199,15,92,121,17,132,18,19,71,75,20,72,74,180,181,195,196,183,197,22,127,176,157,159,125,141,142,143,144,27,30,103,106,107,119,148,171,177,158,169,206,170,28,108,129,104,105,109,110,111,128,31,33,32,145,153,154,34,146,147,151,152,38,23,114,115,42,172,161,182,163,164,198'),(3,'财务管理员',2,1,'1,11,131,12,79,80,81,82,83,84,85,86,87,88,89,90,91,93,94,95,96,97,98,99,100,101,120,13,14,15,92,16,73,76,77,78,121,46,17,132,18,19,71,20,72,74,22,127,24,25,26,130,125,141,142,143,144,157,27,28,108,129,29,102,30,103,106,107,119,21,104,105,109,110,111,128,140,158,35,38,133,116,42,23,114'),(4,'普通商户',2,1,'22,127,24,25,26,130,125,141,142,143,144,157,27,28,108,129,29,102,30,103,106,107,119,148,21,104,105,109,110,111,128,140,158,133,116,122,23,114,115,134,136'),(5,'客服',1,1,'1,22,157,27,158'),(6,'商户',1,1,''),(7,'代理',2,1,'18,19,71,75,20,72,74');
/*!40000 ALTER TABLE `pay_auth_group` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_auth_group_access`
--

DROP TABLE IF EXISTS `pay_auth_group_access`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_auth_group_access` (
  `uid` mediumint(8) unsigned NOT NULL,
  `group_id` mediumint(8) unsigned NOT NULL,
  UNIQUE KEY `uid_group_id` (`uid`,`group_id`) USING BTREE,
  KEY `uid` (`uid`) USING BTREE,
  KEY `group_id` (`group_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_auth_group_access`
--

LOCK TABLES `pay_auth_group_access` WRITE;
/*!40000 ALTER TABLE `pay_auth_group_access` DISABLE KEYS */;
INSERT INTO `pay_auth_group_access` VALUES (9,1),(11,1),(12,1),(13,5),(14,1),(19,5),(20,5),(21,5),(22,1),(23,1),(24,1),(26,2),(28,1),(31,1),(32,1),(34,2),(39,2),(40,2),(41,2),(42,1),(43,1);
/*!40000 ALTER TABLE `pay_auth_group_access` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_auth_rule`
--

DROP TABLE IF EXISTS `pay_auth_rule`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_auth_rule` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `icon` varchar(100) DEFAULT '' COMMENT '图标',
  `menu_name` varchar(100) NOT NULL DEFAULT '' COMMENT '规则唯一标识Controller/action',
  `title` varchar(100) NOT NULL DEFAULT '' COMMENT '菜单名称',
  `pid` mediumint(8) NOT NULL DEFAULT '0' COMMENT '菜单ID ',
  `is_menu` tinyint(1) unsigned DEFAULT '0' COMMENT '1:是主菜单 0否',
  `is_race_menu` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '1:是 0:不是',
  `type` tinyint(1) NOT NULL DEFAULT '1',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `condition` char(100) NOT NULL DEFAULT '',
  `sorts` int(10) NOT NULL DEFAULT '0' COMMENT '排序',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=207 DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_auth_rule`
--

LOCK TABLES `pay_auth_rule` WRITE;
/*!40000 ALTER TABLE `pay_auth_rule` DISABLE KEYS */;
INSERT INTO `pay_auth_rule` VALUES (1,'fa fa-home','Index/main','管理首页',0,1,0,1,1,'',55),(2,'fa fa-cogs','System/#','系统设置',0,1,0,1,1,'',50),(3,'fa fa-cog','System/base','基本设置',2,1,0,1,1,'',0),(4,'fa fa-envelope-o','System/email','邮件设置',2,1,0,1,1,'',0),(5,'fa fa-send','System/smssz','短信设置',2,1,0,1,1,'',0),(6,'fa fa-hourglass','System/planning','计划任务',2,1,0,1,1,'',0),(7,'fa fa-user-circle','Admin/#','管理员管理',0,1,0,1,1,'',45),(8,'fa fa-vcard ','Admin/index','管理员信息',7,1,0,1,1,'',0),(9,'fa fa-street-view','Auth/index','角色配置',7,1,0,1,1,'',0),(10,'fa fa-universal-access','Menu/index','权限配置',7,1,0,1,1,'',0),(11,'fa fa-users','User/#','用户管理',0,1,0,1,1,'',40),(12,'fa fa-user','User/index?status=1&authorized=1','已认证用户',11,1,0,1,1,'',90),(13,'fa fa-user-o','User/index?status=1&authorized=2','待审核认证用户',11,1,0,1,1,'',80),(14,'fa fa-user-plus','User/index?status=1&authorized=0','未认证用户',11,1,0,1,1,'',70),(15,'fa fa-user-times','User/index?status=0','冻结用户',11,1,0,1,1,'',60),(16,'fa fa-gift','User/invitecode','邀请码',11,1,0,1,1,'',50),(17,'fa fa-address-book','User/loginrecord','登录记录',11,1,0,1,1,'',10),(18,'fa fa-sitemap','Agent/#','代理管理',0,1,0,1,1,'',35),(19,'fa fa-hand-lizard-o','User/agentList','代理列表',18,1,0,1,1,'',0),(20,'fa fa-signing','Order/changeRecord?bank=9','佣金记录',18,1,0,1,1,'',0),(21,'fa fa-sellsy','Order/dfApiOrderList','商户代付审核',27,1,0,1,1,'',0),(22,'fa fa-reorder','User/#','代收管理',0,1,0,1,1,'',30),(23,'fa fa-indent','Order/changeRecord','流水记录',38,1,0,1,1,'',60),(24,'fa fa-thumbs-up','Order/index?status=1or2','成功订单',22,1,0,1,1,'',90),(25,'fa fa-thumbs-down','Order/index?status=0','未支付订单',22,1,0,1,1,'',80),(26,'fa fa-hand-o-right','Order/index?status=1','异常订单',22,1,0,1,1,'',70),(27,'fa fa-user-secret','Withdrawal','代付管理',0,1,0,1,1,'',25),(28,'fa fa-wrench','Withdrawal/setting','代付设置',27,1,0,1,1,'',5),(29,'fa fa-asl-interpreting','Withdrawal/index','手动结算',27,1,0,1,1,'',0),(30,'fa fa-window-restore','Withdrawal/payment','代付订单',27,1,0,1,1,'',50),(31,'fa fa-bank','Channel/#','通道管理',0,1,0,1,1,'',20),(32,'fa fa-product-hunt','Channel/index','入金渠道设置',31,1,0,1,1,'',0),(33,'fa fa-sitemap','Channel/product','支付产品设置',31,1,0,1,1,'',10),(34,'fa fa-sliders','PayForAnother/index','代付渠道设置',31,1,0,1,1,'',0),(35,'fa fa-book','Content/#','文章管理',0,1,0,1,1,'',15),(36,'fa fa-tags','Content/category','栏目列表',35,1,0,1,1,'',0),(37,'fa fa-list-alt','Content/article','文章列表',35,1,0,1,1,'',0),(38,'fa fa-line-chart','Statistics/#','财务分析',0,1,0,1,1,'',5),(39,'fa fa-bar-chart-o','Statistics/index','交易统计',38,1,0,1,0,'',0),(40,'fa fa-area-chart','Statistics/userFinance','商户交易统计',38,1,0,1,0,'',0),(41,'fa fa-industry','Statistics/userFinance?groupid=agent','代理商交易统计',38,1,0,1,0,'',0),(42,'fa fa-pie-chart','Statistics/productChannelFinance','接口交易统计',38,1,0,1,1,'',55),(43,'fa fa-cubes','Template/index','模板设置',2,1,0,1,0,'',0),(44,'fa fa-mobile','System/mobile','手机设置',2,1,0,1,1,'',0),(45,'fa fa-signal','Statistics/chargeRank','充值排行榜',38,1,0,1,0,'',0),(46,'fa fa-first-order','Deposit/index','投诉保证金设置',11,1,0,1,1,'',20),(47,'fa fa-asterisk','Statistics/complaintsDeposit','投诉保证金统计',38,1,0,1,0,'',0),(48,'fa fa-database','System/clearData','数据清理',2,1,0,1,1,'',0),(49,'','Index/main','Dashboard',1,1,0,1,0,'',0),(51,'','System/SaveBase','保存设置',3,0,0,1,1,'',0),(52,'','System/BindMobileShow','绑定手机号码',44,0,0,1,1,'',0),(53,'','System/editMobileShow','手机修改',44,0,0,1,1,'',0),(54,'fa fa-lock','System/editPassword','修改密码',2,1,0,1,1,'',0),(55,'','System/editSmstemplate','短信模板',5,0,0,1,1,'',0),(56,'','System/saveSmstemplate','保存短信模板',5,0,0,1,1,'',0),(57,'','System/saveEmail','邮件保存',4,0,0,1,1,'',0),(58,'','System/testMobile','测试短信',5,0,0,1,1,'',0),(59,'','System/deleteAdmin','删除短信模板',5,0,0,1,1,'',0),(60,'','Admin/addAdmin','管理员添加',8,0,0,1,1,'',0),(61,'','Admin/editAdmin','管理员修改',8,0,0,1,1,'',0),(62,'','Admin/deleteAdmin','管理员删除',8,0,0,1,1,'',0),(63,'','Auth/addGroup','添加角色',9,0,0,1,1,'',0),(64,'','Auth/editGroup','修改角色',9,0,0,1,1,'',0),(65,'','Auth/giveRole','选择角色',9,0,0,1,1,'',0),(66,'','Auth/ruleGroup','分配权限',9,0,0,1,1,'',0),(67,'','Menu/addMenu','添加菜单',10,0,0,1,1,'',0),(68,'','Menu/editMenu','修改菜单',10,0,0,1,1,'',0),(69,'','Menu/delMenu','删除菜单',10,0,0,1,1,'',0),(70,'','System/clearDataSend','数据清理提交',48,0,0,1,1,'',0),(71,'','User/addAgentCate','代理级别',19,0,0,1,1,'',0),(72,'','User/saveAgentCate','保存代理级别',18,0,0,1,1,'',0),(73,'','User/addInvitecode','添加激活码',16,0,0,1,1,'',0),(74,'','User/EditAgentCate','修改代理分类',18,0,0,1,1,'',0),(75,'','User/deleteAgentCate','删除代理分类',19,0,0,1,1,'',0),(76,'','User/setInvite','邀请码设置',16,0,0,1,1,'',0),(77,'','User/addInvite','创建邀请码',16,0,0,1,1,'',0),(78,'','User/delInvitecode','删除邀请码',16,0,0,1,1,'',0),(79,'','User/editUser','用户编辑',12,0,0,1,1,'',0),(80,'','User/changeuser','修改状态',12,0,0,1,1,'',0),(81,'','User/authorize','用户认证',12,0,0,1,1,'',0),(82,'','User/usermoney','用户资金管理',12,0,0,1,1,'',0),(83,'','User/userWithdrawal','用户提现设置',12,0,0,1,1,'',0),(84,'','User/userRateEdit','用户费率设置',12,0,0,1,1,'',0),(85,'','User/editPassword','用户密码修改',12,0,0,1,1,'',0),(86,'','User/editStatus','用户状态修改',12,0,0,1,1,'',0),(87,'','User/delUser','用户删除',12,0,0,1,1,'',0),(88,'','User/thawingFunds','T1解冻任务管理',12,0,0,1,1,'',0),(89,'','User/exportuser','导出用户',12,0,0,1,1,'',0),(90,'','User/editAuthoize','修改用户认证',12,0,0,1,1,'',0),(91,'','User/getRandstr','切换商户密钥',12,0,0,1,1,'',0),(92,'','User/suoding','用户锁定',15,0,0,1,1,'',0),(93,'','User/editbankcard','银行卡管理',12,0,0,1,1,'',0),(94,'','User/saveUser','添加用户',12,0,0,1,1,'',0),(95,'','User/saveUserProduct','保存用户产品',12,0,0,1,1,'',0),(96,'','User/saveUserRate','保存用户费率',12,0,0,1,1,'',0),(97,'','User/edittongdao','编辑通道',12,0,0,1,1,'',0),(98,'','User/frozenMoney','用户资金冻结',12,0,0,1,1,'',0),(99,'','User/unfrozenHandles','T1资金解冻',12,0,0,1,1,'',0),(100,'','User/frozenOrder','冻结订单列表',12,0,0,1,1,'',0),(101,'','User/frozenHandles','T1订单解冻展示',12,0,0,1,1,'',0),(102,'','Withdrawal/editStatus','操作状态',29,0,0,1,1,'',0),(103,'','Withdrawal/editwtStatus','操作订单状态',30,0,0,1,1,'',0),(104,'','Withdrawal/exportorder','导出数据',27,0,0,1,1,'',0),(105,'','Withdrawal/editwtAllStatus','批量修改提款状态',27,0,0,1,1,'',0),(106,'','Withdrawal/exportweituo','导出委托提现',30,0,0,1,1,'',0),(107,'','Payment/index','提交上游',30,0,0,1,1,'',0),(108,'','Withdrawal/saveWithdrawal','保存设置',28,0,0,1,1,'',0),(109,'','Withdrawal/AddHoliday','添加假日',27,0,0,1,1,'',0),(110,'','Withdrawal/settimeEdit','编辑提款时间',27,0,0,1,1,'',0),(111,'','Withdrawal/delHoliday','删除节假日',27,0,0,1,1,'',0),(112,'','Statistics/exportorder','订单数据导出',40,0,0,1,1,'',0),(113,'','Statistics/details','查看详情',39,0,0,1,1,'',0),(114,'','Order/exportorder','订单导出',23,0,0,1,1,'',0),(115,'','Order/exceldownload','记录导出',23,0,0,1,1,'',0),(116,'fa fa-area-chart','Statistics/platformReport','平台报表',38,1,0,1,1,'',0),(117,'fa fa-area-chart','Statistics/merchantReport','商户报表',38,1,0,1,0,'',0),(118,'fa fa-area-chart','Statistics/agentReport','代理报表',38,1,0,1,0,'',0),(119,'','Withdrawal/submitDf','代付提交',30,0,0,1,1,'',0),(120,'','User/editUserProduct','分配用户通道',12,0,0,1,1,'',0),(121,'fa fa-wrench','Transaction/index','风控设置',11,1,0,1,1,'',30),(122,'','Statistics/exportPlatform','导出平台报表',116,0,0,1,1,'',0),(123,'','Statistics/exportMerchant','导出商户报表',117,0,0,1,1,'',0),(124,'','Statistics/exportAgent','导出代理报表',118,0,0,1,1,'',0),(125,'','Order/show','查看订单',22,0,0,1,1,'',0),(126,'fa fa-cog','Withdrawal/checkNotice','提现申请声音提示',2,0,0,1,1,'',0),(127,'fa fa-hand-o-right','Order/index','代收订单',22,1,0,1,1,'',100),(128,'','Withdrawal/rejectAllDf','批量驳回代付',27,0,0,1,1,'',0),(129,'','User/saveWithdrawal','保存用户提款设置',28,0,0,1,1,'',0),(130,'fa fa-snowflake-o','Order/frozenOrder','冻结订单',22,1,0,1,1,'',60),(131,'fa fa-group','User/index','全部用户',11,1,0,1,1,'',100),(132,'fa fa-file-text','Log/index','商户操作日志',11,1,0,1,1,'',0),(133,'fa fa-area-chart','Statistics/analysis','交易统计分析',38,1,0,1,1,'',0),(134,'fa fa-pie-chart','OrderTable/MerchantTable','商户报表',38,1,0,1,1,'',0),(135,'fa fa-pie-chart','OrderTable/AgentTable','代理报表',38,1,0,1,1,'',0),(136,'fa fa-line-chart','OrderTable/Statistics','成功率及交易量统计',38,1,0,1,1,'',0),(138,'fa fa-file-text','HtLog/index','管理员操作日志',7,1,0,1,1,'',0),(140,'fa fa-hand-lizard-o','SystemBank/index','银行卡管理',27,1,0,1,1,'',0),(141,'','Order/setOrderPaid','订单成功审核',22,0,0,1,1,'',0),(142,'','Order/setnOrderPaid','订单失败审核',22,0,0,1,1,'',0),(143,'','Order/doForzen','订单冻结',22,0,0,1,1,'',0),(144,'','Order/thawOrder','订单解冻',22,0,0,1,1,'',0),(145,'','Channel/editStatus','供应商开关',32,0,0,1,1,'',0),(146,'','PayForAnother/editStatus','代付供应商开关',34,0,0,1,1,'',0),(147,'','PayForAnother/editDefault','代付供应商默认开关',34,0,0,1,1,'',0),(148,'','Withdrawal/submitnotify','代付补发',30,0,0,1,1,'',0),(149,'','User/editUserChannel','配置默认代付通道',12,0,0,1,1,'',0),(150,'','User/saveUserChannel','保存默认代付通道',12,0,0,1,1,'',0),(151,'','PayForAnother/operationSupplier','编辑代付渠道',34,0,0,1,1,'',0),(152,'','PayForAnother/saveEditSupplier','保存代付渠道',34,0,0,1,1,'',0),(153,'','Channel/editSupplier','编辑入金渠道',32,0,0,1,1,'',0),(154,'','Channel/saveEditSupplier','保存入金渠道',32,0,0,1,1,'',0),(155,'','User/incrMoney','加减余额',12,0,0,1,1,'',0),(156,'','User/frozenTiming','T1冻结资金管理',12,0,0,1,1,'',0),(157,'fa fa-indent','Order/indexCount','代收成功统计',22,1,0,1,1,'',20),(158,'fa fa-window-restore','Withdrawal/payment1','代付成功统计',27,1,0,1,1,'',40),(159,'','Order/showSuccessRate','通道成功率',157,0,0,1,1,'',0),(160,'fa fa-indent','Order/utrOrder','UTR查询记录',22,1,0,1,1,'',10),(161,'fa fa-window-restore','Statistics/merchantReport','商户通道统计',38,1,0,1,1,'',40),(162,'fa fa-line-chart','Statistics/merchantSuccessRate','商户通道成功率',38,1,0,1,1,'',0),(163,'fa fa-hourglass','Download/#','下载中心',0,1,0,1,1,'',1),(164,'fa fa-database','Download/index','下载列表',163,1,0,1,1,'',0),(165,'fa fa-window-restore','Withdrawal/payment1?currency=INR','代付成功统计(2)',27,1,0,1,1,'',0),(166,'fa fa-indent','Order/indexCount?currency=INR','代收成功统计(2）',22,1,0,1,1,'',0),(167,'fa fa-indent','Order/changeRecord?currency=INR','流水记录(2)',38,1,0,1,1,'',0),(168,'fa fa-window-restore','Withdrawal/payment?currency=INR','代付订单(2)',27,1,0,1,1,'',10),(169,'fa fa-window-restore','Withdrawal/payment?type=2','U下发订单',27,1,0,1,1,'',30),(170,'fa fa-window-restore','Withdrawal/payment1U','U下发数据统计',27,1,0,1,1,'',20),(171,'','Withdrawal/refund','代付退款',30,0,0,1,1,'',0),(172,'fa fa-sliders','Statistics/productReconciliation','通道对账单',38,1,0,1,1,'',50),(173,'','User/usermoneyPHP','查看国家1余额',12,0,0,1,1,'',0),(174,'','User/usermoneyINR','查看国家2余额',12,0,0,1,1,'',0),(175,'','User/unbindtelegram','解绑机器人',12,0,0,1,1,'',0),(176,'','Order/showLog','代收日志',127,0,0,1,1,'',0),(177,'','Withdrawal/showLog','代付日志',30,0,0,1,1,'',0),(178,'','User/userAllRateEdit','批量编辑收款费率',12,0,0,1,1,'',0),(179,'','User/unbindGoogle','解绑谷歌验证器',12,0,0,1,1,'',0),(180,'fa fa-window-restore','Agency/#','包网管理',0,1,0,1,1,'',34),(181,'fa fa-street-view','Agency/index','包网列表',180,1,0,1,1,'',0),(182,'fa fa-user','Statistics/agencyReport','包网通道报表',38,1,0,1,1,'',30),(183,'','Agency/saveUser','添加包网',181,0,0,1,1,'',0),(184,'fa fa-cog','Statistics/monitor','安全信息监控',38,1,0,1,1,'',10),(185,'','User/editAllUserWithdrawal','按商户批量设置代付通道',12,0,0,1,1,'',0),(186,'','User/editAllUserProduct','按商户批量设置收款通道',12,0,0,1,1,'',0),(187,'','User/editAllUserWithdrawalCountry','按国家批量设置代付通道',12,0,0,1,1,'',0),(188,'','User/editAllUserWithdrawalType','按包网批量设置代付通道',12,0,0,1,1,'',0),(189,'','User/editAllUserProductCountry','按国家批量设置收款通道',12,0,0,1,1,'',0),(190,'','User/editAllUserProductType','按包网批量设置收款通道',12,0,0,1,1,'',0),(191,'','User/getChannel','获取收款通道信息',12,0,0,1,1,'',0),(192,'','User/getWithdrawal','获取代付通道信息',12,0,0,1,1,'',0),(193,'','User/saveAllUserProduct','保存批量设置收款通道信息',12,0,0,1,1,'',0),(194,'','User/saveAllUserWithdrawal','保存批量设置代付通道信息',12,0,0,1,1,'',0),(195,'','Agency/editUserAgency','查看归属用户',181,0,0,1,1,'',7),(196,'','Agency/saveUser','保存包网修改',181,0,0,1,1,'',3),(197,'','Agency/saveUserAgency','保存分配用户',181,0,0,1,1,'',0),(198,'','Download/setlist','导出数据权限',164,0,0,1,1,'',0),(199,'','User/saveAllUserRate','保存批量编辑收款费率',12,0,0,1,1,'',0),(200,'fa fa-file-text','Order/getAddLog','代收提交日志',22,1,0,1,1,'',0),(201,'fa fa-file-text','Withdrawal/getAddLog','代付提交日志',27,1,0,1,1,'',0),(202,'fa fa-indent','Withdrawal/dfapply','提现申请',205,1,0,1,1,'',0),(203,'fa fa-window-restore','Withdrawal/dfapplylist','提现订单',205,1,0,1,1,'',0),(204,'fa fa-indent','Withdrawal/dfapplylist1','提现成功统计',205,1,0,1,1,'',0),(205,'fa fa-file-text','','提现管理',0,1,0,1,1,'',15),(206,'','Withdrawal/saveURate','修改U汇率',169,0,0,1,1,'',0);
/*!40000 ALTER TABLE `pay_auth_rule` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_auto_df_log`
--

DROP TABLE IF EXISTS `pay_auto_df_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_auto_df_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `df_id` int(11) NOT NULL DEFAULT '0' COMMENT '代付ID',
  `type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '类型：1提交 2查询',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '结果 0：提交失败 1：提交成功 2：代付成功 3：代付失败',
  `msg` varchar(255) DEFAULT '' COMMENT '描述',
  `ctime` int(11) NOT NULL DEFAULT '0' COMMENT '提交时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_auto_df_log`
--

LOCK TABLES `pay_auto_df_log` WRITE;
/*!40000 ALTER TABLE `pay_auto_df_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `pay_auto_df_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_auto_unfrozen_order`
--

DROP TABLE IF EXISTS `pay_auto_unfrozen_order`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_auto_unfrozen_order` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `freeze_money` decimal(15,4) NOT NULL DEFAULT '0.0000' COMMENT '冻结金额',
  `unfreeze_time` int(11) NOT NULL DEFAULT '0' COMMENT '计划解冻时间',
  `real_unfreeze_time` int(11) NOT NULL DEFAULT '0' COMMENT '实际解冻时间',
  `is_pause` tinyint(3) NOT NULL DEFAULT '0' COMMENT '是否暂停解冻 0正常解冻 1暂停解冻',
  `status` tinyint(3) NOT NULL DEFAULT '0' COMMENT '解冻状态 0未解冻 1已解冻',
  `create_at` int(11) NOT NULL COMMENT '记录创建时间',
  `update_at` int(11) NOT NULL COMMENT '记录更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `idx_unfreezeing` (`status`,`is_pause`,`unfreeze_time`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='投诉保证金余额';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_auto_unfrozen_order`
--

LOCK TABLES `pay_auto_unfrozen_order` WRITE;
/*!40000 ALTER TABLE `pay_auto_unfrozen_order` DISABLE KEYS */;
INSERT INTO `pay_auto_unfrozen_order` VALUES (1,6,119567.0000,0,1686813732,0,1,1686656764,1686656764),(2,3,6067.0000,0,1686813702,0,1,1686657069,1686657069),(3,57,3500.0000,0,0,0,0,1698057970,1698057970);
/*!40000 ALTER TABLE `pay_auto_unfrozen_order` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_bankcard`
--

DROP TABLE IF EXISTS `pay_bankcard`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_bankcard` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '商户编号',
  `bankname` varchar(100) NOT NULL COMMENT '银行名称',
  `subbranch` varchar(100) DEFAULT NULL COMMENT '支行名称',
  `accountname` varchar(100) NOT NULL COMMENT '开户名',
  `cardnumber` varchar(100) NOT NULL COMMENT '银行卡号',
  `idnumber` varchar(100) DEFAULT NULL COMMENT '身份证号码',
  `phone` varchar(15) DEFAULT NULL COMMENT '联系电话',
  `province_code` int(10) DEFAULT '0' COMMENT '省份代码',
  `province` varchar(100) DEFAULT NULL COMMENT '所属省',
  `city_code` int(10) DEFAULT '0' COMMENT '城市代码',
  `city` varchar(100) DEFAULT NULL COMMENT '所属市',
  `ip` varchar(100) DEFAULT NULL COMMENT '上次修改IP',
  `ipaddress` varchar(300) DEFAULT NULL COMMENT 'IP地址',
  `alias` varchar(255) DEFAULT '' COMMENT '备注',
  `isdefault` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否默认 1是 0 否',
  `updatetime` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `status` enum('0','1') NOT NULL DEFAULT '1' COMMENT '状态:0=关闭,1=开启',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `IND_UID` (`userid`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_bankcard`
--

LOCK TABLES `pay_bankcard` WRITE;
/*!40000 ALTER TABLE `pay_bankcard` DISABLE KEYS */;
/*!40000 ALTER TABLE `pay_bankcard` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_blockedlog`
--

DROP TABLE IF EXISTS `pay_blockedlog`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_blockedlog` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `orderid` varchar(100) NOT NULL COMMENT '订单号',
  `userid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '商户编号',
  `amount` decimal(15,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '冻结金额',
  `thawtime` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '解冻时间',
  `pid` smallint(6) unsigned NOT NULL DEFAULT '0' COMMENT '商户支付通道',
  `createtime` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '状态 1 解冻 0 冻结',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='资金冻结待解冻记录';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_blockedlog`
--

LOCK TABLES `pay_blockedlog` WRITE;
/*!40000 ALTER TABLE `pay_blockedlog` DISABLE KEYS */;
/*!40000 ALTER TABLE `pay_blockedlog` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_browserecord`
--

DROP TABLE IF EXISTS `pay_browserecord`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_browserecord` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `articleid` int(11) NOT NULL DEFAULT '0',
  `userid` int(11) NOT NULL DEFAULT '0',
  `datetime` datetime NOT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_browserecord`
--

LOCK TABLES `pay_browserecord` WRITE;
/*!40000 ALTER TABLE `pay_browserecord` DISABLE KEYS */;
/*!40000 ALTER TABLE `pay_browserecord` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_category`
--

DROP TABLE IF EXISTS `pay_category`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_category` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `pid` smallint(6) unsigned NOT NULL DEFAULT '0' COMMENT '父级ID',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '状态 1开启 0关闭',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='文章栏目';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_category`
--

LOCK TABLES `pay_category` WRITE;
/*!40000 ALTER TABLE `pay_category` DISABLE KEYS */;
/*!40000 ALTER TABLE `pay_category` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_channel`
--

DROP TABLE IF EXISTS `pay_channel`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_channel` (
  `id` smallint(6) unsigned NOT NULL AUTO_INCREMENT COMMENT '供应商通道ID',
  `code` varchar(200) DEFAULT NULL COMMENT '供应商通道英文编码',
  `title` varchar(200) DEFAULT NULL COMMENT '供应商通道名称',
  `mch_id` varchar(100) DEFAULT NULL COMMENT '商户号',
  `signkey` varchar(500) DEFAULT NULL COMMENT '签文密钥',
  `appid` varchar(100) DEFAULT NULL COMMENT '应用APPID',
  `appsecret` varchar(100) DEFAULT NULL COMMENT '安全密钥',
  `gateway` varchar(300) DEFAULT NULL COMMENT '网关地址',
  `pagereturn` varchar(255) DEFAULT NULL COMMENT '页面跳转网址',
  `serverreturn` varchar(255) DEFAULT NULL COMMENT '服务器通知网址',
  `defaultrate` decimal(10,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '下家费率',
  `fengding` decimal(10,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '封顶手续费',
  `rate` decimal(10,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '银行费率',
  `updatetime` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '上次更改时间',
  `unlockdomain` varchar(100) NOT NULL COMMENT '防封域名',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '状态 1开启 0关闭',
  `paytype` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '渠道类型: 1 Gcash直连 2 Gcash扫码 3 Maya 4 VietQR',
  `start_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '开始时间',
  `end_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '结束时间',
  `paying_money` decimal(11,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '当天交易金额',
  `all_money` decimal(11,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '当天上游可交易量',
  `last_paying_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '最后交易时间',
  `min_money` decimal(11,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '单笔最小交易额',
  `max_money` decimal(11,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '单笔最大交易额',
  `fix_money` varchar(255) DEFAULT NULL COMMENT '指定金额',
  `control_status` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '风控状态:0否1是',
  `offline_status` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '通道上线状态:0已下线，1上线',
  `t0defaultrate` decimal(10,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT 'T0运营费率',
  `t0fengding` decimal(10,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT 'T0封顶手续费',
  `t0rate` decimal(10,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT 'T0成本费率',
  `control_order` tinyint(1) DEFAULT '0' COMMENT '轮询控制',
  `notifyIP` varchar(1000) NOT NULL DEFAULT ' ' COMMENT '白名单IP',
  `currency` varchar(10) NOT NULL COMMENT '国家货币',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=40 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='供应商列表';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_channel`
--

LOCK TABLES `pay_channel` WRITE;
/*!40000 ALTER TABLE `pay_channel` DISABLE KEYS */;
INSERT INTO `pay_channel` VALUES (6,'CeShi','测试通道','','','','','https://api.winpay.site/br/payment.json','',NULL,0.0000,0.0000,0.0150,1779508269,'',1,6,0,0,0.00,0.00,0,0.00,0.00,NULL,0,1,0.0000,0.0000,0.0000,0,'','PHP'),(7,'GcashHiPay','Gcash唤醒--HiPay','','','','','https://api.payhipay.com/api/pay/unifiedOrder','',NULL,0.0000,0.0000,0.0080,1779558703,'',1,6,0,0,0.00,0.00,0,0.00,0.00,NULL,0,1,0.0000,0.0000,0.0000,0,'8.219.113.100,47.245.95.80','PHP'),(8,'GcashHiPay222','Gcash唤醒222--HiPay','','','','','https://api.payhipay.com/api/pay/unifiedOrder','',NULL,0.0000,0.0000,0.0080,1779558693,'',1,6,0,0,0.00,0.00,0,0.00,0.00,NULL,0,1,0.0000,0.0000,0.0000,0,'8.219.113.100,47.245.95.80','PHP');
/*!40000 ALTER TABLE `pay_channel` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_channel_account`
--

DROP TABLE IF EXISTS `pay_channel_account`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_channel_account` (
  `id` smallint(6) unsigned NOT NULL AUTO_INCREMENT COMMENT '供应商通道账号ID',
  `channel_id` smallint(6) unsigned NOT NULL COMMENT '通道id',
  `mch_id` varchar(100) DEFAULT NULL COMMENT '商户号',
  `signkey` varchar(500) DEFAULT NULL COMMENT '签文密钥',
  `subappid` varchar(100) DEFAULT NULL COMMENT '多级代理商编号',
  `appid` varchar(100) DEFAULT NULL COMMENT '应用APPID',
  `appsecret` varchar(2500) DEFAULT NULL COMMENT '安全密钥',
  `defaultrate` decimal(10,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '下家费率',
  `fengding` decimal(10,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '封顶手续费',
  `rate` decimal(10,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '银行费率',
  `updatetime` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '上次更改时间',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '状态 1开启 0关闭',
  `title` varchar(100) DEFAULT NULL COMMENT '账户标题',
  `weight` tinyint(2) DEFAULT NULL COMMENT '轮询权重',
  `custom_rate` tinyint(1) DEFAULT NULL COMMENT '是否自定义费率',
  `start_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '开始交易时间',
  `end_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '结束时间',
  `last_paying_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '最后一笔交易时间',
  `paying_money` decimal(11,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '当天交易金额',
  `all_money` decimal(11,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '单日可交易金额',
  `max_money` decimal(11,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '单笔交易最大金额',
  `min_money` decimal(11,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '单笔交易最小金额',
  `offline_status` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '上线状态-1上线,0下线',
  `control_status` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '风控状态-0不风控,1风控中',
  `is_defined` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '是否自定义:1-是,0-否',
  `unit_frist_paying_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '单位时间第一笔交易时间',
  `unit_paying_number` mediumint(8) unsigned NOT NULL DEFAULT '0' COMMENT '单时间交易笔数',
  `unit_paying_amount` decimal(11,0) unsigned NOT NULL DEFAULT '0' COMMENT '单位时间交易金额',
  `unit_interval` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '单位时间数值',
  `time_unit` char(1) NOT NULL DEFAULT 's' COMMENT '限制时间单位',
  `unit_number` mediumint(8) unsigned NOT NULL DEFAULT '0' COMMENT '单位时间次数',
  `unit_all_money` decimal(11,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '单位时间金额',
  `t0defaultrate` decimal(10,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT 'T0运营费率',
  `t0fengding` decimal(10,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT 'T0封顶手续费',
  `t0rate` decimal(10,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT 'T0成本费率',
  `unlockdomain` varchar(255) NOT NULL COMMENT '防封域名',
  `success_num` decimal(10,0) NOT NULL DEFAULT '0' COMMENT '成功次数',
  `fail_num` decimal(10,0) NOT NULL DEFAULT '0' COMMENT '失败次数',
  `fail_rounds` decimal(10,0) NOT NULL DEFAULT '0' COMMENT '失败轮数',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=44 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='供应商账号列表';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_channel_account`
--

LOCK TABLES `pay_channel_account` WRITE;
/*!40000 ALTER TABLE `pay_channel_account` DISABLE KEYS */;
INSERT INTO `pay_channel_account` VALUES (8,6,'cs','','','','',0.0000,0.0000,0.0000,1733478360,1,'cs',1,0,0,0,0,0.00,0.00,0.00,0.00,0,0,0,0,0,0,0,'s',0,0.00,0.0000,0.0000,0.0000,'',0,0,0),(9,7,'M1765160951','wbLZ58emIy4jamWZrbfvCdyjLTrrynT4aaWR3F9f279tX045eWezCWrd14aauEH0','','693637f71496f396ddecbde3','',0.0000,0.0000,0.0000,1779518175,1,'Maxpay',1,0,0,0,0,0.00,0.00,0.00,0.00,0,0,0,0,0,0,0,'s',0,0.00,0.0000,0.0000,0.0000,'',0,0,0),(10,8,'M1762263548','6VY9zWww8sp9aHcwzBCU1gXXIgM8qZgSl1Xt2NKdfRTXtRYEvFlYjpRyvRBhXedE','','690a01fc1496402801660069','',0.0000,0.0000,0.0000,1779518155,1,'Maxpay2',1,0,0,0,0,0.00,0.00,0.00,0.00,0,0,0,0,0,0,0,'s',0,0.00,0.0000,0.0000,0.0000,'',0,0,0);
/*!40000 ALTER TABLE `pay_channel_account` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_complaints_deposit`
--

DROP TABLE IF EXISTS `pay_complaints_deposit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_complaints_deposit` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `pay_orderid` varchar(100) NOT NULL DEFAULT '0' COMMENT '系统订单号',
  `out_trade_id` varchar(50) NOT NULL DEFAULT '' COMMENT '下游订单号',
  `freeze_money` decimal(15,4) NOT NULL DEFAULT '0.0000' COMMENT '冻结保证金额',
  `unfreeze_time` int(11) NOT NULL DEFAULT '0' COMMENT '计划解冻时间',
  `real_unfreeze_time` int(11) NOT NULL DEFAULT '0' COMMENT '实际解冻时间',
  `is_pause` tinyint(3) NOT NULL DEFAULT '0' COMMENT '是否暂停解冻 0正常解冻 1暂停解冻',
  `status` tinyint(3) NOT NULL DEFAULT '0' COMMENT '解冻状态 0未解冻 1已解冻',
  `create_at` int(11) NOT NULL COMMENT '记录创建时间',
  `update_at` int(11) NOT NULL COMMENT '记录更新时间',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `idx_unfreezeing` (`status`,`is_pause`,`unfreeze_time`) USING BTREE,
  KEY `pay_orderid` (`pay_orderid`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='投诉保证金余额';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_complaints_deposit`
--

LOCK TABLES `pay_complaints_deposit` WRITE;
/*!40000 ALTER TABLE `pay_complaints_deposit` DISABLE KEYS */;
/*!40000 ALTER TABLE `pay_complaints_deposit` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_complaints_deposit_rule`
--

DROP TABLE IF EXISTS `pay_complaints_deposit_rule`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_complaints_deposit_rule` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户id',
  `is_system` tinyint(3) NOT NULL DEFAULT '0' COMMENT '是否系统规则 1是 0否',
  `ratio` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '投诉保证金比例（百分比）',
  `freeze_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '冻结时间（秒）',
  `status` tinyint(3) NOT NULL DEFAULT '0' COMMENT '规则是否开启 1开启 0关闭',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='投诉保证金规则表';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_complaints_deposit_rule`
--

LOCK TABLES `pay_complaints_deposit_rule` WRITE;
/*!40000 ALTER TABLE `pay_complaints_deposit_rule` DISABLE KEYS */;
INSERT INTO `pay_complaints_deposit_rule` VALUES (1,180586943,0,10.00,3,1);
/*!40000 ALTER TABLE `pay_complaints_deposit_rule` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_country`
--

DROP TABLE IF EXISTS `pay_country`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_country` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(50) NOT NULL COMMENT '名称',
  `code` varchar(10) NOT NULL COMMENT '编号',
  `currency` varchar(10) NOT NULL COMMENT '国家货币',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '状态 1激活 0未激活',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=45 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='国家信息';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_country`
--

LOCK TABLES `pay_country` WRITE;
/*!40000 ALTER TABLE `pay_country` DISABLE KEYS */;
INSERT INTO `pay_country` VALUES (38,'菲律宾','PH','PHP',1),(40,'巴基斯坦','PK','PKR',1),(41,'巴西','BR','BRL',1),(42,'越南','VN','VND',1),(43,'墨西哥','MX','MXN',0),(44,'印尼','IDR','IDR',0);
/*!40000 ALTER TABLE `pay_country` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_df_api_order`
--

DROP TABLE IF EXISTS `pay_df_api_order`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_df_api_order` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '商户编号',
  `trade_no` varchar(30) NOT NULL DEFAULT '' COMMENT '平台订单号',
  `out_trade_no` varchar(30) NOT NULL DEFAULT '' COMMENT '商户订单号',
  `money` decimal(15,4) NOT NULL DEFAULT '0.0000' COMMENT '金额',
  `currency` varchar(10) NOT NULL COMMENT '币种',
  `bankname` varchar(100) NOT NULL DEFAULT '' COMMENT '银行名称',
  `subbranch` varchar(100) NOT NULL DEFAULT '' COMMENT '支行名称',
  `accountname` varchar(100) NOT NULL DEFAULT '' COMMENT '开户名',
  `cardnumber` varchar(100) NOT NULL DEFAULT '' COMMENT '银行卡号',
  `phone` varchar(15) DEFAULT NULL COMMENT '联系电话',
  `idnumber` varchar(20) DEFAULT NULL,
  `province` varchar(100) NOT NULL DEFAULT '' COMMENT '所属省',
  `city` varchar(100) NOT NULL DEFAULT '' COMMENT '所属市',
  `ip` varchar(100) DEFAULT '' COMMENT 'IP地址',
  `check_status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0：待审核 1：已提交后台审核 2：审核驳回',
  `extends` text COMMENT '扩展字段',
  `df_id` int(11) NOT NULL DEFAULT '0' COMMENT '代付ID',
  `notifyurl` varchar(255) DEFAULT '' COMMENT '异步通知地址',
  `reject_reason` varchar(255) NOT NULL DEFAULT '' COMMENT '驳回原因',
  `check_time` int(11) NOT NULL DEFAULT '0' COMMENT '审核时间',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `df_charge_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '代付API扣除手续费方式，0：从到账金额里扣，1：从商户余额里扣',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `IND_UID` (`userid`) USING BTREE,
  KEY `df_id` (`df_id`) USING BTREE,
  KEY `out_trade_no` (`out_trade_no`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_df_api_order`
--

LOCK TABLES `pay_df_api_order` WRITE;
/*!40000 ALTER TABLE `pay_df_api_order` DISABLE KEYS */;
/*!40000 ALTER TABLE `pay_df_api_order` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_email`
--

DROP TABLE IF EXISTS `pay_email`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_email` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `smtp_host` varchar(300) DEFAULT NULL,
  `smtp_port` varchar(300) DEFAULT NULL,
  `smtp_user` varchar(300) DEFAULT NULL,
  `smtp_pass` varchar(300) DEFAULT NULL,
  `smtp_email` varchar(300) DEFAULT NULL,
  `smtp_name` varchar(300) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_email`
--

LOCK TABLES `pay_email` WRITE;
/*!40000 ALTER TABLE `pay_email` DISABLE KEYS */;
INSERT INTO `pay_email` VALUES (1,'smtpdm.aliyun.com','888','info@mail.mars68.com','Mailpush123','info@mail.mars68.com','Mars website');
/*!40000 ALTER TABLE `pay_email` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_industry_log`
--

DROP TABLE IF EXISTS `pay_industry_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_industry_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pid` int(11) NOT NULL DEFAULT '1',
  `data` longtext,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_industry_log`
--

LOCK TABLES `pay_industry_log` WRITE;
/*!40000 ALTER TABLE `pay_industry_log` DISABLE KEYS */;
INSERT INTO `pay_industry_log` VALUES (1,1,'ORDER$1$323031392D30352D3036$1$36$1$3630302E30303030$1$36,ORDER$1$323031392D30352D3033$1$38$1$313233302E30303030$1$38,WTTKLIST$1$323031392D30352D3033$1$34$1$333330302E30303030$1$34');
/*!40000 ALTER TABLE `pay_industry_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_invitecode`
--

DROP TABLE IF EXISTS `pay_invitecode`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_invitecode` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `invitecode` varchar(32) NOT NULL,
  `fmusernameid` int(11) unsigned NOT NULL DEFAULT '0',
  `syusernameid` int(11) NOT NULL DEFAULT '0',
  `regtype` tinyint(1) unsigned NOT NULL DEFAULT '4' COMMENT '用户组 4 普通用户 5 代理商',
  `fbdatetime` int(11) unsigned NOT NULL DEFAULT '0',
  `yxdatetime` int(11) unsigned NOT NULL DEFAULT '0',
  `sydatetime` int(11) unsigned DEFAULT '0',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '邀请码状态 0 禁用 1 未使用 2 已使用',
  `is_admin` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '是否管理员添加',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `invitecode` (`invitecode`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_invitecode`
--

LOCK TABLES `pay_invitecode` WRITE;
/*!40000 ALTER TABLE `pay_invitecode` DISABLE KEYS */;
/*!40000 ALTER TABLE `pay_invitecode` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_inviteconfig`
--

DROP TABLE IF EXISTS `pay_inviteconfig`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_inviteconfig` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `invitezt` tinyint(1) unsigned DEFAULT '1',
  `invitetype2number` int(11) NOT NULL DEFAULT '20',
  `invitetype2ff` smallint(6) NOT NULL DEFAULT '1',
  `invitetype5number` int(11) NOT NULL DEFAULT '20',
  `invitetype5ff` smallint(6) NOT NULL DEFAULT '1',
  `invitetype6number` int(11) NOT NULL DEFAULT '20',
  `invitetype6ff` smallint(6) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_inviteconfig`
--

LOCK TABLES `pay_inviteconfig` WRITE;
/*!40000 ALTER TABLE `pay_inviteconfig` DISABLE KEYS */;
INSERT INTO `pay_inviteconfig` VALUES (1,1,0,0,10,1,0,0);
/*!40000 ALTER TABLE `pay_inviteconfig` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_loginrecord`
--

DROP TABLE IF EXISTS `pay_loginrecord`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_loginrecord` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userid` int(11) unsigned NOT NULL DEFAULT '0',
  `logindatetime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `loginip` varchar(100) NOT NULL,
  `loginaddress` varchar(300) DEFAULT NULL,
  `type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '类型：0：前台用户 1：后台用户',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_loginrecord`
--

LOCK TABLES `pay_loginrecord` WRITE;
/*!40000 ALTER TABLE `pay_loginrecord` DISABLE KEYS */;
INSERT INTO `pay_loginrecord` VALUES (1,9,'2026-05-20 13:03:29','203.160.68.85','香港',1),(2,11,'2026-05-20 13:05:53','203.160.68.85','香港',1),(3,42,'2026-05-20 13:13:47','131.226.97.163','美国',1),(4,42,'2026-05-20 13:28:00','131.226.97.163','美国',1),(5,11,'2026-05-22 10:16:11','103.172.183.85','亚太地区',1),(6,11,'2026-05-23 00:45:35','203.160.86.56','香港',1),(7,11,'2026-05-23 03:27:47','203.160.86.56','香港',1),(8,42,'2026-05-23 14:45:19','175.176.31.29','菲律宾',1),(9,3,'2026-05-24 01:56:04','175.176.31.29','菲律宾-',0),(10,11,'2026-05-28 04:52:57','203.160.68.186','香港',1);
/*!40000 ALTER TABLE `pay_loginrecord` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_member`
--

DROP TABLE IF EXISTS `pay_member`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_member` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL COMMENT '用户名',
  `password` varchar(32) NOT NULL COMMENT '密码',
  `groupid` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '用户组',
  `salt` varchar(10) NOT NULL COMMENT '密码随机字符',
  `parentid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '代理ID',
  `agent_cate` int(11) NOT NULL DEFAULT '0' COMMENT '代理级别',
  `balance` decimal(15,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '菲律宾可用余额',
  `blockedbalance` decimal(15,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '菲律宾冻结可用余额',
  `email` varchar(100) NOT NULL,
  `activate` varchar(200) NOT NULL,
  `regdatetime` int(11) unsigned NOT NULL DEFAULT '0',
  `activatedatetime` int(11) unsigned NOT NULL DEFAULT '0',
  `realname` varchar(50) DEFAULT NULL COMMENT '姓名',
  `sex` tinyint(1) NOT NULL DEFAULT '1' COMMENT '性别',
  `birthday` int(11) NOT NULL DEFAULT '0',
  `sfznumber` varchar(20) DEFAULT NULL,
  `mobile` varchar(15) DEFAULT NULL COMMENT '联系电话',
  `qq` varchar(15) DEFAULT NULL COMMENT 'QQ',
  `address` varchar(200) DEFAULT NULL COMMENT '联系地址',
  `paypassword` varchar(32) DEFAULT NULL COMMENT '支付密码',
  `authorized` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '1 已认证 0 未认证 2 待审核',
  `apidomain` varchar(500) DEFAULT NULL COMMENT '授权访问域名',
  `apikey` varchar(32) NOT NULL COMMENT 'APIKEY',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '状态 1激活 0未激活',
  `receiver` varchar(255) DEFAULT NULL COMMENT '台卡显示的收款人信息',
  `unit_paying_number` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '单位时间已交易次数',
  `unit_paying_amount` decimal(15,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '单位时间已交易金额',
  `unit_frist_paying_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '单位时间已交易的第一笔时间',
  `last_paying_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '当天最后一笔已交易时间',
  `paying_money` decimal(15,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '当天已交易金额',
  `login_ip` text COMMENT '登录IP',
  `last_error_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '最后登录错误时间',
  `login_error_num` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '错误登录次数',
  `google_auth` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否开启谷歌身份验证登录',
  `df_api` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否开启代付API',
  `open_charge` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否开启充值功能',
  `df_domain` text NOT NULL COMMENT '代付域名报备',
  `df_auto_check` tinyint(1) NOT NULL DEFAULT '1' COMMENT '代付API自动审核',
  `google_secret_key` varchar(255) NOT NULL DEFAULT '' COMMENT '谷歌密钥',
  `df_ip` text NOT NULL COMMENT '代付域名报备IP',
  `session_random` varchar(50) NOT NULL DEFAULT '' COMMENT 'session随机字符串',
  `df_charge_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '代付API扣除手续费方式，0：从到账金额里扣，1：从商户余额里扣',
  `last_login_time` int(11) NOT NULL DEFAULT '0' COMMENT '最后登录时间',
  `pfa_channel` varchar(100) NOT NULL DEFAULT ' ' COMMENT '指定代付通道',
  `telegram_id` varchar(30) DEFAULT NULL COMMENT '绑定的telegram群ID',
  `auto_tkmoney` decimal(20,0) DEFAULT '0' COMMENT 'telegram操作代付审核金额',
  `operators` text COMMENT 'telegram操作人员列表',
  `agency_id` int(11) NOT NULL DEFAULT '0' COMMENT '包网ID',
  `currency` varchar(10) NOT NULL COMMENT '国家货币',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_member`
--

LOCK TABLES `pay_member` WRITE;
/*!40000 ALTER TABLE `pay_member` DISABLE KEYS */;
INSERT INTO `pay_member` VALUES (2,'demouser','64e52d1254dde19be7966de449f0da66',4,'6450',0,0,0.0000,0.0000,'demouser','8f9a2804950df5c2ff9ba1f8a9b64937',1503828960,2017,'',1,0,'','','','中国','e10adc3949ba59abbe56e057f20f883e',1,NULL,'1ni0s1x77i94vusp4m0ssq0dsk3rrlu2',1,NULL,524,288822.1150,0,1760593607,288822.1150,'203.160.68.85',0,0,1,1,1,'',1,'','203.160.86.56','DvBc1mraJPnLQkSNuQaaFDs7i1ZnyjWG',0,1779287928,'','-1003641962047',100000,'',1,'PHP'),(3,'enzo','1caba6c7c6760a2c12bdd2617829e335',4,'6622',1,4,0.0000,0.0000,'','96f9582c5a1824e2200d69ef08278596',1779558453,2026,NULL,1,0,NULL,NULL,NULL,NULL,'e10adc3949ba59abbe56e057f20f883e',1,NULL,'zkohv8tbmxjpaetzuhx8pbdyrlbepdcq',1,NULL,0,0.0000,0,0,0.0000,'175.176.31.29',0,0,0,1,0,'',1,'','','T9WlzLA7ZoFZ1KUUrXWw45M4M4oBJLpn',0,1779558964,' ',NULL,100,'',0,'PHP');
/*!40000 ALTER TABLE `pay_member` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_member_agent_cate`
--

DROP TABLE IF EXISTS `pay_member_agent_cate`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_member_agent_cate` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `cate_name` varchar(50) DEFAULT NULL COMMENT '等级名',
  `desc` varchar(255) DEFAULT NULL COMMENT '等级描述',
  `ctime` int(11) DEFAULT '0' COMMENT '添加时间',
  `sort` int(11) DEFAULT '99' COMMENT '排序',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_member_agent_cate`
--

LOCK TABLES `pay_member_agent_cate` WRITE;
/*!40000 ALTER TABLE `pay_member_agent_cate` DISABLE KEYS */;
INSERT INTO `pay_member_agent_cate` VALUES (4,'普通商户','',1522638122,99),(5,'集团账号','',1522638122,99),(7,'高级代理商户','',1522638122,99);
/*!40000 ALTER TABLE `pay_member_agent_cate` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_moneycash`
--

DROP TABLE IF EXISTS `pay_moneycash`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_moneycash` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '商户编号',
  `change_type` int(1) unsigned NOT NULL DEFAULT '0' COMMENT '原金额',
  `ymoney` decimal(15,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '原金额',
  `change_money` decimal(15,4) NOT NULL DEFAULT '0.0000' COMMENT '变动金额',
  `gmoney` decimal(15,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '变动后金额',
  `datetime` int(11) NOT NULL COMMENT '修改时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='当日提现记录';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_moneycash`
--

LOCK TABLES `pay_moneycash` WRITE;
/*!40000 ALTER TABLE `pay_moneycash` DISABLE KEYS */;
/*!40000 ALTER TABLE `pay_moneycash` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_moneychange`
--

DROP TABLE IF EXISTS `pay_moneychange`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_moneychange` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '商户编号',
  `ymoney` decimal(15,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '原金额',
  `money` decimal(15,4) NOT NULL DEFAULT '0.0000' COMMENT '变动金额',
  `gmoney` decimal(15,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '变动后金额',
  `datetime` datetime DEFAULT NULL COMMENT '修改时间',
  `transid` varchar(50) DEFAULT NULL COMMENT '交易流水号',
  `tongdao` smallint(6) unsigned DEFAULT '0' COMMENT '支付通道ID',
  `channel_id` int(10) unsigned DEFAULT '0' COMMENT '渠道id',
  `paytype` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '渠道类型: 1 Gcash直连 2 Gcash扫码 3 Maya 4 VietQR',
  `lx` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '类型',
  `tcuserid` int(11) DEFAULT NULL,
  `tcdengji` int(11) DEFAULT NULL,
  `orderid` varchar(50) DEFAULT NULL COMMENT '订单号',
  `contentstr` varchar(255) DEFAULT NULL COMMENT '备注',
  `currency` varchar(10) NOT NULL COMMENT '国家货币',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_moneychange`
--

LOCK TABLES `pay_moneychange` WRITE;
/*!40000 ALTER TABLE `pay_moneychange` DISABLE KEYS */;
/*!40000 ALTER TABLE `pay_moneychange` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_moneychange0521`
--

DROP TABLE IF EXISTS `pay_moneychange0521`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_moneychange0521` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '商户编号',
  `ymoney` decimal(15,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '原金额',
  `money` decimal(15,4) NOT NULL DEFAULT '0.0000' COMMENT '变动金额',
  `gmoney` decimal(15,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '变动后金额',
  `datetime` datetime DEFAULT NULL COMMENT '修改时间',
  `transid` varchar(50) DEFAULT NULL COMMENT '交易流水号',
  `tongdao` smallint(6) unsigned DEFAULT '0' COMMENT '支付通道ID',
  `channel_id` int(10) unsigned DEFAULT '0' COMMENT '渠道id',
  `paytype` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '渠道类型: 1 Gcash直连 2 Gcash扫码 3 Maya 4 VietQR',
  `lx` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '类型',
  `t` tinyint(2) NOT NULL DEFAULT '1' COMMENT '结算周期（计算费率）',
  `tcuserid` int(11) DEFAULT NULL,
  `tcdengji` int(11) DEFAULT NULL,
  `orderid` varchar(50) DEFAULT NULL COMMENT '订单号',
  `contentstr` varchar(255) DEFAULT NULL COMMENT '备注',
  `currency` varchar(10) NOT NULL COMMENT '国家货币',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_moneychange0521`
--

LOCK TABLES `pay_moneychange0521` WRITE;
/*!40000 ALTER TABLE `pay_moneychange0521` DISABLE KEYS */;
/*!40000 ALTER TABLE `pay_moneychange0521` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_moneychange0522`
--

DROP TABLE IF EXISTS `pay_moneychange0522`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_moneychange0522` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '商户编号',
  `ymoney` decimal(15,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '原金额',
  `money` decimal(15,4) NOT NULL DEFAULT '0.0000' COMMENT '变动金额',
  `gmoney` decimal(15,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '变动后金额',
  `datetime` datetime DEFAULT NULL COMMENT '修改时间',
  `transid` varchar(50) DEFAULT NULL COMMENT '交易流水号',
  `tongdao` smallint(6) unsigned DEFAULT '0' COMMENT '支付通道ID',
  `channel_id` int(10) unsigned DEFAULT '0' COMMENT '渠道id',
  `paytype` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '渠道类型: 1 Gcash直连 2 Gcash扫码 3 Maya 4 VietQR',
  `lx` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '类型',
  `t` tinyint(2) NOT NULL DEFAULT '1' COMMENT '结算周期（计算费率）',
  `tcuserid` int(11) DEFAULT NULL,
  `tcdengji` int(11) DEFAULT NULL,
  `orderid` varchar(50) DEFAULT NULL COMMENT '订单号',
  `contentstr` varchar(255) DEFAULT NULL COMMENT '备注',
  `currency` varchar(10) NOT NULL COMMENT '国家货币',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_moneychange0522`
--

LOCK TABLES `pay_moneychange0522` WRITE;
/*!40000 ALTER TABLE `pay_moneychange0522` DISABLE KEYS */;
/*!40000 ALTER TABLE `pay_moneychange0522` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_moneychange0523`
--

DROP TABLE IF EXISTS `pay_moneychange0523`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_moneychange0523` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '商户编号',
  `ymoney` decimal(15,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '原金额',
  `money` decimal(15,4) NOT NULL DEFAULT '0.0000' COMMENT '变动金额',
  `gmoney` decimal(15,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '变动后金额',
  `datetime` datetime DEFAULT NULL COMMENT '修改时间',
  `transid` varchar(50) DEFAULT NULL COMMENT '交易流水号',
  `tongdao` smallint(6) unsigned DEFAULT '0' COMMENT '支付通道ID',
  `channel_id` int(10) unsigned DEFAULT '0' COMMENT '渠道id',
  `paytype` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '渠道类型: 1 Gcash直连 2 Gcash扫码 3 Maya 4 VietQR',
  `lx` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '类型',
  `t` tinyint(2) NOT NULL DEFAULT '1' COMMENT '结算周期（计算费率）',
  `tcuserid` int(11) DEFAULT NULL,
  `tcdengji` int(11) DEFAULT NULL,
  `orderid` varchar(50) DEFAULT NULL COMMENT '订单号',
  `contentstr` varchar(255) DEFAULT NULL COMMENT '备注',
  `currency` varchar(10) NOT NULL COMMENT '国家货币',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_moneychange0523`
--

LOCK TABLES `pay_moneychange0523` WRITE;
/*!40000 ALTER TABLE `pay_moneychange0523` DISABLE KEYS */;
INSERT INTO `pay_moneychange0523` VALUES (1,2,0.0000,100.0000,100.0000,'2026-05-23 15:57:53','2026052315562052561066470',7717,0,6,1,1,NULL,NULL,'E2026052315561468824','E2026052315561468824订单充值,结算方式：t+0','PHP'),(3,2,100.0000,100.0000,0.0000,'2026-05-23 16:10:42','Q2026052316104223842614697',0,0,0,10,1,NULL,NULL,'202605231610421027','2026-05-23 16:10:42委托提现操作','PHP'),(4,2,0.0000,100.0000,100.0000,'2026-05-23 16:28:24','Q2026052316104223842614697',0,0,0,18,1,NULL,NULL,'202605231610421027','支付失败: Q2026052316104223842614697','PHP'),(5,2,100.0000,100.0000,0.0000,'2026-05-23 16:28:28','Q2026052316282824908247803',0,0,0,10,1,NULL,NULL,'202605231628286255','2026-05-23 16:28:28委托提现操作','PHP');
/*!40000 ALTER TABLE `pay_moneychange0523` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_moneychange0524`
--

DROP TABLE IF EXISTS `pay_moneychange0524`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_moneychange0524` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '商户编号',
  `ymoney` decimal(15,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '原金额',
  `money` decimal(15,4) NOT NULL DEFAULT '0.0000' COMMENT '变动金额',
  `gmoney` decimal(15,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '变动后金额',
  `datetime` datetime DEFAULT NULL COMMENT '修改时间',
  `transid` varchar(50) DEFAULT NULL COMMENT '交易流水号',
  `tongdao` smallint(6) unsigned DEFAULT '0' COMMENT '支付通道ID',
  `channel_id` int(10) unsigned DEFAULT '0' COMMENT '渠道id',
  `paytype` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '渠道类型: 1 Gcash直连 2 Gcash扫码 3 Maya 4 VietQR',
  `lx` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '类型',
  `t` tinyint(2) NOT NULL DEFAULT '1' COMMENT '结算周期（计算费率）',
  `tcuserid` int(11) DEFAULT NULL,
  `tcdengji` int(11) DEFAULT NULL,
  `orderid` varchar(50) DEFAULT NULL COMMENT '订单号',
  `contentstr` varchar(255) DEFAULT NULL COMMENT '备注',
  `currency` varchar(10) NOT NULL COMMENT '国家货币',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_moneychange0524`
--

LOCK TABLES `pay_moneychange0524` WRITE;
/*!40000 ALTER TABLE `pay_moneychange0524` DISABLE KEYS */;
/*!40000 ALTER TABLE `pay_moneychange0524` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_order`
--

DROP TABLE IF EXISTS `pay_order`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_order` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pay_memberid` int(10) NOT NULL COMMENT '商户编号',
  `pay_orderid` varchar(100) NOT NULL COMMENT '系统订单号',
  `pay_amount` decimal(15,4) unsigned NOT NULL DEFAULT '0.0000',
  `pay_poundage` decimal(15,4) unsigned NOT NULL DEFAULT '0.0000',
  `pay_actualamount` decimal(15,4) unsigned NOT NULL DEFAULT '0.0000',
  `pay_applydate` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '订单创建日期',
  `pay_successdate` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '订单支付成功时间',
  `pay_bankcode` varchar(100) DEFAULT NULL COMMENT '银行编码',
  `pay_bankname` varchar(300) DEFAULT NULL,
  `pay_notifyurl` varchar(500) NOT NULL COMMENT '商家异步通知地址',
  `pay_callbackurl` varchar(500) NOT NULL COMMENT '商家页面通知地址',
  `pay_status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '订单状态: 0 未支付 1 已支付未返回 2 已支付已返回',
  `out_trade_id` varchar(50) NOT NULL COMMENT '商户订单号',
  `pay_productname` varchar(300) DEFAULT NULL COMMENT '商品名称',
  `pay_tongdao` varchar(50) DEFAULT NULL,
  `pay_zh_tongdao` varchar(50) DEFAULT NULL,
  `pay_tjurl` varchar(1000) DEFAULT NULL,
  `paytype` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '渠道类型: 1 Gcash直连 2 Gcash扫码 3 Maya 4 VietQR',
  `num` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '已补发次数',
  `memberid` varchar(100) DEFAULT NULL COMMENT '支付渠道商家号',
  `key` varchar(500) DEFAULT NULL COMMENT '支付渠道密钥',
  `account` varchar(100) DEFAULT NULL COMMENT '渠道账号',
  `isdel` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '伪删除订单 1 删除 0 未删',
  `ddlx` int(11) DEFAULT '0',
  `pay_ytongdao` varchar(50) DEFAULT NULL,
  `pay_yzh_tongdao` varchar(50) DEFAULT NULL,
  `xx` smallint(6) unsigned NOT NULL DEFAULT '0',
  `attach` text CHARACTER SET utf8mb4 COMMENT '商家附加字段,原样返回',
  `pay_channel_account` varchar(255) DEFAULT NULL COMMENT '通道账户',
  `cost` decimal(15,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '成本',
  `cost_rate` decimal(10,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '成本费率',
  `account_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '子账号id',
  `channel_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '渠道id',
  `t` tinyint(2) NOT NULL DEFAULT '1' COMMENT '结算周期（计算费率）',
  `last_reissue_time` int(11) NOT NULL DEFAULT '11' COMMENT '最后补发时间',
  `lock_status` tinyint(1) DEFAULT '0' COMMENT '是否冻结订单，1冻结，2解冻',
  `qrurl` varchar(200) DEFAULT NULL COMMENT '阿里二维码',
  `in_accountname` varchar(255) DEFAULT NULL,
  `in_cardnumber` varchar(255) DEFAULT NULL,
  `in_bankname` varchar(255) DEFAULT NULL,
  `three_orderid` varchar(100) DEFAULT NULL COMMENT '三方订单号',
  `currency` varchar(30) NOT NULL COMMENT '国家货币',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `IND_ORD` (`pay_orderid`) USING BTREE,
  KEY `account_id` (`account_id`) USING BTREE,
  KEY `channel_id` (`channel_id`) USING BTREE,
  KEY `pay_memberid` (`pay_memberid`,`out_trade_id`) USING BTREE,
  KEY `out_trade_id` (`out_trade_id`,`pay_memberid`) USING BTREE,
  KEY `pay_status` (`pay_status`) USING BTREE,
  KEY `applydate` (`pay_applydate`) USING BTREE,
  KEY `successdate` (`pay_successdate`) USING BTREE,
  KEY `lock_status` (`lock_status`) USING BTREE,
  KEY `pay_memberid_2` (`pay_memberid`,`pay_applydate`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_order`
--

LOCK TABLES `pay_order` WRITE;
/*!40000 ALTER TABLE `pay_order` DISABLE KEYS */;
/*!40000 ALTER TABLE `pay_order` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_order0520`
--

DROP TABLE IF EXISTS `pay_order0520`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_order0520` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pay_memberid` int(10) NOT NULL COMMENT '商户编号',
  `pay_orderid` varchar(40) NOT NULL COMMENT '系统订单号',
  `pay_amount` decimal(15,4) unsigned NOT NULL DEFAULT '0.0000',
  `pay_poundage` decimal(15,4) unsigned NOT NULL DEFAULT '0.0000',
  `pay_actualamount` decimal(15,4) unsigned NOT NULL DEFAULT '0.0000',
  `pay_applydate` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '订单创建日期',
  `pay_successdate` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '订单支付成功时间',
  `pay_bankcode` varchar(100) DEFAULT NULL COMMENT '银行编码',
  `pay_bankname` varchar(300) DEFAULT NULL,
  `pay_notifyurl` varchar(500) NOT NULL COMMENT '商家异步通知地址',
  `pay_callbackurl` varchar(500) NOT NULL COMMENT '商家页面通知地址',
  `pay_status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '订单状态: 0 未支付 1 已支付未返回 2 已支付已返回',
  `pay_productname` varchar(300) DEFAULT NULL COMMENT '商品名称',
  `pay_tongdao` varchar(50) DEFAULT NULL,
  `pay_zh_tongdao` varchar(50) DEFAULT NULL,
  `pay_tjurl` varchar(1000) DEFAULT NULL,
  `pay_sxf_type` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '手续费类型：按单笔收费0，按比例收费：1，按单笔+比例收费：2',
  `out_trade_id` varchar(50) NOT NULL COMMENT '商户订单号',
  `billno` varchar(50) DEFAULT NULL COMMENT '上游交易流水号',
  `paytype` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '渠道类型: 1 Gcash直连 2 Gcash扫码 3 Maya 4 VietQR',
  `num` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '已补发次数',
  `memberid` varchar(100) DEFAULT NULL COMMENT '支付渠道商家号',
  `key` varchar(500) DEFAULT NULL COMMENT '支付渠道密钥',
  `account` varchar(100) DEFAULT NULL COMMENT '渠道账号',
  `isdel` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '伪删除订单 1 删除 0 未删',
  `ddlx` int(11) DEFAULT '0',
  `pay_ytongdao` varchar(50) DEFAULT NULL,
  `pay_yzh_tongdao` varchar(50) DEFAULT NULL,
  `xx` smallint(6) unsigned NOT NULL DEFAULT '0',
  `attach` text CHARACTER SET utf8mb4 COMMENT '商家附加字段,原样返回',
  `pay_channel_account` varchar(255) DEFAULT NULL COMMENT '通道账户',
  `qrurl` varchar(200) DEFAULT NULL COMMENT '阿里二维码',
  `cost_type` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '成本类型：按单笔收费0，按比例收费：1，按单笔+比例收费：2',
  `cost` decimal(15,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '成本',
  `cost_rate` decimal(10,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '成本费率',
  `account_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '子账号id',
  `channel_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '渠道id',
  `t` tinyint(2) NOT NULL DEFAULT '1' COMMENT '结算周期（计算费率）',
  `last_reissue_time` int(11) NOT NULL DEFAULT '11' COMMENT '最后补发时间',
  `lock_status` tinyint(1) DEFAULT '0' COMMENT '是否冻结订单，1冻结，2解冻',
  `three_orderid` varchar(40) DEFAULT NULL COMMENT '三方订单号',
  `currency` varchar(30) NOT NULL COMMENT '国家货币',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `IND_ORD` (`pay_orderid`) USING BTREE,
  KEY `account_id` (`account_id`) USING BTREE,
  KEY `channel_id` (`channel_id`) USING BTREE,
  KEY `pay_memberid` (`pay_memberid`,`out_trade_id`) USING BTREE,
  KEY `out_trade_id` (`out_trade_id`,`pay_memberid`) USING BTREE,
  KEY `pay_status` (`pay_status`) USING BTREE,
  KEY `applydate` (`pay_applydate`) USING BTREE,
  KEY `successdate` (`pay_successdate`) USING BTREE,
  KEY `lock_status` (`lock_status`) USING BTREE,
  KEY `pay_memberid_2` (`pay_memberid`,`pay_applydate`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_order0520`
--

LOCK TABLES `pay_order0520` WRITE;
/*!40000 ALTER TABLE `pay_order0520` DISABLE KEYS */;
INSERT INTO `pay_order0520` VALUES (1,10002,'2026052023414153995016470',10.0000,0.0000,10.0000,1779291701,0,'7717','Gcash原生','http://glht.maxpayhk.com/gmtest/server.php','http://glht.maxpayhk.com/gmtest/page.php',0,'Vip基础服务','H88PayGcash','Gcash唤醒--H88Pay','http://glht.maxpayhk.com/',0,'E2026052023413856260',NULL,6,0,'100850','82d3029000a5897ce2e445541cdc471a','',0,0,'H88PayGcash','Gcash-H88Pay',0,'1234|456','100850',NULL,0,0.1200,0.0000,16,14,0,11,0,NULL,'PHP'),(2,10002,'2026052023422650509839831',14.0000,0.0000,14.0000,1779291746,0,'7717','Gcash原生','http://glht.maxpayhk.com/gmtest/server.php','http://glht.maxpayhk.com/gmtest/page.php',0,'Vip基础服务','H88PayGcash','Gcash唤醒--H88Pay','http://glht.maxpayhk.com/',0,'E2026052023422274319',NULL,6,0,'100850','82d3029000a5897ce2e445541cdc471a','',0,0,'H88PayGcash','Gcash-H88Pay',0,'1234|456','100850',NULL,0,0.1680,0.0000,16,14,0,11,0,NULL,'PHP'),(3,10002,'2026052023444499574998893',12.0000,0.0000,12.0000,1779291884,0,'7717','Gcash原生','http://glht.maxpayhk.com/gmtest/server.php','http://glht.maxpayhk.com/gmtest/page.php',0,'Vip基础服务','CeShi','测试通道','http://glht.maxpayhk.com/',0,'E2026052023444273898',NULL,6,0,'cs','','',0,0,'CeShi','测试通道',0,'1234|456','cs',NULL,0,0.1800,0.0000,8,6,0,11,0,NULL,'PHP');
/*!40000 ALTER TABLE `pay_order0520` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_order0521`
--

DROP TABLE IF EXISTS `pay_order0521`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_order0521` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pay_memberid` int(10) NOT NULL COMMENT '商户编号',
  `pay_orderid` varchar(40) NOT NULL COMMENT '系统订单号',
  `pay_amount` decimal(15,4) unsigned NOT NULL DEFAULT '0.0000',
  `pay_poundage` decimal(15,4) unsigned NOT NULL DEFAULT '0.0000',
  `pay_actualamount` decimal(15,4) unsigned NOT NULL DEFAULT '0.0000',
  `pay_applydate` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '订单创建日期',
  `pay_successdate` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '订单支付成功时间',
  `pay_bankcode` varchar(100) DEFAULT NULL COMMENT '银行编码',
  `pay_bankname` varchar(300) DEFAULT NULL,
  `pay_notifyurl` varchar(500) NOT NULL COMMENT '商家异步通知地址',
  `pay_callbackurl` varchar(500) NOT NULL COMMENT '商家页面通知地址',
  `pay_status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '订单状态: 0 未支付 1 已支付未返回 2 已支付已返回',
  `pay_productname` varchar(300) DEFAULT NULL COMMENT '商品名称',
  `pay_tongdao` varchar(50) DEFAULT NULL,
  `pay_zh_tongdao` varchar(50) DEFAULT NULL,
  `pay_tjurl` varchar(1000) DEFAULT NULL,
  `pay_sxf_type` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '手续费类型：按单笔收费0，按比例收费：1，按单笔+比例收费：2',
  `out_trade_id` varchar(50) NOT NULL COMMENT '商户订单号',
  `billno` varchar(50) DEFAULT NULL COMMENT '上游交易流水号',
  `paytype` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '渠道类型: 1 Gcash直连 2 Gcash扫码 3 Maya 4 VietQR',
  `num` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '已补发次数',
  `memberid` varchar(100) DEFAULT NULL COMMENT '支付渠道商家号',
  `key` varchar(500) DEFAULT NULL COMMENT '支付渠道密钥',
  `account` varchar(100) DEFAULT NULL COMMENT '渠道账号',
  `isdel` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '伪删除订单 1 删除 0 未删',
  `ddlx` int(11) DEFAULT '0',
  `pay_ytongdao` varchar(50) DEFAULT NULL,
  `pay_yzh_tongdao` varchar(50) DEFAULT NULL,
  `xx` smallint(6) unsigned NOT NULL DEFAULT '0',
  `attach` text CHARACTER SET utf8mb4 COMMENT '商家附加字段,原样返回',
  `pay_channel_account` varchar(255) DEFAULT NULL COMMENT '通道账户',
  `qrurl` varchar(200) DEFAULT NULL COMMENT '阿里二维码',
  `cost_type` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '成本类型：按单笔收费0，按比例收费：1，按单笔+比例收费：2',
  `cost` decimal(15,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '成本',
  `cost_rate` decimal(10,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '成本费率',
  `account_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '子账号id',
  `channel_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '渠道id',
  `t` tinyint(2) NOT NULL DEFAULT '1' COMMENT '结算周期（计算费率）',
  `last_reissue_time` int(11) NOT NULL DEFAULT '11' COMMENT '最后补发时间',
  `lock_status` tinyint(1) DEFAULT '0' COMMENT '是否冻结订单，1冻结，2解冻',
  `three_orderid` varchar(40) DEFAULT NULL COMMENT '三方订单号',
  `currency` varchar(30) NOT NULL COMMENT '国家货币',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `IND_ORD` (`pay_orderid`) USING BTREE,
  KEY `account_id` (`account_id`) USING BTREE,
  KEY `channel_id` (`channel_id`) USING BTREE,
  KEY `pay_memberid` (`pay_memberid`,`out_trade_id`) USING BTREE,
  KEY `out_trade_id` (`out_trade_id`,`pay_memberid`) USING BTREE,
  KEY `pay_status` (`pay_status`) USING BTREE,
  KEY `applydate` (`pay_applydate`) USING BTREE,
  KEY `successdate` (`pay_successdate`) USING BTREE,
  KEY `lock_status` (`lock_status`) USING BTREE,
  KEY `pay_memberid_2` (`pay_memberid`,`pay_applydate`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_order0521`
--

LOCK TABLES `pay_order0521` WRITE;
/*!40000 ALTER TABLE `pay_order0521` DISABLE KEYS */;
/*!40000 ALTER TABLE `pay_order0521` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_order0522`
--

DROP TABLE IF EXISTS `pay_order0522`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_order0522` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pay_memberid` int(10) NOT NULL COMMENT '商户编号',
  `pay_orderid` varchar(40) NOT NULL COMMENT '系统订单号',
  `pay_amount` decimal(15,4) unsigned NOT NULL DEFAULT '0.0000',
  `pay_poundage` decimal(15,4) unsigned NOT NULL DEFAULT '0.0000',
  `pay_actualamount` decimal(15,4) unsigned NOT NULL DEFAULT '0.0000',
  `pay_applydate` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '订单创建日期',
  `pay_successdate` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '订单支付成功时间',
  `pay_bankcode` varchar(100) DEFAULT NULL COMMENT '银行编码',
  `pay_bankname` varchar(300) DEFAULT NULL,
  `pay_notifyurl` varchar(500) NOT NULL COMMENT '商家异步通知地址',
  `pay_callbackurl` varchar(500) NOT NULL COMMENT '商家页面通知地址',
  `pay_status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '订单状态: 0 未支付 1 已支付未返回 2 已支付已返回',
  `pay_productname` varchar(300) DEFAULT NULL COMMENT '商品名称',
  `pay_tongdao` varchar(50) DEFAULT NULL,
  `pay_zh_tongdao` varchar(50) DEFAULT NULL,
  `pay_tjurl` varchar(1000) DEFAULT NULL,
  `pay_sxf_type` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '手续费类型：按单笔收费0，按比例收费：1，按单笔+比例收费：2',
  `out_trade_id` varchar(50) NOT NULL COMMENT '商户订单号',
  `billno` varchar(50) DEFAULT NULL COMMENT '上游交易流水号',
  `paytype` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '渠道类型: 1 Gcash直连 2 Gcash扫码 3 Maya 4 VietQR',
  `num` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '已补发次数',
  `memberid` varchar(100) DEFAULT NULL COMMENT '支付渠道商家号',
  `key` varchar(500) DEFAULT NULL COMMENT '支付渠道密钥',
  `account` varchar(100) DEFAULT NULL COMMENT '渠道账号',
  `isdel` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '伪删除订单 1 删除 0 未删',
  `ddlx` int(11) DEFAULT '0',
  `pay_ytongdao` varchar(50) DEFAULT NULL,
  `pay_yzh_tongdao` varchar(50) DEFAULT NULL,
  `xx` smallint(6) unsigned NOT NULL DEFAULT '0',
  `attach` text CHARACTER SET utf8mb4 COMMENT '商家附加字段,原样返回',
  `pay_channel_account` varchar(255) DEFAULT NULL COMMENT '通道账户',
  `qrurl` varchar(200) DEFAULT NULL COMMENT '阿里二维码',
  `cost_type` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '成本类型：按单笔收费0，按比例收费：1，按单笔+比例收费：2',
  `cost` decimal(15,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '成本',
  `cost_rate` decimal(10,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '成本费率',
  `account_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '子账号id',
  `channel_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '渠道id',
  `t` tinyint(2) NOT NULL DEFAULT '1' COMMENT '结算周期（计算费率）',
  `last_reissue_time` int(11) NOT NULL DEFAULT '11' COMMENT '最后补发时间',
  `lock_status` tinyint(1) DEFAULT '0' COMMENT '是否冻结订单，1冻结，2解冻',
  `three_orderid` varchar(40) DEFAULT NULL COMMENT '三方订单号',
  `currency` varchar(30) NOT NULL COMMENT '国家货币',
  PRIMARY KEY (`id`),
  UNIQUE KEY `IND_ORD` (`pay_orderid`) USING BTREE,
  KEY `account_id` (`account_id`) USING BTREE,
  KEY `channel_id` (`channel_id`) USING BTREE,
  KEY `pay_memberid` (`pay_memberid`,`out_trade_id`) USING BTREE,
  KEY `out_trade_id` (`out_trade_id`,`pay_memberid`) USING BTREE,
  KEY `pay_status` (`pay_status`) USING BTREE,
  KEY `applydate` (`pay_applydate`) USING BTREE,
  KEY `successdate` (`pay_successdate`) USING BTREE,
  KEY `lock_status` (`lock_status`) USING BTREE,
  KEY `pay_memberid_2` (`pay_memberid`,`pay_applydate`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_order0522`
--

LOCK TABLES `pay_order0522` WRITE;
/*!40000 ALTER TABLE `pay_order0522` DISABLE KEYS */;
/*!40000 ALTER TABLE `pay_order0522` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_order0523`
--

DROP TABLE IF EXISTS `pay_order0523`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_order0523` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pay_memberid` int(10) NOT NULL COMMENT '商户编号',
  `pay_orderid` varchar(40) NOT NULL COMMENT '系统订单号',
  `pay_amount` decimal(15,4) unsigned NOT NULL DEFAULT '0.0000',
  `pay_poundage` decimal(15,4) unsigned NOT NULL DEFAULT '0.0000',
  `pay_actualamount` decimal(15,4) unsigned NOT NULL DEFAULT '0.0000',
  `pay_applydate` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '订单创建日期',
  `pay_successdate` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '订单支付成功时间',
  `pay_bankcode` varchar(100) DEFAULT NULL COMMENT '银行编码',
  `pay_bankname` varchar(300) DEFAULT NULL,
  `pay_notifyurl` varchar(500) NOT NULL COMMENT '商家异步通知地址',
  `pay_callbackurl` varchar(500) NOT NULL COMMENT '商家页面通知地址',
  `pay_status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '订单状态: 0 未支付 1 已支付未返回 2 已支付已返回',
  `pay_productname` varchar(300) DEFAULT NULL COMMENT '商品名称',
  `pay_tongdao` varchar(50) DEFAULT NULL,
  `pay_zh_tongdao` varchar(50) DEFAULT NULL,
  `pay_tjurl` varchar(1000) DEFAULT NULL,
  `pay_sxf_type` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '手续费类型：按单笔收费0，按比例收费：1，按单笔+比例收费：2',
  `out_trade_id` varchar(50) NOT NULL COMMENT '商户订单号',
  `billno` varchar(50) DEFAULT NULL COMMENT '上游交易流水号',
  `paytype` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '渠道类型: 1 Gcash直连 2 Gcash扫码 3 Maya 4 VietQR',
  `num` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '已补发次数',
  `memberid` varchar(100) DEFAULT NULL COMMENT '支付渠道商家号',
  `key` varchar(500) DEFAULT NULL COMMENT '支付渠道密钥',
  `account` varchar(100) DEFAULT NULL COMMENT '渠道账号',
  `isdel` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '伪删除订单 1 删除 0 未删',
  `ddlx` int(11) DEFAULT '0',
  `pay_ytongdao` varchar(50) DEFAULT NULL,
  `pay_yzh_tongdao` varchar(50) DEFAULT NULL,
  `xx` smallint(6) unsigned NOT NULL DEFAULT '0',
  `attach` text CHARACTER SET utf8mb4 COMMENT '商家附加字段,原样返回',
  `pay_channel_account` varchar(255) DEFAULT NULL COMMENT '通道账户',
  `qrurl` varchar(200) DEFAULT NULL COMMENT '阿里二维码',
  `cost_type` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '成本类型：按单笔收费0，按比例收费：1，按单笔+比例收费：2',
  `cost` decimal(15,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '成本',
  `cost_rate` decimal(10,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '成本费率',
  `account_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '子账号id',
  `channel_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '渠道id',
  `t` tinyint(2) NOT NULL DEFAULT '1' COMMENT '结算周期（计算费率）',
  `last_reissue_time` int(11) NOT NULL DEFAULT '11' COMMENT '最后补发时间',
  `lock_status` tinyint(1) DEFAULT '0' COMMENT '是否冻结订单，1冻结，2解冻',
  `three_orderid` varchar(40) DEFAULT NULL COMMENT '三方订单号',
  `currency` varchar(30) NOT NULL COMMENT '国家货币',
  PRIMARY KEY (`id`),
  UNIQUE KEY `IND_ORD` (`pay_orderid`) USING BTREE,
  KEY `account_id` (`account_id`) USING BTREE,
  KEY `channel_id` (`channel_id`) USING BTREE,
  KEY `pay_memberid` (`pay_memberid`,`out_trade_id`) USING BTREE,
  KEY `out_trade_id` (`out_trade_id`,`pay_memberid`) USING BTREE,
  KEY `pay_status` (`pay_status`) USING BTREE,
  KEY `applydate` (`pay_applydate`) USING BTREE,
  KEY `successdate` (`pay_successdate`) USING BTREE,
  KEY `lock_status` (`lock_status`) USING BTREE,
  KEY `pay_memberid_2` (`pay_memberid`,`pay_applydate`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_order0523`
--

LOCK TABLES `pay_order0523` WRITE;
/*!40000 ALTER TABLE `pay_order0523` DISABLE KEYS */;
INSERT INTO `pay_order0523` VALUES (4,10002,'2026052312090953565072515',1.0000,0.0000,1.0000,1779509349,0,'7717','Gcash原生','https://glht.maxpayhk.com/gmtest/server.php','https://glht.maxpayhk.com/gmtest/page.php',0,'Vip基础服务','CeShi','测试通道','https://glht.maxpayhk.com/',0,'E2026052312090645740',NULL,6,0,'cs','','',0,0,'CeShi','测试通道',0,'1234|456','cs',NULL,0,0.0150,0.0000,8,6,0,11,0,NULL,'PHP'),(5,10002,'2026052315051598525777161',10.0000,0.0000,10.0000,1779519915,0,'7717','Gcash原生','https://glht.maxpayhk.com/gmtest/server.php','https://glht.maxpayhk.com/gmtest/page.php',0,'Vip基础服务','GcashHiPay','Gcash唤醒--HiPay','https://glht.maxpayhk.com/',0,'E2026052315043795079',NULL,6,0,'M1765160951','wbLZ58emIy4jamWZrbfvCdyjLTrrynT4aaWR3F9f279tX045eWezCWrd14aauEH0','693637f71496f396ddecbde3',0,0,'GcashHiPay','Gcash-HiPay',0,'1234|456','Maxpay',NULL,0,0.0000,0.0000,9,7,0,11,0,NULL,'PHP'),(6,10002,'2026052315063155991009723',13.0000,0.0000,13.0000,1779519991,0,'7717','Gcash原生','https://glht.maxpayhk.com/gmtest/server.php','https://glht.maxpayhk.com/gmtest/page.php',0,'Vip基础服务','GcashHiPay','Gcash唤醒--HiPay','https://glht.maxpayhk.com/',0,'E2026052315062887999',NULL,6,0,'M1765160951','wbLZ58emIy4jamWZrbfvCdyjLTrrynT4aaWR3F9f279tX045eWezCWrd14aauEH0','693637f71496f396ddecbde3',0,0,'GcashHiPay','Gcash-HiPay',0,'1234|456','Maxpay',NULL,0,0.0000,0.0000,9,7,0,11,0,NULL,'PHP'),(7,10002,'2026052315121510249992283',12.0000,0.0000,12.0000,1779520335,0,'7717','Gcash原生','https://glht.maxpayhk.com/gmtest/server.php','https://glht.maxpayhk.com/gmtest/page.php',0,'Vip基础服务','GcashHiPay','Gcash唤醒--HiPay','https://glht.maxpayhk.com/',0,'E2026052315121158598',NULL,6,0,'M1765160951','wbLZ58emIy4jamWZrbfvCdyjLTrrynT4aaWR3F9f279tX045eWezCWrd14aauEH0','693637f71496f396ddecbde3',0,0,'GcashHiPay','Gcash-HiPay',0,'1234|456','Maxpay',NULL,0,0.0000,0.0000,9,7,0,11,0,NULL,'PHP'),(8,10002,'2026052315535010150568024',100.0000,0.0000,100.0000,1779522830,0,'7717','Gcash原生','https://glht.maxpayhk.com/gmtest/server.php','https://glht.maxpayhk.com/gmtest/page.php',0,'Vip基础服务','GcashHiPay','Gcash唤醒--HiPay','https://glht.maxpayhk.com/',0,'E2026052315534642857',NULL,6,0,'M1765160951','wbLZ58emIy4jamWZrbfvCdyjLTrrynT4aaWR3F9f279tX045eWezCWrd14aauEH0','693637f71496f396ddecbde3',0,0,'GcashHiPay','Gcash-HiPay',0,'1234|456','Maxpay',NULL,0,0.0000,0.0000,9,7,0,11,0,NULL,'PHP'),(9,10002,'2026052315554953509744494',1.0000,0.0000,1.0000,1779522949,0,'7717','Gcash原生','https://glht.maxpayhk.com/gmtest/server.php','https://glht.maxpayhk.com/gmtest/page.php',0,'Vip基础服务','GcashHiPay222','Gcash唤醒222--HiPay','https://glht.maxpayhk.com/',0,'E2026052315554673248',NULL,6,0,'M1762263548','6VY9zWww8sp9aHcwzBCU1gXXIgM8qZgSl1Xt2NKdfRTXtRYEvFlYjpRyvRBhXedE','690a01fc1496402801660069',0,0,'GcashHiPay222','Gcash-HiPay',0,'1234|456','Maxpay2',NULL,0,0.0000,0.0000,10,8,0,11,0,NULL,'PHP'),(10,10002,'2026052315562052561066470',100.0000,0.0000,100.0000,1779522980,1779523073,'7717','Gcash原生','https://glht.maxpayhk.com/gmtest/server.php','https://glht.maxpayhk.com/gmtest/page.php',2,'Vip基础服务','GcashHiPay222','Gcash唤醒222--HiPay','https://glht.maxpayhk.com/',0,'E2026052315561468824',NULL,6,5,'M1762263548','6VY9zWww8sp9aHcwzBCU1gXXIgM8qZgSl1Xt2NKdfRTXtRYEvFlYjpRyvRBhXedE','690a01fc1496402801660069',0,0,'GcashHiPay222','Gcash-HiPay',0,'1234|456','Maxpay2',NULL,0,0.0000,0.0000,10,8,0,1779523321,0,NULL,'PHP');
/*!40000 ALTER TABLE `pay_order0523` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_order0524`
--

DROP TABLE IF EXISTS `pay_order0524`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_order0524` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pay_memberid` int(10) NOT NULL COMMENT '商户编号',
  `pay_orderid` varchar(40) NOT NULL COMMENT '系统订单号',
  `pay_amount` decimal(15,4) unsigned NOT NULL DEFAULT '0.0000',
  `pay_poundage` decimal(15,4) unsigned NOT NULL DEFAULT '0.0000',
  `pay_actualamount` decimal(15,4) unsigned NOT NULL DEFAULT '0.0000',
  `pay_applydate` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '订单创建日期',
  `pay_successdate` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '订单支付成功时间',
  `pay_bankcode` varchar(100) DEFAULT NULL COMMENT '银行编码',
  `pay_bankname` varchar(300) DEFAULT NULL,
  `pay_notifyurl` varchar(500) NOT NULL COMMENT '商家异步通知地址',
  `pay_callbackurl` varchar(500) NOT NULL COMMENT '商家页面通知地址',
  `pay_status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '订单状态: 0 未支付 1 已支付未返回 2 已支付已返回',
  `pay_productname` varchar(300) DEFAULT NULL COMMENT '商品名称',
  `pay_tongdao` varchar(50) DEFAULT NULL,
  `pay_zh_tongdao` varchar(50) DEFAULT NULL,
  `pay_tjurl` varchar(1000) DEFAULT NULL,
  `pay_sxf_type` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '手续费类型：按单笔收费0，按比例收费：1，按单笔+比例收费：2',
  `out_trade_id` varchar(50) NOT NULL COMMENT '商户订单号',
  `billno` varchar(50) DEFAULT NULL COMMENT '上游交易流水号',
  `paytype` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '渠道类型: 1 Gcash直连 2 Gcash扫码 3 Maya 4 VietQR',
  `num` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '已补发次数',
  `memberid` varchar(100) DEFAULT NULL COMMENT '支付渠道商家号',
  `key` varchar(500) DEFAULT NULL COMMENT '支付渠道密钥',
  `account` varchar(100) DEFAULT NULL COMMENT '渠道账号',
  `isdel` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '伪删除订单 1 删除 0 未删',
  `ddlx` int(11) DEFAULT '0',
  `pay_ytongdao` varchar(50) DEFAULT NULL,
  `pay_yzh_tongdao` varchar(50) DEFAULT NULL,
  `xx` smallint(6) unsigned NOT NULL DEFAULT '0',
  `attach` text CHARACTER SET utf8mb4 COMMENT '商家附加字段,原样返回',
  `pay_channel_account` varchar(255) DEFAULT NULL COMMENT '通道账户',
  `qrurl` varchar(200) DEFAULT NULL COMMENT '阿里二维码',
  `cost_type` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '成本类型：按单笔收费0，按比例收费：1，按单笔+比例收费：2',
  `cost` decimal(15,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '成本',
  `cost_rate` decimal(10,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '成本费率',
  `account_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '子账号id',
  `channel_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '渠道id',
  `t` tinyint(2) NOT NULL DEFAULT '1' COMMENT '结算周期（计算费率）',
  `last_reissue_time` int(11) NOT NULL DEFAULT '11' COMMENT '最后补发时间',
  `lock_status` tinyint(1) DEFAULT '0' COMMENT '是否冻结订单，1冻结，2解冻',
  `three_orderid` varchar(40) DEFAULT NULL COMMENT '三方订单号',
  `currency` varchar(30) NOT NULL COMMENT '国家货币',
  PRIMARY KEY (`id`),
  UNIQUE KEY `IND_ORD` (`pay_orderid`) USING BTREE,
  KEY `account_id` (`account_id`) USING BTREE,
  KEY `channel_id` (`channel_id`) USING BTREE,
  KEY `pay_memberid` (`pay_memberid`,`out_trade_id`) USING BTREE,
  KEY `out_trade_id` (`out_trade_id`,`pay_memberid`) USING BTREE,
  KEY `pay_status` (`pay_status`) USING BTREE,
  KEY `applydate` (`pay_applydate`) USING BTREE,
  KEY `successdate` (`pay_successdate`) USING BTREE,
  KEY `lock_status` (`lock_status`) USING BTREE,
  KEY `pay_memberid_2` (`pay_memberid`,`pay_applydate`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_order0524`
--

LOCK TABLES `pay_order0524` WRITE;
/*!40000 ALTER TABLE `pay_order0524` DISABLE KEYS */;
/*!40000 ALTER TABLE `pay_order0524` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_order0525`
--

DROP TABLE IF EXISTS `pay_order0525`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_order0525` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pay_memberid` int(10) NOT NULL COMMENT '商户编号',
  `pay_orderid` varchar(40) NOT NULL COMMENT '系统订单号',
  `pay_amount` decimal(15,4) unsigned NOT NULL DEFAULT '0.0000',
  `pay_poundage` decimal(15,4) unsigned NOT NULL DEFAULT '0.0000',
  `pay_actualamount` decimal(15,4) unsigned NOT NULL DEFAULT '0.0000',
  `pay_applydate` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '订单创建日期',
  `pay_successdate` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '订单支付成功时间',
  `pay_bankcode` varchar(100) DEFAULT NULL COMMENT '银行编码',
  `pay_bankname` varchar(300) DEFAULT NULL,
  `pay_notifyurl` varchar(500) NOT NULL COMMENT '商家异步通知地址',
  `pay_callbackurl` varchar(500) NOT NULL COMMENT '商家页面通知地址',
  `pay_status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '订单状态: 0 未支付 1 已支付未返回 2 已支付已返回',
  `pay_productname` varchar(300) DEFAULT NULL COMMENT '商品名称',
  `pay_tongdao` varchar(50) DEFAULT NULL,
  `pay_zh_tongdao` varchar(50) DEFAULT NULL,
  `pay_tjurl` varchar(1000) DEFAULT NULL,
  `pay_sxf_type` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '手续费类型：按单笔收费0，按比例收费：1，按单笔+比例收费：2',
  `out_trade_id` varchar(50) NOT NULL COMMENT '商户订单号',
  `billno` varchar(50) DEFAULT NULL COMMENT '上游交易流水号',
  `paytype` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '渠道类型: 1 Gcash直连 2 Gcash扫码 3 Maya 4 VietQR',
  `num` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '已补发次数',
  `memberid` varchar(100) DEFAULT NULL COMMENT '支付渠道商家号',
  `key` varchar(500) DEFAULT NULL COMMENT '支付渠道密钥',
  `account` varchar(100) DEFAULT NULL COMMENT '渠道账号',
  `isdel` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '伪删除订单 1 删除 0 未删',
  `ddlx` int(11) DEFAULT '0',
  `pay_ytongdao` varchar(50) DEFAULT NULL,
  `pay_yzh_tongdao` varchar(50) DEFAULT NULL,
  `xx` smallint(6) unsigned NOT NULL DEFAULT '0',
  `attach` text CHARACTER SET utf8mb4 COMMENT '商家附加字段,原样返回',
  `pay_channel_account` varchar(255) DEFAULT NULL COMMENT '通道账户',
  `qrurl` varchar(200) DEFAULT NULL COMMENT '阿里二维码',
  `cost_type` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '成本类型：按单笔收费0，按比例收费：1，按单笔+比例收费：2',
  `cost` decimal(15,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '成本',
  `cost_rate` decimal(10,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '成本费率',
  `account_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '子账号id',
  `channel_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '渠道id',
  `t` tinyint(2) NOT NULL DEFAULT '1' COMMENT '结算周期（计算费率）',
  `last_reissue_time` int(11) NOT NULL DEFAULT '11' COMMENT '最后补发时间',
  `lock_status` tinyint(1) DEFAULT '0' COMMENT '是否冻结订单，1冻结，2解冻',
  `three_orderid` varchar(40) DEFAULT NULL COMMENT '三方订单号',
  `currency` varchar(30) NOT NULL COMMENT '国家货币',
  PRIMARY KEY (`id`),
  UNIQUE KEY `IND_ORD` (`pay_orderid`) USING BTREE,
  KEY `account_id` (`account_id`) USING BTREE,
  KEY `channel_id` (`channel_id`) USING BTREE,
  KEY `pay_memberid` (`pay_memberid`,`out_trade_id`) USING BTREE,
  KEY `out_trade_id` (`out_trade_id`,`pay_memberid`) USING BTREE,
  KEY `pay_status` (`pay_status`) USING BTREE,
  KEY `applydate` (`pay_applydate`) USING BTREE,
  KEY `successdate` (`pay_successdate`) USING BTREE,
  KEY `lock_status` (`lock_status`) USING BTREE,
  KEY `pay_memberid_2` (`pay_memberid`,`pay_applydate`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_order0525`
--

LOCK TABLES `pay_order0525` WRITE;
/*!40000 ALTER TABLE `pay_order0525` DISABLE KEYS */;
/*!40000 ALTER TABLE `pay_order0525` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_order0526`
--

DROP TABLE IF EXISTS `pay_order0526`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_order0526` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pay_memberid` int(10) NOT NULL COMMENT '商户编号',
  `pay_orderid` varchar(40) NOT NULL COMMENT '系统订单号',
  `pay_amount` decimal(15,4) unsigned NOT NULL DEFAULT '0.0000',
  `pay_poundage` decimal(15,4) unsigned NOT NULL DEFAULT '0.0000',
  `pay_actualamount` decimal(15,4) unsigned NOT NULL DEFAULT '0.0000',
  `pay_applydate` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '订单创建日期',
  `pay_successdate` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '订单支付成功时间',
  `pay_bankcode` varchar(100) DEFAULT NULL COMMENT '银行编码',
  `pay_bankname` varchar(300) DEFAULT NULL,
  `pay_notifyurl` varchar(500) NOT NULL COMMENT '商家异步通知地址',
  `pay_callbackurl` varchar(500) NOT NULL COMMENT '商家页面通知地址',
  `pay_status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '订单状态: 0 未支付 1 已支付未返回 2 已支付已返回',
  `pay_productname` varchar(300) DEFAULT NULL COMMENT '商品名称',
  `pay_tongdao` varchar(50) DEFAULT NULL,
  `pay_zh_tongdao` varchar(50) DEFAULT NULL,
  `pay_tjurl` varchar(1000) DEFAULT NULL,
  `pay_sxf_type` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '手续费类型：按单笔收费0，按比例收费：1，按单笔+比例收费：2',
  `out_trade_id` varchar(50) NOT NULL COMMENT '商户订单号',
  `billno` varchar(50) DEFAULT NULL COMMENT '上游交易流水号',
  `paytype` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '渠道类型: 1 Gcash直连 2 Gcash扫码 3 Maya 4 VietQR',
  `num` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '已补发次数',
  `memberid` varchar(100) DEFAULT NULL COMMENT '支付渠道商家号',
  `key` varchar(500) DEFAULT NULL COMMENT '支付渠道密钥',
  `account` varchar(100) DEFAULT NULL COMMENT '渠道账号',
  `isdel` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '伪删除订单 1 删除 0 未删',
  `ddlx` int(11) DEFAULT '0',
  `pay_ytongdao` varchar(50) DEFAULT NULL,
  `pay_yzh_tongdao` varchar(50) DEFAULT NULL,
  `xx` smallint(6) unsigned NOT NULL DEFAULT '0',
  `attach` text CHARACTER SET utf8mb4 COMMENT '商家附加字段,原样返回',
  `pay_channel_account` varchar(255) DEFAULT NULL COMMENT '通道账户',
  `qrurl` varchar(200) DEFAULT NULL COMMENT '阿里二维码',
  `cost_type` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '成本类型：按单笔收费0，按比例收费：1，按单笔+比例收费：2',
  `cost` decimal(15,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '成本',
  `cost_rate` decimal(10,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '成本费率',
  `account_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '子账号id',
  `channel_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '渠道id',
  `t` tinyint(2) NOT NULL DEFAULT '1' COMMENT '结算周期（计算费率）',
  `last_reissue_time` int(11) NOT NULL DEFAULT '11' COMMENT '最后补发时间',
  `lock_status` tinyint(1) DEFAULT '0' COMMENT '是否冻结订单，1冻结，2解冻',
  `three_orderid` varchar(40) DEFAULT NULL COMMENT '三方订单号',
  `currency` varchar(30) NOT NULL COMMENT '国家货币',
  PRIMARY KEY (`id`),
  UNIQUE KEY `IND_ORD` (`pay_orderid`) USING BTREE,
  KEY `account_id` (`account_id`) USING BTREE,
  KEY `channel_id` (`channel_id`) USING BTREE,
  KEY `pay_memberid` (`pay_memberid`,`out_trade_id`) USING BTREE,
  KEY `out_trade_id` (`out_trade_id`,`pay_memberid`) USING BTREE,
  KEY `pay_status` (`pay_status`) USING BTREE,
  KEY `applydate` (`pay_applydate`) USING BTREE,
  KEY `successdate` (`pay_successdate`) USING BTREE,
  KEY `lock_status` (`lock_status`) USING BTREE,
  KEY `pay_memberid_2` (`pay_memberid`,`pay_applydate`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_order0526`
--

LOCK TABLES `pay_order0526` WRITE;
/*!40000 ALTER TABLE `pay_order0526` DISABLE KEYS */;
/*!40000 ALTER TABLE `pay_order0526` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_order0527`
--

DROP TABLE IF EXISTS `pay_order0527`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_order0527` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pay_memberid` int(10) NOT NULL COMMENT '商户编号',
  `pay_orderid` varchar(40) NOT NULL COMMENT '系统订单号',
  `pay_amount` decimal(15,4) unsigned NOT NULL DEFAULT '0.0000',
  `pay_poundage` decimal(15,4) unsigned NOT NULL DEFAULT '0.0000',
  `pay_actualamount` decimal(15,4) unsigned NOT NULL DEFAULT '0.0000',
  `pay_applydate` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '订单创建日期',
  `pay_successdate` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '订单支付成功时间',
  `pay_bankcode` varchar(100) DEFAULT NULL COMMENT '银行编码',
  `pay_bankname` varchar(300) DEFAULT NULL,
  `pay_notifyurl` varchar(500) NOT NULL COMMENT '商家异步通知地址',
  `pay_callbackurl` varchar(500) NOT NULL COMMENT '商家页面通知地址',
  `pay_status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '订单状态: 0 未支付 1 已支付未返回 2 已支付已返回',
  `pay_productname` varchar(300) DEFAULT NULL COMMENT '商品名称',
  `pay_tongdao` varchar(50) DEFAULT NULL,
  `pay_zh_tongdao` varchar(50) DEFAULT NULL,
  `pay_tjurl` varchar(1000) DEFAULT NULL,
  `pay_sxf_type` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '手续费类型：按单笔收费0，按比例收费：1，按单笔+比例收费：2',
  `out_trade_id` varchar(50) NOT NULL COMMENT '商户订单号',
  `billno` varchar(50) DEFAULT NULL COMMENT '上游交易流水号',
  `paytype` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '渠道类型: 1 Gcash直连 2 Gcash扫码 3 Maya 4 VietQR',
  `num` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '已补发次数',
  `memberid` varchar(100) DEFAULT NULL COMMENT '支付渠道商家号',
  `key` varchar(500) DEFAULT NULL COMMENT '支付渠道密钥',
  `account` varchar(100) DEFAULT NULL COMMENT '渠道账号',
  `isdel` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '伪删除订单 1 删除 0 未删',
  `ddlx` int(11) DEFAULT '0',
  `pay_ytongdao` varchar(50) DEFAULT NULL,
  `pay_yzh_tongdao` varchar(50) DEFAULT NULL,
  `xx` smallint(6) unsigned NOT NULL DEFAULT '0',
  `attach` text CHARACTER SET utf8mb4 COMMENT '商家附加字段,原样返回',
  `pay_channel_account` varchar(255) DEFAULT NULL COMMENT '通道账户',
  `qrurl` varchar(200) DEFAULT NULL COMMENT '阿里二维码',
  `cost_type` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '成本类型：按单笔收费0，按比例收费：1，按单笔+比例收费：2',
  `cost` decimal(15,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '成本',
  `cost_rate` decimal(10,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '成本费率',
  `account_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '子账号id',
  `channel_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '渠道id',
  `t` tinyint(2) NOT NULL DEFAULT '1' COMMENT '结算周期（计算费率）',
  `last_reissue_time` int(11) NOT NULL DEFAULT '11' COMMENT '最后补发时间',
  `lock_status` tinyint(1) DEFAULT '0' COMMENT '是否冻结订单，1冻结，2解冻',
  `three_orderid` varchar(40) DEFAULT NULL COMMENT '三方订单号',
  `currency` varchar(30) NOT NULL COMMENT '国家货币',
  PRIMARY KEY (`id`),
  UNIQUE KEY `IND_ORD` (`pay_orderid`) USING BTREE,
  KEY `account_id` (`account_id`) USING BTREE,
  KEY `channel_id` (`channel_id`) USING BTREE,
  KEY `pay_memberid` (`pay_memberid`,`out_trade_id`) USING BTREE,
  KEY `out_trade_id` (`out_trade_id`,`pay_memberid`) USING BTREE,
  KEY `pay_status` (`pay_status`) USING BTREE,
  KEY `applydate` (`pay_applydate`) USING BTREE,
  KEY `successdate` (`pay_successdate`) USING BTREE,
  KEY `lock_status` (`lock_status`) USING BTREE,
  KEY `pay_memberid_2` (`pay_memberid`,`pay_applydate`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_order0527`
--

LOCK TABLES `pay_order0527` WRITE;
/*!40000 ALTER TABLE `pay_order0527` DISABLE KEYS */;
/*!40000 ALTER TABLE `pay_order0527` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_order0528`
--

DROP TABLE IF EXISTS `pay_order0528`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_order0528` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pay_memberid` int(10) NOT NULL COMMENT '商户编号',
  `pay_orderid` varchar(40) NOT NULL COMMENT '系统订单号',
  `pay_amount` decimal(15,4) unsigned NOT NULL DEFAULT '0.0000',
  `pay_poundage` decimal(15,4) unsigned NOT NULL DEFAULT '0.0000',
  `pay_actualamount` decimal(15,4) unsigned NOT NULL DEFAULT '0.0000',
  `pay_applydate` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '订单创建日期',
  `pay_successdate` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '订单支付成功时间',
  `pay_bankcode` varchar(100) DEFAULT NULL COMMENT '银行编码',
  `pay_bankname` varchar(300) DEFAULT NULL,
  `pay_notifyurl` varchar(500) NOT NULL COMMENT '商家异步通知地址',
  `pay_callbackurl` varchar(500) NOT NULL COMMENT '商家页面通知地址',
  `pay_status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '订单状态: 0 未支付 1 已支付未返回 2 已支付已返回',
  `pay_productname` varchar(300) DEFAULT NULL COMMENT '商品名称',
  `pay_tongdao` varchar(50) DEFAULT NULL,
  `pay_zh_tongdao` varchar(50) DEFAULT NULL,
  `pay_tjurl` varchar(1000) DEFAULT NULL,
  `pay_sxf_type` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '手续费类型：按单笔收费0，按比例收费：1，按单笔+比例收费：2',
  `out_trade_id` varchar(50) NOT NULL COMMENT '商户订单号',
  `billno` varchar(50) DEFAULT NULL COMMENT '上游交易流水号',
  `paytype` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '渠道类型: 1 Gcash直连 2 Gcash扫码 3 Maya 4 VietQR',
  `num` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '已补发次数',
  `memberid` varchar(100) DEFAULT NULL COMMENT '支付渠道商家号',
  `key` varchar(500) DEFAULT NULL COMMENT '支付渠道密钥',
  `account` varchar(100) DEFAULT NULL COMMENT '渠道账号',
  `isdel` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '伪删除订单 1 删除 0 未删',
  `ddlx` int(11) DEFAULT '0',
  `pay_ytongdao` varchar(50) DEFAULT NULL,
  `pay_yzh_tongdao` varchar(50) DEFAULT NULL,
  `xx` smallint(6) unsigned NOT NULL DEFAULT '0',
  `attach` text CHARACTER SET utf8mb4 COMMENT '商家附加字段,原样返回',
  `pay_channel_account` varchar(255) DEFAULT NULL COMMENT '通道账户',
  `qrurl` varchar(200) DEFAULT NULL COMMENT '阿里二维码',
  `cost_type` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '成本类型：按单笔收费0，按比例收费：1，按单笔+比例收费：2',
  `cost` decimal(15,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '成本',
  `cost_rate` decimal(10,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '成本费率',
  `account_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '子账号id',
  `channel_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '渠道id',
  `t` tinyint(2) NOT NULL DEFAULT '1' COMMENT '结算周期（计算费率）',
  `last_reissue_time` int(11) NOT NULL DEFAULT '11' COMMENT '最后补发时间',
  `lock_status` tinyint(1) DEFAULT '0' COMMENT '是否冻结订单，1冻结，2解冻',
  `three_orderid` varchar(40) DEFAULT NULL COMMENT '三方订单号',
  `currency` varchar(30) NOT NULL COMMENT '国家货币',
  PRIMARY KEY (`id`),
  UNIQUE KEY `IND_ORD` (`pay_orderid`) USING BTREE,
  KEY `account_id` (`account_id`) USING BTREE,
  KEY `channel_id` (`channel_id`) USING BTREE,
  KEY `pay_memberid` (`pay_memberid`,`out_trade_id`) USING BTREE,
  KEY `out_trade_id` (`out_trade_id`,`pay_memberid`) USING BTREE,
  KEY `pay_status` (`pay_status`) USING BTREE,
  KEY `applydate` (`pay_applydate`) USING BTREE,
  KEY `successdate` (`pay_successdate`) USING BTREE,
  KEY `lock_status` (`lock_status`) USING BTREE,
  KEY `pay_memberid_2` (`pay_memberid`,`pay_applydate`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_order0528`
--

LOCK TABLES `pay_order0528` WRITE;
/*!40000 ALTER TABLE `pay_order0528` DISABLE KEYS */;
/*!40000 ALTER TABLE `pay_order0528` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_pay_channel_account`
--

DROP TABLE IF EXISTS `pay_pay_channel_account`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_pay_channel_account` (
  `id` smallint(6) unsigned NOT NULL AUTO_INCREMENT COMMENT '供应商通道账号ID',
  `channel_id` smallint(6) unsigned NOT NULL COMMENT '通道id',
  `mch_id` varchar(100) DEFAULT NULL COMMENT '商户号',
  `signkey` varchar(500) DEFAULT NULL COMMENT '签文密钥',
  `subappid` varchar(100) DEFAULT NULL COMMENT '多级代理商编号',
  `appid` varchar(100) DEFAULT NULL COMMENT '应用APPID',
  `appsecret` varchar(2500) DEFAULT NULL COMMENT '安全密钥',
  `defaultrate` decimal(10,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '下家费率',
  `fengding` decimal(10,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '封顶手续费',
  `rate` decimal(10,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '银行费率',
  `updatetime` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '上次更改时间',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '状态 1开启 0关闭',
  `title` varchar(100) DEFAULT NULL COMMENT '账户标题',
  `weight` tinyint(2) DEFAULT NULL COMMENT '轮询权重',
  `custom_rate` tinyint(1) DEFAULT NULL COMMENT '是否自定义费率',
  `start_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '开始交易时间',
  `end_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '结束时间',
  `last_paying_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '最后一笔交易时间',
  `paying_money` decimal(11,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '当天交易金额',
  `all_money` decimal(11,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '单日可交易金额',
  `max_money` decimal(11,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '单笔交易最大金额',
  `min_money` decimal(11,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '单笔交易最小金额',
  `offline_status` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '上线状态-1上线,0下线',
  `control_status` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '风控状态-0不风控,1风控中',
  `is_defined` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '是否自定义:1-是,0-否',
  `unit_frist_paying_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '单位时间第一笔交易时间',
  `unit_paying_number` mediumint(8) unsigned NOT NULL DEFAULT '0' COMMENT '单时间交易笔数',
  `unit_paying_amount` decimal(11,0) unsigned NOT NULL DEFAULT '0' COMMENT '单位时间交易金额',
  `unit_interval` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '单位时间数值',
  `time_unit` char(1) NOT NULL DEFAULT 's' COMMENT '限制时间单位',
  `unit_number` mediumint(8) unsigned NOT NULL DEFAULT '0' COMMENT '单位时间次数',
  `unit_all_money` decimal(11,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '单位时间金额',
  `t0defaultrate` decimal(10,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT 'T0运营费率',
  `t0fengding` decimal(10,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT 'T0封顶手续费',
  `t0rate` decimal(10,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT 'T0成本费率',
  `unlockdomain` varchar(255) NOT NULL COMMENT '防封域名',
  `success_num` decimal(10,0) NOT NULL DEFAULT '0' COMMENT '成功次数',
  `fail_num` decimal(10,0) NOT NULL DEFAULT '0' COMMENT '失败次数',
  `fail_rounds` decimal(10,0) NOT NULL DEFAULT '0' COMMENT '失败轮数',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='供应商账号列表';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_pay_channel_account`
--

LOCK TABLES `pay_pay_channel_account` WRITE;
/*!40000 ALTER TABLE `pay_pay_channel_account` DISABLE KEYS */;
/*!40000 ALTER TABLE `pay_pay_channel_account` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_pay_channel_extend_fields`
--

DROP TABLE IF EXISTS `pay_pay_channel_extend_fields`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_pay_channel_extend_fields` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `channel_id` int(11) NOT NULL DEFAULT '0' COMMENT '代付渠道ID',
  `code` varchar(64) NOT NULL DEFAULT '' COMMENT '代付渠道代码',
  `name` varchar(50) NOT NULL DEFAULT '' COMMENT '扩展字段名',
  `alias` varchar(50) NOT NULL DEFAULT '' COMMENT '扩展字段别名',
  `remark` varchar(255) NOT NULL DEFAULT '' COMMENT '备注',
  `etime` int(11) NOT NULL DEFAULT '0' COMMENT '修改时间',
  `ctime` int(11) NOT NULL DEFAULT '0' COMMENT '添加时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_pay_channel_extend_fields`
--

LOCK TABLES `pay_pay_channel_extend_fields` WRITE;
/*!40000 ALTER TABLE `pay_pay_channel_extend_fields` DISABLE KEYS */;
/*!40000 ALTER TABLE `pay_pay_channel_extend_fields` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_pay_for_another`
--

DROP TABLE IF EXISTS `pay_pay_for_another`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_pay_for_another` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `code` varchar(64) NOT NULL COMMENT '代付代码',
  `title` varchar(64) NOT NULL COMMENT '代付名称',
  `mch_id` varchar(255) NOT NULL DEFAULT ' ' COMMENT '商户号',
  `appid` varchar(100) NOT NULL DEFAULT ' ' COMMENT '应用APPID',
  `appsecret` varchar(100) NOT NULL DEFAULT ' ' COMMENT '应用密钥',
  `signkey` varchar(500) NOT NULL DEFAULT ' ' COMMENT '加密的秘钥',
  `public_key` varchar(1000) NOT NULL DEFAULT '  ' COMMENT '加密的公钥',
  `private_key` varchar(1000) NOT NULL DEFAULT '  ' COMMENT '加密的私钥',
  `exec_gateway` varchar(255) NOT NULL DEFAULT ' ' COMMENT '请求代付的地址',
  `query_gateway` varchar(255) NOT NULL DEFAULT ' ' COMMENT '查询代付的地址',
  `serverreturn` varchar(255) NOT NULL DEFAULT ' ' COMMENT '服务器通知网址',
  `unlockdomain` varchar(255) NOT NULL DEFAULT ' ' COMMENT '防封域名',
  `updatetime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更改时间',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '状态 1开启 0关闭',
  `is_default` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '是否默认：1是，0否',
  `cost_rate` decimal(10,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '成本费率',
  `rate_type` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '费率类型：按单笔收费0，按比例收费：1, 比例+单笔：2',
  `paytype` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '渠道类型: 1 Gcash直连 2 Gcash扫码 3 Maya 4 VietQR',
  `dan_bi` decimal(10,2) DEFAULT '0.00' COMMENT '单笔',
  `open_query` tinyint(1) DEFAULT '0' COMMENT '是否开启查询：0：关闭，1：开启',
  `balance` decimal(15,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '手续费余额',
  `blockedbalance` decimal(15,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '可用余额',
  `currency` varchar(10) NOT NULL COMMENT '国家货币',
  `notifyIP` varchar(1000) NOT NULL DEFAULT ' ' COMMENT '白名单IP',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `code` (`code`) USING BTREE,
  KEY `updatetime` (`updatetime`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='代付通道表';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_pay_for_another`
--

LOCK TABLES `pay_pay_for_another` WRITE;
/*!40000 ALTER TABLE `pay_pay_for_another` DISABLE KEYS */;
INSERT INTO `pay_pay_for_another` VALUES (5,'CeShiDF','测试通道--代付(IDR)','','','','','','','','','','',1766061714,1,0,0.0000,0,25,0.00,0,0.00,0.00,'IDR',' '),(6,'CeShiDF','测试通道--代付(PHP)','','','','','','','','','','',1775387392,1,0,0.0000,0,6,0.00,0,0.00,0.00,'PHP',' '),(7,'CeShiDF','测试通道--代付(PAK)','','','','','','','','','','',1762358298,1,0,0.0000,0,5,0.00,0,0.00,0.00,'PKR',' '),(8,'CeShiDF','测试通道--代付(BRL)','','','','','','','','','','',1762358304,0,0,0.0000,0,1,0.00,0,0.00,0.00,'BRL',' '),(9,'CeShiDF','测试通道--代付(VN)','','','','','','','','','','',1766061754,1,0,0.0000,0,18,0.00,0,0.00,0.00,'VND',' '),(10,'CeShiDF','测试通道--代付(MX)','','','','','','','','','','',1762358323,0,0,0.0000,0,4,0.00,0,0.00,0.00,'MXN',' '),(11,'HiPayDFGcash','HiPay代付--Gcash原生','M1765160951','693637f71496f396ddecbde3','','wbLZ58emIy4jamWZrbfvCdyjLTrrynT4aaWR3F9f279tX045eWezCWrd14aauEH0','','','https://api.payhipay.com/api/transferOrder','https://api.payhipay.com/api/transfer/query','https://api.payhipay.com/api/balance/query','',1779558801,1,0,0.0000,0,6,4.00,0,0.00,0.00,'PHP','8.219.113.100,47.245.95.80'),(12,'HiPayDFGcash222','HiPay代付--Gcash原生222','M1762263548','690a01fc1496402801660069','','6VY9zWww8sp9aHcwzBCU1gXXIgM8qZgSl1Xt2NKdfRTXtRYEvFlYjpRyvRBhXedE','','','https://api.payhipay.com/api/transferOrder','https://api.payhipay.com/api/transfer/query','https://api.payhipay.com/api/balance/query','',1779558733,1,0,0.0000,0,6,4.00,0,0.00,0.00,'PHP','8.219.113.100,47.245.95.80');
/*!40000 ALTER TABLE `pay_pay_for_another` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_paylog`
--

DROP TABLE IF EXISTS `pay_paylog`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_paylog` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `out_trade_no` varchar(50) NOT NULL,
  `result_code` varchar(50) NOT NULL,
  `transaction_id` varchar(50) NOT NULL,
  `fromuser` varchar(50) NOT NULL,
  `time_end` int(11) unsigned NOT NULL DEFAULT '0',
  `total_fee` smallint(6) unsigned NOT NULL DEFAULT '0',
  `payname` varchar(50) NOT NULL,
  `bank_type` varchar(20) DEFAULT NULL,
  `trade_type` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `IND_TRD` (`transaction_id`) USING BTREE,
  UNIQUE KEY `IND_ORD` (`out_trade_no`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_paylog`
--

LOCK TABLES `pay_paylog` WRITE;
/*!40000 ALTER TABLE `pay_paylog` DISABLE KEYS */;
/*!40000 ALTER TABLE `pay_paylog` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_product`
--

DROP TABLE IF EXISTS `pay_product`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_product` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL COMMENT '通道名称',
  `code` varchar(50) NOT NULL COMMENT '通道代码',
  `polling` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '接口模式 0 单独 1 轮询',
  `paytype` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '渠道类型: 1 Gcash直连 2 Gcash扫码 3 Maya 4 VietQR',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '状态',
  `isdisplay` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '用户端显示 1 显示 0 不显示',
  `channel` smallint(6) unsigned NOT NULL DEFAULT '0' COMMENT '通道ID',
  `weight` text COMMENT '平台默认通道权重',
  `t0defaultrate` decimal(10,4) NOT NULL DEFAULT '0.0000' COMMENT 'T0运营费率',
  `t0fengding` decimal(10,4) NOT NULL DEFAULT '0.0000' COMMENT 'T0封顶手续费',
  `defaultrate` decimal(10,4) NOT NULL DEFAULT '0.0000' COMMENT 'T1运营费率',
  `fengding` decimal(10,4) NOT NULL DEFAULT '0.0000' COMMENT 'T1封顶手续费',
  `currency` varchar(10) NOT NULL COMMENT '国家货币',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=7732 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_product`
--

LOCK TABLES `pay_product` WRITE;
/*!40000 ALTER TABLE `pay_product` DISABLE KEYS */;
INSERT INTO `pay_product` VALUES (7710,'巴西H5','H5',0,1,0,0,0,'',0.0000,0.0000,0.0000,0.0000,'BRL'),(7711,'巴西QR','QRcode',0,2,0,0,0,'',0.0000,0.0000,0.0000,0.0000,'BRL'),(7712,'巴西H5--CPF','H5',0,1,0,0,0,'',0.0000,0.0000,0.0000,0.0000,'BRL'),(7713,'巴基斯坦','PAK',0,5,1,1,0,'',0.0000,0.0000,0.0000,0.0000,'PKR'),(7714,'Gcash伪原生','GcashWap2',0,8,0,0,0,'',0.0000,0.0000,0.0000,0.0000,'PHP'),(7715,'Gcash(QRPH)','GcashScan',0,7,0,0,0,'',0.0000,0.0000,0.0000,0.0000,'PHP'),(7716,'Maya','Maya',0,10,1,1,0,'',0.0000,0.0000,0.0000,0.0000,'PHP'),(7717,'Gcash原生','GcashWap',0,6,1,1,0,'',0.0000,0.0000,0.0000,0.0000,'PHP'),(7719,'PAK-jazzcash','PAK',0,14,1,1,0,'',0.0000,0.0000,0.0000,0.0000,'PKR'),(7720,'PAK-easypaisa','PAK',0,15,1,1,0,'',0.0000,0.0000,0.0000,0.0000,'PKR'),(7721,'jazzcash-直连','PAK',0,16,1,1,0,'',0.0000,0.0000,0.0000,0.0000,'PKR'),(7722,'easypaisa-直连','PAK',0,17,1,1,0,'',0.0000,0.0000,0.0000,0.0000,'PKR'),(7723,'越南-zalo扫码','ZALO-P',0,18,1,1,0,'',0.0000,0.0000,0.0000,0.0000,'VND'),(7724,'越南-momo扫码','MOMO-P',0,19,1,1,0,'',0.0000,0.0000,0.0000,0.0000,'VND'),(7725,'越南-viettelpay扫码','VIETTEL',0,20,1,1,0,'',0.0000,0.0000,0.0000,0.0000,'VND'),(7726,'越南-银行卡','VN-BANK',0,21,1,1,0,'',0.0000,0.0000,0.0000,0.0000,'VND'),(7727,'越南-银行卡直连','VN-DIREC',0,22,1,1,0,'',0.0000,0.0000,0.0000,0.0000,'VND'),(7728,'越南-银行卡扫码','VN-BANK-QR',0,23,1,1,0,'',0.0000,0.0000,0.0000,0.0000,'VND'),(7729,'越南-点卡','VN-PERPAID',0,24,1,1,0,'',0.0000,0.0000,0.0000,0.0000,'VND'),(7730,'印尼Qris','IdrQris',0,25,1,1,0,'',0.0000,0.0000,0.0000,0.0000,'IDR'),(7731,'印尼Dana','IdrDana',0,26,1,1,0,'',0.0000,0.0000,0.0000,0.0000,'IDR');
/*!40000 ALTER TABLE `pay_product` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_product_user`
--

DROP TABLE IF EXISTS `pay_product_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_product_user` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT ' ',
  `userid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '商户编号',
  `pid` smallint(6) unsigned NOT NULL DEFAULT '0' COMMENT '商户通道ID',
  `polling` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '接口模式：0 单独 1 轮询',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '通道状态 0 关闭 1 启用',
  `channel` smallint(6) unsigned NOT NULL DEFAULT '0' COMMENT '指定单独通道ID',
  `weight` varchar(255) DEFAULT NULL COMMENT '通道权重',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `pid` (`pid`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_product_user`
--

LOCK TABLES `pay_product_user` WRITE;
/*!40000 ALTER TABLE `pay_product_user` DISABLE KEYS */;
INSERT INTO `pay_product_user` VALUES (1,2,7716,0,0,0,''),(2,2,7717,0,1,8,'');
/*!40000 ALTER TABLE `pay_product_user` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_reconciliation`
--

DROP TABLE IF EXISTS `pay_reconciliation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_reconciliation` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL DEFAULT '0' COMMENT '用户ID',
  `order_total_count` int(11) NOT NULL DEFAULT '0' COMMENT '总订单数',
  `order_success_count` int(11) NOT NULL DEFAULT '0' COMMENT '成功订单数',
  `order_success_poundage` decimal(11,4) NOT NULL DEFAULT '0.0000' COMMENT '订单总额',
  `order_success_amount` decimal(11,4) NOT NULL DEFAULT '0.0000' COMMENT '订单实付总额',
  `date` date NOT NULL COMMENT '日期',
  `ctime` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `wttklist_total_count` int(11) NOT NULL DEFAULT '0' COMMENT '总订单数',
  `wttklist_success_count` int(11) NOT NULL DEFAULT '0' COMMENT '成功订单数',
  `wttklist_success_poundage` decimal(11,4) NOT NULL DEFAULT '0.0000' COMMENT '订单总额',
  `wttklist_success_amount` decimal(11,4) NOT NULL DEFAULT '0.0000' COMMENT '订单实付总额',
  `gmoney` decimal(11,4) NOT NULL DEFAULT '0.0000' COMMENT '当天最后余额',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_reconciliation`
--

LOCK TABLES `pay_reconciliation` WRITE;
/*!40000 ALTER TABLE `pay_reconciliation` DISABLE KEYS */;
INSERT INTO `pay_reconciliation` VALUES (1,2,0,0,0.0000,0.0000,'2026-05-20',1779287951,0,0,0.0000,0.0000,0.0000),(2,3,0,0,0.0000,0.0000,'2026-05-24',1779559010,0,0,0.0000,0.0000,0.0000);
/*!40000 ALTER TABLE `pay_reconciliation` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_reconciliation_payin`
--

DROP TABLE IF EXISTS `pay_reconciliation_payin`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_reconciliation_payin` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL DEFAULT '0' COMMENT '用户ID',
  `channel_id` int(11) NOT NULL DEFAULT '0' COMMENT '通道ID',
  `order_success_amount` decimal(21,4) NOT NULL DEFAULT '0.0000' COMMENT '订单实付总额',
  `order_success_count` int(11) NOT NULL DEFAULT '0' COMMENT '成功订单数',
  `order_success_rate` decimal(21,4) NOT NULL DEFAULT '0.0000' COMMENT '订单成功率',
  `order_success_average` int(11) NOT NULL DEFAULT '0' COMMENT '总均单',
  `date` date NOT NULL COMMENT '日期',
  `ctime` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='代收对账单';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_reconciliation_payin`
--

LOCK TABLES `pay_reconciliation_payin` WRITE;
/*!40000 ALTER TABLE `pay_reconciliation_payin` DISABLE KEYS */;
/*!40000 ALTER TABLE `pay_reconciliation_payin` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_reconciliation_payout`
--

DROP TABLE IF EXISTS `pay_reconciliation_payout`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_reconciliation_payout` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL DEFAULT '0' COMMENT '用户ID',
  `channel_id` int(11) NOT NULL DEFAULT '0' COMMENT '通道ID',
  `wttklist_total_count` int(11) NOT NULL DEFAULT '0' COMMENT '总订单数',
  `wttklist_success_count` int(11) NOT NULL DEFAULT '0' COMMENT '成功订单数',
  `wttklist_success_poundage` decimal(21,4) NOT NULL DEFAULT '0.0000' COMMENT '订单总额',
  `wttklist_success_amount` decimal(21,4) NOT NULL DEFAULT '0.0000' COMMENT '订单实付总额',
  `date` date NOT NULL COMMENT '日期',
  `ctime` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='代付对账单';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_reconciliation_payout`
--

LOCK TABLES `pay_reconciliation_payout` WRITE;
/*!40000 ALTER TABLE `pay_reconciliation_payout` DISABLE KEYS */;
/*!40000 ALTER TABLE `pay_reconciliation_payout` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_redo_order`
--

DROP TABLE IF EXISTS `pay_redo_order`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_redo_order` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL DEFAULT '0' COMMENT '操作管理员',
  `money` decimal(15,4) NOT NULL,
  `type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1：增加 2：减少',
  `remark` varchar(255) NOT NULL DEFAULT '' COMMENT '冲正备注',
  `date` datetime NOT NULL COMMENT '冲正周期',
  `ctime` int(11) NOT NULL DEFAULT '0' COMMENT '操作时间',
  `paytype` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '渠道类型: 1 Gcash直连 2 Gcash扫码 3 Maya 4 VietQR',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_redo_order`
--

LOCK TABLES `pay_redo_order` WRITE;
/*!40000 ALTER TABLE `pay_redo_order` DISABLE KEYS */;
/*!40000 ALTER TABLE `pay_redo_order` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_route`
--

DROP TABLE IF EXISTS `pay_route`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_route` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `urlstr` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_route`
--

LOCK TABLES `pay_route` WRITE;
/*!40000 ALTER TABLE `pay_route` DISABLE KEYS */;
/*!40000 ALTER TABLE `pay_route` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_session`
--

DROP TABLE IF EXISTS `pay_session`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_session` (
  `session_id` varchar(255) NOT NULL,
  `session_expire` int(11) NOT NULL,
  `session_data` blob,
  UNIQUE KEY `session_id` (`session_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_session`
--

LOCK TABLES `pay_session` WRITE;
/*!40000 ALTER TABLE `pay_session` DISABLE KEYS */;
/*!40000 ALTER TABLE `pay_session` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_sms`
--

DROP TABLE IF EXISTS `pay_sms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_sms` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `app_key` varchar(255) DEFAULT NULL COMMENT 'App Key',
  `app_secret` varchar(255) DEFAULT NULL COMMENT 'App Secret',
  `sign_name` varchar(255) DEFAULT NULL COMMENT '默认签名',
  `is_open` int(11) DEFAULT '0' COMMENT '是否开启，0关闭，1开启',
  `admin_mobile` varchar(255) DEFAULT NULL COMMENT '管理员接收手机',
  `is_receive` int(11) DEFAULT '0' COMMENT '是否开启，0关闭，1开启',
  `sms_channel` varchar(20) NOT NULL DEFAULT 'aliyun' COMMENT '短信通道',
  `smsbao_user` varchar(50) NOT NULL DEFAULT '' COMMENT '短信宝账号',
  `smsbao_pass` varchar(50) NOT NULL DEFAULT '' COMMENT '短信宝密码',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_sms`
--

LOCK TABLES `pay_sms` WRITE;
/*!40000 ALTER TABLE `pay_sms` DISABLE KEYS */;
INSERT INTO `pay_sms` VALUES (3,'LTAIRR5X8fMzp3do','Ku8NjuKhx9N6igyaHYstZFsc8GplwV','mars website',0,NULL,0,'aliyun','1','1');
/*!40000 ALTER TABLE `pay_sms` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_sms_template`
--

DROP TABLE IF EXISTS `pay_sms_template`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_sms_template` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `template_code` varchar(255) DEFAULT NULL COMMENT '模板代码',
  `call_index` varchar(255) DEFAULT NULL COMMENT '调用字符串',
  `template_content` text COMMENT '模板内容',
  `ctime` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_sms_template`
--

LOCK TABLES `pay_sms_template` WRITE;
/*!40000 ALTER TABLE `pay_sms_template` DISABLE KEYS */;
INSERT INTO `pay_sms_template` VALUES (3,'修改支付密码','SMS_125115291','editPayPassword','您的验证码为：${code} ，你正在进行重要操作，该验证码 5 分钟内有效，请勿泄露于他人。',1512202260),(4,'修改登录密码','SMS_125115291','editPassword','您的验证码为：${code} ，你正在进行重要操作，该验证码 5 分钟内有效，请勿泄露于他人。',1512190115),(5,'异地登录','SMS_125115291','loginWarning','您的账号于${time}登录异常，异常登录地址：${address}，如非本人操纵，请及时修改账号密码。',1512202260),(6,'申请结算','SMS_125115291','clearing','您的验证码为：${code} ，你正在进行重要操作，该验证码 5 分钟内有效，请勿泄露于他人。',1512202260),(7,'委托结算','SMS_125115291','entrusted','您的验证码为：${code} ，你正在进行重要操作，该验证码 5 分钟内有效，请勿泄露于他人。',1512202260),(8,'绑定手机','SMS_125115291','bindMobile','您的验证码为：${code} ，你正在进行重要操作，该验证码 5 分钟内有效，请勿泄露于他人。',1514534290),(9,'更新手机','SMS_125115291','editMobile','您的验证码为：${code} ，你正在进行重要操作，该验证码 5 分钟内有效，请勿泄露于他人。',1514535688),(10,'更新银行卡 ','SMS_125115291 ','addBankcardSend','您的验证码为：${code} ，你正在进行重要操作，该验证码 5 分钟内有效，请勿泄露于他人。',1514535688),(11,'修改个人资料','SMS_125115291','saveProfile','您的验证码为：${code} ，你正在进行重要操作，该验证码 5 分钟内有效，请勿泄露于他人。',NULL),(12,'绑定管理员手机号码','SMS_125115291','adminbindMobile','您的验证码为：${code} ，你正在进行重要操作，该验证码 5 分钟内有效，请勿泄露于他人。',1527670734),(13,'修改管理员手机号码','SMS_125115291','admineditMobile','您的验证码为：${code} ，你正在进行重要操作，该验证码 5 分钟内有效，请勿泄露于他人。',1527670734),(14,'批量删除订单','SMS_125115291','delOrderSend','您的验证码为：${code} ，你正在进行重要操作，该验证码 5 分钟内有效，请勿泄露于他人。',1527670734),(15,'解绑谷歌身份验证器','SMS_125115291','unbindGoogle','您的验证码为：${code} ，你正在进行重要操作，该验证码 5 分钟内有效，请勿泄露于他人。',1527670734),(16,'设置订单为已支付','SMS_125115291','setOrderPaidSend','您的验证码为：${code} ，你正在进行重要操作，该验证码 5 分钟内有效，请勿泄露于他人。',1527670734),(17,'清理数据','SMS_125115291','clearDataSend','您的验证码为：${code} ，你正在进行重要操作，该验证码 5 分钟内有效，请勿泄露于他人。',1527670734),(18,'增加/减少余额（冲正）','SMS_125115291','adjustUserMoneySend','您的验证码为：${code} ，你正在进行重要操作，该验证码 5 分钟内有效，请勿泄露于他人。',1527670734),(19,'提交代付','SMS_125115291','submitDfSend','您的验证码为：${code} ，你正在进行重要操作，该验证码 5 分钟内有效，请勿泄露于他人。',1527670734),(20,'测试短信','SMS_125115291','test','您的验证码为：${code} ，您正在进行重要操作，该验证码 5 分钟内有效，请勿泄露于他人。',1527670734),(21,'系统配置','SMS_125115291','sysconfig','您的验证码为：${code} ，您正在进行重要操作，该验证码 5 分钟内有效，请勿泄露于他人。',1527670734),(22,'提款设置','SMS_125115291','tkconfig','您的验证码为：${code} ，您正在进行重要操作，该验证码 5 分钟内有效，请勿泄露于他人。',1527670734);
/*!40000 ALTER TABLE `pay_sms_template` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_systembank`
--

DROP TABLE IF EXISTS `pay_systembank`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_systembank` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `bankcode` varchar(100) DEFAULT NULL,
  `bankname` varchar(300) DEFAULT NULL,
  `images` varchar(300) DEFAULT NULL,
  `bank_code` int(12) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=50 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='结算银行';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_systembank`
--

LOCK TABLES `pay_systembank` WRITE;
/*!40000 ALTER TABLE `pay_systembank` DISABLE KEYS */;
INSERT INTO `pay_systembank` VALUES (1,'BOC','中国银行','BOC.gif',100150),(2,'ICBC','中国工商银行','ICBC.gif',100151),(3,'CCB','中国建设银行','CCB.gif',100152),(4,'ABC','中国农业银行','ABC.gif',100153),(5,'PSBC','邮政储蓄银行','PSBC.gif',100154),(6,'BCM','交通银行','BCM.gif',100155),(7,'CMBC','招商银行','CMBC.gif',100156),(8,'CNCB','中信银行','CNCB.gif',100157),(9,'CIB','兴业银行','CIB.gif',100158),(10,'CEB','光大银行','CEB.gif',100159),(11,'CMSB','民生银行','CMSB.gif',100160),(12,'GDB','广发银行','GDB.gif',100161),(13,'PAB','平安银行','PAB.gif',100162),(14,'BOS','上海银行','BOS.gif',100163),(15,'CZB','浙商银行','CZB.gif',100164),(16,'XMB','厦门银行','XMB.jpg',100165),(17,'CQBANK','重庆银行','CQBANK.jpg',100166),(18,'BOB','北京银行','BOB.gif',100167),(19,'HXB','华夏银行','HXB.gif',100168),(20,'NJCB','南京银行','NJCB.gif',100169),(21,'NBCB','宁波银行','NBCB.gif',100170),(22,'SPDB','浦发银行','SPDB.gif',100171),(23,'BEA','东亚银行','BEA.gif',100172),(24,'JXBANK','嘉兴银行','JXBANK.gif',100173),(25,'TJBANK','天津银行','TJBANK.gif',100174),(26,'GZBANK','广州银行','GZBANK.gif',100175),(27,'HBBANK','湖北银行','HBBANK.gif',100176),(28,'LZBANK','兰州银行','LZBANK.gif',100177),(29,'BGZCHINA','贵州银行','BGZCHINA.gif',100178),(30,'HNBANK','海南银行','HNBANK.gif',100179),(31,'JLBANK','吉林银行','JLBANK.gif',100180),(32,'HFBANK','恒丰银行','HFBANK.gif',100181),(33,'HSBC','汇丰银行','HSBC.gif',100182),(34,'HSBL','恒生银行','HSBL.gif',100183),(35,'GZNXBANK','贵州省农村信用社联合社','GZNXBANK.gif',100184),(36,'GDNXBANK','广东省农村信用社联合社','GDNXBANK.gif',100185),(37,'GXNXBANK','广西省农村信用合作联社','GXNXBANK.gif',100186),(38,'SCNXBANK','四川省农村信用合作联社','SCNXBANK.gif',100187),(39,'FJNXBANK','福建省农村信用合作联社','FJNXBANK.gif',100188),(41,'SDB','深圳发展银行','SDB.gif',100190),(43,'CBHB','渤海银行','CBHB.gif',100192),(44,'HZCB','杭州银行','HZCB.gif',100193),(45,'BJRCB','北京农村商业银行','BJRCB.gif',100194),(46,'SRCB','上海农商银行','SRCB.gif',100195),(47,'Maya','Maya','Maya.gif',100151),(48,'gcash','gcash','gcash.gif',100152),(49,'usdt','usdt','usdt.gif',100153);
/*!40000 ALTER TABLE `pay_systembank` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_telegram_api`
--

DROP TABLE IF EXISTS `pay_telegram_api`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_telegram_api` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `telegram_name` varchar(100) DEFAULT NULL COMMENT 'telegram群名称',
  `telegram_id` varchar(30) NOT NULL COMMENT 'telegram群ID',
  `status` tinyint(1) DEFAULT '0' COMMENT '是否开启：0关闭，1开启',
  `is_open` tinyint(1) DEFAULT '0' COMMENT '是否群发消息：0关闭，1开启',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_telegram_api`
--

LOCK TABLES `pay_telegram_api` WRITE;
/*!40000 ALTER TABLE `pay_telegram_api` DISABLE KEYS */;
/*!40000 ALTER TABLE `pay_telegram_api` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_telegram_api_dforder`
--

DROP TABLE IF EXISTS `pay_telegram_api_dforder`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_telegram_api_dforder` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `member_id` int(11) unsigned NOT NULL COMMENT '用户id',
  `pay_orderid` varchar(100) NOT NULL COMMENT '代付系统订单号',
  `chat_id` varchar(30) NOT NULL COMMENT '会话ID',
  `message_id` varchar(30) NOT NULL DEFAULT '0' COMMENT '是否开启：0关闭，1开启',
  `create_time` int(11) NOT NULL COMMENT '创建时间',
  `chat_id2` varchar(30) NOT NULL COMMENT '转发会话ID',
  `message_id2` varchar(30) NOT NULL COMMENT '转发信息ID',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_telegram_api_dforder`
--

LOCK TABLES `pay_telegram_api_dforder` WRITE;
/*!40000 ALTER TABLE `pay_telegram_api_dforder` DISABLE KEYS */;
/*!40000 ALTER TABLE `pay_telegram_api_dforder` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_telegram_api_dforder_autodf`
--

DROP TABLE IF EXISTS `pay_telegram_api_dforder_autodf`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_telegram_api_dforder_autodf` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `orderid` varchar(100) NOT NULL COMMENT '代付系统订单号',
  `callback` tinyint(1) NOT NULL DEFAULT '0' COMMENT '确认操作：1通过，2驳回',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '状态:0=未处理,1=已处理',
  `create_time` int(11) NOT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `orderid` (`orderid`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_telegram_api_dforder_autodf`
--

LOCK TABLES `pay_telegram_api_dforder_autodf` WRITE;
/*!40000 ALTER TABLE `pay_telegram_api_dforder_autodf` DISABLE KEYS */;
/*!40000 ALTER TABLE `pay_telegram_api_dforder_autodf` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_telegram_api_dforder_callback`
--

DROP TABLE IF EXISTS `pay_telegram_api_dforder_callback`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_telegram_api_dforder_callback` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `orderid` varchar(100) NOT NULL COMMENT '代付系统订单号',
  `chat_id` varchar(30) NOT NULL COMMENT '会话ID',
  `message_id` varchar(30) NOT NULL DEFAULT '0' COMMENT '是否开启：0关闭，1开启',
  `doUser` varchar(50) CHARACTER SET utf8mb4 NOT NULL COMMENT '操作人',
  `callback` tinyint(1) NOT NULL DEFAULT '0' COMMENT '确认操作：1通过，2驳回',
  `create_time` int(11) NOT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_telegram_api_dforder_callback`
--

LOCK TABLES `pay_telegram_api_dforder_callback` WRITE;
/*!40000 ALTER TABLE `pay_telegram_api_dforder_callback` DISABLE KEYS */;
/*!40000 ALTER TABLE `pay_telegram_api_dforder_callback` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_telegram_api_order`
--

DROP TABLE IF EXISTS `pay_telegram_api_order`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_telegram_api_order` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `member_id` int(11) unsigned NOT NULL COMMENT '用户id',
  `pay_orderid` varchar(100) NOT NULL COMMENT '系统订单号',
  `chat_id` varchar(30) NOT NULL COMMENT '会话ID',
  `message_id` varchar(20) NOT NULL DEFAULT '0' COMMENT '是否开启：0关闭，1开启',
  `create_time` int(11) NOT NULL COMMENT '创建时间',
  `type` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '类型：1充值，0查询',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_telegram_api_order`
--

LOCK TABLES `pay_telegram_api_order` WRITE;
/*!40000 ALTER TABLE `pay_telegram_api_order` DISABLE KEYS */;
/*!40000 ALTER TABLE `pay_telegram_api_order` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_template`
--

DROP TABLE IF EXISTS `pay_template`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_template` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT ' ' COMMENT '模板名称',
  `theme` varchar(255) NOT NULL DEFAULT ' ' COMMENT '模板代码',
  `is_default` tinyint(4) unsigned NOT NULL DEFAULT '0' COMMENT '是否默认模板:1是，0否',
  `add_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '添加时间',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '修改时间',
  `remarks` varchar(255) NOT NULL DEFAULT ' ' COMMENT '备注',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='模板表';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_template`
--

LOCK TABLES `pay_template` WRITE;
/*!40000 ALTER TABLE `pay_template` DISABLE KEYS */;
INSERT INTO `pay_template` VALUES (1,' 默认模板','default',0,1524299660,1524299660,' 默认模板'),(2,'模板一','view1',0,1524299660,1524299660,'模板一'),(3,'模板二','view2',0,0,1524623473,'模板二'),(4,'模板三','view3',1,0,1524623494,'模板三');
/*!40000 ALTER TABLE `pay_template` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_tikuanconfig`
--

DROP TABLE IF EXISTS `pay_tikuanconfig`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_tikuanconfig` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '商户编号',
  `tkzxmoney` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '单笔最小提款金额',
  `tkzdmoney` decimal(15,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '单笔最大提款金额',
  `dayzdmoney` decimal(15,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '当日提款最大总金额',
  `dayzdnum` int(11) NOT NULL DEFAULT '0' COMMENT '当日提款最大次数',
  `t1zt` tinyint(2) unsigned NOT NULL DEFAULT '0' COMMENT 'T+1 ：1开启 0 关闭',
  `t0zt` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT 'T+0 ：1开启 0 关闭',
  `gmt0` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '购买T0',
  `tkzt` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '提款设置 1 开启 0 关闭',
  `tktype` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '提款手续费类型 1 每笔 0 比例 ',
  `systemxz` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '0 系统规则 1 用户规则',
  `sxfrate` varchar(20) DEFAULT NULL COMMENT '单笔提款比例',
  `sxffixed` varchar(20) DEFAULT NULL COMMENT '单笔提款手续费',
  `issystem` tinyint(1) unsigned DEFAULT '0' COMMENT '平台规则 1 是 0 否',
  `allowstart` tinyint(2) unsigned NOT NULL DEFAULT '0' COMMENT '提款允许开始时间',
  `allowend` tinyint(2) unsigned NOT NULL DEFAULT '0' COMMENT '提款允许结束时间',
  `daycardzdmoney` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '单人单卡单日最高提现额',
  `auto_df_switch` tinyint(1) NOT NULL DEFAULT '0' COMMENT '自动代付开关',
  `auto_df_maxmoney` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '单笔代付最大金额限制',
  `auto_df_stime` varchar(20) NOT NULL DEFAULT '' COMMENT '自动代付开始时间',
  `auto_df_etime` varchar(20) NOT NULL DEFAULT '' COMMENT '自动代付结束时间',
  `auto_df_max_count` int(11) NOT NULL DEFAULT '0' COMMENT '商户每天自动代付笔数限制',
  `auto_df_max_sum` int(11) NOT NULL DEFAULT '0' COMMENT '商户每天自动代付最大总金额限制',
  `tk_charge_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '扣除手续费方式，0：从到账金额里扣，1：从商户余额里扣',
  `channel` int(11) DEFAULT '0' COMMENT '代付通道id',
  `currency` varchar(10) NOT NULL COMMENT '国家货币',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `IND_UID` (`userid`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=76 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_tikuanconfig`
--

LOCK TABLES `pay_tikuanconfig` WRITE;
/*!40000 ALTER TABLE `pay_tikuanconfig` DISABLE KEYS */;
INSERT INTO `pay_tikuanconfig` VALUES (28,1,1.00,50000.00,99999999.00,99999,0,0,200.00,1,0,0,'0','0',1,0,0,500000.00,1,50000.00,'00:00','23:59',0,0,1,0,''),(30,1,100.00,50000.00,9999999999.00,99999999,0,0,0.00,1,1,2,'0','5',0,0,0,0.00,0,0.00,'','',0,0,1,14,'PHP'),(31,1,100.00,50000.00,99999999.00,9999999,0,0,0.00,1,1,2,'','5',0,0,0,0.00,0,0.00,'','',0,0,1,13,'PHP'),(32,1,100.00,50000.00,999999999.00,99999999,0,0,0.00,1,1,2,'','5',0,0,0,0.00,0,0.00,'','',0,0,1,12,'PHP'),(33,1,100.00,50000.00,999999999.00,999999999,0,0,0.00,1,1,2,'','4.5',0,0,0,0.00,0,0.00,'','',0,0,1,15,'PHP'),(34,1,100.00,20000.00,999999999.00,99999999,0,0,0.00,1,1,2,'','5',0,0,0,0.00,0,0.00,'','',0,0,1,16,'PHP'),(35,1,100.00,50000.00,99999999.00,99999999,0,0,0.00,1,0,2,'2','',0,0,0,0.00,0,0.00,'','',0,0,1,18,'PKR'),(36,1,100.00,50000.00,999999999.00,999999999,0,0,0.00,1,0,2,'2','',0,0,0,0.00,0,0.00,'','',0,0,1,17,'PKR'),(37,1,100.00,50000.00,9999999.00,9999999,0,0,0.00,1,0,2,'2','',0,0,0,0.00,0,0.00,'','',0,0,1,20,'PKR'),(38,1,100.00,50000.00,99999999.00,99999999,0,0,0.00,1,0,2,'2','',0,0,0,0.00,0,0.00,'','',0,0,1,19,'PKR'),(39,1,100.00,50000.00,99999999999.00,999999999,0,0,0.00,1,1,2,'','4.5',0,0,0,0.00,0,0.00,'','',0,0,1,21,'PHP'),(40,2,100.00,50000.00,999999999.00,9999999,0,0,0.00,1,1,1,'0','4',0,0,0,0.00,0,0.00,'','',0,0,1,0,'0'),(41,40,100.00,50000.00,9999999999.00,9999,0,0,0.00,1,1,0,'','6',0,0,0,0.00,0,0.00,'','',0,0,1,0,'0'),(42,39,100.00,50000.00,9999999999.00,9999,0,0,0.00,1,1,0,'','5.5',0,0,0,0.00,0,0.00,'','',0,0,1,0,'0'),(43,37,100.00,50000.00,9999999999.00,9999,0,0,0.00,1,1,0,'','6',0,0,0,0.00,0,0.00,'','',0,0,1,0,'0'),(44,36,100.00,50000.00,9999999999.00,9999,0,0,0.00,1,1,0,'','6',0,0,0,0.00,0,0.00,'','',0,0,1,0,'0'),(45,35,100.00,50000.00,9999999999.00,9999,0,0,0.00,1,1,0,'','6',0,0,0,0.00,0,0.00,'','',0,0,1,0,'0'),(46,34,100.00,50000.00,9999999999.00,9999,0,0,0.00,1,1,0,'','6',0,0,0,0.00,0,0.00,'','',0,0,1,0,'0'),(47,33,100.00,50000.00,9999999999.00,9999,0,0,0.00,1,1,0,'','6',0,0,0,0.00,0,0.00,'','',0,0,1,0,'0'),(48,32,100.00,50000.00,9999999999.00,9999,0,0,0.00,1,1,0,'','6',0,0,0,0.00,0,0.00,'','',0,0,1,0,'0'),(49,31,100.00,50000.00,9999999999.00,9999,0,0,0.00,1,1,0,'','6',0,0,0,0.00,0,0.00,'','',0,0,1,0,'0'),(50,30,100.00,50000.00,9999999999.00,9999,0,0,0.00,1,1,0,'','6',0,0,0,0.00,0,0.00,'','',0,0,1,0,'0'),(51,29,100.00,50000.00,9999999999.00,9999,0,0,0.00,1,1,0,'','6',0,0,0,0.00,0,0.00,'','',0,0,1,0,'0'),(52,28,100.00,50000.00,9999999999.00,9999,0,0,0.00,1,1,0,'','6',0,0,0,0.00,0,0.00,'','',0,0,1,0,'0'),(53,27,100.00,50000.00,9999999999.00,9999,0,0,0.00,1,1,0,'','6',0,0,0,0.00,0,0.00,'','',0,0,1,0,'0'),(54,26,100.00,50000.00,9999999999.00,9999,0,0,0.00,1,1,0,'','6',0,0,0,0.00,0,0.00,'','',0,0,1,0,'0'),(55,24,100.00,50000.00,9999999999.00,9999,0,0,0.00,1,1,0,'','6',0,0,0,0.00,0,0.00,'','',0,0,1,0,'0'),(56,23,100.00,50000.00,9999999999.00,9999,0,0,0.00,1,1,0,'','6',0,0,0,0.00,0,0.00,'','',0,0,1,0,'0'),(57,22,100.00,50000.00,9999999999.00,9999999,0,0,0.00,1,1,1,'','4.5',0,0,0,0.00,0,0.00,'','',0,0,1,0,'0'),(58,21,100.00,50000.00,9999999999.00,9999,0,0,0.00,1,1,0,'','5',0,0,0,0.00,0,0.00,'','',0,0,1,0,'0'),(59,18,100.00,50000.00,9999999999.00,9999,0,0,0.00,1,1,0,'','6',0,0,0,0.00,0,0.00,'','',0,0,1,0,'0'),(60,17,100.00,50000.00,9999999999.00,9999,0,0,0.00,1,1,0,'','6',0,0,0,0.00,0,0.00,'','',0,0,1,0,'0'),(61,15,100.00,50000.00,9999999999.00,9999999,0,0,0.00,1,1,0,'','5.5',0,0,0,0.00,0,0.00,'','',0,0,1,0,'0'),(62,14,100.00,50000.00,9999999999.00,99999999,0,0,0.00,1,1,1,'','4',0,0,0,0.00,0,0.00,'','',0,0,1,0,'0'),(63,10,100.00,50000.00,9999999999.00,9999,0,0,0.00,1,1,0,'','5',0,0,0,0.00,0,0.00,'','',0,0,1,0,'0'),(64,9,100.00,50000.00,9999999999.00,99999999,0,0,0.00,1,1,1,'','4',0,0,0,0.00,0,0.00,'','',0,0,1,0,'0'),(65,1,50000.00,300000000.00,9999999999999.00,999999999,0,0,0.00,1,0,2,'','',0,0,0,0.00,0,0.00,'','',0,0,1,22,'VND'),(66,1,10000.00,50000000.00,9999999999999.00,999999999,0,0,0.00,1,2,2,'0.15','1200',0,0,0,0.00,0,0.00,'','',0,0,1,24,'IDR'),(67,1,100.00,50000.00,99999999999.00,9999999,0,0,0.00,1,0,2,'2','',0,0,0,0.00,0,0.00,'','',0,0,1,23,'PKR'),(68,43,100.00,50000.00,99999999.00,9999,0,0,0.00,1,1,0,'','6',0,0,0,0.00,0,0.00,'','',0,0,1,0,'0'),(69,8,100.00,50000.00,999999999.00,999999999,0,0,0.00,1,1,1,'','4.5',0,0,0,0.00,0,0.00,'','',0,0,1,0,'0'),(70,8,100.00,50000.00,999999999.00,999999999,0,0,0.00,1,1,1,'','5.5',0,0,0,0.00,0,0.00,'','',0,0,1,0,'0'),(71,44,100.00,50000.00,99999999999.00,999999,0,0,0.00,1,0,1,'2.1','',0,0,0,0.00,0,0.00,'','',0,0,1,0,'0'),(72,49,100.00,50000.00,9999999999.00,999999999,0,0,0.00,1,1,0,'','6',0,0,0,0.00,0,0.00,'','',0,0,1,0,'0'),(73,1,50.00,50000.00,99999999.00,99999999,0,0,0.00,1,0,2,'2','',0,0,0,0.00,0,0.00,'','',0,0,1,25,'PKR'),(74,56,50.00,50000.00,999999999.00,999999999,0,0,0.00,1,1,1,'1.6','',0,0,0,0.00,0,0.00,'','',0,0,1,0,'0'),(75,13,100.00,500000.00,999999999.00,999999999,0,0,0.00,1,1,1,'','4',0,0,0,0.00,0,0.00,'','',0,0,1,0,'0');
/*!40000 ALTER TABLE `pay_tikuanconfig` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_tikuanholiday`
--

DROP TABLE IF EXISTS `pay_tikuanholiday`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_tikuanholiday` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `datetime` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '排除日期',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='排除节假日';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_tikuanholiday`
--

LOCK TABLES `pay_tikuanholiday` WRITE;
/*!40000 ALTER TABLE `pay_tikuanholiday` DISABLE KEYS */;
INSERT INTO `pay_tikuanholiday` VALUES (5,1503676800),(6,1503763200),(8,1504281600),(9,1504368000);
/*!40000 ALTER TABLE `pay_tikuanholiday` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_tikuanmoney`
--

DROP TABLE IF EXISTS `pay_tikuanmoney`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_tikuanmoney` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL DEFAULT '0' COMMENT '结算用户ID',
  `websiteid` int(11) NOT NULL DEFAULT '0',
  `payapiid` smallint(6) unsigned NOT NULL DEFAULT '0' COMMENT '结算通道ID',
  `t` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '结算方式: 1 T+1 ,0 T+0',
  `money` decimal(10,2) unsigned NOT NULL DEFAULT '0.00',
  `datetype` varchar(2) NOT NULL,
  `createtime` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_tikuanmoney`
--

LOCK TABLES `pay_tikuanmoney` WRITE;
/*!40000 ALTER TABLE `pay_tikuanmoney` DISABLE KEYS */;
/*!40000 ALTER TABLE `pay_tikuanmoney` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_tikuantime`
--

DROP TABLE IF EXISTS `pay_tikuantime`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_tikuantime` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `baiks` tinyint(2) unsigned DEFAULT '0' COMMENT '白天提款开始时间',
  `baijs` tinyint(2) unsigned DEFAULT '0' COMMENT '白天提款结束时间',
  `wanks` tinyint(2) unsigned DEFAULT '0' COMMENT '晚间提款开始时间',
  `wanjs` int(11) DEFAULT '0',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='提款时间';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_tikuantime`
--

LOCK TABLES `pay_tikuantime` WRITE;
/*!40000 ALTER TABLE `pay_tikuantime` DISABLE KEYS */;
INSERT INTO `pay_tikuantime` VALUES (1,24,17,18,24);
/*!40000 ALTER TABLE `pay_tikuantime` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_tklist`
--

DROP TABLE IF EXISTS `pay_tklist`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_tklist` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `bankname` varchar(300) NOT NULL,
  `bankzhiname` varchar(300) NOT NULL,
  `banknumber` varchar(300) NOT NULL,
  `bankfullname` varchar(300) NOT NULL,
  `sheng` varchar(300) NOT NULL,
  `shi` varchar(300) NOT NULL,
  `sqdatetime` datetime DEFAULT NULL,
  `cldatetime` datetime DEFAULT NULL,
  `status` tinyint(4) NOT NULL DEFAULT '0',
  `tkmoney` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `sxfmoney` decimal(15,4) unsigned NOT NULL DEFAULT '0.0000',
  `money` decimal(15,4) unsigned NOT NULL DEFAULT '0.0000',
  `t` int(4) NOT NULL DEFAULT '1',
  `payapiid` int(11) NOT NULL DEFAULT '0',
  `memo` text COMMENT '备注',
  `tk_charge_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '扣除手续费方式，0：从到账金额里扣，1：从商户余额里扣',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_tklist`
--

LOCK TABLES `pay_tklist` WRITE;
/*!40000 ALTER TABLE `pay_tklist` DISABLE KEYS */;
/*!40000 ALTER TABLE `pay_tklist` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_u_rate`
--

DROP TABLE IF EXISTS `pay_u_rate`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_u_rate` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `rate` decimal(10,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT 'U费率',
  `currency` varchar(10) NOT NULL COMMENT '国家货币',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='商户通道费率';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_u_rate`
--

LOCK TABLES `pay_u_rate` WRITE;
/*!40000 ALTER TABLE `pay_u_rate` DISABLE KEYS */;
/*!40000 ALTER TABLE `pay_u_rate` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_updatelog`
--

DROP TABLE IF EXISTS `pay_updatelog`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_updatelog` (
  `version` varchar(20) NOT NULL,
  `lastupdate` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_updatelog`
--

LOCK TABLES `pay_updatelog` WRITE;
/*!40000 ALTER TABLE `pay_updatelog` DISABLE KEYS */;
/*!40000 ALTER TABLE `pay_updatelog` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_user_channel_account`
--

DROP TABLE IF EXISTS `pay_user_channel_account`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_user_channel_account` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '用户id',
  `account_ids` varchar(255) NOT NULL DEFAULT '' COMMENT '子账号id',
  `status` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '是否开启指定账号',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `userid` (`userid`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='用户指定指账号';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_user_channel_account`
--

LOCK TABLES `pay_user_channel_account` WRITE;
/*!40000 ALTER TABLE `pay_user_channel_account` DISABLE KEYS */;
/*!40000 ALTER TABLE `pay_user_channel_account` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_user_code`
--

DROP TABLE IF EXISTS `pay_user_code`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_user_code` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `type` int(11) DEFAULT '0' COMMENT '0找回密码',
  `code` varchar(255) DEFAULT NULL,
  `username` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `mobile` varchar(20) DEFAULT NULL,
  `status` int(11) DEFAULT '0',
  `ctime` int(11) DEFAULT NULL,
  `uptime` int(11) DEFAULT NULL COMMENT '更新时间',
  `endtime` int(11) DEFAULT NULL COMMENT '有效时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_user_code`
--

LOCK TABLES `pay_user_code` WRITE;
/*!40000 ALTER TABLE `pay_user_code` DISABLE KEYS */;
/*!40000 ALTER TABLE `pay_user_code` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_user_log`
--

DROP TABLE IF EXISTS `pay_user_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_user_log` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ip` char(15) NOT NULL DEFAULT '' COMMENT '操作者IP地址',
  `node` char(200) NOT NULL DEFAULT '' COMMENT '当前操作节点',
  `user_id` int(11) NOT NULL DEFAULT '0' COMMENT '操作人用户ID',
  `username` varchar(32) NOT NULL DEFAULT '' COMMENT '用户名',
  `action` varchar(200) NOT NULL DEFAULT '' COMMENT '操作行为',
  `type` tinyint(3) NOT NULL DEFAULT '0' COMMENT '操作类型 1：查看 2：新增 3：修改 4：删除 5：导出',
  `content` text NOT NULL COMMENT '操作内容描述',
  `create_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='用户操作日志表';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_user_log`
--

LOCK TABLES `pay_user_log` WRITE;
/*!40000 ALTER TABLE `pay_user_log` DISABLE KEYS */;
INSERT INTO `pay_user_log` VALUES (1,'175.176.31.29','user/index/main',3,'enzo','访问商户中心首页',1,'访问商户中心首页','2026-05-23 17:54:59'),(2,'175.176.31.29','user/account/google',3,'enzo','访问绑定谷歌身份认证页面',1,'访问绑定谷歌身份认证页面','2026-05-23 17:55:04'),(3,'175.176.31.29','user/account/editpassword',3,'enzo','访问修改登录密码页面',1,'访问修改登录密码页面','2026-05-23 17:55:08'),(4,'175.176.31.29','user/index/main',3,'enzo','访问商户中心首页',1,'访问商户中心首页','2026-05-23 17:55:17'),(5,'175.176.31.29','user/index/main',3,'enzo','访问商户中心首页',1,'访问商户中心首页','2026-05-23 17:56:07'),(6,'175.176.31.29','user/account/google',3,'enzo','访问绑定谷歌身份认证页面',1,'访问绑定谷歌身份认证页面','2026-05-23 17:56:18'),(7,'175.176.31.29','user/account/editpassword',3,'enzo','访问修改登录密码页面',1,'访问修改登录密码页面','2026-05-23 17:56:21'),(8,'175.176.31.29','user/account/editpassword',3,'enzo','访问修改登录密码页面',1,'访问修改登录密码页面','2026-05-23 17:56:22'),(9,'175.176.31.29','user/account/editpaypassword',3,'enzo','访问修改支付密码页面',1,'访问修改支付密码页面','2026-05-23 17:56:23'),(10,'175.176.31.29','user/account/loginrecord',3,'enzo','访问登录记录页面',1,'访问登录记录页面','2026-05-23 17:56:25'),(11,'175.176.31.29','user/order/index',3,'enzo','访问所有订单列表页面',1,'访问所有订单订单列表页面','2026-05-23 17:56:27'),(12,'175.176.31.29','user/withdrawal/dfapplyu',3,'enzo','访问代付申请页面PHP',1,'表单提交方式','2026-05-23 17:56:31'),(13,'175.176.31.29','user/withdrawal/payment',3,'enzo','访问代付记录',1,'访问代付记录','2026-05-23 17:56:35'),(14,'175.176.31.29','user/withdrawal/payment',3,'enzo','访问代付记录',1,'访问代付记录','2026-05-23 17:56:36'),(15,'175.176.31.29','user/withdrawal/dfapplyu',3,'enzo','访问代付申请页面PHP',1,'表单提交方式','2026-05-23 17:56:37'),(16,'175.176.31.29','user/withdrawal/payment',3,'enzo','访问代付记录',1,'访问代付记录','2026-05-23 17:56:46'),(17,'175.176.31.29','user/account/reconciliation',3,'enzo','访问对账单页面',1,'访问对账单页面','2026-05-23 17:56:50'),(18,'175.176.31.29','user/account/changerecord',3,'enzo','访问资金变动记录页面',1,'访问资金变动记录页面','2026-05-23 17:56:51'),(19,'175.176.31.29','user/account/reconciliation',3,'enzo','访问对账单页面',1,'访问对账单页面','2026-05-23 17:56:52'),(20,'175.176.31.29','user/account/google',3,'enzo','访问绑定谷歌身份认证页面',1,'访问绑定谷歌身份认证页面','2026-05-23 17:56:57'),(21,'175.176.31.29','user/account/editpassword',3,'enzo','访问修改登录密码页面',1,'访问修改登录密码页面','2026-05-23 17:56:57'),(22,'175.176.31.29','user/account/editpassword',3,'enzo','访问修改登录密码页面',1,'访问修改登录密码页面','2026-05-23 17:56:58'),(23,'175.176.31.29','user/account/editpaypassword',3,'enzo','访问修改支付密码页面',1,'访问修改支付密码页面','2026-05-23 17:56:59'),(24,'175.176.31.29','user/order/index',3,'enzo','访问所有订单列表页面',1,'访问所有订单订单列表页面','2026-05-23 17:57:00');
/*!40000 ALTER TABLE `pay_user_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_user_pay_for_another`
--

DROP TABLE IF EXISTS `pay_user_pay_for_another`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_user_pay_for_another` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT ' ',
  `userid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '商户编号',
  `pid` smallint(6) unsigned NOT NULL DEFAULT '0' COMMENT '商户通道ID',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '通道状态 0 关闭 1 启用',
  `channel` smallint(6) unsigned NOT NULL DEFAULT '0' COMMENT '指定默认通道ID',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='用户默认代付通道';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_user_pay_for_another`
--

LOCK TABLES `pay_user_pay_for_another` WRITE;
/*!40000 ALTER TABLE `pay_user_pay_for_another` DISABLE KEYS */;
INSERT INTO `pay_user_pay_for_another` VALUES (1,2,7716,0,0),(2,2,7717,1,11);
/*!40000 ALTER TABLE `pay_user_pay_for_another` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_user_riskcontrol_config`
--

DROP TABLE IF EXISTS `pay_user_riskcontrol_config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_user_riskcontrol_config` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `user_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `min_money` decimal(11,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '单笔最小金额',
  `max_money` decimal(11,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '单笔最大金额',
  `unit_all_money` decimal(11,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '单位时间内交易总金额',
  `all_money` decimal(11,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '当天交易总金额',
  `start_time` tinyint(10) unsigned NOT NULL DEFAULT '0' COMMENT '一天交易开始时间',
  `end_time` tinyint(10) unsigned NOT NULL DEFAULT '0' COMMENT '一天交易结束时间',
  `unit_number` mediumint(8) unsigned NOT NULL DEFAULT '0' COMMENT '单位时间内交易的总笔数',
  `is_system` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '是否平台规则',
  `status` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '状态:1开通，0关闭',
  `add_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '添加时间',
  `edit_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '修改时间',
  `time_unit` char(1) CHARACTER SET utf8 NOT NULL DEFAULT 'i' COMMENT '限制的时间单位',
  `unit_interval` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '单位时间值',
  `domain` varchar(255) CHARACTER SET utf8 NOT NULL DEFAULT ' ' COMMENT '防封域名',
  `systemxz` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '0 系统规则 1 用户规则',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `uid` (`user_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC COMMENT='交易配置';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_user_riskcontrol_config`
--

LOCK TABLES `pay_user_riskcontrol_config` WRITE;
/*!40000 ALTER TABLE `pay_user_riskcontrol_config` DISABLE KEYS */;
INSERT INTO `pay_user_riskcontrol_config` VALUES (1,0,0.00,0.00,0.00,0.00,0,0,0,1,0,1522289886,0,'i',0,'',0),(2,2,3.00,10000.00,0.00,0.00,0,0,0,0,0,1739601783,1577779998,'s',0,'',1);
/*!40000 ALTER TABLE `pay_user_riskcontrol_config` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_userrate`
--

DROP TABLE IF EXISTS `pay_userrate`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_userrate` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `payapiid` smallint(6) unsigned NOT NULL DEFAULT '0' COMMENT '通道ID',
  `feilv` decimal(10,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '运营费率',
  `rate_type` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '提款手续费类型 1 每笔 0 比例 ',
  `sxffixed` decimal(10,4) NOT NULL DEFAULT '0.0000' COMMENT '单笔提款手续费',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='商户通道费率';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_userrate`
--

LOCK TABLES `pay_userrate` WRITE;
/*!40000 ALTER TABLE `pay_userrate` DISABLE KEYS */;
/*!40000 ALTER TABLE `pay_userrate` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_version`
--

DROP TABLE IF EXISTS `pay_version`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_version` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `version` varchar(255) NOT NULL DEFAULT '0' COMMENT '版本',
  `author` varchar(255) NOT NULL DEFAULT ' ' COMMENT '作者',
  `save_time` varchar(255) NOT NULL DEFAULT '0000-00-00' COMMENT '修改时间,格式YYYY-mm-dd',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='数据库版本表';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_version`
--

LOCK TABLES `pay_version` WRITE;
/*!40000 ALTER TABLE `pay_version` DISABLE KEYS */;
INSERT INTO `pay_version` VALUES (1,'5.5','陈嘉杰','2018-4-8'),(2,'5.6',' mapeijian','2018/4/17 17:45:33'),(3,'5.7',' mio','2018-4-17');
/*!40000 ALTER TABLE `pay_version` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_websiteconfig`
--

DROP TABLE IF EXISTS `pay_websiteconfig`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_websiteconfig` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `websitename` varchar(300) DEFAULT NULL COMMENT '网站名称',
  `domain` varchar(300) DEFAULT NULL COMMENT '网址',
  `email` varchar(100) DEFAULT NULL,
  `tel` varchar(30) DEFAULT NULL,
  `qq` varchar(30) DEFAULT NULL,
  `directory` varchar(100) DEFAULT NULL COMMENT '后台目录名称',
  `icp` varchar(100) DEFAULT NULL,
  `tongji` varchar(1000) DEFAULT NULL COMMENT '统计',
  `login` varchar(100) DEFAULT NULL COMMENT '登录地址',
  `payingservice` tinyint(1) unsigned DEFAULT '0' COMMENT '商户代付 1 开启 0 关闭',
  `authorized` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '商户认证 1 开启 0 关闭',
  `invitecode` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '邀请码注册',
  `company` varchar(200) DEFAULT NULL COMMENT '公司名称',
  `serverkey` varchar(50) DEFAULT NULL COMMENT '授权服务key',
  `withdraw` tinyint(1) DEFAULT '0' COMMENT '提现通知：0关闭，1开启',
  `login_warning_num` tinyint(3) unsigned NOT NULL DEFAULT '3' COMMENT '前台可以错误登录次数',
  `login_ip` varchar(1000) NOT NULL DEFAULT ' ' COMMENT '登录IP',
  `is_repeat_order` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '是否允许重复订单:1是，0否',
  `google_auth` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否开启谷歌身份验证登录',
  `df_api` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否开启代付API',
  `logo` varchar(255) NOT NULL DEFAULT ' ' COMMENT '公司logo',
  `random_mchno` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否开启随机商户号',
  `register_need_activate` tinyint(1) NOT NULL DEFAULT '0' COMMENT '用户注册是否需激活',
  `admin_alone_login` tinyint(1) NOT NULL DEFAULT '0' COMMENT '管理员是否只允许同时一次登录',
  `max_auth_error_times` int(10) NOT NULL DEFAULT '5' COMMENT '验证错误最大次数',
  `auth_error_ban_time` int(10) NOT NULL DEFAULT '10' COMMENT '验证错误超限冻结时间（分钟）',
  `document_url` varchar(255) NOT NULL DEFAULT '' COMMENT '对接文档链接',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_websiteconfig`
--

LOCK TABLES `pay_websiteconfig` WRITE;
/*!40000 ALTER TABLE `pay_websiteconfig` DISABLE KEYS */;
INSERT INTO `pay_websiteconfig` VALUES (1,'MAX 国际支付','maxpay89.com','maxpay89.com@website.com','88888888','','maxadmin','','','user',1,1,0,'','0d6de302cbc615de3b09463acea87662',0,3,' ',0,0,1,'Uploads/logo/logo123.png',0,1,0,5,10,'');
/*!40000 ALTER TABLE `pay_websiteconfig` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_wttklist`
--

DROP TABLE IF EXISTS `pay_wttklist`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_wttklist` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `orderid` varchar(100) NOT NULL DEFAULT ' ' COMMENT '订单id',
  `out_trade_no` varchar(30) NOT NULL DEFAULT '' COMMENT '下游订单号',
  `bankname` varchar(300) NOT NULL,
  `bankzhiname` varchar(300) DEFAULT NULL,
  `banknumber` varchar(300) NOT NULL,
  `bankfullname` varchar(300) NOT NULL,
  `tkmoney` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `sxfmoney` decimal(15,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '手续费',
  `money` decimal(15,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '实际到账',
  `sqdatetime` datetime NOT NULL,
  `cldatetime` datetime DEFAULT NULL,
  `status` enum('0','1','2','3','4','5','6') NOT NULL DEFAULT '0' COMMENT '状态:0=未处理,1=处理中,2=成功未返回,3=成功已返回,4=失败未返回,5=失败已返回,6=已驳回,',
  `paytype` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '渠道类型: 1 Gcash直连 2 Gcash扫码 3 Maya 4 VietQR',
  `memo` text COMMENT '备注',
  `additional` varchar(1000) NOT NULL DEFAULT ' ' COMMENT '额外的参数',
  `code` varchar(64) NOT NULL DEFAULT ' ' COMMENT '代码控制器名称',
  `df_charge_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '代付API扣除手续费方式，0：从到账金额里扣，1：从商户余额里扣',
  `bankcode` varchar(100) NOT NULL COMMENT '银行编码',
  `df_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '代付通道id',
  `df_name` varchar(64) NOT NULL DEFAULT ' ' COMMENT '代付名称',
  `channel_mch_id` varchar(50) NOT NULL DEFAULT '' COMMENT '通道商户号',
  `cost` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '成本',
  `cost_rate` decimal(10,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '成本费率',
  `rate_type` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '费率类型：按单笔收费0，按比例收费：1，按单笔+比例收费：2',
  `extends` text COMMENT '扩展数据',
  `auto_submit_try` int(10) NOT NULL DEFAULT '0' COMMENT '自动代付尝试提交次数',
  `last_submit_time` int(11) NOT NULL DEFAULT '0' COMMENT '最后提交时间',
  `df_lock` tinyint(1) NOT NULL DEFAULT '0' COMMENT '代付锁，防止重复提交',
  `lock_time` int(11) NOT NULL DEFAULT '0' COMMENT '锁定时间',
  `auto_query_num` int(10) NOT NULL DEFAULT '0' COMMENT '自动查询次数',
  `billno` varchar(50) NOT NULL DEFAULT '' COMMENT '上游交易流水号',
  `notifyurl` varchar(255) DEFAULT '' COMMENT '异步通知地址',
  `notifycount` int(1) unsigned zerofill NOT NULL DEFAULT '0' COMMENT '回调次数',
  `last_notify_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '上次回调时间',
  `last_reissue_time` int(11) DEFAULT '0',
  `num` tinyint(2) DEFAULT '0',
  `is_auto` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否自动提交',
  `type` varchar(20) DEFAULT NULL COMMENT '代付类型：CPF、PHONE、EMAIL、EVP',
  `df_type` tinyint(1) DEFAULT '1' COMMENT '下发类型。1:普通代付，2:U下发',
  `three_orderid` varchar(100) DEFAULT NULL COMMENT '三方订单号',
  `currency` varchar(10) NOT NULL COMMENT '国家货币',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `code` (`code`) USING BTREE,
  KEY `df_id` (`df_id`) USING BTREE,
  KEY `orderid` (`orderid`) USING BTREE,
  KEY `sqdatetime` (`sqdatetime`) USING BTREE,
  KEY `out_trade_no` (`out_trade_no`,`userid`) USING BTREE,
  KEY `cldatetime` (`cldatetime`) USING BTREE,
  KEY `status` (`status`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_wttklist`
--

LOCK TABLES `pay_wttklist` WRITE;
/*!40000 ALTER TABLE `pay_wttklist` DISABLE KEYS */;
/*!40000 ALTER TABLE `pay_wttklist` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_wttklist0520`
--

DROP TABLE IF EXISTS `pay_wttklist0520`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_wttklist0520` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `orderid` varchar(100) NOT NULL DEFAULT ' ' COMMENT '订单id',
  `out_trade_no` varchar(30) NOT NULL DEFAULT '' COMMENT '下游订单号',
  `bankname` varchar(300) NOT NULL,
  `bankzhiname` varchar(300) DEFAULT NULL,
  `banknumber` varchar(300) NOT NULL,
  `bankfullname` varchar(300) NOT NULL,
  `tkmoney` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `sxfmoney` decimal(15,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '手续费',
  `money` decimal(15,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '实际到账',
  `sqdatetime` datetime NOT NULL,
  `cldatetime` datetime DEFAULT NULL,
  `status` enum('0','1','2','3','4','5','6') NOT NULL DEFAULT '0' COMMENT '状态:0=未处理,1=处理中,2=成功未返回,3=成功已返回,4=失败未返回,5=失败已返回,6=已驳回,',
  `paytype` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '渠道类型: 1 Gcash直连 2 Gcash扫码 3 Maya 4 VietQR',
  `memo` text COMMENT '备注',
  `additional` varchar(1000) NOT NULL DEFAULT ' ' COMMENT '额外的参数',
  `code` varchar(64) NOT NULL DEFAULT ' ' COMMENT '代码控制器名称',
  `df_charge_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '代付API扣除手续费方式，0：从到账金额里扣，1：从商户余额里扣',
  `bankcode` varchar(100) NOT NULL COMMENT '银行编码',
  `df_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '代付通道id',
  `df_name` varchar(64) NOT NULL DEFAULT ' ' COMMENT '代付名称',
  `channel_mch_id` varchar(50) NOT NULL DEFAULT '' COMMENT '通道商户号',
  `cost` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '成本',
  `cost_rate` decimal(10,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '成本费率',
  `rate_type` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '费率类型：按单笔收费0，按比例收费：1，按单笔+比例收费：2',
  `extends` text COMMENT '扩展数据',
  `auto_submit_try` int(10) NOT NULL DEFAULT '0' COMMENT '自动代付尝试提交次数',
  `last_submit_time` int(11) NOT NULL DEFAULT '0' COMMENT '最后提交时间',
  `df_lock` tinyint(1) NOT NULL DEFAULT '0' COMMENT '代付锁，防止重复提交',
  `lock_time` int(11) NOT NULL DEFAULT '0' COMMENT '锁定时间',
  `auto_query_num` int(10) NOT NULL DEFAULT '0' COMMENT '自动查询次数',
  `billno` varchar(50) NOT NULL DEFAULT '' COMMENT '上游交易流水号',
  `notifyurl` varchar(255) DEFAULT '' COMMENT '异步通知地址',
  `notifycount` int(1) unsigned zerofill NOT NULL DEFAULT '0' COMMENT '回调次数',
  `last_notify_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '上次回调时间',
  `last_reissue_time` int(11) DEFAULT '0',
  `num` tinyint(2) DEFAULT '0',
  `is_auto` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否自动提交',
  `type` varchar(20) DEFAULT NULL COMMENT '代付类型：CPF、PHONE、EMAIL、EVP',
  `df_type` tinyint(1) DEFAULT '1' COMMENT '下发类型。1:普通代付，2:U下发',
  `three_orderid` varchar(100) DEFAULT NULL COMMENT '三方订单号',
  `currency` varchar(10) NOT NULL COMMENT '国家货币',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `code` (`code`) USING BTREE,
  KEY `df_id` (`df_id`) USING BTREE,
  KEY `orderid` (`orderid`) USING BTREE,
  KEY `sqdatetime` (`sqdatetime`) USING BTREE,
  KEY `out_trade_no` (`out_trade_no`,`userid`) USING BTREE,
  KEY `cldatetime` (`cldatetime`) USING BTREE,
  KEY `status` (`status`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_wttklist0520`
--

LOCK TABLES `pay_wttklist0520` WRITE;
/*!40000 ALTER TABLE `pay_wttklist0520` DISABLE KEYS */;
/*!40000 ALTER TABLE `pay_wttklist0520` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_wttklist0521`
--

DROP TABLE IF EXISTS `pay_wttklist0521`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_wttklist0521` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `orderid` varchar(100) NOT NULL DEFAULT ' ' COMMENT '订单id',
  `out_trade_no` varchar(30) NOT NULL DEFAULT '' COMMENT '下游订单号',
  `bankname` varchar(300) NOT NULL,
  `bankzhiname` varchar(300) DEFAULT NULL,
  `banknumber` varchar(300) NOT NULL,
  `bankfullname` varchar(300) NOT NULL,
  `tkmoney` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `sxfmoney` decimal(15,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '手续费',
  `money` decimal(15,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '实际到账',
  `sqdatetime` datetime NOT NULL,
  `cldatetime` datetime DEFAULT NULL,
  `status` enum('0','1','2','3','4','5','6') NOT NULL DEFAULT '0' COMMENT '状态:0=未处理,1=处理中,2=成功未返回,3=成功已返回,4=失败未返回,5=失败已返回,6=已驳回,',
  `paytype` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '渠道类型: 1 Gcash直连 2 Gcash扫码 3 Maya 4 VietQR',
  `memo` text COMMENT '备注',
  `additional` varchar(1000) NOT NULL DEFAULT ' ' COMMENT '额外的参数',
  `code` varchar(64) NOT NULL DEFAULT ' ' COMMENT '代码控制器名称',
  `df_charge_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '代付API扣除手续费方式，0：从到账金额里扣，1：从商户余额里扣',
  `bankcode` varchar(100) NOT NULL COMMENT '银行编码',
  `df_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '代付通道id',
  `df_name` varchar(64) NOT NULL DEFAULT ' ' COMMENT '代付名称',
  `channel_mch_id` varchar(50) NOT NULL DEFAULT '' COMMENT '通道商户号',
  `cost` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '成本',
  `cost_rate` decimal(10,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '成本费率',
  `rate_type` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '费率类型：按单笔收费0，按比例收费：1，按单笔+比例收费：2',
  `extends` text COMMENT '扩展数据',
  `auto_submit_try` int(10) NOT NULL DEFAULT '0' COMMENT '自动代付尝试提交次数',
  `last_submit_time` int(11) NOT NULL DEFAULT '0' COMMENT '最后提交时间',
  `df_lock` tinyint(1) NOT NULL DEFAULT '0' COMMENT '代付锁，防止重复提交',
  `lock_time` int(11) NOT NULL DEFAULT '0' COMMENT '锁定时间',
  `auto_query_num` int(10) NOT NULL DEFAULT '0' COMMENT '自动查询次数',
  `billno` varchar(50) NOT NULL DEFAULT '' COMMENT '上游交易流水号',
  `notifyurl` varchar(255) DEFAULT '' COMMENT '异步通知地址',
  `notifycount` int(1) unsigned zerofill NOT NULL DEFAULT '0' COMMENT '回调次数',
  `last_notify_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '上次回调时间',
  `last_reissue_time` int(11) DEFAULT '0',
  `num` tinyint(2) DEFAULT '0',
  `is_auto` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否自动提交',
  `type` varchar(20) DEFAULT NULL COMMENT '代付类型：CPF、PHONE、EMAIL、EVP',
  `df_type` tinyint(1) DEFAULT '1' COMMENT '下发类型。1:普通代付，2:U下发',
  `three_orderid` varchar(100) DEFAULT NULL COMMENT '三方订单号',
  `currency` varchar(10) NOT NULL COMMENT '国家货币',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `code` (`code`) USING BTREE,
  KEY `df_id` (`df_id`) USING BTREE,
  KEY `orderid` (`orderid`) USING BTREE,
  KEY `sqdatetime` (`sqdatetime`) USING BTREE,
  KEY `out_trade_no` (`out_trade_no`,`userid`) USING BTREE,
  KEY `cldatetime` (`cldatetime`) USING BTREE,
  KEY `status` (`status`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_wttklist0521`
--

LOCK TABLES `pay_wttklist0521` WRITE;
/*!40000 ALTER TABLE `pay_wttklist0521` DISABLE KEYS */;
/*!40000 ALTER TABLE `pay_wttklist0521` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_wttklist0522`
--

DROP TABLE IF EXISTS `pay_wttklist0522`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_wttklist0522` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `orderid` varchar(100) NOT NULL DEFAULT ' ' COMMENT '订单id',
  `out_trade_no` varchar(30) NOT NULL DEFAULT '' COMMENT '下游订单号',
  `bankname` varchar(300) NOT NULL,
  `bankzhiname` varchar(300) DEFAULT NULL,
  `banknumber` varchar(300) NOT NULL,
  `bankfullname` varchar(300) NOT NULL,
  `tkmoney` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `sxfmoney` decimal(15,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '手续费',
  `money` decimal(15,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '实际到账',
  `sqdatetime` datetime NOT NULL,
  `cldatetime` datetime DEFAULT NULL,
  `status` enum('0','1','2','3','4','5','6') NOT NULL DEFAULT '0' COMMENT '状态:0=未处理,1=处理中,2=成功未返回,3=成功已返回,4=失败未返回,5=失败已返回,6=已驳回,',
  `paytype` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '渠道类型: 1 Gcash直连 2 Gcash扫码 3 Maya 4 VietQR',
  `memo` text COMMENT '备注',
  `additional` varchar(1000) NOT NULL DEFAULT ' ' COMMENT '额外的参数',
  `code` varchar(64) NOT NULL DEFAULT ' ' COMMENT '代码控制器名称',
  `df_charge_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '代付API扣除手续费方式，0：从到账金额里扣，1：从商户余额里扣',
  `bankcode` varchar(100) NOT NULL COMMENT '银行编码',
  `df_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '代付通道id',
  `df_name` varchar(64) NOT NULL DEFAULT ' ' COMMENT '代付名称',
  `channel_mch_id` varchar(50) NOT NULL DEFAULT '' COMMENT '通道商户号',
  `cost` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '成本',
  `cost_rate` decimal(10,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '成本费率',
  `rate_type` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '费率类型：按单笔收费0，按比例收费：1，按单笔+比例收费：2',
  `extends` text COMMENT '扩展数据',
  `auto_submit_try` int(10) NOT NULL DEFAULT '0' COMMENT '自动代付尝试提交次数',
  `last_submit_time` int(11) NOT NULL DEFAULT '0' COMMENT '最后提交时间',
  `df_lock` tinyint(1) NOT NULL DEFAULT '0' COMMENT '代付锁，防止重复提交',
  `lock_time` int(11) NOT NULL DEFAULT '0' COMMENT '锁定时间',
  `auto_query_num` int(10) NOT NULL DEFAULT '0' COMMENT '自动查询次数',
  `billno` varchar(50) NOT NULL DEFAULT '' COMMENT '上游交易流水号',
  `notifyurl` varchar(255) DEFAULT '' COMMENT '异步通知地址',
  `notifycount` int(1) unsigned zerofill NOT NULL DEFAULT '0' COMMENT '回调次数',
  `last_notify_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '上次回调时间',
  `last_reissue_time` int(11) DEFAULT '0',
  `num` tinyint(2) DEFAULT '0',
  `is_auto` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否自动提交',
  `type` varchar(20) DEFAULT NULL COMMENT '代付类型：CPF、PHONE、EMAIL、EVP',
  `df_type` tinyint(1) DEFAULT '1' COMMENT '下发类型。1:普通代付，2:U下发',
  `three_orderid` varchar(100) DEFAULT NULL COMMENT '三方订单号',
  `currency` varchar(10) NOT NULL COMMENT '国家货币',
  PRIMARY KEY (`id`),
  KEY `code` (`code`) USING BTREE,
  KEY `df_id` (`df_id`) USING BTREE,
  KEY `orderid` (`orderid`) USING BTREE,
  KEY `sqdatetime` (`sqdatetime`) USING BTREE,
  KEY `out_trade_no` (`out_trade_no`,`userid`) USING BTREE,
  KEY `cldatetime` (`cldatetime`) USING BTREE,
  KEY `status` (`status`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_wttklist0522`
--

LOCK TABLES `pay_wttklist0522` WRITE;
/*!40000 ALTER TABLE `pay_wttklist0522` DISABLE KEYS */;
/*!40000 ALTER TABLE `pay_wttklist0522` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_wttklist0523`
--

DROP TABLE IF EXISTS `pay_wttklist0523`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_wttklist0523` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `orderid` varchar(100) NOT NULL DEFAULT ' ' COMMENT '订单id',
  `out_trade_no` varchar(30) NOT NULL DEFAULT '' COMMENT '下游订单号',
  `bankname` varchar(300) NOT NULL,
  `bankzhiname` varchar(300) DEFAULT NULL,
  `banknumber` varchar(300) NOT NULL,
  `bankfullname` varchar(300) NOT NULL,
  `tkmoney` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `sxfmoney` decimal(15,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '手续费',
  `money` decimal(15,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '实际到账',
  `sqdatetime` datetime NOT NULL,
  `cldatetime` datetime DEFAULT NULL,
  `status` enum('0','1','2','3','4','5','6') NOT NULL DEFAULT '0' COMMENT '状态:0=未处理,1=处理中,2=成功未返回,3=成功已返回,4=失败未返回,5=失败已返回,6=已驳回,',
  `paytype` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '渠道类型: 1 Gcash直连 2 Gcash扫码 3 Maya 4 VietQR',
  `memo` text COMMENT '备注',
  `additional` varchar(1000) NOT NULL DEFAULT ' ' COMMENT '额外的参数',
  `code` varchar(64) NOT NULL DEFAULT ' ' COMMENT '代码控制器名称',
  `df_charge_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '代付API扣除手续费方式，0：从到账金额里扣，1：从商户余额里扣',
  `bankcode` varchar(100) NOT NULL COMMENT '银行编码',
  `df_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '代付通道id',
  `df_name` varchar(64) NOT NULL DEFAULT ' ' COMMENT '代付名称',
  `channel_mch_id` varchar(50) NOT NULL DEFAULT '' COMMENT '通道商户号',
  `cost` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '成本',
  `cost_rate` decimal(10,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '成本费率',
  `rate_type` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '费率类型：按单笔收费0，按比例收费：1，按单笔+比例收费：2',
  `extends` text COMMENT '扩展数据',
  `auto_submit_try` int(10) NOT NULL DEFAULT '0' COMMENT '自动代付尝试提交次数',
  `last_submit_time` int(11) NOT NULL DEFAULT '0' COMMENT '最后提交时间',
  `df_lock` tinyint(1) NOT NULL DEFAULT '0' COMMENT '代付锁，防止重复提交',
  `lock_time` int(11) NOT NULL DEFAULT '0' COMMENT '锁定时间',
  `auto_query_num` int(10) NOT NULL DEFAULT '0' COMMENT '自动查询次数',
  `billno` varchar(50) NOT NULL DEFAULT '' COMMENT '上游交易流水号',
  `notifyurl` varchar(255) DEFAULT '' COMMENT '异步通知地址',
  `notifycount` int(1) unsigned zerofill NOT NULL DEFAULT '0' COMMENT '回调次数',
  `last_notify_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '上次回调时间',
  `last_reissue_time` int(11) DEFAULT '0',
  `num` tinyint(2) DEFAULT '0',
  `is_auto` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否自动提交',
  `type` varchar(20) DEFAULT NULL COMMENT '代付类型：CPF、PHONE、EMAIL、EVP',
  `df_type` tinyint(1) DEFAULT '1' COMMENT '下发类型。1:普通代付，2:U下发',
  `three_orderid` varchar(100) DEFAULT NULL COMMENT '三方订单号',
  `currency` varchar(10) NOT NULL COMMENT '国家货币',
  PRIMARY KEY (`id`),
  KEY `code` (`code`) USING BTREE,
  KEY `df_id` (`df_id`) USING BTREE,
  KEY `orderid` (`orderid`) USING BTREE,
  KEY `sqdatetime` (`sqdatetime`) USING BTREE,
  KEY `out_trade_no` (`out_trade_no`,`userid`) USING BTREE,
  KEY `cldatetime` (`cldatetime`) USING BTREE,
  KEY `status` (`status`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_wttklist0523`
--

LOCK TABLES `pay_wttklist0523` WRITE;
/*!40000 ALTER TABLE `pay_wttklist0523` DISABLE KEYS */;
INSERT INTO `pay_wttklist0523` VALUES (2,2,'Q2026052316104223842614697','202605231610421027','Gcash','Gcash','09327415749','robert mabaga',100.0000,0.0000,100.0000,'2026-05-23 16:10:42','2026-05-23 16:28:24','5',0,'cs - 2026-05-23 16:28:24;状态不改变！-支付类型错误2026-05-23 16:10:42-',' ','HiPayDFGcash',1,'7717',11,'HiPay代付--Gcash原生','M1765160951',0.00,0.0000,0,'Gcash',1,1779523842,0,1779523842,0,'','https://glht.maxpayhk.com/dhfpay/notify.php',1,1779524905,0,0,1,'0',1,NULL,'PHP'),(3,2,'Q2026052316282824908247803','202605231628286255','Gcash','Gcash','09327415749','robert mabaga',100.0000,0.0000,100.0000,'2026-05-23 16:28:28','2026-05-23 16:28:55','3',0,'代付成功！','INSTAPAY','HiPayDFGcash',1,'7717',11,'HiPay代付--Gcash原生','M1765160951',0.00,0.0000,0,'Gcash',1,1779524909,0,1779524909,0,'466651','https://glht.maxpayhk.com/dhfpay/notify.php',1,1779524935,0,0,1,'Gcash',1,NULL,'PHP');
/*!40000 ALTER TABLE `pay_wttklist0523` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_wttklist0524`
--

DROP TABLE IF EXISTS `pay_wttklist0524`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_wttklist0524` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `orderid` varchar(100) NOT NULL DEFAULT ' ' COMMENT '订单id',
  `out_trade_no` varchar(30) NOT NULL DEFAULT '' COMMENT '下游订单号',
  `bankname` varchar(300) NOT NULL,
  `bankzhiname` varchar(300) DEFAULT NULL,
  `banknumber` varchar(300) NOT NULL,
  `bankfullname` varchar(300) NOT NULL,
  `tkmoney` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `sxfmoney` decimal(15,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '手续费',
  `money` decimal(15,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '实际到账',
  `sqdatetime` datetime NOT NULL,
  `cldatetime` datetime DEFAULT NULL,
  `status` enum('0','1','2','3','4','5','6') NOT NULL DEFAULT '0' COMMENT '状态:0=未处理,1=处理中,2=成功未返回,3=成功已返回,4=失败未返回,5=失败已返回,6=已驳回,',
  `paytype` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '渠道类型: 1 Gcash直连 2 Gcash扫码 3 Maya 4 VietQR',
  `memo` text COMMENT '备注',
  `additional` varchar(1000) NOT NULL DEFAULT ' ' COMMENT '额外的参数',
  `code` varchar(64) NOT NULL DEFAULT ' ' COMMENT '代码控制器名称',
  `df_charge_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '代付API扣除手续费方式，0：从到账金额里扣，1：从商户余额里扣',
  `bankcode` varchar(100) NOT NULL COMMENT '银行编码',
  `df_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '代付通道id',
  `df_name` varchar(64) NOT NULL DEFAULT ' ' COMMENT '代付名称',
  `channel_mch_id` varchar(50) NOT NULL DEFAULT '' COMMENT '通道商户号',
  `cost` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '成本',
  `cost_rate` decimal(10,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '成本费率',
  `rate_type` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '费率类型：按单笔收费0，按比例收费：1，按单笔+比例收费：2',
  `extends` text COMMENT '扩展数据',
  `auto_submit_try` int(10) NOT NULL DEFAULT '0' COMMENT '自动代付尝试提交次数',
  `last_submit_time` int(11) NOT NULL DEFAULT '0' COMMENT '最后提交时间',
  `df_lock` tinyint(1) NOT NULL DEFAULT '0' COMMENT '代付锁，防止重复提交',
  `lock_time` int(11) NOT NULL DEFAULT '0' COMMENT '锁定时间',
  `auto_query_num` int(10) NOT NULL DEFAULT '0' COMMENT '自动查询次数',
  `billno` varchar(50) NOT NULL DEFAULT '' COMMENT '上游交易流水号',
  `notifyurl` varchar(255) DEFAULT '' COMMENT '异步通知地址',
  `notifycount` int(1) unsigned zerofill NOT NULL DEFAULT '0' COMMENT '回调次数',
  `last_notify_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '上次回调时间',
  `last_reissue_time` int(11) DEFAULT '0',
  `num` tinyint(2) DEFAULT '0',
  `is_auto` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否自动提交',
  `type` varchar(20) DEFAULT NULL COMMENT '代付类型：CPF、PHONE、EMAIL、EVP',
  `df_type` tinyint(1) DEFAULT '1' COMMENT '下发类型。1:普通代付，2:U下发',
  `three_orderid` varchar(100) DEFAULT NULL COMMENT '三方订单号',
  `currency` varchar(10) NOT NULL COMMENT '国家货币',
  PRIMARY KEY (`id`),
  KEY `code` (`code`) USING BTREE,
  KEY `df_id` (`df_id`) USING BTREE,
  KEY `orderid` (`orderid`) USING BTREE,
  KEY `sqdatetime` (`sqdatetime`) USING BTREE,
  KEY `out_trade_no` (`out_trade_no`,`userid`) USING BTREE,
  KEY `cldatetime` (`cldatetime`) USING BTREE,
  KEY `status` (`status`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_wttklist0524`
--

LOCK TABLES `pay_wttklist0524` WRITE;
/*!40000 ALTER TABLE `pay_wttklist0524` DISABLE KEYS */;
/*!40000 ALTER TABLE `pay_wttklist0524` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_wttklist0525`
--

DROP TABLE IF EXISTS `pay_wttklist0525`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_wttklist0525` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `orderid` varchar(100) NOT NULL DEFAULT ' ' COMMENT '订单id',
  `out_trade_no` varchar(30) NOT NULL DEFAULT '' COMMENT '下游订单号',
  `bankname` varchar(300) NOT NULL,
  `bankzhiname` varchar(300) DEFAULT NULL,
  `banknumber` varchar(300) NOT NULL,
  `bankfullname` varchar(300) NOT NULL,
  `tkmoney` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `sxfmoney` decimal(15,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '手续费',
  `money` decimal(15,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '实际到账',
  `sqdatetime` datetime NOT NULL,
  `cldatetime` datetime DEFAULT NULL,
  `status` enum('0','1','2','3','4','5','6') NOT NULL DEFAULT '0' COMMENT '状态:0=未处理,1=处理中,2=成功未返回,3=成功已返回,4=失败未返回,5=失败已返回,6=已驳回,',
  `paytype` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '渠道类型: 1 Gcash直连 2 Gcash扫码 3 Maya 4 VietQR',
  `memo` text COMMENT '备注',
  `additional` varchar(1000) NOT NULL DEFAULT ' ' COMMENT '额外的参数',
  `code` varchar(64) NOT NULL DEFAULT ' ' COMMENT '代码控制器名称',
  `df_charge_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '代付API扣除手续费方式，0：从到账金额里扣，1：从商户余额里扣',
  `bankcode` varchar(100) NOT NULL COMMENT '银行编码',
  `df_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '代付通道id',
  `df_name` varchar(64) NOT NULL DEFAULT ' ' COMMENT '代付名称',
  `channel_mch_id` varchar(50) NOT NULL DEFAULT '' COMMENT '通道商户号',
  `cost` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '成本',
  `cost_rate` decimal(10,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '成本费率',
  `rate_type` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '费率类型：按单笔收费0，按比例收费：1，按单笔+比例收费：2',
  `extends` text COMMENT '扩展数据',
  `auto_submit_try` int(10) NOT NULL DEFAULT '0' COMMENT '自动代付尝试提交次数',
  `last_submit_time` int(11) NOT NULL DEFAULT '0' COMMENT '最后提交时间',
  `df_lock` tinyint(1) NOT NULL DEFAULT '0' COMMENT '代付锁，防止重复提交',
  `lock_time` int(11) NOT NULL DEFAULT '0' COMMENT '锁定时间',
  `auto_query_num` int(10) NOT NULL DEFAULT '0' COMMENT '自动查询次数',
  `billno` varchar(50) NOT NULL DEFAULT '' COMMENT '上游交易流水号',
  `notifyurl` varchar(255) DEFAULT '' COMMENT '异步通知地址',
  `notifycount` int(1) unsigned zerofill NOT NULL DEFAULT '0' COMMENT '回调次数',
  `last_notify_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '上次回调时间',
  `last_reissue_time` int(11) DEFAULT '0',
  `num` tinyint(2) DEFAULT '0',
  `is_auto` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否自动提交',
  `type` varchar(20) DEFAULT NULL COMMENT '代付类型：CPF、PHONE、EMAIL、EVP',
  `df_type` tinyint(1) DEFAULT '1' COMMENT '下发类型。1:普通代付，2:U下发',
  `three_orderid` varchar(100) DEFAULT NULL COMMENT '三方订单号',
  `currency` varchar(10) NOT NULL COMMENT '国家货币',
  PRIMARY KEY (`id`),
  KEY `code` (`code`) USING BTREE,
  KEY `df_id` (`df_id`) USING BTREE,
  KEY `orderid` (`orderid`) USING BTREE,
  KEY `sqdatetime` (`sqdatetime`) USING BTREE,
  KEY `out_trade_no` (`out_trade_no`,`userid`) USING BTREE,
  KEY `cldatetime` (`cldatetime`) USING BTREE,
  KEY `status` (`status`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_wttklist0525`
--

LOCK TABLES `pay_wttklist0525` WRITE;
/*!40000 ALTER TABLE `pay_wttklist0525` DISABLE KEYS */;
/*!40000 ALTER TABLE `pay_wttklist0525` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_wttklist0526`
--

DROP TABLE IF EXISTS `pay_wttklist0526`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_wttklist0526` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `orderid` varchar(100) NOT NULL DEFAULT ' ' COMMENT '订单id',
  `out_trade_no` varchar(30) NOT NULL DEFAULT '' COMMENT '下游订单号',
  `bankname` varchar(300) NOT NULL,
  `bankzhiname` varchar(300) DEFAULT NULL,
  `banknumber` varchar(300) NOT NULL,
  `bankfullname` varchar(300) NOT NULL,
  `tkmoney` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `sxfmoney` decimal(15,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '手续费',
  `money` decimal(15,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '实际到账',
  `sqdatetime` datetime NOT NULL,
  `cldatetime` datetime DEFAULT NULL,
  `status` enum('0','1','2','3','4','5','6') NOT NULL DEFAULT '0' COMMENT '状态:0=未处理,1=处理中,2=成功未返回,3=成功已返回,4=失败未返回,5=失败已返回,6=已驳回,',
  `paytype` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '渠道类型: 1 Gcash直连 2 Gcash扫码 3 Maya 4 VietQR',
  `memo` text COMMENT '备注',
  `additional` varchar(1000) NOT NULL DEFAULT ' ' COMMENT '额外的参数',
  `code` varchar(64) NOT NULL DEFAULT ' ' COMMENT '代码控制器名称',
  `df_charge_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '代付API扣除手续费方式，0：从到账金额里扣，1：从商户余额里扣',
  `bankcode` varchar(100) NOT NULL COMMENT '银行编码',
  `df_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '代付通道id',
  `df_name` varchar(64) NOT NULL DEFAULT ' ' COMMENT '代付名称',
  `channel_mch_id` varchar(50) NOT NULL DEFAULT '' COMMENT '通道商户号',
  `cost` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '成本',
  `cost_rate` decimal(10,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '成本费率',
  `rate_type` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '费率类型：按单笔收费0，按比例收费：1，按单笔+比例收费：2',
  `extends` text COMMENT '扩展数据',
  `auto_submit_try` int(10) NOT NULL DEFAULT '0' COMMENT '自动代付尝试提交次数',
  `last_submit_time` int(11) NOT NULL DEFAULT '0' COMMENT '最后提交时间',
  `df_lock` tinyint(1) NOT NULL DEFAULT '0' COMMENT '代付锁，防止重复提交',
  `lock_time` int(11) NOT NULL DEFAULT '0' COMMENT '锁定时间',
  `auto_query_num` int(10) NOT NULL DEFAULT '0' COMMENT '自动查询次数',
  `billno` varchar(50) NOT NULL DEFAULT '' COMMENT '上游交易流水号',
  `notifyurl` varchar(255) DEFAULT '' COMMENT '异步通知地址',
  `notifycount` int(1) unsigned zerofill NOT NULL DEFAULT '0' COMMENT '回调次数',
  `last_notify_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '上次回调时间',
  `last_reissue_time` int(11) DEFAULT '0',
  `num` tinyint(2) DEFAULT '0',
  `is_auto` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否自动提交',
  `type` varchar(20) DEFAULT NULL COMMENT '代付类型：CPF、PHONE、EMAIL、EVP',
  `df_type` tinyint(1) DEFAULT '1' COMMENT '下发类型。1:普通代付，2:U下发',
  `three_orderid` varchar(100) DEFAULT NULL COMMENT '三方订单号',
  `currency` varchar(10) NOT NULL COMMENT '国家货币',
  PRIMARY KEY (`id`),
  KEY `code` (`code`) USING BTREE,
  KEY `df_id` (`df_id`) USING BTREE,
  KEY `orderid` (`orderid`) USING BTREE,
  KEY `sqdatetime` (`sqdatetime`) USING BTREE,
  KEY `out_trade_no` (`out_trade_no`,`userid`) USING BTREE,
  KEY `cldatetime` (`cldatetime`) USING BTREE,
  KEY `status` (`status`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_wttklist0526`
--

LOCK TABLES `pay_wttklist0526` WRITE;
/*!40000 ALTER TABLE `pay_wttklist0526` DISABLE KEYS */;
/*!40000 ALTER TABLE `pay_wttklist0526` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_wttklist0527`
--

DROP TABLE IF EXISTS `pay_wttklist0527`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_wttklist0527` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `orderid` varchar(100) NOT NULL DEFAULT ' ' COMMENT '订单id',
  `out_trade_no` varchar(30) NOT NULL DEFAULT '' COMMENT '下游订单号',
  `bankname` varchar(300) NOT NULL,
  `bankzhiname` varchar(300) DEFAULT NULL,
  `banknumber` varchar(300) NOT NULL,
  `bankfullname` varchar(300) NOT NULL,
  `tkmoney` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `sxfmoney` decimal(15,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '手续费',
  `money` decimal(15,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '实际到账',
  `sqdatetime` datetime NOT NULL,
  `cldatetime` datetime DEFAULT NULL,
  `status` enum('0','1','2','3','4','5','6') NOT NULL DEFAULT '0' COMMENT '状态:0=未处理,1=处理中,2=成功未返回,3=成功已返回,4=失败未返回,5=失败已返回,6=已驳回,',
  `paytype` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '渠道类型: 1 Gcash直连 2 Gcash扫码 3 Maya 4 VietQR',
  `memo` text COMMENT '备注',
  `additional` varchar(1000) NOT NULL DEFAULT ' ' COMMENT '额外的参数',
  `code` varchar(64) NOT NULL DEFAULT ' ' COMMENT '代码控制器名称',
  `df_charge_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '代付API扣除手续费方式，0：从到账金额里扣，1：从商户余额里扣',
  `bankcode` varchar(100) NOT NULL COMMENT '银行编码',
  `df_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '代付通道id',
  `df_name` varchar(64) NOT NULL DEFAULT ' ' COMMENT '代付名称',
  `channel_mch_id` varchar(50) NOT NULL DEFAULT '' COMMENT '通道商户号',
  `cost` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '成本',
  `cost_rate` decimal(10,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '成本费率',
  `rate_type` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '费率类型：按单笔收费0，按比例收费：1，按单笔+比例收费：2',
  `extends` text COMMENT '扩展数据',
  `auto_submit_try` int(10) NOT NULL DEFAULT '0' COMMENT '自动代付尝试提交次数',
  `last_submit_time` int(11) NOT NULL DEFAULT '0' COMMENT '最后提交时间',
  `df_lock` tinyint(1) NOT NULL DEFAULT '0' COMMENT '代付锁，防止重复提交',
  `lock_time` int(11) NOT NULL DEFAULT '0' COMMENT '锁定时间',
  `auto_query_num` int(10) NOT NULL DEFAULT '0' COMMENT '自动查询次数',
  `billno` varchar(50) NOT NULL DEFAULT '' COMMENT '上游交易流水号',
  `notifyurl` varchar(255) DEFAULT '' COMMENT '异步通知地址',
  `notifycount` int(1) unsigned zerofill NOT NULL DEFAULT '0' COMMENT '回调次数',
  `last_notify_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '上次回调时间',
  `last_reissue_time` int(11) DEFAULT '0',
  `num` tinyint(2) DEFAULT '0',
  `is_auto` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否自动提交',
  `type` varchar(20) DEFAULT NULL COMMENT '代付类型：CPF、PHONE、EMAIL、EVP',
  `df_type` tinyint(1) DEFAULT '1' COMMENT '下发类型。1:普通代付，2:U下发',
  `three_orderid` varchar(100) DEFAULT NULL COMMENT '三方订单号',
  `currency` varchar(10) NOT NULL COMMENT '国家货币',
  PRIMARY KEY (`id`),
  KEY `code` (`code`) USING BTREE,
  KEY `df_id` (`df_id`) USING BTREE,
  KEY `orderid` (`orderid`) USING BTREE,
  KEY `sqdatetime` (`sqdatetime`) USING BTREE,
  KEY `out_trade_no` (`out_trade_no`,`userid`) USING BTREE,
  KEY `cldatetime` (`cldatetime`) USING BTREE,
  KEY `status` (`status`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_wttklist0527`
--

LOCK TABLES `pay_wttklist0527` WRITE;
/*!40000 ALTER TABLE `pay_wttklist0527` DISABLE KEYS */;
/*!40000 ALTER TABLE `pay_wttklist0527` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pay_wttklist0528`
--

DROP TABLE IF EXISTS `pay_wttklist0528`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pay_wttklist0528` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `orderid` varchar(100) NOT NULL DEFAULT ' ' COMMENT '订单id',
  `out_trade_no` varchar(30) NOT NULL DEFAULT '' COMMENT '下游订单号',
  `bankname` varchar(300) NOT NULL,
  `bankzhiname` varchar(300) DEFAULT NULL,
  `banknumber` varchar(300) NOT NULL,
  `bankfullname` varchar(300) NOT NULL,
  `tkmoney` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `sxfmoney` decimal(15,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '手续费',
  `money` decimal(15,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '实际到账',
  `sqdatetime` datetime NOT NULL,
  `cldatetime` datetime DEFAULT NULL,
  `status` enum('0','1','2','3','4','5','6') NOT NULL DEFAULT '0' COMMENT '状态:0=未处理,1=处理中,2=成功未返回,3=成功已返回,4=失败未返回,5=失败已返回,6=已驳回,',
  `paytype` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '渠道类型: 1 Gcash直连 2 Gcash扫码 3 Maya 4 VietQR',
  `memo` text COMMENT '备注',
  `additional` varchar(1000) NOT NULL DEFAULT ' ' COMMENT '额外的参数',
  `code` varchar(64) NOT NULL DEFAULT ' ' COMMENT '代码控制器名称',
  `df_charge_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '代付API扣除手续费方式，0：从到账金额里扣，1：从商户余额里扣',
  `bankcode` varchar(100) NOT NULL COMMENT '银行编码',
  `df_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '代付通道id',
  `df_name` varchar(64) NOT NULL DEFAULT ' ' COMMENT '代付名称',
  `channel_mch_id` varchar(50) NOT NULL DEFAULT '' COMMENT '通道商户号',
  `cost` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '成本',
  `cost_rate` decimal(10,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '成本费率',
  `rate_type` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '费率类型：按单笔收费0，按比例收费：1，按单笔+比例收费：2',
  `extends` text COMMENT '扩展数据',
  `auto_submit_try` int(10) NOT NULL DEFAULT '0' COMMENT '自动代付尝试提交次数',
  `last_submit_time` int(11) NOT NULL DEFAULT '0' COMMENT '最后提交时间',
  `df_lock` tinyint(1) NOT NULL DEFAULT '0' COMMENT '代付锁，防止重复提交',
  `lock_time` int(11) NOT NULL DEFAULT '0' COMMENT '锁定时间',
  `auto_query_num` int(10) NOT NULL DEFAULT '0' COMMENT '自动查询次数',
  `billno` varchar(50) NOT NULL DEFAULT '' COMMENT '上游交易流水号',
  `notifyurl` varchar(255) DEFAULT '' COMMENT '异步通知地址',
  `notifycount` int(1) unsigned zerofill NOT NULL DEFAULT '0' COMMENT '回调次数',
  `last_notify_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '上次回调时间',
  `last_reissue_time` int(11) DEFAULT '0',
  `num` tinyint(2) DEFAULT '0',
  `is_auto` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否自动提交',
  `type` varchar(20) DEFAULT NULL COMMENT '代付类型：CPF、PHONE、EMAIL、EVP',
  `df_type` tinyint(1) DEFAULT '1' COMMENT '下发类型。1:普通代付，2:U下发',
  `three_orderid` varchar(100) DEFAULT NULL COMMENT '三方订单号',
  `currency` varchar(10) NOT NULL COMMENT '国家货币',
  PRIMARY KEY (`id`),
  KEY `code` (`code`) USING BTREE,
  KEY `df_id` (`df_id`) USING BTREE,
  KEY `orderid` (`orderid`) USING BTREE,
  KEY `sqdatetime` (`sqdatetime`) USING BTREE,
  KEY `out_trade_no` (`out_trade_no`,`userid`) USING BTREE,
  KEY `cldatetime` (`cldatetime`) USING BTREE,
  KEY `status` (`status`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pay_wttklist0528`
--

LOCK TABLES `pay_wttklist0528` WRITE;
/*!40000 ALTER TABLE `pay_wttklist0528` DISABLE KEYS */;
/*!40000 ALTER TABLE `pay_wttklist0528` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping events for database 'marsdb'
--

--
-- Dumping routines for database 'marsdb'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-05-28  8:02:22
