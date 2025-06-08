-- Collaborative Calendar Database Schema
-- Location: documentation/calendar-app.sql
-- 
-- Import this file into your MySQL database to create the required tables
-- for the collaborative calendar application.
--
-- Usage:
-- 1. Create database: CREATE DATABASE collaborative_calendar;
-- 2. Import this file: mysql -u username -p collaborative_calendar < calendar-app.sql

CREATE DATABASE IF NOT EXISTS `collaborative_calendar` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci */;
USE `collaborative_calendar`;

-- MySQL dump 10.13  Distrib 8.0.42, for Win64 (x86_64)
--
-- Host: localhost    Database: collaborative_calendar
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
-- This table stores real-time update events for SSE broadcasting
--

DROP TABLE IF EXISTS `calendar_updates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `calendar_updates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `event_type` varchar(50) NOT NULL COMMENT 'Type of update: create, update, delete, user_created',
  `event_data` text NOT NULL COMMENT 'JSON data for the event',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'When the update was created',
  PRIMARY KEY (`id`),
  KEY `created_at` (`created_at`),
  KEY `event_type` (`event_type`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Stores real-time updates for SSE broadcasting';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `events`
-- This table stores calendar events
--

DROP TABLE IF EXISTS `events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `events` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL COMMENT 'Reference to the user who created this event',
  `title` varchar(255) NOT NULL COMMENT 'Event title',
  `start_datetime` datetime NOT NULL COMMENT 'Event start date and time',
  `end_datetime` datetime NOT NULL COMMENT 'Event end date and time',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'When the event was created',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT 'When the event was last updated',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `start_datetime` (`start_datetime`),
  KEY `end_datetime` (`end_datetime`),
  CONSTRAINT `events_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Stores calendar events';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `users`
-- This table stores user information
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL COMMENT 'User display name',
  `color` varchar(7) DEFAULT '#3788d8' COMMENT 'User color for calendar events (hex format)',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'When the user was created',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Stores user information';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Sample data (optional) - Remove these if you don't want sample data
--

-- Sample users
INSERT INTO `users` (`name`, `color`) VALUES 
('Alice Johnson', '#e74c3c'),
('Bob Smith', '#2ecc71'),
('Carol Davis', '#f39c12');

-- Sample events
INSERT INTO `events` (`user_id`, `title`, `start_datetime`, `end_datetime`) VALUES
(1, 'Team Meeting', '2025-06-09 10:00:00', '2025-06-09 11:00:00'),
(1, 'Project Deadline', '2025-06-10 17:00:00', '2025-06-10 18:00:00'),
(2, 'Client Call', '2025-06-09 14:00:00', '2025-06-09 15:00:00'),
(3, 'Design Review', '2025-06-11 09:00:00', '2025-06-11 10:30:00');

/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Additional indexes for performance optimization
CREATE INDEX idx_events_user_start ON events(user_id, start_datetime);
CREATE INDEX idx_calendar_updates_type_created ON calendar_updates(event_type, created_at);

-- Views for easier querying (optional)

-- View to get events with user information
CREATE VIEW event_details AS
SELECT 
    e.id,
    e.title,
    e.start_datetime,
    e.end_datetime,
    e.created_at as event_created_at,
    e.updated_at as event_updated_at,
    u.id as user_id,
    u.name as user_name,
    u.color as user_color,
    u.created_at as user_created_at
FROM events e
JOIN users u ON e.user_id = u.id;

-- View to get recent updates
CREATE VIEW recent_updates AS
SELECT 
    id,
    event_type,
    event_data,
    created_at,
    DATE_FORMAT(created_at, '%Y-%m-%d %H:%i:%s') as formatted_date
FROM calendar_updates 
ORDER BY created_at DESC 
LIMIT 100;

-- Triggers for automatic cleanup (optional)

DELIMITER ;;

-- Trigger to automatically clean up old calendar_updates
CREATE TRIGGER cleanup_old_updates
AFTER INSERT ON calendar_updates
FOR EACH ROW
BEGIN
    -- Keep only the latest 1000 updates
    DELETE FROM calendar_updates 
    WHERE id < (
        SELECT id FROM (
            SELECT id FROM calendar_updates 
            ORDER BY id DESC 
            LIMIT 1 OFFSET 1000
        ) AS temp
    );
END;;

DELIMITER ;

-- Dump completed on 2025-06-08 with restructured folders
-- 
-- Setup Instructions:
-- 1. Import this SQL file into your MySQL database
-- 2. Update backend/database/config.php with your database credentials
-- 3. Ensure your web server has PHP with PDO MySQL extension
-- 4. Place the application files in your web server directory
-- 5. Access the application via your web server