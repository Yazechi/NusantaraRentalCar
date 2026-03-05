-- ============================================
-- NusantaraRentalCar / METREV - Complete Database
-- This single file contains all tables and reference data.
-- To set up: mysql -u root < nusantara_rental_car.sql
-- ============================================

CREATE DATABASE IF NOT EXISTS `nusantara_rental_car` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `nusantara_rental_car`;
-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: nusantara_rental_car
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
-- Table structure for table `admin_notification_dismissed`
--

DROP TABLE IF EXISTS `admin_notification_dismissed`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admin_notification_dismissed` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `admin_id` int(11) NOT NULL,
  `notification_key` varchar(100) NOT NULL,
  `dismissed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_dismiss` (`admin_id`,`notification_key`),
  KEY `idx_admin` (`admin_id`),
  CONSTRAINT `admin_notification_dismissed_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `admin_notification_read`
--

DROP TABLE IF EXISTS `admin_notification_read`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admin_notification_read` (
  `admin_id` int(11) NOT NULL,
  `last_read_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`admin_id`),
  CONSTRAINT `admin_notification_read_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `car_brands`
--

DROP TABLE IF EXISTS `car_brands`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `car_brands` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;


INSERT INTO `car_brands` (`id`, `name`, `created_at`) VALUES
(1, 'Toyota', '2026-02-10 04:06:48'),
(2, 'Honda', '2026-02-10 04:06:48'),
(3, 'Suzuki', '2026-02-10 04:06:48'),
(4, 'Mitsubishi', '2026-02-10 04:06:48'),
(5, 'Daihatsu', '2026-02-10 04:06:48'),
(6, 'Nissan', '2026-02-10 04:06:48'),
(7, 'BMW', '2026-02-10 04:06:48'),
(8, 'Mercedes-Benz', '2026-02-10 04:06:48'),
(9, 'Hyundai', '2026-02-11 15:03:47'),
(10, 'Kia', '2026-03-03 07:39:15'),
(11, 'Wuling', '2026-03-03 07:39:15'),
(12, 'Isuzu', '2026-03-03 07:39:15'),
(13, 'Hino', '2026-03-03 07:39:15'),
(14, 'Ford', '2026-03-03 07:39:15'),
(15, 'Jeep', '2026-03-03 07:39:15'),
(16, 'Land Rover', '2026-03-03 07:39:15'),
(17, 'Tesla', '2026-03-03 07:39:15'),
(18, 'BYD', '2026-03-03 07:39:15'),
(19, 'Chery', '2026-03-03 07:39:15');

--
-- Table structure for table `car_images`
--

DROP TABLE IF EXISTS `car_images`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `car_images` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `car_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `is_primary` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `car_id` (`car_id`),
  CONSTRAINT `car_images_ibfk_1` FOREIGN KEY (`car_id`) REFERENCES `cars` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `car_rental_goals`
--

DROP TABLE IF EXISTS `car_rental_goals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `car_rental_goals` (
  `car_id` int(11) NOT NULL,
  `rental_goal_id` int(11) NOT NULL,
  PRIMARY KEY (`car_id`,`rental_goal_id`),
  KEY `rental_goal_id` (`rental_goal_id`),
  CONSTRAINT `car_rental_goals_ibfk_1` FOREIGN KEY (`car_id`) REFERENCES `cars` (`id`) ON DELETE CASCADE,
  CONSTRAINT `car_rental_goals_ibfk_2` FOREIGN KEY (`rental_goal_id`) REFERENCES `rental_goals` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;


INSERT INTO `car_rental_goals` (`car_id`, `rental_goal_id`) VALUES
(1, 1),
(1, 2),
(1, 3),
(1, 9),
(3, 1),
(3, 2),
(3, 5),
(3, 9),
(5, 8),
(6, 2),
(6, 5),
(6, 10),
(7, 1),
(7, 5),
(7, 11),
(8, 6),
(8, 7),
(8, 12),
(9, 1),
(9, 9),
(9, 10),
(10, 2),
(10, 5),
(11, 2),
(11, 11),
(12, 1),
(12, 4),
(12, 11),
(13, 6),
(13, 7),
(13, 12),
(14, 9),
(14, 10),
(15, 5),
(15, 8),
(15, 10),
(16, 1),
(16, 3),
(16, 10),
(17, 3),
(17, 10),
(18, 6),
(18, 7),
(18, 12),
(19, 1),
(19, 3),
(19, 4),
(20, 6),
(20, 12),
(21, 2),
(21, 10),
(22, 1),
(22, 3),
(22, 4),
(23, 1),
(23, 4),
(23, 8),
(24, 1),
(24, 3),
(24, 10),
(25, 2),
(25, 5);

--
-- Table structure for table `car_stock`
--

DROP TABLE IF EXISTS `car_stock`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `car_stock` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `car_id` int(11) NOT NULL,
  `plate_number` varchar(20) NOT NULL,
  `status` enum('available','rented','maintenance') DEFAULT 'available',
  `notes` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `plate_number` (`plate_number`),
  KEY `idx_car_status` (`car_id`,`status`),
  KEY `idx_plate` (`plate_number`),
  CONSTRAINT `car_stock_ibfk_1` FOREIGN KEY (`car_id`) REFERENCES `cars` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=51 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `car_types`
--

DROP TABLE IF EXISTS `car_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `car_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `slug` varchar(50) NOT NULL,
  `icon` varchar(50) DEFAULT 'fas fa-car',
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cars`
--

DROP TABLE IF EXISTS `cars`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cars` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `brand_id` int(11) NOT NULL,
  `type_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `model` varchar(100) DEFAULT NULL,
  `year` int(11) DEFAULT NULL,
  `license_plate` varchar(20) DEFAULT NULL,
  `seats` int(11) NOT NULL,
  `transmission` enum('manual','automatic') NOT NULL,
  `fuel_type` enum('pertalite','pertamax','pertamax_turbo','solar','dexlite','pertamina_dex','hybrid','electric') NOT NULL DEFAULT 'pertalite',
  `is_electric` tinyint(1) NOT NULL DEFAULT 0,
  `color` varchar(50) DEFAULT NULL,
  `price_per_day` decimal(10,2) NOT NULL,
  `description` text DEFAULT NULL,
  `specifications` text DEFAULT NULL,
  `image_main` varchar(255) DEFAULT NULL,
  `is_available` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `discount_percent` int(11) DEFAULT 0,
  `is_featured` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `license_plate` (`license_plate`),
  KEY `brand_id` (`brand_id`),
  KEY `fk_cars_type` (`type_id`),
  CONSTRAINT `cars_ibfk_1` FOREIGN KEY (`brand_id`) REFERENCES `car_brands` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cars_type` FOREIGN KEY (`type_id`) REFERENCES `car_types` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `chat_history`
--

DROP TABLE IF EXISTS `chat_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `chat_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `session_id` varchar(100) NOT NULL,
  `message` text NOT NULL,
  `response` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `chat_history_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=39 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `car_id` int(11) NOT NULL,
  `car_stock_id` int(11) DEFAULT NULL,
  `order_type` enum('website','whatsapp') NOT NULL,
  `rental_start_date` date NOT NULL,
  `rental_end_date` date NOT NULL,
  `duration_days` int(11) NOT NULL,
  `delivery_option` enum('pickup','delivery') NOT NULL,
  `delivery_address` text DEFAULT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `status` enum('pending','approved','cancelled','completed') DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `payment_status` enum('unpaid','pending','paid','failed','expired','refunded') DEFAULT 'unpaid',
  `payment_token` varchar(255) DEFAULT NULL,
  `payment_id` varchar(255) DEFAULT NULL,
  `paid_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `rental_occasion` varchar(50) DEFAULT NULL,
  `discount_type` varchar(50) DEFAULT NULL,
  `discount_percent` int(11) DEFAULT 0,
  `original_price` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `car_id` (`car_id`),
  KEY `car_stock_id` (`car_stock_id`),
  CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`car_id`) REFERENCES `cars` (`id`) ON DELETE CASCADE,
  CONSTRAINT `orders_ibfk_3` FOREIGN KEY (`car_stock_id`) REFERENCES `car_stock` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `promotions`
--

DROP TABLE IF EXISTS `promotions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `promotions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `subtitle` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `banner_color` varchar(50) DEFAULT '#667eea',
  `icon` varchar(100) DEFAULT 'fas fa-tag',
  `discount_percent` int(11) DEFAULT 0,
  `valid_from` date DEFAULT NULL,
  `valid_to` date DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `rental_goals`
--

DROP TABLE IF EXISTS `rental_goals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rental_goals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `icon` varchar(50) DEFAULT 'fas fa-flag',
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;


INSERT INTO `rental_goals` (`id`, `name`, `slug`, `icon`, `description`, `created_at`) VALUES
(1, 'Business Trip', 'business-trip', 'fas fa-briefcase', 'Professional travel for meetings, conferences, and corporate events', '2026-03-03 07:39:15'),
(2, 'Vacation', 'vacation', 'fas fa-umbrella-beach', 'Leisure travel for holidays and sightseeing', '2026-03-03 07:39:15'),
(3, 'Honeymoon', 'honeymoon', 'fas fa-heart', 'Romantic getaways for newlyweds', '2026-03-03 07:39:15'),
(4, 'Wedding', 'wedding', 'fas fa-ring', 'Elegant vehicles for wedding ceremonies and receptions', '2026-03-03 07:39:15'),
(5, 'Family Trip', 'family-trip', 'fas fa-users', 'Comfortable rides for family outings and road trips', '2026-03-03 07:39:15'),
(6, 'Industrial', 'industrial', 'fas fa-industry', 'Heavy-duty vehicles for factory, warehouse, and industrial logistics', '2026-03-03 07:39:15'),
(7, 'Construction', 'construction', 'fas fa-hard-hat', 'Tough vehicles for construction sites and material transport', '2026-03-03 07:39:15'),
(8, 'Events & Parties', 'events', 'fas fa-calendar-alt', 'Vehicles for birthdays, reunions, and special celebrations', '2026-03-03 07:39:15'),
(9, 'Airport Transfer', 'airport-transfer', 'fas fa-plane-departure', 'Reliable transport to and from the airport', '2026-03-03 07:39:15'),
(10, 'City Tour', 'city-tour', 'fas fa-city', 'Comfortable cars for exploring cities and landmarks', '2026-03-03 07:39:15'),
(11, 'Adventure & Off-Road', 'adventure', 'fas fa-mountain', 'Rugged vehicles for outdoor adventures and off-road trails', '2026-03-03 07:39:15'),
(12, 'Cargo & Delivery', 'cargo', 'fas fa-boxes', 'Vehicles designed for hauling goods and delivery services', '2026-03-03 07:39:15');

--
-- Table structure for table `site_settings`
--

DROP TABLE IF EXISTS `site_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `site_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `role` enum('user','admin') DEFAULT 'user',
  `email_verified` tinyint(1) DEFAULT 0,
  `verification_token` varchar(100) DEFAULT NULL,
  `reset_token` varchar(100) DEFAULT NULL,
  `reset_token_expires` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_email` (`email`),
  KEY `idx_verification_token` (`verification_token`),
  KEY `idx_reset_token` (`reset_token`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping routines for database 'nusantara_rental_car'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-03-05 12:50:38

-- ============================================
-- Reference Data (brands, types, goals, promos, settings)
-- ============================================
-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: nusantara_rental_car
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Dumping data for table `car_brands`
--

LOCK TABLES `car_brands` WRITE;
/*!40000 ALTER TABLE `car_brands` DISABLE KEYS */;
INSERT INTO `car_brands` VALUES (1,'Toyota','2026-02-10 04:06:48'),(2,'Honda','2026-02-10 04:06:48'),(3,'Suzuki','2026-02-10 04:06:48'),(4,'Mitsubishi','2026-02-10 04:06:48'),(5,'Daihatsu','2026-02-10 04:06:48'),(6,'Nissan','2026-02-10 04:06:48'),(7,'BMW','2026-02-10 04:06:48'),(8,'Mercedes-Benz','2026-02-10 04:06:48'),(9,'Hyundai','2026-02-11 15:03:47'),(10,'Kia','2026-03-03 07:39:15'),(11,'Wuling','2026-03-03 07:39:15'),(12,'Isuzu','2026-03-03 07:39:15'),(13,'Hino','2026-03-03 07:39:15'),(14,'Ford','2026-03-03 07:39:15'),(15,'Jeep','2026-03-03 07:39:15'),(16,'Land Rover','2026-03-03 07:39:15'),(17,'Tesla','2026-03-03 07:39:15'),(18,'BYD','2026-03-03 07:39:15'),(19,'Chery','2026-03-03 07:39:15');
/*!40000 ALTER TABLE `car_brands` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `car_types`
--

LOCK TABLES `car_types` WRITE;
/*!40000 ALTER TABLE `car_types` DISABLE KEYS */;
INSERT INTO `car_types` VALUES (1,'Sedan','sedan','fas fa-car','Comfortable 4-door cars ideal for city driving and business trips','2026-03-03 07:39:15'),(2,'SUV','suv','fas fa-truck-monster','Sport Utility Vehicles with spacious interiors and off-road capability','2026-03-03 07:39:15'),(3,'MPV','mpv','fas fa-shuttle-van','Multi-Purpose Vehicles perfect for family trips and group travel','2026-03-03 07:39:15'),(4,'Hatchback','hatchback','fas fa-car-side','Compact cars with versatile cargo space, great for urban commuting','2026-03-03 07:39:15'),(5,'Pick-Up','pickup','fas fa-truck-pickup','Rugged trucks for hauling cargo, construction, and industrial use','2026-03-03 07:39:15'),(6,'Truck','truck','fas fa-truck','Heavy-duty vehicles for large-scale transport and industrial work','2026-03-03 07:39:15'),(7,'Van','van','fas fa-van-shuttle','Spacious vans for passenger transport or cargo delivery','2026-03-03 07:39:15'),(8,'ATV','atv','fas fa-tractor','All-Terrain Vehicles for adventure and off-road exploration','2026-03-03 07:39:15'),(9,'EV','ev','fas fa-charging-station','Electric Vehicles with zero emissions and modern technology','2026-03-03 07:39:15'),(10,'Coupe','coupe','fas fa-car-side','Sporty 2-door cars for style and performance','2026-03-03 07:39:15'),(11,'Convertible','convertible','fas fa-car','Open-top cars perfect for leisure drives and special occasions','2026-03-03 07:39:15'),(12,'Minibus','minibus','fas fa-bus','Small buses for group transport, events, and tours','2026-03-03 07:39:15');
/*!40000 ALTER TABLE `car_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `rental_goals`
--

LOCK TABLES `rental_goals` WRITE;
/*!40000 ALTER TABLE `rental_goals` DISABLE KEYS */;
INSERT INTO `rental_goals` VALUES (1,'Business Trip','business-trip','fas fa-briefcase','Professional travel for meetings, conferences, and corporate events','2026-03-03 07:39:15'),(2,'Vacation','vacation','fas fa-umbrella-beach','Leisure travel for holidays and sightseeing','2026-03-03 07:39:15'),(3,'Honeymoon','honeymoon','fas fa-heart','Romantic getaways for newlyweds','2026-03-03 07:39:15'),(4,'Wedding','wedding','fas fa-ring','Elegant vehicles for wedding ceremonies and receptions','2026-03-03 07:39:15'),(5,'Family Trip','family-trip','fas fa-users','Comfortable rides for family outings and road trips','2026-03-03 07:39:15'),(6,'Industrial','industrial','fas fa-industry','Heavy-duty vehicles for factory, warehouse, and industrial logistics','2026-03-03 07:39:15'),(7,'Construction','construction','fas fa-hard-hat','Tough vehicles for construction sites and material transport','2026-03-03 07:39:15'),(8,'Events & Parties','events','fas fa-calendar-alt','Vehicles for birthdays, reunions, and special celebrations','2026-03-03 07:39:15'),(9,'Airport Transfer','airport-transfer','fas fa-plane-departure','Reliable transport to and from the airport','2026-03-03 07:39:15'),(10,'City Tour','city-tour','fas fa-city','Comfortable cars for exploring cities and landmarks','2026-03-03 07:39:15'),(11,'Adventure & Off-Road','adventure','fas fa-mountain','Rugged vehicles for outdoor adventures and off-road trails','2026-03-03 07:39:15'),(12,'Cargo & Delivery','cargo','fas fa-boxes','Vehicles designed for hauling goods and delivery services','2026-03-03 07:39:15');
/*!40000 ALTER TABLE `rental_goals` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `promotions`
--

LOCK TABLES `promotions` WRITE;
/*!40000 ALTER TABLE `promotions` DISABLE KEYS */;
INSERT INTO `promotions` VALUES (1,'Weekend Special','Up to 25% off on weekend rentals','Book any car for Saturday-Sunday and enjoy automatic discounts','#ff6b35','fas fa-calendar-week',25,'2026-01-01','2026-12-31',1,1,'2026-03-04 14:35:20'),(2,'First Ride Bonus','Get 15% off your first rental','New to Nusantara? Enjoy a welcome discount on your first booking','#10b981','fas fa-gift',15,'2026-01-01','2026-12-31',1,2,'2026-03-04 14:35:20'),(3,'Long Trip Deal','Rent 7+ days and save 20%','Planning a long trip? The longer you rent, the more you save','#667eea','fas fa-road',20,'2026-01-01','2026-12-31',1,3,'2026-03-04 14:35:20'),(4,'Family Package','MPV & SUV deals for families','Special rates on 7-seater vehicles perfect for family adventures','#764ba2','fas fa-users',10,'2026-01-01','2026-12-31',1,4,'2026-03-04 14:35:20');
/*!40000 ALTER TABLE `promotions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `site_settings`
--

LOCK TABLES `site_settings` WRITE;
/*!40000 ALTER TABLE `site_settings` DISABLE KEYS */;
INSERT INTO `site_settings` VALUES (1,'whatsapp_number','6287812312632','2026-02-11 15:44:36'),(2,'site_name','Nusantara Rental Car','2026-02-10 04:06:48'),(3,'site_address','Jakarta, Indonesia','2026-02-10 04:06:48'),(5,'admin_email','','2026-02-11 14:42:27'),(6,'gemini_api_key','AIzaSyBOxhyTXJQrbSLero8-M9VpMV55TWgYbEQ','2026-02-11 14:42:27'),(10,'midtrans_client_key','','2026-03-03 07:49:06'),(11,'midtrans_server_key','','2026-03-03 07:49:06');
/*!40000 ALTER TABLE `site_settings` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-03-05 12:50:38

-- ============================================
-- Default Admin Account
-- ============================================

--
-- Default admin account (Email: admin@nusantararental.com / Password: Admin@2024!)
--

LOCK TABLES `users` WRITE;
INSERT INTO `users` (`id`, `name`, `email`, `password`, `phone`, `address`, `role`, `email_verified`, `verification_token`, `reset_token`, `reset_token_expires`, `created_at`, `updated_at`) VALUES
(1, 'Admin', 'admin@nusantararental.com', '$2y$10$b4BByMoVnxb0TRPLzC8owe3JgwnbIuMgw5XCmxrfq9LNAJW6Ud2O2', '082933102923', 'Medan', 'admin', 0, NULL, NULL, NULL, NOW(), NOW());
UNLOCK TABLES;

