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
-- Table structure for table `calendar_updates`
--

DROP TABLE IF EXISTS `calendar_updates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `calendar_updates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `event_type` varchar(50) NOT NULL,
  `event_data` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `calendar_updates`
--

LOCK TABLES `calendar_updates` WRITE;
/*!40000 ALTER TABLE `calendar_updates` DISABLE KEYS */;
/*!40000 ALTER TABLE `calendar_updates` ENABLE KEYS */;
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
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-06-08 21:43:06
