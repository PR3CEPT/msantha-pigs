-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: msantha_pigs
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

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
-- Table structure for table `activity_logs`
--

DROP TABLE IF EXISTS `activity_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `username` varchar(50) NOT NULL,
  `user_role` varchar(20) DEFAULT 'clerk',
  `action` varchar(50) NOT NULL,
  `description` text NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activity_logs`
--

LOCK TABLES `activity_logs` WRITE;
/*!40000 ALTER TABLE `activity_logs` DISABLE KEYS */;
INSERT INTO `activity_logs` VALUES (1,1,'System Admin','admin','pig_updated','Updated pig profile for tag #MS007 (Stage: Adult, Breed: Large white)','127.0.0.1','2026-08-05 19:24:50'),(2,1,'System Admin','admin','pig_created','Registered new pig ear tag #MS011 (Sex: Male, Breed: Large white, Stage: Adult, Source: Born on Farm)','127.0.0.1','2026-08-05 19:47:55'),(3,1,'System Admin','admin','pig_created','Registered new pig ear tag #MS017 (Sex: Female, Breed: Large white, Stage: Adult, Source: Born on Farm)','127.0.0.1','2026-08-05 19:48:58'),(4,1,'System Admin','admin','pig_created','Registered new pig ear tag #HK2730 (Sex: Female, Breed: DUROC, Stage: Adult, Source: External Purchase)','127.0.0.1','2026-08-05 19:52:19'),(5,1,'System Admin','admin','pig_created','Registered new pig ear tag #722 (Sex: Male [Castrated Barrow], Breed: LANDRACE, Stage: Adult, Source: External Purchase)','127.0.0.1','2026-08-05 20:11:56'),(6,1,'System Admin','admin','breeding_added','Logged breeding service for sow #HK2730 (Sire: MS027, Expected Farrowing: 2026-11-14)','127.0.0.1','2026-08-05 20:16:21'),(7,1,'System Admin','admin','pig_created','Registered new pig ear tag #MS020 (Sex: Female, Breed: Large white, Stage: Adult, Source: Born on Farm)','127.0.0.1','2026-08-05 20:41:08'),(8,1,'System Admin','admin','pig_created','Registered new pig ear tag #MS009 (Sex: Female, Breed: Large white, Stage: Adult, Source: Born on Farm)','127.0.0.1','2026-08-05 20:42:21'),(9,1,'System Admin','admin','breeding_added','Logged breeding service for sow #MS009 (Sire: MS027, Expected Farrowing: 2026-11-18)','127.0.0.1','2026-08-05 20:43:09'),(10,1,'System Admin','admin','pig_created','Registered new pig ear tag #MS051 (Sex: Male [Intact Boar], Breed: Large white, Stage: Weaner, Source: Born on Farm)','127.0.0.1','2026-08-05 20:46:12'),(11,1,'System Admin','admin','pig_created','Registered new pig ear tag #MS042 (Sex: Male [Castrated Barrow], Breed: Large white, Stage: Weaner, Source: Born on Farm)','127.0.0.1','2026-08-05 20:48:23'),(12,1,'System Admin','admin','pig_created','Registered new pig ear tag #1249 (Sex: Male [Castrated Barrow], Breed: DUROC, Stage: Weaner, Source: External Purchase)','127.0.0.1','2026-08-05 20:50:01'),(13,1,'System Admin','admin','logout','User \'System Admin\' logged out of the system','127.0.0.1','2026-08-05 21:33:27'),(14,1,'admin','admin','login','User \'admin\' logged into system successfully','127.0.0.1','2026-08-25 17:01:12'),(15,1,'System','system','stage_transition','Pig #722 (LANDRACE) has graduated: ???? Grower → ???? Finisher (auto-detected from date of birth)','127.0.0.1','2026-08-25 17:01:13'),(16,1,'System','system','stage_transition','Pig #MS051 (Large white) has graduated: ???? Weaner → ???? Grower (auto-detected from date of birth)','127.0.0.1','2026-08-25 17:01:13'),(17,1,'System','system','stage_transition','Pig #MS042 (Large white) has graduated: ???? Weaner → ???? Grower (auto-detected from date of birth)','127.0.0.1','2026-08-25 17:01:13'),(18,1,'admin','admin','password_changed','Updated account password and profile details','127.0.0.1','2026-08-25 17:14:28'),(19,1,'admin','admin','pig_created','Registered new pig ear tag #716 (Sex: Male [Castrated Barrow], Breed: Large white, Stage: Adult, Source: Born on Farm)','127.0.0.1','2026-08-25 18:15:24'),(20,1,'admin','admin','pig_updated','Updated pig profile for tag #716 (Stage: Piglet, Breed: Large white)','127.0.0.1','2026-08-25 18:18:30'),(21,1,'System','system','stage_transition','Pig #716 (Large white) has graduated: ???? Piglet → ???? Finisher (auto-detected from date of birth)','127.0.0.1','2026-08-25 18:18:30'),(22,1,'admin','admin','pig_updated','Updated pig profile for tag #716 (Stage: Piglet, Breed: Large white)','127.0.0.1','2026-08-25 20:07:55');
/*!40000 ALTER TABLE `activity_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `breeding_records`
--

DROP TABLE IF EXISTS `breeding_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `breeding_records` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pig_id` int(11) NOT NULL,
  `date_of_service` date NOT NULL,
  `sire_no` varchar(50) DEFAULT NULL,
  `expected_farrowing` date DEFAULT NULL,
  `actual_farrowing` date DEFAULT NULL,
  `total_born` int(11) DEFAULT NULL,
  `born_alive` int(11) DEFAULT NULL,
  `stillborn` int(11) DEFAULT NULL,
  `avg_weaning_wt` decimal(10,2) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'pregnant',
  `weaning_date` date DEFAULT NULL,
  `weaned_count` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pig_id` (`pig_id`),
  CONSTRAINT `breeding_records_ibfk_1` FOREIGN KEY (`pig_id`) REFERENCES `pigs` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `breeding_records`
--

LOCK TABLES `breeding_records` WRITE;
/*!40000 ALTER TABLE `breeding_records` DISABLE KEYS */;
INSERT INTO `breeding_records` VALUES (1,4,'2026-07-23','MS027','2026-11-14',NULL,NULL,NULL,NULL,NULL,'pregnant',NULL,NULL),(2,7,'2026-07-27','MS027','2026-11-18',NULL,NULL,NULL,NULL,NULL,'pregnant',NULL,NULL);
/*!40000 ALTER TABLE `breeding_records` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `growth_records`
--

DROP TABLE IF EXISTS `growth_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `growth_records` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pig_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `weight` decimal(10,2) DEFAULT NULL,
  `age_days` int(11) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pig_id` (`pig_id`),
  CONSTRAINT `growth_records_ibfk_1` FOREIGN KEY (`pig_id`) REFERENCES `pigs` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `growth_records`
--

LOCK TABLES `growth_records` WRITE;
/*!40000 ALTER TABLE `growth_records` DISABLE KEYS */;
/*!40000 ALTER TABLE `growth_records` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mortality`
--

DROP TABLE IF EXISTS `mortality`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mortality` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pig_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `cause` varchar(255) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pig_id` (`pig_id`),
  CONSTRAINT `mortality_ibfk_1` FOREIGN KEY (`pig_id`) REFERENCES `pigs` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mortality`
--

LOCK TABLES `mortality` WRITE;
/*!40000 ALTER TABLE `mortality` DISABLE KEYS */;
/*!40000 ALTER TABLE `mortality` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(30) NOT NULL DEFAULT 'info',
  `title` varchar(120) NOT NULL,
  `message` text NOT NULL,
  `pig_id` int(11) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
INSERT INTO `notifications` VALUES (1,'info','Stage Update: Pig #722','Pig #722 (LANDRACE) has moved from ???? Grower to ???? Finisher.',5,0,'2026-08-25 17:01:13'),(2,'info','Stage Update: Pig #MS051','Pig #MS051 (Large white) has moved from ???? Weaner to ???? Grower.',9,0,'2026-08-25 17:01:13'),(3,'info','Stage Update: Pig #MS042','Pig #MS042 (Large white) has moved from ???? Weaner to ???? Grower.',10,0,'2026-08-25 17:01:13'),(4,'info','Stage Update: Pig #716','Pig #716 (Large white) has moved from ???? Piglet to ???? Finisher.',12,0,'2026-08-25 18:18:30');
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pigs`
--

DROP TABLE IF EXISTS `pigs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pigs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tag_no` varchar(50) NOT NULL,
  `sex` varchar(10) NOT NULL,
  `breed` varchar(50) DEFAULT NULL,
  `dob` date NOT NULL,
  `sire` varchar(50) DEFAULT NULL,
  `dam` varchar(50) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'active',
  `stage` varchar(20) DEFAULT 'adult',
  `source` varchar(50) DEFAULT 'Born on Farm',
  `castrated` tinyint(1) DEFAULT 0,
  `castration_date` date DEFAULT NULL,
  `last_known_stage` varchar(20) DEFAULT NULL,
  `purchase_price` decimal(10,2) DEFAULT NULL,
  `vendor` varchar(255) DEFAULT NULL,
  `acquisition_type` varchar(30) DEFAULT 'born',
  PRIMARY KEY (`id`),
  UNIQUE KEY `tag_no` (`tag_no`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pigs`
--

LOCK TABLES `pigs` WRITE;
/*!40000 ALTER TABLE `pigs` DISABLE KEYS */;
INSERT INTO `pigs` VALUES (1,'MS007','Male','Large white','2024-10-30','','','active','adult','External Purchase',0,NULL,'adult',NULL,NULL,'purchased'),(2,'MS011','Male','Large white','2025-09-05','MS007','','active','adult','Born on Farm',0,NULL,'adult',NULL,NULL,'born'),(3,'MS017','Female','Large white','2025-11-05','MS007','','active','adult','Born on Farm',0,NULL,'adult',NULL,NULL,'born'),(4,'HK2730','Female','DUROC','2025-08-25','','','active','adult','External Purchase',0,NULL,'adult',NULL,NULL,'purchased'),(5,'722','Male','LANDRACE','2026-03-20','','','active','adult','External Purchase',1,'2026-05-05','finisher',NULL,NULL,'purchased'),(6,'MS020','Female','Large white','2026-03-05','MS007','','active','adult','Born on Farm',0,NULL,'finisher',NULL,NULL,'born'),(7,'MS009','Female','Large white','2025-11-05','MS007','','active','adult','Born on Farm',0,NULL,'adult',NULL,NULL,'born'),(9,'MS051','Male','Large white','2026-05-15','MS007','','active','weaner','Born on Farm',0,NULL,'grower',NULL,NULL,'born'),(10,'MS042','Male','Large white','2026-05-15','MS007','','active','weaner','Born on Farm',1,'2026-06-15','grower',NULL,NULL,'born'),(11,'1249','Male','DUROC','2026-04-20','','','active','weaner','External Purchase',1,'2026-06-15','grower',NULL,NULL,'purchased'),(12,'716','Male','Large white','2026-02-25','MS007','','active','piglet','Born on Farm',1,'2026-06-25','finisher',NULL,NULL,'born');
/*!40000 ALTER TABLE `pigs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sales`
--

DROP TABLE IF EXISTS `sales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sales` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(20) NOT NULL,
  `reference_id` varchar(50) DEFAULT NULL,
  `weight` decimal(10,2) DEFAULT NULL,
  `date` date NOT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `buyer_info` varchar(255) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sales`
--

LOCK TABLES `sales` WRITE;
/*!40000 ALTER TABLE `sales` DISABLE KEYS */;
/*!40000 ALTER TABLE `sales` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(20) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `session_token` varchar(64) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'admin','$2y$10$Omi/.MbxJJKL83p9Qz/Ine3n7paEIhgJOg0Q4AaSUesnIxw2hcVSK','admin','+265888880057','ISAAC CHIPETA'),(2,'clerk','$2y$10$g0//SgGrOx04YuItXurFYOUAQQtrZb/tvxRlLySfjTm3VSwjYjSHe','clerk','0000000000','Farm Clerk');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vaccination_records`
--

DROP TABLE IF EXISTS `vaccination_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `vaccination_records` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pig_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `vaccine` varchar(100) DEFAULT NULL,
  `dose` varchar(50) DEFAULT NULL,
  `route` varchar(50) DEFAULT NULL,
  `administered_by` varchar(100) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pig_id` (`pig_id`),
  CONSTRAINT `vaccination_records_ibfk_1` FOREIGN KEY (`pig_id`) REFERENCES `pigs` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vaccination_records`
--

LOCK TABLES `vaccination_records` WRITE;
/*!40000 ALTER TABLE `vaccination_records` DISABLE KEYS */;
/*!40000 ALTER TABLE `vaccination_records` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-08-25 22:57:16
