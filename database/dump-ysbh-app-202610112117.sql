/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19-11.7.2-MariaDB, for Win64 (AMD64)
--
-- Host: 127.0.0.1    Database: ysbh-app
-- ------------------------------------------------------
-- Server version	10.11.11-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*M!100616 SET @OLD_NOTE_VERBOSITY=@@NOTE_VERBOSITY, NOTE_VERBOSITY=0 */;

--
-- Table structure for table `activity_log`
--

DROP TABLE IF EXISTS `activity_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `activity_log` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `log_name` varchar(255) DEFAULT NULL,
  `description` text NOT NULL,
  `subject_type` varchar(255) DEFAULT NULL,
  `subject_id` bigint(20) unsigned DEFAULT NULL,
  `event` varchar(255) DEFAULT NULL,
  `causer_type` varchar(255) DEFAULT NULL,
  `causer_id` bigint(20) unsigned DEFAULT NULL,
  `attribute_changes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`attribute_changes`)),
  `properties` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`properties`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `subject` (`subject_type`,`subject_id`),
  KEY `causer` (`causer_type`,`causer_id`),
  KEY `activity_log_log_name_index` (`log_name`)
) ENGINE=InnoDB AUTO_INCREMENT=100 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activity_log`
--

LOCK TABLES `activity_log` WRITE;
/*!40000 ALTER TABLE `activity_log` DISABLE KEYS */;
INSERT INTO `activity_log` VALUES
(1,'snippet_updates','created','snippet',1,'created',NULL,NULL,'{\"attributes\":{\"key\":\"legal-details\",\"title\":\"Legal and bank details\",\"status\":\"offline\",\"is_closing\":false,\"sort_order\":0}}','[]','2026-10-09 18:54:11','2026-10-09 18:54:11'),
(2,'default','created','page',1,'created',NULL,NULL,'[]','[]','2026-10-09 18:54:11','2026-10-09 18:54:11'),
(3,'default','created','page',2,'created',NULL,NULL,'[]','[]','2026-10-09 18:54:11','2026-10-09 18:54:11'),
(4,'default','created','page',3,'created',NULL,NULL,'[]','[]','2026-10-09 18:54:11','2026-10-09 18:54:11'),
(5,'default','created','page',4,'created',NULL,NULL,'[]','[]','2026-10-09 18:54:11','2026-10-09 18:54:11'),
(6,'default','created','page',5,'created',NULL,NULL,'[]','[]','2026-10-09 18:54:11','2026-10-09 18:54:11'),
(7,'default','created','page',6,'created',NULL,NULL,'[]','[]','2026-10-09 18:54:11','2026-10-09 18:54:11'),
(8,'default','created','page',7,'created',NULL,NULL,'[]','[]','2026-10-09 18:54:11','2026-10-09 18:54:11'),
(9,'default','created','page',8,'created',NULL,NULL,'[]','[]','2026-10-09 18:54:11','2026-10-09 18:54:11'),
(10,'default','created','page',9,'created',NULL,NULL,'[]','[]','2026-10-09 18:54:11','2026-10-09 18:54:11'),
(11,'default','created','page',10,'created',NULL,NULL,'[]','[]','2026-10-09 18:54:11','2026-10-09 18:54:11'),
(12,'default','created','page',11,'created',NULL,NULL,'[]','[]','2026-10-09 18:54:11','2026-10-09 18:54:11'),
(13,'default','created','page',12,'created',NULL,NULL,'[]','[]','2026-10-09 18:54:11','2026-10-09 18:54:11'),
(14,'security','Pengguna berhasil masuk ke sistem',NULL,NULL,NULL,'App\\Models\\User',2,'[]','{\"ip_address\":\"127.0.0.1\",\"browser\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/154.0.0.0 Safari\\/537.36\"}','2026-10-09 18:54:19','2026-10-09 18:54:19'),
(15,'default','updated','page',6,'updated','App\\Models\\User',2,'[]','[]','2026-10-09 18:56:02','2026-10-09 18:56:02'),
(16,'default','updated','page',1,'updated','App\\Models\\User',2,'[]','[]','2026-10-09 18:57:22','2026-10-09 18:57:22'),
(17,'default','updated','page',2,'updated','App\\Models\\User',2,'[]','[]','2026-10-09 18:57:52','2026-10-09 18:57:52'),
(18,'security','Pengguna berhasil masuk ke sistem',NULL,NULL,NULL,'App\\Models\\User',2,'[]','{\"ip_address\":\"127.0.0.1\",\"browser\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/154.0.0.0 Safari\\/537.36\"}','2026-10-09 21:26:11','2026-10-09 21:26:11'),
(19,'article_updates','created','post',1,'created','App\\Models\\User',2,'{\"attributes\":{\"title\":{\"en\":\"Ten Kilometer\",\"id\":\"Sepuluh Kilometer\"},\"content\":{\"blocks\":{\"blk_6ac95fa401715\":{\"id\":\"blk_6ac95fa401715\",\"type\":\"heading\",\"data\":{\"text\":{\"en\":\"\",\"id\":\"\"},\"level\":\"h2\",\"margin_bottom\":\"mb-4 md:mb-6\"}}},\"order\":[\"blk_6ac95fa401715\"],\"settings\":{\"toc_position\":\"right\"}},\"status\":\"draft\"}}','[]','2026-10-09 21:46:34','2026-10-09 21:46:34'),
(20,'default','updated','page',3,'updated','App\\Models\\User',2,'[]','[]','2026-10-09 22:41:27','2026-10-09 22:41:27'),
(21,'default','updated','page',3,'updated','App\\Models\\User',2,'[]','[]','2026-10-09 22:44:03','2026-10-09 22:44:03'),
(22,'default','updated','page',3,'updated','App\\Models\\User',2,'[]','[]','2026-10-09 22:44:42','2026-10-09 22:44:42'),
(23,'default','updated','page',3,'updated','App\\Models\\User',2,'[]','[]','2026-10-09 22:44:56','2026-10-09 22:44:56'),
(24,'default','updated','page',3,'updated','App\\Models\\User',2,'[]','[]','2026-10-09 22:47:01','2026-10-09 22:47:01'),
(25,'default','updated','page',4,'updated','App\\Models\\User',2,'[]','[]','2026-10-09 22:47:14','2026-10-09 22:47:14'),
(26,'default','updated','page',5,'updated','App\\Models\\User',2,'[]','[]','2026-10-09 22:47:26','2026-10-09 22:47:26'),
(27,'default','updated','page',7,'updated','App\\Models\\User',2,'[]','[]','2026-10-09 22:47:33','2026-10-09 22:47:33'),
(28,'default','updated','page',8,'updated','App\\Models\\User',2,'[]','[]','2026-10-09 22:47:40','2026-10-09 22:47:40'),
(29,'default','updated','page',9,'updated','App\\Models\\User',2,'[]','[]','2026-10-09 22:47:52','2026-10-09 22:47:52'),
(30,'default','updated','page',10,'updated','App\\Models\\User',2,'[]','[]','2026-10-09 22:47:59','2026-10-09 22:47:59'),
(31,'default','updated','page',11,'updated','App\\Models\\User',2,'[]','[]','2026-10-09 22:48:05','2026-10-09 22:48:05'),
(32,'default','updated','page',12,'updated','App\\Models\\User',2,'[]','[]','2026-10-09 22:48:14','2026-10-09 22:48:14'),
(36,'article_updates','updated_general','post',1,'updated','App\\Models\\User',2,'{\"attributes\":{\"content\":{\"blocks\":{\"blk_6ac95fa401715\":{\"id\":\"blk_6ac95fa401715\",\"type\":\"heading\",\"data\":{\"text\":{\"en\":\"Mama Bathsheba\'s struggle to ensure all her children were <span style=\\\"color: rgb(235, 204, 38);\\\"><em><strong class=\\\"font-bold\\\">immunized<\\/strong><\\/em><\\/span>.\",\"id\":\"perjuangan mama bathsheba memastikan semua anaknya diimunisasi\"},\"level\":\"h2\",\"margin_bottom\":\"mb-4 md:mb-6\"}},\"blk_mG6gvjLe\":{\"id\":\"blk_mG6gvjLe\",\"type\":\"paragraph\",\"data\":{\"text\":{\"en\":\"<p>Lorem ipsum dolor sit amet consectetur adipiscing elit. Quisque faucibus ex sapien vitae pellentesque sem placerat. In id cursus mi pretium tellus duis convallis. Tempus leo eu aenean sed diam urna tempor. Pulvinar vivamus fringilla lacus nec metus bibendum egestas. Iaculis massa nisl malesuada lacinia integer nunc posuere. Ut hendrerit semper vel class aptent taciti sociosqu. Ad litora torquent per conubia nostra inceptos himenaeos.<\\/p><p>Lorem ipsum dolor sit amet consectetur adipiscing elit. Quisque faucibus ex sapien vitae pellentesque sem placerat. In id cursus mi pretium tellus duis convallis. Tempus leo eu aenean sed diam urna tempor. Pulvinar vivamus fringilla lacus nec metus bibendum egestas. Iaculis massa nisl malesuada lacinia integer nunc posuere. Ut hendrerit semper vel class aptent taciti sociosqu. Ad litora torquent per conubia nostra inceptos himenaeos.<\\/p>\",\"id\":\"<p>Lorem ipsum dolor sit amet consectetur adipiscing elit. Quisque faucibus ex sapien vitae pellentesque sem placerat. In id cursus mi pretium tellus duis convallis. Tempus leo eu aenean sed diam urna tempor. Pulvinar vivamus fringilla lacus nec metus bibendum egestas. Iaculis massa nisl malesuada lacinia integer nunc posuere. Ut hendrerit semper vel class aptent taciti sociosqu. Ad litora torquent per conubia nostra inceptos himenaeos.<\\/p><p>Lorem ipsum dolor sit amet consectetur adipiscing elit. Quisque faucibus ex sapien vitae pellentesque sem placerat. In id cursus mi pretium tellus duis convallis. Tempus leo eu aenean sed diam urna tempor. Pulvinar vivamus fringilla lacus nec metus bibendum egestas. Iaculis massa nisl malesuada lacinia integer nunc posuere. Ut hendrerit semper vel class aptent taciti sociosqu. Ad litora torquent per conubia nostra inceptos himenaeos.<\\/p>\"},\"margin_bottom\":\"mb-4 md:mb-6\"}}},\"order\":[\"blk_6ac95fa401715\",\"blk_mG6gvjLe\"],\"settings\":{\"toc_position\":\"right\"}}},\"old\":{\"content\":{\"blocks\":{\"blk_6ac95fa401715\":{\"id\":\"blk_6ac95fa401715\",\"type\":\"heading\",\"data\":{\"text\":{\"en\":\"\",\"id\":\"\"},\"level\":\"h2\",\"margin_bottom\":\"mb-4 md:mb-6\"}}},\"order\":[\"blk_6ac95fa401715\"],\"settings\":{\"toc_position\":\"right\"}}}}','[]','2026-10-09 22:58:34','2026-10-09 22:58:34'),
(37,'article_updates','status_review','post',1,'updated','App\\Models\\User',2,'{\"attributes\":{\"status\":\"review\"},\"old\":{\"status\":\"draft\"}}','[]','2026-10-09 22:58:46','2026-10-09 22:58:46'),
(38,'article_updates','status_published','post',1,'updated','App\\Models\\User',2,'{\"attributes\":{\"status\":\"published\"},\"old\":{\"status\":\"review\"}}','[]','2026-10-09 23:01:59','2026-10-09 23:01:59'),
(39,'default','updated','page',3,'updated','App\\Models\\User',2,'[]','[]','2026-10-09 23:10:21','2026-10-09 23:10:21'),
(40,'media_manager','Membuat direktori folder baru: program-hiv','App\\Models\\MediaFolder',1,'created','App\\Models\\User',2,'[]','[]','2026-10-09 23:12:01','2026-10-09 23:12:01'),
(41,'media_manager','Membuat direktori folder baru: program-imunisasi','App\\Models\\MediaFolder',2,'created','App\\Models\\User',2,'[]','[]','2026-10-09 23:12:06','2026-10-09 23:12:06'),
(42,'media_manager','Membuat direktori folder baru: program-kia','App\\Models\\MediaFolder',3,'created','App\\Models\\User',2,'[]','[]','2026-10-09 23:12:13','2026-10-09 23:12:13'),
(43,'media_manager','Membuat direktori folder baru: program malaria','App\\Models\\MediaFolder',4,'created','App\\Models\\User',2,'[]','[]','2026-10-09 23:12:18','2026-10-09 23:12:18'),
(44,'media_manager','Mengubah nama folder dari \'program malaria\' menjadi \'program-malaria\'','App\\Models\\MediaFolder',4,'updated','App\\Models\\User',2,'[]','[]','2026-10-09 23:12:25','2026-10-09 23:12:25'),
(45,'media_manager','Membuat direktori folder baru: program-tbc','App\\Models\\MediaFolder',5,'created','App\\Models\\User',2,'[]','[]','2026-10-09 23:12:38','2026-10-09 23:12:38'),
(46,'media_manager','Mengunggah file baru: 001.png','App\\Models\\Media',1,'created','App\\Models\\User',2,'[]','[]','2026-10-09 23:12:48','2026-10-09 23:12:48'),
(47,'media_manager','Mengunggah file baru: 002.png','App\\Models\\Media',2,'created','App\\Models\\User',2,'[]','[]','2026-10-09 23:12:48','2026-10-09 23:12:48'),
(48,'media_manager','Mengunggah file baru: 003.png','App\\Models\\Media',3,'created','App\\Models\\User',2,'[]','[]','2026-10-09 23:12:48','2026-10-09 23:12:48'),
(49,'media_manager','Mengunggah file baru: 004.png','App\\Models\\Media',4,'created','App\\Models\\User',2,'[]','[]','2026-10-09 23:12:48','2026-10-09 23:12:48'),
(50,'media_manager','Mengunggah file baru: 005.png','App\\Models\\Media',5,'created','App\\Models\\User',2,'[]','[]','2026-10-09 23:12:48','2026-10-09 23:12:48'),
(51,'media_manager','Mengunggah file baru: 006.png','App\\Models\\Media',6,'created','App\\Models\\User',2,'[]','[]','2026-10-09 23:12:48','2026-10-09 23:12:48'),
(52,'media_manager','Mengunggah file baru: 007.png','App\\Models\\Media',7,'created','App\\Models\\User',2,'[]','[]','2026-10-09 23:12:48','2026-10-09 23:12:48'),
(53,'media_manager','Mengunggah file baru: 008.png','App\\Models\\Media',8,'created','App\\Models\\User',2,'[]','[]','2026-10-09 23:12:48','2026-10-09 23:12:48'),
(54,'media_manager','Mengunggah file baru: 001.jpg','App\\Models\\Media',9,'created','App\\Models\\User',2,'[]','[]','2026-10-09 23:12:57','2026-10-09 23:12:57'),
(55,'media_manager','Mengunggah file baru: 002.jpg','App\\Models\\Media',10,'created','App\\Models\\User',2,'[]','[]','2026-10-09 23:12:57','2026-10-09 23:12:57'),
(56,'media_manager','Mengunggah file baru: 003.jpg','App\\Models\\Media',11,'created','App\\Models\\User',2,'[]','[]','2026-10-09 23:12:57','2026-10-09 23:12:57'),
(57,'media_manager','Mengunggah file baru: 004.jpg','App\\Models\\Media',12,'created','App\\Models\\User',2,'[]','[]','2026-10-09 23:12:57','2026-10-09 23:12:57'),
(58,'media_manager','Mengunggah file baru: 005.jpg','App\\Models\\Media',13,'created','App\\Models\\User',2,'[]','[]','2026-10-09 23:12:57','2026-10-09 23:12:57'),
(59,'media_manager','Mengunggah file baru: 006.jpg','App\\Models\\Media',14,'created','App\\Models\\User',2,'[]','[]','2026-10-09 23:12:57','2026-10-09 23:12:57'),
(60,'media_manager','Mengunggah file baru: 007.jpg','App\\Models\\Media',15,'created','App\\Models\\User',2,'[]','[]','2026-10-09 23:12:57','2026-10-09 23:12:57'),
(61,'media_manager','Mengunggah file baru: 001.png','App\\Models\\Media',16,'created','App\\Models\\User',2,'[]','[]','2026-10-09 23:13:03','2026-10-09 23:13:03'),
(62,'media_manager','Mengunggah file baru: 002.png','App\\Models\\Media',17,'created','App\\Models\\User',2,'[]','[]','2026-10-09 23:13:03','2026-10-09 23:13:03'),
(63,'media_manager','Mengunggah file baru: 003.png','App\\Models\\Media',18,'created','App\\Models\\User',2,'[]','[]','2026-10-09 23:13:03','2026-10-09 23:13:03'),
(64,'media_manager','Mengunggah file baru: 004.png','App\\Models\\Media',19,'created','App\\Models\\User',2,'[]','[]','2026-10-09 23:13:03','2026-10-09 23:13:03'),
(65,'media_manager','Mengunggah file baru: 005.png','App\\Models\\Media',20,'created','App\\Models\\User',2,'[]','[]','2026-10-09 23:13:03','2026-10-09 23:13:03'),
(66,'media_manager','Mengunggah file baru: 004.png','App\\Models\\Media',21,'created','App\\Models\\User',2,'[]','[]','2026-10-09 23:13:10','2026-10-09 23:13:10'),
(67,'media_manager','Mengunggah file baru: 005.jpg','App\\Models\\Media',22,'created','App\\Models\\User',2,'[]','[]','2026-10-09 23:13:10','2026-10-09 23:13:10'),
(68,'media_manager','Mengunggah file baru: 006.jpg','App\\Models\\Media',23,'created','App\\Models\\User',2,'[]','[]','2026-10-09 23:13:10','2026-10-09 23:13:10'),
(69,'media_manager','Mengunggah file baru: 007.jpg','App\\Models\\Media',24,'created','App\\Models\\User',2,'[]','[]','2026-10-09 23:13:10','2026-10-09 23:13:10'),
(70,'media_manager','Mengunggah file baru: 008.jpg','App\\Models\\Media',25,'created','App\\Models\\User',2,'[]','[]','2026-10-09 23:13:10','2026-10-09 23:13:10'),
(71,'media_manager','Mengunggah file baru: 001.png','App\\Models\\Media',26,'created','App\\Models\\User',2,'[]','[]','2026-10-09 23:13:10','2026-10-09 23:13:10'),
(72,'media_manager','Mengunggah file baru: 002.png','App\\Models\\Media',27,'created','App\\Models\\User',2,'[]','[]','2026-10-09 23:13:10','2026-10-09 23:13:10'),
(73,'media_manager','Mengunggah file baru: 003.png','App\\Models\\Media',28,'created','App\\Models\\User',2,'[]','[]','2026-10-09 23:13:10','2026-10-09 23:13:10'),
(74,'media_manager','Mengunggah file baru: TBC 06.png','App\\Models\\Media',29,'created','App\\Models\\User',2,'[]','[]','2026-10-09 23:13:18','2026-10-09 23:13:18'),
(75,'media_manager','Mengunggah file baru: TBC 01.png','App\\Models\\Media',30,'created','App\\Models\\User',2,'[]','[]','2026-10-09 23:13:18','2026-10-09 23:13:18'),
(76,'media_manager','Mengunggah file baru: TBC 02.png','App\\Models\\Media',31,'created','App\\Models\\User',2,'[]','[]','2026-10-09 23:13:18','2026-10-09 23:13:18'),
(77,'media_manager','Mengunggah file baru: TBC 03.png','App\\Models\\Media',32,'created','App\\Models\\User',2,'[]','[]','2026-10-09 23:13:18','2026-10-09 23:13:18'),
(78,'media_manager','Mengunggah file baru: TBC 04.png','App\\Models\\Media',33,'created','App\\Models\\User',2,'[]','[]','2026-10-09 23:13:18','2026-10-09 23:13:18'),
(79,'media_manager','Mengunggah file baru: TBC 05.png','App\\Models\\Media',34,'created','App\\Models\\User',2,'[]','[]','2026-10-09 23:13:18','2026-10-09 23:13:18'),
(80,'default','updated','page',4,'updated','App\\Models\\User',2,'[]','[]','2026-10-09 23:17:56','2026-10-09 23:17:56'),
(81,'default','updated','page',4,'updated','App\\Models\\User',2,'[]','[]','2026-10-09 23:19:09','2026-10-09 23:19:09'),
(82,'default','updated','page',5,'updated','App\\Models\\User',2,'[]','[]','2026-10-09 23:31:24','2026-10-09 23:31:24'),
(83,'default','updated','page',5,'updated','App\\Models\\User',2,'[]','[]','2026-10-09 23:37:28','2026-10-09 23:37:28'),
(84,'default','updated','page',5,'updated','App\\Models\\User',2,'[]','[]','2026-10-09 23:40:41','2026-10-09 23:40:41'),
(85,'default','updated','page',5,'updated','App\\Models\\User',2,'[]','[]','2026-10-09 23:44:48','2026-10-09 23:44:48'),
(86,'article_updates','created','post',2,'created','App\\Models\\User',2,'{\"attributes\":{\"title\":{\"en\":\"extramiles\",\"id\":\"extramiles\"},\"content\":{\"blocks\":{\"blk_6ac97cfb294a9\":{\"id\":\"blk_6ac97cfb294a9\",\"type\":\"heading\",\"data\":{\"text\":{\"en\":\"\",\"id\":\"\"},\"level\":\"h2\",\"margin_bottom\":\"mb-4 md:mb-6\"}}},\"order\":[\"blk_6ac97cfb294a9\"],\"settings\":{\"toc_position\":\"right\"}},\"status\":\"draft\"}}','[]','2026-10-09 23:48:41','2026-10-09 23:48:41'),
(87,'article_updates','status_review','post',2,'updated','App\\Models\\User',2,'{\"attributes\":{\"status\":\"review\"},\"old\":{\"status\":\"draft\"}}','[]','2026-10-09 23:49:11','2026-10-09 23:49:11'),
(88,'media_manager','Membuat direktori folder baru: penelitian-a','App\\Models\\MediaFolder',6,'created','App\\Models\\User',2,'[]','[]','2026-10-10 00:26:26','2026-10-10 00:26:26'),
(89,'security','Pengguna berhasil masuk ke sistem',NULL,NULL,NULL,'App\\Models\\User',2,'[]','{\"ip_address\":\"127.0.0.1\",\"browser\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/154.0.0.0 Safari\\/537.36\"}','2026-10-10 03:53:29','2026-10-10 03:53:29'),
(90,'security','Pengguna berhasil masuk ke sistem',NULL,NULL,NULL,'App\\Models\\User',2,'[]','{\"ip_address\":\"127.0.0.1\",\"browser\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/154.0.0.0 Safari\\/537.36\"}','2026-10-10 03:53:29','2026-10-10 03:53:29'),
(91,'default','updated','page',3,'updated','App\\Models\\User',2,'[]','[]','2026-10-10 04:04:44','2026-10-10 04:04:44'),
(92,'default','updated','page',3,'updated','App\\Models\\User',2,'[]','[]','2026-10-10 04:06:00','2026-10-10 04:06:00'),
(93,'security','Pengguna berhasil masuk ke sistem',NULL,NULL,NULL,'App\\Models\\User',2,'[]','{\"ip_address\":\"127.0.0.1\",\"browser\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/154.0.0.0 Safari\\/537.36\"}','2026-10-10 08:47:46','2026-10-10 08:47:46'),
(94,'security','Pengguna berhasil masuk ke sistem',NULL,NULL,NULL,'App\\Models\\User',2,'[]','{\"ip_address\":\"127.0.0.1\",\"browser\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/154.0.0.0 Safari\\/537.36\"}','2026-10-10 08:47:46','2026-10-10 08:47:46'),
(95,'security','Pengguna berhasil masuk ke sistem',NULL,NULL,NULL,'App\\Models\\User',4,'[]','{\"ip_address\":\"127.0.0.1\",\"browser\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/154.0.0.0 Safari\\/537.36\"}','2026-10-10 10:12:53','2026-10-10 10:12:53'),
(96,'default','updated','page',3,'updated','App\\Models\\User',4,'[]','[]','2026-10-10 10:13:55','2026-10-10 10:13:55'),
(97,'security','Pengguna berhasil masuk ke sistem',NULL,NULL,NULL,'App\\Models\\User',2,'[]','{\"ip_address\":\"127.0.0.1\",\"browser\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/154.0.0.0 Safari\\/537.36\"}','2026-10-11 03:15:56','2026-10-11 03:15:56'),
(98,'security','Pengguna berhasil masuk ke sistem',NULL,NULL,NULL,'App\\Models\\User',2,'[]','{\"ip_address\":\"127.0.0.1\",\"browser\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/154.0.0.0 Safari\\/537.36\"}','2026-10-11 10:56:45','2026-10-11 10:56:45'),
(99,'security','Pengguna berhasil masuk ke sistem',NULL,NULL,NULL,'App\\Models\\User',2,'[]','{\"ip_address\":\"127.0.0.1\",\"browser\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/154.0.0.0 Safari\\/537.36\"}','2026-10-11 10:56:45','2026-10-11 10:56:45');
/*!40000 ALTER TABLE `activity_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `article_reviews`
--

DROP TABLE IF EXISTS `article_reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `article_reviews` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `post_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `action` varchar(20) NOT NULL,
  `from_status` varchar(20) NOT NULL,
  `to_status` varchar(20) NOT NULL,
  `note` text DEFAULT NULL,
  `scheduled_for` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `article_reviews_post_id_id_index` (`post_id`,`id`),
  KEY `article_reviews_post_id_index` (`post_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `article_reviews`
--

LOCK TABLES `article_reviews` WRITE;
/*!40000 ALTER TABLE `article_reviews` DISABLE KEYS */;
INSERT INTO `article_reviews` VALUES
(1,1,2,'submit','draft','review',NULL,NULL,'2026-10-09 22:58:46'),
(2,1,2,'publish','review','published',NULL,NULL,'2026-10-09 23:01:59'),
(3,2,2,'submit','draft','review',NULL,NULL,'2026-10-09 23:49:11');
/*!40000 ALTER TABLE `article_reviews` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` bigint(20) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
INSERT INTO `cache` VALUES
('cms-ysbh-cache-content-preview:2a78305d84d705ecd2d454cbf6b28ff8bbc25cfc','a:8:{s:4:\"type\";s:7:\"article\";s:4:\"user\";s:1:\"2\";s:3:\"rev\";i:1;s:6:\"blocks\";a:1:{s:17:\"blk_6ac97cfb294a9\";a:3:{s:2:\"id\";s:17:\"blk_6ac97cfb294a9\";s:4:\"type\";s:7:\"heading\";s:4:\"data\";a:3:{s:4:\"text\";a:2:{s:2:\"en\";s:0:\"\";s:2:\"id\";s:0:\"\";}s:5:\"level\";s:2:\"h2\";s:13:\"margin_bottom\";s:12:\"mb-4 md:mb-6\";}}}s:5:\"order\";a:1:{i:0;s:17:\"blk_6ac97cfb294a9\";}s:8:\"settings\";a:1:{s:12:\"toc_position\";s:5:\"right\";}s:6:\"titles\";a:2:{s:2:\"en\";s:10:\"extramiles\";s:2:\"id\";s:10:\"extramiles\";}s:7:\"locales\";a:2:{i:0;s:2:\"en\";i:1;s:2:\"id\";}}',1791719550),
('cms-ysbh-cache-content-preview:6131b91a8b303413b5a28ccf70b7500b93c1782b','a:8:{s:4:\"type\";s:7:\"article\";s:4:\"user\";s:1:\"2\";s:3:\"rev\";i:1;s:6:\"blocks\";a:1:{s:17:\"blk_6ac97cfb294a9\";a:3:{s:2:\"id\";s:17:\"blk_6ac97cfb294a9\";s:4:\"type\";s:7:\"heading\";s:4:\"data\";a:3:{s:4:\"text\";a:2:{s:2:\"en\";s:0:\"\";s:2:\"id\";s:0:\"\";}s:5:\"level\";s:2:\"h2\";s:13:\"margin_bottom\";s:12:\"mb-4 md:mb-6\";}}}s:5:\"order\";a:1:{i:0;s:17:\"blk_6ac97cfb294a9\";}s:8:\"settings\";a:1:{s:12:\"toc_position\";s:5:\"right\";}s:6:\"titles\";a:2:{s:2:\"en\";s:10:\"extramiles\";s:2:\"id\";s:10:\"extramiles\";}s:7:\"locales\";a:2:{i:0;s:2:\"en\";i:1;s:2:\"id\";}}',1791721053),
('cms-ysbh-cache-content-preview:993d029c58d2d90e3427acfdd764d62cff1eac70','a:8:{s:4:\"type\";s:7:\"article\";s:4:\"user\";s:1:\"2\";s:3:\"rev\";i:1;s:6:\"blocks\";a:1:{s:17:\"blk_6ac97cfb294a9\";a:3:{s:2:\"id\";s:17:\"blk_6ac97cfb294a9\";s:4:\"type\";s:7:\"heading\";s:4:\"data\";a:3:{s:4:\"text\";a:2:{s:2:\"en\";s:0:\"\";s:2:\"id\";s:0:\"\";}s:5:\"level\";s:2:\"h2\";s:13:\"margin_bottom\";s:12:\"mb-4 md:mb-6\";}}}s:5:\"order\";a:1:{i:0;s:17:\"blk_6ac97cfb294a9\";}s:8:\"settings\";a:1:{s:12:\"toc_position\";s:5:\"right\";}s:6:\"titles\";a:2:{s:2:\"en\";s:10:\"extramiles\";s:2:\"id\";s:10:\"extramiles\";}s:7:\"locales\";a:2:{i:0;s:2:\"en\";i:1;s:2:\"id\";}}',1791721064),
('cms-ysbh-cache-content-preview:caf1ed57fbe3150c0377d5904d31894bb3d7072a','a:8:{s:4:\"type\";s:7:\"article\";s:4:\"user\";s:1:\"2\";s:3:\"rev\";i:1;s:6:\"blocks\";a:1:{s:17:\"blk_6ac97cfb294a9\";a:3:{s:2:\"id\";s:17:\"blk_6ac97cfb294a9\";s:4:\"type\";s:7:\"heading\";s:4:\"data\";a:3:{s:4:\"text\";a:2:{s:2:\"en\";s:0:\"\";s:2:\"id\";s:0:\"\";}s:5:\"level\";s:2:\"h2\";s:13:\"margin_bottom\";s:12:\"mb-4 md:mb-6\";}}}s:5:\"order\";a:1:{i:0;s:17:\"blk_6ac97cfb294a9\";}s:8:\"settings\";a:1:{s:12:\"toc_position\";s:5:\"right\";}s:6:\"titles\";a:2:{s:2:\"en\";s:10:\"extramiles\";s:2:\"id\";s:10:\"extramiles\";}s:7:\"locales\";a:2:{i:0;s:2:\"en\";i:1;s:2:\"id\";}}',1791721430),
('cms-ysbh-cache-content-preview:f6c49c9d3f4adc7f42c85b9c9f7fc6475bb5d159','a:8:{s:4:\"type\";s:7:\"article\";s:4:\"user\";s:1:\"2\";s:3:\"rev\";i:25;s:6:\"blocks\";a:16:{s:17:\"blk_6ac97cfb294a9\";a:3:{s:2:\"id\";s:17:\"blk_6ac97cfb294a9\";s:4:\"type\";s:7:\"heading\";s:4:\"data\";a:3:{s:4:\"text\";a:2:{s:2:\"en\";s:0:\"\";s:2:\"id\";s:0:\"\";}s:5:\"level\";s:2:\"h2\";s:13:\"margin_bottom\";s:12:\"mb-4 md:mb-6\";}}s:12:\"blk_6uhbjA49\";a:3:{s:2:\"id\";s:12:\"blk_6uhbjA49\";s:4:\"type\";s:7:\"heading\";s:4:\"data\";a:3:{s:4:\"text\";a:2:{s:2:\"en\";s:0:\"\";s:2:\"id\";s:0:\"\";}s:5:\"level\";s:2:\"h2\";s:13:\"margin_bottom\";s:12:\"mb-4 md:mb-6\";}}s:12:\"blk_iBOaSEjm\";a:3:{s:2:\"id\";s:12:\"blk_iBOaSEjm\";s:4:\"type\";s:9:\"paragraph\";s:4:\"data\";a:2:{s:4:\"text\";a:2:{s:2:\"en\";s:0:\"\";s:2:\"id\";s:0:\"\";}s:13:\"margin_bottom\";s:12:\"mb-4 md:mb-6\";}}s:12:\"blk_4OgGbWKQ\";a:3:{s:2:\"id\";s:12:\"blk_4OgGbWKQ\";s:4:\"type\";s:7:\"eyebrow\";s:4:\"data\";a:4:{s:4:\"text\";a:2:{s:2:\"en\";s:0:\"\";s:2:\"id\";s:0:\"\";}s:4:\"icon\";s:9:\"newspaper\";s:5:\"color\";s:7:\"#E42326\";s:13:\"margin_bottom\";s:12:\"mb-4 md:mb-6\";}}s:12:\"blk_Dd3lshZB\";a:3:{s:2:\"id\";s:12:\"blk_Dd3lshZB\";s:4:\"type\";s:5:\"image\";s:4:\"data\";a:10:{s:3:\"url\";s:0:\"\";s:8:\"alt_text\";s:0:\"\";s:13:\"margin_bottom\";s:12:\"mb-4 md:mb-6\";s:7:\"caption\";a:2:{s:2:\"en\";s:0:\"\";s:2:\"id\";s:0:\"\";}s:5:\"width\";s:6:\"w-full\";s:5:\"align\";s:7:\"mx-auto\";s:6:\"radius\";s:12:\"rounded-none\";s:10:\"max_height\";s:10:\"max-h-none\";s:10:\"object_fit\";s:12:\"object-cover\";s:7:\"space_y\";s:5:\"gap-3\";}}s:12:\"blk_Lyq0nGPn\";a:3:{s:2:\"id\";s:12:\"blk_Lyq0nGPn\";s:4:\"type\";s:14:\"button-builder\";s:4:\"data\";a:3:{s:5:\"align\";s:4:\"left\";s:12:\"stack_mobile\";b:1;s:7:\"buttons\";a:1:{i:0;a:8:{s:2:\"id\";s:12:\"itm_c46141bb\";s:5:\"label\";a:2:{s:2:\"en\";s:0:\"\";s:2:\"id\";s:0:\"\";}s:4:\"link\";a:6:{s:4:\"kind\";s:3:\"url\";s:3:\"ref\";s:0:\"\";s:9:\"ref_label\";s:0:\"\";s:8:\"media_id\";N;s:3:\"url\";s:0:\"\";s:7:\"new_tab\";b:0;}s:7:\"variant\";s:5:\"solid\";s:5:\"color\";s:7:\"foresty\";s:4:\"size\";s:2:\"md\";s:4:\"icon\";s:0:\"\";s:13:\"icon_position\";s:4:\"left\";}}}}s:12:\"blk_dTCAbVxB\";a:3:{s:2:\"id\";s:12:\"blk_dTCAbVxB\";s:4:\"type\";s:12:\"card-builder\";s:4:\"data\";a:2:{s:4:\"grid\";a:2:{s:4:\"cols\";i:1;s:13:\"margin_bottom\";s:12:\"mb-4 md:mb-6\";}s:5:\"cards\";a:0:{}}}s:12:\"blk_kOT94gJm\";a:3:{s:2:\"id\";s:12:\"blk_kOT94gJm\";s:4:\"type\";s:17:\"accordion-builder\";s:4:\"data\";a:7:{s:5:\"style\";s:5:\"boxed\";s:5:\"color\";s:7:\"foresty\";s:6:\"marker\";s:7:\"chevron\";s:10:\"first_open\";b:0;s:14:\"allow_multiple\";b:0;s:6:\"schema\";b:0;s:5:\"items\";a:1:{i:0;a:3:{s:2:\"id\";s:12:\"itm_08eeec6a\";s:8:\"question\";a:2:{s:2:\"en\";s:0:\"\";s:2:\"id\";s:0:\"\";}s:6:\"answer\";a:2:{s:2:\"en\";s:0:\"\";s:2:\"id\";s:0:\"\";}}}}}s:12:\"blk_aQAEyngU\";a:3:{s:2:\"id\";s:12:\"blk_aQAEyngU\";s:4:\"type\";s:17:\"downloads-builder\";s:4:\"data\";a:7:{s:6:\"layout\";s:4:\"list\";s:8:\"group_by\";s:4:\"none\";s:4:\"sort\";s:6:\"manual\";s:5:\"color\";s:7:\"foresty\";s:9:\"show_meta\";b:1;s:7:\"new_tab\";b:1;s:5:\"items\";a:1:{i:0;a:5:{s:2:\"id\";s:12:\"itm_a580c4b5\";s:5:\"title\";a:2:{s:2:\"en\";s:0:\"\";s:2:\"id\";s:0:\"\";}s:4:\"year\";s:0:\"\";s:8:\"category\";a:2:{s:2:\"en\";s:0:\"\";s:2:\"id\";s:0:\"\";}s:4:\"file\";a:2:{s:8:\"media_id\";N;s:3:\"url\";s:0:\"\";}}}}}s:12:\"blk_cfAYdlLQ\";a:3:{s:2:\"id\";s:12:\"blk_cfAYdlLQ\";s:4:\"type\";s:15:\"callout-builder\";s:4:\"data\";a:7:{s:4:\"tone\";s:4:\"info\";s:5:\"style\";s:4:\"soft\";s:9:\"show_icon\";b:1;s:4:\"icon\";s:0:\"\";s:5:\"title\";a:2:{s:2:\"en\";s:0:\"\";s:2:\"id\";s:0:\"\";}s:4:\"body\";a:2:{s:2:\"en\";s:0:\"\";s:2:\"id\";s:0:\"\";}s:6:\"action\";a:2:{s:5:\"label\";a:2:{s:2:\"en\";s:0:\"\";s:2:\"id\";s:0:\"\";}s:4:\"link\";a:6:{s:4:\"kind\";s:3:\"url\";s:3:\"ref\";s:0:\"\";s:9:\"ref_label\";s:0:\"\";s:8:\"media_id\";N;s:3:\"url\";s:0:\"\";s:7:\"new_tab\";b:0;}}}}s:12:\"blk_dhb4rhUT\";a:3:{s:2:\"id\";s:12:\"blk_dhb4rhUT\";s:4:\"type\";s:15:\"gallery-builder\";s:4:\"data\";a:8:{s:4:\"mode\";s:6:\"photos\";s:6:\"layout\";s:4:\"grid\";s:7:\"columns\";s:1:\"3\";s:5:\"ratio\";s:3:\"4:3\";s:9:\"grayscale\";b:0;s:8:\"lightbox\";b:1;s:8:\"autoplay\";b:0;s:5:\"items\";a:1:{i:0;a:5:{s:2:\"id\";s:12:\"itm_291f66f6\";s:5:\"title\";a:2:{s:2:\"en\";s:0:\"\";s:2:\"id\";s:0:\"\";}s:7:\"caption\";a:2:{s:2:\"en\";s:0:\"\";s:2:\"id\";s:0:\"\";}s:5:\"image\";a:2:{s:8:\"media_id\";N;s:3:\"url\";s:0:\"\";}s:4:\"link\";a:6:{s:4:\"kind\";s:3:\"url\";s:3:\"ref\";s:0:\"\";s:9:\"ref_label\";s:0:\"\";s:8:\"media_id\";N;s:3:\"url\";s:0:\"\";s:7:\"new_tab\";b:0;}}}}}s:12:\"blk_Kd9mTpEX\";a:3:{s:2:\"id\";s:12:\"blk_Kd9mTpEX\";s:4:\"type\";s:23:\"latest-articles-builder\";s:4:\"data\";a:16:{s:5:\"title\";a:2:{s:2:\"en\";s:0:\"\";s:2:\"id\";s:0:\"\";}s:6:\"source\";s:10:\"categories\";s:10:\"categories\";a:2:{i:0;a:2:{s:2:\"id\";i:2;s:5:\"label\";s:13:\"Press Release\";}i:1;a:2:{s:2:\"id\";i:6;s:5:\"label\";s:24:\"Reports & Transparancies\";}}s:14:\"featured_count\";s:1:\"0\";s:6:\"pinned\";a:0:{}s:6:\"mirror\";b:0;s:11:\"list_layout\";s:4:\"cols\";s:7:\"columns\";s:1:\"3\";s:6:\"paging\";s:5:\"pages\";s:5:\"limit\";s:2:\"12\";s:10:\"show_image\";b:1;s:13:\"show_category\";b:1;s:9:\"show_date\";b:1;s:12:\"show_excerpt\";b:1;s:9:\"all_label\";a:2:{s:2:\"en\";s:0:\"\";s:2:\"id\";s:0:\"\";}s:8:\"all_link\";a:6:{s:4:\"kind\";s:3:\"url\";s:3:\"ref\";s:0:\"\";s:9:\"ref_label\";s:0:\"\";s:8:\"media_id\";N;s:3:\"url\";s:0:\"\";s:7:\"new_tab\";b:0;}}}s:12:\"blk_fbRvOQhK\";a:3:{s:2:\"id\";s:12:\"blk_fbRvOQhK\";s:4:\"type\";s:13:\"table-builder\";s:4:\"data\";a:10:{s:7:\"caption\";a:2:{s:2:\"en\";s:0:\"\";s:2:\"id\";s:0:\"\";}s:4:\"note\";a:2:{s:2:\"en\";s:0:\"\";s:2:\"id\";s:0:\"\";}s:6:\"header\";b:1;s:16:\"first_col_header\";b:0;s:7:\"striped\";b:1;s:6:\"border\";s:4:\"rows\";s:7:\"density\";s:6:\"normal\";s:5:\"color\";s:7:\"foresty\";s:7:\"columns\";a:3:{i:0;a:3:{s:2:\"id\";s:12:\"itm_17115995\";s:5:\"label\";a:2:{s:2:\"en\";s:0:\"\";s:2:\"id\";s:0:\"\";}s:5:\"align\";s:4:\"left\";}i:1;a:3:{s:2:\"id\";s:12:\"itm_13a83be1\";s:5:\"label\";a:2:{s:2:\"en\";s:0:\"\";s:2:\"id\";s:0:\"\";}s:5:\"align\";s:4:\"left\";}i:2;a:3:{s:2:\"id\";s:12:\"itm_cf9cb5fe\";s:5:\"label\";a:2:{s:2:\"en\";s:0:\"\";s:2:\"id\";s:0:\"\";}s:5:\"align\";s:4:\"left\";}}s:4:\"rows\";a:2:{i:0;a:2:{s:2:\"id\";s:12:\"itm_c2d9bd12\";s:5:\"cells\";a:3:{i:0;a:2:{s:2:\"en\";s:0:\"\";s:2:\"id\";s:0:\"\";}i:1;a:2:{s:2:\"en\";s:0:\"\";s:2:\"id\";s:0:\"\";}i:2;a:2:{s:2:\"en\";s:0:\"\";s:2:\"id\";s:0:\"\";}}}i:1;a:2:{s:2:\"id\";s:12:\"itm_12a714b7\";s:5:\"cells\";a:3:{i:0;a:2:{s:2:\"en\";s:0:\"\";s:2:\"id\";s:0:\"\";}i:1;a:2:{s:2:\"en\";s:0:\"\";s:2:\"id\";s:0:\"\";}i:2;a:2:{s:2:\"en\";s:0:\"\";s:2:\"id\";s:0:\"\";}}}}}}s:12:\"blk_6gzC58ik\";a:3:{s:2:\"id\";s:12:\"blk_6gzC58ik\";s:4:\"type\";s:13:\"video-builder\";s:4:\"data\";a:6:{s:3:\"url\";s:0:\"\";s:5:\"title\";a:2:{s:2:\"en\";s:0:\"\";s:2:\"id\";s:0:\"\";}s:7:\"caption\";a:2:{s:2:\"en\";s:0:\"\";s:2:\"id\";s:0:\"\";}s:6:\"poster\";a:2:{s:8:\"media_id\";N;s:3:\"url\";s:0:\"\";}s:5:\"ratio\";s:4:\"16:9\";s:9:\"max_width\";s:4:\"full\";}}s:12:\"blk_N23RVOQi\";a:3:{s:2:\"id\";s:12:\"blk_N23RVOQi\";s:4:\"type\";s:10:\"step-group\";s:4:\"data\";a:5:{s:11:\"orientation\";s:8:\"vertical\";s:3:\"gap\";s:5:\"gap-8\";s:10:\"node_color\";s:21:\"bg-foresty text-white\";s:10:\"line_color\";s:13:\"bg-foresty/30\";s:8:\"children\";a:0:{}}}s:12:\"blk_pPVGLQQx\";a:3:{s:2:\"id\";s:12:\"blk_pPVGLQQx\";s:4:\"type\";s:15:\"section-divider\";s:4:\"data\";a:3:{s:10:\"background\";s:8:\"bg-paper\";s:10:\"text_color\";s:13:\"text-charcoal\";s:7:\"padding\";s:14:\"py-16 sm:py-24\";}}}s:5:\"order\";a:16:{i:0;s:17:\"blk_6ac97cfb294a9\";i:1;s:12:\"blk_6uhbjA49\";i:2;s:12:\"blk_iBOaSEjm\";i:3;s:12:\"blk_4OgGbWKQ\";i:4;s:12:\"blk_Dd3lshZB\";i:5;s:12:\"blk_Lyq0nGPn\";i:6;s:12:\"blk_dTCAbVxB\";i:7;s:12:\"blk_kOT94gJm\";i:8;s:12:\"blk_aQAEyngU\";i:9;s:12:\"blk_cfAYdlLQ\";i:10;s:12:\"blk_dhb4rhUT\";i:11;s:12:\"blk_Kd9mTpEX\";i:12;s:12:\"blk_fbRvOQhK\";i:13;s:12:\"blk_6gzC58ik\";i:14;s:12:\"blk_N23RVOQi\";i:15;s:12:\"blk_pPVGLQQx\";}s:8:\"settings\";a:1:{s:12:\"toc_position\";s:5:\"right\";}s:6:\"titles\";a:2:{s:2:\"en\";s:10:\"extramiles\";s:2:\"id\";s:10:\"extramiles\";}s:7:\"locales\";a:2:{i:0;s:2:\"en\";i:1;s:2:\"id\";}}',1791721420);
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` bigint(20) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_locks`
--

LOCK TABLES `cache_locks` WRITE;
/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`name`)),
  `slug` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`slug`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES
(1,'{\"id\":\"Berita\",\"en\":\"News\"}','{\"id\":\"berita\",\"en\":\"news\"}','2026-07-30 23:00:00','2026-10-09 18:54:08'),
(2,'{\"id\":\"Siaran Pers\",\"en\":\"Press Release\"}','{\"id\":\"siaran-pers\",\"en\":\"press-release\"}','2026-07-31 00:00:00','2026-10-09 18:54:08'),
(3,'{\"id\":\"Pengumuman\",\"en\":\"Announcement\"}','{\"id\":\"pengumuman\",\"en\":\"announcement\"}','2026-07-31 00:00:00','2026-10-09 18:54:08'),
(4,'{\"id\":\"Rancangan & Kampanye\",\"en\":\"Program & Campaign\"}','{\"id\":\"rancangan\",\"en\":\"program\"}','2026-07-31 02:00:00','2026-10-09 18:54:08'),
(5,'{\"id\":\"Kisah Inspiratif\",\"en\":\"Inspirational Story\"}','{\"id\":\"cerita\",\"en\":\"stories\"}','2026-07-31 03:00:00','2026-10-09 18:54:08'),
(6,'{\"id\":\"Laporan & Transparansi\",\"en\":\"Reports & Transparancies\"}','{\"id\":\"laporan\",\"en\":\"reports\"}','2026-07-31 04:00:00','2026-10-09 18:54:08'),
(7,'{\"id\":\"Acara & Kegiatan\",\"en\":\"Events\"}','{\"id\":\"kegiatan\",\"en\":\"events\"}','2026-07-31 05:00:00','2026-10-09 18:54:08');
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` varchar(255) NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`),
  KEY `failed_jobs_connection_queue_failed_at_index` (`connection`,`queue`,`failed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_batches`
--

LOCK TABLES `job_batches` WRITE;
/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` smallint(5) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `media`
--

DROP TABLE IF EXISTS `media`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `media` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `folder_id` bigint(20) unsigned DEFAULT NULL,
  `disk` varchar(255) NOT NULL DEFAULT 'public',
  `path` varchar(255) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `alt_text` varchar(255) DEFAULT NULL,
  `mime_type` varchar(255) NOT NULL,
  `size` bigint(20) unsigned NOT NULL,
  `width` int(10) unsigned DEFAULT NULL,
  `height` int(10) unsigned DEFAULT NULL,
  `uploaded_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `media_uploaded_by_foreign` (`uploaded_by`),
  KEY `media_folder_id_index` (`folder_id`),
  CONSTRAINT `media_folder_id_foreign` FOREIGN KEY (`folder_id`) REFERENCES `media_folders` (`id`) ON DELETE SET NULL,
  CONSTRAINT `media_uploaded_by_foreign` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `media`
--

LOCK TABLES `media` WRITE;
/*!40000 ALTER TABLE `media` DISABLE KEYS */;
INSERT INTO `media` VALUES
(1,1,'public','media/2026/10/Cq5WzMWO8kUh3weAWz70ZTV8Zre9T89D3WbXBk4B.png','001.png',NULL,'image/png',712052,NULL,NULL,2,'2026-10-09 23:12:48','2026-10-09 23:12:48',NULL),
(2,1,'public','media/2026/10/ic0p554nJmmtXmjk5IiQ3fxCj2CSHQNMKDQyApu9.png','002.png',NULL,'image/png',1270361,NULL,NULL,2,'2026-10-09 23:12:48','2026-10-09 23:12:48',NULL),
(3,1,'public','media/2026/10/0VPcvYMTKDf7ybRsbLaQ94JLbSIU9dwjsLjIpq1L.png','003.png',NULL,'image/png',369847,NULL,NULL,2,'2026-10-09 23:12:48','2026-10-09 23:12:48',NULL),
(4,1,'public','media/2026/10/VrshZtNFkVuvOXePPnH3ayWpSwZkAIXW1TBqz5wB.png','004.png',NULL,'image/png',454611,NULL,NULL,2,'2026-10-09 23:12:48','2026-10-09 23:12:48',NULL),
(5,1,'public','media/2026/10/gSQ1fxs2SGIFv65AIGIp2tBgmdLaJQtxvYJJSEv6.png','005.png',NULL,'image/png',351280,NULL,NULL,2,'2026-10-09 23:12:48','2026-10-09 23:12:48',NULL),
(6,1,'public','media/2026/10/swKd7OzPIhDcPxwO3Sz1SAKdHmWBOjK3pPVvQaOv.png','006.png',NULL,'image/png',223063,NULL,NULL,2,'2026-10-09 23:12:48','2026-10-09 23:12:48',NULL),
(7,1,'public','media/2026/10/GwVINNXP8kYCL4Xybx77h6AmBEXNqh23PdXBpzxW.png','007.png',NULL,'image/png',678145,NULL,NULL,2,'2026-10-09 23:12:48','2026-10-09 23:12:48',NULL),
(8,1,'public','media/2026/10/pYVSnYjChQgG7Dj1HFG0k7p4xAgU8vTKAr5qO7m7.png','008.png',NULL,'image/png',310350,NULL,NULL,2,'2026-10-09 23:12:48','2026-10-09 23:12:48',NULL),
(9,2,'public','media/2026/10/oK0kwO9hJF09FeTyjFomtJ8noxBCdVmmVQ1lN9tg.jpg','001.jpg',NULL,'image/jpeg',286806,NULL,NULL,2,'2026-10-09 23:12:57','2026-10-09 23:12:57',NULL),
(10,2,'public','media/2026/10/LVBJbcDuVAtBotkRobtzrSvUSHFoWkpZwEnOhem5.jpg','002.jpg',NULL,'image/jpeg',29750,NULL,NULL,2,'2026-10-09 23:12:57','2026-10-09 23:12:57',NULL),
(11,2,'public','media/2026/10/vVi1b4DTyFImtxp8NHTTeVOH4sdv9MkaRuOLbPO0.jpg','003.jpg',NULL,'image/jpeg',26850,NULL,NULL,2,'2026-10-09 23:12:57','2026-10-09 23:12:57',NULL),
(12,2,'public','media/2026/10/uwdiEmEmqmpPXlG3g5dAYMHn3DmKEpXRR0kc3fAo.jpg','004.jpg',NULL,'image/jpeg',23371,NULL,NULL,2,'2026-10-09 23:12:57','2026-10-09 23:12:57',NULL),
(13,2,'public','media/2026/10/nK3PbJ3C1w0f2eRh27LaSEDKv9a9PrYSJ03Na5pq.jpg','005.jpg',NULL,'image/jpeg',81945,NULL,NULL,2,'2026-10-09 23:12:57','2026-10-09 23:12:57',NULL),
(14,2,'public','media/2026/10/m3tXP67ADGXgBDGVyDWSmfQ0ejJItxRYFhaVZc9M.jpg','006.jpg',NULL,'image/jpeg',110466,NULL,NULL,2,'2026-10-09 23:12:57','2026-10-09 23:12:57',NULL),
(15,2,'public','media/2026/10/byMxsPskgCHZcmBh3VCs6lOt7asWaXpMiyWugnvX.jpg','007.jpg',NULL,'image/jpeg',59701,NULL,NULL,2,'2026-10-09 23:12:57','2026-10-09 23:12:57',NULL),
(16,3,'public','media/2026/10/hBL5bEhbMBzW81WZyKcYj1OwRbRvQxCZ5Lq7DBXh.png','001.png',NULL,'image/png',490891,NULL,NULL,2,'2026-10-09 23:13:03','2026-10-09 23:13:03',NULL),
(17,3,'public','media/2026/10/R1CpTMAtvBBvnCd3S1RfMoiTDtQ77Uf2IWGWuENY.png','002.png',NULL,'image/png',331810,NULL,NULL,2,'2026-10-09 23:13:03','2026-10-09 23:13:03',NULL),
(18,3,'public','media/2026/10/unTW19jlE8cFq3INzwn4CKSP4HohB6VGuQOTLZ4m.png','003.png',NULL,'image/png',684806,NULL,NULL,2,'2026-10-09 23:13:03','2026-10-09 23:13:03',NULL),
(19,3,'public','media/2026/10/pIUhfucwSTpfkWOrhRNugHD5BOPaDKwvE7Ifk1nV.png','004.png',NULL,'image/png',573960,NULL,NULL,2,'2026-10-09 23:13:03','2026-10-09 23:13:03',NULL),
(20,3,'public','media/2026/10/DmBi3kgPknWPhLVJBiqZj1Up2ii9EFwBwmmHdyKG.png','005.png',NULL,'image/png',551516,NULL,NULL,2,'2026-10-09 23:13:03','2026-10-09 23:13:03',NULL),
(21,4,'public','media/2026/10/xiDvgGwha7zG0Pa0gXMwJL8Ixx8iSlbXj8Xp6D5m.png','004.png',NULL,'image/png',332792,NULL,NULL,2,'2026-10-09 23:13:10','2026-10-09 23:13:10',NULL),
(22,4,'public','media/2026/10/2gVsRmukMsI3zlngPzjtzSbq4iRQwAFlniqzlCyN.jpg','005.jpg',NULL,'image/jpeg',111987,NULL,NULL,2,'2026-10-09 23:13:10','2026-10-09 23:13:10',NULL),
(23,4,'public','media/2026/10/6kCWSQInZRvKda78ls5xsmHiQDhtLuPYrIE99RPa.jpg','006.jpg',NULL,'image/jpeg',26185,NULL,NULL,2,'2026-10-09 23:13:10','2026-10-09 23:13:10',NULL),
(24,4,'public','media/2026/10/GS4xx96htCj6nD1EzkIUIEvc3K0FKUpZqtnfqkif.jpg','007.jpg',NULL,'image/jpeg',64303,NULL,NULL,2,'2026-10-09 23:13:10','2026-10-09 23:13:10',NULL),
(25,4,'public','media/2026/10/rlxqZjhu08tK90NAeUz6vbmxSubeKg2Yu9mH6S3s.jpg','008.jpg',NULL,'image/jpeg',63386,NULL,NULL,2,'2026-10-09 23:13:10','2026-10-09 23:13:10',NULL),
(26,4,'public','media/2026/10/SGWYAOcSsXqIV4HjVPCLlT8ISm0VQqx7GKWvTLNW.png','001.png',NULL,'image/png',508024,NULL,NULL,2,'2026-10-09 23:13:10','2026-10-09 23:13:10',NULL),
(27,4,'public','media/2026/10/qIppS0CLNTADLWoMkjC6J5r1MlXtA617BmwIGHQm.png','002.png',NULL,'image/png',880730,NULL,NULL,2,'2026-10-09 23:13:10','2026-10-09 23:13:10',NULL),
(28,4,'public','media/2026/10/B6NwGAGTmQjN7HqSeKQ5h0AGzoVLXggYxabZia0s.png','003.png',NULL,'image/png',615934,NULL,NULL,2,'2026-10-09 23:13:10','2026-10-09 23:13:10',NULL),
(29,5,'public','media/2026/10/U1Cst2thgkJzp6SpE30S3d00jbOmvHIlEA5NlLtZ.png','TBC 06.png',NULL,'image/png',183192,NULL,NULL,2,'2026-10-09 23:13:18','2026-10-09 23:13:18',NULL),
(30,5,'public','media/2026/10/zNAbprW49u09Wl4cJus70e0AqGCzZaaWUzZIoj9w.png','TBC 01.png',NULL,'image/png',471104,NULL,NULL,2,'2026-10-09 23:13:18','2026-10-09 23:13:18',NULL),
(31,5,'public','media/2026/10/2ajLgmBFPy1Qk3ZlVLibnqBlLYZkoylrwF7BS81R.png','TBC 02.png',NULL,'image/png',245761,NULL,NULL,2,'2026-10-09 23:13:18','2026-10-09 23:13:18',NULL),
(32,5,'public','media/2026/10/gSYUX3Iw7BDZ0RmaxOe1MoV2JBN2Tx9vlz5D5s2v.png','TBC 03.png',NULL,'image/png',579934,NULL,NULL,2,'2026-10-09 23:13:18','2026-10-09 23:13:18',NULL),
(33,5,'public','media/2026/10/Zjo4ZmEgZw9XW8ao7eGX2Buqx34K2uKOyv5bBLob.png','TBC 04.png',NULL,'image/png',315545,NULL,NULL,2,'2026-10-09 23:13:18','2026-10-09 23:13:18',NULL),
(34,5,'public','media/2026/10/jP8E4v1dvwwwClNVbIiuEdCY9G1ggB4B1DJB1izj.png','TBC 05.png',NULL,'image/png',349755,NULL,NULL,2,'2026-10-09 23:13:18','2026-10-09 23:13:18',NULL);
/*!40000 ALTER TABLE `media` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `media_folders`
--

DROP TABLE IF EXISTS `media_folders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `media_folders` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `parent_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `media_folders_parent_id_foreign` (`parent_id`),
  CONSTRAINT `media_folders_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `media_folders` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `media_folders`
--

LOCK TABLES `media_folders` WRITE;
/*!40000 ALTER TABLE `media_folders` DISABLE KEYS */;
INSERT INTO `media_folders` VALUES
(1,'program-hiv',NULL,'2026-10-09 23:12:01','2026-10-09 23:12:01'),
(2,'program-imunisasi',NULL,'2026-10-09 23:12:06','2026-10-09 23:12:06'),
(3,'program-kia',NULL,'2026-10-09 23:12:13','2026-10-09 23:12:13'),
(4,'program-malaria',NULL,'2026-10-09 23:12:18','2026-10-09 23:12:25'),
(5,'program-tbc',NULL,'2026-10-09 23:12:38','2026-10-09 23:12:38'),
(6,'penelitian-a',NULL,'2026-10-10 00:26:26','2026-10-10 00:26:26');
/*!40000 ALTER TABLE `media_folders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `media_usages`
--

DROP TABLE IF EXISTS `media_usages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `media_usages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `media_id` bigint(20) unsigned NOT NULL,
  `usable_type` varchar(255) NOT NULL,
  `usable_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `media_usage_unique` (`media_id`,`usable_type`,`usable_id`),
  KEY `media_usages_usable_type_usable_id_index` (`usable_type`,`usable_id`),
  CONSTRAINT `media_usages_media_id_foreign` FOREIGN KEY (`media_id`) REFERENCES `media` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `media_usages`
--

LOCK TABLES `media_usages` WRITE;
/*!40000 ALTER TABLE `media_usages` DISABLE KEYS */;
/*!40000 ALTER TABLE `media_usages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `menus`
--

DROP TABLE IF EXISTS `menus`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `menus` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `label` varchar(255) NOT NULL,
  `type` enum('page_link','custom_url') NOT NULL DEFAULT 'custom_url',
  `editable` tinyint(1) NOT NULL DEFAULT 1,
  `page_id` bigint(20) DEFAULT NULL,
  `url` varchar(255) DEFAULT NULL,
  `parent_id` bigint(20) DEFAULT NULL,
  `order` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `menus_label_unique` (`label`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `menus`
--

LOCK TABLES `menus` WRITE;
/*!40000 ALTER TABLE `menus` DISABLE KEYS */;
/*!40000 ALTER TABLE `menus` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES
(1,'0001_01_01_000000_create_users_table',1),
(2,'0001_01_01_000001_create_cache_table',1),
(3,'0001_01_01_000002_create_jobs_table',1),
(4,'2024_01_01_000000_create_passkeys_table',1),
(5,'2025_08_14_170933_add_two_factor_columns_to_users_table',1),
(6,'2026_05_28_135809_create_permission_tables',1),
(7,'2026_05_28_141619_c_m_sdb_migration',1),
(8,'2026_07_26_075431_create_activity_log_table',1),
(9,'2026_09_26_000001_create_media_folders_table',1),
(10,'2026_09_26_000002_create_media_table',1),
(11,'2026_09_26_000003_create_media_usages_table',1),
(12,'2026_09_27_000001_add_alt_text_and_soft_deletes_to_media_table',1),
(13,'2026_10_07_000001_create_snippets_table',1),
(14,'2026_10_10_000001_create_article_reviews_table',2);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `model_has_permissions`
--

DROP TABLE IF EXISTS `model_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `model_has_permissions` (
  `permission_id` bigint(20) unsigned NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `model_has_permissions`
--

LOCK TABLES `model_has_permissions` WRITE;
/*!40000 ALTER TABLE `model_has_permissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `model_has_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `model_has_roles`
--

DROP TABLE IF EXISTS `model_has_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `model_has_roles` (
  `role_id` bigint(20) unsigned NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `model_has_roles`
--

LOCK TABLES `model_has_roles` WRITE;
/*!40000 ALTER TABLE `model_has_roles` DISABLE KEYS */;
INSERT INTO `model_has_roles` VALUES
(1,'App\\Models\\User',1),
(1,'App\\Models\\User',2),
(1,'App\\Models\\User',3),
(2,'App\\Models\\User',1),
(2,'App\\Models\\User',3),
(3,'App\\Models\\User',1),
(3,'App\\Models\\User',4),
(3,'App\\Models\\User',5);
/*!40000 ALTER TABLE `model_has_roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `navigations`
--

DROP TABLE IF EXISTS `navigations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `navigations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `label` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`label`)),
  `route_name` varchar(255) DEFAULT NULL,
  `url` varchar(255) DEFAULT NULL,
  `order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `navigations`
--

LOCK TABLES `navigations` WRITE;
/*!40000 ALTER TABLE `navigations` DISABLE KEYS */;
INSERT INTO `navigations` VALUES
(1,'{\"en\":\"Programs\",\"id\":\"Program\"}','','/programs',1,1,'2026-10-09 22:34:15','2026-10-09 22:34:15'),
(2,'{\"en\":\"Articles\",\"id\":\"Artikel\"}','','/articles',2,1,'2026-10-09 22:51:54','2026-10-09 22:51:54'),
(3,'{\"en\":\"Impact\",\"id\":\"Dampak\"}','','/impact',4,1,'2026-10-09 22:52:08','2026-10-10 00:28:49'),
(4,'{\"en\":\"Transparency\",\"id\":\"Transparansi\"}','','/transparency',5,1,'2026-10-09 22:52:44','2026-10-10 00:28:49'),
(5,'{\"en\":\"Credibility\",\"id\":\"Kredibilitas\"}','','/credibility',6,1,'2026-10-09 22:53:21','2026-10-10 00:28:49'),
(6,'{\"en\":\"Home\",\"id\":\"Beranda\"}','','/home',0,1,'2026-10-09 22:54:30','2026-10-09 22:54:32'),
(7,'{\"en\":\"Research\",\"id\":\"Penelitian\"}','','/credibility',3,1,'2026-10-10 00:28:45','2026-10-10 00:28:49');
/*!40000 ALTER TABLE `navigations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pages`
--

DROP TABLE IF EXISTS `pages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `pages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`title`)),
  `slug` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`slug`)),
  `content` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`content`)),
  `meta_title` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`meta_title`)),
  `meta_description` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`meta_description`)),
  `status` enum('online','offline') NOT NULL DEFAULT 'offline',
  `published_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pages_slug_unique` (`slug`) USING HASH
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pages`
--

LOCK TABLES `pages` WRITE;
/*!40000 ALTER TABLE `pages` DISABLE KEYS */;
INSERT INTO `pages` VALUES
(1,'{\"en\":\"Home\",\"id\":\"Beranda\"}','{\"en\":\"home\",\"id\":\"beranda\"}','{\"blocks\":{\"b01\":{\"id\":\"b01\",\"type\":\"heading\",\"data\":{\"text\":{\"en\":\"Stronger health systems for the people of Papua\",\"id\":\"Sistem kesehatan yang lebih kuat bagi masyarakat Papua\"},\"level\":\"h1\"}},\"b02\":{\"id\":\"b02\",\"type\":\"paragraph\",\"data\":{\"text\":{\"en\":\"<p>Yayasan Sinar Bhakti Husada (YSBH) is a Papua-based health foundation founded in 2014. We work alongside government and international partners to strengthen health services, empower communities, and improve quality of life, especially in remote areas.</p>\",\"id\":\"<p>Yayasan Sinar Bhakti Husada (YSBH) adalah yayasan kesehatan di Papua yang berdiri sejak 2014. Kami bekerja bersama pemerintah dan mitra internasional untuk memperkuat layanan kesehatan, memberdayakan masyarakat, dan meningkatkan kualitas hidup, terutama di wilayah terpencil.</p>\"}}},\"b03\":{\"id\":\"b03\",\"type\":\"heading\",\"data\":{\"text\":{\"en\":\"What we do\",\"id\":\"Yang kami kerjakan\"},\"level\":\"h2\"}},\"b04\":{\"id\":\"b04\",\"type\":\"paragraph\",\"data\":{\"text\":{\"en\":\"<ul><li><strong>Health system strengthening</strong> — technical assistance, planning and monitoring support for district and provincial health offices.</li><li><strong>Community empowerment</strong> — training for health workers and community cadres, and health education.</li><li><strong>Research and equipment</strong> — health research, and providing health equipment and facilities.</li></ul>\",\"id\":\"<ul><li><strong>Penguatan sistem kesehatan</strong> — pendampingan teknis, perencanaan, dan pemantauan bagi dinas kesehatan kabupaten dan provinsi.</li><li><strong>Pemberdayaan masyarakat</strong> — pelatihan tenaga kesehatan dan kader, serta edukasi kesehatan.</li><li><strong>Riset dan peralatan</strong> — riset kesehatan, serta penyediaan peralatan dan sarana kesehatan.</li></ul>\"}}},\"b05\":{\"id\":\"b05\",\"type\":\"paragraph\",\"data\":{\"text\":{\"en\":\"<p><strong>Our programs:</strong> <a href=\\\"internal://page/malaria-program\\\">Malaria</a> · <a href=\\\"internal://page/immunization-program\\\">Immunization</a> · <a href=\\\"internal://page/maternal-child-health-program\\\">Maternal and Child Health</a> · <a href=\\\"internal://page/tb-program\\\">Tuberculosis (TB)</a> · <a href=\\\"internal://page/hiv-program\\\">HIV</a></p>\",\"id\":\"<p><strong>Program kami:</strong> <a href=\\\"internal://page/program-malaria\\\">Malaria</a> · <a href=\\\"internal://page/program-imunisasi\\\">Imunisasi</a> · <a href=\\\"internal://page/program-kesehatan-ibu-anak\\\">Kesehatan Ibu dan Anak</a> · <a href=\\\"internal://page/program-tbc\\\">Tuberkulosis (TBC)</a> · <a href=\\\"internal://page/program-hiv\\\">HIV</a></p>\"}}},\"b06\":{\"id\":\"b06\",\"type\":\"paragraph\",\"data\":{\"text\":{\"en\":\"<p><strong>Trusted by partners:</strong> Since 2014 YSBH has implemented health programs with UNICEF, USAID (through Pact and FHI 360), the World Health Organization (WHO), and provincial and district health offices in Papua.</p>\",\"id\":\"<p><strong>Dipercaya mitra:</strong> Sejak 2014 YSBH melaksanakan program kesehatan bersama UNICEF, USAID (melalui Pact dan FHI 360), World Health Organization (WHO), serta dinas kesehatan provinsi dan kabupaten di Papua.</p>\"}}},\"b07\":{\"id\":\"b07\",\"type\":\"paragraph\",\"data\":{\"text\":{\"en\":\"<p><a href=\\\"internal://page/programs\\\">Learn about our work →</a> · <a href=\\\"internal://page/credibility\\\">Our credibility →</a></p>\",\"id\":\"<p><a href=\\\"internal://page/program\\\">Pelajari kerja kami →</a> · <a href=\\\"internal://page/kredibilitas\\\">Kredibilitas kami →</a></p>\"}}},\"b08\":{\"id\":\"b08\",\"type\":\"paragraph\",\"data\":{\"text\":{\"en\":\"<p>[ISI-DULU] Donation call to action: waiting for the foundation\'s decision on payment methods.</p>\",\"id\":\"<p>[ISI-DULU] Ajakan donasi: menunggu keputusan yayasan tentang metode pembayaran.</p>\"}}}},\"order\":[\"b01\",\"b02\",\"b03\",\"b04\",\"b05\",\"b06\",\"b07\",\"b08\"],\"settings\":{\"toc_position\":\"right\"}}','{\"id\":\"\"}','{\"id\":\"\"}','online','2026-10-09 18:57:22','2026-10-09 18:54:11','2026-10-09 18:57:22'),
(2,'{\"en\":\"About Us\",\"id\":\"Tentang Kami\"}','{\"en\":\"about-us\",\"id\":\"tentang-kami\"}','{\"blocks\":{\"b01\":{\"id\":\"b01\",\"type\":\"heading\",\"data\":{\"text\":{\"en\":\"About us\",\"id\":\"Tentang kami\"},\"level\":\"h1\"}},\"b02\":{\"id\":\"b02\",\"type\":\"paragraph\",\"data\":{\"text\":{\"en\":\"<p><strong>Our story.</strong> Yayasan Sinar Bhakti Husada was founded in 2014. Most of its founding board were former civil servants working in program development at the Papua Provincial Health Office. The foundation works in social and humanitarian service, especially delivering health services and improving public health.</p>\",\"id\":\"<p><strong>Sejarah kami.</strong> Yayasan Sinar Bhakti Husada didirikan pada tahun 2014. Kepengurusan saat pendirian sebagian besar diisi mantan pegawai negeri sipil bidang bina program Dinas Kesehatan Provinsi Papua. Yayasan bergerak di bidang sosial dan kemanusiaan, khususnya pelayanan dan peningkatan derajat kesehatan masyarakat.</p>\"}}},\"b03\":{\"id\":\"b03\",\"type\":\"heading\",\"data\":{\"text\":{\"en\":\"Why we exist\",\"id\":\"Latar belakang\"},\"level\":\"h2\"}},\"b04\":{\"id\":\"b04\",\"type\":\"paragraph\",\"data\":{\"text\":{\"en\":\"<ul><li>Health is a basic human right.</li><li>Access to health services is still limited.</li><li>Health literacy is low.</li><li>Health workers and medical equipment are in short supply.</li><li>The foundation is a partner in finding solutions.</li></ul>\",\"id\":\"<ul><li>Kesehatan adalah hak dasar manusia.</li><li>Akses layanan masih terbatas.</li><li>Literasi kesehatan rendah.</li><li>Keterbatasan tenaga dan alat kesehatan.</li><li>Yayasan hadir sebagai mitra untuk mencari solusi.</li></ul>\"}}},\"b05\":{\"id\":\"b05\",\"type\":\"paragraph\",\"data\":{\"text\":{\"en\":\"<p><strong>Vision.</strong> To optimize the foundation\'s role in involving all stakeholders in health development.</p>\",\"id\":\"<p><strong>Visi.</strong> Mengoptimalkan peran yayasan dalam melibatkan semua pemangku kepentingan dalam pembangunan kesehatan.</p>\"}}},\"b06\":{\"id\":\"b06\",\"type\":\"heading\",\"data\":{\"text\":{\"en\":\"Mission\",\"id\":\"Misi\"},\"level\":\"h2\"}},\"b07\":{\"id\":\"b07\",\"type\":\"paragraph\",\"data\":{\"text\":{\"en\":\"<ul><li>Community empowerment</li><li>Health system strengthening</li><li>Improving quality of life</li></ul>\",\"id\":\"<ul><li>Pemberdayaan masyarakat</li><li>Penguatan sistem kesehatan</li><li>Peningkatan kualitas hidup</li></ul>\"}}},\"b08\":{\"id\":\"b08\",\"type\":\"paragraph\",\"data\":{\"text\":{\"en\":\"<p><strong>Our objectives:</strong> improve the level of health · empower communities · strengthen health systems · develop research · improve the health workforce · provide health facilities.</p>\",\"id\":\"<p><strong>Tujuan yayasan:</strong> meningkatkan derajat kesehatan · pemberdayaan masyarakat · penguatan sistem kesehatan · pengembangan riset · peningkatan SDM kesehatan · penyediaan sarana kesehatan.</p>\"}}},\"b09\":{\"id\":\"b09\",\"type\":\"paragraph\",\"data\":{\"text\":{\"en\":\"<p><strong>What we do:</strong> health system strengthening · health research · technical assistance for health programs · providing equipment · human resource development · health education.</p>\",\"id\":\"<p><strong>Bentuk kegiatan:</strong> penguatan sistem kesehatan · riset kesehatan · pendampingan teknis program kesehatan · penyediaan peralatan · peningkatan SDM · edukasi kesehatan.</p>\"}}},\"b10\":{\"id\":\"b10\",\"type\":\"heading\",\"data\":{\"text\":{\"en\":\"How we are organized\",\"id\":\"Organisasi pelaksana\"},\"level\":\"h2\"}},\"b11\":{\"id\":\"b11\",\"type\":\"paragraph\",\"data\":{\"text\":{\"en\":\"<ul><li><strong>Core Team</strong>, coordinated by the Director: Finance Manager, Human Resources and Admin Manager, and Senior Technical Advisor.</li><li><strong>Technical Team</strong>, coordinated by the Program Manager: technical staff for health systems, maternal, neonatal and child health (MNCH), HIV, TB, pharmacy and logistics, malaria, and the Expanded Program on Immunization (EPI).</li><li><strong>Program Team</strong>: four Program Officers (MNCH, EPI, TB–HIV, malaria), who supervise Field Officers.</li><li><strong>Research Division</strong>: memoranda of understanding with five researchers and one university, the Faculty of Public Health of Cenderawasih University (UNCEN), Jayapura.</li><li><strong>Training Team</strong>: coaching for Program Officers and Field Officers, and for government technical agencies, during assignments.</li></ul>\",\"id\":\"<ul><li><strong>Core Team</strong> dikoordinir oleh Direktur: Finance Manager, Human Resources–Admin Manager, dan Senior Technical Advisor.</li><li><strong>Technical Team</strong> dikoordinir oleh Program Manager: tenaga teknis sistem kesehatan, MNCH (Maternal Neonatal Child Health), HIV, TB, Pharmacy/Logistic, Malaria, dan EPI (Expanded Program Immunization).</li><li><strong>Program Team</strong>: empat Program Officer (MNCH, EPI, TB–HIV, Malaria) yang membawahi Field Officer.</li><li><strong>Tim/Divisi Riset</strong>: MoU dengan lima peneliti dan satu universitas, yaitu FKM Universitas Cenderawasih (UNCEN), Jayapura.</li><li><strong>Tim Training</strong>: coaching bagi PO dan FO, serta bagi instansi teknis pemerintah, saat penugasan.</li></ul>\"}}},\"b12\":{\"id\":\"b12\",\"type\":\"paragraph\",\"data\":{\"text\":{\"en\":\"<p><strong>Founding board (2014).</strong> Chair of the Advisory Board: drg. Agnes Ang · Advisory Board members: Saryono, SKM; Sriyana, SKM · Chair of the Supervisory Board: Sriyana, SKM · Supervisory Board member: Julianingsih Bakker · General Chair: I Kadek Hermanta, SKM., M.Si · Secretary: drh. Putu Yuga Andacara · Treasurer: Kusdiyana, S.IKom., MM.</p>\",\"id\":\"<p><strong>Susunan pengurus saat pendirian (2014).</strong> Ketua Pembina: drg. Agnes Ang · Anggota Pembina: Saryono, SKM; Sriyana, SKM · Ketua Pengawas: Sriyana, SKM · Anggota Pengawas: Julianingsih Bakker · Ketua Umum: I Kadek Hermanta, SKM., M.Si · Sekretaris: drh. Putu Yuga Andacara · Bendahara: Kusdiyana, S.IKom., MM.</p>\"}}},\"b13\":{\"id\":\"b13\",\"type\":\"paragraph\",\"data\":{\"text\":{\"en\":\"<p>[ISI-DULU] Confirm current leadership and each person\'s consent to be listed (see notes).</p>\",\"id\":\"<p>[ISI-DULU] Konfirmasi susunan terkini dan persetujuan tiap orang untuk dicantumkan (lihat catatan).</p>\"}}}},\"order\":[\"b01\",\"b02\",\"b03\",\"b04\",\"b05\",\"b06\",\"b07\",\"b08\",\"b09\",\"b10\",\"b11\",\"b12\",\"b13\"],\"settings\":{\"toc_position\":\"right\"}}','{\"id\":\"\"}','{\"id\":\"\"}','online','2026-10-09 18:57:52','2026-10-09 18:54:11','2026-10-09 18:57:52'),
(3,'{\"en\":\"Our Programs\",\"id\":\"Program Kami\"}','{\"en\":\"programs\",\"id\":\"program\"}','{\"blocks\":{\"b01\":{\"id\":\"b01\",\"type\":\"heading\",\"data\":{\"text\":{\"en\":\"Our programs\",\"id\":\"Program kami\"},\"level\":\"h1\"}},\"b02\":{\"id\":\"b02\",\"type\":\"paragraph\",\"data\":{\"text\":{\"en\":\"<p>Our programs are built around what local health systems need. We provide technical assistance, training, planning and monitoring support, and work with district and provincial health offices so that results last beyond the funding period.</p>\",\"id\":\"<p>Program kami disusun berdasarkan kebutuhan sistem kesehatan setempat. Kami memberikan pendampingan teknis, pelatihan, serta dukungan perencanaan dan pemantauan, dan bekerja bersama dinas kesehatan kabupaten dan provinsi agar hasilnya bertahan setelah masa pendanaan berakhir.</p>\"}}},\"b03\":{\"id\":\"b03\",\"type\":\"heading\",\"data\":{\"text\":{\"en\":\"Five program areas\",\"id\":\"Lima bidang program\"},\"level\":\"h2\"}},\"blk_KcZGt47x\":{\"id\":\"blk_KcZGt47x\",\"type\":\"card-builder\",\"data\":{\"grid\":{\"cols\":5,\"margin_bottom\":\"mb-4 md:mb-6\"},\"cards\":[{\"id\":\"card_6ac96c10c875f\",\"layout\":{\"type\":\"row\",\"children\":[{\"id\":\"col_6ac96c10c8759\",\"type\":\"column\",\"width\":1,\"children\":[{\"id\":\"el_TBMIGphtLM\",\"type\":\"element\",\"elementType\":\"text\",\"data\":{\"content\":{\"en\":\"Malaria\",\"id\":\"Malaria\"},\"style\":{\"is_pill\":true,\"font\":\"font-jakarta\",\"weight\":\"font-normal\",\"size\":\"text-[15px]\",\"text_transform\":\"normal-case\",\"pill_bg\":\"bg-goldy-soft\",\"pill_radius\":\"rounded-full\",\"color\":\"text-ink-soft\",\"margin\":\"mb-2\"}}},{\"id\":\"el_SWHry7eHhr\",\"type\":\"element\",\"elementType\":\"text\",\"data\":{\"content\":{\"en\":\"malaria control and elimination, including integrated services for pregnant women and children, and insecticide-resistance surveillance.\",\"id\":\"Pengendalian dan eliminasi malaria, termasuk layanan terpadu bagi ibu hamil dan anak-anak, serta surveilans resistansi insektisida.\"},\"style\":{\"is_pill\":false,\"font\":\"font-jakarta\",\"weight\":\"font-normal\",\"size\":\"text-[15px]\",\"text_transform\":\"normal-case\",\"pill_bg\":\"bg-goldy-soft\",\"pill_radius\":\"rounded-md\",\"color\":\"text-ink-soft\",\"margin\":\"mb-2\"}}}]}]},\"container\":{\"bg\":\"bg-mist\",\"padding\":\"p-2 md:p-4\",\"border_width\":\"border-0\",\"border_style\":\"border-solid\",\"border_color\":\"border-forest\",\"radius\":\"rounded-[14px]\",\"shadow\":\"shadow-sm\",\"hover\":\"hover:-translate-y-1\",\"align_y\":\"items-center\",\"url\":\"internal://page/malaria-program\",\"url_label\":\"Malaria Program\"}},{\"id\":\"card_6ac96c9e11689\",\"layout\":{\"type\":\"row\",\"children\":[{\"id\":\"col_6ac96c9e1168b\",\"type\":\"column\",\"width\":1,\"children\":[{\"id\":\"el_6ac96c9e1168c\",\"type\":\"element\",\"elementType\":\"text\",\"data\":{\"content\":{\"en\":\"Immunization\",\"id\":\"Imunisasi\"},\"style\":{\"is_pill\":true,\"font\":\"font-jakarta\",\"weight\":\"font-normal\",\"size\":\"text-[15px]\",\"text_transform\":\"normal-case\",\"pill_bg\":\"bg-goldy-soft\",\"pill_radius\":\"rounded-full\",\"color\":\"text-foresty\",\"margin\":\"mb-2\"}}},{\"id\":\"el_6ac96c9e1168d\",\"type\":\"element\",\"elementType\":\"text\",\"data\":{\"content\":{\"en\":\"support for routine immunization and the elimination of maternal and neonatal tetanus.\",\"id\":\"dukungan untuk imunisasi rutin dan eliminasi tetanus maternal dan neonatal.\"},\"style\":{\"is_pill\":false,\"font\":\"font-jakarta\",\"weight\":\"font-normal\",\"size\":\"text-[15px]\",\"text_transform\":\"normal-case\",\"pill_bg\":\"bg-goldy-soft\",\"pill_radius\":\"rounded-md\",\"color\":\"text-aurum\",\"margin\":\"mb-2\"}}}]}]},\"container\":{\"bg\":\"bg-forest\",\"padding\":\"p-2 md:p-4\",\"border_width\":\"border-0\",\"border_style\":\"border-solid\",\"border_color\":\"border-forest\",\"radius\":\"rounded-[14px]\",\"shadow\":\"shadow-sm\",\"hover\":\"hover:-translate-y-1\",\"align_y\":\"items-center\",\"url\":\"internal://page/immunization-program\",\"url_label\":\"Immunization Program\"}},{\"id\":\"card_6ac96d556e268\",\"layout\":{\"type\":\"row\",\"children\":[{\"id\":\"col_6ac96d556e269\",\"type\":\"column\",\"width\":1,\"children\":[{\"id\":\"el_6ac96d556e26a\",\"type\":\"element\",\"elementType\":\"text\",\"data\":{\"content\":{\"en\":\"Maternal & Child Health\",\"id\":\"Kesehatan Ibu & Anak\"},\"style\":{\"is_pill\":true,\"font\":\"font-jakarta\",\"weight\":\"font-normal\",\"size\":\"text-[15px]\",\"text_transform\":\"normal-case\",\"pill_bg\":\"bg-goldy-soft\",\"pill_radius\":\"rounded-full\",\"color\":\"text-foresty\",\"margin\":\"mb-2\"}}},{\"id\":\"el_6ac96d556e26b\",\"type\":\"element\",\"elementType\":\"text\",\"data\":{\"content\":{\"en\":\"support for routine immunization and the elimination of maternal and neonatal tetanus.\",\"id\":\"dukungan untuk imunisasi rutin dan eliminasi tetanus maternal dan neonatal.\"},\"style\":{\"is_pill\":false,\"font\":\"font-jakarta\",\"weight\":\"font-normal\",\"size\":\"text-[15px]\",\"text_transform\":\"normal-case\",\"pill_bg\":\"bg-goldy-soft\",\"pill_radius\":\"rounded-md\",\"color\":\"text-aurum\",\"margin\":\"mb-2\"}}}]}]},\"container\":{\"bg\":\"bg-coral\",\"padding\":\"p-2 md:p-4\",\"border_width\":\"border-0\",\"border_style\":\"border-solid\",\"border_color\":\"border-forest\",\"radius\":\"rounded-[14px]\",\"shadow\":\"shadow-sm\",\"hover\":\"hover:-translate-y-1\",\"align_y\":\"items-center\",\"url\":\"internal://page/maternal-child-health-program\",\"url_label\":\"Maternal and Child Health Program\"}},{\"id\":\"card_6ac96dcec9ce1\",\"layout\":{\"type\":\"row\",\"children\":[{\"id\":\"col_6ac96dcec9ce2\",\"type\":\"column\",\"width\":1,\"children\":[{\"id\":\"el_6ac96dcec9ce3\",\"type\":\"element\",\"elementType\":\"text\",\"data\":{\"content\":{\"en\":\"Tuberculosis (TB)\",\"id\":\"Tuberculosis (TB)\"},\"style\":{\"is_pill\":true,\"font\":\"font-jakarta\",\"weight\":\"font-normal\",\"size\":\"text-[15px]\",\"text_transform\":\"normal-case\",\"pill_bg\":\"bg-goldy-soft\",\"pill_radius\":\"rounded-full\",\"color\":\"text-ink-soft\",\"margin\":\"mb-2\"}}},{\"id\":\"el_6ac96dcec9ce4\",\"type\":\"element\",\"elementType\":\"text\",\"data\":{\"content\":{\"en\":\"strengthening TB control in health services.\",\"id\":\"memperkuat pengendalian TB di layanan kesehatan.\"},\"style\":{\"is_pill\":false,\"font\":\"font-jakarta\",\"weight\":\"font-normal\",\"size\":\"text-[15px]\",\"text_transform\":\"normal-case\",\"pill_bg\":\"bg-goldy-soft\",\"pill_radius\":\"rounded-md\",\"color\":\"text-ink-soft\",\"margin\":\"mb-2\"}}}]}]},\"container\":{\"bg\":\"bg-mist\",\"padding\":\"p-2 md:p-4\",\"border_width\":\"border-0\",\"border_style\":\"border-solid\",\"border_color\":\"border-forest\",\"radius\":\"rounded-[14px]\",\"shadow\":\"shadow-sm\",\"hover\":\"hover:-translate-y-1\",\"align_y\":\"items-center\",\"url\":\"internal://page/tb-program\",\"url_label\":\"Tuberculosis (TB) Program\"}},{\"id\":\"card_6ac96e1364f9a\",\"layout\":{\"type\":\"row\",\"children\":[{\"id\":\"col_6ac96e1364f9b\",\"type\":\"column\",\"width\":1,\"children\":[{\"id\":\"el_6ac96e1364f9c\",\"type\":\"element\",\"elementType\":\"text\",\"data\":{\"content\":{\"en\":\"HIV\",\"id\":\"HIV\"},\"style\":{\"is_pill\":true,\"font\":\"font-jakarta\",\"weight\":\"font-normal\",\"size\":\"text-[15px]\",\"text_transform\":\"normal-case\",\"pill_bg\":\"bg-goldy-soft\",\"pill_radius\":\"rounded-full\",\"color\":\"text-foresty\",\"margin\":\"mb-2\"}}},{\"id\":\"el_6ac96e1364f9d\",\"type\":\"element\",\"elementType\":\"text\",\"data\":{\"content\":{\"en\":\"support to health offices for HIV services and data.\",\"id\":\"dukungan kepada dinas kesehatan untuk layanan dan data HIV.\"},\"style\":{\"is_pill\":false,\"font\":\"font-jakarta\",\"weight\":\"font-normal\",\"size\":\"text-[15px]\",\"text_transform\":\"normal-case\",\"pill_bg\":\"bg-goldy-soft\",\"pill_radius\":\"rounded-md\",\"color\":\"text-aurum\",\"margin\":\"mb-2\"}}}]}]},\"container\":{\"bg\":\"bg-forest\",\"padding\":\"p-2 md:p-4\",\"border_width\":\"border-0\",\"border_style\":\"border-solid\",\"border_color\":\"border-forest\",\"radius\":\"rounded-[14px]\",\"shadow\":\"shadow-sm\",\"hover\":\"hover:-translate-y-1\",\"align_y\":\"items-center\",\"url\":\"internal://page/hiv-program\",\"url_label\":\"HIV Program\"}}]}},\"blk_qO8QDKxk\":{\"id\":\"blk_qO8QDKxk\",\"type\":\"paragraph\",\"data\":{\"text\":{\"en\":\"<p>sasangeyo, sasangeyo, shinzo</p>\",\"id\":\"<p>sasangeyo, sasangeyo, shinzo</p>\"},\"margin_bottom\":\"mb-4 md:mb-6\"}}},\"order\":[\"b01\",\"b02\",\"b03\",\"blk_KcZGt47x\",\"blk_qO8QDKxk\"],\"settings\":{\"toc_position\":\"right\"}}','{\"id\":\"\"}','{\"id\":\"\"}','online','2026-10-09 22:47:01','2026-10-09 18:54:11','2026-10-10 10:13:55'),
(4,'{\"en\":\"Malaria Program\",\"id\":\"Program Malaria\"}','{\"en\":\"malaria-program\",\"id\":\"program-malaria\"}','{\"blocks\":{\"b01\":{\"id\":\"b01\",\"type\":\"heading\",\"data\":{\"text\":{\"en\":\"Together Towards a Malaria-Free Papua\",\"id\":\"Bersama Mewujudkan Tanah Papua Bebas Malaria\"},\"level\":\"h1\"},\"anchor\":\"together-towards-a-malaria-free-papua\"},\"b02\":{\"id\":\"b02\",\"type\":\"paragraph\",\"data\":{\"text\":{\"en\":\"<p>The Papua region, with six autonomous provinces and 42 regencies, accounts for 90% of malaria cases in Indonesia. The 2025 E-SISMAL report recorded 204,068 cases in Central Papua alone. Mimika Regency is the largest contributor in the province with 190,597 cases, followed by Nabire Regency with 8,744 cases and several others. Case numbers vary across the other provinces and regencies, from coastal areas such as Sarmi Regency (Papua Province) to mountainous regions such as Yahukimo Regency (Highland Papua Province). This high burden shows that malaria remains a public health challenge that needs sustained attention and control efforts.</p>\",\"id\":\"<p>Tanah Papua, dengan 6 provinsi mandiri dan 42 kabupaten, menyumbang 90% kasus malaria di Indonesia. Menurut laporan E-SISMAL 2025, Papua Tengah mencatat 204.068 kasus malaria. Kabupaten Mimika menjadi penyumbang terbesar di provinsi ini dengan 190.597 kasus, diikuti Kabupaten Nabire sebanyak 8.744 kasus dan beberapa kabupaten lainnya. Di provinsi dan kabupaten lain angkanya bervariasi, dari kabupaten pesisir seperti Sarmi (Provinsi Papua) sampai kabupaten pegunungan seperti Yahukimo (Provinsi Papua Pegunungan). Tingginya beban kasus ini menunjukkan bahwa malaria masih menjadi tantangan kesehatan masyarakat yang memerlukan perhatian dan upaya pengendalian berkelanjutan.</p>\"}}},\"b03\":{\"id\":\"b03\",\"type\":\"paragraph\",\"data\":{\"text\":{\"en\":\"<p>Beyond the number of cases, malaria hits vulnerable groups hardest: pregnant women, infants and children under five. Infection in these groups raises the risk of health problems that affect quality of life and child growth and development. Eliminating malaria is therefore not only about reducing cases; it is about protecting the future generation of Papua.</p>\",\"id\":\"<p>Di balik tingginya jumlah kasus, malaria berdampak lebih besar pada kelompok rentan seperti ibu hamil, bayi dan balita. Infeksi pada kelompok ini meningkatkan risiko gangguan kesehatan yang memengaruhi kualitas hidup serta tumbuh kembang anak. Eliminasi malaria bukan hanya soal menurunkan angka kasus, tetapi juga melindungi generasi masa depan Papua.</p>\"}}},\"b04\":{\"id\":\"b04\",\"type\":\"paragraph\",\"data\":{\"text\":{\"en\":\"<p>Eliminating malaria in the Papua region, including Central Papua and Highland Papua, needs a comprehensive and sustainable approach. Efforts to accelerate elimination focus not only on case management but also on strengthening health systems.</p>\",\"id\":\"<p>Eliminasi malaria di Tanah Papua, termasuk di Papua Tengah dan Papua Pegunungan, memerlukan pendekatan yang menyeluruh dan berkelanjutan. Upaya percepatan eliminasi tidak hanya berfokus pada penanganan kasus, tetapi juga pada penguatan sistem kesehatan.</p>\"}}},\"b05\":{\"id\":\"b05\",\"type\":\"image\",\"data\":{\"url\":\"http://landing-ysbh.test/storage/media/2026/10/SGWYAOcSsXqIV4HjVPCLlT8ISm0VQqx7GKWvTLNW.png\",\"alt_text\":\"\",\"caption\":{\"en\":\"On-the-job training in Paniai Regency, Central Papua Province, for the malaria program.\",\"id\":\"Pelatihan di tempat kerja (OJT) di Kabupaten Paniai, Provinsi Papua Tengah, untuk program malaria.\"},\"width\":\"w-3/4\",\"align\":\"mx-auto\",\"radius\":\"rounded-lg\",\"height\":\"h-auto\",\"max_height\":\"max-h-[32rem]\",\"object_fit\":\"object-cover\",\"margin_bottom\":\"mb-4 md:mb-6\",\"media_id\":26}},\"b06\":{\"id\":\"b06\",\"type\":\"paragraph\",\"data\":{\"text\":{\"en\":\"<p>Yayasan Sinar Bhakti Husada (YSBH) is committed to helping local governments and other stakeholders accelerate malaria elimination by developing strategies and giving technical support for malaria program implementation. YSBH\'s cooperation with local governments on malaria is formalized through a type-3 self-management (<em>swakelola</em>) mechanism. A partnership with a local university, Universitas Cenderawasih, has also begun, to research how effective community health workers are in malaria services, with local culture built in as a key factor.</p>\",\"id\":\"<p>Yayasan Sinar Bhakti Husada (YSBH) berkomitmen membantu pemerintah daerah dan pemangku kepentingan lain mempercepat eliminasi malaria melalui pengembangan strategi dan pendampingan teknis untuk pelaksanaan program malaria. Kerja sama YSBH dengan pemerintah daerah untuk program malaria diikat dalam mekanisme swakelola tipe-3. Kerja sama dengan perguruan tinggi lokal, Universitas Cenderawasih, juga telah dimulai untuk riset implementasi tentang efektivitas peran kader dalam layanan malaria, dengan budaya setempat sebagai faktor yang diperhitungkan.</p>\"}}},\"b07\":{\"id\":\"b07\",\"type\":\"image\",\"data\":{\"url\":\"http://landing-ysbh.test/storage/media/2026/10/qIppS0CLNTADLWoMkjC6J5r1MlXtA617BmwIGHQm.png\",\"alt_text\":\"\",\"caption\":{\"en\":\"Signing of the cooperation agreement on self-managed funds between YSBH and the Sarmi District Health Office, in Sarmi.\",\"id\":\"Penandatanganan perjanjian kerja sama dana swakelola antara YSBH dan Dinas Kesehatan Kabupaten Sarmi, di Sarmi.\"},\"width\":\"w-3/4\",\"align\":\"mx-auto\",\"radius\":\"rounded-lg\",\"height\":\"h-full\",\"max_height\":\"max-h-none\",\"object_fit\":\"object-cover\",\"margin_bottom\":\"mb-4 md:mb-6\",\"media_id\":27}},\"b08\":{\"id\":\"b08\",\"type\":\"paragraph\",\"data\":{\"text\":{\"en\":\"<p>YSBH\'s malaria program rests on five key components: strengthening program governance, enhancing malaria surveillance and response, building the capacity of health workers, improving the quality of malaria services, and developing Model Primary Health Centers (Puskesmas) and On-the-Job Training (OJT) Centers. Support focuses on high-burden areas (Mimika and Nabire regencies) and moderate-burden areas (Paniai, Deiyai, Dogiyai and Puncak Jaya regencies in Central Papua Province, Yahukimo Regency in Highland Papua Province, and Sarmi Regency in Papua Province).</p>\",\"id\":\"<p>Program malaria YSBH bertumpu pada lima komponen: penguatan tata kelola program, penguatan surveilans dan respons malaria, peningkatan kapasitas tenaga kesehatan, penguatan mutu layanan malaria, serta pengembangan Puskesmas Model dan pusat pembelajaran (On the Job Training Center). Dukungan difokuskan pada wilayah dengan beban tinggi (Kabupaten Mimika dan Nabire) dan beban sedang (Kabupaten Paniai, Deiyai, Dogiyai dan Puncak Jaya di Provinsi Papua Tengah, Kabupaten Yahukimo di Provinsi Papua Pegunungan, dan Kabupaten Sarmi di Provinsi Papua).</p>\"}}},\"b09\":{\"id\":\"b09\",\"type\":\"image\",\"data\":{\"url\":\"http://landing-ysbh.test/storage/media/2026/10/B6NwGAGTmQjN7HqSeKQ5h0AGzoVLXggYxabZia0s.png\",\"alt_text\":\"\",\"caption\":{\"en\":\"An OJT session on AIDS, TB and malaria introduced in Paniai Regency, one of the supported regencies in Central Papua Province.\",\"id\":\"Sesi OJT AIDS, TBC dan malaria di Kabupaten Paniai, salah satu kabupaten dampingan di Provinsi Papua Tengah.\"},\"width\":\"w-screen\",\"align\":\"mx-auto\",\"radius\":\"rounded-lg\",\"height\":\"h-auto\",\"max_height\":\"max-h-[32rem]\",\"object_fit\":\"object-cover\",\"margin_bottom\":\"mb-4 md:mb-6\",\"media_id\":28}},\"b10\":{\"id\":\"b10\",\"type\":\"heading\",\"data\":{\"text\":{\"en\":\"Five Key Components of YSBH\'s Malaria Program\",\"id\":\"Lima Komponen Intervensi Program Malaria YSBH\"},\"level\":\"h2\"},\"anchor\":\"five-key-components-of-ysbh-malaria-program\"},\"b11\":{\"id\":\"b11\",\"type\":\"heading\",\"data\":{\"text\":{\"en\":\"1. Strengthening Program Governance\",\"id\":\"1. Penguatan Tata Kelola Program\"},\"level\":\"h3\"}},\"b12\":{\"id\":\"b12\",\"type\":\"paragraph\",\"data\":{\"text\":{\"en\":\"<p>In several YSBH-supported regencies in Papua, geographical challenges, limited access to health services and changing field conditions call for strong coordination among local governments, the health sector and other stakeholders. Strong program governance is the foundation that keeps malaria elimination well directed, coordinated and sustainable.</p>\",\"id\":\"<p>Di beberapa wilayah dampingan YSBH di Tanah Papua, tantangan geografis, keterbatasan akses layanan dan dinamika kondisi lapangan menuntut koordinasi yang kuat antara pemerintah daerah, sektor kesehatan dan pemangku kepentingan lain. Tata kelola program yang kuat menjadi fondasi agar upaya eliminasi malaria terarah, terkoordinasi dan berkelanjutan.</p>\"}}},\"b13\":{\"id\":\"b13\",\"type\":\"paragraph\",\"data\":{\"text\":{\"en\":\"<p>YSBH helps local governments and partners strengthen coordination mechanisms, prepare for malaria elimination assessment, and apply standardized elimination strategies. This includes setting up and strengthening the provincial Malaria Elimination Assessment Team and facilitating workshops that speed up elimination, so regions are ready to reach their targets in a measurable and sustainable way.</p>\",\"id\":\"<p>YSBH mendukung pemerintah daerah dan mitra memperkuat mekanisme koordinasi, menyiapkan proses penilaian eliminasi malaria, dan mendorong penerapan strategi eliminasi yang terstandar. Dukungan ini diwujudkan melalui pembentukan dan penguatan Tim Assessment Eliminasi Malaria tingkat provinsi, serta fasilitasi workshop percepatan eliminasi malaria agar daerah siap mencapai target secara terukur dan berkelanjutan.</p>\"}}},\"b14\":{\"id\":\"b14\",\"type\":\"image\",\"data\":{\"url\":\"http://landing-ysbh.test/storage/media/2026/10/xiDvgGwha7zG0Pa0gXMwJL8Ixx8iSlbXj8Xp6D5m.png\",\"alt_text\":\"\",\"caption\":{\"en\":\"Microplanning assistance for accelerating malaria elimination in Nabire Regency.\",\"id\":\"Pendampingan mikroplanning untuk percepatan eliminasi malaria di Kabupaten Nabire.\"},\"width\":\"w-3/4\",\"align\":\"mx-auto\",\"radius\":\"rounded-lg\",\"height\":\"h-auto\",\"max_height\":\"max-h-[32rem]\",\"object_fit\":\"object-cover\",\"margin_bottom\":\"mb-4 md:mb-6\",\"media_id\":21}},\"b15\":{\"id\":\"b15\",\"type\":\"heading\",\"data\":{\"text\":{\"en\":\"2. Strengthening Malaria Surveillance and Case Response\",\"id\":\"2. Penguatan Surveilans dan Respons Kasus Malaria\"},\"level\":\"h3\"}},\"b16\":{\"id\":\"b16\",\"type\":\"paragraph\",\"data\":{\"text\":{\"en\":\"<p>Large service areas, population mobility and limited access make detecting and responding to malaria cases a particular challenge in Central Papua. For elimination, every case must be found quickly, investigated and monitored to prevent further transmission.</p>\",\"id\":\"<p>Wilayah layanan yang luas, mobilitas penduduk dan keterbatasan akses di beberapa daerah membuat deteksi dan respons kasus malaria menjadi tantangan tersendiri di Papua Tengah. Dalam konteks eliminasi, setiap kasus perlu segera ditemukan, ditindaklanjuti dan dipantau untuk mencegah penularan lanjutan.</p>\"}}},\"b17\":{\"id\":\"b17\",\"type\":\"paragraph\",\"data\":{\"text\":{\"en\":\"<p>YSBH helps local governments and health facilities strengthen case detection, epidemiological investigation, contact surveys and the 1-2-5 strategy as a rapid response to cases. The program promotes data-driven decisions through regular monitoring and evaluation, and develops digital innovations, including a medication reminder app to improve treatment adherence among malaria patients. Every case is not only recorded but followed up fully, to help break the chain of transmission in the supported areas.</p>\",\"id\":\"<p>YSBH mendukung pemerintah daerah dan fasilitas kesehatan memperkuat deteksi kasus, penyelidikan epidemiologi, contact survey, dan pelaksanaan strategi 1-2-5 sebagai respons cepat terhadap kasus. Program juga mendorong pemanfaatan data untuk pengambilan keputusan lewat monitoring dan evaluasi berkala, sekaligus mengembangkan inovasi digital berupa aplikasi pengingat minum obat untuk meningkatkan kepatuhan pengobatan pasien malaria. Dengan cara ini setiap kasus tidak hanya tercatat, tetapi ditindaklanjuti menyeluruh untuk membantu memutus rantai penularan di daerah dampingan.</p>\"}}},\"b18\":{\"id\":\"b18\",\"type\":\"image\",\"data\":{\"url\":\"http://landing-ysbh.test/storage/media/2026/10/2gVsRmukMsI3zlngPzjtzSbq4iRQwAFlniqzlCyN.jpg\",\"alt_text\":\"\",\"caption\":{\"en\":\"Strengthening malaria surveillance through routine data validation in Mimika Regency.\",\"id\":\"Penguatan surveilans malaria melalui validasi data rutin di Kabupaten Mimika.\"},\"width\":\"w-3/4\",\"align\":\"mx-auto\",\"radius\":\"rounded-lg\",\"height\":\"h-auto\",\"max_height\":\"max-h-[32rem]\",\"object_fit\":\"object-cover\",\"margin_bottom\":\"mb-4 md:mb-6\",\"media_id\":22}},\"b19\":{\"id\":\"b19\",\"type\":\"heading\",\"data\":{\"text\":{\"en\":\"3. Strengthening the Capacity of Health Workers\",\"id\":\"3. Penguatan Kapasitas Tenaga Kesehatan\"},\"level\":\"h3\"}},\"b20\":{\"id\":\"b20\",\"type\":\"paragraph\",\"data\":{\"text\":{\"en\":\"<p>The quality of a malaria program depends heavily on frontline health workers. Geography and limited access make it hard to build their skills evenly, so continuous capacity strengthening is essential for applying elimination standards consistently across all service areas.</p>\",\"id\":\"<p>Kualitas pelaksanaan program malaria sangat bergantung pada tenaga kesehatan di garis depan. Kondisi geografis dan keterbatasan akses sering menyulitkan pemerataan peningkatan kapasitas mereka, sehingga penguatan kompetensi yang berkelanjutan penting agar standar eliminasi malaria diterapkan konsisten di seluruh wilayah layanan.</p>\"}}},\"b21\":{\"id\":\"b21\",\"type\":\"paragraph\",\"data\":{\"text\":{\"en\":\"<p>YSBH supports health worker capacity through training, workshops and technical mentoring tailored to local needs. Beyond technical malaria skills, this builds a culture of continuous learning, so health workers can adapt, keep service quality high and contribute to elimination targets in the supported provinces and regencies.</p>\",\"id\":\"<p>YSBH mendukung penguatan kapasitas tenaga kesehatan melalui pelatihan, workshop dan pendampingan teknis yang disesuaikan dengan kebutuhan daerah. Selain meningkatkan kompetensi teknis malaria, pendekatan ini mendorong proses belajar berkelanjutan agar tenaga kesehatan dapat beradaptasi, menjaga mutu layanan, dan berkontribusi optimal pada target eliminasi di provinsi dan kabupaten dampingan.</p>\"}}},\"b22\":{\"id\":\"b22\",\"type\":\"heading\",\"data\":{\"text\":{\"en\":\"4. Strengthening the Quality of Malaria Services\",\"id\":\"4. Penguatan Mutu Layanan Malaria\"},\"level\":\"h3\"}},\"b23\":{\"id\":\"b23\",\"type\":\"paragraph\",\"data\":{\"text\":{\"en\":\"<p>Everyone has the right to quality malaria services wherever they seek care. But differences in facility capacity and operational challenges can affect how consistently standards are applied. Improving service quality ensures each patient gets diagnosis, treatment and follow-up that meet standards.</p>\",\"id\":\"<p>Masyarakat berhak memperoleh layanan malaria yang berkualitas di mana pun mereka berobat. Namun perbedaan kapasitas fasilitas kesehatan dan tantangan operasional di lapangan dapat memengaruhi konsistensi penerapan standar. Peningkatan mutu layanan memastikan setiap pasien mendapat diagnosis, pengobatan dan tindak lanjut yang sesuai standar.</p>\"}}},\"b24\":{\"id\":\"b24\",\"type\":\"paragraph\",\"data\":{\"text\":{\"en\":\"<p>YSBH facilitates quality assurance and regular monitoring and evaluation to assess service quality and find areas that need strengthening. The findings are the basis for service improvement, stronger coordination and better program implementation, so health facilities can keep improving malaria services in support of elimination in Papua.</p>\",\"id\":\"<p>YSBH memfasilitasi quality assurance serta monitoring dan evaluasi berkala untuk menilai mutu layanan dan menemukan area yang perlu diperkuat. Temuannya menjadi dasar perbaikan layanan, penguatan koordinasi dan peningkatan kualitas pelaksanaan program, sehingga fasilitas kesehatan dapat terus meningkatkan mutu layanan malaria untuk mendukung eliminasi di Tanah Papua.</p>\"}}},\"b25\":{\"id\":\"b25\",\"type\":\"image\",\"data\":{\"url\":\"http://landing-ysbh.test/storage/media/2026/10/6kCWSQInZRvKda78ls5xsmHiQDhtLuPYrIE99RPa.jpg\",\"alt_text\":\"\",\"caption\":{\"en\":\"Monitoring microscopy capacity at primary health centers to support quality malaria services.\",\"id\":\"Pemantauan kapasitas mikroskopis di puskesmas untuk mendukung layanan malaria yang bermutu.\"},\"width\":\"w-3/4\",\"align\":\"mx-auto\",\"radius\":\"rounded-lg\",\"height\":\"h-auto\",\"max_height\":\"max-h-[32rem]\",\"object_fit\":\"object-cover\",\"margin_bottom\":\"mb-4 md:mb-6\",\"media_id\":23}},\"b26\":{\"id\":\"b26\",\"type\":\"heading\",\"data\":{\"text\":{\"en\":\"5. Developing Model Primary Health Centers (Puskesmas) and On-the-Job Training (OJT) Centers\",\"id\":\"5. Pengembangan Puskesmas Model dan On the Job Training (OJT) Center\"},\"level\":\"h3\"}},\"b27\":{\"id\":\"b27\",\"type\":\"paragraph\",\"data\":{\"text\":{\"en\":\"<p>Eliminating malaria needs health facilities that not only deliver services to standard but also set an example of good practice for others. In Central Papua this faces difficult terrain, limited access to basic services and, in some areas, security situations that can affect the continuity of care. Learning centers that reflect real field conditions are therefore increasingly important for building health worker capacity over time.</p>\",\"id\":\"<p>Eliminasi malaria membutuhkan fasilitas kesehatan yang tidak hanya melayani sesuai standar, tetapi juga menjadi contoh praktik baik bagi fasilitas lain. Di Papua Tengah, upaya ini menghadapi medan yang sulit dijangkau, keterbatasan akses layanan dasar, dan situasi keamanan di beberapa wilayah yang dapat memengaruhi kesinambungan layanan. Pusat pembelajaran yang dekat dengan kenyataan di lapangan makin penting untuk peningkatan kapasitas tenaga kesehatan secara berkelanjutan.</p>\"}}},\"b28\":{\"id\":\"b28\",\"type\":\"paragraph\",\"data\":{\"text\":{\"en\":\"<p>Through Model Puskesmas and OJT Centers, YSBH creates places where health workers learn directly from practice in malaria service delivery. Model Puskesmas are also expected to become references for good practice in service delivery, program management and applying service standards, so proven lessons can be replicated by other facilities across the supported provinces and regencies.</p>\",\"id\":\"<p>Melalui Puskesmas Model dan OJT Center, YSBH menciptakan ruang belajar tempat tenaga kesehatan belajar langsung dari praktik pelayanan malaria. Puskesmas Model juga diharapkan menjadi rujukan praktik baik dalam penyelenggaraan layanan, pengelolaan program dan penerapan standar pelayanan, sehingga pengalaman yang terbukti efektif dapat direplikasi oleh fasilitas lain di daerah dampingan.</p>\"}}},\"b29\":{\"id\":\"b29\",\"type\":\"image\",\"data\":{\"url\":\"http://landing-ysbh.test/storage/media/2026/10/GS4xx96htCj6nD1EzkIUIEvc3K0FKUpZqtnfqkif.jpg\",\"alt_text\":\"\",\"caption\":{\"en\":\"Assessment of Model Primary Health Centers as learning centers for malaria service best practices.\",\"id\":\"Penilaian Puskesmas Model sebagai pusat pembelajaran praktik baik layanan malaria.\"},\"width\":\"w-3/4\",\"align\":\"mx-auto\",\"radius\":\"rounded-lg\",\"height\":\"h-auto\",\"max_height\":\"max-h-[32rem]\",\"object_fit\":\"object-cover\",\"margin_bottom\":\"mb-4 md:mb-6\",\"media_id\":24}},\"b30\":{\"id\":\"b30\",\"type\":\"heading\",\"data\":{\"text\":{\"en\":\"What we have done\",\"id\":\"Yang telah kami kerjakan\"},\"level\":\"h2\"},\"anchor\":\"what-we-have-done\"},\"b31\":{\"id\":\"b31\",\"type\":\"paragraph\",\"data\":{\"text\":{\"en\":\"<ul><li><strong>2014–2015, with UNICEF:</strong> integrated malaria control in Papua province, including a mass bed-net campaign week across 16 malaria-endemic districts.</li><li><strong>2016–2017, with UNICEF:</strong> integrated malaria control with a focus on preventing malaria in pregnant women and children, in Papua and especially Jayapura District. Our role covered training health workers, supporting health services, mentoring at health facilities and in communities, and monitoring and evaluation with the local health office.</li><li><strong>2020, with WHO:</strong> surveillance of insecticide resistance in malaria mosquitoes (Anopheles) in Jayapura City, Jayapura District, Waropen and Nabire, including field data collection, resistance testing, training of local people and technical reporting to national and WHO standards.</li><li><strong>2026, with the Sarmi District Health Office:</strong> accelerating malaria elimination in Sarmi. We support the district\'s malaria action plan for 2026–2030, regular cross-sector coordination, standardized diagnosis and treatment, and links between malaria, maternal and child health and immunization services.</li></ul>\",\"id\":\"<ul><li><strong>2014–2015, bersama UNICEF:</strong> pengendalian malaria terintegrasi di Provinsi Papua, termasuk Pekan Kelambu Massal di 16 kabupaten endemis malaria.</li><li><strong>2016–2017, bersama UNICEF:</strong> Program Pengendalian Malaria Terintegrasi dengan fokus pencegahan malaria pada ibu hamil dan anak, di Papua khususnya Kabupaten Jayapura. Peran kami meliputi penguatan kapasitas tenaga kesehatan, dukungan pelaksanaan layanan, pendampingan di fasilitas kesehatan dan masyarakat, serta monitoring dan evaluasi bersama dinas kesehatan setempat.</li><li><strong>2020, bersama WHO:</strong> surveilans resistensi insektisida nyamuk Anopheles di Kota Jayapura, Kabupaten Jayapura, Waropen, dan Nabire, meliputi pengumpulan data lapangan, pengujian resistensi, pelatihan masyarakat lokal, dan laporan teknis sesuai standar nasional dan WHO.</li><li><strong>2026, bersama Dinas Kesehatan Kabupaten Sarmi:</strong> percepatan eliminasi malaria di Kabupaten Sarmi. Kami mendukung Rencana Aksi Daerah (RAD) Percepatan Eliminasi Malaria 2026–2030, pertemuan rutin lintas sektor, standar diagnosis dan tata laksana, serta keterpaduan layanan malaria dengan KIA dan imunisasi.</li></ul>\"}}},\"b32\":{\"id\":\"b32\",\"type\":\"heading\",\"data\":{\"text\":{\"en\":\"Results reported\",\"id\":\"Hasil yang dilaporkan\"},\"level\":\"h2\"}},\"b33\":{\"id\":\"b33\",\"type\":\"paragraph\",\"data\":{\"text\":{\"en\":\"<ul><li>In 2015, the annual parasite incidence (API) of malaria fell in several districts, such as Jayawijaya and Biak Numfor (UNICEF-supported areas, 2015).</li></ul>\",\"id\":\"<ul><li>Pada 2015, angka kejadian malaria tahunan (Annual Parasite Incidence/API) menurun di beberapa kabupaten, seperti Jayawijaya dan Biak Numfor (wilayah dukungan UNICEF, 2015).</li></ul>\"}}},\"b34\":{\"id\":\"b34\",\"type\":\"heading\",\"data\":{\"text\":{\"en\":\"A Legacy That Must End\",\"id\":\"Warisan yang Perlu Diakhiri\"},\"level\":\"h2\"}},\"b35\":{\"id\":\"b35\",\"type\":\"paragraph\",\"data\":{\"text\":{\"en\":\"<p>For many communities, malaria has become a disease passed from one generation to the next. It has been present so long that it is often seen as a normal part of daily life. Yet no child should grow up facing repeated illness from malaria, and no family should accept a preventable disease as inevitable.</p>\",\"id\":\"<p>Bagi sebagian masyarakat, malaria telah menjadi penyakit yang diwariskan dari satu generasi ke generasi berikutnya. Kehadirannya begitu lama sehingga sering dianggap bagian dari kehidupan sehari-hari. Padahal tidak ada anak yang seharusnya tumbuh dengan risiko sakit berulang akibat malaria, dan tidak ada keluarga yang seharusnya menerima penyakit yang dapat dicegah sebagai hal yang wajar.</p>\"}}},\"b36\":{\"id\":\"b36\",\"type\":\"paragraph\",\"data\":{\"text\":{\"en\":\"<p>Creating malaria-free communities is not only about ending a disease. It is about breaking a legacy of risk that has long limited the chances of the people of Papua to live healthier and more prosperous lives.</p>\",\"id\":\"<p>Mewujudkan wilayah bebas malaria bukan hanya soal menghilangkan penyakit, tetapi juga memutus warisan risiko yang selama ini membatasi kesempatan masyarakat Papua untuk hidup lebih sehat dan sejahtera.</p>\"}}},\"blk_hjFC0E7s\":{\"id\":\"blk_hjFC0E7s\",\"type\":\"image\",\"data\":{\"url\":\"http://landing-ysbh.test/storage/media/2026/10/rlxqZjhu08tK90NAeUz6vbmxSubeKg2Yu9mH6S3s.jpg\",\"alt_text\":\"\",\"caption\":{\"en\":\"Assessment of Model Primary Health Centers as learning centers for malaria service best practices.\",\"id\":\"Penilaian Puskesmas Model sebagai pusat pembelajaran praktik baik layanan malaria.\"},\"width\":\"w-3/4\",\"align\":\"mx-auto\",\"radius\":\"rounded-lg\",\"height\":\"h-auto\",\"max_height\":\"max-h-none\",\"object_fit\":\"object-cover\",\"margin_bottom\":\"mb-4 md:mb-6\",\"media_id\":25}}},\"order\":[\"b01\",\"b02\",\"b03\",\"b04\",\"b05\",\"b06\",\"b07\",\"b08\",\"b09\",\"b10\",\"b11\",\"b12\",\"b13\",\"b14\",\"b15\",\"b16\",\"b17\",\"b18\",\"b19\",\"b20\",\"b21\",\"b22\",\"b23\",\"b24\",\"b25\",\"b26\",\"b27\",\"b29\",\"b28\",\"blk_hjFC0E7s\",\"b30\",\"b31\",\"b32\",\"b33\",\"b34\",\"b35\",\"b36\"],\"settings\":{\"toc_position\":\"right\"}}','{\"id\":\"\"}','{\"id\":\"\"}','online','2026-10-09 22:47:14','2026-10-09 18:54:11','2026-10-09 23:19:09'),
(5,'{\"en\":\"Immunization Program\",\"id\":\"Program Imunisasi\"}','{\"en\":\"immunization-program\",\"id\":\"program-imunisasi\"}','{\"blocks\":{\"b01\":{\"id\":\"b01\",\"type\":\"heading\",\"data\":{\"text\":{\"en\":\"Immunization: Safeguarding the Future of Central Papua\'s Children\",\"id\":\"Imunisasi: Melindungi Masa Depan Generasi Papua Tengah\"},\"level\":\"h1\"}},\"b02\":{\"id\":\"b02\",\"type\":\"paragraph\",\"data\":{\"text\":{\"en\":\"<p>The YSBH Immunization Program is committed to helping local governments make sure every child in Central Papua has a shield against vaccine-preventable diseases. Through innovative and culturally sensitive approaches, we work to reduce the number of \\\"zero-dose\\\" children, for a healthy and resilient future for Papua\'s next generation.</p>\",\"id\":\"<p>Program Imunisasi YSBH berkomitmen mendampingi pemerintah daerah memastikan setiap anak di Papua Tengah memiliki perlindungan terhadap penyakit yang dapat dicegah. Melalui pendekatan inovatif dan sensitif budaya, kami berupaya menurunkan jumlah anak zero dose demi masa depan generasi Papua yang sehat dan tangguh.</p>\"}}},\"b06\":{\"id\":\"b06\",\"type\":\"heading\",\"data\":{\"text\":{\"en\":\"Five Pillars of YSBH\'s Immunization Program\",\"id\":\"Lima Pilar Intervensi Program Imunisasi YSBH\"},\"level\":\"h2\"}},\"b07\":{\"id\":\"b07\",\"type\":\"heading\",\"data\":{\"text\":{\"en\":\"1. Strengthening Immunization Regulations and Policies\",\"id\":\"1. Penguatan Regulasi dan Kebijakan Imunisasi\"},\"level\":\"h3\"}},\"b08\":{\"id\":\"b08\",\"type\":\"paragraph\",\"data\":{\"text\":{\"en\":\"<p>We believe that immunization programs last only when they are rooted in strong political and legal commitment at regional level. YSBH gives technical assistance to provincial and district governments to draft a solid legal framework, including a Central Papua Governor\'s Regulation and Regent Regulations in Nabire, Dogiyai, Deiyai and Puncak Jaya. These regulations are crucial for a firm regional budget allocation, better vaccine logistics management, and integrating immunization services into regional development plans.</p>\",\"id\":\"<p>Kami percaya keberlanjutan program imunisasi harus berakar pada komitmen politik dan hukum yang kuat di tingkat daerah. YSBH memberikan pendampingan teknis kepada pemerintah provinsi dan kabupaten untuk menyusun payung hukum yang kuat, termasuk Peraturan Gubernur Papua Tengah serta Peraturan Bupati di Kabupaten Nabire, Dogiyai, Deiyai dan Puncak Jaya. Regulasi ini penting untuk memastikan alokasi anggaran daerah yang pasti, tata kelola logistik vaksin yang lebih baik, dan integrasi layanan imunisasi ke dalam rencana pembangunan daerah.</p>\"}}},\"b10\":{\"id\":\"b10\",\"type\":\"heading\",\"data\":{\"text\":{\"en\":\"2. Development of Model Puskesmas and OJT (On-the-Job Training) Centers\",\"id\":\"2. Pengembangan Puskesmas Model OJT (On-the-Job Training) Center\"},\"level\":\"h3\"}},\"b11\":{\"id\":\"b11\",\"type\":\"paragraph\",\"data\":{\"text\":{\"en\":\"<p>Papua\'s geography makes conventional training extremely costly, while fast rotation of health workers often leaves remote areas without trained staff. In response, YSBH developed Model Puskesmas OJT Centers at strategic locations: Wanggar Sari and Topo Puskesmas (Nabire), Waghete Puskesmas (Deiyai), Moanemani Puskesmas (Dogiyai) and Mulia Puskesmas (Puncak Jaya). These centers are practical learning laboratories where health workers deepen their immunization skills directly in the field.</p>\",\"id\":\"<p>Tantangan geografis Papua membuat biaya pelatihan konvensional sangat mahal, sementara rotasi petugas kesehatan yang cepat sering meninggalkan kekosongan tenaga terlatih di daerah terpencil. Sebagai solusi, YSBH mengembangkan Puskesmas Model OJT Center di lokasi strategis: Puskesmas Wanggar Sari dan Topo (Nabire), Puskesmas Waghete (Deiyai), Puskesmas Moanemani (Dogiyai) dan Puskesmas Mulia (Puncak Jaya). Pusat ini menjadi laboratorium belajar praktis bagi tenaga kesehatan untuk memperdalam keterampilan imunisasi langsung di lapangan.</p>\"}}},\"b12\":{\"id\":\"b12\",\"type\":\"paragraph\",\"data\":{\"text\":{\"en\":\"<p>The model works because it uses the way health workers from remote areas already travel to district capitals for logistics or personal matters. While there, they can join a flexible short internship without being away from their duties for long. This sharply cuts transport costs and builds a sense of belonging and a culture of mutual learning between health centers, for a competent workforce that lasts.</p>\",\"id\":\"<p>Model ini efektif karena memanfaatkan pola mobilitas petugas dari daerah terpencil yang sering ke ibu kota kabupaten untuk urusan logistik atau pribadi. Selama di ibu kota, mereka dapat mengikuti magang singkat dengan waktu fleksibel tanpa meninggalkan tugas terlalu lama. Pendekatan ini menekan biaya transportasi secara signifikan dan membangun keterikatan serta budaya saling belajar antarpuskesmas, demi sumber daya manusia yang kompeten secara berkelanjutan.</p>\"}}},\"b13\":{\"id\":\"b13\",\"type\":\"heading\",\"data\":{\"text\":{\"en\":\"3. Implementing Human-Centered Design (HCD)\",\"id\":\"3. Implementasi Human-Centered Design (HCD)\"},\"level\":\"h3\"}},\"b14\":{\"id\":\"b14\",\"type\":\"paragraph\",\"data\":{\"text\":{\"en\":\"<p>Immunization programs often meet community hesitation or fear about vaccines. Through Human-Centered Design, YSBH works to understand the human and socio-cultural reasons behind low coverage. Instead of one-way education, we sit with parents, community leaders and health workers, listen to their concerns and co-design solutions that fit the local context.</p>\",\"id\":\"<p>Program imunisasi sering menghadapi hambatan berupa keraguan atau ketakutan masyarakat terhadap vaksin. Melalui Human-Centered Design, YSBH berupaya memahami sisi kemanusiaan dan sosial-budaya di balik rendahnya cakupan imunisasi. Kami tidak hanya memberi edukasi satu arah, tetapi duduk bersama orang tua, tokoh masyarakat dan petugas kesehatan untuk mendengarkan kekhawatiran mereka dan merancang bersama solusi yang sesuai dengan konteks lokal.</p>\"}}},\"b15\":{\"id\":\"b15\",\"type\":\"paragraph\",\"data\":{\"text\":{\"en\":\"<p>The aim is immunization services that are friendlier, more empathetic and trusted by Papuan communities. By understanding barriers from the service user\'s side, we can develop risk-communication strategies that resonate and change how people see vaccines. HCD ensures every child receives care that is medically sound and culturally respectful.</p>\",\"id\":\"<p>Pendekatan ini bertujuan menciptakan layanan imunisasi yang lebih ramah, empatik dan dipercaya masyarakat Papua. Dengan memahami hambatan dari sudut pandang pengguna layanan, kami dapat mengembangkan strategi komunikasi risiko yang menyentuh dan mengubah persepsi masyarakat tentang vaksin. HCD memastikan setiap anak mendapat layanan yang bermutu secara medis sekaligus dihargai secara budaya.</p>\"}}},\"b17\":{\"id\":\"b17\",\"type\":\"paragraph\",\"data\":{\"text\":{\"en\":\"<p>Immunization is a shared responsibility that the health sector cannot carry alone. YSBH actively brings together traditional leaders, religious leaders, village governments and the private sector in Central Papua. When a pastor, a tribal chief or a village head speaks up for immunization, the message is accepted far more strongly in the community.</p>\",\"id\":\"<p>Imunisasi adalah tanggung jawab bersama yang tidak bisa dipikul sektor kesehatan sendiri. YSBH aktif menggerakkan kolaborasi lintas sektor yang melibatkan tokoh adat, pemuka agama, pemerintah desa, hingga sektor swasta di Papua Tengah. Ketika seorang pendeta, kepala suku atau kepala desa menyuarakan pentingnya imunisasi, pesan itu jauh lebih kuat diterima masyarakat.</p>\"}}},\"b18\":{\"id\":\"b18\",\"type\":\"paragraph\",\"data\":{\"text\":{\"en\":\"<p>This cooperation includes using village funds to support outreach to target groups, involving religious leaders in outreach at places of worship, and securing logistics support from other sectors. By strengthening this synergy we build a solid support system to protect the next generation, and reach every child, even in the hardest-to-reach areas.</p>\",\"id\":\"<p>Kerja sama ini mencakup pemanfaatan dana desa untuk mendukung penggerakan sasaran, keterlibatan tokoh agama dalam sosialisasi di rumah ibadah, hingga dukungan logistik dari sektor lain. Dengan memperkuat sinergi ini, kami membangun ekosistem pendukung yang kokoh untuk melindungi generasi penerus, dan menjangkau setiap anak, bahkan di wilayah yang paling sulit dijangkau.</p>\"}}},\"b19\":{\"id\":\"b19\",\"type\":\"heading\",\"data\":{\"text\":{\"en\":\"5. Establishing and Strengthening the Provincial Immunization Facilitator Team\",\"id\":\"5. Pembentukan dan Penguatan Tim Fasilitator Imunisasi Provinsi\"},\"level\":\"h3\"}},\"b20\":{\"id\":\"b20\",\"type\":\"paragraph\",\"data\":{\"text\":{\"en\":\"<p>To keep technical assistance high quality and lasting, YSBH helps set up an Immunization Facilitator Team at the Central Papua provincial level. The team is made up of local experts with in-depth knowledge of immunization management, quality monitoring and persuasive communication. They are the main drivers of continuous technical assistance to the supported regencies.</p>\",\"id\":\"<p>Untuk menjamin mutu dan keberlanjutan pendampingan teknis, YSBH memfasilitasi pembentukan Tim Fasilitator Imunisasi di tingkat Provinsi Papua Tengah. Tim ini terdiri dari tenaga ahli lokal yang dibekali pengetahuan mendalam tentang manajemen imunisasi, pemantauan mutu dan teknik komunikasi persuasif. Mereka menjadi penggerak utama bimbingan teknis yang berkelanjutan kepada kabupaten dampingan.</p>\"}}},\"b21\":{\"id\":\"b21\",\"type\":\"paragraph\",\"data\":{\"text\":{\"en\":\"<p>The team aims to build lasting regional independence. With experts ready at provincial level, knowledge reaches health centers faster and more consistently. It is our effort to leave behind a resilient health system and capable local staff who can look after Papuan children\'s health without depending on outside parties.</p>\",\"id\":\"<p>Tim ini bertujuan menciptakan kemandirian daerah dalam jangka panjang. Dengan tim ahli yang siap di tingkat provinsi, alih pengetahuan ke puskesmas berjalan lebih cepat dan konsisten. Ini upaya kami meninggalkan sistem kesehatan yang tangguh dan tenaga lokal yang mumpuni untuk menjaga kesehatan anak-anak Papua tanpa bergantung pada pihak luar.</p>\"}}},\"b22\":{\"id\":\"b22\",\"type\":\"heading\",\"data\":{\"text\":{\"en\":\"What we have done\",\"id\":\"Yang telah kami kerjakan\"},\"level\":\"h2\"}},\"b23\":{\"id\":\"b23\",\"type\":\"paragraph\",\"data\":{\"text\":{\"en\":\"<ul><li><strong>2014, with UNICEF and the Papua Provincial Health Office:</strong> technical guidance, supervision and monitoring of tetanus toxoid immunization for women of reproductive age (TT WUS) and DPT-HB-Hib immunization, toward the elimination of maternal and neonatal tetanus; and the mass bed-net campaign week in 16 malaria-endemic districts.</li><li><strong>2015, with UNICEF:</strong> support for health programs covering immunization, maternal and child health, newborn health, nutrition, malaria and community-led sanitation, by strengthening health centers, health cadres and cross-sector partners, including in remote areas.</li><li><strong>2026, with the Provincial Health Office of Central Papua:</strong> support for health system strengthening in six districts and at provincial level, including expanding immunization coverage and reducing zero-dose children (children who have received no routine vaccine).</li></ul>\",\"id\":\"<ul><li><strong>2014, bersama UNICEF dan Dinas Kesehatan Provinsi Papua:</strong> bimbingan teknis, supervisi, dan monitoring imunisasi TT WUS dan DPT-HB-Hib menuju eliminasi Tetanus Maternal dan Neonatal; serta Pekan Kelambu Massal di 16 kabupaten endemis malaria.</li><li><strong>2015, bersama UNICEF:</strong> dukungan program kesehatan masyarakat yang mencakup imunisasi, kesehatan ibu dan anak, kesehatan neonatus, gizi, pengendalian malaria, dan sanitasi berbasis masyarakat, melalui penguatan puskesmas, kader kesehatan, dan pemangku kepentingan lintas sektor hingga wilayah terpencil.</li><li><strong>2026, bersama Dinas Kesehatan Provinsi Papua Tengah:</strong> dukungan penguatan sistem kesehatan di enam kabupaten dan tingkat provinsi, termasuk peningkatan cakupan imunisasi dan penurunan anak zero dose (anak yang belum menerima imunisasi rutin sama sekali).</li></ul>\"}}},\"b24\":{\"id\":\"b24\",\"type\":\"heading\",\"data\":{\"text\":{\"en\":\"A Humanitarian Investment: Why Should You Care?\",\"id\":\"Investasi Kemanusiaan: Mengapa Anda Harus Peduli?\"},\"level\":\"h2\"}},\"b25\":{\"id\":\"b25\",\"type\":\"paragraph\",\"data\":{\"text\":{\"en\":\"<p>Supporting immunization in Central Papua is an investment with large humanitarian and economic impact. When a child falls ill or becomes disabled from a preventable disease, the effects spread through every part of life: families lose productive time, medical costs rise and, saddest of all, the child\'s future potential is lost to Papuan society.</p>\",\"id\":\"<p>Mendukung program imunisasi di Papua Tengah adalah investasi dengan dampak kemanusiaan dan ekonomi yang sangat besar. Ketika seorang anak sakit atau cacat akibat penyakit yang seharusnya bisa dicegah, dampaknya meluas ke seluruh aspek kehidupan: keluarga kehilangan waktu produktif, biaya pengobatan meningkat, dan yang paling menyedihkan, hilang pula potensi masa depan sang anak sebagai penerus masyarakat Papua.</p>\"}}},\"b26\":{\"id\":\"b26\",\"type\":\"paragraph\",\"data\":{\"text\":{\"en\":\"<p>Every contribution helps us strengthen Model Puskesmas, train local facilitators and make sure vaccines reach children in remote villages. Together we can help Papuan children grow up healthy, bright and ready to lead their own future. Join us in protecting every life, because every Papuan child deserves a healthy future.</p>\",\"id\":\"<p>Setiap dukungan membantu kami memperkuat Puskesmas Model, melatih fasilitator lokal, dan memastikan vaksin sampai ke anak-anak di kampung terpencil. Bersama kita bisa memastikan anak-anak Papua tumbuh sehat, cerdas dan siap memimpin masa depan mereka sendiri. Bergabunglah bersama kami melindungi setiap nyawa, karena setiap anak Papua berhak atas masa depan yang sehat.</p>\"}}},\"blk_3cpQ9Crd\":{\"id\":\"blk_3cpQ9Crd\",\"type\":\"image\",\"data\":{\"url\":\"http://landing-ysbh.test/storage/media/2026/10/oK0kwO9hJF09FeTyjFomtJ8noxBCdVmmVQ1lN9tg.jpg\",\"alt_text\":\"\",\"margin_bottom\":\"mb-4 md:mb-6\",\"caption\":{\"en\":\"\",\"id\":\"\"},\"width\":\"w-screen\",\"align\":\"mx-auto\",\"radius\":\"rounded-none\",\"max_height\":\"max-h-none\",\"object_fit\":\"object-cover\",\"space_y\":\"gap-3\",\"media_id\":9}},\"blk_XcISQIl6\":{\"id\":\"blk_XcISQIl6\",\"type\":\"heading\",\"data\":{\"text\":{\"en\":\"4. Strengthening Cross-Sector Collaboration\",\"id\":\"4. Penguatan Kolaborasi Lintas Sektor\"},\"level\":\"h3\"}},\"blk_mESFTxyW\":{\"id\":\"blk_mESFTxyW\",\"type\":\"image\",\"data\":{\"url\":\"\",\"alt_text\":\"\",\"margin_bottom\":\"mb-4 md:mb-6\",\"caption\":{\"en\":\"\",\"id\":\"\"},\"width\":\"w-full\",\"align\":\"mx-auto\",\"radius\":\"rounded-none\",\"max_height\":\"max-h-none\",\"object_fit\":\"object-cover\",\"space_y\":\"gap-3\"}},\"blk_DQy9WmAK\":{\"id\":\"blk_DQy9WmAK\",\"type\":\"paragraph\",\"data\":{\"text\":{\"en\":\"<p>With clear regulations, immunization is no longer seen as just a routine health activity but as a government obligation to protect the basic rights of Papuan children. These policies also aim to secure long-term support from decision-makers, so the program stays stable through changes in leadership or regional politics.</p>\",\"id\":\"<p>Melalui regulasi yang jelas, imunisasi tidak lagi dipandang sebagai kegiatan kesehatan rutin semata, tetapi sebagai kewajiban pemerintah untuk melindungi hak dasar anak-anak Papua. Kebijakan ini juga bertujuan mengunci dukungan jangka panjang dari para pengambil keputusan, sehingga program tetap berjalan stabil meski terjadi pergantian kepemimpinan atau dinamika politik di daerah.</p>\"}}},\"blk_0ct9fdaC\":{\"id\":\"blk_0ct9fdaC\",\"type\":\"image\",\"data\":{\"url\":\"http://landing-ysbh.test/storage/media/2026/10/nK3PbJ3C1w0f2eRh27LaSEDKv9a9PrYSJ03Na5pq.jpg\",\"alt_text\":\"\",\"margin_bottom\":\"mb-4 md:mb-6\",\"caption\":{\"en\":\"Pelayanan imunisasi melibatkan berbagai persona untuk menciptakan layanan sesuai kebutuhan, budaya dan harapan masyarakat di Kabupaten Deiyai.\",\"id\":\"Pelayanan imunisasi  melibatkan berbagai persona untuk menciptakan layanan sesuai kebutuhan, budaya dan harapan masyarakat di Kabupaten Deiyai.\"},\"width\":\"w-3/4\",\"align\":\"mx-auto\",\"radius\":\"rounded-none\",\"max_height\":\"max-h-none\",\"object_fit\":\"object-cover\",\"space_y\":\"gap-3\",\"media_id\":13}},\"blk_C3rR4fls\":{\"id\":\"blk_C3rR4fls\",\"type\":\"image\",\"data\":{\"url\":\"http://landing-ysbh.test/storage/media/2026/10/vVi1b4DTyFImtxp8NHTTeVOH4sdv9MkaRuOLbPO0.jpg\",\"alt_text\":\"\",\"margin_bottom\":\"mb-4 md:mb-6\",\"caption\":{\"en\":\"Proses penyusunan rancangan Peraturan Gubernur Papua Tengah tentang Penyelenggaraan Imunisasi\",\"id\":\"Proses penyusunan draf Peraturan Gubernur Papua Tengah tentang Penyelenggaraan Imunisasi \"},\"width\":\"w-3/4\",\"align\":\"mx-auto\",\"radius\":\"rounded-none\",\"max_height\":\"max-h-[40rem]\",\"object_fit\":\"object-cover\",\"space_y\":\"gap-3\",\"media_id\":11}},\"blk_Fbmv8zon\":{\"id\":\"blk_Fbmv8zon\",\"type\":\"multi-columns\",\"data\":{\"col_count\":2,\"mobile_reverse\":false,\"align_x\":\"items-start\",\"align_y\":\"justify-start\",\"gap\":\"gap-4\",\"col_1_zone\":[\"blk_ddKkw94F\",\"blk_j1Ymv6vQ\",\"blk_dVu31EIv\"],\"col_2_zone\":[\"blk_xvYUocPu\"],\"col_3_zone\":[],\"col_4_zone\":[],\"col_5_zone\":[],\"col_6_zone\":[]}},\"blk_ddKkw94F\":{\"id\":\"blk_ddKkw94F\",\"type\":\"heading\",\"data\":{\"text\":{\"en\":\"We Take Action Amid the Immunization Crisis\",\"id\":\"\"},\"level\":\"h2\",\"margin_bottom\":\"mb-4 md:mb-6\"}},\"blk_j1Ymv6vQ\":{\"id\":\"blk_j1Ymv6vQ\",\"type\":\"paragraph\",\"data\":{\"text\":{\"en\":\"<p>The immunization situation in Central Papua Province demands urgent attention from all stakeholders. As of 2024, Complete Basic Immunization coverage had reached only 12%, a drastic decline from the year before. The result is the return of deadly outbreaks such as measles, polio and diphtheria, which have repeatedly hit children in recent years.</p>\",\"id\":\"<p>Situasi imunisasi di Provinsi Papua Tengah memerlukan perhatian mendesak dari seluruh pemangku kepentingan. Hingga tahun 2024, cakupan Imunisasi Dasar Lengkap (IDL) baru mencapai 12%, turun drastis dibandingkan tahun sebelumnya. Dampaknya, wabah penyakit mematikan seperti campak, polio dan difteri muncul kembali dan silih berganti menyerang anak-anak dalam beberapa tahun terakhir.</p>\"},\"margin_bottom\":\"mb-4 md:mb-6\"}},\"blk_dVu31EIv\":{\"id\":\"blk_dVu31EIv\",\"type\":\"paragraph\",\"data\":{\"text\":{\"en\":\"<p>This gap is not just a statistic; it is a real threat to the survival of Papuan society. Every outbreak brings deep grief to families and a heavy economic burden. Without systematic and sustained intervention, we risk losing the generation that will build Papua\'s future.</p>\",\"id\":\"<p>Ketertinggalan ini bukan sekadar angka, melainkan ancaman nyata bagi keberlangsungan masyarakat Papua. Setiap wabah membawa duka mendalam bagi keluarga dan beban ekonomi yang besar. Tanpa intervensi yang sistematis dan berkelanjutan, kita berisiko kehilangan generasi yang akan membangun Tanah Papua di masa depan.</p>\"},\"margin_bottom\":\"mb-4 md:mb-6\"}},\"blk_xvYUocPu\":{\"id\":\"blk_xvYUocPu\",\"type\":\"image\",\"data\":{\"url\":\"http://landing-ysbh.test/storage/media/2026/10/LVBJbcDuVAtBotkRobtzrSvUSHFoWkpZwEnOhem5.jpg\",\"alt_text\":\"\",\"margin_bottom\":\"mb-4 md:mb-6\",\"caption\":{\"en\":\"YSBH and Puncak Jaya Health Office navigating difficult terrain to ensure immunization access in remote areas.\",\"id\":\"YSBH dan Dinas Kesehatan Puncak Jaya menempuh medan yang sulit demi memastikan akses imunisasi di daerah terpencil.\"},\"width\":\"w-full\",\"align\":\"mx-auto\",\"radius\":\"rounded-none\",\"max_height\":\"max-h-none\",\"object_fit\":\"object-cover\",\"space_y\":\"gap-3\",\"media_id\":10}},\"blk_SaxXAket\":{\"id\":\"blk_SaxXAket\",\"type\":\"eyebrow\",\"data\":{\"text\":{\"en\":\"title\",\"id\":\"title\"},\"icon\":\"newspaper\",\"color\":\"#E42326\",\"margin_bottom\":\"mb-4 md:mb-6\"}},\"blk_WLWMzkOt\":{\"id\":\"blk_WLWMzkOt\",\"type\":\"accordion-builder\",\"data\":{\"style\":\"boxed\",\"color\":\"foresty\",\"marker\":\"chevron\",\"first_open\":false,\"allow_multiple\":false,\"schema\":false,\"items\":[{\"id\":\"itm_1641aacd\",\"question\":{\"en\":\"question\",\"id\":\"\"},\"answer\":{\"en\":\"answer\",\"id\":\"\"}}]}}},\"order\":[\"blk_3cpQ9Crd\",\"b01\",\"b02\",\"blk_Fbmv8zon\",\"b06\",\"b08\",\"b07\",\"blk_DQy9WmAK\",\"blk_C3rR4fls\",\"b10\",\"b11\",\"b12\",\"blk_0ct9fdaC\",\"b13\",\"b14\",\"b15\",\"blk_mESFTxyW\",\"blk_XcISQIl6\",\"b17\",\"b18\",\"b19\",\"b20\",\"b21\",\"b22\",\"b23\",\"blk_SaxXAket\",\"b24\",\"b25\",\"b26\",\"blk_WLWMzkOt\"],\"settings\":{\"toc_position\":\"right\"}}','{\"id\":\"\"}','{\"id\":\"\"}','online','2026-10-09 22:47:26','2026-10-09 18:54:11','2026-10-09 23:44:48'),
(6,'{\"en\":\"Maternal and Child Health Program\",\"id\":\"Program Kesehatan Ibu dan Anak\"}','{\"en\":\"maternal-child-health-program\",\"id\":\"program-kesehatan-ibu-anak\"}','{\"blocks\":{\"b01\":{\"id\":\"b01\",\"type\":\"heading\",\"data\":{\"text\":{\"en\":\"Maternal and Child Health\",\"id\":\"Kesehatan Ibu dan Anak (KIA)\"},\"level\":\"h1\"}},\"b02\":{\"id\":\"b02\",\"type\":\"paragraph\",\"data\":{\"text\":{\"en\":\"<p>With YSBH\'s support, local governments are working to make sure pregnant women, newborns, children and adolescents in all supported areas, including remote ones, have fair access to high-quality primary health care, especially in marginalized and underserved villages (<em>kampung</em>).</p>\",\"id\":\"<p>Dengan dukungan YSBH, pemerintah daerah berupaya memastikan ibu hamil, bayi baru lahir, anak-anak dan remaja di seluruh wilayah dukungan, termasuk daerah terpencil, memiliki akses ke layanan kesehatan primer yang adil dan bermutu tinggi, khususnya di kampung-kampung yang terpinggirkan dan kurang terlayani.</p>\"}}},\"b03\":{\"id\":\"b03\",\"type\":\"paragraph\",\"data\":{\"text\":{\"en\":\"<p>The supported areas span several provinces and regencies in Papua: Papua, Highland Papua and Central Papua Provinces, and the regencies of Sarmi, Pegunungan Bintang, Yahukimo, Nabire, Mimika, Paniai, Deiyai, Dogiyai and Puncak Jaya. Their unique geographical challenges are met by strengthening local health workers, <em>posyandu</em> (integrated health post) volunteers and Puskesmas (public health centers). This capacity building is meant to plan, carry out, monitor and deliver the essential services that keep mothers and newborns safe and healthy.</p>\",\"id\":\"<p>Wilayah dukungan tersebar di beberapa provinsi dan kabupaten di Tanah Papua: Provinsi Papua, Papua Pegunungan dan Papua Tengah, serta Kabupaten Sarmi, Pegunungan Bintang, Yahukimo, Nabire, Mimika, Paniai, Deiyai, Dogiyai dan Puncak Jaya. Tantangan geografisnya yang unik dijawab dengan memperkuat kapasitas tenaga medis lokal, kader posyandu dan puskesmas. Penguatan ini bertujuan merencanakan, melaksanakan, memantau dan memberikan layanan esensial bagi keselamatan dan kesehatan ibu dan bayi baru lahir.</p>\"}}},\"b04\":{\"id\":\"b04\",\"type\":\"paragraph\",\"data\":{\"text\":{\"en\":\"<p>The program also focuses on preventing, controlling and eliminating the main childhood illnesses, especially those that often cause child deaths: pneumonia and diarrhea, malnutrition (stunting), vaccine-preventable diseases, and endemic infectious diseases such as malaria. These goals are pursued by building resilient health systems that stay responsive to emergencies and local conditions, especially through Model Puskesmas that also serve as Puskesmas On-the-Job Training (OJT) Centers in each supported regency.</p>\",\"id\":\"<p>Program ini juga berfokus pada pencegahan, pengendalian dan eliminasi penyakit utama pada anak, terutama yang sering menyebabkan kematian anak: pneumonia dan diare, malnutrisi (stunting), penyakit yang dapat dicegah dengan imunisasi (PD3I), serta penyakit menular endemik seperti malaria. Tujuan ini diwujudkan lewat sistem kesehatan yang tangguh dan tetap responsif terhadap keadaan darurat dan situasi lokal, terutama di Puskesmas Model yang juga berfungsi sebagai Puskesmas On the Job Training (OJT) Center di setiap kabupaten dampingan.</p>\"}}},\"b05\":{\"id\":\"b05\",\"type\":\"paragraph\",\"data\":{\"text\":{\"en\":\"<p>The program has four main components: Maternal and Newborn Care, Child Health and Nutrition, Strengthening Community-Based Health Systems, and Health in Emergencies. They are carried out in selected districts using approaches that fit the socio-cultural context of Papuan communities, in the hope that local governments and other sectors will adopt and scale them up to save the next generation of Papua.</p>\",\"id\":\"<p>Program KIA terdiri dari empat komponen utama: Perawatan Ibu Hamil dan Bayi Baru Lahir, Kesehatan dan Gizi Anak, Penguatan Sistem Kesehatan Berbasis Komunitas, serta Kesehatan dalam Keadaan Darurat. Program dijalankan di distrik terpilih dengan pendekatan yang sesuai dengan konteks sosial-budaya masyarakat Papua, dengan harapan pemerintah daerah dan lintas sektor mengadopsi dan memperluasnya demi menyelamatkan generasi masa depan Papua.</p>\"}}},\"b06\":{\"id\":\"b06\",\"type\":\"heading\",\"data\":{\"text\":{\"en\":\"Newborn Care\",\"id\":\"Perawatan Bayi Baru Lahir\"},\"level\":\"h2\"}},\"b07\":{\"id\":\"b07\",\"type\":\"paragraph\",\"data\":{\"text\":{\"en\":\"<p>The first 28 days of life (the neonatal period) are the most critical for a child\'s survival. Newborns face a high risk of death and disability, largely rooted in poor maternal health during pregnancy, too few standard antenatal care (ANC) visits and inadequate delivery care. National data show that most deaths of children under five happen in the neonatal period (0–28 days). Reducing the Neonatal Mortality Rate therefore cannot be separated from improving maternal health and giving integrated medical care from pregnancy through the postpartum period.</p>\",\"id\":\"<p>Periode 28 hari pertama kehidupan (neonatal) adalah fase paling kritis bagi kelangsungan hidup anak. Bayi baru lahir menghadapi risiko tinggi kematian dan kecacatan, yang sebagian besar berakar dari kesehatan ibu yang kurang optimal selama kehamilan, kurangnya kunjungan antenatal (ANC) standar, dan penanganan persalinan yang tidak memadai. Data nasional menunjukkan sebagian besar kematian balita terjadi pada masa neonatal (0–28 hari). Penurunan Angka Kematian Neonatal (AKN) karena itu tidak dapat dipisahkan dari upaya meningkatkan kesehatan ibu dan intervensi medis terpadu sejak kehamilan hingga pascapersalinan.</p>\"}}},\"b08\":{\"id\":\"b08\",\"type\":\"paragraph\",\"data\":{\"text\":{\"en\":\"<p>Saving newborns needs integrated service standards: preventing delivery complications, managing asphyxia, treating severe infections such as neonatal sepsis, and giving targeted care to vulnerable infants. Recent evidence confirms that most deaths in this phase come from conditions that can be prevented and treated with simple, cost-efficient care at primary health facilities. The main areas are:</p>\",\"id\":\"<p>Menyelamatkan bayi baru lahir membutuhkan pemenuhan standar pelayanan terpadu: mencegah komplikasi persalinan, menangani asfiksia, menangani infeksi berat seperti sepsis neonatal, dan memberi intervensi khusus bagi bayi rentan. Bukti ilmiah terbaru menegaskan bahwa sebagian besar kematian pada fase ini disebabkan kondisi yang sebenarnya dapat dicegah dan diobati dengan intervensi sederhana dan hemat biaya di fasilitas kesehatan primer. Fokus utamanya:</p>\"}}},\"b09\":{\"id\":\"b09\",\"type\":\"paragraph\",\"data\":{\"text\":{\"en\":\"<ul><li>Low birth weight and prematurity</li><li>Hypothermia</li><li>Pneumonia and neonatal infections</li><li>Exclusive breastfeeding and early initiation of breastfeeding</li></ul>\",\"id\":\"<ul><li>Berat Badan Lahir Rendah (BBLR) dan prematuritas</li><li>Hipotermia</li><li>Pneumonia dan infeksi neonatal</li><li>Optimalisasi ASI eksklusif dan Inisiasi Menyusu Dini (IMD)</li></ul>\"}}},\"b10\":{\"id\":\"b10\",\"type\":\"paragraph\",\"data\":{\"text\":{\"en\":\"<p>Through YSBH\'s technical assistance, local governments have been introduced to the Point of Care Quality Improvement (POCQI) approach and collaborative Quality Improvement (QI). YSBH also helps local governments develop Governor and Regent Regulations and Regional Action Plans (RAD) to strengthen Maternal and Child Health services and speed up the reduction of maternal and child deaths, especially newborn deaths.</p>\",\"id\":\"<p>Melalui pendampingan teknis YSBH, pemerintah daerah telah diperkenalkan dengan pendekatan Point of Care Quality Improvement (POCQI) dan Quality Improvement (QI) kolaboratif. YSBH juga membantu pemerintah daerah menyusun Peraturan Gubernur/Bupati beserta Rencana Aksi Daerah (RAD) Penguatan Layanan KIA dan Percepatan Penurunan Angka Kematian Ibu dan Anak, terutama bayi baru lahir.</p>\"}}},\"b11\":{\"id\":\"b11\",\"type\":\"heading\",\"data\":{\"text\":{\"en\":\"Child Health\",\"id\":\"Kesehatan Anak\"},\"level\":\"h2\"}},\"b12\":{\"id\":\"b12\",\"type\":\"paragraph\",\"data\":{\"text\":{\"en\":\"<p>Pneumonia and diarrhea remain the biggest infectious threats to the health and survival of infants and children worldwide. According to WHO global data and the IVAC Progress Report, the two diseases together account for about 23% of all deaths of children under five, equal to 1.17 million deaths a year. UNICEF statistics confirm that pneumonia alone takes the lives of more than 700,000 children a year, making it the deadliest infectious disease for young children globally.</p>\",\"id\":\"<p>Pneumonia dan diare tetap menjadi ancaman infeksi terbesar bagi kesehatan dan kelangsungan hidup bayi dan anak di seluruh dunia. Menurut data global WHO dan Laporan Progress IVAC, kedua penyakit ini bersama-sama menyumbang sekitar 23% kematian balita, setara 1,17 juta kematian anak di bawah lima tahun setiap tahun. Statistik UNICEF menegaskan bahwa pneumonia sendiri merenggut nyawa lebih dari 700.000 anak per tahun, menjadikannya penyakit infeksi paling mematikan bagi balita di dunia.</p>\"}}},\"b13\":{\"id\":\"b13\",\"type\":\"paragraph\",\"data\":{\"text\":{\"en\":\"<p>In Indonesia the pattern is similar: pneumonia is the leading cause of death among children under five, followed closely by diarrhea. According to the Ministry of Health\'s Accountability Report, the national Under-Five Mortality Rate has been brought down to 19.83 per 1,000 live births, already below the global SDG 2030 target of 25 per 1,000 live births.</p>\",\"id\":\"<p>Di Indonesia, tren serupa menempatkan pneumonia sebagai penyebab utama kematian balita, disusul diare di urutan kedua. Menurut Laporan Akuntabilitas Kemenkes, Angka Kematian Balita (AKBA) nasional telah ditekan hingga 19,83 per 1.000 kelahiran hidup, melampaui ambang target global SDGs 2030 sebesar 25 per 1.000 kelahiran hidup.</p>\"}}},\"b14\":{\"id\":\"b14\",\"type\":\"paragraph\",\"data\":{\"text\":{\"en\":\"<p>YSBH gives technical assistance to local governments to develop Governor and Regent Regulations and Regional Action Plans (RAD) for Pneumonia and Diarrhea Control that bind cross-sector support. It also builds the capacity of health workers to carry out Integrated Management of Childhood Illness (IMCI, <em>MTBS</em>) and IMCI clinical mentoring (<em>Kalakarya MTBS</em>), and of volunteers to carry out community-based IMCI (<em>MTBS-M</em>), under Ministry of Health Regulations No. 70/2013 and No. 41/2018.</p>\",\"id\":\"<p>YSBH memberi bantuan teknis kepada pemerintah daerah untuk menyusun Peraturan Gubernur/Bupati beserta Rencana Aksi Daerah (RAD) Penanggulangan Pneumonia dan Diare guna mengikat dukungan lintas sektor. YSBH juga meningkatkan kapasitas tenaga kesehatan dalam melaksanakan Manajemen Terpadu Balita Sakit (MTBS) dan Kalakarya MTBS, serta kapasitas kader dalam melaksanakan MTBS berbasis masyarakat (MTBS-M) yang dinaungi Permenkes No. 70/2013 dan No. 41/2018.</p>\"}}},\"b15\":{\"id\":\"b15\",\"type\":\"paragraph\",\"data\":{\"text\":{\"en\":\"<p>[ISI-DULU] The source document announces four components but covers only Newborn Care and Child Health. Text is still needed for Maternal Care of pregnant women, Strengthening Community-Based Health Systems, and Health in Emergencies.</p>\",\"id\":\"<p>[ISI-DULU] Dokumen sumber menyebut empat komponen tetapi baru menguraikan Perawatan Bayi Baru Lahir dan Kesehatan Anak. Masih perlu teks untuk Perawatan Ibu Hamil, Penguatan Sistem Kesehatan Berbasis Komunitas, dan Kesehatan dalam Keadaan Darurat.</p>\"}}},\"b16\":{\"id\":\"b16\",\"type\":\"heading\",\"data\":{\"text\":{\"en\":\"What we have done\",\"id\":\"Yang telah kami kerjakan\"},\"level\":\"h2\"}},\"b17\":{\"id\":\"b17\",\"type\":\"paragraph\",\"data\":{\"text\":{\"en\":\"<ul><li><strong>2015, with UNICEF:</strong> monitoring and supervision of newborn health services and of community-based management of childhood illness (MTBS-M) in remote villages; training curricula and modules; provincial and district working groups for MTBS-M and for water and sanitation.</li><li><strong>2016–2017, with UNICEF (Papua province):</strong> improving the quality of maternal and newborn health services by strengthening the referral system, building health worker capacity, mentoring health facilities, and advocating and monitoring health policy at provincial and district level, together with provincial and district health offices and referral hospitals.</li><li><strong>2016–2017, with UNICEF:</strong> Integrated Micro-Planning (IMP) at health center level, strengthening evidence-based planning, budgeting and monitoring of health services toward universal health coverage.</li><li><strong>2026, with the Pegunungan Bintang District Health Office:</strong> integrated, cross-sector support to accelerate the reduction of maternal and infant mortality and stunting. It covers antenatal care, safe delivery, essential newborn care through a collaborative quality-improvement approach, and integrated management of sick children, with planning based on village data.</li></ul>\",\"id\":\"<ul><li><strong>2015, bersama UNICEF:</strong> monitoring dan supervisi layanan kesehatan neonatus serta Manajemen Terpadu Balita Sakit Berbasis Masyarakat (MTBS-M) di kampung terpencil; penyusunan kurikulum dan modul pelatihan; pembentukan kelompok kerja MTBS-M dan AMPL (air minum dan penyehatan lingkungan) di tingkat provinsi dan kabupaten.</li><li><strong>2016–2017, bersama UNICEF (Provinsi Papua):</strong> peningkatan kualitas layanan kesehatan ibu dan bayi baru lahir melalui penguatan sistem rujukan, peningkatan kapasitas tenaga kesehatan, pendampingan fasilitas kesehatan, serta advokasi dan monitoring kebijakan kesehatan di tingkat provinsi dan kabupaten, bersama dinas kesehatan provinsi dan kabupaten serta rumah sakit rujukan.</li><li><strong>2016–2017, bersama UNICEF:</strong> Integrated Micro Planning (IMP) di tingkat puskesmas untuk meningkatkan perencanaan, penganggaran, dan pemantauan layanan kesehatan berbasis bukti menuju Universal Health Coverage (UHC).</li><li><strong>2026, bersama Dinas Kesehatan Kabupaten Pegunungan Bintang:</strong> intervensi terpadu lintas sektor dan lintas level untuk mempercepat penurunan angka kematian ibu dan bayi serta stunting. Mencakup ANC terpadu, persalinan aman, layanan esensial bayi baru lahir melalui pendekatan QI kolaboratif, dan Manajemen Terpadu Balita Sakit (MTBS), dengan perencanaan berbasis data kampung.</li></ul>\"}}}},\"order\":[\"b01\",\"b02\",\"b03\",\"b04\",\"b05\",\"b06\",\"b07\",\"b08\",\"b09\",\"b10\",\"b11\",\"b12\",\"b13\",\"b14\",\"b15\",\"b16\",\"b17\"],\"settings\":{\"toc_position\":\"right\"}}','{\"id\":\"\"}','{\"id\":\"\"}','online','2026-10-09 18:56:02','2026-10-09 18:54:11','2026-10-09 18:56:02'),
(7,'{\"en\":\"Tuberculosis (TB) Program\",\"id\":\"Program Tuberkulosis (TBC)\"}','{\"en\":\"tb-program\",\"id\":\"program-tbc\"}','{\"blocks\":{\"b01\":{\"id\":\"b01\",\"type\":\"heading\",\"data\":{\"text\":{\"en\":\"Integrated Tuberculosis (TB) Program\",\"id\":\"Program Tuberkulosis (TBC) Terpadu\"},\"level\":\"h1\"}},\"b02\":{\"id\":\"b02\",\"type\":\"paragraph\",\"data\":{\"text\":{\"en\":\"<p>The TB program supported by YSBH helps local governments make sure everyone, including pregnant women, newborns, children and adolescents, has access to fair, high-quality TB screening that is integrated with the HIV and Maternal and Child Health programs. This covers all supported areas, including remote ones, and focuses on the service areas of the intervention (model) Puskesmas, including marginalized and underserved villages in their catchments.</p>\",\"id\":\"<p>Program TBC dukungan YSBH membantu pemerintah daerah memastikan semua masyarakat, termasuk ibu hamil, bayi baru lahir, anak-anak dan remaja, memiliki akses ke layanan pemeriksaan TBC yang adil dan bermutu tinggi serta terintegrasi, terutama dengan program HIV dan KIA. Ini mencakup seluruh wilayah dukungan, termasuk daerah terpencil, dengan fokus di wilayah kerja Puskesmas intervensi (Puskesmas Model), termasuk kampung-kampung yang terpinggirkan dan kurang terlayani di wilayah kerja puskesmas tersebut.</p>\"}}},\"b03\":{\"id\":\"b03\",\"type\":\"paragraph\",\"data\":{\"text\":{\"en\":\"<p>A 2014 national health survey put national TB prevalence at 382 per 100,000 people and TB deaths at 94 per 100,000. In the latest national TB program report (2025), the provinces of Papua are among those with the highest TB prevalence in the country, both in the general population and in special groups such as pregnant women and children. Nationally, TB prevalence ranks second highest in the world after India (WHO Global Report 2024). HIV is widespread in Papua and no area is free of cases, which indirectly points to high and widespread TB as well.</p>\",\"id\":\"<p>Menurut survei kesehatan nasional tahun 2014, prevalensi TBC nasional sebesar 382 per 100.000 penduduk dan kematian karena TBC sebesar 94 per 100.000 penduduk. Dari laporan program TBC nasional terkini (2025), provinsi-provinsi di Tanah Papua termasuk yang prevalensi TBC-nya tertinggi secara nasional, baik di populasi umum maupun di kelompok khusus seperti ibu hamil dan anak. Prevalensi TBC nasional menempati posisi kedua tertinggi di dunia setelah India menurut laporan global WHO 2024. HIV telah menjadi epidemi meluas di Tanah Papua dan tidak ada daerah yang bebas kasus HIV, yang secara tidak langsung menunjukkan bahwa kasus TBC juga tinggi dan meluas.</p>\"}}},\"b04\":{\"id\":\"b04\",\"type\":\"paragraph\",\"data\":{\"text\":{\"en\":\"<p>The supported areas span Papua, Highland Papua and Central Papua Provinces and the regencies of Sarmi, Pegunungan Bintang, Yahukimo, Nabire, Mimika, Paniai, Deiyai, Dogiyai and Puncak Jaya. Their geographical challenges are met by strengthening local health workers and <em>posyandu</em> volunteers in the catchments of model Puskesmas, so they can plan, carry out, monitor, educate on and deliver comprehensive, integrated TB services, including for mothers and newborns, the most vulnerable groups.</p>\",\"id\":\"<p>Wilayah dukungan tersebar di Provinsi Papua, Papua Pegunungan dan Papua Tengah serta Kabupaten Sarmi, Pegunungan Bintang, Yahukimo, Nabire, Mimika, Paniai, Deiyai, Dogiyai dan Puncak Jaya. Tantangan geografisnya dijawab dengan memperkuat kapasitas tenaga medis lokal dan kader posyandu di wilayah kerja Puskesmas Model, agar dapat merencanakan, melaksanakan, memantau, mengedukasi dan memberikan layanan TBC yang menyeluruh dan terpadu, termasuk bagi ibu dan bayi baru lahir yang paling rentan.</p>\"}}},\"b05\":{\"id\":\"b05\",\"type\":\"paragraph\",\"data\":{\"text\":{\"en\":\"<p>YSBH\'s support focuses on TB prevention, control and elimination, a national priority with a 2030 target: TB incidence down 80% from a baseline of 326 to 65 per 100,000 people, and TB deaths down 90% from 94 to 6 per 100,000. TB control is closely tied to Maternal and Child Health, through prevention of mother-to-child transmission (PMTCT) and Integrated Management of Childhood Illness (IMCI), and to communicable disease control, through TB and its integration with HIV and STI services. This takes cooperation among three divisions: Disease Prevention and Control, Public Health, and Health Services. The goals are pursued through resilient health systems that stay responsive to emergencies and local conditions, especially in Model Puskesmas that also serve as On-the-Job Training (OJT) Centers in each supported regency.</p>\",\"id\":\"<p>Dukungan YSBH difokuskan pada pencegahan, pengendalian dan eliminasi TBC, program prioritas nasional dengan target 2030: insiden TBC turun 80% dari baseline 326 menjadi 65 per 100.000 penduduk, dan kematian karena TBC turun 90% dari 94 menjadi 6 per 100.000. Penanggulangan TBC sangat berkaitan dengan program KIA melalui Pencegahan Penularan dari Ibu ke Anak (PPIA) dan MTBS, serta dengan Penanggulangan dan Pencegahan Penyakit Menular (P2PM) melalui program TBC dan integrasinya dengan HIV-IMS. Ini merupakan kerja sama tiga bidang: Bidang P2P, Bidang Kesmas dan Bidang Yankes. Tujuannya diwujudkan lewat sistem kesehatan yang tangguh dan tetap responsif terhadap keadaan darurat dan situasi lokal, terutama di Puskesmas Model yang juga berfungsi sebagai Puskesmas On the Job Training (OJT) Center di setiap kabupaten dampingan.</p>\"}}},\"b06\":{\"id\":\"b06\",\"type\":\"paragraph\",\"data\":{\"text\":{\"en\":\"<p>The program covers TB screening and treatment in the general population and key populations, and in mothers and children. It runs in selected districts with model Puskesmas, using approaches that fit the socio-cultural context of Papuan communities, in the hope that local governments and other sectors will adopt and scale them up to other health centers and hospitals.</p>\",\"id\":\"<p>Program mencakup pemeriksaan (skrining) dan pengobatan TBC di populasi umum dan populasi kunci, serta di kelompok ibu dan anak. Program dijalankan di distrik terpilih dengan Puskesmas Model, memakai pendekatan yang sesuai dengan konteks sosial-budaya masyarakat Papua, dengan harapan pemerintah daerah dan lintas sektor mengadopsi dan memperluasnya ke puskesmas dan rumah sakit lain demi menyelamatkan generasi masa depan Papua.</p>\"}}},\"b07\":{\"id\":\"b07\",\"type\":\"image\",\"data\":{\"url\":\"\",\"alt_text\":\"\",\"caption\":{\"en\":\"YSBH\'s integrated technical guidance team, with the Nabire District Health Office and the Central Papua Provincial Health Office, running OJT on TB and TB-HIV recording and reporting at Wanggar Puskesmas, Nabire. 6 August 2026.\",\"id\":\"Tim bimbingan teknis terpadu YSBH, bersama Dinas Kesehatan Kabupaten Nabire dan Dinas Kesehatan Provinsi Papua Tengah, melakukan OJT pencatatan dan pelaporan TB dan TB-HIV di Puskesmas Wanggar, Kabupaten Nabire. 6 Agustus 2026.\"},\"width\":\"w-full\",\"align\":\"mx-auto\",\"radius\":\"rounded-lg\",\"height\":\"h-auto\",\"max_height\":\"max-h-[32rem]\",\"object_fit\":\"object-cover\",\"margin_bottom\":\"mb-4 md:mb-6\"}},\"b08\":{\"id\":\"b08\",\"type\":\"heading\",\"data\":{\"text\":{\"en\":\"TB Screening and Treatment in the General and Key Populations\",\"id\":\"Pemeriksaan (Skrining) dan Pengobatan TBC di Populasi Umum dan Populasi Kunci\"},\"level\":\"h2\"}},\"b09\":{\"id\":\"b09\",\"type\":\"paragraph\",\"data\":{\"text\":{\"en\":\"<p>TB screening is often done together with HIV screening, as part of a national One-Stop Services approach: everyone screened for TB is also screened for HIV, and the other way round. For pregnant women, integrated screening for HIV, syphilis, hepatitis B, TB and malaria is routine and mandatory at the first antenatal visit (ANC1) at a health facility. The aim is to reduce illness among pregnant women and stop these diseases passing to the baby during pregnancy or birth.</p>\",\"id\":\"<p>Skrining TBC sering dilakukan bersamaan dengan skrining HIV karena menjadi bagian dari program nasional dengan pendekatan One Stop Services: setiap orang yang diskrining TBC sekaligus diskrining HIV, dan sebaliknya. Pada ibu hamil, skrining terpadu HIV, sifilis, hepatitis B, TBC dan malaria bersifat rutin dan wajib pada pemeriksaan kehamilan pertama (ANC 1 atau K1) di fasilitas pelayanan kesehatan. Tujuannya menekan angka kesakitan ibu hamil dan mencegah penularan penyakit-penyakit tersebut kepada janin dalam kandungan maupun saat bayi lahir.</p>\"}}},\"b10\":{\"id\":\"b10\",\"type\":\"paragraph\",\"data\":{\"text\":{\"en\":\"<p>National data show that the provinces of Papua still have the highest prevalence of HIV, syphilis and hepatitis B (the \\\"triple elimination\\\" diseases) and the highest malaria incidence. High prevalence of these diseases suggests high TB prevalence too. National data for 2025 put TB incidence in the general population of these provinces at 1,170 per 100,000, a figure from modeling by academics (University of Indonesia) and WHO that takes into account household crowding, poverty, HIV burden and the number of older people. TB cases actually notified in Papua in 2025 are still far below the number WHO and the Ministry of Health expect should be found. Coverage of household contact investigation and of TB preventive therapy (TPT) is also very low, especially for people most at risk, such as children under five and people living with HIV. TB exposure risk in the general and key populations therefore stays high, and in the end it reaches the most vulnerable people in the community: mothers and children.</p>\",\"id\":\"<p>Data nasional menunjukkan provinsi-provinsi di Tanah Papua masih memiliki prevalensi HIV, sifilis dan hepatitis B (triple eliminasi) tertinggi, serta insiden malaria tertinggi. Bila prevalensi tiga penyakit triple eliminasi tinggi, prevalensi TBC diperkirakan juga tinggi. Data nasional 2025 melaporkan angka insiden TBC di populasi umum di provinsi-provinsi Tanah Papua sebesar 1.170 per 100.000 penduduk, hasil pemodelan akademisi (UI) dan WHO dengan mempertimbangkan kepadatan hunian dalam satu rumah, angka kemiskinan, beban HIV, jumlah lansia dan lainnya. Kasus TBC yang ternotifikasi di Tanah Papua pada 2025 masih jauh dari angka yang diperkirakan WHO dan Kementerian Kesehatan seharusnya ditemukan. Cakupan investigasi kontak (IK) serumah dengan pasien TBC dan pemberian Terapi Pencegahan TBC (TPT) juga masih sangat rendah, terutama bagi yang rentan tertular seperti balita dan ODHIV. Risiko paparan TBC di populasi umum dan populasi kunci karena itu tetap tinggi, dan pada akhirnya berdampak pada kelompok paling rentan di komunitas, yaitu ibu dan anak.</p>\"}}},\"b11\":{\"id\":\"b11\",\"type\":\"paragraph\",\"data\":{\"text\":{\"en\":\"<p>Testing that is correct, on time and to standard decides what happens next. It relies mainly on the Rapid Molecular Test (TCM) at primary and referral facilities that have the machine. Not every facility does, so other options are used: acid-fast bacilli (AFB) microscopy, chest X-ray or the tuberculin skin test (Mantoux). Good testing allows an accurate TB diagnosis and immediate, appropriate treatment, so further spread can be controlled quickly.</p>\",\"id\":\"<p>Pemeriksaan yang baik, benar dan tepat waktu sesuai standar menentukan langkah selanjutnya. Pemeriksaan ini terutama memakai Tes Cepat Molekular (TCM) di fasilitas kesehatan tingkat pertama (FKTP) maupun tingkat lanjutan (FKTL) yang memiliki alatnya. Karena belum semua fasilitas memilikinya, dipakai pilihan lain: pemeriksaan BTA (Basil Tahan Asam) dengan mikroskop, rontgen dada, atau tes tuberkulin (Mantoux). Dengan pemeriksaan yang baik, diagnosis TBC dapat ditegakkan dan pengobatan yang tepat segera diberikan, sehingga penularan lanjutan segera terkendali.</p>\"}}},\"b12\":{\"id\":\"b12\",\"type\":\"paragraph\",\"data\":{\"text\":{\"en\":\"<p>A TCM machine, which also serves HIV viral load and hepatitis tests, is badly needed in model Puskesmas, above all for triple-elimination services for pregnant women. Wanggar Puskesmas, a model Puskesmas, still refers such samples to Kalibumi Puskesmas, the referral laboratory for TCM.</p>\",\"id\":\"<p>Mesin TCM, yang juga dipakai untuk pemeriksaan viral load HIV dan hepatitis, sangat diperlukan di Puskesmas Model, terutama untuk layanan triple eliminasi bagi ibu hamil. Puskesmas Wanggar sebagai Puskesmas Model masih merujuk sampel tersebut ke Puskesmas Kalibumi sebagai laboratorium rujukan TCM.</p>\"}}},\"b13\":{\"id\":\"b13\",\"type\":\"image\",\"data\":{\"url\":\"\",\"alt_text\":\"\",\"caption\":{\"en\":\"Wanggar Puskesmas laboratory facilities (blood chemistry). 5 August 2026.\",\"id\":\"Fasilitas laboratorium Puskesmas Wanggar untuk pemeriksaan kimia darah. 5 Agustus 2026.\"},\"width\":\"w-full\",\"align\":\"mx-auto\",\"radius\":\"rounded-lg\",\"height\":\"h-auto\",\"max_height\":\"max-h-[32rem]\",\"object_fit\":\"object-cover\",\"margin_bottom\":\"mb-4 md:mb-6\"}},\"b14\":{\"id\":\"b14\",\"type\":\"paragraph\",\"data\":{\"text\":{\"en\":\"<p>The Ministry of Health has issued and shared national testing guidelines for some time, but limited on-site practical training often keeps health workers from feeling confident enough to do the tests on their own. To close this confidence gap at Puskesmas, YSBH has introduced OJT in model Puskesmas as part of its technical assistance to local governments.</p>\",\"id\":\"<p>Panduan nasional untuk pemeriksaan ini sudah lama diterbitkan dan disosialisasikan Kementerian Kesehatan, tetapi pelatihan praktis di tempat kerja terbatas, sehingga tenaga kesehatan sering kurang berani melaksanakannya secara mandiri. Untuk mengatasi kurangnya kepercayaan diri tenaga kesehatan di puskesmas dalam skrining TBC terpadu ini, YSBH memperkenalkan penguatan kapasitas melalui on-the-job training (OJT) di Puskesmas Model, sebagai bagian dari pendampingan teknis kepada pemerintah daerah.</p>\"}}},\"b15\":{\"id\":\"b15\",\"type\":\"image\",\"data\":{\"url\":\"\",\"alt_text\":\"\",\"caption\":{\"en\":\"Presentation of initial assessment results for Wanggar Puskesmas by YSBH\'s Senior MCH Officer to the Nabire District Health Office and Wanggar staff. 5 August 2026.\",\"id\":\"Pemaparan hasil asesmen awal Puskesmas Wanggar oleh Senior MCH Officer YSBH kepada Dinas Kesehatan Kabupaten Nabire dan staf Puskesmas Wanggar. 5 Agustus 2026.\"},\"width\":\"w-full\",\"align\":\"mx-auto\",\"radius\":\"rounded-lg\",\"height\":\"h-auto\",\"max_height\":\"max-h-[32rem]\",\"object_fit\":\"object-cover\",\"margin_bottom\":\"mb-4 md:mb-6\"}},\"b16\":{\"id\":\"b16\",\"type\":\"heading\",\"data\":{\"text\":{\"en\":\"TB Screening and Treatment for Vulnerable Groups\",\"id\":\"Pemeriksaan (Skrining) dan Pengobatan TBC di Kelompok Rentan\"},\"level\":\"h2\"}},\"b17\":{\"id\":\"b17\",\"type\":\"paragraph\",\"data\":{\"text\":{\"en\":\"<p>Vulnerable groups such as mothers and children, including adolescents, are at high risk of catching TB from other adults close to them. Yet TB detection is still not consistent in routine antenatal checks or in routine health checks for children and adolescents. National TB policy requires every HIV screening to be done together with TB screening and the other way round, through One-Stop Services. At the first antenatal visit (ANC1), integrated screening is done for several infectious diseases, including HIV, syphilis, hepatitis B and malaria, to reduce illness among pregnant women and stop transmission to the baby. But TB screening is not yet included at ANC1 in most health facilities in YSBH\'s supported districts. TB screening is also not yet routine in IMCI services, nor when new students are admitted at the start of the school year.</p>\",\"id\":\"<p>Kelompok rentan seperti ibu dan anak, termasuk remaja, sangat berisiko tertular TBC dari orang dewasa lain di lingkungan terdekat. Sayangnya, deteksi TBC pada pemeriksaan kehamilan rutin dan pemeriksaan kesehatan rutin anak dan remaja belum dilaksanakan konsisten. Kebijakan nasional mewajibkan setiap skrining HIV dilakukan bersamaan dengan skrining TBC dan sebaliknya, melalui One Stop Services. Pada pemeriksaan kehamilan pertama (ANC1 atau K1), skrining terpadu dilakukan untuk beberapa penyakit menular, termasuk HIV, sifilis, hepatitis B dan malaria, untuk menekan kesakitan ibu hamil dan mencegah penularan kepada janin dan bayi. Namun skrining TBC belum sekaligus dilakukan pada kunjungan ANC1 di sebagian besar fasilitas kesehatan di kabupaten dampingan YSBH. Pada layanan MTBS, skrining TBC juga belum rutin, begitu pula pada penerimaan siswa baru di awal tahun ajaran.</p>\"}}},\"b18\":{\"id\":\"b18\",\"type\":\"paragraph\",\"data\":{\"text\":{\"en\":\"<p>To support pregnant women, children and adolescents with TB effectively, health workers in primary and referral care need training and capacity building from facilitators or trainers. YSBH gives technical assistance to build the skills of primary care health workers so they can support TB patients properly. This assistance goes mainly to model Puskesmas, which will then also serve as OJT Centers for effective TB services, so other Puskesmas can learn from them.</p>\",\"id\":\"<p>Agar dapat mendukung ibu hamil dan anak-remaja penderita TBC secara efektif, tenaga kesehatan di layanan primer dan lanjutan harus mendapat pelatihan dari fasilitator atau pelatih. YSBH memberikan pendampingan teknis untuk meningkatkan kapasitas tenaga kesehatan di layanan primer agar dapat mendukung penderita TBC dengan memadai. Pendampingan ini terutama diberikan kepada Puskesmas Model, yang kemudian juga berfungsi sebagai Puskesmas OJT Center untuk layanan TBC yang efektif, sehingga puskesmas lain dapat belajar darinya.</p>\"}}},\"b19\":{\"id\":\"b19\",\"type\":\"image\",\"data\":{\"url\":\"\",\"alt_text\":\"\",\"caption\":{\"en\":\"YSBH cross-checking manual data against electronic reports in SITB and SIHA at Wanggar Puskesmas. 5 August 2026.\",\"id\":\"YSBH mencocokkan data manual dengan laporan elektronik di SITB dan SIHA Puskesmas Wanggar. 5 Agustus 2026.\"},\"width\":\"w-full\",\"align\":\"mx-auto\",\"radius\":\"rounded-lg\",\"height\":\"h-auto\",\"max_height\":\"max-h-[32rem]\",\"object_fit\":\"object-cover\",\"margin_bottom\":\"mb-4 md:mb-6\"}},\"b20\":{\"id\":\"b20\",\"type\":\"image\",\"data\":{\"url\":\"\",\"alt_text\":\"\",\"caption\":{\"en\":\"Monitoring TB and HIV supplies and discussing with the Paniai District Hospital pharmacist the need to request replenishment of TB medicine whose stock expires in August 2026. Enarotali, 10 July 2026.\",\"id\":\"Memantau ketersediaan logistik TB dan HIV dan berdiskusi dengan apoteker RSUD Paniai tentang kebutuhan segera mengajukan penggantian obat TBC yang stoknya kedaluwarsa pada Agustus 2026. Enarotali, 10 Juli 2026.\"},\"width\":\"w-full\",\"align\":\"mx-auto\",\"radius\":\"rounded-lg\",\"height\":\"h-auto\",\"max_height\":\"max-h-[32rem]\",\"object_fit\":\"object-cover\",\"margin_bottom\":\"mb-4 md:mb-6\"}},\"b21\":{\"id\":\"b21\",\"type\":\"heading\",\"data\":{\"text\":{\"en\":\"What we are doing\",\"id\":\"Yang sedang kami kerjakan\"},\"level\":\"h2\"}},\"b22\":{\"id\":\"b22\",\"type\":\"paragraph\",\"data\":{\"text\":{\"en\":\"<ul><li><strong>2026, with the Provincial Health Office of Central Papua:</strong> support for strengthening health services in six districts and at provincial level. TB control is one of the priority activities, alongside integrated maternal and child health, immunization, malaria elimination, and strengthening surveillance, recording and reporting.</li></ul>\",\"id\":\"<ul><li><strong>2026, bersama Dinas Kesehatan Provinsi Papua Tengah:</strong> dukungan penguatan layanan kesehatan di enam kabupaten dan tingkat provinsi. Pengendalian TBC termasuk kegiatan prioritas, bersama KIA terpadu, imunisasi, eliminasi malaria, serta penguatan surveilans, pencatatan, dan pelaporan.</li></ul>\"}}}},\"order\":[\"b01\",\"b02\",\"b03\",\"b04\",\"b05\",\"b06\",\"b07\",\"b08\",\"b09\",\"b10\",\"b11\",\"b12\",\"b13\",\"b14\",\"b15\",\"b16\",\"b17\",\"b18\",\"b19\",\"b20\",\"b21\",\"b22\"],\"settings\":{\"toc_position\":\"right\"}}','{\"id\":\"\"}','{\"id\":\"\"}','online','2026-10-09 22:47:33','2026-10-09 18:54:11','2026-10-09 22:47:33'),
(8,'{\"en\":\"HIV Program\",\"id\":\"Program HIV\"}','{\"en\":\"hiv-program\",\"id\":\"program-hiv\"}','{\"blocks\":{\"b01\":{\"id\":\"b01\",\"type\":\"heading\",\"data\":{\"text\":{\"en\":\"Integrated HIV Program\",\"id\":\"Program HIV Terpadu\"},\"level\":\"h1\"}},\"b02\":{\"id\":\"b02\",\"type\":\"paragraph\",\"data\":{\"text\":{\"en\":\"<p>The HIV-STI and PMTCT program supported by YSBH helps local governments make sure everyone, including pregnant women, newborns, children and adolescents, in all supported areas, including remote ones, has access to integrated, fair, high-quality Care, Support and Treatment (CST, known locally as <em>PDP</em>) services. The focus is on the service areas of the intervention (model) Puskesmas, including marginalized and underserved villages in their catchments.</p>\",\"id\":\"<p>Program HIV-IMS dan PPIA dukungan YSBH membantu pemerintah daerah memastikan semua masyarakat, termasuk ibu hamil, bayi baru lahir, anak-anak dan remaja di seluruh wilayah dukungan, termasuk daerah terpencil, memiliki akses ke layanan Perawatan, Dukungan dan Pengobatan (PDP) yang terintegrasi, adil dan bermutu tinggi. Fokusnya di wilayah kerja Puskesmas intervensi (Puskesmas Model), termasuk kampung-kampung yang terpinggirkan dan kurang terlayani di wilayah kerja puskesmas tersebut.</p>\"}}},\"b03\":{\"id\":\"b03\",\"type\":\"paragraph\",\"data\":{\"text\":{\"en\":\"<p>The 2025 Integrated Biological and Behavioral Survey (STBP) in Papua found HIV prevalence of 1.4%. HIV therefore remains a widespread epidemic in Papua, although prevalence has fallen from 2.3% in the 2013 survey, and no area is free of HIV cases. Syphilis and hepatitis B are also rising year after year, including among pregnant women and newborns.</p>\",\"id\":\"<p>Menurut Survei Terpadu Biologis dan Perilaku (STBP) di Tanah Papua tahun 2025, prevalensi HIV sebesar 1,4%. Ini menunjukkan HIV masih menjadi epidemi meluas di Tanah Papua, walaupun prevalensinya sudah turun dibanding STBP 2013 sebesar 2,3%, dan tidak ada daerah yang bebas kasus HIV. Insiden sifilis dan hepatitis B juga terus meningkat dari tahun ke tahun, termasuk di kalangan ibu hamil dan bayi baru lahir.</p>\"}}},\"b04\":{\"id\":\"b04\",\"type\":\"paragraph\",\"data\":{\"text\":{\"en\":\"<p>The supported areas span Papua, Highland Papua and Central Papua Provinces and the regencies of Sarmi, Pegunungan Bintang, Yahukimo, Nabire, Mimika, Paniai, Deiyai, Dogiyai and Puncak Jaya. Their geographical challenges are met by strengthening local health workers and <em>posyandu</em> volunteers in the catchments of model Puskesmas, so they can plan, carry out, monitor, educate on and deliver comprehensive, integrated CST services, including for mothers and newborns, the most vulnerable groups.</p>\",\"id\":\"<p>Wilayah dukungan tersebar di Provinsi Papua, Papua Pegunungan dan Papua Tengah serta Kabupaten Sarmi, Pegunungan Bintang, Yahukimo, Nabire, Mimika, Paniai, Deiyai, Dogiyai dan Puncak Jaya. Tantangan geografisnya dijawab dengan memperkuat kapasitas tenaga medis lokal dan kader posyandu di wilayah kerja Puskesmas Model, agar dapat merencanakan, melaksanakan, memantau, mengedukasi dan memberikan layanan PDP yang menyeluruh dan terpadu, termasuk bagi ibu dan bayi baru lahir yang paling rentan.</p>\"}}},\"b05\":{\"id\":\"b05\",\"type\":\"paragraph\",\"data\":{\"text\":{\"en\":\"<p>YSBH\'s support focuses on preventing, controlling and eliminating HIV, syphilis and hepatitis B, the national \\\"triple elimination\\\" priority with a 2030 target. Triple elimination is closely tied to Maternal and Child Health through prevention of mother-to-child transmission (PMTCT), and to communicable disease control through HIV-STI, hepatitis and TB programs. This takes cooperation among three divisions: Disease Prevention and Control, Public Health, and Health Services. The goals are pursued through resilient health systems that stay responsive to emergencies and local conditions, especially in Model Puskesmas that also serve as On-the-Job Training (OJT) Centers in each supported regency.</p>\",\"id\":\"<p>Dukungan YSBH difokuskan pada pencegahan, pengendalian dan eliminasi HIV, sifilis dan hepatitis B, program prioritas nasional (triple eliminasi) dengan target 2030. Triple eliminasi sangat berkaitan dengan program KIA melalui Pencegahan Penularan dari Ibu ke Anak (PPIA) dan dengan program Penanggulangan dan Pencegahan Penyakit Menular (P2PM) melalui HIV-IMS, hepatitis dan TBC. Ini merupakan kerja sama tiga bidang: Bidang P2P, Bidang Kesmas dan Bidang Yankes. Tujuannya diwujudkan lewat sistem kesehatan yang tangguh dan tetap responsif terhadap keadaan darurat dan situasi lokal, terutama di Puskesmas Model yang juga berfungsi sebagai Puskesmas On the Job Training (OJT) Center di setiap kabupaten dampingan.</p>\"}}},\"b06\":{\"id\":\"b06\",\"type\":\"paragraph\",\"data\":{\"text\":{\"en\":\"<p>The HIV-PMTCT program has three main components: screening for HIV, syphilis, hepatitis B and TB; support for people who test positive; and treatment. It runs in selected districts with model Puskesmas, using approaches that fit the socio-cultural context of Papuan communities, in the hope that local governments and other sectors will adopt and scale them up to other health centers and hospitals. YSBH also helps local governments develop Governor Regulations and Regional Action Plans (RAD) for prevention of mother-to-child transmission, for example in Central Papua Province, to strengthen cross-sector cooperation toward the 2030 triple elimination targets.</p>\",\"id\":\"<p>Program HIV-PPIA terdiri dari tiga komponen utama: pemeriksaan (skrining) HIV, sifilis, hepatitis B serta skrining TBC; dukungan bagi pasien yang positif; dan pengobatan. Program dijalankan di distrik terpilih dengan Puskesmas Model, memakai pendekatan yang sesuai dengan konteks sosial-budaya masyarakat Papua, dengan harapan pemerintah daerah dan lintas sektor mengadopsi dan memperluasnya ke puskesmas dan rumah sakit lain. YSBH juga membantu pemerintah daerah menyusun Peraturan Gubernur beserta Rencana Aksi Daerah (RAD) Pencegahan Penularan dari Ibu ke Anak di beberapa wilayah dukungan, seperti Provinsi Papua Tengah, untuk memperkuat kerja sama lintas sektor mempercepat target triple eliminasi 2030.</p>\"}}},\"b07\":{\"id\":\"b07\",\"type\":\"image\",\"data\":{\"url\":\"\",\"alt_text\":\"\",\"caption\":{\"en\":\"Technical guidance on the HIV-PMTCT program at Wanggar Puskesmas, a model Puskesmas in Nabire, facilitated by YSBH with the Nabire District Health Office and the Central Papua Provincial Health Office. Integrated guidance and OJT on HIV and PMTCT recording and reporting in the CST, MCH, pharmacy and laboratory services, 5–7 August 2026.\",\"id\":\"Bimbingan teknis program HIV-PPIA di Puskesmas Wanggar, salah satu Puskesmas Model di Kabupaten Nabire, difasilitasi YSBH bersama Dinas Kesehatan Kabupaten Nabire dan Dinas Kesehatan Provinsi Papua Tengah. Bimtek terpadu dan OJT pencatatan pelaporan HIV dan PPIA di layanan PDP, KIA, apotek dan laboratorium, 5–7 Agustus 2026.\"},\"width\":\"w-full\",\"align\":\"mx-auto\",\"radius\":\"rounded-lg\",\"height\":\"h-auto\",\"max_height\":\"max-h-[32rem]\",\"object_fit\":\"object-cover\",\"margin_bottom\":\"mb-4 md:mb-6\"}},\"b08\":{\"id\":\"b08\",\"type\":\"heading\",\"data\":{\"text\":{\"en\":\"Screening for HIV, Syphilis, Hepatitis B and TB\",\"id\":\"Pemeriksaan (Skrining) HIV, Sifilis, Hepatitis B serta Skrining TBC\"},\"level\":\"h2\"}},\"b09\":{\"id\":\"b09\",\"type\":\"paragraph\",\"data\":{\"text\":{\"en\":\"<p>HIV screening is often done together with TB screening, as part of a national One-Stop Services approach: everyone screened for HIV is also screened for TB, and the other way round. For pregnant women, integrated screening for HIV, syphilis, hepatitis B, TB and malaria is mandatory and routine at the first antenatal visit (ANC1). The aim is to reduce illness among pregnant women and stop these diseases passing to the baby during pregnancy or birth.</p>\",\"id\":\"<p>Skrining HIV sering dilakukan bersamaan dengan skrining TBC karena menjadi bagian dari program nasional dengan pendekatan One Stop Services: setiap orang yang diskrining HIV sekaligus diskrining TBC, dan sebaliknya. Pada ibu hamil, skrining terpadu HIV, sifilis, hepatitis B, TBC dan malaria bersifat rutin dan wajib pada pemeriksaan kehamilan pertama (ANC 1 atau K1). Tujuannya menekan angka kesakitan ibu hamil dan mencegah penularan penyakit-penyakit tersebut kepada janin dalam kandungan maupun saat bayi lahir.</p>\"}}},\"b10\":{\"id\":\"b10\",\"type\":\"paragraph\",\"data\":{\"text\":{\"en\":\"<p>National data show that the provinces of Papua still have the highest prevalence of HIV, syphilis and hepatitis B (the \\\"triple elimination\\\" diseases) and the highest malaria incidence. High prevalence of these diseases suggests high TB prevalence too. All of them raise the risk of maternal and child death. Reducing the Maternal Mortality Rate, Infant Mortality Rate and Under-Five Mortality Rate therefore cannot be separated from improving maternal health and giving integrated medical care from pregnancy and the postpartum period through early childhood.</p>\",\"id\":\"<p>Data nasional menunjukkan provinsi-provinsi di Tanah Papua masih memiliki prevalensi HIV, sifilis dan hepatitis B (triple eliminasi) tertinggi, serta insiden malaria tertinggi. Bila prevalensi tiga penyakit itu tinggi, prevalensi TBC diperkirakan juga tinggi. Semua penyakit tersebut meningkatkan risiko kematian ibu dan anak. Penurunan Angka Kematian Ibu (AKI), Angka Kematian Bayi (AKB) dan Angka Kematian Balita (AKBA) karena itu tidak dapat dipisahkan dari upaya meningkatkan kesehatan ibu dan intervensi medis terpadu sejak kehamilan, pascapersalinan, hingga usia balita.</p>\"}}},\"b11\":{\"id\":\"b11\",\"type\":\"image\",\"data\":{\"url\":\"\",\"alt_text\":\"\",\"caption\":{\"en\":\"The laboratory of Wanggar Puskesmas, which tests for HIV, syphilis, hepatitis B and more. 5 August 2026.\",\"id\":\"Laboratorium Puskesmas Wanggar, tempat pemeriksaan HIV, sifilis, hepatitis B dan lainnya. 5 Agustus 2026.\"},\"width\":\"w-full\",\"align\":\"mx-auto\",\"radius\":\"rounded-lg\",\"height\":\"h-auto\",\"max_height\":\"max-h-[32rem]\",\"object_fit\":\"object-cover\",\"margin_bottom\":\"mb-4 md:mb-6\"}},\"b12\":{\"id\":\"b12\",\"type\":\"paragraph\",\"data\":{\"text\":{\"en\":\"<p>Testing that is correct, on time and to standard decides the support and treatment steps that follow. It can be done with Rapid Diagnostic Tests (RDTs), which trained health workers can easily use even where supporting facilities are very limited. The Ministry of Health has issued and shared national guidelines for some time, but limited on-site practical training often keeps health workers from doing the tests confidently on their own. To close this gap, YSBH has introduced OJT in model Puskesmas as part of its technical assistance to local governments, including evaluating how clinical mentoring of CST services is carried out.</p>\",\"id\":\"<p>Pemeriksaan yang baik, benar dan tepat waktu sesuai standar menentukan langkah dukungan dan pengobatan berikutnya. Pemeriksaan ini dapat dilakukan dengan Rapid Diagnostic Test (RDT) yang mudah dilaksanakan tenaga kesehatan terlatih, bahkan di daerah dengan fasilitas pendukung yang sangat terbatas. Panduan nasional sudah lama diterbitkan dan disosialisasikan Kementerian Kesehatan, tetapi pelatihan praktis di tempat kerja terbatas, sehingga tenaga kesehatan sering kurang berani melaksanakannya secara mandiri. Untuk mengatasi hal ini, YSBH memperkenalkan penguatan kapasitas melalui on-the-job training di Puskesmas Model sebagai bagian dari pendampingan teknis kepada pemerintah daerah, termasuk evaluasi pelaksanaan mentoring klinis layanan PDP.</p>\"}}},\"b13\":{\"id\":\"b13\",\"type\":\"image\",\"data\":{\"url\":\"\",\"alt_text\":\"\",\"caption\":{\"en\":\"Evaluation of clinical mentoring in several CST services in Nabire and Dogiyai Regencies, funded by the Global Fund (AIDS) in Central Papua, in cooperation with the Papua and Central Papua Provincial Health Offices, the Nabire District Health Office, CST mentors, the HIV technical officer of the Central Papua Provincial Health Office and YSBH. Nabire, 28–30 July 2026. Shown with the speakers and participants from Dogiyai with the best pre- and post-test results: Moanemani Puskesmas, Dogiyai Hospital and Bomomani Puskesmas.\",\"id\":\"Evaluasi pelaksanaan monitoring klinis di beberapa layanan PDP Kabupaten Nabire dan Kabupaten Dogiyai, didukung dana Global Fund (AIDS) Provinsi Papua Tengah, bekerja sama dengan Dinas Kesehatan Provinsi Papua, Dinas Kesehatan Provinsi Papua Tengah, Dinas Kesehatan Kabupaten Nabire, mentor PDP, Technical Officer HIV Dinkes Papua Tengah dan YSBH. Nabire, 28–30 Juli 2026. Bersama narasumber dan peserta dengan hasil pre-test dan post-test terbaik dari Kabupaten Dogiyai: Puskesmas Moanemani, RSUD Dogiyai dan Puskesmas Bomomani.\"},\"width\":\"w-full\",\"align\":\"mx-auto\",\"radius\":\"rounded-lg\",\"height\":\"h-auto\",\"max_height\":\"max-h-[32rem]\",\"object_fit\":\"object-cover\",\"margin_bottom\":\"mb-4 md:mb-6\"}},\"b14\":{\"id\":\"b14\",\"type\":\"image\",\"data\":{\"url\":\"\",\"alt_text\":\"\",\"caption\":{\"en\":\"Closing of the evaluation meeting on clinical mentoring for CST services, with HIV and MCH officers from several Puskesmas and hospitals in Nabire and Dogiyai. Nabire, July 2026.\",\"id\":\"Penutupan pertemuan evaluasi mentoring klinis layanan PDP, dengan penanggung jawab HIV dan KIA dari beberapa puskesmas dan rumah sakit di Kabupaten Nabire dan Dogiyai. Nabire, Juli 2026.\"},\"width\":\"w-full\",\"align\":\"mx-auto\",\"radius\":\"rounded-lg\",\"height\":\"h-auto\",\"max_height\":\"max-h-[32rem]\",\"object_fit\":\"object-cover\",\"margin_bottom\":\"mb-4 md:mb-6\"}},\"b15\":{\"id\":\"b15\",\"type\":\"heading\",\"data\":{\"text\":{\"en\":\"Support for People Living with HIV/AIDS\",\"id\":\"Dukungan bagi Penderita HIV/AIDS\"},\"level\":\"h2\"}},\"b16\":{\"id\":\"b16\",\"type\":\"paragraph\",\"data\":{\"text\":{\"en\":\"<p>People living with HIV, including pregnant women, who test positive through screening at CST services are entitled to comprehensive support from trained health workers. This includes mental health support through counselling by trained counsellors, networking with other people living with HIV, and referral to other institutions according to each person\'s needs.</p>\",\"id\":\"<p>Orang dengan HIV (ODHIV), termasuk ibu hamil, yang dinyatakan positif melalui skrining dan tes di layanan PDP, berhak mendapat dukungan menyeluruh dari tenaga kesehatan terlatih. Dukungan berupa dukungan mental melalui konseling oleh konselor terlatih, jejaring dengan sesama ODHIV, dan rujukan ke lembaga atau pihak lain sesuai kebutuhan masing-masing ODHIV.</p>\"}}},\"b17\":{\"id\":\"b17\",\"type\":\"paragraph\",\"data\":{\"text\":{\"en\":\"<p>To give effective support, health workers in CST services need training from facilitators or trainers. YSBH gives technical assistance to build their capacity so they can support people living with HIV properly. The assistance goes mainly to model Puskesmas, which then also serve as OJT Centers for effective CST services, so other Puskesmas can learn from them.</p>\",\"id\":\"<p>Agar dapat memberi dukungan yang efektif, tenaga kesehatan di layanan PDP harus mendapat pelatihan dari fasilitator atau pelatih. YSBH memberikan pendampingan teknis untuk meningkatkan kapasitas mereka agar dapat mendukung penderita HIV dengan memadai. Pendampingan ini terutama diberikan kepada Puskesmas Model, yang kemudian juga berfungsi sebagai Puskesmas OJT Center untuk layanan PDP yang efektif, sehingga puskesmas lain dapat belajar darinya.</p>\"}}},\"b18\":{\"id\":\"b18\",\"type\":\"image\",\"data\":{\"url\":\"\",\"alt_text\":\"\",\"caption\":{\"en\":\"Technical guidance for CST services at a model Puskesmas in Paniai Regency. The visit also began activating CST and PMTCT services at Enarotali Puskesmas, the model Puskesmas in Paniai, which YSBH helped make available together with the Central Papua Provincial Health Office, the Paniai District Health Office and the Global Fund (AIDS) technical officer. Here the provincial and district teams discuss activating the SIHA account for CST services with the Enarotali team. 9 July 2026.\",\"id\":\"Bimbingan teknis layanan PDP di Puskesmas Model di Kabupaten Paniai. Kegiatan ini sekaligus mulai mengaktifkan layanan PDP dan PPIA di Puskesmas Enarotali, Puskesmas Model di Paniai, yang YSBH bantu hadirkan bersama Dinas Kesehatan Provinsi Papua Tengah, Dinas Kesehatan Kabupaten Paniai dan Technical Officer Global Fund (AIDS). Tim Dinkes provinsi dan kabupaten berdiskusi dengan tim Puskesmas Enarotali untuk mengaktifkan akun SIHA layanan PDP. 9 Juli 2026.\"},\"width\":\"w-full\",\"align\":\"mx-auto\",\"radius\":\"rounded-lg\",\"height\":\"h-auto\",\"max_height\":\"max-h-[32rem]\",\"object_fit\":\"object-cover\",\"margin_bottom\":\"mb-4 md:mb-6\"}},\"b19\":{\"id\":\"b19\",\"type\":\"image\",\"data\":{\"url\":\"\",\"alt_text\":\"\",\"caption\":{\"en\":\"OJT for the CST team at Enarotali Puskesmas, with records and reporting staff and the pharmacist. 9 July 2026.\",\"id\":\"OJT untuk tim PDP Puskesmas Enarotali, melibatkan staf pencatatan dan pelaporan serta apoteker. 9 Juli 2026.\"},\"width\":\"w-full\",\"align\":\"mx-auto\",\"radius\":\"rounded-lg\",\"height\":\"h-auto\",\"max_height\":\"max-h-[32rem]\",\"object_fit\":\"object-cover\",\"margin_bottom\":\"mb-4 md:mb-6\"}},\"b20\":{\"id\":\"b20\",\"type\":\"heading\",\"data\":{\"text\":{\"en\":\"Treatment for People Living with HIV/AIDS\",\"id\":\"Pengobatan bagi Penderita HIV/AIDS\"},\"level\":\"h2\"}},\"b21\":{\"id\":\"b21\",\"type\":\"paragraph\",\"data\":{\"text\":{\"en\":\"<p>People who test positive for HIV need more than mental support and counselling at CST services: they also need regular medication to keep their quality of life. This treatment applies for life, includes HIV-positive pregnant women and their infants, and is recorded and reported through an app-based system, SIHA (HIV/AIDS Information System), which also covers sexually transmitted infections including syphilis. A pregnant woman who tests positive should start treatment as soon as she is diagnosed and take it for at least 6 months of pregnancy, so she can deliver normally at the Puskesmas. All health workers in CST services must therefore be able to start HIV treatment, both for HIV-positive people in the general population and for pregnant women with HIV. This protects babies from mother-to-child transmission, the goal of the national PMTCT program. Under the national program, HIV treatment must be accompanied by TB treatment for people with HIV who screen positive for TB.</p>\",\"id\":\"<p>Orang dengan hasil skrining HIV positif, selain mendapat dukungan mental dan konseling di layanan PDP, juga harus mendapat pengobatan rutin agar kualitas hidupnya terjaga. Pengobatan ini berlaku seumur hidup, termasuk bagi ibu hamil dan bayinya yang positif, dan dicatat serta dilaporkan lewat aplikasi SIHA (Sistem Informasi HIV/AIDS), yang juga dipakai untuk infeksi menular seksual termasuk sifilis. Ibu hamil yang positif HIV perlu mendapat pengobatan secepatnya setelah terdiagnosis, selama minimal 6 bulan dalam kehamilannya, agar dapat bersalin normal di puskesmas. Karena itu semua tenaga kesehatan di layanan PDP harus mampu memulai pengobatan HIV, baik bagi penderita di populasi umum maupun ibu hamil dengan HIV. Dengan demikian bayi yang lahir terhindar dari penularan HIV dari ibunya, sesuai tujuan program nasional PPIA. Pengobatan HIV/AIDS harus disertai pengobatan TBC bagi penderita yang hasil skrining TBC-nya positif, sebagai bagian dari program nasional.</p>\"}}},\"b22\":{\"id\":\"b22\",\"type\":\"image\",\"data\":{\"url\":\"\",\"alt_text\":\"\",\"caption\":{\"en\":\"YSBH, with the Central Papua Provincial Health Office, the Paniai District Health Office and the Global Fund (AIDS) technical officer, running OJT and a refresher on SIHA data recording and reporting, integrated with SMILE, at Enarotali Puskesmas and Paniai Hospital. About 150 people living with HIV who started antiretroviral (ARV) treatment at the Enarotali model Puskesmas CST service were entered into SIHA 2.1 during the mentoring and OJT. Enarotali, [ISI-DULU] 2026.\",\"id\":\"YSBH, bersama Dinas Kesehatan Provinsi Papua Tengah, Dinas Kesehatan Kabupaten Paniai dan Technical Officer Global Fund (AIDS), melaksanakan OJT dan penyegaran pencatatan pelaporan data SIHA terintegrasi dengan SMILE di Puskesmas Enarotali dan RSUD Paniai. Sekitar 150 ODHIV yang mulai mendapat ARV di layanan PDP Puskesmas Model Enarotali berhasil diinput ke SIHA 2.1 saat pendampingan dan OJT. Enarotali, [ISI-DULU] 2026.\"},\"width\":\"w-full\",\"align\":\"mx-auto\",\"radius\":\"rounded-lg\",\"height\":\"h-auto\",\"max_height\":\"max-h-[32rem]\",\"object_fit\":\"object-cover\",\"margin_bottom\":\"mb-4 md:mb-6\"}},\"b23\":{\"id\":\"b23\",\"type\":\"paragraph\",\"data\":{\"text\":{\"en\":\"<p>The PMTCT program targets not only pregnant women with HIV but also those with syphilis and hepatitis B. All pregnant women with syphilis or hepatitis B must be treated during pregnancy and after delivery so they do not pass the infection to their babies. Babies born to mothers with syphilis or hepatitis B must also receive syphilis treatment and the hepatitis B vaccine. Treatment outcomes are recorded and reported through SIHEPI (Hepatitis Information System).</p>\",\"id\":\"<p>Program PPIA tidak hanya menyasar ibu hamil dengan HIV, tetapi juga ibu hamil dengan sifilis dan hepatitis B. Semua ibu hamil dengan sifilis dan hepatitis B harus mendapat pengobatan selama kehamilan dan setelah persalinan agar tidak menularkan ke bayinya. Bayi yang lahir dari ibu dengan sifilis dan hepatitis B juga harus mendapat pengobatan sifilis dan vaksinasi hepatitis B agar tidak tertular saat persalinan. Hasil pengobatan dicatat dan dilaporkan lewat aplikasi SIHEPI (Sistem Informasi Hepatitis).</p>\"}}},\"b24\":{\"id\":\"b24\",\"type\":\"paragraph\",\"data\":{\"text\":{\"en\":\"<p>To make sure every CST service can start and give routine HIV treatment, as well as treatment for syphilis and hepatitis B, for the general population, pregnant women and newborns, YSBH works with the provincial and district health offices. It gives technical assistance to CST services in model Puskesmas, which will become OJT Centers, and helps ensure steady and adequate supplies of medicines, materials and diagnostic equipment.</p>\",\"id\":\"<p>Agar setiap layanan PDP mampu memulai dan memberikan pengobatan rutin HIV, sifilis dan hepatitis B bagi populasi umum, ibu hamil dan bayi baru lahir, YSBH bekerja sama dengan dinas kesehatan provinsi dan kabupaten dampingan. YSBH memberikan pendampingan teknis bagi layanan PDP di Puskesmas Model, yang akan menjadi Puskesmas OJT Center, termasuk memastikan ketersediaan logistik obat, bahan dan alat diagnostik yang konsisten dan memadai.</p>\"}}},\"b25\":{\"id\":\"b25\",\"type\":\"image\",\"data\":{\"url\":\"\",\"alt_text\":\"\",\"caption\":{\"en\":\"Joint monitoring visit by the Central Papua Provincial Health Office and YSBH to the pharmacy of Enarotali Puskesmas, checking the availability of medicines and supplies for HIV, syphilis and hepatitis B. 9 July 2026.\",\"id\":\"Kunjungan monitoring bersama Dinas Kesehatan Provinsi Papua Tengah dan YSBH ke ruang farmasi Puskesmas Enarotali, Kabupaten Paniai, memantau ketersediaan obat dan perbekalan HIV, sifilis dan hepatitis B. 9 Juli 2026.\"},\"width\":\"w-full\",\"align\":\"mx-auto\",\"radius\":\"rounded-lg\",\"height\":\"h-auto\",\"max_height\":\"max-h-[32rem]\",\"object_fit\":\"object-cover\",\"margin_bottom\":\"mb-4 md:mb-6\"}},\"b26\":{\"id\":\"b26\",\"type\":\"heading\",\"data\":{\"text\":{\"en\":\"What we have done\",\"id\":\"Yang telah kami kerjakan\"},\"level\":\"h2\"}},\"b27\":{\"id\":\"b27\",\"type\":\"paragraph\",\"data\":{\"text\":{\"en\":\"<ul><li><strong>August 2018–January 2019, USAID-funded (Pact, Inc. as grant manager; USAID LINKAGES program implemented by FHI 360):</strong> support to the Provincial Health Office to achieve fast-track goals. We helped set up and run fast-track working groups, improve coordination of HIV data and services, train staff in care, support and treatment (CST), and provide technical assistance across districts and cities.</li><li><strong>May–September 2019, same program:</strong> continued support through a fixed amount award: strengthening the fast-track secretariat, building AIDS program management capacity at the Provincial Health Office, training and mentoring in HIV services (CST and antiretroviral therapy), strengthening data systems and cross-stakeholder coordination, and monitoring and evaluating the decentralization of antiretroviral (ARV) therapy in several priority districts and cities in Papua.</li></ul>\",\"id\":\"<ul><li><strong>Agustus 2018–Januari 2019, didanai USAID (Pact, Inc. sebagai pengelola hibah; program USAID LINKAGES dilaksanakan FHI 360):</strong> dukungan kepada Dinas Kesehatan Provinsi untuk mencapai fast track goals. Kami membantu membentuk dan mengelola kelompok kerja fast track, meningkatkan koordinasi data dan layanan HIV, melatih petugas dalam CST (perawatan, dukungan, dan pengobatan), dan memberi asistensi teknis lintas kabupaten/kota.</li><li><strong>Mei–September 2019, program yang sama:</strong> dukungan lanjutan melalui Fixed Amount Award: penguatan sekretariat Fast-Track, peningkatan kapasitas manajemen program AIDS di Dinas Kesehatan Provinsi, pelatihan dan pendampingan layanan HIV (CST dan ART), penguatan sistem data dan koordinasi lintas pemangku kepentingan, serta monitoring dan evaluasi desentralisasi terapi ARV di beberapa kabupaten/kota prioritas di Papua.</li></ul>\"}}}},\"order\":[\"b01\",\"b02\",\"b03\",\"b04\",\"b05\",\"b06\",\"b07\",\"b08\",\"b09\",\"b10\",\"b11\",\"b12\",\"b13\",\"b14\",\"b15\",\"b16\",\"b17\",\"b18\",\"b19\",\"b20\",\"b21\",\"b22\",\"b23\",\"b24\",\"b25\",\"b26\",\"b27\"],\"settings\":{\"toc_position\":\"right\"}}','{\"id\":\"\"}','{\"id\":\"\"}','online','2026-10-09 22:47:40','2026-10-09 18:54:11','2026-10-09 22:47:40'),
(9,'{\"en\":\"Credibility\",\"id\":\"Kredibilitas\"}','{\"en\":\"credibility\",\"id\":\"kredibilitas\"}','{\"blocks\":{\"b01\":{\"id\":\"b01\",\"type\":\"heading\",\"data\":{\"text\":{\"en\":\"A track record with international and government partners\",\"id\":\"Rekam jejak bersama mitra internasional dan pemerintah\"},\"level\":\"h1\"}},\"b02\":{\"id\":\"b02\",\"type\":\"paragraph\",\"data\":{\"text\":{\"en\":\"<p>Since 2014, YSBH has implemented health programs funded by UNICEF, USAID, WHO, and provincial and district health offices. The table lists the projects in our profile; contract values are not shown.</p>\",\"id\":\"<p>Sejak 2014, YSBH melaksanakan program kesehatan yang didanai UNICEF, USAID, WHO, serta dinas kesehatan provinsi dan kabupaten. Tabel berikut memuat proyek yang tercantum dalam profil kami; nilai kontrak tidak ditampilkan.</p>\"}}},\"b03\":{\"id\":\"b03\",\"type\":\"paragraph\",\"data\":{\"text\":{\"en\":\"<table><thead><tr><th>Partner</th><th>Project</th><th>Period</th><th>Area</th></tr></thead><tbody><tr><td>UNICEF</td><td>Strengthening malaria control, integrated toward malaria elimination</td><td>May 2014 – Dec 2015</td><td>Papua Province</td></tr><tr><td>UNICEF</td><td>Integrated Micro-Planning at health center level</td><td>Oct 2016 – Dec 2017</td><td>Papua Province</td></tr><tr><td>UNICEF</td><td>Strengthening the quality of maternal and newborn health programs</td><td>Oct 2016 – Dec 2017</td><td>Papua Province</td></tr><tr><td>UNICEF</td><td>Strengthening malaria control, integrated toward malaria elimination</td><td>Oct 2016 – Dec 2017</td><td>Papua Province</td></tr><tr><td>USAID (Pact–FHI 360)</td><td>Support to the Provincial Health Office for HIV fast-track goals (first award)</td><td>Aug 2018 – 31 Jan 2019</td><td>Papua Province</td></tr><tr><td>USAID (Pact–FHI 360)</td><td>Support to the Provincial Health Office for HIV fast-track goals (second award)</td><td>May – Sep 2019</td><td>Papua Province</td></tr><tr><td>WHO</td><td>Surveillance of insecticide resistance in malaria vectors</td><td>4 Sep – 14 Nov 2020</td><td>Jayapura City and Jayapura District</td></tr><tr><td>WHO</td><td>Monitoring insecticide resistance</td><td>16 Oct – 30 Dec 2020</td><td>Waropen and Nabire Districts</td></tr><tr><td>Pegunungan Bintang District Health Office</td><td>Integrated maternal and child health services; reducing maternal and infant deaths and stunting</td><td>Feb – Nov 2026</td><td>Pegunungan Bintang District</td></tr><tr><td>Central Papua Provincial Health Office</td><td>Health system strengthening through integrated interventions</td><td>Mar – Nov 2026</td><td>Central Papua Province</td></tr><tr><td>Sarmi District Health Office</td><td>Health system strengthening to accelerate malaria elimination</td><td>2026</td><td>Sarmi District</td></tr></tbody></table>\",\"id\":\"<table><thead><tr><th>Mitra</th><th>Proyek</th><th>Periode</th><th>Wilayah</th></tr></thead><tbody><tr><td>UNICEF</td><td>Penguatan program pengendalian malaria, terpadu menuju eliminasi malaria</td><td>Mei 2014 – Des 2015</td><td>Provinsi Papua</td></tr><tr><td>UNICEF</td><td>Integrated Micro Planning di tingkat puskesmas</td><td>Okt 2016 – Des 2017</td><td>Provinsi Papua</td></tr><tr><td>UNICEF</td><td>Penguatan kualitas program kesehatan ibu dan bayi baru lahir</td><td>Okt 2016 – Des 2017</td><td>Provinsi Papua</td></tr><tr><td>UNICEF</td><td>Penguatan pengendalian malaria, terpadu menuju eliminasi malaria</td><td>Okt 2016 – Des 2017</td><td>Provinsi Papua</td></tr><tr><td>USAID (Pact–FHI 360)</td><td>Dukungan kepada Dinas Kesehatan Provinsi untuk fast track goals HIV (hibah pertama)</td><td>Agu 2018 – 31 Jan 2019</td><td>Provinsi Papua</td></tr><tr><td>USAID (Pact–FHI 360)</td><td>Dukungan kepada Dinas Kesehatan Provinsi untuk fast track goals HIV (hibah kedua)</td><td>Mei – Sep 2019</td><td>Provinsi Papua</td></tr><tr><td>WHO</td><td>Surveilans resistensi insektisida pada vektor malaria</td><td>4 Sep – 14 Nov 2020</td><td>Kota Jayapura dan Kabupaten Jayapura</td></tr><tr><td>WHO</td><td>Monitoring resistensi insektisida</td><td>16 Okt – 30 Des 2020</td><td>Kabupaten Waropen dan Nabire</td></tr><tr><td>Dinas Kesehatan Kab. Pegunungan Bintang</td><td>Layanan kesehatan ibu dan anak terpadu; penurunan kematian ibu dan bayi serta stunting</td><td>Feb – Nov 2026</td><td>Kab. Pegunungan Bintang</td></tr><tr><td>Dinas Kesehatan Provinsi Papua Tengah</td><td>Penguatan sistem kesehatan melalui intervensi terpadu</td><td>Mar – Nov 2026</td><td>Provinsi Papua Tengah</td></tr><tr><td>Dinas Kesehatan Kab. Sarmi</td><td>Penguatan sistem kesehatan untuk percepatan eliminasi malaria</td><td>2026</td><td>Kab. Sarmi</td></tr></tbody></table>\"}}},\"b04\":{\"id\":\"b04\",\"type\":\"paragraph\",\"data\":{\"text\":{\"en\":\"<p><strong>Financial audit.</strong> The foundation\'s financial statements were audited on 18 May 2016 by Moore Stephens LLP, which issued an unqualified opinion.</p>\",\"id\":\"<p><strong>Audit keuangan.</strong> Laporan keuangan yayasan diaudit pada 18 Mei 2016 oleh Moore Stephens LLP dengan opini wajar tanpa pengecualian (\\\"Unqualified\\\").</p>\"}}},\"b05\":{\"id\":\"b05\",\"type\":\"paragraph\",\"data\":{\"text\":{\"en\":\"<p>[ISI-DULU] Add a more recent audit, if one exists.</p>\",\"id\":\"<p>[ISI-DULU] Tambahkan audit yang lebih baru, bila ada.</p>\"}}},\"b06\":{\"id\":\"b06\",\"type\":\"heading\",\"data\":{\"text\":{\"en\":\"Our commitments\",\"id\":\"Komitmen kami\"},\"level\":\"h2\"}},\"b07\":{\"id\":\"b07\",\"type\":\"paragraph\",\"data\":{\"text\":{\"en\":\"<ul><li>Take an active role in improving public health.</li><li>Run sustainable, needs-based programs.</li><li>Uphold professionalism and partnership.</li></ul>\",\"id\":\"<ul><li>Berperan aktif meningkatkan derajat kesehatan masyarakat.</li><li>Menjalankan program berkelanjutan berbasis kebutuhan.</li><li>Menjunjung profesionalisme dan kemitraan.</li></ul>\"}}}},\"order\":[\"b01\",\"b02\",\"b03\",\"b04\",\"b05\",\"b06\",\"b07\"],\"settings\":{\"toc_position\":\"right\"}}','{\"id\":\"\"}','{\"id\":\"\"}','online','2026-10-09 22:47:52','2026-10-09 18:54:11','2026-10-09 22:47:52'),
(10,'{\"en\":\"Our Impact\",\"id\":\"Dampak Kami\"}','{\"en\":\"impact\",\"id\":\"dampak\"}','{\"blocks\":{\"b01\":{\"id\":\"b01\",\"type\":\"heading\",\"data\":{\"text\":{\"en\":\"What our work has changed\",\"id\":\"Apa yang berubah berkat kerja kami\"},\"level\":\"h1\"}},\"b02\":{\"id\":\"b02\",\"type\":\"paragraph\",\"data\":{\"text\":{\"en\":\"<p>Our work is about lasting change in how health systems plan, deliver and monitor care, especially in remote parts of Papua. These are results reported from our programs with UNICEF in 2015.</p>\",\"id\":\"<p>Kerja kami bertujuan menghasilkan perubahan yang bertahan dalam cara sistem kesehatan merencanakan, menjalankan, dan memantau layanan, terutama di wilayah terpencil Papua. Berikut hasil yang dilaporkan dari program kami bersama UNICEF pada 2015.</p>\"}}},\"b03\":{\"id\":\"b03\",\"type\":\"heading\",\"data\":{\"text\":{\"en\":\"Results reported (2015)\",\"id\":\"Hasil yang dilaporkan (2015)\"},\"level\":\"h2\"}},\"b04\":{\"id\":\"b04\",\"type\":\"paragraph\",\"data\":{\"text\":{\"en\":\"<ul><li>More health centers took part in Integrated Micro-Planning, reaching above 80% in UNICEF-supported areas.</li><li>Training curricula and modules were developed for community-based management of childhood illness (MTBS-M) and Integrated Micro-Planning.</li><li>Working groups for MTBS-M, and for water and sanitation, were formed at provincial and district level.</li><li>Monitoring and supervision of newborn health services and MTBS-M were carried out in remote villages.</li><li>Malaria incidence (annual parasite incidence) fell in several districts, such as Jayawijaya and Biak Numfor.</li><li>More villages reached open-defecation-free status as part of community-led total sanitation.</li></ul>\",\"id\":\"<ul><li>Cakupan puskesmas yang terlatih Integrated Micro-Planning meningkat hingga di atas 80% di wilayah binaan UNICEF.</li><li>Tersusun kurikulum dan modul pelatihan untuk MTBS-M dan Integrated Micro-Planning.</li><li>Terbentuk kelompok kerja (Pokja) MTBS-M dan AMPL di tingkat provinsi dan kabupaten.</li><li>Terlaksana monitoring dan supervisi layanan kesehatan neonatus serta MTBS-M di kampung terpencil.</li><li>Angka Annual Parasite Incidence (API) malaria menurun di beberapa kabupaten, seperti Jayawijaya dan Biak Numfor.</li><li>Bertambah jumlah kampung yang mencapai status Stop Buang Air Besar Sembarangan (SBS) sebagai bagian dari Sanitasi Total Berbasis Masyarakat (STBM).</li></ul>\"}}},\"b05\":{\"id\":\"b05\",\"type\":\"heading\",\"data\":{\"text\":{\"en\":\"Our reach\",\"id\":\"Jangkauan kami\"},\"level\":\"h2\"}},\"b06\":{\"id\":\"b06\",\"type\":\"paragraph\",\"data\":{\"text\":{\"en\":\"<ul><li>Provincial-scale programs in a region with demanding geography, including a mass bed-net campaign week across 16 malaria-endemic districts (2014).</li><li>Technical support to health offices at provincial and district level, in Papua and Central Papua.</li></ul>\",\"id\":\"<ul><li>Program berskala provinsi di wilayah dengan tantangan geografis tinggi, termasuk Pekan Kelambu Massal di 16 kabupaten endemis malaria (2014).</li><li>Dukungan teknis kepada dinas kesehatan di tingkat provinsi dan kabupaten, di Papua dan Papua Tengah.</li></ul>\"}}},\"b07\":{\"id\":\"b07\",\"type\":\"paragraph\",\"data\":{\"text\":{\"en\":\"<p>[ISI-DULU] Add recent results from 2016 onward (for example from the Pegunungan Bintang, Central Papua and Sarmi programs), each with its source.</p>\",\"id\":\"<p>[ISI-DULU] Tambahkan hasil terbaru sejak 2016 (misalnya dari program Pegunungan Bintang, Papua Tengah, dan Sarmi), masing-masing dengan sumbernya.</p>\"}}}},\"order\":[\"b01\",\"b02\",\"b03\",\"b04\",\"b05\",\"b06\",\"b07\"],\"settings\":{\"toc_position\":\"right\"}}','{\"id\":\"\"}','{\"id\":\"\"}','online','2026-10-09 22:47:58','2026-10-09 18:54:11','2026-10-09 22:47:58'),
(11,'{\"en\":\"Transparency\",\"id\":\"Transparansi\"}','{\"en\":\"transparency\",\"id\":\"transparansi\"}','{\"blocks\":{\"b01\":{\"id\":\"b01\",\"type\":\"heading\",\"data\":{\"text\":{\"en\":\"Transparency and legal status\",\"id\":\"Transparansi dan status hukum\"},\"level\":\"h1\"}},\"b02\":{\"id\":\"b02\",\"type\":\"paragraph\",\"data\":{\"text\":{\"en\":\"<p>We manage grants and public funds under donor and government standards, with planning, monitoring and evaluation, and technical and financial reporting. This page lists our legal basis and how we are governed.</p>\",\"id\":\"<p>Kami mengelola dana hibah dan dana publik sesuai standar donor dan pemerintah, dengan perencanaan, monitoring dan evaluasi, serta pelaporan teknis dan keuangan. Halaman ini memuat dasar hukum dan tata kelola yayasan.</p>\"}}},\"b03\":{\"id\":\"b03\",\"type\":\"heading\",\"data\":{\"text\":{\"en\":\"Legal status\",\"id\":\"Status hukum\"},\"level\":\"h2\"}},\"b04\":{\"id\":\"b04\",\"type\":\"paragraph\",\"data\":{\"text\":{\"en\":\"<ul><li>Name: Yayasan Sinar Bhakti Husada</li><li>Established: 2014</li><li>Field of activity: social and humanitarian work, especially health services and improving public health</li><li>Deed of establishment: Notary Puspo Adi Kuncoro, SH., MKn., No. 290, dated 13 June 2014</li><li>Ministry of Law and Human Rights decree (SK Kemenkumham): No. AHU-02604.50.10.2014</li></ul>\",\"id\":\"<ul><li>Nama: Yayasan Sinar Bhakti Husada</li><li>Tahun berdiri: 2014</li><li>Bidang kegiatan: sosial dan kemanusiaan, khususnya pelayanan dan peningkatan derajat kesehatan masyarakat</li><li>Akta pendirian: Notaris Puspo Adi Kuncoro, SH., MKn., No. 290 tanggal 13 Juni 2014</li><li>SK Kemenkumham: Nomor AHU-02604.50.10.2014</li></ul>\"}}},\"b05\":{\"id\":\"b05\",\"type\":\"heading\",\"data\":{\"text\":{\"en\":\"Governance and accountability\",\"id\":\"Tata kelola dan akuntabilitas\"},\"level\":\"h2\"}},\"b06\":{\"id\":\"b06\",\"type\":\"paragraph\",\"data\":{\"text\":{\"en\":\"<ul><li>Board and management structure: see <a href=\\\"internal://page/about-us\\\">About Us</a>.</li><li>Financial statements audited by Moore Stephens LLP (18 May 2016), unqualified opinion.</li><li>Projects and partners: see <a href=\\\"internal://page/credibility\\\">Credibility</a>.</li></ul>\",\"id\":\"<ul><li>Susunan pengurus dan organisasi pelaksana: lihat <a href=\\\"internal://page/tentang-kami\\\">Tentang Kami</a>.</li><li>Laporan keuangan diaudit oleh Moore Stephens LLP (18 Mei 2016), opini wajar tanpa pengecualian.</li><li>Proyek dan mitra: lihat <a href=\\\"internal://page/kredibilitas\\\">Kredibilitas</a>.</li></ul>\"}}},\"b07\":{\"id\":\"b07\",\"type\":\"heading\",\"data\":{\"text\":{\"en\":\"Tax ID and bank details\",\"id\":\"NPWP dan rekening\"},\"level\":\"h2\"}},\"sn1\":{\"id\":\"sn1\",\"type\":\"snippet\",\"data\":{\"snippet_id\":1}}},\"order\":[\"b01\",\"b02\",\"b03\",\"b04\",\"b05\",\"b06\",\"b07\",\"sn1\"],\"settings\":{\"toc_position\":\"right\"}}','{\"id\":\"\"}','{\"id\":\"\"}','online','2026-10-09 22:48:05','2026-10-09 18:54:11','2026-10-09 22:48:05'),
(12,'{\"en\":\"Contact Us\",\"id\":\"Hubungi Kami\"}','{\"en\":\"contact\",\"id\":\"kontak\"}','{\"blocks\":{\"b01\":{\"id\":\"b01\",\"type\":\"heading\",\"data\":{\"text\":{\"en\":\"Contact us\",\"id\":\"Hubungi kami\"},\"level\":\"h1\"}},\"b02\":{\"id\":\"b02\",\"type\":\"paragraph\",\"data\":{\"text\":{\"en\":\"<p>To discuss a partnership, a program, or support for our work, reach us through the details below.</p>\",\"id\":\"<p>Untuk membahas kemitraan, program, atau dukungan bagi kerja kami, hubungi kami melalui kontak berikut.</p>\"}}},\"b03\":{\"id\":\"b03\",\"type\":\"paragraph\",\"data\":{\"text\":{\"en\":\"<ul><li><strong>Address:</strong> BTN Umum Blok I No. 155, Kelurahan Awiyo, Distrik Abepura, Kota Jayapura, Papua, Indonesia (postal code: [ISI-DULU] 99351 or 99225)</li><li><strong>Phone:</strong> 0823 8229 5759</li><li><strong>Email:</strong> contact@ysbh.org</li><li><strong>Instagram:</strong> @ysbhusada</li><li><strong>Website:</strong> https://ysbh.org</li></ul>\",\"id\":\"<ul><li><strong>Alamat:</strong> BTN Umum Blok I No. 155, Kelurahan Awiyo, Distrik Abepura, Kota Jayapura, Papua, Indonesia (kode pos: [ISI-DULU] 99351 atau 99225)</li><li><strong>Telepon:</strong> 0823 8229 5759</li><li><strong>Email:</strong> contact@ysbh.org</li><li><strong>Instagram:</strong> @ysbhusada</li><li><strong>Situs web:</strong> https://ysbh.org</li></ul>\"}}},\"b04\":{\"id\":\"b04\",\"type\":\"paragraph\",\"data\":{\"text\":{\"en\":\"<p>[ISI-DULU] Contact form, office hours, and map: only if the foundation wants them.</p>\",\"id\":\"<p>[ISI-DULU] Formulir kontak, jam layanan, dan peta: hanya bila yayasan menginginkannya.</p>\"}}}},\"order\":[\"b01\",\"b02\",\"b03\",\"b04\"],\"settings\":{\"toc_position\":\"right\"}}','{\"id\":\"\"}','{\"id\":\"\"}','online','2026-10-09 22:48:14','2026-10-09 18:54:11','2026-10-09 22:48:14');
/*!40000 ALTER TABLE `pages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `passkeys`
--

DROP TABLE IF EXISTS `passkeys`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `passkeys` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `credential_id` varchar(255) NOT NULL,
  `credential` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`credential`)),
  `last_used_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `passkeys_credential_id_unique` (`credential_id`),
  KEY `passkeys_user_id_index` (`user_id`),
  CONSTRAINT `passkeys_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `passkeys`
--

LOCK TABLES `passkeys` WRITE;
/*!40000 ALTER TABLE `passkeys` DISABLE KEYS */;
/*!40000 ALTER TABLE `passkeys` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_tokens`
--

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `permissions`
--

DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `permissions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `permissions`
--

LOCK TABLES `permissions` WRITE;
/*!40000 ALTER TABLE `permissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `post_tags`
--

DROP TABLE IF EXISTS `post_tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `post_tags` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `post_id` bigint(20) unsigned NOT NULL,
  `tag_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `post_tags_post_id_foreign` (`post_id`),
  KEY `post_tags_tag_id_foreign` (`tag_id`),
  CONSTRAINT `post_tags_post_id_foreign` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `post_tags_tag_id_foreign` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `post_tags`
--

LOCK TABLES `post_tags` WRITE;
/*!40000 ALTER TABLE `post_tags` DISABLE KEYS */;
INSERT INTO `post_tags` VALUES
(1,1,2),
(2,1,3),
(3,1,5),
(4,1,6),
(5,2,4),
(6,2,5);
/*!40000 ALTER TABLE `post_tags` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `post_users`
--

DROP TABLE IF EXISTS `post_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `post_users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `post_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `post_users_post_id_foreign` (`post_id`),
  KEY `post_users_user_id_foreign` (`user_id`),
  CONSTRAINT `post_users_post_id_foreign` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `post_users_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `post_users`
--

LOCK TABLES `post_users` WRITE;
/*!40000 ALTER TABLE `post_users` DISABLE KEYS */;
/*!40000 ALTER TABLE `post_users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `posts`
--

DROP TABLE IF EXISTS `posts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `posts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `category_id` bigint(20) unsigned NOT NULL,
  `title` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`title`)),
  `slug` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`slug`)),
  `content` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`content`)),
  `meta_title` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`meta_title`)),
  `meta_description` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`meta_description`)),
  `featured_image` varchar(255) DEFAULT NULL,
  `status` enum('draft','review','published','scheduled','archived','rejected') NOT NULL DEFAULT 'draft',
  `published_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `posts_title_unique` (`title`) USING HASH,
  UNIQUE KEY `posts_slug_unique` (`slug`) USING HASH,
  KEY `posts_user_id_foreign` (`user_id`),
  KEY `posts_category_id_foreign` (`category_id`),
  CONSTRAINT `posts_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `posts_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `posts`
--

LOCK TABLES `posts` WRITE;
/*!40000 ALTER TABLE `posts` DISABLE KEYS */;
INSERT INTO `posts` VALUES
(1,2,5,'{\"en\":\"Ten Kilometer\",\"id\":\"Sepuluh Kilometer\"}','{\"en\":\"ten-kilometer\",\"id\":\"sepuluh-kilometer\"}','{\"blocks\":{\"blk_6ac95fa401715\":{\"id\":\"blk_6ac95fa401715\",\"type\":\"heading\",\"data\":{\"text\":{\"en\":\"Mama Bathsheba\'s struggle to ensure all her children were <span style=\\\"color: rgb(235, 204, 38);\\\"><em><strong class=\\\"font-bold\\\">immunized<\\/strong><\\/em><\\/span>.\",\"id\":\"perjuangan mama bathsheba memastikan semua anaknya diimunisasi\"},\"level\":\"h2\",\"margin_bottom\":\"mb-4 md:mb-6\"}},\"blk_mG6gvjLe\":{\"id\":\"blk_mG6gvjLe\",\"type\":\"paragraph\",\"data\":{\"text\":{\"en\":\"<p>Lorem ipsum dolor sit amet consectetur adipiscing elit. Quisque faucibus ex sapien vitae pellentesque sem placerat. In id cursus mi pretium tellus duis convallis. Tempus leo eu aenean sed diam urna tempor. Pulvinar vivamus fringilla lacus nec metus bibendum egestas. Iaculis massa nisl malesuada lacinia integer nunc posuere. Ut hendrerit semper vel class aptent taciti sociosqu. Ad litora torquent per conubia nostra inceptos himenaeos.<\\/p><p>Lorem ipsum dolor sit amet consectetur adipiscing elit. Quisque faucibus ex sapien vitae pellentesque sem placerat. In id cursus mi pretium tellus duis convallis. Tempus leo eu aenean sed diam urna tempor. Pulvinar vivamus fringilla lacus nec metus bibendum egestas. Iaculis massa nisl malesuada lacinia integer nunc posuere. Ut hendrerit semper vel class aptent taciti sociosqu. Ad litora torquent per conubia nostra inceptos himenaeos.<\\/p>\",\"id\":\"<p>Lorem ipsum dolor sit amet consectetur adipiscing elit. Quisque faucibus ex sapien vitae pellentesque sem placerat. In id cursus mi pretium tellus duis convallis. Tempus leo eu aenean sed diam urna tempor. Pulvinar vivamus fringilla lacus nec metus bibendum egestas. Iaculis massa nisl malesuada lacinia integer nunc posuere. Ut hendrerit semper vel class aptent taciti sociosqu. Ad litora torquent per conubia nostra inceptos himenaeos.<\\/p><p>Lorem ipsum dolor sit amet consectetur adipiscing elit. Quisque faucibus ex sapien vitae pellentesque sem placerat. In id cursus mi pretium tellus duis convallis. Tempus leo eu aenean sed diam urna tempor. Pulvinar vivamus fringilla lacus nec metus bibendum egestas. Iaculis massa nisl malesuada lacinia integer nunc posuere. Ut hendrerit semper vel class aptent taciti sociosqu. Ad litora torquent per conubia nostra inceptos himenaeos.<\\/p>\"},\"margin_bottom\":\"mb-4 md:mb-6\"}}},\"order\":[\"blk_6ac95fa401715\",\"blk_mG6gvjLe\"],\"settings\":{\"toc_position\":\"right\"}}','{\"en\":\"\",\"id\":\"\"}','{\"en\":\"Even though Mama Bathsheba lives in a village far from the health center, it doesn\'t change Mama\'s intention to ensure her children get immunizations.\",\"id\":\"meskipun mama bathsheba tinggal di kampung yang jauh dari puskesmas, tidak mengungari niat mama untuk memastikan anak-anaknya mendapatkan imunisasi \"}','default.webp','published','2026-10-09 23:01:59','2026-10-09 21:46:34','2026-10-09 23:01:59'),
(2,2,5,'{\"en\":\"extramiles\",\"id\":\"extramiles\"}','{\"en\":\"extramiles\",\"id\":\"extramiles\"}','{\"blocks\":{\"blk_6ac97cfb294a9\":{\"id\":\"blk_6ac97cfb294a9\",\"type\":\"heading\",\"data\":{\"text\":{\"en\":\"\",\"id\":\"\"},\"level\":\"h2\",\"margin_bottom\":\"mb-4 md:mb-6\"}}},\"order\":[\"blk_6ac97cfb294a9\"],\"settings\":{\"toc_position\":\"right\"}}','{\"en\":\"\",\"id\":\"\"}','{\"en\":\"\",\"id\":\"\"}','default.webp','review',NULL,'2026-10-09 23:48:41','2026-10-09 23:49:11');
/*!40000 ALTER TABLE `posts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `role_has_permissions`
--

DROP TABLE IF EXISTS `role_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `role_has_permissions` (
  `permission_id` bigint(20) unsigned NOT NULL,
  `role_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`role_id`),
  KEY `role_has_permissions_role_id_foreign` (`role_id`),
  CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `role_has_permissions`
--

LOCK TABLES `role_has_permissions` WRITE;
/*!40000 ALTER TABLE `role_has_permissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `role_has_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES
(1,'admin','web','2026-10-09 18:54:08','2026-10-09 18:54:08'),
(2,'editor','web','2026-10-09 18:54:08','2026-10-09 18:54:08'),
(3,'writer','web','2026-10-09 18:54:08','2026-10-09 18:54:08');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
INSERT INTO `sessions` VALUES
('6C4vJGXOuNYoROe07L0Qm180m5gLRJUTGSDafkz3',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJ5a3VsQ1JRYWEwQ3ptVXpzQ1o2bHE1eG1wYWJ5b0RXNzloYlU5U0NhIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cL2xhbmRpbmcteXNiaC50ZXN0XC9hcnRpY2xlcyIsInJvdXRlIjoiYXJ0aWNsZXMifSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',1791717162),
('Rh09L82NuAWbinwh03IkEmRrvPtEhsnyOdAeNTNG',2,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','eyJfdG9rZW4iOiI1aWk0RUdBU25Gb2wweHoxbE5JR0VrdEVUOWtsVkRtbVJudGdrSEtWIiwibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiOjIsIl9wcmV2aW91cyI6eyJ1cmwiOiJodHRwOlwvXC9jbXMteXNiaC50ZXN0XC9wcmV2aWV3XC9hYWVhOWZhMjg2OTQyZGIxNTNmMWYzNTE0YjM4ZTdlODk3NjU4N2M1P2xhbmc9ZW4iLCJyb3V0ZSI6InByZXZpZXcuZnJhbWUifSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',1791716205),
('Woxf11BAv1Jxqvx9ti2zcZkHIxrNBJGZb9WW7zE1',2,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJ0Vk5haWNUMGxucUlYOVV2Tm1NeHBOVXE2Z1U0SWdLekV1dFdOd24zIiwibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiOjIsIl9wcmV2aW91cyI6eyJ1cmwiOiJodHRwOlwvXC9jbXMteXNiaC50ZXN0XC9wcmV2aWV3XC9jYWYxZWQ1N2ZiZTMxNTBjMDM3N2Q1OTA0ZDMxODk0YmIzZDcwNzJhP2xhbmc9ZW4iLCJyb3V0ZSI6InByZXZpZXcuZnJhbWUifSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',1791719631);
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(255) NOT NULL,
  `value` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `settings_key_unique` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `settings`
--

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `snippet_usages`
--

DROP TABLE IF EXISTS `snippet_usages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `snippet_usages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `snippet_id` bigint(20) unsigned NOT NULL,
  `usable_type` varchar(255) NOT NULL,
  `usable_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `snippet_usage_unique` (`snippet_id`,`usable_type`,`usable_id`),
  KEY `snippet_usages_usable_type_usable_id_index` (`usable_type`,`usable_id`),
  CONSTRAINT `snippet_usages_snippet_id_foreign` FOREIGN KEY (`snippet_id`) REFERENCES `snippets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `snippet_usages`
--

LOCK TABLES `snippet_usages` WRITE;
/*!40000 ALTER TABLE `snippet_usages` DISABLE KEYS */;
/*!40000 ALTER TABLE `snippet_usages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `snippets`
--

DROP TABLE IF EXISTS `snippets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `snippets` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(80) NOT NULL,
  `title` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`title`)),
  `description` text DEFAULT NULL,
  `content` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`content`)),
  `status` varchar(20) NOT NULL DEFAULT 'offline',
  `is_closing` tinyint(1) NOT NULL DEFAULT 0,
  `sort_order` smallint(5) unsigned NOT NULL DEFAULT 0,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `snippets_key_unique` (`key`),
  KEY `snippets_created_by_foreign` (`created_by`),
  KEY `snippets_updated_by_foreign` (`updated_by`),
  KEY `snippets_is_closing_status_sort_order_index` (`is_closing`,`status`,`sort_order`),
  CONSTRAINT `snippets_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `snippets_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `snippets`
--

LOCK TABLES `snippets` WRITE;
/*!40000 ALTER TABLE `snippets` DISABLE KEYS */;
INSERT INTO `snippets` VALUES
(1,'legal-details','{\"en\":\"Legal and bank details\",\"id\":\"Data hukum dan rekening\"}','Tempat NPWP dan rekening (giro/bank) yayasan. Isi hanya setelah yayasan memutuskan apa yang boleh ditampilkan. Selama masih memuat [ISI-DULU], jangan dijadikan online (cms:audit-placeholders).','{\"blocks\":{\"p1\":{\"id\":\"p1\",\"type\":\"paragraph\",\"data\":{\"text\":{\"en\":\"<p>Tax ID (NPWP): [ISI-DULU]<\\/p>\",\"id\":\"<p>NPWP: [ISI-DULU]<\\/p>\"}}},\"p2\":{\"id\":\"p2\",\"type\":\"paragraph\",\"data\":{\"text\":{\"en\":\"<p>Bank \\/ giro account: [ISI-DULU]<\\/p>\",\"id\":\"<p>Rekening giro\\/bank: [ISI-DULU]<\\/p>\"}}}},\"order\":[\"p1\",\"p2\"],\"settings\":[]}','offline',0,0,NULL,NULL,'2026-10-09 18:54:11','2026-10-09 18:54:11',NULL);
/*!40000 ALTER TABLE `snippets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tags`
--

DROP TABLE IF EXISTS `tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tags` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`name`)),
  `slug` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`slug`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tags`
--

LOCK TABLES `tags` WRITE;
/*!40000 ALTER TABLE `tags` DISABLE KEYS */;
INSERT INTO `tags` VALUES
(1,'{\"id\":\"Nutrisi\",\"en\":\"Nutrition\"}','{\"id\":\"nutrisi\",\"en\":\"nutrition\"}','2026-07-30 15:00:00','2026-10-09 18:54:08'),
(2,'{\"id\":\"Lansia\",\"en\":\"Elderly\"}','{\"id\":\"lansia\",\"en\":\"elderly\"}','2026-07-30 15:00:00','2026-10-09 18:54:08'),
(3,'{\"id\":\"Posyandu\",\"en\":\"Integrated Health Post\"}','{\"id\":\"posyandu\",\"en\":\"integrated-health-post\"}','2026-07-30 15:00:00','2026-10-09 18:54:08'),
(4,'{\"id\":\"Kota Jayapura\",\"en\":\"Jayapura City\"}','{\"id\":\"kota-jayapura\",\"en\":\"jayapura-city\"}','2026-07-30 15:00:00','2026-10-09 18:54:08'),
(5,'{\"id\":\"Kabupaten Jayapura\",\"en\":\"Jayapura Regency\"}','{\"id\":\"kota-jayapura\",\"en\":\"jayapura-regency\"}','2026-07-30 15:00:00','2026-10-09 18:54:08'),
(6,'{\"en\":\"Immunization\",\"id\":\"Immunization\"}','{\"en\":\"immunization\",\"id\":\"immunization\"}','2026-10-09 21:46:34','2026-10-09 21:46:34');
/*!40000 ALTER TABLE `tags` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `handle` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `job_title` varchar(255) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `two_factor_secret` text DEFAULT NULL,
  `two_factor_recovery_codes` text DEFAULT NULL,
  `two_factor_confirmed_at` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_handle_unique` (`handle`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES
(1,'Test User','guest','test@example.com','Staff',1,'2026-10-09 18:54:07',NULL,'$2y$12$uyH0iYWtrv4m/MC4k8FS0OktXS9I/pV.V2LskrH0H3YfyZLrv0G6O',NULL,NULL,NULL,'I7YXaFT5CJ','2026-10-09 18:54:08','2026-10-09 18:54:08'),
(2,'Alfrida Pabasi','apabasi','midar@mail.com','Ketua Yayasan',1,'2026-10-09 18:54:08',NULL,'$2y$12$DnORK4ElfHWz4euHP9yZauIQzVr3W1ihXIH8L7FRNnTP/IegEmBvC',NULL,NULL,NULL,'wnQH9DVi12','2026-10-09 18:54:08','2026-10-09 18:54:08'),
(3,'ELizabeth Kristiani','ekristiani','liza@mail.com','Bendahara',1,'2026-10-09 18:54:08',NULL,'$2y$12$f/uHw2g45LT8dZIH5Rl.9uGOqxkGBhk9.CwCkx2gj8A/R6Er0FTTO',NULL,NULL,NULL,'AW2VnY0ETS','2026-10-09 18:54:08','2026-10-09 18:54:08'),
(4,'Ruth Charlota Yakoba Fouw','rfouw','utha@example.com','Program Officer Kesehatan Ibu & Anak',1,'2026-10-09 18:54:08',NULL,'$2y$12$Yh3wLc9xZ.gMdBB5Y7xZQOcBsQpokk8KSGv9buv1pdNABP09vvDCS',NULL,NULL,NULL,'qxGToMpoz1','2026-10-09 18:54:08','2026-10-09 18:54:08'),
(5,'Leon Dolfus Mangonto','lmangonto','leon@mail.com','Program Officer KIA',0,'2026-10-09 18:54:08',NULL,'$2y$12$zCMm.hxHJ4tRocwWdjtzHuqJuWofNK9BCkyxsRdqsp0usyA7BqV9S',NULL,NULL,NULL,'mMo4FoMDOq','2026-10-09 18:54:08','2026-10-09 18:54:08');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'ysbh-app'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*M!100616 SET NOTE_VERBOSITY=@OLD_NOTE_VERBOSITY */;

-- Dump completed on 2026-10-11 21:17:33
