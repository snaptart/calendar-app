CREATE DATABASE  IF NOT EXISTS `itfmvp` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci */;
USE `itfmvp`;
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
-- Table structure for table `activities`
--

DROP TABLE IF EXISTS `activities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `activities` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `activity__name` varchar(191) DEFAULT NULL,
  `activity_desc` varchar(191) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `activity`
--

DROP TABLE IF EXISTS `activity`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `activity` (
  `activity_ID` int(11) NOT NULL AUTO_INCREMENT,
  `activity_Name` varchar(100) DEFAULT NULL COMMENT 'Assigns multiple teams to one episode',
  `activity_Desc` varchar(255) DEFAULT NULL,
  `create_ts` datetime NOT NULL DEFAULT current_timestamp(),
  `update_ts` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` varchar(30) DEFAULT NULL,
  `updated_by` varchar(30) DEFAULT NULL,
  `activity__name` varchar(191) DEFAULT NULL,
  PRIMARY KEY (`activity_ID`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `contact`
--

DROP TABLE IF EXISTS `contact`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `contact` (
  `contact_ID` int(11) NOT NULL AUTO_INCREMENT,
  `user_ID` int(11) DEFAULT NULL,
  `contact_Title` varchar(30) DEFAULT NULL,
  `contact_First_Name` varchar(50) DEFAULT NULL,
  `contact_Last_Name` varchar(50) DEFAULT NULL,
  `contact_Address_1` varchar(100) DEFAULT NULL,
  `contact_Address_2` varchar(100) DEFAULT NULL,
  `contact_City` varchar(50) DEFAULT NULL,
  `contact_State_ID` int(11) DEFAULT NULL,
  `contact_Postal_Code` varchar(20) DEFAULT NULL,
  `contact_Country_ID` int(11) DEFAULT NULL,
  `contact_Area_Code` varchar(30) DEFAULT NULL,
  `contact_Phone_No_Prefix` varchar(30) DEFAULT NULL,
  `contact_Phone_No_Suffix` varchar(30) DEFAULT NULL,
  `contact_Area_Code_Alt` varchar(30) DEFAULT NULL,
  `contact_Phone_No_Prefix_Alt` varchar(30) DEFAULT NULL,
  `contact_Phone_No_Suffix_Alt` varchar(30) DEFAULT NULL,
  `contact_Email` varchar(100) DEFAULT NULL,
  `contact_Email_Alt` varchar(100) DEFAULT NULL,
  `mobile_Provider_ID` int(11) DEFAULT NULL,
  `create_ts` datetime NOT NULL DEFAULT current_timestamp(),
  `update_ts` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` varchar(30) DEFAULT NULL,
  `updated_by` varchar(30) DEFAULT NULL,
  PRIMARY KEY (`contact_ID`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `dim_country`
--

DROP TABLE IF EXISTS `dim_country`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `dim_country` (
  `country_ID` int(11) NOT NULL AUTO_INCREMENT,
  `country_Name` varchar(100) NOT NULL,
  `active` enum('Yes','No') NOT NULL DEFAULT 'Yes',
  `isoA2` char(2) NOT NULL,
  `isoA3` char(3) NOT NULL,
  `isoNumber` varchar(4) NOT NULL,
  PRIMARY KEY (`country_ID`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `dim_facility`
--

DROP TABLE IF EXISTS `dim_facility`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `dim_facility` (
  `dim_Facility_ID` int(11) NOT NULL AUTO_INCREMENT,
  `facility_ID` int(11) DEFAULT NULL,
  `facility_Name` varchar(100) DEFAULT NULL,
  `facility_Address_1` varchar(100) DEFAULT NULL,
  `facility_Address_2` varchar(100) DEFAULT NULL,
  `facility_City` varchar(50) DEFAULT NULL,
  `facility_State` varchar(50) DEFAULT NULL,
  `facility_Postal_Code` varchar(20) DEFAULT NULL,
  `facility_Country` varchar(50) DEFAULT NULL,
  `facility_Phone_No` varchar(30) DEFAULT NULL,
  `facility_Type` varchar(20) DEFAULT NULL,
  `facility_Default_Maint_Int` int(11) DEFAULT NULL,
  PRIMARY KEY (`dim_Facility_ID`)
) ENGINE=InnoDB AUTO_INCREMENT=533 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `dim_mobile_providers`
--

DROP TABLE IF EXISTS `dim_mobile_providers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `dim_mobile_providers` (
  `mobile_Provider_ID` int(11) NOT NULL AUTO_INCREMENT,
  `mobile_Provider` varchar(30) DEFAULT NULL,
  `mobile_Domain` varchar(30) DEFAULT NULL,
  `create_ts` datetime NOT NULL DEFAULT current_timestamp(),
  `update_ts` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` varchar(30) DEFAULT NULL,
  `updated_by` varchar(30) DEFAULT NULL,
  PRIMARY KEY (`mobile_Provider_ID`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `dim_postal_code`
--

DROP TABLE IF EXISTS `dim_postal_code`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `dim_postal_code` (
  `dim_Postal_Code_ID` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `postal_Code` varchar(20) NOT NULL,
  `postal_Code_City` varchar(50) DEFAULT NULL,
  `postal_Code_County` varchar(50) DEFAULT NULL,
  `postal_Code_State_Name` varchar(50) DEFAULT NULL,
  `postal_Code_State_Prefix` varchar(2) DEFAULT NULL,
  `postal_Code_Area_Code` varchar(3) DEFAULT NULL,
  `postal_Code_Timezone` varchar(50) DEFAULT NULL,
  `postal_Code_Latitude` double NOT NULL,
  `postal_Code_Longitude` double NOT NULL,
  PRIMARY KEY (`dim_Postal_Code_ID`),
  KEY `zip_code` (`postal_Code`)
) ENGINE=InnoDB AUTO_INCREMENT=53266 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `dim_program`
--

DROP TABLE IF EXISTS `dim_program`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `dim_program` (
  `dim_Program_ID` int(11) NOT NULL AUTO_INCREMENT,
  `program_ID` int(11) DEFAULT NULL,
  `program_Name` varchar(100) DEFAULT NULL,
  `program_Address_1` varchar(100) DEFAULT NULL,
  `program_Address_2` varchar(100) DEFAULT NULL,
  `program_City` varchar(50) DEFAULT NULL,
  `program_State` varchar(50) DEFAULT NULL,
  `program_Postal_Code` varchar(20) DEFAULT NULL,
  `program_Country` varchar(50) DEFAULT NULL,
  `program_Type` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`dim_Program_ID`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `dim_resource`
--

DROP TABLE IF EXISTS `dim_resource`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `dim_resource` (
  `dim_Resource_ID` int(11) NOT NULL AUTO_INCREMENT,
  `resource_Name` varchar(255) DEFAULT NULL,
  `resource_ID` int(11) DEFAULT NULL,
  `dim_Facility_ID` int(11) DEFAULT NULL,
  `facility_ID` int(11) DEFAULT NULL,
  `resource_Type` varchar(60) DEFAULT NULL,
  `resource_Size` varchar(60) DEFAULT NULL,
  `resource_Details` varchar(255) DEFAULT NULL,
  `resource_Floor_Type` varchar(60) DEFAULT NULL,
  PRIMARY KEY (`dim_Resource_ID`)
) ENGINE=InnoDB AUTO_INCREMENT=515 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `dim_state`
--

DROP TABLE IF EXISTS `dim_state`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `dim_state` (
  `state_ID` int(11) NOT NULL AUTO_INCREMENT,
  `country_ID` int(11) NOT NULL DEFAULT 0,
  `state_Name` varchar(255) NOT NULL,
  `state_Abbreviation` varchar(100) NOT NULL,
  PRIMARY KEY (`state_ID`),
  KEY `countryid` (`country_ID`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=65 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `episode`
--

DROP TABLE IF EXISTS `episode`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `episode` (
  `episode_ID` int(11) NOT NULL AUTO_INCREMENT,
  `event_ID` int(11) NOT NULL,
  `resource_ID` int(11) NOT NULL,
  `schedule_ID` int(11) DEFAULT NULL,
  `team_ID` int(11) NOT NULL DEFAULT 0,
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
  PRIMARY KEY (`episode_ID`),
  KEY `event_ibfk1` (`event_ID`) USING BTREE,
  KEY `schedule_ibfk1` (`schedule_ID`) USING BTREE,
  KEY `team_ibfk1` (`team_ID`) USING BTREE,
  KEY `activity_ibfk1` (`activity_ID`) USING BTREE,
  KEY `validation_Status_ibfk1` (`validation_Status_ID`) USING BTREE,
  KEY `episode_Start_Date_Time_ih1` (`episode_Start_Date_Time`) USING BTREE,
  KEY `episode_End_Date_Time_ih1` (`episode_End_Date_Time`) USING BTREE,
  KEY `episode_Public_Flag_ih1` (`episode_Public_Flag`) USING HASH,
  CONSTRAINT `event_ibfk1` FOREIGN KEY (`event_ID`) REFERENCES `event` (`event_ID`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=2074 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `episode_authorization`
--

DROP TABLE IF EXISTS `episode_authorization`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `episode_authorization` (
  `episode_Authorization_ID` int(11) NOT NULL AUTO_INCREMENT,
  `user_ID` int(11) DEFAULT NULL,
  `group_ID` int(11) DEFAULT NULL,
  `facility_ID` int(11) DEFAULT NULL,
  `program_ID` int(11) DEFAULT NULL,
  `program_Level_ID` int(11) DEFAULT NULL,
  `team_ID` int(11) DEFAULT NULL,
  `event_ID` int(11) DEFAULT NULL,
  `episode_ID` int(11) DEFAULT NULL,
  `episode_Edit_Auth_ID` int(11) NOT NULL DEFAULT 1 COMMENT 'This denotes functionality: Full,Edit,Label,View,None',
  `create_ts` datetime NOT NULL DEFAULT current_timestamp(),
  `update_ts` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` varchar(30) DEFAULT NULL,
  `updated_by` varchar(30) DEFAULT NULL,
  PRIMARY KEY (`episode_Authorization_ID`),
  KEY `index_ea_User_ID` (`user_ID`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `episode_edit_authorization`
--

DROP TABLE IF EXISTS `episode_edit_authorization`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `episode_edit_authorization` (
  `episode_Edit_Auth_ID` int(11) NOT NULL AUTO_INCREMENT,
  `episode_Edit_Auth_Name` varchar(30) DEFAULT NULL,
  `episode_Edit_Auth_Desc` varchar(255) DEFAULT NULL,
  `create_ts` datetime NOT NULL,
  `update_ts` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` varchar(30) DEFAULT NULL,
  `updated_by` varchar(30) DEFAULT NULL,
  PRIMARY KEY (`episode_Edit_Auth_ID`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `episode_editable`
--

DROP TABLE IF EXISTS `episode_editable`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `episode_editable` (
  `episode_Editable_Key_ID` int(11) NOT NULL AUTO_INCREMENT,
  `rendering` varchar(30) DEFAULT 'background',
  `episode_ID` int(11) NOT NULL,
  `event_ID` int(11) NOT NULL,
  `resource_ID` int(11) NOT NULL,
  `schedule_ID` int(11) DEFAULT NULL,
  `team_ID` int(11) NOT NULL DEFAULT 0,
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
  `episode_Public_Flag` enum('Private','Public') DEFAULT 'Public',
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
  `update_ts` timestamp NOT NULL DEFAULT current_timestamp(),
  `create_ts` datetime NOT NULL DEFAULT current_timestamp(),
  `created_by` varchar(30) DEFAULT NULL,
  `updated_by` varchar(30) DEFAULT NULL,
  PRIMARY KEY (`episode_Editable_Key_ID`),
  KEY `event_ibfk1` (`event_ID`) USING BTREE,
  KEY `schedule_ibfk1` (`schedule_ID`) USING BTREE,
  KEY `team_ibfk1` (`team_ID`) USING BTREE,
  KEY `activity_ibfk1` (`activity_ID`) USING BTREE,
  KEY `validation_Status_ibfk1` (`validation_Status_ID`) USING BTREE,
  KEY `episode_Start_Date_Time_ih1` (`episode_Start_Date_Time`) USING BTREE,
  KEY `episode_End_Date_Time_ih1` (`episode_End_Date_Time`) USING BTREE,
  KEY `episode_ibk1` (`episode_ID`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `episode_history`
--

DROP TABLE IF EXISTS `episode_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `episode_history` (
  `history_ts` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `db_action` varchar(30) DEFAULT NULL,
  `sequence_No` int(11) NOT NULL AUTO_INCREMENT,
  `notification_ID` int(11) DEFAULT NULL,
  `episode_ID` int(11) NOT NULL,
  `event_ID` int(11) NOT NULL,
  `resource_ID` int(11) NOT NULL,
  `schedule_ID` int(11) DEFAULT NULL,
  `team_ID` int(11) NOT NULL DEFAULT 0,
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
  `episode_Public_Flag` enum('Private','Public') DEFAULT 'Public',
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
  `update_ts` timestamp NOT NULL DEFAULT current_timestamp(),
  `create_ts` datetime NOT NULL DEFAULT current_timestamp(),
  `created_by` varchar(30) DEFAULT NULL,
  `updated_by` varchar(30) DEFAULT NULL,
  PRIMARY KEY (`episode_ID`,`sequence_No`),
  KEY `event_ibfk1` (`event_ID`) USING BTREE,
  KEY `schedule_ibfk1` (`schedule_ID`) USING BTREE,
  KEY `team_ibfk1` (`team_ID`) USING BTREE,
  KEY `activity_ibfk1` (`activity_ID`) USING BTREE,
  KEY `validation_Status_ibfk1` (`validation_Status_ID`) USING BTREE,
  KEY `episode_Start_Date_Time_ih1` (`episode_Start_Date_Time`) USING BTREE,
  KEY `episode_End_Date_Time_ih1` (`episode_End_Date_Time`) USING BTREE,
  KEY `episode_ibk1` (`episode_ID`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci ROW_FORMAT=DYNAMIC COMMENT='This table is MYISAM so that a composite key can be used.';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `episode_upload_temp`
--

DROP TABLE IF EXISTS `episode_upload_temp`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `episode_upload_temp` (
  `episode_upload_temp_ID` int(11) NOT NULL AUTO_INCREMENT,
  `upload_ID` int(11) DEFAULT NULL,
  `Arena` varchar(255) DEFAULT NULL,
  `Resource` varchar(255) DEFAULT NULL,
  `activity_ID` int(11) DEFAULT NULL,
  `program_ID` int(11) DEFAULT NULL,
  `facility_ID` int(11) DEFAULT NULL,
  `resource_ID` int(11) DEFAULT NULL,
  `activity_Name` varchar(255) DEFAULT NULL,
  `program_Name` varchar(255) DEFAULT NULL,
  `ITF_Arena` varchar(255) DEFAULT NULL,
  `ITF_Resource` varchar(255) DEFAULT NULL,
  `ITF_Date` varchar(255) DEFAULT NULL,
  `ITF_StartTime` varchar(255) DEFAULT NULL,
  `ITF_EndTime` varchar(255) DEFAULT NULL,
  `ITF_Price` decimal(10,2) DEFAULT NULL,
  `ITF_Status` varchar(255) DEFAULT NULL,
  `ITF_Status_Message` varchar(255) DEFAULT NULL,
  `StartDate` varchar(255) DEFAULT NULL,
  `StartTime` varchar(255) DEFAULT NULL,
  `EndTime` varchar(255) DEFAULT NULL,
  `Price` decimal(10,2) DEFAULT NULL,
  `episode_ID` int(11) DEFAULT NULL,
  `update_ts` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `create_ts` datetime NOT NULL DEFAULT current_timestamp(),
  `created_by` varchar(30) DEFAULT NULL,
  `updated_by` varchar(30) DEFAULT NULL,
  PRIMARY KEY (`episode_upload_temp_ID`)
) ENGINE=InnoDB AUTO_INCREMENT=2074 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

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
  `num_Occurrences` int(11) DEFAULT NULL,
  `num_Conflicts` int(11) DEFAULT NULL,
  `all_Day` varchar(11) DEFAULT NULL,
  `repeat_Mode` varchar(10) DEFAULT NULL,
  `repeat_Ends` varchar(30) DEFAULT NULL,
  `event_Dates` mediumtext DEFAULT NULL,
  `episode_Duration` int(11) DEFAULT NULL,
  `maintenance_Interval` int(11) DEFAULT NULL,
  `create_ts` datetime NOT NULL DEFAULT current_timestamp(),
  `update_ts` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` varchar(30) DEFAULT NULL,
  `updated_by` varchar(30) DEFAULT NULL,
  PRIMARY KEY (`event_ID`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `facility`
--

DROP TABLE IF EXISTS `facility`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `facility` (
  `facility_ID` int(11) NOT NULL AUTO_INCREMENT,
  `dim_Facility_ID` int(11) DEFAULT NULL,
  `org_ID` int(11) DEFAULT NULL,
  `location_ID` int(11) DEFAULT NULL,
  `facility_authorized` int(11) NOT NULL DEFAULT 0,
  `facility_Contact_ID` int(11) DEFAULT NULL,
  `facility_Admin_User_ID` int(11) DEFAULT NULL,
  `facility_Name` varchar(100) DEFAULT NULL,
  `facility_Address_1` varchar(100) DEFAULT NULL,
  `facility_Address_2` varchar(100) DEFAULT NULL,
  `facility_City` varchar(50) DEFAULT NULL,
  `facility_State` varchar(50) DEFAULT NULL,
  `facility_State_ID` int(11) DEFAULT NULL,
  `facility_Postal_Code` varchar(20) DEFAULT NULL,
  `facility_Country` varchar(50) DEFAULT NULL,
  `facility_Country_ID` int(11) DEFAULT NULL,
  `facility_Phone_No` varchar(30) DEFAULT NULL,
  `facility_Time_Zone` varchar(100) DEFAULT NULL,
  `facility_Latitude` double DEFAULT NULL,
  `facility_Longitude` double DEFAULT NULL,
  `facility_Daily_Start_Time` time DEFAULT NULL,
  `facility_Daily_End_Time` time DEFAULT NULL,
  `facility_Default_Duration` int(11) DEFAULT NULL,
  `facility_Default_Maint_Int` int(11) DEFAULT NULL,
  `facility_Tax_Rate` decimal(11,0) DEFAULT NULL,
  `facility_Status` enum('Active','Inactive') DEFAULT 'Active',
  `create_ts` datetime NOT NULL DEFAULT current_timestamp(),
  `update_ts` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` varchar(30) DEFAULT NULL,
  `updated_by` varchar(30) DEFAULT NULL,
  PRIMARY KEY (`facility_ID`),
  KEY `org_ID_ibfk1` (`org_ID`) USING BTREE,
  KEY `fac_Admin_ib1` (`facility_Admin_User_ID`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `facility_prefs`
--

DROP TABLE IF EXISTS `facility_prefs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `facility_prefs` (
  `facility_Prefs_ID` int(11) NOT NULL AUTO_INCREMENT,
  `facility_ID` int(11) DEFAULT NULL,
  `facility_Default_Duration` int(11) DEFAULT NULL,
  `facility_Default_Maint_Int` int(11) DEFAULT NULL,
  `facility_Daily_Start_Time` time DEFAULT NULL,
  `facility_Daily_End_Time` time DEFAULT NULL,
  `facility_Tax_Rate` float DEFAULT NULL,
  `facility_Payment_Policy` varchar(255) DEFAULT NULL,
  `facility_Color_1` varchar(30) DEFAULT NULL,
  `facility_Color_2` varchar(30) DEFAULT NULL,
  `create_ts` datetime NOT NULL DEFAULT current_timestamp(),
  `update_ts` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` varchar(30) DEFAULT NULL,
  `updated_by` varchar(30) DEFAULT NULL,
  PRIMARY KEY (`facility_Prefs_ID`),
  KEY `facility_ibfk1` (`facility_ID`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `group`
--

DROP TABLE IF EXISTS `group`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `group` (
  `group_ID` int(11) NOT NULL AUTO_INCREMENT,
  `group_Name` varchar(30) NOT NULL,
  `group_Desc` varchar(255) DEFAULT NULL,
  `group_Entity_ID` int(11) DEFAULT NULL,
  `group_Entity` varchar(30) DEFAULT NULL,
  `group_Entity_Default` tinyint(4) DEFAULT NULL,
  `group_Create_Method` enum('Auto','Manual') DEFAULT 'Auto' COMMENT 'Created by system (Auto) or user (Manual)',
  `create_ts` datetime NOT NULL DEFAULT current_timestamp(),
  `update_ts` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` varchar(30) DEFAULT NULL,
  `updated_by` varchar(30) DEFAULT NULL,
  PRIMARY KEY (`group_ID`),
  KEY `index_group_Entity_ID` (`group_Entity_ID`) USING BTREE,
  KEY `index_group_Entity` (`group_Entity`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=267 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `notification`
--

DROP TABLE IF EXISTS `notification`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notification` (
  `notification_ID` int(11) NOT NULL AUTO_INCREMENT,
  `notification_Type_ID` int(11) DEFAULT NULL,
  `notification_ID_Name` varchar(50) DEFAULT NULL,
  `notification_ID_Values` mediumtext DEFAULT NULL,
  `notification_Text` mediumtext DEFAULT NULL,
  `notification_Sent_Count` int(11) DEFAULT 0,
  `notification_Status` enum('Not Sent','Sent','Failed') DEFAULT 'Not Sent',
  `create_ts` datetime NOT NULL DEFAULT current_timestamp(),
  `update_ts` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` varchar(30) DEFAULT NULL,
  `updated_by` varchar(30) DEFAULT NULL,
  PRIMARY KEY (`notification_ID`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `notification_type`
--

DROP TABLE IF EXISTS `notification_type`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notification_type` (
  `notification_Type_ID` int(11) NOT NULL AUTO_INCREMENT,
  `notification_Type_Name` varchar(100) DEFAULT NULL,
  `notification_Type_Desc` varchar(255) DEFAULT NULL,
  `create_ts` datetime NOT NULL DEFAULT current_timestamp(),
  `update_ts` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` varchar(30) DEFAULT NULL,
  `updated_by` varchar(30) DEFAULT NULL,
  PRIMARY KEY (`notification_Type_ID`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `order`
--

DROP TABLE IF EXISTS `order`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `order` (
  `order_ID` int(11) NOT NULL AUTO_INCREMENT,
  `user_ID` int(11) DEFAULT NULL,
  `session_ID` int(11) DEFAULT NULL,
  `order_Status` enum('New','Abandon','Process','Backorder','Canceled','Completed','Failed') DEFAULT 'New',
  `order_Removed` enum('Yes','No') DEFAULT 'No',
  `order_No_Episodes` decimal(10,0) DEFAULT NULL,
  `subtotal_Amount` decimal(20,2) unsigned DEFAULT 0.00 COMMENT 'The total of the listed ice time prices',
  `tax_Amount` decimal(20,2) DEFAULT NULL COMMENT 'Any taxes charged on the total of the listed ice time prices',
  `commission_Amount` decimal(20,2) DEFAULT NULL COMMENT 'Any commissions charged on the total of the listed ice time prices',
  `transaction_Fee` decimal(20,2) DEFAULT NULL,
  `total_Amount` decimal(20,2) unsigned DEFAULT 0.00,
  `total_Amount_USD` decimal(20,2) DEFAULT NULL,
  `create_ts` datetime NOT NULL DEFAULT current_timestamp(),
  `update_ts` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` varchar(30) DEFAULT NULL,
  `updated_by` varchar(30) DEFAULT NULL,
  PRIMARY KEY (`order_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `order_detail`
--

DROP TABLE IF EXISTS `order_detail`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_detail` (
  `order_Detail_ID` int(11) NOT NULL AUTO_INCREMENT,
  `order_ID` int(11) DEFAULT NULL,
  `episode_ID` int(11) DEFAULT NULL,
  `order_Detail_Tax_Amount` decimal(20,2) DEFAULT NULL,
  `create_ts` datetime NOT NULL DEFAULT current_timestamp(),
  `update_ts` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` varchar(30) DEFAULT NULL,
  `updated_by` varchar(30) DEFAULT NULL,
  PRIMARY KEY (`order_Detail_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `page`
--

DROP TABLE IF EXISTS `page`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `page` (
  `page_ID` int(11) NOT NULL AUTO_INCREMENT,
  `page_Parent_ID` int(11) DEFAULT NULL,
  `page_Min_Role_Level` int(11) DEFAULT NULL,
  `page_Min_Role_Operator` varchar(20) DEFAULT NULL,
  `page_Max_Role_Level` int(11) DEFAULT NULL,
  `page_Max_Role_Operator` varchar(20) DEFAULT NULL,
  `page_Name` varchar(50) DEFAULT NULL,
  `page_Description` mediumtext DEFAULT NULL,
  `page_Link_Label` varchar(50) DEFAULT NULL,
  `page_Link_Menu` enum('dashboard','market','header') DEFAULT NULL,
  `page_Link_Sort` int(11) DEFAULT NULL,
  `page_Menu_Icon` varchar(50) DEFAULT NULL,
  `page_Status` enum('Inactive','Active') DEFAULT 'Inactive',
  `create_ts` datetime NOT NULL DEFAULT current_timestamp(),
  `update_ts` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` varchar(30) DEFAULT NULL,
  `updated_by` varchar(30) DEFAULT NULL,
  PRIMARY KEY (`page_ID`)
) ENGINE=InnoDB AUTO_INCREMENT=57 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `pricing_structure`
--

DROP TABLE IF EXISTS `pricing_structure`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pricing_structure` (
  `pricing_Structure_ID` int(11) NOT NULL AUTO_INCREMENT,
  `facility_ID` int(11) DEFAULT NULL,
  `program_ID` int(11) DEFAULT NULL,
  `pricing_Structure_Name` varchar(50) DEFAULT NULL,
  `pricing_Structure_Desc` mediumtext DEFAULT NULL,
  `pricing_Structure_Rate_Default` decimal(10,2) DEFAULT NULL,
  `create_ts` datetime NOT NULL DEFAULT current_timestamp(),
  `update_ts` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` varchar(30) DEFAULT NULL,
  `updated_by` varchar(30) DEFAULT NULL,
  PRIMARY KEY (`pricing_Structure_ID`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `pricing_structure_rule`
--

DROP TABLE IF EXISTS `pricing_structure_rule`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pricing_structure_rule` (
  `pricing_Structure_Rule_ID` int(11) NOT NULL AUTO_INCREMENT,
  `pricing_Structure_ID` int(11) DEFAULT NULL,
  `pricing_Structure_Rule_Desc` varchar(255) DEFAULT NULL,
  `pricing_Structure_Rule_Weekdays` varchar(30) DEFAULT NULL,
  `pricing_Structure_Rule_Start_Date` date DEFAULT NULL,
  `pricing_Structure_Rule_End_Date` date DEFAULT NULL,
  `pricing_Structure_Rule_Daily_Start_Time` time DEFAULT NULL,
  `pricing_Structure_Rule_Daily_End_Time` time DEFAULT NULL,
  `pricing_Structure_Rule_Rate` decimal(10,2) DEFAULT NULL,
  `pricing_Structure_Rule_Type` enum('Flat','Daily','Hourly') DEFAULT 'Hourly',
  `create_ts` datetime NOT NULL DEFAULT current_timestamp(),
  `update_ts` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` varchar(30) DEFAULT NULL,
  `updated_by` varchar(30) DEFAULT NULL,
  PRIMARY KEY (`pricing_Structure_Rule_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `program`
--

DROP TABLE IF EXISTS `program`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `program` (
  `program_ID` int(11) NOT NULL AUTO_INCREMENT,
  `dim_Program_ID` int(11) DEFAULT NULL COMMENT 'not needed',
  `facility_ID` int(11) NOT NULL DEFAULT 0,
  `program_Admin_User_ID` int(11) DEFAULT NULL,
  `program_Type_ID` int(11) DEFAULT NULL,
  `program_Desc` mediumtext DEFAULT NULL,
  `program_Name` varchar(100) DEFAULT NULL,
  `program_Color` varchar(20) DEFAULT NULL,
  `program_Address_1` varchar(255) DEFAULT NULL,
  `program_Address_2` varchar(255) DEFAULT NULL,
  `program_State_ID` int(11) DEFAULT NULL,
  `program_Country_ID` int(11) DEFAULT NULL,
  `program_Postal_Code` varchar(20) DEFAULT NULL,
  `program_City` varchar(100) DEFAULT NULL,
  `program_Time_Zone` varchar(100) DEFAULT NULL,
  `program_Authorized` int(11) NOT NULL DEFAULT 0,
  `program_Status` enum('Active','Inactive') DEFAULT 'Active',
  `create_ts` datetime NOT NULL DEFAULT current_timestamp(),
  `update_ts` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` varchar(30) DEFAULT NULL,
  `updated_by` varchar(30) DEFAULT NULL,
  PRIMARY KEY (`program_ID`),
  KEY `program_type_ibfk1` (`program_Type_ID`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=91 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `program_level`
--

DROP TABLE IF EXISTS `program_level`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `program_level` (
  `program_Level_ID` int(11) NOT NULL AUTO_INCREMENT,
  `program_Level_Admin_User_ID` int(11) DEFAULT NULL,
  `program_ID` int(11) DEFAULT NULL,
  `program_Level_Name` varchar(100) DEFAULT NULL,
  `program_Level_Rank` int(11) DEFAULT 0,
  `program_Level_Unassigned` tinyint(4) DEFAULT 0,
  `create_ts` datetime NOT NULL DEFAULT current_timestamp(),
  `update_ts` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` varchar(30) DEFAULT NULL,
  `updated_by` varchar(30) DEFAULT NULL,
  PRIMARY KEY (`program_Level_ID`),
  KEY `program_ibfk1` (`program_ID`) USING BTREE,
  CONSTRAINT `program_ID_fk` FOREIGN KEY (`program_ID`) REFERENCES `program` (`program_ID`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=91 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

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
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `resource`
--

DROP TABLE IF EXISTS `resource`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `resource` (
  `resource_ID` int(11) NOT NULL AUTO_INCREMENT,
  `dim_Resource_ID` int(11) DEFAULT NULL,
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
  KEY `facility_ID_ibfk1` (`facility_ID`) USING BTREE,
  KEY `resource_Type_ID_fk` (`resource_Type_ID`),
  CONSTRAINT `facility_ID_ibfk1` FOREIGN KEY (`facility_ID`) REFERENCES `facility` (`facility_ID`) ON DELETE CASCADE ON UPDATE NO ACTION,
  CONSTRAINT `resource_Type_ID_fk` FOREIGN KEY (`resource_Type_ID`) REFERENCES `resource_type` (`resource_Type_ID`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

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
  PRIMARY KEY (`resource_Type_ID`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

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
-- Table structure for table `session`
--

DROP TABLE IF EXISTS `session`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `session` (
  `session_ID` int(11) NOT NULL AUTO_INCREMENT,
  `php_Session_ID` varchar(30) DEFAULT NULL,
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
  PRIMARY KEY (`session_ID`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `step_pricing_rule`
--

DROP TABLE IF EXISTS `step_pricing_rule`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `step_pricing_rule` (
  `step_Pricing_Rule_ID` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Assigns multiple teams to one episode',
  `facility_ID` int(11) DEFAULT NULL,
  `program_ID` int(11) DEFAULT NULL,
  `step_Pricing_Rule_Name` varchar(30) DEFAULT NULL,
  `step_Pricing_Rule_Desc` mediumtext DEFAULT NULL,
  `step_Pricing_Rule_No_Hours` int(11) DEFAULT NULL,
  `step_Pricing_Rule_Price_Reduction` int(11) DEFAULT NULL,
  `step_Pricing_Rule_Percent_Reduction` decimal(10,4) DEFAULT NULL,
  `step_Pricing_Rule_Minimum_Price` decimal(10,2) DEFAULT NULL,
  `step_Pricing_Rule_Start_Date` date DEFAULT NULL,
  `step_Pricing_Rule_End_Date` date DEFAULT NULL,
  `step_Pricing_Rule_Daily_Start_Time` time DEFAULT NULL,
  `step_Pricing_Rule_Daily_End_Time` time DEFAULT NULL,
  `step_Pricing_Rule_Weekdays` varchar(30) DEFAULT NULL,
  `create_ts` datetime NOT NULL DEFAULT current_timestamp(),
  `update_ts` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` varchar(30) DEFAULT NULL,
  `updated_by` varchar(30) DEFAULT NULL,
  PRIMARY KEY (`step_Pricing_Rule_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `team`
--

DROP TABLE IF EXISTS `team`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `team` (
  `team_ID` int(11) NOT NULL AUTO_INCREMENT,
  `team_Admin_User_ID` int(11) DEFAULT NULL,
  `program_ID` int(11) DEFAULT NULL,
  `program_Level_ID` int(11) DEFAULT NULL,
  `team_Name` varchar(100) DEFAULT NULL,
  `team_Color` varchar(30) DEFAULT NULL,
  `team_Unassigned` tinyint(4) DEFAULT 0,
  `create_ts` datetime NOT NULL DEFAULT current_timestamp(),
  `update_ts` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` varchar(30) DEFAULT NULL,
  `updated_by` varchar(30) DEFAULT NULL,
  PRIMARY KEY (`team_ID`),
  KEY `teamLevel_fk` (`program_Level_ID`) USING BTREE,
  CONSTRAINT `program_Level_ID_fk` FOREIGN KEY (`program_Level_ID`) REFERENCES `program_level` (`program_Level_ID`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=91 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `transaction`
--

DROP TABLE IF EXISTS `transaction`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `transaction` (
  `transaction_ID` int(11) NOT NULL AUTO_INCREMENT,
  `order_ID` int(11) DEFAULT NULL,
  `user_ID` int(11) DEFAULT NULL,
  `transaction_Payment_Method` varchar(100) DEFAULT '',
  `transaction_Payment_Gateway_ID` varchar(100) DEFAULT '',
  `transaction_Payment_Gateway` varchar(100) DEFAULT NULL,
  `transaction_Payment_Gateway_TRX_ID` varchar(255) DEFAULT '',
  `transaction_Payment_Gateway_Record` mediumtext DEFAULT NULL,
  `transaction_Payment_Status` enum('Pending','Partial','Received','Refunded','Declined','Canceled','Error') DEFAULT 'Pending',
  `transaction_Payment_Amount` decimal(20,2) DEFAULT NULL,
  `create_ts` datetime NOT NULL DEFAULT current_timestamp(),
  `update_ts` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` varchar(30) DEFAULT NULL,
  `updated_by` varchar(30) DEFAULT NULL,
  PRIMARY KEY (`transaction_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `transaction_detail`
--

DROP TABLE IF EXISTS `transaction_detail`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `transaction_detail` (
  `transaction_Detail_ID` int(11) NOT NULL AUTO_INCREMENT,
  `transaction_ID` int(11) DEFAULT NULL,
  `episode_ID` int(11) DEFAULT NULL,
  `create_ts` datetime NOT NULL DEFAULT current_timestamp(),
  `update_ts` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` varchar(30) DEFAULT NULL,
  `updated_by` varchar(30) DEFAULT NULL,
  PRIMARY KEY (`transaction_Detail_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

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
  `user_Status` enum('Active','Inactive') DEFAULT NULL,
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
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user_group`
--

DROP TABLE IF EXISTS `user_group`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_group` (
  `user_Group_ID` int(11) NOT NULL AUTO_INCREMENT,
  `user_ID` int(11) DEFAULT NULL,
  `group_ID` int(11) DEFAULT NULL,
  `user_Group_Status` enum('Inactive','Active') DEFAULT 'Active',
  `create_ts` datetime NOT NULL DEFAULT current_timestamp(),
  `update_ts` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` varchar(30) DEFAULT NULL,
  `updated_by` varchar(30) DEFAULT NULL,
  PRIMARY KEY (`user_Group_ID`),
  KEY `group_ID` (`group_ID`) USING BTREE,
  KEY `user_ID` (`user_ID`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Temporary view structure for view `v_group`
--

DROP TABLE IF EXISTS `v_group`;
/*!50001 DROP VIEW IF EXISTS `v_group`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `v_group` AS SELECT 
 1 AS `group_ID`,
 1 AS `group_Name`,
 1 AS `group_Desc`,
 1 AS `group_Entity_ID`,
 1 AS `group_Entity`,
 1 AS `group_Entity_Default`,
 1 AS `group_Create_Method`,
 1 AS `create_ts`,
 1 AS `update_ts`,
 1 AS `created_by`,
 1 AS `updated_by`,
 1 AS `entityName`,
 1 AS `entityType`,
 1 AS `program_ID`,
 1 AS `program_Name`,
 1 AS `program_Level_ID`,
 1 AS `team_ID`,
 1 AS `program_Admin_User_ID`*/;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `validation_category`
--

DROP TABLE IF EXISTS `validation_category`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `validation_category` (
  `validation_Category_ID` int(11) NOT NULL AUTO_INCREMENT,
  `validation_Category_Name` varchar(30) DEFAULT NULL,
  `validation_Category_Desc` varchar(250) DEFAULT NULL,
  `create_ts` datetime NOT NULL DEFAULT current_timestamp(),
  `update_ts` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` varchar(30) DEFAULT NULL,
  `updated_by` varchar(30) DEFAULT NULL,
  PRIMARY KEY (`validation_Category_ID`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `validation_status`
--

DROP TABLE IF EXISTS `validation_status`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `validation_status` (
  `validation_Status_ID` int(11) NOT NULL AUTO_INCREMENT,
  `validation_Status_Name` varchar(30) DEFAULT NULL,
  `validation_Category_ID` int(11) DEFAULT NULL,
  `validation_Status_Desc` varchar(250) DEFAULT NULL,
  `create_ts` datetime NOT NULL DEFAULT current_timestamp(),
  `update_ts` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` varchar(30) DEFAULT NULL,
  `updated_by` varchar(30) DEFAULT NULL,
  PRIMARY KEY (`validation_Status_ID`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Final view structure for view `v_group`
--

/*!50001 DROP VIEW IF EXISTS `v_group`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_group` AS select `group`.`group_ID` AS `group_ID`,`group`.`group_Name` AS `group_Name`,`group`.`group_Desc` AS `group_Desc`,`group`.`group_Entity_ID` AS `group_Entity_ID`,`group`.`group_Entity` AS `group_Entity`,`group`.`group_Entity_Default` AS `group_Entity_Default`,`group`.`group_Create_Method` AS `group_Create_Method`,`group`.`create_ts` AS `create_ts`,`group`.`update_ts` AS `update_ts`,`group`.`created_by` AS `created_by`,`group`.`updated_by` AS `updated_by`,`program`.`program_Name` AS `entityName`,'Skating Program' AS `entityType`,`program`.`program_ID` AS `program_ID`,`program`.`program_Name` AS `program_Name`,0 AS `program_Level_ID`,0 AS `team_ID`,`program`.`program_Admin_User_ID` AS `program_Admin_User_ID` from (`group` join `program` on(`program`.`program_ID` = `group`.`group_Entity_ID`)) where `group`.`group_Entity` = 'program_ID' union select `group`.`group_ID` AS `group_ID`,`group`.`group_Name` AS `group_Name`,`group`.`group_Desc` AS `group_Desc`,`group`.`group_Entity_ID` AS `group_Entity_ID`,`group`.`group_Entity` AS `group_Entity`,`group`.`group_Entity_Default` AS `group_Entity_Default`,`group`.`group_Create_Method` AS `group_Create_Method`,`group`.`create_ts` AS `create_ts`,`group`.`update_ts` AS `update_ts`,`group`.`created_by` AS `created_by`,`group`.`updated_by` AS `updated_by`,`program_level`.`program_Level_Name` AS `entityName`,'Skating Level' AS `entityType`,`program`.`program_ID` AS `program_ID`,`program`.`program_Name` AS `program_Name`,`program_level`.`program_Level_ID` AS `program_Level_ID`,0 AS `team_ID`,`program`.`program_Admin_User_ID` AS `program_Admin_User_ID` from ((`group` join `program_level` on(`program_level`.`program_Level_ID` = `group`.`group_Entity_ID`)) join `program` on(`program`.`program_ID` = `program_level`.`program_ID`)) where `group`.`group_Entity` = 'program_Level_ID' union select `group`.`group_ID` AS `group_ID`,`group`.`group_Name` AS `group_Name`,`group`.`group_Desc` AS `group_Desc`,`group`.`group_Entity_ID` AS `group_Entity_ID`,`group`.`group_Entity` AS `group_Entity`,`group`.`group_Entity_Default` AS `group_Entity_Default`,`group`.`group_Create_Method` AS `group_Create_Method`,`group`.`create_ts` AS `create_ts`,`group`.`update_ts` AS `update_ts`,`group`.`created_by` AS `created_by`,`group`.`updated_by` AS `updated_by`,`team`.`team_Name` AS `entityName`,'Group/Skater' AS `entityType`,`program`.`program_ID` AS `program_ID`,`program`.`program_Name` AS `program_Name`,`team`.`program_Level_ID` AS `program_Level_ID`,`team`.`team_ID` AS `team_ID`,`program`.`program_Admin_User_ID` AS `program_Admin_User_ID` from ((`group` join `team` on(`team`.`team_ID` = `group`.`group_Entity_ID`)) join `program` on(`program`.`program_ID` = `team`.`program_ID`)) where `group`.`group_Entity` = 'team_ID' union select `group`.`group_ID` AS `group_ID`,`group`.`group_Name` AS `group_Name`,`group`.`group_Desc` AS `group_Desc`,`group`.`group_Entity_ID` AS `group_Entity_ID`,`group`.`group_Entity` AS `group_Entity`,`group`.`group_Entity_Default` AS `group_Entity_Default`,`group`.`group_Create_Method` AS `group_Create_Method`,`group`.`create_ts` AS `create_ts`,`group`.`update_ts` AS `update_ts`,`group`.`created_by` AS `created_by`,`group`.`updated_by` AS `updated_by`,'Open' AS `entityName`,'Open' AS `entityType`,0 AS `program_ID`,'Open' AS `program_Name`,0 AS `program_Level_ID`,0 AS `team_ID`,0 AS `program_Admin_User_ID` from `group` where `group`.`group_Entity` not in ('program_ID','program_Level_ID','team_ID') */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-06-08 19:21:28
