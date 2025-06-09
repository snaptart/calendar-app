CREATE DATABASE  IF NOT EXISTS `itmdev` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci */;
USE `itmdev`;
-- MySQL dump 10.13  Distrib 8.0.42, for Win64 (x86_64)
--
-- Host: localhost    Database: itmdev
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
-- Table structure for table `episode`
--

DROP TABLE IF EXISTS `episode`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `episode` (
  `episode_ID` int(11) NOT NULL AUTO_INCREMENT,
  `event_ID` int(11) NOT NULL,
  `resource_ID` int(11) NOT NULL DEFAULT 1,
  `facility_ID` int(11) DEFAULT 1,
  `program_ID` int(11) DEFAULT NULL,
  `team_ID_new` int(11) DEFAULT NULL,
  `schedule_ID` int(11) DEFAULT NULL,
  `team_ID` int(11) NOT NULL DEFAULT 1,
  `activity_ID` int(11) DEFAULT 1,
  `validation_Status_ID` int(11) DEFAULT NULL,
  `order_ID` int(11) DEFAULT NULL,
  `transaction_ID` int(11) DEFAULT NULL,
  `contact_ID` int(11) DEFAULT NULL,
  `team2_ID` int(11) DEFAULT NULL,
  `team3_ID` int(11) DEFAULT NULL,
  `team4_ID` int(11) DEFAULT NULL,
  `away_Program_Name` varchar(100) DEFAULT NULL,
  `away_Team_Name` varchar(100) DEFAULT NULL,
  `episode_Public_Flag` enum('Public','Private') DEFAULT 'Public',
  `episode_Editable_ID` int(11) DEFAULT 0,
  `episode_Seq_No` int(11) DEFAULT 0,
  `episode_Start_Date_Time` datetime NOT NULL,
  `episode_End_Date_Time` datetime NOT NULL,
  `episode_Duration` int(11) DEFAULT NULL,
  `episode_Title` varchar(100) DEFAULT NULL,
  `episode_Description` mediumtext DEFAULT NULL,
  `episode_Url` varchar(200) DEFAULT NULL,
  `episode_Color` varchar(20) DEFAULT NULL,
  `episode_Price` decimal(10,2) DEFAULT NULL,
  `episode_GL_Code` varchar(30) DEFAULT NULL,
  `update_ts` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `create_ts` datetime NOT NULL DEFAULT current_timestamp(),
  `created_by` varchar(30) DEFAULT NULL,
  `updated_by` varchar(30) DEFAULT NULL,
  `user_ID` int(11) DEFAULT NULL COMMENT 'Added to link episodes to users',
  PRIMARY KEY (`episode_ID`),
  KEY `event_ibfk1` (`event_ID`) USING BTREE,
  KEY `user_ibfk1` (`user_ID`) USING BTREE,
  KEY `episode_Start_Date_Time_ih1` (`episode_Start_Date_Time`) USING BTREE,
  KEY `episode_End_Date_Time_ih1` (`episode_End_Date_Time`) USING BTREE,
  KEY `episode_Public_Flag_ih1` (`episode_Public_Flag`) USING HASH,
  KEY `episode_facility_fk` (`facility_ID`),
  KEY `episode_program_fk` (`program_ID`),
  KEY `episode_team_fk` (`team_ID_new`),
  CONSTRAINT `episode_event_ibfk1` FOREIGN KEY (`event_ID`) REFERENCES `event` (`event_ID`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `episode_facility_fk` FOREIGN KEY (`facility_ID`) REFERENCES `facility` (`facility_ID`) ON DELETE SET NULL ON UPDATE NO ACTION,
  CONSTRAINT `episode_program_fk` FOREIGN KEY (`program_ID`) REFERENCES `program` (`program_ID`) ON DELETE SET NULL ON UPDATE NO ACTION,
  CONSTRAINT `episode_team_fk` FOREIGN KEY (`team_ID_new`) REFERENCES `team` (`team_ID`) ON DELETE SET NULL ON UPDATE NO ACTION,
  CONSTRAINT `episode_user_ibfk1` FOREIGN KEY (`user_ID`) REFERENCES `user` (`user_ID`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `episode`
--

LOCK TABLES `episode` WRITE;
/*!40000 ALTER TABLE `episode` DISABLE KEYS */;
INSERT INTO `episode` VALUES (1,1,1,1,1,1,NULL,1,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Public',0,0,'2025-06-10 10:10:00','2025-06-10 11:25:00',75,'OS hockey','Migrated from calendar-app event ID: 14',NULL,'#3498db',NULL,NULL,'2025-06-09 01:10:53','2025-06-08 00:11:12','calendar_migration',NULL,1),(2,2,1,1,2,6,NULL,1,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Public',0,0,'2025-06-08 13:15:00','2025-06-08 14:15:00',60,'asdf','Migrated from calendar-app event ID: 17',NULL,'#3498db',NULL,NULL,'2025-06-09 01:10:53','2025-06-08 10:35:57','calendar_migration',NULL,2),(3,3,1,1,2,6,NULL,1,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Public',0,0,'2025-06-08 11:00:00','2025-06-08 12:00:00',60,'survey jsed','Migrated from calendar-app event ID: 19',NULL,'#3498db',NULL,NULL,'2025-06-09 01:10:53','2025-06-08 10:56:43','calendar_migration',NULL,2),(4,4,1,1,2,6,NULL,1,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Public',0,0,'2025-06-08 11:45:00','2025-06-08 12:45:00',60,'new title','Migrated from calendar-app event ID: 20',NULL,'#3498db',NULL,NULL,'2025-06-09 01:10:53','2025-06-08 11:35:03','calendar_migration',NULL,2),(5,5,1,1,1,3,NULL,1,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Public',0,0,'2025-06-11 11:00:00','2025-06-11 12:00:00',60,'OS hockey','Migrated from calendar-app event ID: 21',NULL,'#f39c12',NULL,NULL,'2025-06-09 01:10:53','2025-06-08 12:51:10','calendar_migration',NULL,3),(6,6,6,3,6,17,NULL,1,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Public',0,0,'2025-06-12 11:00:00','2025-06-12 12:00:00',60,'me time','Migrated from calendar-app event ID: 22',NULL,'#3498db',NULL,NULL,'2025-06-09 01:10:53','2025-06-08 12:53:51','calendar_migration',NULL,4),(7,7,6,3,6,17,NULL,1,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Public',0,0,'2025-06-13 11:00:00','2025-06-13 12:15:00',75,'me time again','Migrated from calendar-app event ID: 23',NULL,'#3498db',NULL,NULL,'2025-06-09 01:10:53','2025-06-08 12:54:04','calendar_migration',NULL,4),(8,8,6,3,6,17,NULL,1,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Public',0,0,'2025-06-15 10:00:00','2025-06-15 11:00:00',60,'Team Meeting','Migrated from calendar-app event ID: 25',NULL,'#3498db',NULL,NULL,'2025-06-09 01:10:53','2025-06-08 14:46:54','calendar_migration',NULL,4),(9,9,6,3,6,17,NULL,1,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Public',0,0,'2025-06-16 14:00:00','2025-06-16 15:30:00',90,'Project Review','Migrated from calendar-app event ID: 26',NULL,'#3498db',NULL,NULL,'2025-06-09 01:10:53','2025-06-08 14:46:54','calendar_migration',NULL,4),(10,10,6,3,6,17,NULL,1,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Public',0,0,'2025-06-17 09:00:00','2025-06-17 10:00:00',60,'Client Call','Migrated from calendar-app event ID: 27',NULL,'#3498db',NULL,NULL,'2025-06-09 01:10:53','2025-06-08 14:46:54','calendar_migration',NULL,4),(11,11,6,3,6,17,NULL,1,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Public',0,0,'2025-06-18 13:00:00','2025-06-18 16:00:00',180,'Training Session','Migrated from calendar-app event ID: 28',NULL,'#3498db',NULL,NULL,'2025-06-09 01:10:53','2025-06-08 14:46:54','calendar_migration',NULL,4),(12,12,6,3,6,17,NULL,1,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Public',0,0,'2025-06-25 10:00:00','2025-06-25 11:00:00',60,'Team Meeting','Migrated from calendar-app event ID: 29',NULL,'#3498db',NULL,NULL,'2025-06-09 01:10:53','2025-06-08 15:28:31','calendar_migration',NULL,4),(13,13,6,3,6,17,NULL,1,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Public',0,0,'2025-06-26 14:00:00','2025-06-26 15:30:00',90,'Project Review','Migrated from calendar-app event ID: 30',NULL,'#3498db',NULL,NULL,'2025-06-09 01:10:53','2025-06-08 15:28:31','calendar_migration',NULL,4),(14,14,6,3,6,17,NULL,1,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Public',0,0,'2025-06-27 09:00:00','2025-06-27 10:00:00',60,'Client Call','Migrated from calendar-app event ID: 31',NULL,'#3498db',NULL,NULL,'2025-06-09 01:10:53','2025-06-08 15:28:31','calendar_migration',NULL,4),(15,15,6,3,6,17,NULL,1,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Public',0,0,'2025-06-28 13:00:00','2025-06-28 16:00:00',180,'Training Session','Migrated from calendar-app event ID: 32',NULL,'#3498db',NULL,NULL,'2025-06-09 01:10:53','2025-06-08 15:28:31','calendar_migration',NULL,4);
/*!40000 ALTER TABLE `episode` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `event`
--

DROP TABLE IF EXISTS `event`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `event` (
  `event_ID` int(11) NOT NULL AUTO_INCREMENT,
  `event_Type` varchar(30) DEFAULT NULL,
  `event_Start_Date` date DEFAULT NULL,
  `event_Start_Time` time DEFAULT NULL,
  `event_End_Date` date DEFAULT NULL,
  `event_End_Time` time DEFAULT NULL,
  `event_Start_Date_Time` datetime NOT NULL,
  `event_End_Date_Time` datetime NOT NULL,
  `event_Last_Date_Time` datetime DEFAULT NULL,
  `num_Occurrences` int(11) DEFAULT 1,
  `num_Conflicts` int(11) DEFAULT 0,
  `all_Day` varchar(11) DEFAULT 'No',
  `repeat_Mode` varchar(10) DEFAULT 'None',
  `repeat_Ends` varchar(30) DEFAULT NULL,
  `event_Dates` mediumtext DEFAULT NULL,
  `episode_Duration` int(11) DEFAULT NULL,
  `maintenance_Interval` int(11) DEFAULT NULL,
  `create_ts` datetime NOT NULL DEFAULT current_timestamp(),
  `update_ts` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` varchar(30) DEFAULT NULL,
  `updated_by` varchar(30) DEFAULT NULL,
  PRIMARY KEY (`event_ID`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `event`
--

LOCK TABLES `event` WRITE;
/*!40000 ALTER TABLE `event` DISABLE KEYS */;
INSERT INTO `event` VALUES (1,'Calendar Event','2025-06-10','10:10:00','2025-06-10','11:25:00','2025-06-10 10:10:00','2025-06-10 11:25:00','2025-06-10 11:25:00',1,0,'No','None',NULL,NULL,75,NULL,'2025-06-08 00:11:12','2025-06-08 05:11:40','calendar_migration',NULL),(2,'Calendar Event','2025-06-08','13:15:00','2025-06-08','14:15:00','2025-06-08 13:15:00','2025-06-08 14:15:00','2025-06-08 14:15:00',1,0,'No','None',NULL,NULL,60,NULL,'2025-06-08 10:35:57','2025-06-08 15:36:13','calendar_migration',NULL),(3,'Calendar Event','2025-06-08','11:00:00','2025-06-08','12:00:00','2025-06-08 11:00:00','2025-06-08 12:00:00','2025-06-08 12:00:00',1,0,'No','None',NULL,NULL,60,NULL,'2025-06-08 10:56:43','2025-06-08 15:56:43','calendar_migration',NULL),(4,'Calendar Event','2025-06-08','11:45:00','2025-06-08','12:45:00','2025-06-08 11:45:00','2025-06-08 12:45:00','2025-06-08 12:45:00',1,0,'No','None',NULL,NULL,60,NULL,'2025-06-08 11:35:03','2025-06-08 16:35:03','calendar_migration',NULL),(5,'Calendar Event','2025-06-11','11:00:00','2025-06-11','12:00:00','2025-06-11 11:00:00','2025-06-11 12:00:00','2025-06-11 12:00:00',1,0,'No','None',NULL,NULL,60,NULL,'2025-06-08 12:51:10','2025-06-08 17:51:40','calendar_migration',NULL),(6,'Calendar Event','2025-06-12','11:00:00','2025-06-12','12:00:00','2025-06-12 11:00:00','2025-06-12 12:00:00','2025-06-12 12:00:00',1,0,'No','None',NULL,NULL,60,NULL,'2025-06-08 12:53:51','2025-06-08 17:53:51','calendar_migration',NULL),(7,'Calendar Event','2025-06-13','11:00:00','2025-06-13','12:15:00','2025-06-13 11:00:00','2025-06-13 12:15:00','2025-06-13 12:15:00',1,0,'No','None',NULL,NULL,75,NULL,'2025-06-08 12:54:04','2025-06-08 17:56:21','calendar_migration',NULL),(8,'Calendar Event','2025-06-15','10:00:00','2025-06-15','11:00:00','2025-06-15 10:00:00','2025-06-15 11:00:00','2025-06-15 11:00:00',1,0,'No','None',NULL,NULL,60,NULL,'2025-06-08 14:46:54','2025-06-08 19:46:54','calendar_migration',NULL),(9,'Calendar Event','2025-06-16','14:00:00','2025-06-16','15:30:00','2025-06-16 14:00:00','2025-06-16 15:30:00','2025-06-16 15:30:00',1,0,'No','None',NULL,NULL,90,NULL,'2025-06-08 14:46:54','2025-06-08 19:46:54','calendar_migration',NULL),(10,'Calendar Event','2025-06-17','09:00:00','2025-06-17','10:00:00','2025-06-17 09:00:00','2025-06-17 10:00:00','2025-06-17 10:00:00',1,0,'No','None',NULL,NULL,60,NULL,'2025-06-08 14:46:54','2025-06-08 19:46:54','calendar_migration',NULL),(11,'Calendar Event','2025-06-18','13:00:00','2025-06-18','16:00:00','2025-06-18 13:00:00','2025-06-18 16:00:00','2025-06-18 16:00:00',1,0,'No','None',NULL,NULL,180,NULL,'2025-06-08 14:46:54','2025-06-08 19:46:54','calendar_migration',NULL),(12,'Calendar Event','2025-06-25','10:00:00','2025-06-25','11:00:00','2025-06-25 10:00:00','2025-06-25 11:00:00','2025-06-25 11:00:00',1,0,'No','None',NULL,NULL,60,NULL,'2025-06-08 15:28:31','2025-06-08 20:28:31','calendar_migration',NULL),(13,'Calendar Event','2025-06-26','14:00:00','2025-06-26','15:30:00','2025-06-26 14:00:00','2025-06-26 15:30:00','2025-06-26 15:30:00',1,0,'No','None',NULL,NULL,90,NULL,'2025-06-08 15:28:31','2025-06-08 20:28:31','calendar_migration',NULL),(14,'Calendar Event','2025-06-27','09:00:00','2025-06-27','10:00:00','2025-06-27 09:00:00','2025-06-27 10:00:00','2025-06-27 10:00:00',1,0,'No','None',NULL,NULL,60,NULL,'2025-06-08 15:28:31','2025-06-08 20:28:31','calendar_migration',NULL),(15,'Calendar Event','2025-06-28','13:00:00','2025-06-28','16:00:00','2025-06-28 13:00:00','2025-06-28 16:00:00','2025-06-28 16:00:00',1,0,'No','None',NULL,NULL,180,NULL,'2025-06-08 15:28:31','2025-06-08 20:28:31','calendar_migration',NULL);
/*!40000 ALTER TABLE `event` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `event_updates`
--

DROP TABLE IF EXISTS `event_updates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `event_updates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `event_type` varchar(50) NOT NULL,
  `event_data` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `event_updates`
--

LOCK TABLES `event_updates` WRITE;
/*!40000 ALTER TABLE `event_updates` DISABLE KEYS */;
/*!40000 ALTER TABLE `event_updates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `facility`
--

DROP TABLE IF EXISTS `facility`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `facility` (
  `facility_ID` int(11) NOT NULL AUTO_INCREMENT,
  `facility_Name` varchar(100) DEFAULT NULL,
  `facility_Address_1` varchar(100) DEFAULT NULL,
  `facility_Address_2` varchar(100) DEFAULT NULL,
  `facility_City` varchar(50) DEFAULT NULL,
  `facility_State` varchar(50) DEFAULT NULL,
  `facility_Postal_Code` varchar(20) DEFAULT NULL,
  `facility_Country` varchar(50) DEFAULT NULL,
  `facility_Phone_No` varchar(30) DEFAULT NULL,
  `facility_Time_Zone` varchar(100) DEFAULT NULL,
  `facility_Latitude` double DEFAULT NULL,
  `facility_Longitude` double DEFAULT NULL,
  `facility_Daily_Start_Time` time DEFAULT '06:00:00',
  `facility_Daily_End_Time` time DEFAULT '23:00:00',
  `facility_Default_Duration` int(11) DEFAULT 60,
  `facility_Default_Maint_Int` int(11) DEFAULT 15,
  `facility_Tax_Rate` decimal(5,4) DEFAULT 0.0875,
  `facility_Status` enum('Active','Inactive') DEFAULT 'Active',
  `create_ts` datetime NOT NULL DEFAULT current_timestamp(),
  `update_ts` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` varchar(30) DEFAULT NULL,
  `updated_by` varchar(30) DEFAULT NULL,
  PRIMARY KEY (`facility_ID`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `facility`
--

LOCK TABLES `facility` WRITE;
/*!40000 ALTER TABLE `facility` DISABLE KEYS */;
INSERT INTO `facility` VALUES (1,'Burnsville Ice Center','251 Civic Center Pkwy',NULL,'Burnsville','MN','55337','USA','(952) 895-4640','America/Chicago',NULL,NULL,'06:00:00','23:00:00',60,15,0.0875,'Active','2025-06-08 20:10:53','2025-06-09 01:10:53','system_init',NULL),(2,'Mariucci Arena','1901 4th St SE',NULL,'Minneapolis','MN','55455','USA','(612) 625-6800','America/Chicago',NULL,NULL,'06:00:00','23:00:00',60,15,0.0875,'Active','2025-06-08 20:10:53','2025-06-09 01:10:53','system_init',NULL),(3,'Braemar Arena','7501 Ikola Way',NULL,'Edina','MN','55439','USA','(952) 941-1322','America/Chicago',NULL,NULL,'06:00:00','23:00:00',60,15,0.0875,'Active','2025-06-08 20:10:53','2025-06-09 01:10:53','system_init',NULL);
/*!40000 ALTER TABLE `facility` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `program`
--

DROP TABLE IF EXISTS `program`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `program` (
  `program_ID` int(11) NOT NULL AUTO_INCREMENT,
  `facility_ID` int(11) NOT NULL DEFAULT 1,
  `program_Type_ID` int(11) DEFAULT NULL,
  `program_Name` varchar(100) DEFAULT NULL,
  `program_Desc` mediumtext DEFAULT NULL,
  `program_Color` varchar(20) DEFAULT '#3498db',
  `program_Contact_Name` varchar(100) DEFAULT NULL,
  `program_Contact_Email` varchar(100) DEFAULT NULL,
  `program_Contact_Phone` varchar(30) DEFAULT NULL,
  `program_Status` enum('Active','Inactive') DEFAULT 'Active',
  `create_ts` datetime NOT NULL DEFAULT current_timestamp(),
  `update_ts` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` varchar(30) DEFAULT NULL,
  `updated_by` varchar(30) DEFAULT NULL,
  PRIMARY KEY (`program_ID`),
  KEY `facility_ID_idx` (`facility_ID`),
  KEY `program_Type_ID_idx` (`program_Type_ID`),
  CONSTRAINT `program_facility_fk` FOREIGN KEY (`facility_ID`) REFERENCES `facility` (`facility_ID`) ON DELETE CASCADE ON UPDATE NO ACTION,
  CONSTRAINT `program_type_fk` FOREIGN KEY (`program_Type_ID`) REFERENCES `program_type` (`program_Type_ID`) ON DELETE SET NULL ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `program`
--

LOCK TABLES `program` WRITE;
/*!40000 ALTER TABLE `program` DISABLE KEYS */;
INSERT INTO `program` VALUES (1,1,1,'Burnsville Youth Hockey','Youth hockey program serving Burnsville and surrounding communities','#ff6b35','Mike Johnson','mike@burnsvillehockey.org','(952) 555-0101','Active','2025-06-08 20:10:53','2025-06-09 01:10:53','system_init',NULL),(2,1,3,'Twin Cities Figure Skating Club','Competitive and recreational figure skating programs','#8e44ad','Sarah Williams','sarah@tcfsc.org','(952) 555-0102','Active','2025-06-08 20:10:53','2025-06-09 01:10:53','system_init',NULL),(3,2,1,'University of Minnesota Hockey','University hockey program and camps','#7d2d2d','Coach Anderson','anderson@umn.edu','(612) 555-0201','Active','2025-06-08 20:10:53','2025-06-09 01:10:53','system_init',NULL),(4,3,2,'Edina Adult Hockey League','Adult recreational hockey league','#2ecc71','Tom Peterson','tom@edinahockey.com','(952) 555-0301','Active','2025-06-08 20:10:53','2025-06-09 01:10:53','system_init',NULL),(5,1,4,'Learn to Skate USA','Beginning skating lessons for all ages','#f39c12','Lisa Chen','lisa@learntoskate.org','(952) 555-0103','Active','2025-06-08 20:10:53','2025-06-09 01:10:53','system_init',NULL),(6,3,3,'Braemar Figure Skating Club','Premier figure skating training facility','#9b59b6','Jennifer Smith','jennifer@braemarfsc.org','(952) 555-0302','Active','2025-06-08 20:10:53','2025-06-09 01:10:53','system_init',NULL);
/*!40000 ALTER TABLE `program` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `program_type`
--

DROP TABLE IF EXISTS `program_type`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `program_type` (
  `program_Type_ID` int(11) NOT NULL AUTO_INCREMENT,
  `program_Type_Name` varchar(100) DEFAULT NULL,
  `program_Type_Desc` mediumtext DEFAULT NULL,
  `create_ts` datetime NOT NULL DEFAULT current_timestamp(),
  `update_ts` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` varchar(30) DEFAULT NULL,
  `updated_by` varchar(30) DEFAULT NULL,
  PRIMARY KEY (`program_Type_ID`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `program_type`
--

LOCK TABLES `program_type` WRITE;
/*!40000 ALTER TABLE `program_type` DISABLE KEYS */;
INSERT INTO `program_type` VALUES (1,'Youth Hockey','Youth hockey associations and teams for players under 18','2025-06-08 20:10:53','2025-06-09 01:10:53','system_init',NULL),(2,'Adult Hockey','Adult recreational and competitive hockey leagues','2025-06-08 20:10:53','2025-06-09 01:10:53','system_init',NULL),(3,'Figure Skating','Figure skating clubs and programs','2025-06-08 20:10:53','2025-06-09 01:10:53','system_init',NULL),(4,'Learn to Skate','Beginner skating programs for all ages','2025-06-08 20:10:53','2025-06-09 01:10:53','system_init',NULL),(5,'Speed Skating','Competitive speed skating programs','2025-06-08 20:10:53','2025-06-09 01:10:53','system_init',NULL),(6,'Curling','Curling clubs and leagues','2025-06-08 20:10:53','2025-06-09 01:10:53','system_init',NULL);
/*!40000 ALTER TABLE `program_type` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `resource`
--

DROP TABLE IF EXISTS `resource`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `resource` (
  `resource_ID` int(11) NOT NULL AUTO_INCREMENT,
  `facility_ID` int(11) DEFAULT NULL,
  `resource_Name` varchar(100) DEFAULT NULL,
  `resource_Type_ID` int(11) DEFAULT NULL,
  `resource_Status` enum('Inactive','Active') DEFAULT 'Active',
  `resource_Desc` varchar(255) DEFAULT NULL,
  `create_ts` datetime NOT NULL DEFAULT current_timestamp(),
  `update_ts` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` varchar(30) DEFAULT NULL,
  `updated_by` varchar(30) DEFAULT NULL,
  PRIMARY KEY (`resource_ID`),
  KEY `facility_ID_idx` (`facility_ID`),
  KEY `resource_Type_ID_idx` (`resource_Type_ID`),
  CONSTRAINT `resource_facility_fk` FOREIGN KEY (`facility_ID`) REFERENCES `facility` (`facility_ID`) ON DELETE CASCADE ON UPDATE NO ACTION,
  CONSTRAINT `resource_type_fk` FOREIGN KEY (`resource_Type_ID`) REFERENCES `resource_type` (`resource_Type_ID`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `resource`
--

LOCK TABLES `resource` WRITE;
/*!40000 ALTER TABLE `resource` DISABLE KEYS */;
INSERT INTO `resource` VALUES (1,1,'Rink A',1,'Active','Main NHL-size rink at Burnsville Ice Center','2025-06-08 20:10:53','2025-06-09 01:10:53','system_init',NULL),(2,1,'Rink B',1,'Active','Secondary NHL-size rink at Burnsville Ice Center','2025-06-08 20:10:53','2025-06-09 01:10:53','system_init',NULL),(3,2,'Mariucci Main',3,'Active','Main arena rink at Mariucci Arena','2025-06-08 20:10:53','2025-06-09 01:10:53','system_init',NULL),(4,3,'Braemar East',1,'Active','East rink at Braemar Arena','2025-06-08 20:10:53','2025-06-09 01:10:53','system_init',NULL),(5,3,'Braemar West',1,'Active','West rink at Braemar Arena','2025-06-08 20:10:53','2025-06-09 01:10:53','system_init',NULL),(6,3,'Braemar Studio',5,'Active','Studio rink for figure skating','2025-06-08 20:10:53','2025-06-09 01:10:53','system_init',NULL);
/*!40000 ALTER TABLE `resource` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `resource_type`
--

DROP TABLE IF EXISTS `resource_type`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `resource_type` (
  `resource_Type_ID` int(11) NOT NULL AUTO_INCREMENT,
  `facility_ID` int(11) DEFAULT NULL,
  `resource_Type_Name` varchar(50) DEFAULT NULL,
  `resource_Type_Desc` varchar(255) DEFAULT NULL,
  `create_ts` datetime NOT NULL DEFAULT current_timestamp(),
  `update_ts` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` varchar(30) DEFAULT NULL,
  `updated_by` varchar(30) DEFAULT NULL,
  PRIMARY KEY (`resource_Type_ID`),
  KEY `facility_ID_idx` (`facility_ID`),
  CONSTRAINT `resource_type_facility_fk` FOREIGN KEY (`facility_ID`) REFERENCES `facility` (`facility_ID`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `resource_type`
--

LOCK TABLES `resource_type` WRITE;
/*!40000 ALTER TABLE `resource_type` DISABLE KEYS */;
INSERT INTO `resource_type` VALUES (1,1,'NHL Rink','Standard NHL size ice surface - 200ft x 85ft','2025-06-08 20:10:53','2025-06-09 01:10:53','system_init',NULL),(2,1,'Olympic Rink','International/Olympic size ice surface - 200ft x 100ft','2025-06-08 20:10:53','2025-06-09 01:10:53','system_init',NULL),(3,2,'Arena Rink','Main arena ice surface for games and events','2025-06-08 20:10:53','2025-06-09 01:10:53','system_init',NULL),(4,3,'Practice Rink','Smaller practice ice surface','2025-06-08 20:10:53','2025-06-09 01:10:53','system_init',NULL),(5,3,'Studio Rink','Small studio rink for lessons and figure skating','2025-06-08 20:10:53','2025-06-09 01:10:53','system_init',NULL);
/*!40000 ALTER TABLE `resource_type` ENABLE KEYS */;
UNLOCK TABLES;

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
) ENGINE=InnoDB AUTO_INCREMENT=1002 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `role`
--

LOCK TABLES `role` WRITE;
/*!40000 ALTER TABLE `role` DISABLE KEYS */;
INSERT INTO `role` VALUES (1001,'Calendar User','Imported from calendar-app',10,'calendar','2025-06-08 19:54:24','2025-06-09 00:54:24','migration',NULL);
/*!40000 ALTER TABLE `role` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `session`
--

DROP TABLE IF EXISTS `session`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `session` (
  `session_ID` int(11) NOT NULL AUTO_INCREMENT,
  `php_Session_ID` varchar(128) DEFAULT NULL,
  `user_ID` int(11) DEFAULT NULL,
  `session_No_Pages` int(11) DEFAULT NULL,
  `session_End_Reason` varchar(20) DEFAULT NULL,
  `create_ts` datetime NOT NULL DEFAULT current_timestamp(),
  `update_ts` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` varchar(30) DEFAULT NULL,
  `updated_by` varchar(30) DEFAULT NULL,
  `session_HTTP_X_FORWARDED_FOR` varchar(100) DEFAULT NULL,
  `session_REMOTE_ADDR` varchar(100) DEFAULT NULL,
  `session_HTTP_CLIENT_IP` varchar(100) DEFAULT NULL,
  `session_Latitude` double DEFAULT NULL,
  `session_Longitude` double DEFAULT NULL,
  `session_Location` varchar(255) DEFAULT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `remember_me` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`session_ID`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `session`
--

LOCK TABLES `session` WRITE;
/*!40000 ALTER TABLE `session` DISABLE KEYS */;
INSERT INTO `session` VALUES (1,'31e1bee302fa181e2996aa6ef45f1266647117380e6901e66cc8ef934e1a9b05',4,NULL,NULL,'2025-06-08 12:53:22','2025-06-09 00:54:24','calendar_migration',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-06-10 00:53:22','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 Edg/137.0.0.0',0,1),(2,'7b486bec587f72cfaf8dfbdadc59116b204746846cc0285de65b911ca2a23f92',3,NULL,NULL,'2025-06-08 15:01:40','2025-06-09 00:54:24','calendar_migration',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-06-10 03:01:40','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36',0,1),(3,'d9f1c12f41ed452731d3d00ea34003d1e35c00bd5c8b90f4786756a523e7b148',4,NULL,NULL,'2025-06-08 18:55:38','2025-06-09 00:54:24','calendar_migration',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-06-10 06:55:38','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 Edg/137.0.0.0',0,1),(4,'fb77572116985e109c1fe8c241ea251d87ad6362d12d9ef017c2f0c0495b3bea',3,NULL,NULL,'2025-06-08 11:59:23','2025-06-09 00:54:24','calendar_migration',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-07-08 23:59:23','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36',1,1);
/*!40000 ALTER TABLE `session` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `team`
--

DROP TABLE IF EXISTS `team`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `team` (
  `team_ID` int(11) NOT NULL AUTO_INCREMENT,
  `program_ID` int(11) DEFAULT NULL,
  `team_Name` varchar(100) DEFAULT NULL,
  `team_Color` varchar(30) DEFAULT NULL,
  `team_Age_Group` varchar(50) DEFAULT NULL,
  `team_Skill_Level` varchar(50) DEFAULT NULL,
  `team_Contact_Name` varchar(100) DEFAULT NULL,
  `team_Contact_Email` varchar(100) DEFAULT NULL,
  `team_Contact_Phone` varchar(30) DEFAULT NULL,
  `create_ts` datetime NOT NULL DEFAULT current_timestamp(),
  `update_ts` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` varchar(30) DEFAULT NULL,
  `updated_by` varchar(30) DEFAULT NULL,
  PRIMARY KEY (`team_ID`),
  KEY `program_ID_idx` (`program_ID`),
  CONSTRAINT `team_program_fk` FOREIGN KEY (`program_ID`) REFERENCES `program` (`program_ID`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `team`
--

LOCK TABLES `team` WRITE;
/*!40000 ALTER TABLE `team` DISABLE KEYS */;
INSERT INTO `team` VALUES (1,1,'Mite Red','#ff0000','8U','Beginner','Dave Miller','dave.miller@email.com','(952) 555-1001','2025-06-08 20:10:53','2025-06-09 01:10:53','system_init',NULL),(2,1,'Mite Blue','#0000ff','8U','Intermediate','Karen Davis','karen.davis@email.com','(952) 555-1002','2025-06-08 20:10:53','2025-06-09 01:10:53','system_init',NULL),(3,1,'Squirt A','#00ff00','10U','Advanced','Bob Wilson','bob.wilson@email.com','(952) 555-1003','2025-06-08 20:10:53','2025-06-09 01:10:53','system_init',NULL),(4,1,'Squirt B','#ffff00','10U','Intermediate','Nancy Brown','nancy.brown@email.com','(952) 555-1004','2025-06-08 20:10:53','2025-06-09 01:10:53','system_init',NULL),(5,1,'Peewee AA','#ff6600','12U','Elite','Steve Taylor','steve.taylor@email.com','(952) 555-1005','2025-06-08 20:10:53','2025-06-09 01:10:53','system_init',NULL),(6,2,'Beginner Group','#cc99ff','All Ages','Beginner','Mary Johnson','mary.johnson@email.com','(952) 555-2001','2025-06-08 20:10:53','2025-06-09 01:10:53','system_init',NULL),(7,2,'Intermediate Group','#9966cc','All Ages','Intermediate','Rachel Green','rachel.green@email.com','(952) 555-2002','2025-06-08 20:10:53','2025-06-09 01:10:53','system_init',NULL),(8,2,'Advanced Group','#663399','All Ages','Advanced','Emma Wilson','emma.wilson@email.com','(952) 555-2003','2025-06-08 20:10:53','2025-06-09 01:10:53','system_init',NULL),(9,3,'Gophers Varsity','#8b1538','College','Elite','Coach Bob Motzko','motzko@umn.edu','(612) 555-3001','2025-06-08 20:10:53','2025-06-09 01:10:53','system_init',NULL),(10,3,'Summer Camp A','#ffcc33','Youth','All Levels','Assistant Coach','camp@umn.edu','(612) 555-3002','2025-06-08 20:10:53','2025-06-09 01:10:53','system_init',NULL),(11,4,'Division A Gold','#ffd700','Adult','Advanced','Jim Anderson','jim.anderson@email.com','(952) 555-4001','2025-06-08 20:10:53','2025-06-09 01:10:53','system_init',NULL),(12,4,'Division B Silver','#c0c0c0','Adult','Intermediate','Mark Thompson','mark.thompson@email.com','(952) 555-4002','2025-06-08 20:10:53','2025-06-09 01:10:53','system_init',NULL),(13,4,'Division C Bronze','#cd7f32','Adult','Beginner','Paul Martinez','paul.martinez@email.com','(952) 555-4003','2025-06-08 20:10:53','2025-06-09 01:10:53','system_init',NULL),(14,5,'Snowplow Sam','#87ceeb','3-5 years','Beginner','Amy Roberts','amy.roberts@email.com','(952) 555-5001','2025-06-08 20:10:53','2025-06-09 01:10:53','system_init',NULL),(15,5,'Basic 1-3','#98fb98','6-12 years','Beginner','Chris Lee','chris.lee@email.com','(952) 555-5002','2025-06-08 20:10:53','2025-06-09 01:10:53','system_init',NULL),(16,5,'Pre-Freestyle','#dda0dd','10+ years','Intermediate','Michelle Garcia','michelle.garcia@email.com','(952) 555-5003','2025-06-08 20:10:53','2025-06-09 01:10:53','system_init',NULL),(17,6,'Competitive Team','#4b0082','All Ages','Elite','Coach Alexandra','alexandra@braemarfsc.org','(952) 555-6001','2025-06-08 20:10:53','2025-06-09 01:10:53','system_init',NULL),(18,6,'Recreational Skaters','#da70d6','All Ages','Recreational','Assistant Coach','recreation@braemarfsc.org','(952) 555-6002','2025-06-08 20:10:53','2025-06-09 01:10:53','system_init',NULL),(19,6,'Adult Skaters','#ba55d3','Adult','All Levels','Sarah Miller','sarah.miller@email.com','(952) 555-6003','2025-06-08 20:10:53','2025-06-09 01:10:53','system_init',NULL);
/*!40000 ALTER TABLE `team` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user`
--

DROP TABLE IF EXISTS `user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user` (
  `user_ID` int(11) NOT NULL AUTO_INCREMENT,
  `role_ID` int(11) DEFAULT NULL,
  `user_Name` varchar(100) NOT NULL,
  `user_Email` varchar(100) NOT NULL,
  `password` char(128) NOT NULL,
  `reg_Type` int(11) DEFAULT NULL,
  `salt` int(11) DEFAULT NULL,
  `auth_Key` varchar(255) DEFAULT NULL,
  `auth_ts` timestamp NULL DEFAULT NULL COMMENT 'Used to time registration validation and password resets',
  `validated` tinyint(4) DEFAULT NULL,
  `validated_ts` timestamp NULL DEFAULT NULL,
  `remember_Me` varchar(255) DEFAULT NULL,
  `user_Last_Login` datetime DEFAULT NULL,
  `timezone` varchar(30) DEFAULT NULL,
  `language` varchar(30) DEFAULT NULL,
  `user_Status` enum('Active','Inactive') DEFAULT 'Active',
  `created_by` varchar(30) DEFAULT NULL,
  `updated_by` varchar(30) DEFAULT NULL,
  `create_ts` datetime NOT NULL DEFAULT current_timestamp(),
  `update_ts` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`user_ID`),
  UNIQUE KEY `username` (`user_Name`) USING BTREE,
  UNIQUE KEY `user_Email` (`user_Email`) USING BTREE,
  KEY `user_ID` (`user_ID`) USING BTREE,
  KEY `role_ibfk1` (`role_ID`) USING BTREE,
  CONSTRAINT `user_ibfk_1` FOREIGN KEY (`role_ID`) REFERENCES `role` (`role_ID`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user`
--

LOCK TABLES `user` WRITE;
/*!40000 ALTER TABLE `user` DISABLE KEYS */;
INSERT INTO `user` VALUES (1,1001,'Burnsville Ice Arena','user8@example.com','',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active','calendar_migration',NULL,'2025-06-08 00:10:18','2025-06-09 00:54:24'),(2,1001,'OS Hockey','user10@example.com','',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Active','calendar_migration',NULL,'2025-06-08 00:10:24','2025-06-09 00:54:24'),(3,1001,'Michael Schroeder','michael.schroeder123@gmail.com','$2y$10$MP45kfUSfpxbmhzEyMpFiuaD/Be4lkki4NSv0YY/T/xKkbmn4gDCe',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-06-08 15:01:40',NULL,NULL,'Active','calendar_migration',NULL,'2025-06-08 11:59:16','2025-06-09 00:54:24'),(4,1001,'Celine Schroeder','michael.schroeder@collageart.com','$2y$10$6bePxhkr0IrzG1HTzCoJXORVdajKGx6gVncefrbbUoMKdeeZb4KQu',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-06-08 18:55:38',NULL,NULL,'Active','calendar_migration',NULL,'2025-06-08 12:53:17','2025-06-09 00:54:24');
/*!40000 ALTER TABLE `user` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-06-08 20:13:27
