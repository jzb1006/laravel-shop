-- MySQL dump 10.13  Distrib 5.7.25, for Linux (x86_64)
--
-- Host: 192.168.10.10    Database: laravel-shop
-- ------------------------------------------------------
-- Server version	5.7.25-0ubuntu0.18.04.2

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Dumping data for table `admin_menu`
--

LOCK TABLES `admin_menu` WRITE;
/*!40000 ALTER TABLE `admin_menu` DISABLE KEYS */;
INSERT INTO `admin_menu` VALUES (1,0,1,'Dashboard','fa-bar-chart','/',NULL,NULL,NULL),(2,0,9,'Admin','fa-tasks','',NULL,NULL,'2019-07-27 08:17:11'),(3,2,10,'Users','fa-users','auth/users',NULL,NULL,'2019-07-27 08:17:11'),(4,2,11,'Roles','fa-user','auth/roles',NULL,NULL,'2019-07-27 08:17:11'),(5,2,12,'Permission','fa-ban','auth/permissions',NULL,NULL,'2019-07-27 08:17:11'),(6,2,13,'Menu','fa-bars','auth/menu',NULL,NULL,'2019-07-27 08:17:11'),(7,2,14,'操作日志','fa-history','auth/logs',NULL,NULL,'2019-07-27 08:17:11'),(8,0,2,'用户管理','fa-users','/users',NULL,'2019-07-02 06:29:35','2019-07-02 07:43:47'),(9,0,3,'商品管理','fa-cubes','/products',NULL,'2019-07-02 07:43:08','2019-07-02 07:43:47'),(10,0,6,'订单管理','fa-bars','/orders',NULL,'2019-07-23 03:57:21','2019-07-27 08:17:11'),(11,0,7,'优惠券管理','fa-tag','/coupon_codes',NULL,'2019-07-23 07:29:46','2019-07-27 08:17:11'),(12,0,8,'商品类目管理','fa-bars','/categories',NULL,'2019-07-26 06:17:29','2019-07-27 08:17:11'),(13,9,5,'众筹商品管理','fa-flag-checkered','/crowdfunding_products',NULL,'2019-07-27 08:14:11','2019-07-27 08:17:11'),(14,9,4,'普通商品管理啊','fa-cubes','/products',NULL,'2019-07-27 08:15:48','2019-07-27 08:17:11');
/*!40000 ALTER TABLE `admin_menu` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `admin_permissions`
--

LOCK TABLES `admin_permissions` WRITE;
/*!40000 ALTER TABLE `admin_permissions` DISABLE KEYS */;
INSERT INTO `admin_permissions` VALUES (1,'All permission','*','','*',NULL,NULL),(2,'Dashboard','dashboard','GET','/',NULL,NULL),(3,'Login','auth.login','','/auth/login\r\n/auth/logout',NULL,NULL),(4,'User setting','auth.setting','GET,PUT','/auth/setting',NULL,NULL),(5,'Auth management','auth.management','','/auth/roles\r\n/auth/permissions\r\n/auth/menu\r\n/auth/logs',NULL,NULL),(6,'用户管理','users','','/users*','2019-07-02 06:51:41','2019-07-02 06:51:41');
/*!40000 ALTER TABLE `admin_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `admin_role_menu`
--

LOCK TABLES `admin_role_menu` WRITE;
/*!40000 ALTER TABLE `admin_role_menu` DISABLE KEYS */;
INSERT INTO `admin_role_menu` VALUES (1,2,NULL,NULL);
/*!40000 ALTER TABLE `admin_role_menu` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `admin_role_permissions`
--

LOCK TABLES `admin_role_permissions` WRITE;
/*!40000 ALTER TABLE `admin_role_permissions` DISABLE KEYS */;
INSERT INTO `admin_role_permissions` VALUES (1,1,NULL,NULL),(2,2,NULL,NULL),(2,3,NULL,NULL),(2,4,NULL,NULL),(2,6,NULL,NULL);
/*!40000 ALTER TABLE `admin_role_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `admin_role_users`
--

LOCK TABLES `admin_role_users` WRITE;
/*!40000 ALTER TABLE `admin_role_users` DISABLE KEYS */;
INSERT INTO `admin_role_users` VALUES (1,1,NULL,NULL),(2,2,NULL,NULL);
/*!40000 ALTER TABLE `admin_role_users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `admin_roles`
--

LOCK TABLES `admin_roles` WRITE;
/*!40000 ALTER TABLE `admin_roles` DISABLE KEYS */;
INSERT INTO `admin_roles` VALUES (1,'Administrator','administrator','2019-07-01 09:11:03','2019-07-01 09:11:03'),(2,'运营','operator','2019-07-02 06:54:58','2019-07-02 06:54:58');
/*!40000 ALTER TABLE `admin_roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `admin_user_permissions`
--

LOCK TABLES `admin_user_permissions` WRITE;
/*!40000 ALTER TABLE `admin_user_permissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `admin_user_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `admin_users`
--

LOCK TABLES `admin_users` WRITE;
/*!40000 ALTER TABLE `admin_users` DISABLE KEYS */;
INSERT INTO `admin_users` VALUES (1,'admin','$2y$10$E2lO2k/053ze.thzzlrktelQNfeQgiWy/UNUvkIrSdQ5WB6sTRDUa','Administrator',NULL,'agBrOgDYxsaXWvMZo2ZIuwlwDITnUSfdJwpFHthxfYE9A3zl5N71tPLoEEiq','2019-07-01 09:11:03','2019-07-01 09:11:03'),(2,'operator','$2y$10$qT1KkD0dpiUvDrOmuwXDPede4.xa/aRp35VE/zhMuOntPin9MBaN.','运营',NULL,'aQVPd7jFSqNFPgkbMRFkuYKDYZVDaTZQ3UbZElvTOVY1u1Gdti0RHMU2Fa3I','2019-07-02 06:58:16','2019-07-02 07:01:52');
/*!40000 ALTER TABLE `admin_users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2019-07-27  8:37:04
