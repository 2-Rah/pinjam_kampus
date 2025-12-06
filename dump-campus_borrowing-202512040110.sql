-- MySQL dump 10.13  Distrib 8.0.30, for Win64 (x86_64)
--
-- Host: localhost    Database: campus_borrowing
-- ------------------------------------------------------
-- Server version	8.0.30

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `borrowing_details`
--

DROP TABLE IF EXISTS `borrowing_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `borrowing_details` (
  `id` int NOT NULL AUTO_INCREMENT,
  `borrowing_id` int NOT NULL,
  `item_id` int NOT NULL,
  `quantity` int NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_borrowing_item` (`borrowing_id`,`item_id`),
  KEY `item_id` (`item_id`),
  CONSTRAINT `borrowing_details_ibfk_1` FOREIGN KEY (`borrowing_id`) REFERENCES `borrowings` (`id`) ON DELETE CASCADE,
  CONSTRAINT `borrowing_details_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `borrowing_details`
--

LOCK TABLES `borrowing_details` WRITE;
/*!40000 ALTER TABLE `borrowing_details` DISABLE KEYS */;
INSERT INTO `borrowing_details` VALUES (9,8,5,1,'2025-11-24 01:51:44'),(10,9,8,1,'2025-11-24 02:12:52'),(11,10,8,1,'2025-11-24 02:29:25'),(12,11,8,1,'2025-12-03 15:03:16'),(13,12,6,1,'2025-12-03 15:29:26'),(14,13,5,1,'2025-12-03 16:53:31'),(15,13,9,1,'2025-12-03 16:53:31'),(16,13,7,1,'2025-12-03 16:53:31');
/*!40000 ALTER TABLE `borrowing_details` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `borrowings`
--

DROP TABLE IF EXISTS `borrowings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `borrowings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `status` enum('pending','approved','rejected','picked_up','completed','not_returned') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `pickup_location` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rejection_reason` text COLLATE utf8mb4_unicode_ci,
  `approved_at` timestamp NULL DEFAULT NULL,
  `approved_by` int DEFAULT NULL,
  `pickup_image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `judul` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `approved_by` (`approved_by`),
  CONSTRAINT `borrowings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `borrowings_ibfk_2` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `borrowings`
--

LOCK TABLES `borrowings` WRITE;
/*!40000 ALTER TABLE `borrowings` DISABLE KEYS */;
INSERT INTO `borrowings` VALUES (8,11,'','2025-11-24','2025-11-25','12312312e','picked_up','jpewofjsdhaiwcnisdfhmxoewhfmrujmhqrghreg9phcp9rmghruejg3orxmdklgheawgn0rugjepoigjaisjfsdokgjnogmuerjpiiiiiiigghhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhjjjjjjjjjjjjjjjjjkldfgahgoreihaldfjk',NULL,NULL,NULL,NULL,'2025-11-24 01:51:44','2025-11-24 01:55:52',NULL),(9,11,'','2025-11-24','2025-11-26','buat tugas','picked_up','ti???',NULL,NULL,NULL,NULL,'2025-11-24 02:12:52','2025-11-24 02:13:37',NULL),(10,11,'','2025-11-24','2025-11-24','123','picked_up','dsdsad',NULL,NULL,NULL,NULL,'2025-11-24 02:29:25','2025-12-03 15:20:08',NULL),(11,11,'','2025-12-04','2025-12-03','may be we need a judul\r\n','pending','tester ',NULL,NULL,NULL,NULL,'2025-12-03 15:03:16','2025-12-03 15:03:16',NULL),(12,11,'untuk test aku masih hidup atau gak','2025-12-05','2025-12-27','well i fucked up','pending',NULL,NULL,NULL,NULL,NULL,'2025-12-03 15:29:26','2025-12-03 15:29:26',NULL),(13,12,'mikumiku','2025-12-18','2026-01-01','miku<3','pending',NULL,NULL,NULL,NULL,NULL,'2025-12-03 16:53:31','2025-12-03 16:53:31',NULL);
/*!40000 ALTER TABLE `borrowings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `items`
--

DROP TABLE IF EXISTS `items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('barang','ruangan') COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `stock` int NOT NULL DEFAULT '1',
  `capacity` int DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `items`
--

LOCK TABLES `items` WRITE;
/*!40000 ALTER TABLE `items` DISABLE KEYS */;
INSERT INTO `items` VALUES (5,'My Istri','barang','Istri',1,NULL,'Punya Turah','1762779136_wallpaper761.png',1,'2025-11-10 12:52:16'),(6,'test item','barang','testing',13,NULL,'testing','1763341625_wallpaper766__1_.png',1,'2025-11-17 01:07:05'),(7,'miku','barang','miku',1,NULL,'miku','1763341785_undefined_-_Imgur__1_.png',1,'2025-11-17 01:09:45'),(8,'laptop','barang','laptop',1,NULL,'laptop aja','1763950306_13-laptop-platinum-right-render-fy25_VP4-1260x795.avif',1,'2025-11-24 02:11:36'),(9,'ruang','ruangan','kelas',1,2000,'hey ini kelas','1764777334_Screenshot_2025-10-13_231152.png',1,'2025-12-03 15:55:34');
/*!40000 ALTER TABLE `items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `login_logs`
--

DROP TABLE IF EXISTS `login_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `login_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `login_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `login_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `login_logs`
--

LOCK TABLES `login_logs` WRITE;
/*!40000 ALTER TABLE `login_logs` DISABLE KEYS */;
INSERT INTO `login_logs` VALUES (1,11,'2025-11-25 03:26:05','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36'),(2,5,'2025-11-25 09:13:56','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36'),(3,11,'2025-12-01 01:32:21','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36'),(4,11,'2025-12-01 02:21:36','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36'),(5,11,'2025-12-01 02:22:09','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36'),(6,11,'2025-12-01 02:29:06','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36'),(7,11,'2025-12-03 15:01:03','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36'),(8,11,'2025-12-03 15:15:01','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36'),(9,12,'2025-12-03 16:52:30','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36');
/*!40000 ALTER TABLE `login_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `return_details`
--

DROP TABLE IF EXISTS `return_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `return_details` (
  `id` int NOT NULL AUTO_INCREMENT,
  `return_id` int NOT NULL,
  `item_id` int NOT NULL,
  `borrowing_detail_id` int NOT NULL,
  `quantity` int NOT NULL DEFAULT '1',
  `item_condition` enum('good','damaged','lost','needs_repair') COLLATE utf8mb4_unicode_ci DEFAULT 'good',
  `image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('pending','approved','rejected') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `rejection_reason` text COLLATE utf8mb4_unicode_ci,
  `approved_at` timestamp NULL DEFAULT NULL,
  `approved_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `return_id` (`return_id`),
  KEY `item_id` (`item_id`),
  KEY `borrowing_detail_id` (`borrowing_detail_id`),
  KEY `approved_by` (`approved_by`),
  CONSTRAINT `return_details_ibfk_1` FOREIGN KEY (`return_id`) REFERENCES `returns` (`id`) ON DELETE CASCADE,
  CONSTRAINT `return_details_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`),
  CONSTRAINT `return_details_ibfk_3` FOREIGN KEY (`borrowing_detail_id`) REFERENCES `borrowing_details` (`id`),
  CONSTRAINT `return_details_ibfk_4` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `return_details`
--

LOCK TABLES `return_details` WRITE;
/*!40000 ALTER TABLE `return_details` DISABLE KEYS */;
INSERT INTO `return_details` VALUES (4,3,5,9,1,'damaged','pengembalian_barang/1763949617_wallpaper761.png','pending',NULL,NULL,NULL,'2025-11-24 02:00:17'),(5,4,8,10,1,'good','pengembalian_barang/1763950431_13-laptop-platinum-right-render-fy25_VP4-1260x795.avif','pending',NULL,NULL,NULL,'2025-11-24 02:13:51'),(6,5,8,11,1,'good','pengembalian_barang/1763951405_1763950431_13-laptop-platinum-right-render-fy25_VP4-1260x795 (1).avif','pending',NULL,NULL,NULL,'2025-11-24 02:30:05');
/*!40000 ALTER TABLE `return_details` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `returns`
--

DROP TABLE IF EXISTS `returns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `returns` (
  `id` int NOT NULL AUTO_INCREMENT,
  `borrowing_id` int NOT NULL,
  `user_id` int NOT NULL,
  `return_date` date NOT NULL,
  `status` enum('pending','approved','rejected','completed') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `rejection_reason` text COLLATE utf8mb4_unicode_ci,
  `approved_at` timestamp NULL DEFAULT NULL,
  `approved_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `borrowing_id` (`borrowing_id`),
  KEY `user_id` (`user_id`),
  KEY `approved_by` (`approved_by`),
  CONSTRAINT `returns_ibfk_1` FOREIGN KEY (`borrowing_id`) REFERENCES `borrowings` (`id`),
  CONSTRAINT `returns_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `returns_ibfk_3` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `returns`
--

LOCK TABLES `returns` WRITE;
/*!40000 ALTER TABLE `returns` DISABLE KEYS */;
INSERT INTO `returns` VALUES (3,8,11,'2025-11-24','approved','','2025-11-24 02:10:13',5,'2025-11-24 02:00:17','2025-11-24 02:10:13'),(4,9,11,'2025-11-24','approved','','2025-11-24 02:23:08',5,'2025-11-24 02:13:51','2025-11-24 02:23:08'),(5,10,11,'2025-11-24','approved','','2025-11-24 02:30:26',5,'2025-11-24 02:30:05','2025-11-24 02:30:26');
/*!40000 ALTER TABLE `returns` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nim_nip` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('user','admin','none') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'none',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `nim_nip` (`nim_nip`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (5,'Admin Test','admin@test.com','123','$2y$10$ZvomBL/EnHeC0OQ9.72GY.PkWrHiWgSKfO/82IA5MLEtwUKgFJHPG','admin','2025-11-02 16:05:23'),(11,'admin penghutang handal','123@admin','222','$2y$10$q86QLUkCp92bJoklIB9zf.Ukz8yEVprc2l6TZrgsJ0Ue1F3ZXdQ5G','user','2025-11-10 04:33:15'),(12,'Hatsune Miku','hatsunemiku@miku.com','39','$2y$10$xtv97CEeUlQt8SJ7DjWN/OsXJNEBpFJtqAJ8NH2nlwQ12mUr8QKPy','user','2025-12-03 15:49:01');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'campus_borrowing'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-12-04  1:10:57
