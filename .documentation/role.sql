-- MySQL dump 10.13  Distrib 8.0.42, for Win64 (x86_64)
--
-- Host: localhost    Database: itfmvp
-- ------------------------------------------------------
-- Server version	5.5.5-10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `role`
--

DROP TABLE IF EXISTS `role`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `role` (
  `role_ID` int(11) NOT NULL AUTO_INCREMENT,
  `role_Name` varchar(30) NOT NULL,
  `role_Desc` varchar(50) DEFAULT NULL,
  `role_Level` int(11) DEFAULT NULL,
  `role_Entity` varchar(30) DEFAULT NULL,
  `create_ts` datetime NOT NULL DEFAULT current_timestamp(),
  `update_ts` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` varchar(30) DEFAULT NULL,
  `updated_by` varchar(30) DEFAULT NULL,
  PRIMARY KEY (`role_ID`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `role`
--

LOCK TABLES `role` WRITE;
/*!40000 ALTER TABLE `role` DISABLE KEYS */;
INSERT INTO `role` VALUES (1,'SysAdmin','ITF Administrator',1001,'All','2013-12-17 00:29:19','2015-09-30 21:04:12',NULL,NULL),(2,'OrgAdmin','',91,'organization','2013-10-30 14:56:20','2015-09-30 21:04:12',NULL,NULL),(3,'FacAdmin','Arena Administrator',81,'facility','2013-10-30 14:56:27','2015-12-15 22:32:46',NULL,NULL),(4,'ProgAdmin','Program Administrator',71,'program','2013-10-30 14:56:44','2015-12-15 22:32:50',NULL,NULL),(5,'ProgLevelAdmin','Program Level Administrator',61,'program_level','2013-10-30 14:59:42','2015-12-15 22:32:53',NULL,NULL),(6,'TeamAdmin','Team Administrator',51,'team','2013-10-30 15:03:42','2015-12-15 22:32:58',NULL,NULL),(8,'SchAdmin','',41,'schedule','2013-10-31 10:13:33','2015-09-30 21:04:12',NULL,NULL),(9,'CalAdmin','',31,'calendar','2014-01-29 00:18:31','2015-09-30 21:04:12',NULL,NULL),(10,'CalUser','',21,NULL,'2014-01-29 00:18:33','2015-09-30 21:04:12',NULL,NULL),(11,'iCalUser','',21,NULL,'2014-03-02 15:46:19','2015-09-30 21:04:12',NULL,NULL),(12,'PublicUser','Public User',11,'public','2014-03-14 18:00:18','2015-09-30 21:04:12',NULL,NULL);
/*!40000 ALTER TABLE `role` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-06-08 21:45:56
