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
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `categories_slug_unique` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES
(1,'Berita','berita','2026-07-16 02:45:56','2026-07-16 02:45:56'),
(2,'Siaran Pers','siaran-pers','2026-07-16 02:45:56','2026-07-16 02:45:56'),
(3,'Pengumuman','pengumuman','2026-07-16 02:45:56','2026-07-16 02:45:56'),
(4,'Program & Kampanye','program','2026-07-16 02:45:56','2026-07-16 02:45:56'),
(5,'Kisah Inspiratif','cerita','2026-07-16 02:45:56','2026-07-16 02:45:56'),
(6,'Laporan & Transparansi','laporan','2026-07-16 02:45:56','2026-07-16 02:45:56'),
(7,'Tulisan Edukasi','blog','2026-07-16 02:45:56','2026-07-16 02:45:56'),
(8,'Acara & Kegiatan','events','2026-07-16 02:45:56','2026-07-16 02:45:56');
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
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
(7,'2026_05_28_141619_c_m_sdb_migration',1);
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
(1,'App\\Models\\User',2),
(1,'App\\Models\\User',3),
(2,'App\\Models\\User',3),
(3,'App\\Models\\User',4),
(3,'App\\Models\\User',5);
/*!40000 ALTER TABLE `model_has_roles` ENABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `post_tags`
--

LOCK TABLES `post_tags` WRITE;
/*!40000 ALTER TABLE `post_tags` DISABLE KEYS */;
INSERT INTO `post_tags` VALUES
(1,1,1),
(2,2,7),
(3,3,6),
(4,4,1),
(5,5,3),
(6,6,4),
(7,7,5),
(8,8,5),
(9,8,4);
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
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `content` longtext NOT NULL,
  `featured_image` varchar(255) DEFAULT NULL,
  `status` enum('draft','review','published','scheduled','archived','rejected') NOT NULL DEFAULT 'draft',
  `published_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `posts_title_unique` (`title`),
  UNIQUE KEY `posts_slug_unique` (`slug`),
  KEY `posts_user_id_foreign` (`user_id`),
  KEY `posts_category_id_foreign` (`category_id`),
  CONSTRAINT `posts_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `posts_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `posts`
--

LOCK TABLES `posts` WRITE;
/*!40000 ALTER TABLE `posts` DISABLE KEYS */;
INSERT INTO `posts` VALUES
(1,2,4,'template imunisasi','template-imunisasi','<h1 style=\"text-align: center;\"><span style=\"font-family: Arial, Arial_EmbeddedFont, Arial_MSFontService, sans-serif;\"><strong>Melindungi Masa Depan Generasi Papua Tengah</strong>&nbsp;</span></h1><p style=\"text-align: left;\"><img src=\"https://cms-ysbh.test/storage/articles/1lFLSJSCzF00cFJAmYsMC1VDhG2Kx4d60fzeuLkR.webp\" alt=\"pasted-inline-0.webp\" title=\"pasted-inline-0.webp\" class=\"rounded-lg max-w-full my-2 transition-all cursor-pointer tiptap-uploaded-image inline-block\" style=\"width: 100%; display: block !important; margin: 0.75rem auto !important; float: none !important;\"><span style=\"font-family: Arial, Arial_EmbeddedFont, Arial_MSFontService, sans-serif;\">&nbsp;</span></p><p style=\"text-align: justify;\"><span style=\"font-family: Arial, Arial_EmbeddedFont, Arial_MSFontService, sans-serif;\">Program Imunisasi Yayasan Sinar Bhakti Husada (YSBH) berkomitmen mendampingi pemerintah daerah dalam memastikan setiap anak di Papua Tengah memiliki benteng perlindungan terhadap penyakit yang dapat dicegah. Melalui pendekatan inovatif dan sensitif budaya, kami berupaya menurunkan angka anak <em>zero dose</em> demi memastikan keberlangsungan generasi masa depan Papua yang sehat dan tangguh.&nbsp;</span></p><p style=\"text-align: justify;\"><span style=\"font-family: Arial, Arial_EmbeddedFont, Arial_MSFontService, sans-serif;\"><strong>Kami Bertindak di Tengah Krisis Imunisasi</strong>&nbsp;</span></p><p style=\"text-align: justify;\"><img src=\"https://cms-ysbh.test/storage/articles/fygOIITKhx3yCP8HGPlaXfpIWwqmdLJaVLXwV8OF.webp\" alt=\"pasted-inline-1.webp\" title=\"pasted-inline-1.webp\" class=\"rounded-lg max-w-full my-2 transition-all cursor-pointer tiptap-uploaded-image inline-block\" style=\"width: 25%; float: right; margin-left: 1rem; margin-top: 0.25rem; margin-bottom: 0.5rem; display: inline !important;\"><span style=\"font-family: Arial, Arial_EmbeddedFont, Arial_MSFontService, sans-serif;\">Situasi imunisasi di Provinsi Papua Tengah saat ini memerlukan perhatian mendesak dari seluruh pemangku kepentingan. Hingga tahun 2024, cakupan Imunisasi Dasar Lengkap (IDL) baru mencapai 12%, sebuah penurunan drastis dibandingkan tahun sebelumnya. Dampaknya adalah kemunculan kembali wabah penyakit mematikan seperti Campak, Polio, dan Difteri yang silih berganti menyerang anak-anak kita dalam beberapa tahun terakhir. Ketertinggalan ini bukan sekadar angka, melainkan ancaman nyata bagi keberlangsungan masyarakat Papua. Setiap wabah yang terjadi mengakibatkan duka mendalam bagi keluarga dan beban ekonomi yang besar. Tanpa intervensi yang sistematis dan berkelanjutan, kita berisiko kehilangan generasi emas yang akan membangun Tanah Papua di masa depan.&nbsp;</span></p><p style=\"text-align: justify;\"><span style=\"font-family: Arial, Arial_EmbeddedFont, Arial_MSFontService, sans-serif;\"><strong>Lima Pilar Intervensi Program Imunisasi Yayasan Sinar Bhakti Husada</strong>&nbsp;</span></p><p style=\"text-align: justify;\"><span style=\"font-family: Arial, Arial_EmbeddedFont, Arial_MSFontService, sans-serif;\"><strong>Penguatan Regulasi dan Kebijakan Imunisasi</strong>&nbsp;</span></p><p style=\"text-align: justify;\"><img src=\"https://cms-ysbh.test/storage/articles/RftBoS4ZtnJvfAGsmOo0C9hJblbSYqX5NlAj0Ccb.webp\" alt=\"pasted-inline-2.webp\" title=\"pasted-inline-2.webp\" class=\"rounded-lg max-w-full my-2 transition-all cursor-pointer tiptap-uploaded-image inline-block\" style=\"width: 50%; float: left; margin-right: 1rem; margin-top: 0.25rem; margin-bottom: 0.5rem; display: inline !important;\"><span style=\"font-family: Arial, Arial_EmbeddedFont, Arial_MSFontService, sans-serif;\">Kami percaya bahwa keberlanjutan program imunisasi harus berakar pada komitmen politik dan hukum yang kuat di tingkat daerah. YSBH memberikan pendampingan teknis kepada pemerintah provinsi dan kabupaten untuk menyusun payung hukum yang kuat, termasuk Peraturan Gubernur Papua Tengah serta Peraturan Bupati di Kabupaten Nabire, Dogiyai, Deiyai, dan Puncak Jaya. Regulasi ini sangat krusial untuk memastikan adanya alokasi anggaran daerah yang pasti, tata kelola logistik vaksin yang lebih baik, serta integrasi layanan imunisasi ke dalam rencana pembangunan daerah.&nbsp;</span></p><p style=\"text-align: justify;\"><span style=\"font-family: Arial, Arial_EmbeddedFont, Arial_MSFontService, sans-serif;\">Melalui regulasi yang jelas, imunisasi tidak lagi dipandang sebagai kegiatan kesehatan rutin semata, melainkan sebagai kewajiban pemerintah untuk melindungi hak asasi anak-anak Papua. Kebijakan ini juga bertujuan untuk mengunci dukungan jangka panjang dari para pengambil keputusan, sehingga program imunisasi tetap berjalan stabil meskipun terjadi pergantian kepemimpinan atau dinamika politik di daerah.&nbsp;</span></p><p style=\"text-align: justify;\"></p><p style=\"text-align: justify;\"></p><p style=\"text-align: justify;\"><span style=\"font-family: Arial, Arial_EmbeddedFont, Arial_MSFontService, sans-serif;\"><strong>Pengembangan Puskesmas Model OJT (<em>On-the-Job Training</em>) Center</strong>&nbsp;</span></p><p style=\"text-align: justify;\"><span style=\"font-family: Arial, Arial_EmbeddedFont, Arial_MSFontService, sans-serif;\">Tantangan geografis Papua membuat biaya pelatihan konvensional menjadi sangat mahal, sementara rotasi petugas kesehatan yang cepat seringkali meninggalkan kekosongan tenaga terlatih di daerah terpencil. Sebagai solusinya, YSBH mengembangkan Puskesmas Model OJT Center di lokasi strategis seperti Puskesmas Wanggar Sari dan Topo (Nabire), Puskesmas Waghete (Deiyai), Puskesmas Moanemani (Dogiyai), dan Puskesmas Mulia (Puncak Jaya). Pusat ini berfungsi sebagai laboratorium pembelajaran praktis bagi tenaga medis untuk memperdalam keterampilan imunisasi secara langsung di lapangan.&nbsp;</span></p><p style=\"text-align: justify;\"><span style=\"font-family: Arial, Arial_EmbeddedFont, Arial_MSFontService, sans-serif;\">Model ini sangat efektif karena memanfaatkan pola mobilitas petugas kesehatan dari daerah terpencil yang sering berkunjung ke ibu kota kabupaten untuk urusan logistik atau pribadi. Saat mereka berada di ibu kota, mereka dapat mengikuti magang singkat dengan waktu fleksibel tanpa harus meninggalkan tugas mereka terlalu lama. Pendekatan ini tidak hanya menekan biaya transportasi secara signifikan, tetapi juga membangun keterikatan dan budaya saling belajar antar Puskesmas guna menciptakan sumber daya manusia yang kompeten secara berkelanjutan.&nbsp;</span></p><p style=\"text-align: justify;\"><img src=\"https://cms-ysbh.test/storage/articles/kqRLu89IMFvPuNNLMpXcJ3AR1dA6OFQcj0g1TcLB.webp\" alt=\"pasted-inline-4.webp\" title=\"pasted-inline-4.webp\" class=\"rounded-lg max-w-full my-2 transition-all cursor-pointer tiptap-uploaded-image inline-block\" style=\"width: 25%; float: right; margin-left: 1rem; margin-top: 0.25rem; margin-bottom: 0.5rem; display: inline !important;\"><span style=\"font-family: Arial, Arial_EmbeddedFont, Arial_MSFontService, sans-serif;\"><strong>Implementasi <em>Human Centered Design </em>(HCD)</strong>&nbsp;</span></p><p style=\"text-align: justify;\"><span style=\"font-family: Arial, Arial_EmbeddedFont, Arial_MSFontService, sans-serif;\">Program imunisasi seringkali menghadapi hambatan berupa keraguan atau ketakutan masyarakat terhadap vaksin. Melalui implementasi <em>Human-Centered Design</em> (HCD), YSBH berupaya memahami sisi kemanusiaan dan sosial-budaya di balik rendahnya cakupan imunisasi. Kami tidak hanya memberikan edukasi satu arah, melainkan duduk bersama orang tua, tokoh masyarakat, dan petugas kesehatan untuk mendengarkan kekhawatiran mereka dan bersama-sama merancang solusi yang sesuai dengan konteks lokal.&nbsp;</span></p><p style=\"text-align: justify;\"><span style=\"font-family: Arial, Arial_EmbeddedFont, Arial_MSFontService, sans-serif;\">Pendekatan ini bertujuan untuk menciptakan layanan imunisasi yang lebih ramah, berempati, dan dapat dipercaya oleh masyarakat Papua. Dengan memahami hambatan dari sudut pandang pengguna layanan, kami dapat mengembangkan strategi komunikasi risiko yang menyentuh hati dan mengubah persepsi masyarakat terhadap vaksin. HCD memastikan bahwa setiap anak mendapatkan layanan yang tidak hanya berkualitas secara medis, tetapi juga dihargai secara budaya.&nbsp;</span></p><p style=\"text-align: justify;\"><span style=\"font-family: Arial, Arial_EmbeddedFont, Arial_MSFontService, sans-serif;\">&nbsp;</span></p><p style=\"text-align: justify;\"><span style=\"font-family: Arial, Arial_EmbeddedFont, Arial_MSFontService, sans-serif;\">&nbsp;</span></p><p style=\"text-align: justify;\"><span style=\"font-family: Arial, Arial_EmbeddedFont, Arial_MSFontService, sans-serif;\">&nbsp;</span></p><p style=\"text-align: justify;\"><span style=\"font-family: Arial, Arial_EmbeddedFont, Arial_MSFontService, sans-serif;\">&nbsp;</span></p><p style=\"text-align: justify;\"><span style=\"font-family: Arial, Arial_EmbeddedFont, Arial_MSFontService, sans-serif;\"><strong>Penguatan Kolaborasi Lintas Sektor</strong>&nbsp;</span></p><p style=\"text-align: justify;\"><span style=\"font-family: Arial, Arial_EmbeddedFont, Arial_MSFontService, sans-serif;\">Imunisasi adalah tanggung jawab bersama yang tidak bisa hanya dipikul oleh sektor kesehatan sendirian. YSBH aktif menggerakkan kolaborasi lintas sektor yang melibatkan tokoh adat, pemuka agama, pemerintah desa, hingga sektor swasta di Papua Tengah. Kami percaya bahwa ketika seorang pendeta, kepala suku, atau kepala desa menyuarakan pentingnya imunisasi, pesan tersebut akan memiliki daya terima yang jauh lebih kuat di tengah masyarakat.&nbsp;</span></p><p style=\"text-align: justify;\"><span style=\"font-family: Arial, Arial_EmbeddedFont, Arial_MSFontService, sans-serif;\">Kerja sama ini mencakup pemanfaatan dana desa untuk mendukung mobilisasi sasaran, keterlibatan tokoh agama dalam sosialisasi di rumah-rumah ibadah, hingga dukungan logistik dari sektor lain. Dengan memperkuat sinergi ini, kami membangun ekosistem pendukung yang solid untuk melindungi generasi penerus bangsa. Kolaborasi ini adalah kunci untuk mencapai setiap anak, bahkan di wilayah yang paling sulit dijangkau sekalipun.&nbsp;</span></p><p style=\"text-align: justify;\"><span style=\"font-family: Arial, Arial_EmbeddedFont, Arial_MSFontService, sans-serif;\"><strong>Pembentukan dan Penguatan Tim Fasilitator Imunisasi Provinsi</strong>&nbsp;</span></p><p style=\"text-align: justify;\"><img src=\"https://cms-ysbh.test/storage/articles/pmDGdW574eKN2MWU6Y6og3KxmGKjlJHL4IX5D5Zj.webp\" alt=\"pasted-inline-6.webp\" title=\"pasted-inline-6.webp\" class=\"rounded-lg max-w-full my-2 transition-all cursor-pointer tiptap-uploaded-image inline-block\" style=\"width: 25%; float: right; margin-left: 1rem; margin-top: 0.25rem; margin-bottom: 0.5rem; display: inline !important;\"><span style=\"font-family: Arial, Arial_EmbeddedFont, Arial_MSFontService, sans-serif;\">Untuk menjamin kualitas dan keberlanjutan pendampingan teknis, YSBH memfasilitasi pembentukan Tim Fasilitator Imunisasi di tingkat Provinsi Papua Tengah. Tim ini terdiri dari para tenaga ahli lokal yang telah dibekali dengan pengetahuan mendalam mengenai manajemen imunisasi, pemantauan kualitas, hingga teknik komunikasi persuasif. Peran mereka adalah menjadi penggerak utama dalam memberikan bimbingan teknis (<em>technical assistance</em>) yang berkelanjutan kepada kabupaten-kabupaten dampingan.&nbsp;</span></p><p style=\"text-align: justify;\"><span style=\"font-family: Arial, Arial_EmbeddedFont, Arial_MSFontService, sans-serif;\">Keberadaan tim fasilitator ini bertujuan untuk menciptakan kemandirian daerah dalam jangka panjang. Dengan memiliki tim ahli yang siap sedia di tingkat provinsi, proses transfer pengetahuan ke Puskesmas-Puskesmas akan berjalan lebih cepat dan konsisten. Ini adalah upaya kami untuk meninggalkan warisan berupa sistem kesehatan yang tangguh dan SDM lokal yang mumpuni untuk menjaga kesehatan anak-anak Papua tanpa ketergantungan pada pihak luar di masa depan.&nbsp;</span></p><p style=\"text-align: justify;\"><span style=\"font-family: Arial, Arial_EmbeddedFont, Arial_MSFontService, sans-serif;\">&nbsp;</span></p><p style=\"text-align: justify;\"><span style=\"font-family: Arial, Arial_EmbeddedFont, Arial_MSFontService, sans-serif;\"><strong>Investasi Kemanusiaan: Mengapa Anda Harus Peduli?</strong>&nbsp;</span></p><p style=\"text-align: justify;\"><span style=\"font-family: Arial, Arial_EmbeddedFont, Arial_MSFontService, sans-serif;\">Mendukung program imunisasi di Papua Tengah adalah investasi dengan dampak kemanusiaan dan ekonomi yang sangat besar. Ketika seorang anak jatuh sakit atau cacat akibat penyakit yang seharusnya bisa dicegah, dampaknya meluas ke seluruh aspek kehidupan: keluarga kehilangan waktu produktif, beban biaya pengobatan meningkat, dan yang paling menyedihkan adalah hilangnya potensi masa depan sang anak sebagai penerus masyarakat Papua.&nbsp;</span></p><p style=\"text-align: justify;\"><span style=\"font-family: Arial, Arial_EmbeddedFont, Arial_MSFontService, sans-serif;\">Setiap dukungan yang Anda berikan membantu kami memperkuat Puskesmas Model, melatih fasilitator lokal, dan memastikan vaksin sampai ke lengan anak-anak di kampung-kampung terpencil. Bersama-sama, kita bisa memastikan bahwa anak-anak Papua tumbuh sehat, cerdas, dan siap untuk memimpin masa depan mereka sendiri. Bergabunglah bersama kami untuk melindungi setiap nyawa, karena setiap anak Papua berhak atas masa depan yang sehat.&nbsp;</span></p><p style=\"text-align: justify;\"><span style=\"font-family: Arial, Arial_EmbeddedFont, Arial_MSFontService, sans-serif;\">&nbsp;</span></p><p style=\"text-align: justify;\"><span style=\"font-family: Arial, Arial_EmbeddedFont, Arial_MSFontService, sans-serif;\">&nbsp;</span></p><p style=\"text-align: left;\"><span style=\"font-family: Aptos, Aptos_EmbeddedFont, Aptos_MSFontService, sans-serif;\">&nbsp;</span></p>','default.webp','published',NULL,'2026-07-16 04:17:55','2026-07-16 04:17:55'),
(2,2,4,'template malaria','template-malaria','<p><span style=\"font-family: Candara, Candara_EmbeddedFont, Candara_MSFontService, sans-serif;\">Tanah Papua dengan 6 provinsi mandiri dan 42 kabupatennya, merupakan wilayah penyumbang 90% kasus malaria di Indonesia, di mana menurut laporan E-SISMAL Tahun 2025, Papua Tengah mencatat ada 204.068 kasus malaria. Kabupaten Mimika menjadi penyumbang terbesar di provinsi ini, dengan total 190.597 kasus, diikuti oleh Kabupaten Nabire sebanyak&nbsp; 8.744 kasus dan beberapa kabupaten lainnya. Sedangkan di wilayah provinsi dan kabupaten lainnya, angka kasus bervariasi, termasuk di Kabupaten di pesisir seperti di Kabupaten Sarmi, Provinsi Papua, maupun di pegunungan, seperti di Kabupaten Yahukimo, Provinsi Papua Pegunungan. Tingginya beban kasus tersebut menunjukkan bahwa malaria masih menjadi tantangan kesehatan masyarakat yang memerlukan perhatian dan upaya pengendalian secara berkelanjutan.&nbsp;&nbsp;</span></p><p style=\"text-align: justify;\"><span style=\"font-family: Candara, Candara_EmbeddedFont, Candara_MSFontService, sans-serif;\">Dibalik tingginya jumlah kasus, malaria memberikan dampak yang lebih besar pada kelompok rentan seperti ibu hamil, bayi dan balita. Infeksi malaria pada kelompok tersebut dapat meningkatkan resiko gangguan kesehatan yang berdampak pada kualitas hidup dan tumbuh kembang anak. Kondisi ini menunjukkan bahwa eliminasi malaria bukan hanya tentang menurunkan angka kasus, tetapi juga melindungi generasi masa depan Papua secara keseluruhan.&nbsp;&nbsp;</span></p><p style=\"text-align: justify;\"><span style=\"font-family: Candara, Candara_EmbeddedFont, Candara_MSFontService, sans-serif;\">Eliminasi malaria di Tanah Papua, termasuk di Papua Tengah dan Papua Pegunungan, memerlukan pendekatan yang komprehensif dan berkelanjutan, oleh karena itu upaya percepatan eliminasi malaria tidak hanya berfokus pada penanganan kasus tetapi juga pada penguatan sistem kesehatan.&nbsp;&nbsp;</span></p><p style=\"text-align: justify;\"><img src=\"https://cms-ysbh.test/storage/articles/AQhdmkov2Xq09l8pfviRhZL9mieZQSXgVrvYNVe1.webp\" alt=\"pasted-inline-0.webp\" title=\"pasted-inline-0.webp\" class=\"rounded-lg max-w-full my-2 transition-all cursor-pointer tiptap-uploaded-image inline-block\" style=\"width: 25%; display: block !important; margin: 0.75rem auto !important; float: none !important;\"><span style=\"font-family: Candara, Candara_EmbeddedFont, Candara_MSFontService, sans-serif;\">&nbsp;</span></p><p style=\"text-align: justify;\"><span style=\"font-family: Candara, Candara_EmbeddedFont, Candara_MSFontService, sans-serif;\"><strong>Picture : On the job training in Paniai regency, an indirect district of Deiyai regency for malaria program in Central Papua Province.&nbsp;</strong>&nbsp;</span></p><p style=\"text-align: justify;\"><span style=\"font-family: Candara, Candara_EmbeddedFont, Candara_MSFontService, sans-serif;\">&nbsp;</span></p><p style=\"text-align: justify;\"><span style=\"font-family: Candara, Candara_EmbeddedFont, Candara_MSFontService, sans-serif;\">&nbsp;</span></p><p style=\"text-align: justify;\"><img src=\"https://cms-ysbh.test/storage/articles/QXVvLIYJ2Vsq0hjBEz47ULMurn4fdB39QpJDXeBj.webp\" alt=\"pasted-inline-1.webp\" title=\"pasted-inline-1.webp\" class=\"rounded-lg max-w-full my-2 transition-all cursor-pointer tiptap-uploaded-image inline-block\" style=\"width: 25%; display: block !important; margin: 0.75rem auto !important; float: none !important;\"><span style=\"font-family: Candara, Candara_EmbeddedFont, Candara_MSFontService, sans-serif;\">Yayasan Sinar Bhakti Husada (YSBH) berkomitmen untuk membantu pemerintah daerah maupun pemangku kepentingan lainnya, dalam upaya-upaya percepatan eliminasi malaria melalui pengembangan strategi dan pendampingan teknis untuk pelaksanaan program Malaria. Kerja sama YSBH untuk program Malaria dengan pemerintah daerah diikat dalam mekanisme swakelola tipe-3. Sedangkan kerja sama dengan perguruan tinggi lokal telah dimulai untuk pengembangan riset implementasi terkait efektivitas peranan kader dalam layanan Malaria dengan memasukkan budaya setempat sebagai faktor yang harus diperhitungkan.&nbsp;</span></p><p style=\"text-align: justify;\"><span style=\"font-family: Candara, Candara_EmbeddedFont, Candara_MSFontService, sans-serif;\"><strong>Picture : The signing of cooperation agreement on self-managed fund between YSBH and Sarmi District Health Office, in Sarmi.&nbsp;</strong>&nbsp;</span></p><p style=\"text-align: justify;\"><span style=\"font-family: Candara, Candara_EmbeddedFont, Candara_MSFontService, sans-serif;\">&nbsp;</span></p><p style=\"text-align: justify;\"><span style=\"font-family: Candara, Candara_EmbeddedFont, Candara_MSFontService, sans-serif;\">Program Malaria YSBH mengembangkan lima komponen penting yang menjadi landasan pelaksanaan program. Kelima komponen tersebut meliputi penguatan tata kelola program, penguatan surveilans dan respon malaria, peningkatan kapasitas tenaga kesehatan, penguatan mutu layanan malaria, serta pengembangan Puskesmas Model dan pusat pembelajaran <em>(On the Job Training Center</em>). Dukungan program ini,&nbsp; difokuskan pada wilayah dengan beban malaria yang masih tinggi seperti Kabupaten Mimika dan Nabire dan wilayah dengan kategori sedang seperti kabupaten Pania, Deiyai, Dogiyai dan Puncak Jaya di Provinsi Papua Tengah, maupun kabupaten Yahukimo di Provinsi Papua Pegunungan dan kabupaten Sarmi di Provinsi Papua.&nbsp;</span></p><p style=\"text-align: justify;\"><span style=\"font-family: Candara, Candara_EmbeddedFont, Candara_MSFontService, sans-serif;\">&nbsp;</span></p><p style=\"text-align: justify;\"><span style=\"font-family: Candara, Candara_EmbeddedFont, Candara_MSFontService, sans-serif;\">&nbsp;</span></p><p style=\"text-align: justify;\"><span style=\"font-family: Candara, Candara_EmbeddedFont, Candara_MSFontService, sans-serif;\"><strong>Welcome to the Malaria Program Page:&nbsp;</strong>&nbsp;</span></p><p style=\"text-align: justify;\"><span style=\"font-family: Candara, Candara_EmbeddedFont, Candara_MSFontService, sans-serif;\"><strong>Together Towards a Malaria-Free of Papua Land</strong>&nbsp;</span></p><p style=\"text-align: justify;\"><span style=\"font-family: Candara, Candara_EmbeddedFont, Candara_MSFontService, sans-serif;\">&nbsp;</span></p><p style=\"text-align: justify;\"><span style=\"font-family: Candara, Candara_EmbeddedFont, Candara_MSFontService, sans-serif;\">The Papua region – comprising six autonomous provinces and 42 regencies – accounts for 90% of malaria cases in Indonesia; notably, the 2025 E-SISMAL report recorded 204,068 cases in Central Papua. Mimika regency is the largest contributor within the province, with a total of 190,597 cases, followed by Nabire regency with 8,744 cases and several others. Meanwhile, case numbers vary across other provinces and regencies ranging from coastal areasa like Sarmi regency (Papua Province) to mountainous regions such as Yahukimo regency (Highland Papua Province). This high disease burden indicates that malaria remains a public health challenge requiring sustained attention and control efforts.&nbsp;</span></p><p style=\"text-align: justify;\"><span style=\"font-family: Candara, Candara_EmbeddedFont, Candara_MSFontService, sans-serif;\">Beyond the high number of cases, malaria disproportionately affects vulnerable groups such as pregnant women, infants and children under five. Malaria infections in these groups can increase the risk of health issues that impact quality of life as well as child growth and development. This situation demonstrates that malaria elimination is not merely about reducing case numbers, but also protecting the future generation of Papua as a whole.&nbsp;</span></p><p style=\"text-align: justify;\"><span style=\"font-family: Candara, Candara_EmbeddedFont, Candara_MSFontService, sans-serif;\">Eliminating malaria in the Papua Land – including Central Papua and Highland Papua – requires a comprehensive and sustainable approach; therefore, efforts to accelerate elimination focus not only on case management but also on strengthening health systems.&nbsp;</span></p><p style=\"text-align: justify;\"><span style=\"font-family: Candara, Candara_EmbeddedFont, Candara_MSFontService, sans-serif;\">The Sinar Bhakti Husada (YSBH) is committed to assisting local government and other stakeholders in accelerating malaria elimination efforts by developing strategies and providing technical support for malaria program implementation. YSBH’s cooperation with local governments on malaria programs is formalized through a type-3 self management mechanism. Meanwhile, partnership with local university (Universitas Cenderawasih) has been initiated to conduct implementation research on the effectiveness of community health workers in providing malaria services, specifically incorporating local culture as a key factor to be considered.&nbsp;</span></p><p style=\"text-align: justify;\"><span style=\"font-family: Candara, Candara_EmbeddedFont, Candara_MSFontService, sans-serif;\">The Malaria Program of Yayasan Sinar Bhakti Husada (YSBH) has developed five key components that serve as the foundations of program implementation. These components include strengthening program governance, enhancing malaria surveillance and response, building the capacity of healthcare workers, improving the quality of malaria services, and developing Model Primary Health Centers (Puskesmas) and On-the-Job Training (OJT) Centers. Program support is focused on high-burden areas such as Mimika and Nabire regencies, as well as moderate-burden areas including Paniai, Deiyai, Dogiyai, and Puncak Jaya regencies in Central Papua Province, as well as in Yahukimo regency in Highland Papua Province and Sarmi regency in Papua Province.&nbsp;</span></p><p style=\"text-align: justify;\"><span style=\"font-family: Candara, Candara_EmbeddedFont, Candara_MSFontService, sans-serif;\">&nbsp;</span></p><p style=\"text-align: justify;\"><img src=\"https://cms-ysbh.test/storage/articles/Bk75o04Pow2mi8h00kIB1yU4LdQ5AyObFAFTs83h.webp\" alt=\"pasted-inline-2.webp\" title=\"pasted-inline-2.webp\" class=\"rounded-lg max-w-full my-2 transition-all cursor-pointer tiptap-uploaded-image inline-block\" style=\"width: 100%; display: block !important; margin: 0.75rem auto !important; float: none !important;\"><span style=\"font-family: Candara, Candara_EmbeddedFont, Candara_MSFontService, sans-serif;\">&nbsp;</span></p><p style=\"text-align: justify;\"><span style=\"font-family: Candara, Candara_EmbeddedFont, Candara_MSFontService, sans-serif;\"><strong>Picture : On the Job Training (OJT) session on AIDS-TB-Malaria has been introduced in Paniai regency, on of supported regency in Central Papua Province.&nbsp;</strong>&nbsp;</span></p><p style=\"text-align: justify;\"><img src=\"https://cms-ysbh.test/storage/articles/Hp751SWK5R2PF3hrhhsYNRYRhNd2Up8W98PJIez7.webp\" alt=\"pasted-inline-3.webp\" title=\"pasted-inline-3.webp\" class=\"rounded-lg max-w-full my-2 transition-all cursor-pointer tiptap-uploaded-image inline-block\" style=\"width: 25%; display: block !important; margin: 0.75rem auto !important; float: none !important;\"><span style=\"font-family: Candara, Candara_EmbeddedFont, Candara_MSFontService, sans-serif;\"><strong>5 komponen penting Intervensi Program Malaria oleh Yayasan Sinar Bhakti Husada (YSBH)</strong>&nbsp;</span></p><p style=\"text-align: justify;\"><span style=\"font-family: Candara, Candara_EmbeddedFont, Candara_MSFontService, sans-serif;\"><strong>Five Key Components of YSBH’s Malaria Program Interventions</strong>&nbsp;</span></p><p style=\"text-align: justify;\"><span style=\"font-family: Candara, Candara_EmbeddedFont, Candara_MSFontService, sans-serif;\"><strong>Penguatan Tata Kelola Program&nbsp;</strong>&nbsp;</span></p><table style=\"min-width: 50px;\"><colgroup><col style=\"min-width: 25px;\"><col style=\"min-width: 25px;\"></colgroup><tbody><tr><td colspan=\"1\" rowspan=\"1\"><p style=\"text-align: justify;\"><span style=\"font-family: Candara, Candara_EmbeddedFont, Candara_MSFontService, sans-serif;\">Di beberapa wilayah dampingan YSBH di Tanah Papua, termasuk di Papua Tengah, tantangan geografis, keterbatasan akses layanan, dan dinamika kondisi di lapangan menuntut koordinasi yang kuat antara pemerintah daerah, sektor kesehatan, dan pemangku kepentingan lainnya. Oleh karena itu, penguatan tata kelola program menjadi fondasi penting untuk memastikan setiap upaya eliminasi malaria berjalan secara terarah, terkoordinasi, dan berkelanjutan.&nbsp;</span></p><p style=\"text-align: center;\"><span style=\"font-family: Candara, Candara_EmbeddedFont, Candara_MSFontService, sans-serif;\">&nbsp;</span></p><p style=\"text-align: center;\"><span style=\"font-family: Candara, Candara_EmbeddedFont, Candara_MSFontService, sans-serif;\">&nbsp;</span></p><p style=\"text-align: center;\"><img src=\"https://cms-ysbh.test/storage/articles/htrtQLAe8dJxQaA2stGLq5snmaOT3KzJcwmU7Sti.webp\" alt=\"pasted-inline-4.webp\" title=\"pasted-inline-4.webp\" class=\"rounded-lg max-w-full my-2 transition-all cursor-pointer tiptap-uploaded-image inline-block\" style=\"width: 25%; display: block !important; margin: 0.75rem auto !important; float: none !important;\"><span style=\"font-family: Candara, Candara_EmbeddedFont, Candara_MSFontService, sans-serif;\">&nbsp;</span></p><p style=\"text-align: center;\"><span style=\"font-family: Candara, Candara_EmbeddedFont, Candara_MSFontService, sans-serif;\"><strong>Picture : Microplanning Assistance for Accelerating Malaria Elimination in Nabire Regency</strong>&nbsp;</span></p></td><td colspan=\"1\" rowspan=\"1\"><p style=\"text-align: justify;\"><span style=\"font-family: Candara, Candara_EmbeddedFont, Candara_MSFontService, sans-serif;\">Melalui pendekatan penguatan tata kelola program, YSBH&nbsp; mendukung pemerintah daerah dan mitra terkait dalam memperkuat mekanisme koordinasi, mempersiapkan proses penilaian eliminasi malaria, serta mendorong penerapan strategi eliminasi yang terstandar. Dukungan tersebut diwujudkan melalui pembentukan dan penguatan Tim Assessment Eliminasi Malaria tingkat Provinsi, serta fasilitasi workshop percepatan eliminasi malaria untuk meningkatkan kesiapan daerah dalam mencapai target eliminasi secara terukur dan berkelanjutan.&nbsp;</span></p><p style=\"text-align: justify;\"><span style=\"font-family: Candara, Candara_EmbeddedFont, Candara_MSFontService, sans-serif;\">&nbsp;</span></p></td></tr></tbody></table><p style=\"text-align: justify;\"><span style=\"font-family: Candara, Candara_EmbeddedFont, Candara_MSFontService, sans-serif;\">&nbsp;</span></p><p style=\"text-align: justify;\"><span style=\"font-family: Candara, Candara_EmbeddedFont, Candara_MSFontService, sans-serif;\"><strong>Strengthening Program Governance</strong>&nbsp;</span></p><p style=\"text-align: justify;\"><span style=\"font-family: Candara, Candara_EmbeddedFont, Candara_MSFontService, sans-serif;\"><strong>In several YSBH’s supported regencies in Papua Land, including Central Papua, geographical challenges, limited access to healthcare services, and dynamic field conditions require strong coordination among local governments, the health sector, and other stakeholders. Therefore, strengthening program governance is a critical foundation for ensuring that malaria elimination efforts are well-directed, coordinated, and sustainable.</strong>&nbsp;</span></p><p style=\"text-align: justify;\"><span style=\"font-family: Candara, Candara_EmbeddedFont, Candara_MSFontService, sans-serif;\"><strong>Through its program governance strengthening approach, YSBH supports local governments and partners in enhancing coordination mechanisms, preparing for malaria elimination assessment processes, and promoting the implementation of standardized elimination strategies. This support is realized through the establishment and strengthening of the Provincial level Malaria Elimination Assessment Team, as well as the facilitation of malaria elimination acceleration workshops to improve regional readiness in achieving elimination targets in a measurable and sustainable manner.</strong>&nbsp;</span></p><p style=\"text-align: justify;\"><span style=\"font-family: Candara, Candara_EmbeddedFont, Candara_MSFontService, sans-serif;\">&nbsp;</span></p><p style=\"text-align: justify;\"><span style=\"font-family: Candara, Candara_EmbeddedFont, Candara_MSFontService, sans-serif;\">&nbsp;</span></p><p style=\"text-align: justify;\"><span style=\"font-family: Candara, Candara_EmbeddedFont, Candara_MSFontService, sans-serif;\">&nbsp;</span></p><p style=\"text-align: justify;\"><span style=\"font-family: Candara, Candara_EmbeddedFont, Candara_MSFontService, sans-serif;\">&nbsp;</span></p><p style=\"text-align: justify;\"><span style=\"font-family: Candara, Candara_EmbeddedFont, Candara_MSFontService, sans-serif;\"><strong>Penguatan Surveilans dan Respon&nbsp; Kasus Malaria</strong>&nbsp;</span></p><table style=\"min-width: 50px;\"><colgroup><col style=\"min-width: 25px;\"><col style=\"min-width: 25px;\"></colgroup><tbody><tr><td colspan=\"1\" rowspan=\"1\"><p style=\"text-align: justify;\"><span style=\"font-family: Candara, Candara_EmbeddedFont, Candara_MSFontService, sans-serif;\">Luasnya wilayah layanan, mobilitas penduduk, serta keterbatasan akses di beberapa daerah menjadikan deteksi dan respons terhadap kasus malaria sebagai tantangan tersendiri di Papua Tengah. Dalam konteks eliminasiz, setiap kasus perlu segera ditemukan, ditindaklanjuti, dan dipantau untuk mencegah terjadinya penularan lanjutan.&nbsp;</span></p><p style=\"text-align: justify;\"><span style=\"font-family: Candara, Candara_EmbeddedFont, Candara_MSFontService, sans-serif;\">&nbsp;</span></p><p style=\"text-align: justify;\"><span style=\"font-family: Candara, Candara_EmbeddedFont, Candara_MSFontService, sans-serif;\">Melalui pendekatan penguatan surveilans dan respons malaria, YSBH mendukung pemerintah daerah dan fasilitas kesehatan untuk memperkuat deteksi kasus, penyelidikan epidemiologi, contact survey, serta pelaksanaan strategi 1-2-5 sebagai bagian dari respon cepat terhadap kasus malaria. Program juga mendorong pemanfaatan data sebagai dasar pengambilan keputusan melalui monitoring dan evaluasi berkala, sekaligus&nbsp;&nbsp;</span></p></td><td colspan=\"1\" rowspan=\"1\"><p style=\"text-align: justify;\"><span style=\"font-family: Candara, Candara_EmbeddedFont, Candara_MSFontService, sans-serif;\">mengembangkan inovasi digital berupa aplikasi pengingat minum obat untuk membantu meningkatkan kepatuhan pengobatan pasien malaria. Dengan pendekatan ini, setiap kasus tidak hanya tercatat dalam sistem, tetapi juga ditindaklanjuti secara menyeluruh untuk mendukung terputusnya rantai penularan malaria di daerah dampingan.&nbsp;&nbsp;</span></p><p style=\"text-align: center;\"><span style=\"font-family: Candara, Candara_EmbeddedFont, Candara_MSFontService, sans-serif;\">&nbsp;</span></p><p style=\"text-align: center;\"><img src=\"https://cms-ysbh.test/storage/articles/JJW5vtFsV9Azu0KGThJukgYbcgZTACJoZqfkX7yK.webp\" alt=\"pasted-inline-6.webp\" title=\"pasted-inline-6.webp\" class=\"rounded-lg max-w-full my-2 transition-all cursor-pointer tiptap-uploaded-image inline-block\" style=\"width: 25%; display: block !important; margin: 0.75rem auto !important; float: none !important;\"><span style=\"font-family: Candara, Candara_EmbeddedFont, Candara_MSFontService, sans-serif;\">&nbsp;</span></p><p style=\"text-align: center;\"><span style=\"font-family: Candara, Candara_EmbeddedFont, Candara_MSFontService, sans-serif;\">Picture : Strengthening malaria surveillance through routine data validation in Mimika Regency.&nbsp;</span></p></td></tr></tbody></table><p style=\"text-align: justify;\"><img src=\"https://cms-ysbh.test/storage/articles/4s89zag0wBtQ8pNqzRENF6Ir0feMgVzIhW0TXu6p.webp\" alt=\"pasted-inline-7.webp\" title=\"pasted-inline-7.webp\" class=\"rounded-lg max-w-full my-2 transition-all cursor-pointer tiptap-uploaded-image inline-block\" style=\"width: 25%; display: block !important; margin: 0.75rem auto !important; float: none !important;\"><span style=\"font-family: Candara, Candara_EmbeddedFont, Candara_MSFontService, sans-serif;\">&nbsp;</span></p><p style=\"text-align: justify;\"><span style=\"font-family: Candara, Candara_EmbeddedFont, Candara_MSFontService, sans-serif;\"><strong>Strengthening Malaria Surveillance and Case Response</strong>&nbsp;</span></p><p style=\"text-align: justify;\"><span style=\"font-family: Candara, Candara_EmbeddedFont, Candara_MSFontService, sans-serif;\">The vast service areas, population mobility, and limited access in certain locations present unique challenges for malaria case detection and response in Central Papua. Within the context of malaria elimination, every case must be promptly detected, investigated, and monitored to prevent further transmission.&nbsp;</span></p><p style=\"text-align: justify;\"><span style=\"font-family: Candara, Candara_EmbeddedFont, Candara_MSFontService, sans-serif;\">Through its malaria surveillance and response strengthening approach, YSBH supports local governments and health facilities in enhancing case detection, epidemiological investigations, contact surveys, and the implementation of the 1-2-5 strategy as part of a rapid response to malaria cases.&nbsp;</span></p><p style=\"text-align: justify;\"><span style=\"font-family: Candara, Candara_EmbeddedFont, Candara_MSFontService, sans-serif;\">The program also promotes data-driven decision-making through regular monitoring and evaluation activities while developing digital innovations, including a medication reminder application designed to improve treatment adherence among malaria patients. Through this approach, every malaria case is not only recorded in the system but also comprehensively followed up to support the interruption of malaria transmission in supported provinces and regencies.&nbsp;</span></p><p style=\"text-align: justify;\"><span style=\"font-family: Candara, Candara_EmbeddedFont, Candara_MSFontService, sans-serif;\"><strong>Penguatan Kapasitas Tenaga Kesehatan</strong>&nbsp;</span></p><p style=\"text-align: justify;\"><span style=\"font-family: Candara, Candara_EmbeddedFont, Candara_MSFontService, sans-serif;\">Kualitas pelaksanaan program malaria sangat bergantung pada kapasitas tenaga kesehatan yang berada di garis depan pelayanan. Namun, kondisi geografis dan keterbatasan akses di beberapa wilayah sering kali menjadi tantangan dalam pemerataan peningkatan kapasitas tenaga kesehatan. Oleh karena itu, penguatan kompetensi secara berkelanjutan menjadi kebutuhan penting untuk memastikan standar eliminasi malaria dapat diterapkan secara konsisten di seluruh wilayah layanan.&nbsp;</span></p><p style=\"text-align: justify;\"><span style=\"font-family: Candara, Candara_EmbeddedFont, Candara_MSFontService, sans-serif;\">Untuk itu, YSBH mendukung penguatan kapasitas tenaga kesehatan melalui berbagai kegiatan pelatihan, workshop, dan pendampingan teknis yang disesuaikan dengan kebutuhan daerah. Selain meningkatkan kompetensi teknis terkait malaria, pendekatan ini juga mendorong terciptanya proses belajar yang berkelanjutan sehingga tenaga kesehatan dapat terus beradaptasi, menjaga kualitas layanan, dan berkontribusi secara optimal dalam mencapai target eliminasi malaria di provinsi dan kabupaten dampingan.&nbsp;&nbsp;</span></p><p style=\"text-align: justify;\"><img src=\"https://cms-ysbh.test/storage/articles/2kmxVK6dISYrVIK1clfTt0BeQzryN74TqDoBjgzN.webp\" alt=\"pasted-inline-8.webp\" title=\"pasted-inline-8.webp\" class=\"rounded-lg max-w-full my-2 transition-all cursor-pointer tiptap-uploaded-image inline-block\" style=\"width: 25%; display: block !important; margin: 0.75rem auto !important; float: none !important;\"><span style=\"font-family: Candara, Candara_EmbeddedFont, Candara_MSFontService, sans-serif;\">&nbsp;</span></p><p style=\"text-align: justify;\"><span style=\"font-family: Candara, Candara_EmbeddedFont, Candara_MSFontService, sans-serif;\"><strong>Strengthening the Capacity of Healthcare Workers</strong>&nbsp;</span></p><p style=\"text-align: justify;\"><span style=\"font-family: Candara, Candara_EmbeddedFont, Candara_MSFontService, sans-serif;\">The quality of malaria program implementation depends heavily on the capacity of frontline healthcare workers. However, geographical conditions and limited accessibility in certain areas often present challenges to the equitable development of healthcare worker competencies. Therefore, continuous capacity strengthening is essential to ensure that malaria elimination standards are consistently implemented across all service areas.&nbsp;</span></p><p style=\"text-align: justify;\"><span style=\"font-family: Candara, Candara_EmbeddedFont, Candara_MSFontService, sans-serif;\">To address this need, YSBH supports healthcare worker capacity strengthening through various training programs, workshops, and technical mentoring activities tailored to local needs. In addition to enhancing malaria-related technical competencies, this approach promotes a culture of continuous learning, enabling healthcare workers to adapt, maintain service quality, and contribute effectively to achieving malaria elimination targets in supported provinces and regencies.&nbsp;</span></p><p style=\"text-align: justify;\"><span style=\"font-family: Candara, Candara_EmbeddedFont, Candara_MSFontService, sans-serif;\">&nbsp;</span></p><p style=\"text-align: justify;\"><span style=\"font-family: Candara, Candara_EmbeddedFont, Candara_MSFontService, sans-serif;\">&nbsp;</span></p><p style=\"text-align: justify;\"><span style=\"font-family: Candara, Candara_EmbeddedFont, Candara_MSFontService, sans-serif;\">&nbsp;</span></p><p style=\"text-align: justify;\"><span style=\"font-family: Candara, Candara_EmbeddedFont, Candara_MSFontService, sans-serif;\">&nbsp;</span></p><p style=\"text-align: justify;\"><span style=\"font-family: Candara, Candara_EmbeddedFont, Candara_MSFontService, sans-serif;\">&nbsp;</span></p><p style=\"text-align: justify;\"><span style=\"font-family: Candara, Candara_EmbeddedFont, Candara_MSFontService, sans-serif;\">&nbsp;</span></p><p style=\"text-align: justify;\"><span style=\"font-family: Candara, Candara_EmbeddedFont, Candara_MSFontService, sans-serif;\">&nbsp;</span></p><p style=\"text-align: justify;\"><span style=\"font-family: Candara, Candara_EmbeddedFont, Candara_MSFontService, sans-serif;\">&nbsp;</span></p><p style=\"text-align: justify;\"><span style=\"font-family: Candara, Candara_EmbeddedFont, Candara_MSFontService, sans-serif;\"><strong>Penguatan Mutu Layanan Malaria</strong>&nbsp;</span></p><table style=\"min-width: 50px;\"><colgroup><col style=\"min-width: 25px;\"><col style=\"min-width: 25px;\"></colgroup><tbody><tr><td colspan=\"1\" rowspan=\"1\"><p style=\"text-align: left;\"><span style=\"font-family: Aptos, Aptos_EmbeddedFont, Aptos_MSFontService, sans-serif;\">&nbsp;</span></p><p style=\"text-align: center;\"><img src=\"https://cms-ysbh.test/storage/articles/NNfT2o7BPADgh9tohO7EgRiMYy9V5oTBmaDYWyYl.webp\" alt=\"pasted-inline-9.webp\" title=\"pasted-inline-9.webp\" class=\"rounded-lg max-w-full my-2 transition-all cursor-pointer tiptap-uploaded-image inline-block\" style=\"width: 100%; float: left; margin-right: 1rem; margin-top: 0.25rem; margin-bottom: 0.5rem; display: inline !important;\"><span style=\"font-family: Candara, Candara_EmbeddedFont, Candara_MSFontService, sans-serif;\">&nbsp;</span></p><p style=\"text-align: center;\"><span style=\"font-family: Candara, Candara_EmbeddedFont, Candara_MSFontService, sans-serif;\"><strong>Picture : Monitoring microscopy capacity at primary health centers to support quality malaria services.</strong>&nbsp;</span></p><p style=\"text-align: justify;\"><span style=\"font-family: Candara, Candara_EmbeddedFont, Candara_MSFontService, sans-serif;\">&nbsp;</span></p><p style=\"text-align: justify;\"><span style=\"font-family: Candara, Candara_EmbeddedFont, Candara_MSFontService, sans-serif;\">&nbsp;</span></p><p style=\"text-align: justify;\"><span style=\"font-family: Candara, Candara_EmbeddedFont, Candara_MSFontService, sans-serif;\">&nbsp;</span></p></td><td colspan=\"1\" rowspan=\"1\"><p style=\"text-align: justify;\"><span style=\"font-family: Candara, Candara_EmbeddedFont, Candara_MSFontService, sans-serif;\">Masyarakat berhak memperoleh layanan malaria yang berkualitas, terlepas dari lokasi tempat mereka mengakses pelayanan kesehatan. Namun, perbedaan kapasitas fasilitas kesehatan dan tantangan operasional di lapangan dapat memengaruhi konsistensi penerapan standar pelayanan. Karena itu, upaya peningkatan mutu layanan menjadi penting untuk memastikan setiap pasien mendapatkan diagnosis, pengobatan, dan tindak lanjut yang sesuai standar.&nbsp;</span></p><p style=\"text-align: justify;\"><span style=\"font-family: Candara, Candara_EmbeddedFont, Candara_MSFontService, sans-serif;\">Sebagai bagian dari upaya tersebut, YSBH memfasilitasi pelaksanaan <em>quality assurance</em>, monitoring dan evaluasi secara berkala untuk memperoleh gambaran mengenai kualitas layanan yang telah berjalan sekaligus mengidentifikasi area yang masih perlu diperkuat. Temuan-temuan yang dihasilkan kemudian menjadi dasar untuk perbaikan layanan, penguatan koordinasi, dan peningkatan kualitas pelaksanaan program. Dengan demikian, fasilitas kesehatan memiliki kesempatan untuk terus meningkatkan mutu layanan malaria secara berkelanjutan dalam mendukung pencapaian eliminasi malaria di Tanah Papua.&nbsp;</span></p></td></tr></tbody></table><p style=\"text-align: justify;\"><span style=\"font-family: Candara, Candara_EmbeddedFont, Candara_MSFontService, sans-serif;\"><strong>Strengthening the Quality of Malaria Services</strong>&nbsp;</span></p><p style=\"text-align: justify;\"><span style=\"font-family: Candara, Candara_EmbeddedFont, Candara_MSFontService, sans-serif;\">Communities have the right to access quality malaria services regardless of where they seek healthcare. However, variations in health facility capacity and operational challenges in the field can affect the consistent implementation of service standards. Therefore, efforts to improve service quality are essential to ensure that every patient receives appropriate diagnosis, treatment, and follow-up care in accordance with established standards.&nbsp;</span></p><p style=\"text-align: justify;\"><span style=\"font-family: Candara, Candara_EmbeddedFont, Candara_MSFontService, sans-serif;\">As part of these efforts, YSBH facilitates quality assurance activities as well as regular monitoring and evaluation to assess service quality and identify areas requiring further improvement. The findings generated serve as a basis for service improvement, strengthened coordination, and enhanced program implementation quality.&nbsp;</span></p><p style=\"text-align: justify;\"><span style=\"font-family: Candara, Candara_EmbeddedFont, Candara_MSFontService, sans-serif;\">Through this approach, health facilities are provided with opportunities to continuously improve the quality of malaria services in support of malaria elimination efforts in Papua Land.&nbsp;</span></p><p style=\"text-align: justify;\"><span style=\"font-family: Candara, Candara_EmbeddedFont, Candara_MSFontService, sans-serif;\">&nbsp;</span></p><p style=\"text-align: justify;\"><span style=\"font-family: Candara, Candara_EmbeddedFont, Candara_MSFontService, sans-serif;\">&nbsp;</span></p><p style=\"text-align: justify;\"><span style=\"font-family: Candara, Candara_EmbeddedFont, Candara_MSFontService, sans-serif;\"><strong>Pengembangan Puskesmas Model dan <em>On the Job Training</em> (OJT) – <em>Center</em></strong>&nbsp;</span></p><table style=\"min-width: 50px;\"><colgroup><col style=\"min-width: 25px;\"><col style=\"min-width: 25px;\"></colgroup><tbody><tr><td colspan=\"1\" rowspan=\"1\"><p style=\"text-align: justify;\"><span style=\"font-family: Candara, Candara_EmbeddedFont, Candara_MSFontService, sans-serif;\">Pencapaian eliminasi malaria membutuhkan fasilitas kesehatan yang tidak hanya mampu memberikan layanan sesuai standar, tetapi juga dapat menjadi contoh penerapan praktik baik bagi fasilitas kesehatan lainnya. Di Papua Tengah, upaya tersebut menghadapi berbagai tantangan, mulai dari kondisi geografis yang sulit dijangkau, keterbatasan akses layanan dasar, hingga situasi keamanan di beberapa wilayah yang dapat memengaruhi kontinuitas pelayanan kesehatan. Dalam konteks tersebut, ketersediaan pusat pembelajaran yang dekat dengan realitas lapangan menjadi semakin penting untuk mendukung peningkatan kapasitas tenaga kesehatan secara berkelanjutan.&nbsp;</span></p><p style=\"text-align: justify;\"><span style=\"font-family: Candara, Candara_EmbeddedFont, Candara_MSFontService, sans-serif;\">&nbsp;</span></p></td><td colspan=\"1\" rowspan=\"1\"><p style=\"text-align: center;\"><img src=\"https://cms-ysbh.test/storage/articles/uzKk7ZPICE3GW4Y0EJjTOEzzUh6YgpP68Vb4ZlcA.webp\" alt=\"pasted-inline-11.webp\" title=\"pasted-inline-11.webp\" class=\"rounded-lg max-w-full my-2 transition-all cursor-pointer tiptap-uploaded-image inline-block\" style=\"width: 100%; float: right; margin-left: 1rem; margin-top: 0.25rem; margin-bottom: 0.5rem; display: inline !important;\"><span style=\"font-family: Candara, Candara_EmbeddedFont, Candara_MSFontService, sans-serif;\">&nbsp;</span></p><p style=\"text-align: center;\"><span style=\"font-family: Candara, Candara_EmbeddedFont, Candara_MSFontService, sans-serif;\"><strong>Picture : Assessment of Model Primary Health Centers as learning centers for malaria service best practices.</strong>&nbsp;</span></p></td></tr><tr><td colspan=\"1\" rowspan=\"1\"><p style=\"text-align: justify;\"><span style=\"font-family: Candara, Candara_EmbeddedFont, Candara_MSFontService, sans-serif;\">Melalui pengembangan Puskesmas Model dan <em>On-the-Job Training</em> (OJT) <em>Center</em>, YSBH berupaya menciptakan ruang pembelajaran yang memungkinkan tenaga kesehatan belajar langsung dari pengalaman praktik pelayanan malaria di fasilitas kesehatan. Selain menjadi sarana peningkatan kapasitas, Puskesmas Model diharapkan dapat menjadi rujukan praktik baik dalam penyelenggaraan layanan malaria, pengelolaan program, dan penerapan standar pelayanan. Dengan demikian, pengalaman dan pembelajaran yang telah terbukti efektif dapat direplikasi oleh fasilitas kesehatan lain untuk memperkuat upaya eliminasi malaria di daerah dampingan.&nbsp;</span></p><p style=\"text-align: justify;\"><span style=\"font-family: Candara, Candara_EmbeddedFont, Candara_MSFontService, sans-serif;\">&nbsp;</span></p></td><td colspan=\"1\" rowspan=\"1\"><p style=\"text-align: center;\"><span style=\"font-family: Aptos, Aptos_EmbeddedFont, Aptos_MSFontService, sans-serif;\">&nbsp;</span></p><p style=\"text-align: center;\"><img src=\"https://cms-ysbh.test/storage/articles/7SjKFbB8XqqU3xjIrUO2OyzaSIb7Mlovp3uDnIK5.webp\" alt=\"pasted-inline-12.webp\" title=\"pasted-inline-12.webp\" class=\"rounded-lg max-w-full my-2 transition-all cursor-pointer tiptap-uploaded-image inline-block\" style=\"width: 100%; display: block !important; margin: 0.75rem auto !important; float: none !important;\"><span style=\"font-family: Aptos, Aptos_EmbeddedFont, Aptos_MSFontService, sans-serif;\">&nbsp;</span></p><p style=\"text-align: center;\"><span style=\"font-family: Candara, Candara_EmbeddedFont, Candara_MSFontService, sans-serif;\"><strong>Picture : Assessment of Model Primary Health Centers as learning centers for malaria service best practices.</strong>&nbsp;</span></p><p style=\"text-align: center;\"><span style=\"font-family: Candara, Candara_EmbeddedFont, Candara_MSFontService, sans-serif;\">&nbsp;</span></p></td></tr></tbody></table><p style=\"text-align: justify;\"><span style=\"font-family: Candara, Candara_EmbeddedFont, Candara_MSFontService, sans-serif;\">&nbsp;</span></p><p style=\"text-align: justify;\"><span style=\"font-family: Candara, Candara_EmbeddedFont, Candara_MSFontService, sans-serif;\"><strong>Development of Model Primary Health Centers (Puskesmas) and On-the-Job Training (OJT) Centers</strong>&nbsp;</span></p><p style=\"text-align: justify;\"><span style=\"font-family: Candara, Candara_EmbeddedFont, Candara_MSFontService, sans-serif;\">Achieving malaria elimination requires health facilities that not only provide services according to established standards but also serve as models of best practices for other health facilities. In Central Papua, these efforts face various challenges, including difficult geographical conditions, limited access to basic healthcare services, and security situations in certain areas that may affect the continuity of health service delivery.&nbsp;</span></p><p style=\"text-align: justify;\"><span style=\"font-family: Candara, Candara_EmbeddedFont, Candara_MSFontService, sans-serif;\">In this context, the availability of learning centers that reflect real field conditions becomes increasingly important to support the continuous development of healthcare worker capacity.&nbsp;</span></p><p style=\"text-align: justify;\"><span style=\"font-family: Candara, Candara_EmbeddedFont, Candara_MSFontService, sans-serif;\">Through the development of Model Primary Health Centers (Puskesmas) and On-the-Job Training (OJT) Centers, YSBH seeks to create learning environments where healthcare workers can learn directly from practical experiences in malaria service delivery. In addition to serving as capacity-building platforms, Model Puskesmas are expected to become references for best practices in malaria service delivery, program management, and the implementation of service standards. Through this approach, proven experiences and effective lessons learned can be replicated by other health facilities to strengthen malaria elimination efforts across supported provinces and regencies.&nbsp;</span></p><p style=\"text-align: justify;\"><img src=\"https://cms-ysbh.test/storage/articles/4WiLUvyi0PujniDXrgqu5A8IVp3S0Uuua2ErZGhF.webp\" alt=\"pasted-inline-14.webp\" title=\"pasted-inline-14.webp\" class=\"rounded-lg max-w-full my-2 transition-all cursor-pointer tiptap-uploaded-image inline-block\" style=\"width: 25%; display: block !important; margin: 0.75rem auto !important; float: none !important;\"><span style=\"font-family: Candara, Candara_EmbeddedFont, Candara_MSFontService, sans-serif;\">&nbsp;</span></p><p style=\"text-align: justify;\"><span style=\"font-family: Candara, Candara_EmbeddedFont, Candara_MSFontService, sans-serif;\">&nbsp;</span></p><p style=\"text-align: justify;\"><span style=\"font-family: Candara, Candara_EmbeddedFont, Candara_MSFontService, sans-serif;\"><strong>Warisan yang perlu diakhiri</strong>&nbsp;</span></p><p style=\"text-align: justify;\"><span style=\"font-family: Candara, Candara_EmbeddedFont, Candara_MSFontService, sans-serif;\">Bagi sebagian masyarakat, malaria telah menjadi penyakit yang diwariskan dari satu generasi ke generasi berikutnya. Kehadirannya begitu lama sehingga sering dianggap sebagai bagian dari kehidupan sehari-hari. Padahal, tidak ada anak yang seharusnya tumbuh dengan risiko sakit berulang akibat malaria, dan tidak ada keluarga yang seharusnya menerima penyakit yang dapat dicegah sebagai sesuatu yang normal.&nbsp;</span></p><p style=\"text-align: justify;\"><span style=\"font-family: Candara, Candara_EmbeddedFont, Candara_MSFontService, sans-serif;\">Mewujudkan wilayah bebas malaria bukan hanya tentang menghilangkan penyakit, tetapi juga tentang memutus warisan risiko yang selama ini membatasi kesempatan masyarakat Papua untuk hidup lebih sehat dan sejahtera.&nbsp;</span></p><p style=\"text-align: justify;\"><img src=\"https://cms-ysbh.test/storage/articles/ugeKeyrmDLONpKupvXHxLbja4r3AUBjIpJRxlnSI.webp\" alt=\"pasted-inline-15.webp\" title=\"pasted-inline-15.webp\" class=\"rounded-lg max-w-full my-2 transition-all cursor-pointer tiptap-uploaded-image inline-block\" style=\"width: 25%; display: block !important; margin: 0.75rem auto !important; float: none !important;\"><span style=\"font-family: Candara, Candara_EmbeddedFont, Candara_MSFontService, sans-serif;\">&nbsp;</span></p><p style=\"text-align: justify;\"><span style=\"font-family: Candara, Candara_EmbeddedFont, Candara_MSFontService, sans-serif;\"><strong>A Legacy That Must End</strong>&nbsp;</span></p><p style=\"text-align: justify;\"><span style=\"font-family: Candara, Candara_EmbeddedFont, Candara_MSFontService, sans-serif;\">For many communities, malaria has become a disease passed down from one generation to the next. Its presence has persisted for so long that it is often perceived as a normal part of daily life. Yet no child should grow up facing the risk of repeated illness caused by malaria, and no family should accept a preventable disease as something inevitable.&nbsp;</span></p><p style=\"text-align: justify;\"><span style=\"font-family: Candara, Candara_EmbeddedFont, Candara_MSFontService, sans-serif;\">Creating malaria-free communities is not only about eliminating a disease; it is about breaking a legacy of risk that has long limited opportunities for the people of Papua to live healthier and more prosperous lives.&nbsp;</span></p>','default.webp','archived',NULL,'2026-07-16 04:18:08','2026-07-16 04:18:08'),
(3,2,4,'template kia','template-kia','<p style=\"text-align: justify;\"><span style=\"font-family: Arial, Arial_EmbeddedFont, Arial_MSFontService, sans-serif;\">Program KIA dukungan Yayasan Sinar Bhakti Husada telah mendukung pemerintah daerah untuk memastikan bahwa ibu hamil, bayi baru lahir, anak-anak, dan remaja di seluruh wilayah dukungan termasuk daerah terpencil memiliki akses ke layanan kesehatan primer yang adil dan berkualitas tinggi, khususnya di kampung-kampung yang terpinggirkan dan kurang terlayani.&nbsp;</span></p><p style=\"text-align: justify;\"><span style=\"font-family: Arial, Arial_EmbeddedFont, Arial_MSFontService, sans-serif;\">Tantangan geografis yang unik di berbagai wilayah di daerah dukungan yang tersebar di beberapa Provinsi dan Kabupaten di Tanah Papua, termasuk Provinsi Papua, Papua Pegunungan dan Papua Tengah, serta Kabupaten Sarmi, Pegunungan Bintang, Yahukimo, Nabire, Mimika, Paniai,&nbsp;&nbsp;</span></p><p style=\"text-indent: 2rem; text-align: justify;\"><span style=\"font-family: Arial, Arial_EmbeddedFont, Arial_MSFontService, sans-serif;\">Deiyai, Dogiyai dan Puncak Jaya dijawab dengan memperkuat kapasitas tenaga medis lokal, kader posyandu, dan Puskesmas. Penguatan ini bertujuan untuk merencanakan, melaksanakan, memantau, dan memberikan layanan esensial bagi keselamatan dan kesehatan ibu dan bayi baru lahir. Selain itu, program ini berfokus pada pencegahan, pengendalian, dan pengeliminasian penyakit utama pada anak, terutama penyakit yang sering menyebabkan kematian pada anak seperti pneumonia dan diare, malnutrisi (stunting), penyakit yang dapat dicegah dengan imunisasi (PD3I), serta penyakit menular endemik seperti malaria. Tujuan-tujuan ini diwujudkan melalui peningkatan sistem kesehatan yang tangguh dan tetap responsif terhadap keadaan darurat serta situasi lokal, terutama di Puskesmas Model yang akan berfungsi juga sebagai Puskesmas On The Job Training (OJT) Center di masing-masing Kabupaten dampingan.&nbsp;</span></p><p style=\"text-align: justify;\"><img src=\"https://cms-ysbh.test/storage/articles/H6rZONnFD7NNQY0zF2qnN6aTdKmbbZVcNyNNs81g.webp\" alt=\"pasted-inline-0.webp\" title=\"pasted-inline-0.webp\" class=\"rounded-lg max-w-full my-2 transition-all cursor-pointer tiptap-uploaded-image inline-block\" style=\"width: 100%; display: block !important; margin: 0.75rem auto !important; float: none !important;\"><span style=\"font-family: Arial, Arial_EmbeddedFont, Arial_MSFontService, sans-serif;\">&nbsp;</span></p><p style=\"text-align: justify;\"><span style=\"font-family: Arial, Arial_EmbeddedFont, Arial_MSFontService, sans-serif;\">Program KIA di wilayah ini terdiri dari empat komponen utama: Perawatan Ibu Hamil dan Bayi Baru Lahir,&nbsp; Kesehatan dan Gizi Anak,&nbsp; Penguatan Sistem Kesehatan Berbasis Komunitas,&nbsp; Kesehatan dalam Keadaan Darurat. Program-program ini diimplementasikan di distrik-distrik terpilih di wilayah dukungan di Tanah Papua dengan menggunakan pendekatan efektif yang disesuaikan dengan konteks sosial-budaya masyarakat Papua, dengan harapan bahwa pemerintah daerah dan lintas sektor dapat mengadopsi serta memperluas skalanya demi menyelamatkan generasi masa depan Papua.&nbsp;</span></p><p style=\"text-align: justify;\"><span style=\"font-family: Arial, Arial_EmbeddedFont, Arial_MSFontService, sans-serif;\"><strong>Welcome to the Maternal and Child Health (MCH) Program Page</strong>&nbsp;</span></p><p style=\"text-align: justify;\"><span style=\"font-family: Arial, Arial_EmbeddedFont, Arial_MSFontService, sans-serif;\">The MCH Program, supported by the Sinar Bhakti Husada Foundation, has been assisting local governments to ensure that pregnant women, newborns, children, and adolescents across all supported areas—including remote regions—have equitable access to high-quality primary healthcare services, particularly in marginalized and underserved villages (<em>kampung</em>).&nbsp;</span></p><p style=\"text-align: justify;\"><span style=\"font-family: Arial, Arial_EmbeddedFont, Arial_MSFontService, sans-serif;\">The unique geographical challenges across the supported areas, which span several Provinces and Regencies in the Land of Papua—including Papua, Highlands Papua, and Central Papua Provinces, as well as the Regencies of Sarmi, Pegunungan Bintang, Yahukimo, Nabire, Mimika, Paniai, Deiyai, Dogiyai, and Puncak Jaya—are addressed by strengthening the capacity of local medical personnel, <em>posyandu</em> (integrated healthcare center) volunteers, and <em>Puskesmas</em> (public health centers). This capacity building aims to plan, implement, monitor, and deliver essential services for the safety and health of mothers and newborns. Furthermore, the program focuses on preventing, controlling, and eliminating major childhood illnesses, especially those that frequently cause infant and child mortality, such as pneumonia and diarrhea, malnutrition (stunting), vaccine-preventable diseases (VPDs), and endemic infectious diseases like malaria. These goals are achieved by building resilient health systems that remain responsive to emergencies and local situations, particularly through Model <em>Puskesmas</em> that will also function as <em>Puskesmas</em> On-the-Job Training (OJT) Centers in each assisted regency.&nbsp;</span></p><p style=\"text-align: justify;\"><span style=\"font-family: Arial, Arial_EmbeddedFont, Arial_MSFontService, sans-serif;\">The MCH Program in this region consists of four main components: Maternal and Newborn Care, Child Health and Nutrition, Strengthening Community-Based Health Systems, and Health in Emergencies. These programs are implemented in selected districts within the supported areas of the Land of Papua using effective approaches tailored to the socio-cultural context of the Papuan community, with the hope that local governments and cross-sector partners can adopt and scale up these initiatives to save the future generation of Papua.&nbsp;</span></p><p style=\"text-indent: 2rem; text-align: justify;\"><span style=\"font-family: Arial, Arial_EmbeddedFont, Arial_MSFontService, sans-serif;\">&nbsp;</span></p><p style=\"text-align: justify;\"><span style=\"font-family: Arial, Arial_EmbeddedFont, Arial_MSFontService, sans-serif;\">&nbsp;</span></p><p style=\"text-align: justify;\"><span style=\"font-family: Arial, Arial_EmbeddedFont, Arial_MSFontService, sans-serif;\"><strong>Perawatan Bayi Baru Lahir</strong>&nbsp;</span></p><p style=\"text-align: justify;\"><img src=\"https://cms-ysbh.test/storage/articles/kOa48W5V1is5c87wekCVqVFNygjf8IpkqjHAuNEn.webp\" alt=\"pasted-inline-1.webp\" title=\"pasted-inline-1.webp\" class=\"rounded-lg max-w-full my-2 transition-all cursor-pointer tiptap-uploaded-image inline-block\" style=\"width: 25%; float: right; margin-left: 1rem; margin-top: 0.25rem; margin-bottom: 0.5rem; display: inline !important;\"><span style=\"font-family: Arial, Arial_EmbeddedFont, Arial_MSFontService, sans-serif;\">Periode 28 hari pertama kehidupan (neonatal) merupakan fase paling kritis bagi kelangsungan hidup seorang anak. Bayi baru lahir menghadapi risiko tinggi mengalami kematian dan kecacatan, yang sebagian besar berakar dari kondisi kesehatan ibu yang kurang optimal selama kehamilan, kurangnya kunjungan antenatal (ANC) standar, serta penanganan persalinan yang tidak memadai. Data nasional menunjukkan bahwa mayoritas kematian balita justru terjadi pada masa neonatal (0–28 hari). Oleh karena itu, penurunan Angka Kematian Neonatal (AKN) tidak dapat dipisahkan dari upaya peningkatan kesehatan ibu dan intervensi medis yang terintegrasi sejak masa kehamilan hingga pasca persalinan.&nbsp;</span></p><p style=\"text-align: justify;\"><span style=\"font-family: Arial, Arial_EmbeddedFont, Arial_MSFontService, sans-serif;\">&nbsp;</span></p><p style=\"text-align: justify;\"><span style=\"font-family: Arial, Arial_EmbeddedFont, Arial_MSFontService, sans-serif;\">Penyelamatan bayi baru lahir membutuhkan pemenuhan standar pelayanan terpadu. Hal ini mencakup pencegahan komplikasi persalinan, tata laksana asfiksia, penanganan infeksi berat (seperti sepsis neonatal), serta intervensi khusus untuk bayi rentan. Berbagai bukti ilmiah terbaru menegaskan bahwa mayoritas kematian pada fase ini disebabkan oleh kondisi yang sebenarnya dapat dicegah dan diobati melalui intervensi sederhana dan berbiaya efisien di fasilitas kesehatan primer. Faktor resiko utama yakni : Berat Badan Lahir Rendah (BBLR) &amp; Prematuritas, Hipotermia, Pneumonia dan Infeksi Neonatal, Optimalisasi ASI Eksklusif dan Inisiasi Menyusu Dini (IMD), dan Strategi Intervensi Terbaru berupa pendekatan Point of Care Quality Improvement (POCQI) dan Quality Improvement (QI) kolaboratif telah diperkenalkan kepada pemerintah daerah melalui pendampingan teknis dari Yayasan Sinar Bhakti Husada. Selain itu, Yayasan Sinar Bhakti Husada juga membantu pemerintah daerah untuk mengembangkan Peraturan Gubernur/Bupati beserta Rencana Aksi Daerah (RAD) Penguatan Layanan Kesehatan Ibu dan Anak (KIA) dan Percepatan Penurunan Angka Kematian Ibu dan Anak, terutama bayi baru lahir.&nbsp;</span></p><p style=\"text-align: justify;\"><span style=\"font-family: WordVisiCarriageReturn_MSFontService, Calibri, Calibri_EmbeddedFont, Calibri_MSFontService, sans-serif;\">&nbsp;<br></span><span style=\"font-family: Arial, Arial_EmbeddedFont, Arial_MSFontService, sans-serif;\"><strong>Newborn Care</strong>&nbsp;</span></p><p style=\"text-align: justify;\"><span style=\"font-family: Arial, Arial_EmbeddedFont, Arial_MSFontService, sans-serif;\">&nbsp;</span></p><p style=\"text-align: justify;\"><span style=\"font-family: Arial, Arial_EmbeddedFont, Arial_MSFontService, sans-serif;\">The first 28 days of life (the neonatal period) represent the most critical phase for a childs survival. Newborns face a high risk of mortality and disability, largely stemming from sub-optimal maternal health conditions during pregnancy, a lack of standardized antenatal care (ANC) visits, and inadequate delivery management. National data shows that the majority of under-five deaths actually occur during the neonatal period (0–28 days). Therefore, reducing the Neonatal Mortality Rate (NMR) is inseparable from efforts to improve maternal health and provide integrated medical interventions from pregnancy through the postpartum period.&nbsp;</span></p><p style=\"text-align: justify;\"><img src=\"https://cms-ysbh.test/storage/articles/jKbcDBCskv941vbLFJP6uZOIk9LMaUCuxgdo0LaZ.webp\" alt=\"pasted-inline-2.webp\" title=\"pasted-inline-2.webp\" class=\"rounded-lg max-w-full my-2 transition-all cursor-pointer tiptap-uploaded-image inline-block\" style=\"width: 50%; float: left; margin-right: 1rem; margin-top: 0.25rem; margin-bottom: 0.5rem; display: inline !important;\"><span style=\"font-family: Arial, Arial_EmbeddedFont, Arial_MSFontService, sans-serif;\">Saving newborns requires meeting integrated service standards. This includes preventing delivery complications, managing asphyxia, treating severe infections (such as neonatal sepsis), and providing targeted interventions for vulnerable infants. Recent scientific evidence confirms that most deaths in this phase are caused by conditions that are actually preventable and treatable through simple, cost-efficient interventions at primary healthcare facilities. The main risk factors and strategies include: Low Birth Weight (LBW) &amp; Prematurity, Hypothermia, Pneumonia and Neonatal Infections, Optimization of Exclusive Breastfeeding and Early Initiation of Breastfeeding (EIBF). To address these, the latest intervention strategies—such as the Point of Care Quality Improvement (POCQI) approach and collaborative Quality Improvement (QI)—have been introduced to local governments through technical assistance from the Sinar Bhakti Husada Foundation. Additionally, the Sinar Bhakti Husada Foundation assists local governments in developing Governor/Regent Regulations along with Regional Action Plans (RAD) to strengthen Maternal and Child Health (MCH) services and accelerate the reduction of maternal and child mortality, particularly for newborns.&nbsp;</span></p><p style=\"text-align: justify;\"><span style=\"font-family: Arial, Arial_EmbeddedFont, Arial_MSFontService, sans-serif;\">&nbsp;</span></p><p style=\"text-align: justify;\"><span style=\"font-family: Arial, Arial_EmbeddedFont, Arial_MSFontService, sans-serif;\">&nbsp;</span></p><p style=\"text-align: justify;\"><span style=\"font-family: Arial, Arial_EmbeddedFont, Arial_MSFontService, sans-serif;\"><strong>Kesehatan Anak:&nbsp;</strong>&nbsp;</span></p><p style=\"text-align: justify;\"><img src=\"https://cms-ysbh.test/storage/articles/47VqpdcjQSyCZbwTAIxKSA45vllYIkIiFvnIFPXn.webp\" alt=\"pasted-inline-3.webp\" title=\"pasted-inline-3.webp\" class=\"rounded-lg max-w-full my-2 transition-all cursor-pointer tiptap-uploaded-image inline-block\" style=\"width: 50%; float: right; margin-left: 1rem; margin-top: 0.25rem; margin-bottom: 0.5rem; display: inline !important;\"><span style=\"font-family: Arial, Arial_EmbeddedFont, Arial_MSFontService, sans-serif;\">Pneumonia dan diare terus menjadi ancaman infeksi terbesar bagi kesehatan dan kelangsungan hidup bayi serta anak-anak di seluruh dunia. Berdasarkan data Global World Health Organization (WHO) dan </span><a target=\"_blank\" rel=\"noreferrer noopener\" class=\"text-forest underline cursor-pointer Hyperlink SCXW134540084 BCX0\" href=\"https://publichealth.jhu.edu/ivac/2024/tracking-progress-toward-pneumonia-and-diarrhea-control\"><span style=\"font-family: Arial, Arial_EmbeddedFont, Arial_MSFontService, sans-serif;\">Laporan Progress IVAC</span></a><span style=\"font-family: Arial, Arial_EmbeddedFont, Arial_MSFontService, sans-serif;\">, kombinasi kedua penyakit ini menyumbang sekitar 23% dari total kematian balita, yang setara dengan 1,17 juta kematian anak di bawah usia lima tahun setiap tahunnya. Statistik UNICEF mengonfirmasi bahwa pneumonia secara mandiri merenggut nyawa lebih dari 700.000 anak per tahun, menjadikannya penyakit infeksi paling mematikan bagi balita di tingkat global.&nbsp;</span></p><p style=\"text-align: justify;\"><span style=\"font-family: Arial, Arial_EmbeddedFont, Arial_MSFontService, sans-serif;\">Di Indonesia, tren serupa menempatkan pneumonia sebagai penyebab utama kematian pada kelompok anak balita, yang kemudian diikuti secara ketat oleh diare di urutan kedua. Berdasarkan </span><a target=\"_blank\" rel=\"noreferrer noopener\" class=\"text-forest underline cursor-pointer Hyperlink SCXW134540084 BCX0\" href=\"https://kesprimkom.kemkes.go.id/assets/uploads/contents/others/LAKIP_DITJEN_Kesprimkom_TAHUN_2025_semester_1.pdf\"><span style=\"font-family: Arial, Arial_EmbeddedFont, Arial_MSFontService, sans-serif;\">Laporan Akuntabilitas Kemenkes</span></a><span style=\"font-family: Arial, Arial_EmbeddedFont, Arial_MSFontService, sans-serif;\">, Angka Kematian Balita (AKBa) nasional telah ditekan hingga mencapai 19,83 per 1.000 kelahiran hidup, sebuah pencapaian yang berhasil melampaui ambang batas target global SDGs tahun 2030, yaitu sebesar 25 per 1.000 kelahiran hidup.&nbsp;&nbsp;</span></p><p style=\"text-align: justify;\"><span style=\"font-family: Arial, Arial_EmbeddedFont, Arial_MSFontService, sans-serif;\">Yayasan Sinar Bhakti Husada memberikan bantuan teknis kepada pemerintah daerah untuk mengembangkan Peraturan Gubernur/Bupati beserta Rencana Aksi Daerah (RAD) Penanggulangan Pneumonia dan Diare untuk mengikat dukungan lintas sektor, dan peningkatan kapasitas tenaga kesehatan dalam melaksanakan Manajemen Terpadu Balita Sakit (MTBS) dan Kalakarya MTBS, maupun pengembangan kapasitas kader dalam melaksanakan MTBS berbasis masyarakat (MTBS-M) yang dipayungi oleh Permenkes no.70/2013 dan Permenkes no. 41/2018.&nbsp;</span></p><p style=\"text-align: justify;\"><span style=\"font-family: Arial, Arial_EmbeddedFont, Arial_MSFontService, sans-serif;\"><strong>Child Health:</strong>&nbsp;</span></p><p style=\"text-align: justify;\"><span style=\"font-family: Arial, Arial_EmbeddedFont, Arial_MSFontService, sans-serif;\">Pneumonia and diarrhea continue to be the greatest infectious threats to the health and survival of infants and children worldwide. According to global data from the World Health Organization (WHO) and the IVAC Progress Report, the combination of these two diseases accounts for approximately 23% of total under-five deaths, which equates to 1.17 million child deaths under the age of five annually. UNICEF statistics confirm that pneumonia alone claims the lives of over 700,000 children per year, making it the deadliest infectious disease for toddlers globally.&nbsp;&nbsp;</span></p><p style=\"text-align: justify;\"><img src=\"https://cms-ysbh.test/storage/articles/PzN1m6XSbTvVzh3EKzena0AcCmVcIcjcuEmgytDH.webp\" alt=\"pasted-inline-4.webp\" title=\"pasted-inline-4.webp\" class=\"rounded-lg max-w-full my-2 transition-all cursor-pointer tiptap-uploaded-image inline-block\" style=\"width: 100%; display: block !important; margin: 0.75rem auto !important; float: none !important;\"><span style=\"font-family: Arial, Arial_EmbeddedFont, Arial_MSFontService, sans-serif;\">&nbsp;</span></p><p style=\"text-align: justify;\"><span style=\"font-family: Arial, Arial_EmbeddedFont, Arial_MSFontService, sans-serif;\">In Indonesia, a similar trend places pneumonia as the leading cause of death among children under five, followed closely by diarrhea in second place. According to the Ministry of Healths Accountability Report, the national Under-Five Mortality Rate (U5MR) has been reduced to 19.83 per 1.000 live births, an achievement that successfully surpasses the global SDG 2030 target threshold of 25 per 1.000 live births.&nbsp;&nbsp;</span></p><p style=\"text-align: justify;\"><span style=\"font-family: Arial, Arial_EmbeddedFont, Arial_MSFontService, sans-serif;\">The Sinar Bhakti Husada Foundation provides technical assistance to local governments to develop Governor/Regent Regulations along with Regional Action Plans (RAD) for Pneumonia and Diarrhea Control to secure cross-sector support. This includes building the capacity of healthcare workers to implement the Integrated Management of Childhood Illness (IMCI) and IMCI clinical mentoring (<em>Kalakarya</em> MTBS), as well as developing the capacity of volunteers to implement community-based IMCI (c-IMCI), which is legally frameworked by Ministry of Health Regulations (Permenkes) No. 70/2013 and No. 41/2018.&nbsp;</span></p><p style=\"text-align: justify;\"><img src=\"https://cms-ysbh.test/storage/articles/GiTSodiPBgFSwtZLdOFNjJPoF8h7jMtVwh7N5Keq.webp\" alt=\"pasted-inline-5.webp\" title=\"pasted-inline-5.webp\" class=\"rounded-lg max-w-full my-2 transition-all cursor-pointer tiptap-uploaded-image inline-block\" style=\"width: 25%; display: block !important; margin: 0.75rem auto !important; float: none !important;\"><span style=\"font-family: Arial, Arial_EmbeddedFont, Arial_MSFontService, sans-serif;\">&nbsp;</span></p><p style=\"text-align: justify;\"><span style=\"font-family: Arial, Arial_EmbeddedFont, Arial_MSFontService, sans-serif;\">&nbsp;</span></p>','default.webp','draft',NULL,'2026-07-16 04:18:24','2026-07-16 04:18:24'),
(4,2,4,'Magia Outro','magia-outro','this is dummy content','default.webp','rejected',NULL,'2026-07-16 02:45:56','2026-07-16 02:45:56'),
(5,2,4,'Merry Go round','merry-go-round','this is dummy content','default.webp','scheduled',NULL,'2026-07-16 02:45:56','2026-07-16 02:45:56'),
(6,2,4,'Children in Gaza need life-saving support','children-in-gaza-need-life-saving-support','this is dummy content','default.webp','published',NULL,'2026-07-16 02:45:56','2026-07-16 02:45:56'),
(7,2,4,'il vento d\'oro','il-vento-doro','this is dummy content, Golden Wind','default.webp','draft',NULL,'2026-07-16 02:45:56','2026-07-16 02:45:56'),
(8,2,4,'Program Kesehatan Ibu & Anak (KIA)','program-kesehatan-ibu-anak-kia','<h1><span style=\"font-size: 42px; color: rgb(6, 79, 59); font-family: Roboto;\">Program Kesehatan Ibu &amp; Anak (KIA)</span></h1><p><span style=\"font-size: 18px; color: rgb(75, 93, 83); font-family: &quot;Instrument Sans&quot;, ui-sans-serif, system-ui, sans-serif, &quot;Apple Color Emoji&quot;, &quot;Segoe UI Emoji&quot;, &quot;Segoe UI Symbol&quot;, &quot;Noto Color Emoji&quot;;\">Program KIA dukungan Yayasan Sinar Bhakti Husada telah mendukung pemerintah daerah untuk memastikan bahwa ibu hamil, bayi baru lahir, anak-anak, dan remaja di seluruh wilayah dukungan — termasuk daerah terpencil — memiliki akses ke layanan kesehatan primer yang adil dan berkualitas tinggi, khususnya di kampung-kampung yang terpinggirkan dan kurang terlayani.</span></p><p><img src=\"http://cms-ysbh.test/storage/articles/pvzIgfwdnKH4pAc1pzs33Y7YEU4lhpexx6gX0JvR.webp\" alt=\"kia-1.webp\" title=\"kia-1.webp\" class=\"rounded-lg max-w-full my-2 transition-all cursor-pointer tiptap-uploaded-image inline-block\" style=\"width: 100%; display: block !important; margin: 0.75rem auto !important; float: none !important;\"></p><section data-bg-color=\"#E9F1EB\" data-inner-color=\"transparent\" data-type=\"section-block\" class=\"tiptap-full-bleed max-w-7xl mx-auto px-[2.5rem] relative py-10 sm:py-12\" style=\"--bg-outer: #E9F1EB;\"><div class=\"transition-colors duration-300 flow-root \" style=\"background-color: transparent;\"><div data-type=\"column-block\" class=\"flex flex-col md:flex-row gap-2 my-6 w-full px-[2.5rem] [[data-type=&quot;section-block&quot;]_&amp;]:px-0\"><div data-span=\"1\" data-type=\"column\" class=\"flex flex-col gap-4 min-w-0 p-0 has-[[data-type=info-card]]:!p-0  border border-transparent md:flex-1\"><p><span style=\"font-size: 13px; color: rgb(190, 20, 23);\"><span data-icon=\"crosshair\" data-type=\"eyebrow\" class=\"inline-flex items-center gap-2.5 font-bold tracking-[0.16em] uppercase align-middle mx-0.5\" style=\"color: rgb(190, 20, 23); font-size: 13px;\"><span contenteditable=\"false\" class=\"flex shrink-0 select-none\"><svg xmlns=\"http://www.w3.org/2000/svg\" viewbox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\" style=\"width: 1.2em; height: 1.2em; display: block;\"><circle cx=\"12\" cy=\"12\" r=\"9\"></circle><path d=\"M12 3v2M12 19v2M3 12h2M19 12h2\"></path></svg></span><span class=\"outline-none\"><span style=\"font-size: 13px; color: rgb(190, 20, 23);\">Pendekatan</span></span></span></span></p><p><span style=\"font-size: 26px; color: rgb(7, 51, 36); font-family: &quot;Instrument Sans&quot;, ui-sans-serif, system-ui, sans-serif, &quot;Apple Color Emoji&quot;, &quot;Segoe UI Emoji&quot;, &quot;Segoe UI Symbol&quot;, &quot;Noto Color Emoji&quot;;\"><strong>Menjawab tantangan geografis lewat penguatan tenaga medis lokal, kader posyandu, dan Puskesmas.</strong></span></p></div><div data-span=\"1\" data-type=\"column\" class=\"flex flex-col gap-4 min-w-0 p-0 has-[[data-type=info-card]]:!p-0  border border-transparent md:flex-1\"><p>Tantangan geografis yang unik di berbagai wilayah di daerah dukungan yang tersebar di beberapa Provinsi dan Kabupaten di Tanah Papua dijawab dengan memperkuat kapasitas tenaga medis lokal, kader posyandu, dan Puskesmas. Penguatan ini bertujuan untuk merencanakan, melaksanakan, memantau, dan memberikan layanan esensial bagi keselamatan dan kesehatan ibu dan bayi baru lahir.</p><p>Program ini juga berfokus pada pencegahan, pengendalian, dan pengeliminasian penyakit utama pada anak — terutama penyakit yang sering menyebabkan kematian pada anak seperti pneumonia dan diare, malnutrisi (stunting), penyakit yang dapat dicegah dengan imunisasi (PD3I), serta penyakit menular endemik seperti malaria — melalui peningkatan sistem kesehatan yang tangguh, terutama di Puskesmas Model yang juga berfungsi sebagai Puskesmas<em> On The Job Training </em>(OJT) Center di masing-masing Kabupaten dampingan.</p></div></div></div></section><p></p><p><span style=\"font-size: 13px; color: rgb(190, 20, 23);\"><span data-icon=\"crosshair\" data-type=\"eyebrow\" class=\"inline-flex items-center gap-2.5 font-bold tracking-[0.16em] uppercase align-middle mx-0.5\" style=\"color: rgb(190, 20, 23); font-size: 13px;\"><span contenteditable=\"false\" class=\"flex shrink-0 select-none\"><svg xmlns=\"http://www.w3.org/2000/svg\" viewbox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\" style=\"width: 1.2em; height: 1.2em; display: block;\"><circle cx=\"12\" cy=\"12\" r=\"9\"></circle><path d=\"M12 3v2M12 19v2M3 12h2M19 12h2\"></path></svg></span><span class=\"outline-none\"><span style=\"font-size: 13px; color: rgb(190, 20, 23);\">Empat Komponen Utama</span></span></span></span></p><h1>Program KIA di wilayah ini</h1><p><span style=\"font-size: 16.5px; color: rgb(75, 93, 83);\">Diimplementasikan di distrik-distrik terpilih dengan pendekatan yang disesuaikan konteks sosial-budaya masyarakat Papua, dengan harapan pemerintah daerah dan lintas sektor dapat mengadopsi serta memperluas skalanya.</span></p><div data-type=\"column-block\" class=\"flex flex-col md:flex-row gap-2 my-6 w-full px-[2.5rem] [[data-type=&quot;section-block&quot;]_&amp;]:px-0\"><div data-span=\"1\" data-type=\"column\" class=\"flex flex-col gap-4 min-w-0 p-0 has-[[data-type=info-card]]:!p-0  border border-transparent md:flex-1\"><div bgcolor=\"white\" data-fill-height=\"true\" data-type=\"card\" class=\"flex flex-col border border-transparent rounded-[18px] p-6 md:p-8 shadow-sm transition-colors w-full h-full\" style=\"background-color: rgb(255, 255, 255);\"><p><span style=\"font-size: 32px; color: rgb(235, 204, 38);\"><strong>01</strong></span></p><p><span style=\"font-size: 14px; color: rgb(0, 0, 0);\">Perawatan Ibu Hamil &amp; Bayi Baru Lahir</span></p></div></div><div data-span=\"1\" data-type=\"column\" class=\"flex flex-col gap-4 min-w-0 p-0 has-[[data-type=info-card]]:!p-0  border border-transparent md:flex-1\"><div bgcolor=\"white\" data-fill-height=\"true\" data-type=\"card\" class=\"flex flex-col border border-transparent rounded-[18px] p-6 md:p-8 shadow-sm transition-colors w-full h-full\" style=\"background-color: rgb(255, 255, 255);\"><p><span style=\"font-size: 32px; color: rgb(235, 204, 38);\"><strong>02</strong></span></p><p><span style=\"font-size: 14px; color: rgb(0, 0, 0);\">Kesehatan dan gizi anak</span></p></div></div><div data-span=\"1\" data-type=\"column\" class=\"flex flex-col gap-4 min-w-0 p-0 has-[[data-type=info-card]]:!p-0  border border-transparent md:flex-1\"><div bgcolor=\"white\" data-fill-height=\"true\" data-type=\"card\" class=\"flex flex-col border border-transparent rounded-[18px] p-6 md:p-8 shadow-sm transition-colors w-full h-full\" style=\"background-color: rgb(255, 255, 255);\"><p><span style=\"font-size: 32px; color: rgb(235, 204, 38);\"><strong>03</strong></span></p><p><span style=\"font-size: 14px; color: rgb(0, 0, 0);\">Penguatan sistem kesehatan berbasis komunitas</span></p></div></div><div data-span=\"1\" data-type=\"column\" class=\"flex flex-col gap-4 min-w-0 p-0 has-[[data-type=info-card]]:!p-0  border border-transparent md:flex-1\"><div bgcolor=\"white\" data-fill-height=\"true\" data-type=\"card\" class=\"flex flex-col border border-transparent rounded-[18px] p-6 md:p-8 shadow-sm transition-colors w-full h-full\" style=\"background-color: rgb(255, 255, 255);\"><p><span style=\"font-size: 32px; color: rgb(235, 204, 38);\"><strong>04</strong></span></p><p><span style=\"font-size: 14px; color: rgb(0, 0, 0);\">Kesehatan dalam keadaan darurat</span></p></div></div></div><p></p><section data-bg-color=\"#E9F1EB\" data-inner-color=\"transparent\" data-type=\"section-block\" class=\"tiptap-full-bleed max-w-7xl mx-auto px-[2.5rem] relative py-10 sm:py-12\" style=\"--bg-outer: #E9F1EB;\"><div class=\"transition-colors duration-300 flow-root \" style=\"background-color: transparent;\"><p><span style=\"font-size: 13px; color: rgb(190, 20, 23);\"><span data-icon=\"crosshair\" data-type=\"eyebrow\" class=\"inline-flex items-center gap-2.5 font-bold tracking-[0.16em] uppercase align-middle mx-0.5\" style=\"color: rgb(190, 20, 23); font-size: 13px;\"><span contenteditable=\"false\" class=\"flex shrink-0 select-none\"><svg xmlns=\"http://www.w3.org/2000/svg\" viewbox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\" style=\"width: 1.2em; height: 1.2em; display: block;\"><circle cx=\"12\" cy=\"12\" r=\"9\"></circle><path d=\"M12 3v2M12 19v2M3 12h2M19 12h2\"></path></svg></span><span class=\"outline-none\"><span style=\"font-size: 13px; color: rgb(190, 20, 23);\">Wilayah dampingan</span></span></span></span></p><h1><span style=\"color: rgb(6, 79, 59);\">Tersebar Di Tanah Papua</span></h1><div bgcolor=\"white\" data-fill-height=\"true\" data-type=\"card\" class=\"flex flex-col border border-transparent rounded-[18px] p-6 md:p-8 shadow-sm transition-colors w-full h-full\" style=\"background-color: rgb(255, 255, 255);\"><p>3 Provinsi • 9 Kabupaten</p><div data-type=\"column-block\" class=\"flex flex-col md:flex-row gap-2 my-6 w-full px-[2.5rem] [[data-type=&quot;section-block&quot;]_&amp;]:px-0\"><div data-span=\"1\" data-type=\"column\" class=\"flex flex-col gap-4 min-w-0 p-0 has-[[data-type=info-card]]:!p-0  border border-transparent md:flex-1\"><p><span style=\"font-size: 14px;\">PAPUA</span></p><p><span style=\"font-size: 12px; color: inherit;\"><strong><span class=\"pill-wrapper inline-flex items-center font-medium rounded-full\" style=\"background-color: rgb(233, 241, 235); border: 1.5px solid transparent; padding: 0.3em 0.85em; margin: 0.3rem 0.3rem 0.3rem 0px; color: inherit;\">Sarmi</span></strong></span></p></div><div data-span=\"1\" data-type=\"column\" class=\"flex flex-col gap-4 min-w-0 p-0 has-[[data-type=info-card]]:!p-0  border border-transparent md:flex-1\"><p><span style=\"font-size: 14px;\">PAPUA PEGUNUNGAN</span></p><p><span style=\"font-size: 12px; color: inherit;\"><strong><span class=\"pill-wrapper inline-flex items-center font-medium rounded-full\" style=\"background-color: rgb(233, 241, 235); border: 1.5px solid transparent; padding: 0.3em 0.85em; margin: 0.3rem 0.3rem 0.3rem 0px; color: inherit;\">Pegunungan</span></strong></span><span style=\"font-size: 12px;\"><strong> </strong></span><span style=\"font-size: 12px; color: inherit;\"><strong><span class=\"pill-wrapper inline-flex items-center font-medium rounded-full\" style=\"background-color: rgb(233, 241, 235); border: 1.5px solid transparent; padding: 0.3em 0.85em; margin: 0.3rem 0.3rem 0.3rem 0px; color: inherit;\">Bintang</span></strong></span><span style=\"font-size: 12px;\"><strong> </strong></span><span style=\"font-size: 12px; color: inherit;\"><strong><span class=\"pill-wrapper inline-flex items-center font-medium rounded-full\" style=\"background-color: rgb(233, 241, 235); border: 1.5px solid transparent; padding: 0.3em 0.85em; margin: 0.3rem 0.3rem 0.3rem 0px; color: inherit;\">Yahukimo</span></strong></span></p></div><div data-span=\"2\" data-type=\"column\" class=\"flex flex-col gap-4 min-w-0 p-0 has-[[data-type=info-card]]:!p-0  border border-transparent md:flex-1\"><p><span style=\"font-size: 14px;\">PAPUA TENGAH</span></p><p><span style=\"font-size: 13.5px; color: inherit;\"><strong><span class=\"pill-wrapper inline-flex items-center font-medium rounded-full\" style=\"background-color: rgb(233, 241, 235); border: 1.5px solid transparent; padding: 0.3em 0.85em; margin: 0.3rem 0.3rem 0.3rem 0px; color: inherit;\">Nabire</span></strong></span><span style=\"font-size: 13.5px; color: rgb(7, 51, 36);\"><strong> </strong></span><span style=\"font-size: 13.5px; color: inherit;\"><strong><span class=\"pill-wrapper inline-flex items-center font-medium rounded-full\" style=\"background-color: rgb(233, 241, 235); border: 1.5px solid transparent; padding: 0.3em 0.85em; margin: 0.3rem 0.3rem 0.3rem 0px; color: inherit;\">Mimika</span></strong></span><span style=\"font-size: 13.5px; color: rgb(7, 51, 36);\"><strong> </strong></span><span style=\"font-size: 13.5px; color: inherit;\"><strong><span class=\"pill-wrapper inline-flex items-center font-medium rounded-full\" style=\"background-color: rgb(233, 241, 235); border: 1.5px solid transparent; padding: 0.3em 0.85em; margin: 0.3rem 0.3rem 0.3rem 0px; color: inherit;\">Paniai</span></strong></span><span style=\"font-size: 13.5px; color: rgb(7, 51, 36);\"><strong> </strong></span><span style=\"font-size: 13.5px; color: inherit;\"><strong><span class=\"pill-wrapper inline-flex items-center font-medium rounded-full\" style=\"background-color: rgb(233, 241, 235); border: 1.5px solid transparent; padding: 0.3em 0.85em; margin: 0.3rem 0.3rem 0.3rem 0px; color: inherit;\">Deiyai</span></strong></span><span style=\"font-size: 13.5px; color: rgb(7, 51, 36);\"><strong> </strong></span><span style=\"font-size: 13.5px; color: inherit;\"><strong><span class=\"pill-wrapper inline-flex items-center font-medium rounded-full\" style=\"background-color: rgb(233, 241, 235); border: 1.5px solid transparent; padding: 0.3em 0.85em; margin: 0.3rem 0.3rem 0.3rem 0px; color: inherit;\">Dogiyai</span></strong></span><span style=\"font-size: 13.5px; color: rgb(7, 51, 36);\"><strong> </strong></span><span style=\"font-size: 13.5px; color: inherit;\"><strong><span class=\"pill-wrapper inline-flex items-center font-medium rounded-full\" style=\"background-color: rgb(233, 241, 235); border: 1.5px solid transparent; padding: 0.3em 0.85em; margin: 0.3rem 0.3rem 0.3rem 0px; color: inherit;\">Puncak Jaya</span></strong></span></p><p><br></p></div></div></div></div></section><p></p><p><span style=\"font-size: 13px; color: rgb(190, 20, 23);\"><span data-icon=\"crosshair\" data-type=\"eyebrow\" class=\"inline-flex items-center gap-2.5 font-bold tracking-[0.16em] uppercase align-middle mx-0.5\" style=\"color: rgb(190, 20, 23); font-size: 13px;\"><span contenteditable=\"false\" class=\"flex shrink-0 select-none\"><svg xmlns=\"http://www.w3.org/2000/svg\" viewbox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\" style=\"width: 1.2em; height: 1.2em; display: block;\"><circle cx=\"12\" cy=\"12\" r=\"9\"></circle><path d=\"M12 3v2M12 19v2M3 12h2M19 12h2\"></path></svg></span><span class=\"outline-none\"><span style=\"font-size: 13px; color: rgb(190, 20, 23);\">komponen 01</span></span></span></span></p><h1>Perawatan Bayi Baru Lahir</h1><p></p><div data-type=\"column-block\" class=\"flex flex-col md:flex-row gap-2 my-6 w-full px-[2.5rem] [[data-type=&quot;section-block&quot;]_&amp;]:px-0\"><div data-span=\"1\" data-type=\"column\" class=\"flex flex-col gap-4 min-w-0 p-0 has-[[data-type=info-card]]:!p-0  border border-transparent md:flex-1\"><p><img src=\"http://cms-ysbh.test/storage/articles/I2hPOKgoMFLZuZPZMWW9opfjqpLDhrH3cecmpKDV.webp\" alt=\"kia-2-cropped.webp\" title=\"kia-2-cropped.webp\" class=\"rounded-lg max-w-full my-2 transition-all cursor-pointer tiptap-uploaded-image inline-block\" style=\"width: 100% !important; display: block !important; margin: 0.75rem auto !important; float: none !important;\"></p></div><div data-span=\"2\" data-type=\"column\" class=\"flex flex-col gap-4 min-w-0 p-0 has-[[data-type=info-card]]:!p-0  border border-transparent md:flex-1\"><p>Periode 28 hari pertama kehidupan (neonatal) merupakan fase paling kritis bagi kelangsungan hidup seorang anak. Bayi baru lahir menghadapi risiko tinggi mengalami kematian dan kecacatan, yang sebagian besar berakar dari kondisi kesehatan ibu yang kurang optimal selama kehamilan, kurangnya kunjungan antenatal (ANC) standar, serta penanganan persalinan yang tidak memadai. Data nasional menunjukkan bahwa mayoritas kematian balita justru terjadi pada masa neonatal (0–28 hari).</p><p>Oleh karena itu, penurunan Angka Kematian Neonatal (AKN) tidak dapat dipisahkan dari upaya peningkatan kesehatan ibu dan intervensi medis yang terintegrasi sejak masa kehamilan hingga pasca persalinan. Penyelamatan bayi baru lahir membutuhkan pemenuhan standar pelayanan terpadu — mencakup pencegahan komplikasi persalinan, tata laksana asfiksia, penanganan infeksi berat (seperti sepsis neonatal), serta intervensi khusus untuk bayi rentan.</p></div></div><p><strong>Faktor-faktor resiko utama :</strong></p><div data-type=\"column-block\" class=\"flex flex-col md:flex-row gap-2 my-6 w-full px-[2.5rem] [[data-type=&quot;section-block&quot;]_&amp;]:px-0\"><div data-span=\"1\" data-type=\"column\" class=\"flex flex-col gap-4 min-w-0 p-0 has-[[data-type=info-card]]:!p-0  border border-transparent md:flex-1\"><div bgcolor=\"softForest\" data-fill-height=\"false\" data-type=\"card\" class=\"flex flex-col border border-transparent rounded-[18px] p-6 md:p-8 shadow-sm transition-colors w-full \" style=\"background-color: rgb(255, 255, 255);\"><p><span style=\"font-size: 13px; color: rgb(190, 20, 23);\"><span data-icon=\"flag\" data-type=\"eyebrow\" class=\"inline-flex items-center gap-2.5 font-bold tracking-[0.16em] uppercase align-middle mx-0.5\" style=\"color: rgb(190, 20, 23); font-size: 13px;\"><span contenteditable=\"false\" class=\"flex shrink-0 select-none\"><svg xmlns=\"http://www.w3.org/2000/svg\" viewbox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\" style=\"width: 1.2em; height: 1.2em; display: block;\"><path d=\"M4 22V4a1 1 0 0 1 .4-.8A6 6 0 0 1 8 2c3 0 5 2 7.333 2q2 0 3.067-.8A1 1 0 0 1 20 4v10a1 1 0 0 1-.4.8A6 6 0 0 1 16 16c-3 0-5-2-8-2a6 6 0 0 0-4 1.528\"></path></svg></span><span class=\"outline-none\"><span style=\"font-size: 13px; color: rgb(190, 20, 23);\">Berat Badan Lahir Rendah (BBLR) &amp; prematuritas</span></span></span></span></p></div><p></p><div bgcolor=\"softForest\" data-fill-height=\"false\" data-type=\"card\" class=\"flex flex-col border border-transparent rounded-[18px] p-6 md:p-8 shadow-sm transition-colors w-full \" style=\"background-color: rgb(255, 255, 255);\"><p><span style=\"font-size: 13px; color: rgb(190, 20, 23);\"><span data-icon=\"flag\" data-type=\"eyebrow\" class=\"inline-flex items-center gap-2.5 font-bold tracking-[0.16em] uppercase align-middle mx-0.5\" style=\"color: rgb(190, 20, 23); font-size: 13px;\"><span contenteditable=\"false\" class=\"flex shrink-0 select-none\"><svg xmlns=\"http://www.w3.org/2000/svg\" viewbox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\" style=\"width: 1.2em; height: 1.2em; display: block;\"><path d=\"M4 22V4a1 1 0 0 1 .4-.8A6 6 0 0 1 8 2c3 0 5 2 7.333 2q2 0 3.067-.8A1 1 0 0 1 20 4v10a1 1 0 0 1-.4.8A6 6 0 0 1 16 16c-3 0-5-2-8-2a6 6 0 0 0-4 1.528\"></path></svg></span><span class=\"outline-none\"><span style=\"font-size: 13px; color: rgb(190, 20, 23);\">pneumonia &amp; infeksi neonatal</span></span></span></span></p></div></div><div data-span=\"1\" data-type=\"column\" class=\"flex flex-col gap-4 min-w-0 p-0 has-[[data-type=info-card]]:!p-0  border border-transparent md:flex-1\"><div bgcolor=\"softForest\" data-fill-height=\"false\" data-type=\"card\" class=\"flex flex-col border border-transparent rounded-[18px] p-6 md:p-8 shadow-sm transition-colors w-full \" style=\"background-color: rgb(255, 255, 255);\"><p><span style=\"font-size: 13px; color: rgb(190, 20, 23);\"><span data-icon=\"flag\" data-type=\"eyebrow\" class=\"inline-flex items-center gap-2.5 font-bold tracking-[0.16em] uppercase align-middle mx-0.5\" style=\"color: rgb(190, 20, 23); font-size: 13px;\"><span contenteditable=\"false\" class=\"flex shrink-0 select-none\"><svg xmlns=\"http://www.w3.org/2000/svg\" viewbox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\" style=\"width: 1.2em; height: 1.2em; display: block;\"><path d=\"M4 22V4a1 1 0 0 1 .4-.8A6 6 0 0 1 8 2c3 0 5 2 7.333 2q2 0 3.067-.8A1 1 0 0 1 20 4v10a1 1 0 0 1-.4.8A6 6 0 0 1 16 16c-3 0-5-2-8-2a6 6 0 0 0-4 1.528\"></path></svg></span><span class=\"outline-none\"><span style=\"font-size: 13px; color: rgb(190, 20, 23);\">Hipotermia</span></span></span></span></p></div><p></p><div bgcolor=\"softForest\" data-fill-height=\"false\" data-type=\"card\" class=\"flex flex-col border border-transparent rounded-[18px] p-6 md:p-8 shadow-sm transition-colors w-full \" style=\"background-color: rgb(255, 255, 255);\"><p><span style=\"font-size: 13px; color: rgb(190, 20, 23);\"><span data-icon=\"flag\" data-type=\"eyebrow\" class=\"inline-flex items-center gap-2.5 font-bold tracking-[0.16em] uppercase align-middle mx-0.5\" style=\"color: rgb(190, 20, 23); font-size: 13px;\"><span contenteditable=\"false\" class=\"flex shrink-0 select-none\"><svg xmlns=\"http://www.w3.org/2000/svg\" viewbox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\" style=\"width: 1.2em; height: 1.2em; display: block;\"><path d=\"M4 22V4a1 1 0 0 1 .4-.8A6 6 0 0 1 8 2c3 0 5 2 7.333 2q2 0 3.067-.8A1 1 0 0 1 20 4v10a1 1 0 0 1-.4.8A6 6 0 0 1 16 16c-3 0-5-2-8-2a6 6 0 0 0-4 1.528\"></path></svg></span><span class=\"outline-none\"><span style=\"font-size: 13px; color: rgb(190, 20, 23);\">optimalisasi asi eksklusif &amp; IMD</span></span></span></span></p></div></div></div><p></p><div data-type=\"column-block\" class=\"flex flex-col md:flex-row gap-2 my-6 w-full px-[2.5rem] [[data-type=&quot;section-block&quot;]_&amp;]:px-0\"><div data-span=\"1\" data-type=\"column\" class=\"flex flex-col gap-4 min-w-0 p-0 has-[[data-type=info-card]]:!p-0  border border-transparent md:flex-1\"><p><span style=\"font-size: medium; color: rgb(75, 93, 83);\">Strategi intervensi terbaru berupa pendekatan Point of Care Quality Improvement (POCQI) dan Quality Improvement (QI) kolaboratif telah diperkenalkan kepada pemerintah daerah melalui pendampingan teknis dari Yayasan Sinar Bhakti Husada. Selain itu, Yayasan juga membantu pemerintah daerah mengembangkan Peraturan Gubernur/Bupati beserta Rencana Aksi Daerah (RAD) Penguatan Layanan KIA dan Percepatan Penurunan Angka Kematian Ibu dan Anak, terutama bayi baru lahir.</span></p></div><div data-span=\"2\" data-type=\"column\" class=\"flex flex-col gap-4 min-w-0 p-0 has-[[data-type=info-card]]:!p-0  border border-transparent md:flex-1\"><p><img src=\"http://cms-ysbh.test/storage/articles/37n5KPq5gyJZz5kqDK9VxiHBg4gIgFzJcaMUCFfH.webp\" alt=\"kia-3.webp\" title=\"kia-3.webp\" class=\"rounded-lg max-w-full my-2 transition-all cursor-pointer tiptap-uploaded-image inline-block\" style=\"width: 100% !important; display: block !important; margin: 0.75rem auto !important; float: none !important;\"></p></div></div><p></p><section data-bg-color=\"#E9F1EB\" data-inner-color=\"transparent\" data-type=\"section-block\" class=\"tiptap-full-bleed max-w-7xl mx-auto px-[2.5rem] relative py-10 sm:py-12\" style=\"--bg-outer: #E9F1EB;\"><div class=\"transition-colors duration-300 flow-root \" style=\"background-color: transparent;\"><p><span data-icon=\"crosshair\" data-type=\"eyebrow\" class=\"inline-flex items-center gap-2.5 font-bold tracking-[0.16em] uppercase align-middle mx-0.5\" style=\"color: rgb(190, 20, 23); font-size: 13px;\"><span contenteditable=\"false\" class=\"flex shrink-0 select-none\"><svg xmlns=\"http://www.w3.org/2000/svg\" viewbox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\" style=\"width: 1.2em; height: 1.2em; display: block;\"><circle cx=\"12\" cy=\"12\" r=\"9\"></circle><path d=\"M12 3v2M12 19v2M3 12h2M19 12h2\"></path></svg></span><span class=\"outline-none\">komponen 02</span></span></p><h1>Kesehatan Anak</h1><p>p</p><p><span style=\"font-size: medium; color: rgb(75, 93, 83);\">Pneumonia dan diare terus menjadi ancaman infeksi terbesar bagi kesehatan dan kelangsungan hidup bayi serta anak-anak di seluruh dunia. Berdasarkan data WHO dan </span><a target=\"_blank\" rel=\"noopener\" class=\"text-forest underline cursor-pointer font-bold decoration-goldy underline-offset-4\" href=\"https://publichealth.jhu.edu/ivac/2024/tracking-progress-toward-pneumonia-and-diarrhea-control\"><strong>Laporan Progress IVAC</strong></a><span style=\"font-size: medium; color: rgb(75, 93, 83);\">, kombinasi kedua penyakit ini menyumbang sekitar 23% dari total kematian balita — setara 1,17 juta kematian anak di bawah lima tahun setiap tahunnya. Statistik UNICEF mengonfirmasi pneumonia secara mandiri merenggut nyawa lebih dari 700.000 anak per tahun, menjadikannya penyakit infeksi paling mematikan bagi balita di tingkat global.</span></p><div data-type=\"column-block\" class=\"flex flex-col md:flex-row gap-2 my-6 w-full px-[2.5rem] [[data-type=&quot;section-block&quot;]_&amp;]:px-0\"><div data-span=\"1\" data-type=\"column\" class=\"flex flex-col gap-4 min-w-0 p-0 has-[[data-type=info-card]]:!p-0  border border-transparent md:flex-1\"><p><img src=\"http://cms-ysbh.test/storage/articles/Cw6r4P50CkXhyDYrmcct0pZjZBI8H2nQCS9zYAmB.webp\" alt=\"kia-4-a.webp\" title=\"kia-4-a.webp\" class=\"rounded-lg max-w-full my-2 transition-all cursor-pointer tiptap-uploaded-image inline-block\" style=\"width: 100% !important; display: block !important; margin: 0.75rem auto !important; float: none !important;\"></p></div><div data-span=\"1\" data-type=\"column\" class=\"flex flex-col gap-4 min-w-0 p-0 has-[[data-type=info-card]]:!p-0  border border-transparent md:flex-1\"><p><img src=\"http://cms-ysbh.test/storage/articles/0rXOxAmtVrFSriLvqBv5BCmCoCXfPilnuzi4H7Dm.webp\" alt=\"kia-4-b.webp\" title=\"kia-4-b.webp\" class=\"rounded-lg max-w-full my-2 transition-all cursor-pointer tiptap-uploaded-image inline-block\" style=\"width: 100% !important; display: block !important; margin: 0.75rem auto !important; float: none !important;\"></p></div></div><div data-type=\"column-block\" class=\"flex flex-col md:flex-row gap-2 my-6 w-full px-[2.5rem] [[data-type=&quot;section-block&quot;]_&amp;]:px-0\"><div data-span=\"1\" data-type=\"column\" class=\"flex flex-col gap-4 min-w-0 p-0 has-[[data-type=info-card]]:!p-0  border border-transparent md:flex-1\"><div bgcolor=\"forest\" data-fill-height=\"true\" data-type=\"card\" class=\"flex flex-col border border-transparent rounded-[18px] p-6 md:p-8 shadow-sm transition-colors w-full h-full\" style=\"background-color: rgb(233, 241, 235);\"><p style=\"text-align: center;\"><span style=\"font-size: 32px; color: rgb(235, 204, 38);\"><strong>23%</strong></span></p><p style=\"text-align: center;\"><span style=\"font-size: 14px; color: rgb(255, 255, 255);\">dari total kematian bayi global</span></p></div></div><div data-span=\"1\" data-type=\"column\" class=\"flex flex-col gap-4 min-w-0 p-0 has-[[data-type=info-card]]:!p-0  border border-transparent md:flex-1\"><div bgcolor=\"forest\" data-fill-height=\"true\" data-type=\"card\" class=\"flex flex-col border border-transparent rounded-[18px] p-6 md:p-8 shadow-sm transition-colors w-full h-full\" style=\"background-color: rgb(233, 241, 235);\"><p style=\"text-align: center;\"><span style=\"font-size: 32px; color: rgb(235, 204, 38);\"><strong>1,17 juta</strong></span></p><p style=\"text-align: center;\"><span style=\"font-size: 14px; color: rgb(255, 255, 255);\">kematian anak balita per tahun</span></p></div></div><div data-span=\"1\" data-type=\"column\" class=\"flex flex-col gap-4 min-w-0 p-0 has-[[data-type=info-card]]:!p-0  border border-transparent md:flex-1\"><div bgcolor=\"forest\" data-fill-height=\"true\" data-type=\"card\" class=\"flex flex-col border border-transparent rounded-[18px] p-6 md:p-8 shadow-sm transition-colors w-full h-full\" style=\"background-color: rgb(233, 241, 235);\"><p style=\"text-align: center;\"><span style=\"font-size: 32px; color: rgb(235, 204, 38);\"><strong>700rb+</strong></span></p><p style=\"text-align: center;\"><span style=\"font-size: 14px; color: rgb(255, 255, 255);\">kematian akibat pneumonia pern tahun</span></p></div></div><div data-span=\"1\" data-type=\"column\" class=\"flex flex-col gap-4 min-w-0 p-0 has-[[data-type=info-card]]:!p-0  border border-transparent md:flex-1\"><div bgcolor=\"forest\" data-fill-height=\"true\" data-type=\"card\" class=\"flex flex-col border border-transparent rounded-[18px] p-6 md:p-8 shadow-sm transition-colors w-full h-full\" style=\"background-color: rgb(233, 241, 235);\"><p style=\"text-align: center;\"><span style=\"font-size: 32px; color: rgb(235, 204, 38);\"><strong>19,38</strong></span></p><p style=\"text-indent: 2rem; text-align: center;\"><span style=\"font-size: 14px; color: rgb(255, 255, 255);\">AKBa nasional per 1000 kelahiran</span><br><span style=\"font-size: 14px; color: rgb(255, 255, 255);\">(target SDG : 25)</span></p></div></div></div><div data-type=\"column-block\" class=\"flex flex-col md:flex-row gap-2 my-6 w-full px-[2.5rem] [[data-type=&quot;section-block&quot;]_&amp;]:px-0\"><div data-span=\"1\" data-type=\"column\" class=\"flex flex-col gap-4 min-w-0 p-0 has-[[data-type=info-card]]:!p-0  border border-transparent md:flex-1\"><p>Di Indonesia, tren serupa menempatkan pneumonia sebagai penyebab utama kematian pada kelompok anak balita, diikuti ketat oleh diare. Berdasarkan <a target=\"_blank\" rel=\"noopener\" class=\"text-forest underline cursor-pointer font-bold decoration-goldy underline-offset-4\" href=\"https://kesprimkom.kemkes.go.id/assets/uploads/contents/others/LAKIP_DITJEN_Kesprimkom_TAHUN_2025_semester_1.pdf\"><strong>Laporan Akuntabilitas Kemenkes</strong></a>, Angka Kematian Balita (AKBa) nasional telah ditekan hingga 19,83 per 1.000 kelahiran hidup — melampaui ambang batas target global SDGs 2030 sebesar 25 per 1.000 kelahiran hidup.</p><p>Yayasan Sinar Bhakti Husada memberikan bantuan teknis kepada pemerintah daerah untuk mengembangkan Peraturan Gubernur/Bupati beserta Rencana Aksi Daerah (RAD) Penanggulangan Pneumonia dan Diare, serta peningkatan kapasitas tenaga kesehatan dalam melaksanakan Manajemen Terpadu Balita Sakit (MTBS) dan Kalakarya MTBS, maupun pengembangan kapasitas kader dalam melaksanakan MTBS berbasis masyarakat (MTBS-M) yang dipayungi oleh Permenkes No. 70/2013 dan Permenkes No. 41/2018.</p></div><div data-span=\"1\" data-type=\"column\" class=\"flex flex-col gap-4 min-w-0 p-0 has-[[data-type=info-card]]:!p-0  border border-transparent md:flex-1\"><p><img src=\"http://cms-ysbh.test/storage/articles/SMvYCtKiZXCIeOOQC69KSYrymUgi3K4wEdr0r5s0.webp\" alt=\"kia-5.webp\" title=\"kia-5.webp\" class=\"rounded-lg max-w-full my-2 transition-all cursor-pointer tiptap-uploaded-image inline-block\" style=\"width: 100% !important; display: block !important; margin: 0.75rem auto !important; float: none !important;\"></p></div></div></div></section><p></p><p></p><p></p><p></p><p></p><p></p><p></p>','default.webp','draft',NULL,'2026-07-16 06:15:13','2026-07-20 08:05:44');
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
(1,'admin','web','2026-07-16 02:45:56','2026-07-16 02:45:56'),
(2,'editor','web','2026-07-16 02:45:56','2026-07-16 02:45:56'),
(3,'writer','web','2026-07-16 02:45:56','2026-07-16 02:45:56');
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
('9Ej8dcYv88qof9Eu9BFKFapmWdpGGbMLoQxPQK2U',2,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJRRjFpQXNzVTlaZEo1NFpJTG50RkZ1bmlPaUNDbXBHVHY4SnBBa280IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cL2Ntcy15c2JoLnRlc3RcL2FydGljbGVcL3ByZXZpZXdcL3Byb2dyYW0ta2VzZWhhdGFuLWlidS1hbmFrLWtpYSIsInJvdXRlIjoiYXJ0aWNsZS5wcmV2aWV3In0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfSwibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiOjJ9',1784534748),
('qjY1D0PZCEiRGz0VGvDQsdPsIqQkINRLJRfEC0nC',2,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJjREFCUkh4NHZScXBmUTJvSVpnYmhIb0dhdnZYS21zTDVFZjNndm5NIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cL2Ntcy15c2JoLnRlc3RcL2FydGljbGVcL3dyaXRlIiwicm91dGUiOiJhcnRpY2xlLmVkaXRvciJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX0sImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjoyfQ==',1784186177);
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tags`
--

DROP TABLE IF EXISTS `tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tags` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tags_slug_unique` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tags`
--

LOCK TABLES `tags` WRITE;
/*!40000 ALTER TABLE `tags` DISABLE KEYS */;
INSERT INTO `tags` VALUES
(1,'Nutrisi','nutrisi','2026-07-16 02:45:56','2026-07-16 02:45:56'),
(2,'Lansia','lansia','2026-07-16 02:45:56','2026-07-16 02:45:56'),
(3,'Posyandu','posyandu','2026-07-16 02:45:56','2026-07-16 02:45:56'),
(4,'Kota Jayapura','kota-jayapura','2026-07-16 02:45:56','2026-07-16 02:45:56'),
(5,'Kab. Jayapura','kab-jayapura','2026-07-16 02:45:56','2026-07-16 02:45:56'),
(6,'Kuda Menjangan','kuda-menjangan','2026-07-16 02:45:56','2026-07-16 02:45:56'),
(7,'Aku Papua','aku-papua','2026-07-16 02:45:56','2026-07-16 02:45:56'),
(8,'Edo Kondologit','edo-kondologit','2026-07-16 02:45:56','2026-07-16 02:45:56');
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
  `email` varchar(255) NOT NULL,
  `job_title` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `two_factor_secret` text DEFAULT NULL,
  `two_factor_recovery_codes` text DEFAULT NULL,
  `two_factor_confirmed_at` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  UNIQUE KEY `users_job_title_unique` (`job_title`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES
(1,'Test User','test@example.com','Gembel','2026-07-16 02:45:55','$2y$12$6p5xSCyjzu5WPmbs4qGx6OU.aC5Y9PPBM4CYcot.a62eHCj62V/fy',NULL,NULL,NULL,'0Uj2UalzV8','2026-07-16 02:45:55','2026-07-16 02:45:55'),
(2,'Alfrida Pabasi','midar@mail.com','Ketua Yayasan','2026-07-16 02:45:55','$2y$12$Jtm5DLF0xsWlxT.feKRXieejTCAY84bS/2RtLn8F3TAak/8/vUooK',NULL,NULL,NULL,'6fAF8zJd1C','2026-07-16 02:45:55','2026-07-16 02:45:55'),
(3,'ELizabeth Kristiani','liza@mail.com','Bendahara','2026-07-16 02:45:55','$2y$12$CFtnPCr6HxVEhKDXcuq1tedC0o7hguhdrtU3W/y2hE0Be93mmEmMi',NULL,NULL,NULL,'Oua5sVT5Tu','2026-07-16 02:45:55','2026-07-16 02:45:55'),
(4,'Ruth Charlota Yakoba Fouw','utha@example.com','Program Officer Kesehatan Ibu & Anak','2026-07-16 02:45:56','$2y$12$clA6b2mUCH05EVhskWcl1erA5jrtiG1kKfkd5Pv9PRgx5FOJ9M0Wi',NULL,NULL,NULL,'uE6OJCKMtw','2026-07-16 02:45:56','2026-07-16 02:45:56'),
(5,'Leon Dolfus Mangonto','leon@mail.com','Program Officer KIA','2026-07-16 02:45:56','$2y$12$uo1Cgpk1BPXwz1gRnITgFeU7s/ZxhcYqqBCsEsHIa6/1QWAXtcAC6',NULL,NULL,NULL,'YsLNtp1dBm','2026-07-16 02:45:56','2026-07-16 02:45:56');
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

-- Dump completed on 2026-07-20 20:56:44
