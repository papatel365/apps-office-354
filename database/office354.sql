-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 21, 2026 at 02:23 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `office354`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` char(36) NOT NULL,
  `tenant_id` bigint(20) UNSIGNED DEFAULT NULL,
  `subject_type` varchar(255) DEFAULT NULL,
  `subject_id` bigint(20) UNSIGNED DEFAULT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `log_name` varchar(100) DEFAULT NULL,
  `description` varchar(255) NOT NULL,
  `event` varchar(50) DEFAULT NULL,
  `properties` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`properties`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `uuid`, `tenant_id`, `subject_type`, `subject_id`, `user_id`, `log_name`, `description`, `event`, `properties`, `ip_address`, `user_agent`, `created_at`) VALUES
(262, '8f991a36-a5a5-4e98-8679-4fd7e6d2353c', NULL, 'App\\Models\\Backup', 10, 33, 'backup', 'Backup file berhasil dibuat', 'success', '{\"type\":\"success\",\"backup_id\":10,\"backup_type\":\"file\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 11:16:47'),
(263, '05d6dc0d-0d0b-43bc-b640-581a49baf182', NULL, 'App\\Modules\\System\\Models\\User', 33, 33, 'auth', 'Logged out', 'logout', '[]', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 11:17:34'),
(264, '2d7fe083-8d3b-4ef3-b8bc-8236f8da4338', NULL, 'App\\Modules\\System\\Models\\User', 37, NULL, 'auth', 'Logged in', 'login', '[]', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 11:17:42');

-- --------------------------------------------------------

--
-- Table structure for table `addresses`
--

CREATE TABLE `addresses` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` char(36) NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `addressable_type` varchar(255) NOT NULL,
  `addressable_id` bigint(20) UNSIGNED NOT NULL,
  `type` enum('billing','shipping','office','home','other') NOT NULL DEFAULT 'office',
  `street` varchar(255) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `country_code` varchar(2) DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `api_keys`
--

CREATE TABLE `api_keys` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `key` varchar(255) NOT NULL,
  `abilities` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`abilities`)),
  `expires_at` timestamp NULL DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attachments`
--

CREATE TABLE `attachments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` char(36) NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `attachable_type` varchar(255) NOT NULL,
  `attachable_id` bigint(20) UNSIGNED NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_type` varchar(100) DEFAULT NULL,
  `file_size` bigint(20) NOT NULL DEFAULT 0,
  `disk` varchar(50) NOT NULL DEFAULT 'local',
  `uploaded_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attendances`
--

CREATE TABLE `attendances` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `employee_id` bigint(20) UNSIGNED DEFAULT NULL,
  `date` date NOT NULL,
  `check_in` timestamp NULL DEFAULT NULL,
  `check_in_time` timestamp NULL DEFAULT NULL,
  `check_out` timestamp NULL DEFAULT NULL,
  `check_out_time` timestamp NULL DEFAULT NULL,
  `check_in_photo` varchar(255) DEFAULT NULL,
  `check_out_photo` varchar(255) DEFAULT NULL,
  `check_in_latitude` decimal(10,8) DEFAULT NULL,
  `check_in_longitude` decimal(11,8) DEFAULT NULL,
  `check_in_address` varchar(500) DEFAULT NULL,
  `check_in_gps_accuracy` decimal(8,2) DEFAULT NULL,
  `check_out_latitude` decimal(10,8) DEFAULT NULL,
  `check_out_longitude` decimal(11,8) DEFAULT NULL,
  `check_out_address` varchar(500) DEFAULT NULL,
  `check_out_gps_accuracy` decimal(8,2) DEFAULT NULL,
  `shift_id` bigint(20) UNSIGNED DEFAULT NULL,
  `shift_name` varchar(255) DEFAULT NULL,
  `shift_start` timestamp NULL DEFAULT NULL,
  `shift_end` timestamp NULL DEFAULT NULL,
  `late_minutes` int(11) NOT NULL DEFAULT 0,
  `early_leave_minutes` int(11) NOT NULL DEFAULT 0,
  `overtime_minutes` int(11) NOT NULL DEFAULT 0,
  `working_hours` decimal(8,2) DEFAULT NULL,
  `is_face_verified` tinyint(1) NOT NULL DEFAULT 0,
  `is_location_verified` tinyint(1) NOT NULL DEFAULT 0,
  `is_suspicious` tinyint(1) NOT NULL DEFAULT 0,
  `suspicious_reasons` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`suspicious_reasons`)),
  `check_in_ip` varchar(50) DEFAULT NULL,
  `check_in_device` varchar(255) DEFAULT NULL,
  `check_in_browser` varchar(255) DEFAULT NULL,
  `check_in_os` varchar(50) DEFAULT NULL,
  `check_in_timezone` varchar(50) DEFAULT NULL,
  `check_in_timezone_name` varchar(20) DEFAULT NULL,
  `check_in_timezone_offset` varchar(10) DEFAULT NULL,
  `check_in_province` varchar(100) DEFAULT NULL,
  `check_in_city` varchar(100) DEFAULT NULL,
  `check_out_ip` varchar(50) DEFAULT NULL,
  `check_out_device` varchar(255) DEFAULT NULL,
  `check_out_browser` varchar(255) DEFAULT NULL,
  `check_out_os` varchar(50) DEFAULT NULL,
  `check_out_timezone` varchar(50) DEFAULT NULL,
  `check_out_timezone_offset` varchar(10) DEFAULT NULL,
  `check_out_province` varchar(100) DEFAULT NULL,
  `check_out_city` varchar(100) DEFAULT NULL,
  `check_out_timezone_name` varchar(20) DEFAULT NULL,
  `approved_by` bigint(20) UNSIGNED DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `approval_notes` text DEFAULT NULL,
  `status` enum('present','absent','late','half_day') NOT NULL DEFAULT 'present',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `placement_id` bigint(20) UNSIGNED DEFAULT NULL,
  `distance_meters` decimal(10,2) DEFAULT NULL,
  `is_outside_radius` tinyint(1) NOT NULL DEFAULT 0,
  `face_verification_score` decimal(5,4) DEFAULT NULL,
  `face_landmarks` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`face_landmarks`)),
  `attendance_location_name` varchar(200) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attendance_histories`
--

CREATE TABLE `attendance_histories` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `attendance_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `action` varchar(50) NOT NULL,
  `old_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_data`)),
  `new_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_data`)),
  `reason` varchar(255) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `device` varchar(100) DEFAULT NULL,
  `browser` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attendance_requests`
--

CREATE TABLE `attendance_requests` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `employee_id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `type` enum('sick','permission') NOT NULL,
  `date` date NOT NULL,
  `reason` text DEFAULT NULL,
  `document_path` varchar(255) DEFAULT NULL,
  `attachment_path` varchar(255) DEFAULT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `approved_by` bigint(20) UNSIGNED DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `rejected_by` bigint(20) UNSIGNED DEFAULT NULL,
  `rejected_at` timestamp NULL DEFAULT NULL,
  `reject_reason` text DEFAULT NULL,
  `approval_notes` text DEFAULT NULL,
  `ip_address` varchar(255) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `browser` varchar(255) DEFAULT NULL,
  `os` varchar(255) DEFAULT NULL,
  `device` varchar(255) DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `location_address` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attendance_settings`
--

CREATE TABLE `attendance_settings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` char(36) NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(200) NOT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `radius_meters` int(11) NOT NULL DEFAULT 100,
  `require_face_verification` tinyint(1) NOT NULL DEFAULT 1,
  `require_gps` tinyint(1) NOT NULL DEFAULT 1,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` char(36) NOT NULL,
  `tenant_id` bigint(20) UNSIGNED DEFAULT NULL,
  `auditable_type` varchar(255) DEFAULT NULL,
  `auditable_id` bigint(20) UNSIGNED DEFAULT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `user_type` varchar(255) DEFAULT NULL,
  `action` varchar(50) NOT NULL,
  `model_type` varchar(255) DEFAULT NULL,
  `model_id` bigint(20) UNSIGNED DEFAULT NULL,
  `model_label` varchar(255) DEFAULT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `changes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`changes`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `device` varchar(50) DEFAULT NULL,
  `browser` varchar(100) DEFAULT NULL,
  `browser_version` varchar(50) DEFAULT NULL,
  `os` varchar(50) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `category` varchar(50) DEFAULT NULL,
  `logged_at` timestamp NULL DEFAULT NULL,
  `url` varchar(500) DEFAULT NULL,
  `method` varchar(10) DEFAULT NULL,
  `request_id` varchar(36) DEFAULT NULL,
  `batch_uuid` varchar(36) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `backups`
--

CREATE TABLE `backups` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` char(36) NOT NULL,
  `company_id` bigint(20) UNSIGNED DEFAULT NULL,
  `backup_type` enum('database','file','full') NOT NULL,
  `filename` varchar(255) NOT NULL,
  `filesize` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `checksum` varchar(64) DEFAULT NULL,
  `disk` varchar(50) NOT NULL DEFAULT 'local',
  `path` varchar(255) DEFAULT NULL,
  `status` enum('pending','in_progress','completed','failed','restoring','restored') NOT NULL DEFAULT 'pending',
  `is_scheduled` tinyint(1) NOT NULL DEFAULT 0,
  `schedule_type` enum('manual','daily','weekly','monthly') DEFAULT NULL,
  `started_at` timestamp NULL DEFAULT NULL,
  `finished_at` timestamp NULL DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `backup_settings`
--

CREATE TABLE `backup_settings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` char(36) NOT NULL,
  `company_id` bigint(20) UNSIGNED DEFAULT NULL,
  `schedule_type` enum('manual','daily','weekly','monthly') NOT NULL DEFAULT 'manual',
  `backup_time` time NOT NULL DEFAULT '01:00:00',
  `backup_day` varchar(10) DEFAULT NULL,
  `retention_count` int(11) NOT NULL DEFAULT 7,
  `disk` varchar(50) NOT NULL DEFAULT 'local',
  `compress` tinyint(1) NOT NULL DEFAULT 1,
  `is_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `last_backup_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `updated_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cache`
--

INSERT INTO `cache` (`key`, `value`, `expiration`) VALUES
('office354-cache-5c785c036466adea360111aa28563bfd556b5fba', 'i:1;', 1787314722),
('office354-cache-5c785c036466adea360111aa28563bfd556b5fba:timer', 'i:1787314722;', 1787314722),
('office354-cache-guest_company_logo_favicon', 'a:4:{s:8:\"logo_url\";N;s:11:\"favicon_url\";N;s:9:\"logo_path\";N;s:12:\"favicon_path\";N;}', 1787318254),
('office354-cache-sidebar_menu_config_v8', 'a:4:{i:0;a:10:{s:3:\"key\";s:9:\"dashboard\";s:5:\"label\";s:7:\"Beranda\";s:4:\"icon\";s:7:\"fa-home\";s:10:\"icon_class\";s:16:\"fa-solid fa-home\";s:4:\"type\";s:4:\"item\";s:5:\"route\";s:9:\"dashboard\";s:14:\"permission_key\";s:17:\"sidebar.dashboard\";s:5:\"group\";N;s:10:\"is_visible\";b:1;s:8:\"children\";a:0:{}}i:1;a:10:{s:3:\"key\";s:14:\"projects_tasks\";s:5:\"label\";s:14:\"Proyek & Tugas\";s:4:\"icon\";s:12:\"fa-briefcase\";s:10:\"icon_class\";s:21:\"fa-solid fa-briefcase\";s:4:\"type\";s:5:\"group\";s:5:\"route\";N;s:14:\"permission_key\";N;s:5:\"group\";s:14:\"projects_tasks\";s:10:\"is_visible\";b:0;s:8:\"children\";a:0:{}}i:2;a:10:{s:3:\"key\";s:5:\"staff\";s:5:\"label\";s:12:\"Administrasi\";s:4:\"icon\";s:8:\"fa-users\";s:10:\"icon_class\";s:17:\"fa-solid fa-users\";s:4:\"type\";s:5:\"group\";s:5:\"route\";N;s:14:\"permission_key\";N;s:5:\"group\";s:5:\"staff\";s:10:\"is_visible\";b:1;s:8:\"children\";a:4:{i:0;a:10:{s:3:\"key\";s:15:\"staff_dashboard\";s:5:\"label\";s:5:\"Staff\";s:4:\"icon\";s:8:\"fa-gauge\";s:10:\"icon_class\";s:17:\"fa-solid fa-gauge\";s:4:\"type\";s:4:\"item\";s:5:\"route\";s:22:\"administrasi.dashboard\";s:14:\"permission_key\";s:23:\"sidebar.staff_dashboard\";s:5:\"group\";s:5:\"staff\";s:10:\"is_visible\";b:0;s:8:\"children\";a:0:{}}i:1;a:10:{s:3:\"key\";s:9:\"employees\";s:5:\"label\";s:13:\"Data Karyawan\";s:4:\"icon\";s:7:\"fa-user\";s:10:\"icon_class\";s:16:\"fa-solid fa-user\";s:4:\"type\";s:4:\"item\";s:5:\"route\";s:32:\"administrasi.data_karyawan.index\";s:14:\"permission_key\";s:17:\"sidebar.employees\";s:5:\"group\";s:5:\"staff\";s:10:\"is_visible\";b:1;s:8:\"children\";a:0:{}}i:2;a:10:{s:3:\"key\";s:11:\"attendances\";s:5:\"label\";s:7:\"Absensi\";s:4:\"icon\";s:17:\"fa-calendar-check\";s:10:\"icon_class\";s:26:\"fa-solid fa-calendar-check\";s:4:\"type\";s:4:\"item\";s:5:\"route\";s:24:\"administrasi.absen.index\";s:14:\"permission_key\";s:19:\"sidebar.attendances\";s:5:\"group\";s:5:\"staff\";s:10:\"is_visible\";b:1;s:8:\"children\";a:0:{}}i:3;a:10:{s:3:\"key\";s:13:\"staff_reports\";s:5:\"label\";s:7:\"Laporan\";s:4:\"icon\";s:12:\"fa-chart-bar\";s:10:\"icon_class\";s:21:\"fa-solid fa-chart-bar\";s:4:\"type\";s:4:\"item\";s:5:\"route\";s:26:\"administrasi.laporan.index\";s:14:\"permission_key\";s:21:\"sidebar.staff_reports\";s:5:\"group\";s:5:\"staff\";s:10:\"is_visible\";b:1;s:8:\"children\";a:0:{}}}}i:3;a:11:{s:3:\"key\";s:8:\"atur_crm\";s:5:\"label\";s:10:\"Pengaturan\";s:4:\"icon\";s:7:\"fa-cogs\";s:10:\"icon_class\";s:16:\"fa-solid fa-cogs\";s:4:\"type\";s:5:\"group\";s:5:\"route\";s:8:\"atur_crm\";s:14:\"permission_key\";N;s:5:\"group\";s:8:\"atur_crm\";s:10:\"is_visible\";b:1;s:11:\"redirect_to\";s:21:\"pengaturan.umum.index\";s:8:\"children\";a:3:{i:0;a:10:{s:3:\"key\";s:6:\"backup\";s:5:\"label\";s:6:\"Backup\";s:4:\"icon\";s:11:\"fa-download\";s:10:\"icon_class\";s:20:\"fa-solid fa-download\";s:4:\"type\";s:4:\"item\";s:5:\"route\";s:23:\"pengaturan.backup.index\";s:14:\"permission_key\";s:14:\"sidebar.backup\";s:5:\"group\";s:8:\"atur_crm\";s:10:\"is_visible\";b:1;s:8:\"children\";a:0:{}}i:1;a:10:{s:3:\"key\";s:9:\"hak_akses\";s:5:\"label\";s:9:\"Hak Akses\";s:4:\"icon\";s:14:\"fa-user-shield\";s:10:\"icon_class\";s:23:\"fa-solid fa-user-shield\";s:4:\"type\";s:4:\"item\";s:5:\"route\";s:26:\"pengaturan.hak_akses.index\";s:14:\"permission_key\";s:17:\"sidebar.hak_akses\";s:5:\"group\";s:8:\"atur_crm\";s:10:\"is_visible\";b:1;s:8:\"children\";a:0:{}}i:2;a:10:{s:3:\"key\";s:16:\"master_data_umum\";s:5:\"label\";s:4:\"Umum\";s:4:\"icon\";s:12:\"fa-sliders-h\";s:10:\"icon_class\";s:21:\"fa-solid fa-sliders-h\";s:4:\"type\";s:4:\"item\";s:5:\"route\";s:21:\"pengaturan.umum.index\";s:14:\"permission_key\";s:24:\"sidebar.master_data_umum\";s:5:\"group\";s:8:\"atur_crm\";s:10:\"is_visible\";b:1;s:8:\"children\";a:0:{}}}}}', 1787388778),
('office354-cache-user_permissions_37', 'a:8:{s:8:\"projects\";a:6:{s:9:\"scope_own\";b:0;s:12:\"scope_global\";b:0;s:8:\"can_view\";b:0;s:10:\"can_create\";b:0;s:10:\"can_update\";b:0;s:10:\"can_delete\";b:0;}s:5:\"tasks\";a:6:{s:9:\"scope_own\";b:0;s:12:\"scope_global\";b:0;s:8:\"can_view\";b:0;s:10:\"can_create\";b:0;s:10:\"can_update\";b:0;s:10:\"can_delete\";b:0;}s:9:\"employees\";a:6:{s:9:\"scope_own\";b:0;s:12:\"scope_global\";b:0;s:8:\"can_view\";b:0;s:10:\"can_create\";b:0;s:10:\"can_update\";b:0;s:10:\"can_delete\";b:0;}s:11:\"attendances\";a:6:{s:9:\"scope_own\";b:1;s:12:\"scope_global\";b:0;s:8:\"can_view\";b:1;s:10:\"can_create\";b:0;s:10:\"can_update\";b:0;s:10:\"can_delete\";b:0;}s:13:\"staff_reports\";a:6:{s:9:\"scope_own\";b:0;s:12:\"scope_global\";b:0;s:8:\"can_view\";b:0;s:10:\"can_create\";b:0;s:10:\"can_update\";b:0;s:10:\"can_delete\";b:0;}s:6:\"backup\";a:6:{s:9:\"scope_own\";b:0;s:12:\"scope_global\";b:0;s:8:\"can_view\";b:0;s:10:\"can_create\";b:0;s:10:\"can_update\";b:0;s:10:\"can_delete\";b:0;}s:9:\"hak_akses\";a:6:{s:9:\"scope_own\";b:0;s:12:\"scope_global\";b:0;s:8:\"can_view\";b:0;s:10:\"can_create\";b:0;s:10:\"can_update\";b:0;s:10:\"can_delete\";b:0;}s:16:\"master_data_umum\";a:6:{s:9:\"scope_own\";b:0;s:12:\"scope_global\";b:0;s:8:\"can_view\";b:0;s:10:\"can_create\";b:0;s:10:\"can_update\";b:0;s:10:\"can_delete\";b:0;}}', 1787318247);

-- --------------------------------------------------------

--
-- Table structure for table `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `clients`
--

CREATE TABLE `clients` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` char(36) NOT NULL,
  `tenant_id` bigint(20) UNSIGNED DEFAULT NULL,
  `company_id` bigint(20) UNSIGNED DEFAULT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `company_name` varchar(255) DEFAULT NULL,
  `vat_number` varchar(50) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `industry` varchar(100) DEFAULT NULL,
  `company_size` varchar(50) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `updated_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `comments`
--

CREATE TABLE `comments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` char(36) NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `commentable_type` varchar(255) NOT NULL,
  `commentable_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `content` text NOT NULL,
  `parent_id` bigint(20) UNSIGNED DEFAULT NULL,
  `is_internal` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `companies`
--

CREATE TABLE `companies` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` char(36) NOT NULL,
  `name` varchar(255) NOT NULL,
  `short_name` varchar(100) DEFAULT NULL,
  `tagline` varchar(255) DEFAULT NULL,
  `footer_text` varchar(500) DEFAULT NULL,
  `slug` varchar(255) NOT NULL,
  `address` text DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `npwp` varchar(32) DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `favicon` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `crm_version` varchar(50) DEFAULT NULL,
  `db_version` varchar(50) DEFAULT NULL,
  `storage_used_bytes` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `last_login_at` timestamp NULL DEFAULT NULL,
  `server_ip` varchar(255) DEFAULT NULL,
  `server_hostname` varchar(255) DEFAULT NULL,
  `timezone` varchar(255) NOT NULL DEFAULT 'Asia/Jakarta',
  `locale` varchar(255) NOT NULL DEFAULT 'id',
  `last_payment_at` timestamp NULL DEFAULT NULL,
  `max_users` int(10) UNSIGNED DEFAULT NULL,
  `max_storage_gb` int(10) UNSIGNED DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `system_info` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`system_info`)),
  `preferences` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`preferences`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `company_notifications`
--

CREATE TABLE `company_notifications` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` char(36) NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` varchar(500) NOT NULL,
  `module` varchar(100) NOT NULL,
  `action` varchar(50) NOT NULL,
  `severity` varchar(20) NOT NULL DEFAULT 'info',
  `notifiable_type` varchar(200) NOT NULL,
  `notifiable_id` bigint(20) UNSIGNED DEFAULT NULL,
  `notifiable_label` varchar(255) DEFAULT NULL,
  `action_url` varchar(500) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `read_at` timestamp NULL DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `company_subscriptions`
--

CREATE TABLE `company_subscriptions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `plan_id` bigint(20) UNSIGNED NOT NULL,
  `subscription_code` varchar(255) NOT NULL,
  `billing_cycle` enum('monthly','yearly') NOT NULL DEFAULT 'monthly',
  `price` decimal(12,2) NOT NULL,
  `discount_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(12,2) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `next_billing_date` date DEFAULT NULL,
  `status` enum('active','expired','cancelled','pending','trial') NOT NULL DEFAULT 'active',
  `trial_ends_at` timestamp NULL DEFAULT NULL,
  `auto_renew` tinyint(1) NOT NULL DEFAULT 1,
  `is_lifetime` tinyint(1) NOT NULL DEFAULT 0,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `countries`
--

CREATE TABLE `countries` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `alpha_2` varchar(2) NOT NULL,
  `alpha_3` varchar(3) NOT NULL,
  `numeric_code` varchar(3) DEFAULT NULL,
  `capital` varchar(100) DEFAULT NULL,
  `region` varchar(100) DEFAULT NULL,
  `subregion` varchar(100) DEFAULT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `credit_notes`
--

CREATE TABLE `credit_notes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` char(36) NOT NULL,
  `tenant_id` bigint(20) UNSIGNED DEFAULT NULL,
  `company_id` bigint(20) UNSIGNED DEFAULT NULL,
  `credit_note_number` varchar(50) NOT NULL,
  `client_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `contact_id` bigint(20) UNSIGNED DEFAULT NULL,
  `invoice_id` bigint(20) UNSIGNED DEFAULT NULL,
  `status` enum('draft','applied','refunded') NOT NULL DEFAULT 'draft',
  `credit_note_date` date NOT NULL,
  `amount` decimal(20,4) NOT NULL,
  `applied_amount` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `remaining_amount` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `reason` text DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `updated_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `crm_module_permissions`
--

CREATE TABLE `crm_module_permissions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `module` varchar(50) NOT NULL,
  `permission_key` varchar(50) NOT NULL,
  `allowed` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `crm_user_permissions`
--

CREATE TABLE `crm_user_permissions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `module` varchar(50) NOT NULL,
  `scope` enum('own','global') NOT NULL DEFAULT 'own',
  `can_view` tinyint(1) NOT NULL DEFAULT 1,
  `can_create` tinyint(1) NOT NULL DEFAULT 0,
  `can_update` tinyint(1) NOT NULL DEFAULT 0,
  `can_delete` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `crm_user_permissions_v2`
--

CREATE TABLE `crm_user_permissions_v2` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `module` varchar(50) NOT NULL,
  `scope_own` tinyint(1) NOT NULL DEFAULT 0,
  `scope_global` tinyint(1) NOT NULL DEFAULT 0,
  `can_view` tinyint(1) NOT NULL DEFAULT 0,
  `can_create` tinyint(1) NOT NULL DEFAULT 0,
  `can_update` tinyint(1) NOT NULL DEFAULT 0,
  `can_delete` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `currencies`
--

CREATE TABLE `currencies` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(3) NOT NULL,
  `symbol` varchar(10) NOT NULL,
  `symbol_position` varchar(10) NOT NULL DEFAULT 'before',
  `exchange_rate` decimal(18,8) NOT NULL DEFAULT 1.00000000,
  `exchange_rate_updated_at` decimal(8,2) DEFAULT NULL,
  `decimal_places` int(11) NOT NULL DEFAULT 2,
  `thousand_separator` varchar(5) NOT NULL DEFAULT ',',
  `decimal_separator` varchar(5) NOT NULL DEFAULT '.',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(20) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `parent_id` bigint(20) UNSIGNED DEFAULT NULL,
  `head_id` bigint(20) UNSIGNED DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `divisions`
--

CREATE TABLE `divisions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED DEFAULT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `department_id` bigint(20) UNSIGNED DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(20) DEFAULT NULL,
  `slug` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `sidebar_permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`sidebar_permissions`)),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employee_account_link_requests`
--

CREATE TABLE `employee_account_link_requests` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `employee_profile_id` bigint(20) UNSIGNED DEFAULT NULL,
  `status` enum('pending','approved','rejected','cancelled') NOT NULL DEFAULT 'pending',
  `processed_by` bigint(20) UNSIGNED DEFAULT NULL,
  `processed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employee_documents`
--

CREATE TABLE `employee_documents` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `employee_id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `document_type` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_size` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `mime_type` varchar(255) DEFAULT NULL,
  `issued_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `uploaded_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employee_placements`
--

CREATE TABLE `employee_placements` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` char(36) NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `name` varchar(200) NOT NULL,
  `code` varchar(50) DEFAULT NULL,
  `type` varchar(50) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `province` varchar(100) DEFAULT NULL,
  `pic_name` varchar(100) DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `radius_meters` int(11) NOT NULL DEFAULT 100,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employee_profiles`
--

CREATE TABLE `employee_profiles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `department_id` bigint(20) UNSIGNED DEFAULT NULL,
  `position_id` bigint(20) UNSIGNED DEFAULT NULL,
  `employee_type_id` bigint(20) UNSIGNED DEFAULT NULL,
  `supervisor_id` bigint(20) UNSIGNED DEFAULT NULL,
  `division_id` bigint(20) UNSIGNED DEFAULT NULL,
  `employee_number` varchar(255) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `nick_name` varchar(255) DEFAULT NULL,
  `gender` enum('male','female') DEFAULT NULL,
  `birth_date` date DEFAULT NULL,
  `birth_place` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `mobile` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `province` varchar(255) DEFAULT NULL,
  `postal_code` varchar(10) DEFAULT NULL,
  `ktp_number` varchar(20) DEFAULT NULL,
  `ktp_address` varchar(255) DEFAULT NULL,
  `npwp_number` varchar(20) DEFAULT NULL,
  `bpjs_kesehatan` varchar(20) DEFAULT NULL,
  `bpjs_number` varchar(20) DEFAULT NULL,
  `bpjs_ketenagakerjaan` varchar(20) DEFAULT NULL,
  `blood_type` varchar(5) DEFAULT NULL,
  `emergency_contact_name` varchar(255) DEFAULT NULL,
  `emergency_contact_phone` varchar(20) DEFAULT NULL,
  `emergency_contact_relation` varchar(255) DEFAULT NULL,
  `bank_name` varchar(255) DEFAULT NULL,
  `bank_account_number` varchar(30) DEFAULT NULL,
  `bank_account_name` varchar(100) DEFAULT NULL,
  `bank_account_holder` varchar(255) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `signature` varchar(255) DEFAULT NULL,
  `join_date` date NOT NULL,
  `probation_end_date` date DEFAULT NULL,
  `contract_end_date` date DEFAULT NULL,
  `contract_end` date DEFAULT NULL,
  `employment_status` enum('permanent','contract','probation','intern','outsource') NOT NULL DEFAULT 'probation',
  `marital_status` enum('single','married','divorced','widowed') DEFAULT NULL,
  `punya_anak` tinyint(1) DEFAULT NULL,
  `jumlah_anak` tinyint(3) UNSIGNED DEFAULT NULL,
  `religion` varchar(20) DEFAULT NULL,
  `education` varchar(255) DEFAULT NULL,
  `institution` varchar(255) DEFAULT NULL,
  `graduation_year` varchar(4) DEFAULT NULL,
  `previous_company` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `resign_date` date DEFAULT NULL,
  `resign_reason` text DEFAULT NULL,
  `employment_type` enum('permanent','contract','probation','part_time') NOT NULL DEFAULT 'permanent',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `placement_id` bigint(20) UNSIGNED DEFAULT NULL,
  `placement_name` varchar(200) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employee_salary_components`
--

CREATE TABLE `employee_salary_components` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `salary_id` bigint(20) UNSIGNED NOT NULL,
  `type` enum('allowance','deduction') NOT NULL,
  `name` varchar(255) NOT NULL,
  `calculation_type` enum('fixed','percentage') NOT NULL DEFAULT 'fixed',
  `amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employee_sidebar_permissions`
--

CREATE TABLE `employee_sidebar_permissions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `employee_id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED DEFAULT NULL,
  `menu_key` varchar(100) NOT NULL,
  `can_view` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employee_types`
--

CREATE TABLE `employee_types` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `color` varchar(20) NOT NULL DEFAULT '#6B7280',
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `estimates`
--

CREATE TABLE `estimates` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` char(36) NOT NULL,
  `tenant_id` bigint(20) UNSIGNED DEFAULT NULL,
  `company_id` bigint(20) UNSIGNED DEFAULT NULL,
  `estimate_number` varchar(50) NOT NULL,
  `client_id` bigint(20) UNSIGNED NOT NULL,
  `contact_id` bigint(20) UNSIGNED DEFAULT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `proposal_id` bigint(20) UNSIGNED DEFAULT NULL,
  `subject` varchar(255) NOT NULL,
  `content` longtext DEFAULT NULL,
  `status` enum('draft','sent','accepted','rejected','revised','expired') NOT NULL DEFAULT 'draft',
  `currency_code` varchar(3) NOT NULL DEFAULT 'IDR',
  `sub_total` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `discount_percent` decimal(5,2) NOT NULL DEFAULT 0.00,
  `discount_amount` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `tax_percent` decimal(5,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `total` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `valid_until` date DEFAULT NULL,
  `accepted_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `updated_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `estimate_items`
--

CREATE TABLE `estimate_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED DEFAULT NULL,
  `company_id` bigint(20) UNSIGNED DEFAULT NULL,
  `estimate_id` bigint(20) UNSIGNED NOT NULL,
  `item_id` bigint(20) UNSIGNED DEFAULT NULL,
  `description` varchar(500) NOT NULL,
  `quantity` decimal(15,4) NOT NULL DEFAULT 1.0000,
  `unit_price` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `discount_percent` decimal(5,2) NOT NULL DEFAULT 0.00,
  `tax_percent` decimal(5,2) NOT NULL DEFAULT 0.00,
  `amount` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `estimate_requests`
--

CREATE TABLE `estimate_requests` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` char(36) NOT NULL,
  `tenant_id` bigint(20) UNSIGNED DEFAULT NULL,
  `company_id` bigint(20) UNSIGNED DEFAULT NULL,
  `request_number` varchar(50) NOT NULL,
  `client_name` varchar(255) DEFAULT NULL,
  `client_email` varchar(255) DEFAULT NULL,
  `client_phone` varchar(20) DEFAULT NULL,
  `subject` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('new','in_progress','converted','declined') NOT NULL DEFAULT 'new',
  `priority` enum('low','medium','high') NOT NULL DEFAULT 'medium',
  `lead_id` bigint(20) UNSIGNED DEFAULT NULL,
  `assigned_to` bigint(20) UNSIGNED DEFAULT NULL,
  `converted_to_estimate_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `updated_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `invoices`
--

CREATE TABLE `invoices` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` char(36) NOT NULL,
  `tenant_id` bigint(20) UNSIGNED DEFAULT NULL,
  `company_id` bigint(20) UNSIGNED DEFAULT NULL,
  `invoice_number` varchar(50) NOT NULL,
  `linked_type` enum('proposal','estimate','manual') DEFAULT NULL,
  `linked_id` bigint(20) UNSIGNED DEFAULT NULL,
  `estimate_id` bigint(20) UNSIGNED DEFAULT NULL,
  `proposal_id` bigint(20) UNSIGNED DEFAULT NULL,
  `client_id` bigint(20) UNSIGNED NOT NULL,
  `contact_id` bigint(20) UNSIGNED DEFAULT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `subject` varchar(255) NOT NULL,
  `status` enum('draft','sent','viewed','partial','paid','overdue','cancelled') NOT NULL DEFAULT 'draft',
  `invoice_date` date NOT NULL,
  `due_date` date NOT NULL,
  `currency_code` varchar(3) NOT NULL DEFAULT 'IDR',
  `sub_total` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `discount_percent` decimal(5,2) NOT NULL DEFAULT 0.00,
  `discount_amount` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `tax_percent` decimal(5,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `total` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `paid_amount` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `credit_applied` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `remaining_amount` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `sent_at` timestamp NULL DEFAULT NULL,
  `viewed_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `updated_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `invoice_items`
--

CREATE TABLE `invoice_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED DEFAULT NULL,
  `company_id` bigint(20) UNSIGNED DEFAULT NULL,
  `invoice_id` bigint(20) UNSIGNED NOT NULL,
  `item_id` bigint(20) UNSIGNED DEFAULT NULL,
  `description` varchar(500) NOT NULL,
  `quantity` decimal(15,4) NOT NULL DEFAULT 1.0000,
  `unit_price` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `discount_percent` decimal(5,2) NOT NULL DEFAULT 0.00,
  `tax_percent` decimal(5,2) NOT NULL DEFAULT 0.00,
  `amount` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `items`
--

CREATE TABLE `items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` char(36) NOT NULL,
  `tenant_id` bigint(20) UNSIGNED DEFAULT NULL,
  `company_id` bigint(20) UNSIGNED DEFAULT NULL,
  `sku` varchar(100) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `unit` varchar(20) NOT NULL DEFAULT 'unit',
  `unit_price` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `tax_percent` decimal(5,2) NOT NULL DEFAULT 0.00,
  `group_name` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `updated_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) UNSIGNED NOT NULL,
  `reserved_at` int(10) UNSIGNED DEFAULT NULL,
  `available_at` int(10) UNSIGNED NOT NULL,
  `created_at` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_batches`
--

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
  `finished_at` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `knowledge_base`
--

CREATE TABLE `knowledge_base` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` char(36) NOT NULL,
  `tenant_id` bigint(20) UNSIGNED DEFAULT NULL,
  `company_id` bigint(20) UNSIGNED DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `content` text DEFAULT NULL,
  `slug` varchar(255) NOT NULL,
  `category_id` bigint(20) UNSIGNED DEFAULT NULL,
  `status` enum('draft','published','archived') NOT NULL DEFAULT 'draft',
  `featured` tinyint(1) NOT NULL DEFAULT 0,
  `views` int(11) NOT NULL DEFAULT 0,
  `author_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `updated_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `knowledge_base_categories`
--

CREATE TABLE `knowledge_base_categories` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` char(36) NOT NULL,
  `tenant_id` bigint(20) UNSIGNED DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `k_p_i_settings`
--

CREATE TABLE `k_p_i_settings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `position_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `category` varchar(50) DEFAULT NULL,
  `target_value` decimal(10,2) NOT NULL DEFAULT 0.00,
  `target_unit` varchar(20) DEFAULT NULL,
  `min_value` decimal(10,2) NOT NULL DEFAULT 0.00,
  `max_value` decimal(10,2) NOT NULL DEFAULT 100.00,
  `weight` decimal(5,2) NOT NULL DEFAULT 1.00,
  `position_id_sort` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `languages`
--

CREATE TABLE `languages` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `native_name` varchar(100) DEFAULT NULL,
  `code` varchar(10) NOT NULL,
  `locale` varchar(20) DEFAULT NULL,
  `direction` varchar(10) NOT NULL DEFAULT 'ltr',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `leads`
--

CREATE TABLE `leads` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` char(36) NOT NULL,
  `tenant_id` bigint(20) UNSIGNED DEFAULT NULL,
  `company_id` bigint(20) UNSIGNED DEFAULT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `company` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `mobile` varchar(20) DEFAULT NULL,
  `position` varchar(100) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `industry` varchar(100) DEFAULT NULL,
  `source` enum('website','referral','social_media','cold_call','email_campaign','trade_show','other') NOT NULL DEFAULT 'website',
  `status` enum('new','contacted','qualified','proposal','negotiation','won','lost') NOT NULL DEFAULT 'new',
  `priority` enum('low','medium','high') NOT NULL DEFAULT 'medium',
  `notes` text DEFAULT NULL,
  `estimated_value` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `currency_code` varchar(3) NOT NULL DEFAULT 'IDR',
  `last_contacted_at` timestamp NULL DEFAULT NULL,
  `converted_at` timestamp NULL DEFAULT NULL,
  `converted_to_client_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `updated_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `leaves`
--

CREATE TABLE `leaves` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `leave_type_id` bigint(20) UNSIGNED DEFAULT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `leave_type` enum('annual','sick','emergency','maternity','paternity','unpaid') NOT NULL DEFAULT 'annual',
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `total_days` int(11) NOT NULL DEFAULT 0,
  `reason` text DEFAULT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `approved_by` bigint(20) UNSIGNED DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `leave_entitlements`
--

CREATE TABLE `leave_entitlements` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `employee_id` bigint(20) UNSIGNED NOT NULL,
  `leave_type_id` bigint(20) UNSIGNED NOT NULL,
  `year` int(11) NOT NULL DEFAULT 2026,
  `entitled_days` int(11) NOT NULL DEFAULT 0,
  `used_days` int(11) NOT NULL DEFAULT 0,
  `pending_days` int(11) NOT NULL DEFAULT 0,
  `effective_date` date NOT NULL,
  `expired_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `leave_types`
--

CREATE TABLE `leave_types` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(20) NOT NULL,
  `description` text DEFAULT NULL,
  `default_days` int(11) NOT NULL DEFAULT 0,
  `is_paid` tinyint(1) NOT NULL DEFAULT 1,
  `requires_approval` tinyint(1) NOT NULL DEFAULT 1,
  `requires_document` tinyint(1) NOT NULL DEFAULT 0,
  `max_consecutive_days` int(11) DEFAULT NULL,
  `min_advance_days` int(11) NOT NULL DEFAULT 0,
  `can_carry_forward` tinyint(1) NOT NULL DEFAULT 0,
  `carry_forward_days` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `model_has_permissions`
--

CREATE TABLE `model_has_permissions` (
  `permission_id` bigint(20) UNSIGNED NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `model_has_roles`
--

CREATE TABLE `model_has_roles` (
  `role_id` bigint(20) UNSIGNED NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `modules`
--

CREATE TABLE `modules` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `code` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `icon` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `short_description` text DEFAULT NULL,
  `category` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_core` tinyint(1) NOT NULL DEFAULT 0,
  `is_premium` tinyint(1) NOT NULL DEFAULT 1,
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `is_visible` tinyint(1) NOT NULL DEFAULT 1,
  `published_at` timestamp NULL DEFAULT NULL,
  `has_trial` tinyint(1) NOT NULL DEFAULT 1,
  `trial_days` int(11) NOT NULL DEFAULT 14,
  `force_trial` tinyint(1) NOT NULL DEFAULT 0,
  `default_subscription_days` int(11) NOT NULL DEFAULT 365,
  `allow_custom_expiry` tinyint(1) NOT NULL DEFAULT 1,
  `version` varchar(255) NOT NULL DEFAULT '1.0.0',
  `requirements` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`requirements`)),
  `screenshots` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`screenshots`)),
  `documentation_url` varchar(255) DEFAULT NULL,
  `price_monthly` decimal(15,2) DEFAULT NULL,
  `price_yearly` decimal(15,2) DEFAULT NULL,
  `price_lifetime` decimal(15,2) DEFAULT NULL,
  `setup_fee` decimal(15,2) DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `promo` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`promo`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `module_features`
--

CREATE TABLE `module_features` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `module_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `module_transactions`
--

CREATE TABLE `module_transactions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `subscription_id` bigint(20) UNSIGNED DEFAULT NULL,
  `module_id` bigint(20) UNSIGNED NOT NULL,
  `plan_id` bigint(20) UNSIGNED DEFAULT NULL,
  `transaction_code` varchar(255) NOT NULL,
  `duitku_order_id` varchar(255) DEFAULT NULL,
  `duitku_payment_method` varchar(255) DEFAULT NULL,
  `amount` decimal(12,2) NOT NULL,
  `discount_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(12,2) NOT NULL,
  `billing_cycle` enum('monthly','yearly') NOT NULL,
  `status` enum('pending','paid','failed','expired','refunded') NOT NULL DEFAULT 'pending',
  `payment_channel` varchar(255) DEFAULT NULL,
  `gateway` varchar(50) DEFAULT NULL,
  `payment_transaction_id` bigint(20) UNSIGNED DEFAULT NULL,
  `payment_reference` varchar(255) DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` char(36) NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `type` varchar(255) NOT NULL,
  `notifiable_type` varchar(255) NOT NULL,
  `notifiable_id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `body` text DEFAULT NULL,
  `action_url` varchar(500) DEFAULT NULL,
  `action_text` varchar(100) DEFAULT NULL,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`data`)),
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `plans`
--

CREATE TABLE `plans` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `module_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(255) NOT NULL,
  `price_monthly` decimal(12,2) NOT NULL DEFAULT 0.00,
  `price_yearly` decimal(12,2) NOT NULL DEFAULT 0.00,
  `discount_yearly_percent` decimal(5,2) NOT NULL DEFAULT 0.00,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `max_users` int(11) DEFAULT NULL,
  `max_storage_gb` int(11) DEFAULT NULL,
  `features` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`features`)),
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `positions`
--

CREATE TABLE `positions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `department_id` bigint(20) UNSIGNED DEFAULT NULL,
  `division_id` bigint(20) UNSIGNED DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(20) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `level` varchar(50) DEFAULT NULL,
  `parent_id` bigint(20) UNSIGNED DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `recruitments`
--

CREATE TABLE `recruitments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `department_id` bigint(20) UNSIGNED NOT NULL,
  `position_id` bigint(20) UNSIGNED NOT NULL,
  `candidate_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `gender` enum('male','female','other') DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `education` varchar(255) DEFAULT NULL,
  `major` varchar(255) DEFAULT NULL,
  `institution` varchar(255) DEFAULT NULL,
  `experience_years` varchar(255) DEFAULT NULL,
  `current_company` varchar(255) DEFAULT NULL,
  `current_position` varchar(255) DEFAULT NULL,
  `skills` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `source` enum('website','linkedin','jobstreet','referral','direct','headhunter','other') NOT NULL DEFAULT 'website',
  `stage` enum('applied','screening','interview_hr','interview_user','interview_director','offering','hiring','rejected','cancelled') NOT NULL DEFAULT 'applied',
  `rejection_reason` text DEFAULT NULL,
  `expected_salary` decimal(12,2) DEFAULT NULL,
  `offered_salary` decimal(12,2) DEFAULT NULL,
  `interview_date` date DEFAULT NULL,
  `interview_time` time DEFAULT NULL,
  `offer_date` date DEFAULT NULL,
  `hire_date` date DEFAULT NULL,
  `join_date` date DEFAULT NULL,
  `interview_notes` text DEFAULT NULL,
  `offer_notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `retry_logs`
--

CREATE TABLE `retry_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` char(36) NOT NULL,
  `entity_type` varchar(100) NOT NULL,
  `entity_id` bigint(20) UNSIGNED NOT NULL,
  `action` varchar(100) NOT NULL,
  `attempts` int(11) NOT NULL DEFAULT 0,
  `max_attempts` int(11) NOT NULL DEFAULT 5,
  `last_attempt_at` timestamp NULL DEFAULT NULL,
  `next_attempt_at` timestamp NULL DEFAULT NULL,
  `success` tinyint(1) NOT NULL DEFAULT 0,
  `last_error` text DEFAULT NULL,
  `context` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`context`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `role_has_permissions`
--

CREATE TABLE `role_has_permissions` (
  `permission_id` bigint(20) UNSIGNED NOT NULL,
  `role_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `salaries`
--

CREATE TABLE `salaries` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `employee_id` bigint(20) UNSIGNED DEFAULT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `period_year` year(4) DEFAULT NULL,
  `period_month` tinyint(4) DEFAULT NULL,
  `basic_salary` decimal(15,2) NOT NULL DEFAULT 0.00,
  `allowances` decimal(15,2) NOT NULL DEFAULT 0.00,
  `deductions` decimal(15,2) NOT NULL DEFAULT 0.00,
  `late_deduction` decimal(12,2) NOT NULL DEFAULT 0.00,
  `bpjs_employee` decimal(12,2) NOT NULL DEFAULT 0.00,
  `bpjs_company` decimal(12,2) NOT NULL DEFAULT 0.00,
  `tax` decimal(12,2) NOT NULL DEFAULT 0.00,
  `other_deduction` decimal(12,2) NOT NULL DEFAULT 0.00,
  `payment_method` enum('cash','bank_transfer','cheque','other') NOT NULL DEFAULT 'bank_transfer',
  `total_salary` decimal(15,2) NOT NULL DEFAULT 0.00,
  `payment_date` date DEFAULT NULL,
  `payment_status` enum('pending','paid','cancelled') NOT NULL DEFAULT 'pending',
  `bank_name` varchar(100) DEFAULT NULL,
  `bank_account_number` varchar(50) DEFAULT NULL,
  `bank_account_holder` varchar(100) DEFAULT NULL,
  `approved_by` bigint(20) UNSIGNED DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `shifts`
--

CREATE TABLE `shifts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(20) DEFAULT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `working_hours` int(11) NOT NULL DEFAULT 8,
  `grace_period_minutes` int(11) NOT NULL DEFAULT 5,
  `late_tolerance_minutes` int(11) NOT NULL DEFAULT 5,
  `early_out_tolerance_minutes` int(11) NOT NULL DEFAULT 5,
  `overtime_start_time` time DEFAULT NULL,
  `description` text DEFAULT NULL,
  `is_night_shift` tinyint(1) NOT NULL DEFAULT 0,
  `color` varchar(7) NOT NULL DEFAULT '#3B82F6',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sidebar_permissions`
--

CREATE TABLE `sidebar_permissions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `permission_key` varchar(100) NOT NULL,
  `allowed` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `system_logs`
--

CREATE TABLE `system_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` char(36) NOT NULL,
  `level` varchar(20) NOT NULL,
  `channel` varchar(50) NOT NULL,
  `message` varchar(255) NOT NULL,
  `context` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`context`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `taggables`
--

CREATE TABLE `taggables` (
  `tag_id` bigint(20) UNSIGNED NOT NULL,
  `taggable_type` varchar(255) NOT NULL,
  `taggable_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tags`
--

CREATE TABLE `tags` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` char(36) NOT NULL,
  `tenant_id` bigint(20) UNSIGNED DEFAULT NULL,
  `name` varchar(50) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `color` varchar(7) NOT NULL DEFAULT '#6B7280',
  `type` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tenants`
--

CREATE TABLE `tenants` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` char(36) NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `domain` varchar(255) DEFAULT NULL,
  `logo_url` varchar(500) DEFAULT NULL,
  `favicon_url` varchar(500) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `status` enum('trial','active','suspended','cancelled','expired') NOT NULL DEFAULT 'trial',
  `timezone` varchar(64) NOT NULL DEFAULT 'Asia/Jakarta',
  `locale` varchar(10) NOT NULL DEFAULT 'id',
  `currency_code` varchar(3) NOT NULL DEFAULT 'IDR',
  `country_code` varchar(2) NOT NULL DEFAULT 'ID',
  `primary_color` varchar(7) DEFAULT NULL,
  `secondary_color` varchar(7) DEFAULT NULL,
  `settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`settings`)),
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `trial_ends_at` date DEFAULT NULL,
  `subscription_ends_at` date DEFAULT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED DEFAULT NULL,
  `division_id` bigint(20) UNSIGNED DEFAULT NULL,
  `company_role` varchar(255) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `uuid` char(36) NOT NULL,
  `tenant_id` bigint(20) UNSIGNED DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `username` varchar(100) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `avatar_url` varchar(500) DEFAULT NULL,
  `profile_photo` varchar(500) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `employee_id` varchar(50) DEFAULT NULL,
  `nik` varchar(20) DEFAULT NULL,
  `kk_number` varchar(20) DEFAULT NULL,
  `birth_place` varchar(100) DEFAULT NULL,
  `birth_date` date DEFAULT NULL,
  `gender` enum('male','female') DEFAULT NULL,
  `religion` varchar(50) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `province` varchar(100) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `district` varchar(100) DEFAULT NULL,
  `village` varchar(100) DEFAULT NULL,
  `postal_code` varchar(10) DEFAULT NULL,
  `ktp_address` varchar(255) DEFAULT NULL,
  `blood_type` varchar(5) DEFAULT NULL,
  `marital_status` varchar(20) DEFAULT NULL,
  `emergency_contact_name` varchar(100) DEFAULT NULL,
  `emergency_contact_phone` varchar(20) DEFAULT NULL,
  `emergency_contact_relation` varchar(50) DEFAULT NULL,
  `bank_name` varchar(100) DEFAULT NULL,
  `bank_account_number` varchar(50) DEFAULT NULL,
  `bank_account_name` varchar(100) DEFAULT NULL,
  `bpjs_number` varchar(20) DEFAULT NULL,
  `npwp_number` varchar(20) DEFAULT NULL,
  `father_name` varchar(100) DEFAULT NULL,
  `mother_name` varchar(100) DEFAULT NULL,
  `mother_maiden_name` varchar(100) DEFAULT NULL,
  `user_type` enum('super_admin','director','admin','manager','staff','client','developer','direktur') NOT NULL DEFAULT 'staff',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_owner` tinyint(1) NOT NULL DEFAULT 0,
  `department` varchar(100) DEFAULT NULL,
  `position` varchar(100) DEFAULT NULL,
  `last_login_at` timestamp NULL DEFAULT NULL,
  `last_login_ip` varchar(45) DEFAULT NULL,
  `password_changed_at` timestamp NULL DEFAULT NULL,
  `preferences` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`preferences`)),
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `sidebar_permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`sidebar_permissions`)),
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `updated_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `company_id`, `division_id`, `company_role`, `avatar`, `uuid`, `tenant_id`, `name`, `username`, `email`, `email_verified_at`, `password`, `remember_token`, `avatar_url`, `profile_photo`, `phone`, `employee_id`, `nik`, `kk_number`, `birth_place`, `birth_date`, `gender`, `religion`, `address`, `province`, `city`, `district`, `village`, `postal_code`, `ktp_address`, `blood_type`, `marital_status`, `emergency_contact_name`, `emergency_contact_phone`, `emergency_contact_relation`, `bank_name`, `bank_account_number`, `bank_account_name`, `bpjs_number`, `npwp_number`, `father_name`, `mother_name`, `mother_maiden_name`, `user_type`, `is_active`, `is_owner`, `department`, `position`, `last_login_at`, `last_login_ip`, `password_changed_at`, `preferences`, `metadata`, `sidebar_permissions`, `created_by`, `updated_by`, `created_at`, `updated_at`, `deleted_at`) VALUES
(33, NULL, NULL, NULL, NULL, '9e8ec3d5-c192-40d3-8d62-ab49622ac8bb', 13, 'Administrator', 'admin', 'admin@office354.com', '2026-08-20 06:38:47', '$2y$12$56cfBB9D9miMiG3/0bkz5ue6ILK3fZqVkz1L4V1M5pE6lFzi1qviK', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'developer', 1, 0, NULL, NULL, '2026-08-21 09:57:44', '127.0.0.1', NULL, NULL, NULL, '[\"sidebar.dashboard\",\"sidebar.staff_dashboard\",\"sidebar.employees\",\"sidebar.attendances\",\"sidebar.staff_reports\",\"sidebar.backup\",\"sidebar.hak_akses\",\"sidebar.master_data_umum\"]', NULL, NULL, '2026-08-20 06:38:47', '2026-08-21 10:00:34', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `webhook_configs`
--

CREATE TABLE `webhook_configs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `url` varchar(255) NOT NULL,
  `events` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`events`)),
  `secret` varchar(255) DEFAULT NULL,
  `headers` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`headers`)),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `webhook_delivery_logs`
--

CREATE TABLE `webhook_delivery_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `webhook_config_id` bigint(20) UNSIGNED DEFAULT NULL,
  `event` varchar(255) NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`payload`)),
  `response` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`response`)),
  `status_code` int(11) DEFAULT NULL,
  `attempts` int(11) NOT NULL DEFAULT 1,
  `delivered_at` timestamp NULL DEFAULT NULL,
  `error` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `webhook_logs`
--

CREATE TABLE `webhook_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` char(36) NOT NULL,
  `webhook_url` varchar(500) NOT NULL,
  `event` varchar(100) NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`payload`)),
  `headers` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`headers`)),
  `response` text DEFAULT NULL,
  `status_code` int(11) DEFAULT NULL,
  `attempts` int(11) NOT NULL DEFAULT 0,
  `delivered_at` timestamp NULL DEFAULT NULL,
  `failed_at` timestamp NULL DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `work_updates`
--

CREATE TABLE `work_updates` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` char(36) NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `task_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `completed_work` text DEFAULT NULL,
  `in_progress_work` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `progress` int(11) NOT NULL DEFAULT 0,
  `progress_manual` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `photo_count` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `activity_logs_uuid_unique` (`uuid`),
  ADD KEY `activity_logs_user_id_foreign` (`user_id`),
  ADD KEY `activity_logs_tenant_id_log_name_index` (`tenant_id`,`log_name`),
  ADD KEY `activity_logs_tenant_id_user_id_index` (`tenant_id`,`user_id`),
  ADD KEY `activity_logs_tenant_id_subject_type_subject_id_index` (`tenant_id`,`subject_type`,`subject_id`),
  ADD KEY `activity_logs_tenant_id_event_index` (`tenant_id`,`event`),
  ADD KEY `activity_logs_tenant_id_created_at_index` (`tenant_id`,`created_at`);

--
-- Indexes for table `addresses`
--
ALTER TABLE `addresses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `addresses_uuid_unique` (`uuid`),
  ADD KEY `addresses_tenant_id_addressable_type_addressable_id_index` (`tenant_id`,`addressable_type`,`addressable_id`);

--
-- Indexes for table `api_keys`
--
ALTER TABLE `api_keys`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `api_keys_key_unique` (`key`),
  ADD KEY `api_keys_user_id_expires_at_index` (`user_id`,`expires_at`);

--
-- Indexes for table `attachments`
--
ALTER TABLE `attachments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `attachments_uuid_unique` (`uuid`),
  ADD KEY `attachments_uploaded_by_foreign` (`uploaded_by`),
  ADD KEY `attachments_tenant_id_attachable_type_attachable_id_index` (`tenant_id`,`attachable_type`,`attachable_id`);

--
-- Indexes for table `attendances`
--
ALTER TABLE `attendances`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `attendances_user_id_date_unique` (`user_id`,`date`),
  ADD KEY `attendances_company_id_date_index` (`company_id`,`date`),
  ADD KEY `attendances_placement_id_foreign` (`placement_id`);

--
-- Indexes for table `attendance_histories`
--
ALTER TABLE `attendance_histories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `attendance_histories_attendance_id_index` (`attendance_id`),
  ADD KEY `attendance_histories_user_id_index` (`user_id`);

--
-- Indexes for table `attendance_requests`
--
ALTER TABLE `attendance_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `attendance_requests_user_id_foreign` (`user_id`),
  ADD KEY `attendance_requests_approved_by_foreign` (`approved_by`),
  ADD KEY `attendance_requests_rejected_by_foreign` (`rejected_by`),
  ADD KEY `attendance_requests_employee_id_date_index` (`employee_id`,`date`),
  ADD KEY `attendance_requests_company_id_status_index` (`company_id`,`status`),
  ADD KEY `attendance_requests_status_created_at_index` (`status`,`created_at`);

--
-- Indexes for table `attendance_settings`
--
ALTER TABLE `attendance_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `attendance_settings_uuid_unique` (`uuid`),
  ADD KEY `attendance_settings_company_id_is_active_index` (`company_id`,`is_active`);

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `audit_logs_uuid_unique` (`uuid`),
  ADD KEY `audit_logs_user_id_foreign` (`user_id`),
  ADD KEY `audit_logs_tenant_id_auditable_type_auditable_id_index` (`tenant_id`,`auditable_type`,`auditable_id`),
  ADD KEY `audit_logs_tenant_id_user_id_index` (`tenant_id`,`user_id`),
  ADD KEY `audit_logs_tenant_id_action_index` (`tenant_id`,`action`),
  ADD KEY `audit_logs_tenant_id_created_at_index` (`tenant_id`,`created_at`),
  ADD KEY `audit_logs_request_id_index` (`request_id`);

--
-- Indexes for table `backups`
--
ALTER TABLE `backups`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `backups_uuid_unique` (`uuid`),
  ADD KEY `backups_created_by_foreign` (`created_by`),
  ADD KEY `backups_company_id_index` (`company_id`),
  ADD KEY `backups_backup_type_index` (`backup_type`),
  ADD KEY `backups_status_index` (`status`),
  ADD KEY `backups_is_scheduled_index` (`is_scheduled`),
  ADD KEY `backups_created_at_index` (`created_at`);

--
-- Indexes for table `backup_settings`
--
ALTER TABLE `backup_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `backup_settings_uuid_unique` (`uuid`),
  ADD KEY `backup_settings_created_by_foreign` (`created_by`),
  ADD KEY `backup_settings_updated_by_foreign` (`updated_by`),
  ADD KEY `backup_settings_company_id_index` (`company_id`);

--
-- Indexes for table `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`),
  ADD KEY `cache_expiration_index` (`expiration`);

--
-- Indexes for table `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`),
  ADD KEY `cache_locks_expiration_index` (`expiration`);

--
-- Indexes for table `clients`
--
ALTER TABLE `clients`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `clients_uuid_unique` (`uuid`),
  ADD KEY `clients_user_id_foreign` (`user_id`),
  ADD KEY `clients_created_by_foreign` (`created_by`),
  ADD KEY `clients_updated_by_foreign` (`updated_by`),
  ADD KEY `clients_tenant_id_is_active_index` (`tenant_id`,`is_active`),
  ADD KEY `clients_tenant_id_name_index` (`tenant_id`,`name`),
  ADD KEY `clients_company_id_foreign` (`company_id`);

--
-- Indexes for table `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `comments_uuid_unique` (`uuid`),
  ADD KEY `comments_user_id_foreign` (`user_id`),
  ADD KEY `comments_parent_id_foreign` (`parent_id`),
  ADD KEY `comments_tenant_id_commentable_type_commentable_id_index` (`tenant_id`,`commentable_type`,`commentable_id`);

--
-- Indexes for table `companies`
--
ALTER TABLE `companies`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `companies_uuid_unique` (`uuid`),
  ADD UNIQUE KEY `companies_slug_unique` (`slug`),
  ADD KEY `companies_slug_index` (`slug`),
  ADD KEY `companies_is_active_index` (`is_active`);

--
-- Indexes for table `company_notifications`
--
ALTER TABLE `company_notifications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `company_notifications_uuid_unique` (`uuid`),
  ADD KEY `company_notifications_user_id_foreign` (`user_id`),
  ADD KEY `company_notifications_company_id_is_read_index` (`company_id`,`is_read`),
  ADD KEY `company_notifications_company_id_user_id_index` (`company_id`,`user_id`),
  ADD KEY `company_notifications_notifiable_type_notifiable_id_index` (`notifiable_type`,`notifiable_id`),
  ADD KEY `company_notifications_created_at_index` (`created_at`);

--
-- Indexes for table `company_subscriptions`
--
ALTER TABLE `company_subscriptions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `company_subscriptions_subscription_code_unique` (`subscription_code`),
  ADD KEY `company_subscriptions_company_id_index` (`company_id`),
  ADD KEY `company_subscriptions_plan_id_index` (`plan_id`),
  ADD KEY `company_subscriptions_status_index` (`status`),
  ADD KEY `company_subscriptions_end_date_index` (`end_date`);

--
-- Indexes for table `countries`
--
ALTER TABLE `countries`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `countries_alpha_2_unique` (`alpha_2`),
  ADD UNIQUE KEY `countries_alpha_3_unique` (`alpha_3`);

--
-- Indexes for table `credit_notes`
--
ALTER TABLE `credit_notes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `credit_notes_uuid_unique` (`uuid`),
  ADD UNIQUE KEY `credit_notes_credit_note_number_unique` (`credit_note_number`),
  ADD KEY `credit_notes_client_id_foreign` (`client_id`),
  ADD KEY `credit_notes_user_id_foreign` (`user_id`),
  ADD KEY `credit_notes_invoice_id_foreign` (`invoice_id`),
  ADD KEY `credit_notes_created_by_foreign` (`created_by`),
  ADD KEY `credit_notes_updated_by_foreign` (`updated_by`),
  ADD KEY `credit_notes_tenant_id_client_id_index` (`tenant_id`,`client_id`),
  ADD KEY `credit_notes_tenant_id_status_index` (`tenant_id`,`status`),
  ADD KEY `credit_notes_company_id_status_index` (`company_id`,`status`),
  ADD KEY `credit_notes_company_id_client_id_index` (`company_id`,`client_id`),
  ADD KEY `credit_notes_contact_id_foreign` (`contact_id`);

--
-- Indexes for table `crm_module_permissions`
--
ALTER TABLE `crm_module_permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_module_permission_unique` (`user_id`,`module`,`permission_key`),
  ADD KEY `crm_module_permissions_company_id_user_id_index` (`company_id`,`user_id`),
  ADD KEY `crm_module_permissions_module_permission_key_index` (`module`,`permission_key`);

--
-- Indexes for table `crm_user_permissions`
--
ALTER TABLE `crm_user_permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `crm_user_permissions_user_id_module_unique` (`user_id`,`module`),
  ADD KEY `crm_user_permissions_user_id_index` (`user_id`),
  ADD KEY `crm_user_permissions_module_index` (`module`);

--
-- Indexes for table `crm_user_permissions_v2`
--
ALTER TABLE `crm_user_permissions_v2`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `crm_user_permissions_v2_user_id_module_unique` (`user_id`,`module`),
  ADD KEY `crm_user_permissions_v2_user_id_index` (`user_id`),
  ADD KEY `crm_user_permissions_v2_module_index` (`module`);

--
-- Indexes for table `currencies`
--
ALTER TABLE `currencies`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `currencies_code_unique` (`code`),
  ADD KEY `currencies_code_index` (`code`),
  ADD KEY `currencies_is_default_index` (`is_default`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `departments_parent_id_foreign` (`parent_id`),
  ADD KEY `departments_company_id_is_active_index` (`company_id`,`is_active`);

--
-- Indexes for table `divisions`
--
ALTER TABLE `divisions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `divisions_company_id_slug_unique` (`company_id`,`slug`),
  ADD KEY `divisions_company_id_index` (`company_id`),
  ADD KEY `divisions_tenant_id_foreign` (`tenant_id`),
  ADD KEY `divisions_department_id_foreign` (`department_id`),
  ADD KEY `divisions_company_id_department_id_index` (`company_id`,`department_id`);

--
-- Indexes for table `employee_account_link_requests`
--
ALTER TABLE `employee_account_link_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_account_link_requests_employee_profile_id_foreign` (`employee_profile_id`),
  ADD KEY `employee_account_link_requests_processed_by_foreign` (`processed_by`),
  ADD KEY `employee_account_link_requests_company_id_status_index` (`company_id`,`status`),
  ADD KEY `employee_account_link_requests_user_id_status_index` (`user_id`,`status`);

--
-- Indexes for table `employee_documents`
--
ALTER TABLE `employee_documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_documents_user_id_foreign` (`user_id`),
  ADD KEY `employee_documents_uploaded_by_foreign` (`uploaded_by`),
  ADD KEY `employee_documents_employee_id_document_type_index` (`employee_id`,`document_type`),
  ADD KEY `employee_documents_company_id_index` (`company_id`);

--
-- Indexes for table `employee_placements`
--
ALTER TABLE `employee_placements`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `employee_placements_uuid_unique` (`uuid`),
  ADD KEY `employee_placements_created_by_foreign` (`created_by`),
  ADD KEY `employee_placements_company_id_is_active_index` (`company_id`,`is_active`),
  ADD KEY `employee_placements_code_index` (`code`);

--
-- Indexes for table `employee_profiles`
--
ALTER TABLE `employee_profiles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `employee_profiles_employee_number_unique` (`employee_number`),
  ADD KEY `employee_profiles_position_id_foreign` (`position_id`),
  ADD KEY `employee_profiles_company_id_is_active_index` (`company_id`,`is_active`),
  ADD KEY `employee_profiles_department_id_is_active_index` (`department_id`,`is_active`),
  ADD KEY `employee_profiles_placement_id_foreign` (`placement_id`),
  ADD KEY `employee_profiles_user_id_foreign` (`user_id`),
  ADD KEY `employee_profiles_division_id_is_active_index` (`division_id`,`is_active`),
  ADD KEY `employee_profiles_employee_type_id_index` (`employee_type_id`),
  ADD KEY `employee_profiles_supervisor_id_foreign` (`supervisor_id`);

--
-- Indexes for table `employee_salary_components`
--
ALTER TABLE `employee_salary_components`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_salary_components_salary_id_type_index` (`salary_id`,`type`);

--
-- Indexes for table `employee_sidebar_permissions`
--
ALTER TABLE `employee_sidebar_permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `employee_sidebar_permissions_employee_id_menu_key_unique` (`employee_id`,`menu_key`),
  ADD KEY `employee_sidebar_permissions_employee_id_menu_key_index` (`employee_id`,`menu_key`);

--
-- Indexes for table `employee_types`
--
ALTER TABLE `employee_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `employee_types_company_id_code_unique` (`company_id`,`code`),
  ADD KEY `employee_types_company_id_is_active_index` (`company_id`,`is_active`);

--
-- Indexes for table `estimates`
--
ALTER TABLE `estimates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `estimates_uuid_unique` (`uuid`),
  ADD UNIQUE KEY `estimates_estimate_number_unique` (`estimate_number`),
  ADD KEY `estimates_client_id_foreign` (`client_id`),
  ADD KEY `estimates_contact_id_foreign` (`contact_id`),
  ADD KEY `estimates_user_id_foreign` (`user_id`),
  ADD KEY `estimates_created_by_foreign` (`created_by`),
  ADD KEY `estimates_updated_by_foreign` (`updated_by`),
  ADD KEY `estimates_tenant_id_status_index` (`tenant_id`,`status`),
  ADD KEY `estimates_tenant_id_client_id_index` (`tenant_id`,`client_id`),
  ADD KEY `estimates_company_id_foreign` (`company_id`),
  ADD KEY `estimates_proposal_id_foreign` (`proposal_id`);

--
-- Indexes for table `estimate_items`
--
ALTER TABLE `estimate_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `estimate_items_item_id_foreign` (`item_id`),
  ADD KEY `estimate_items_estimate_id_index` (`estimate_id`),
  ADD KEY `estimate_items_tenant_id_foreign` (`tenant_id`),
  ADD KEY `estimate_items_company_id_index` (`company_id`);

--
-- Indexes for table `estimate_requests`
--
ALTER TABLE `estimate_requests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `estimate_requests_uuid_unique` (`uuid`),
  ADD UNIQUE KEY `estimate_requests_request_number_unique` (`request_number`),
  ADD KEY `estimate_requests_lead_id_foreign` (`lead_id`),
  ADD KEY `estimate_requests_assigned_to_foreign` (`assigned_to`),
  ADD KEY `estimate_requests_converted_to_estimate_id_foreign` (`converted_to_estimate_id`),
  ADD KEY `estimate_requests_created_by_foreign` (`created_by`),
  ADD KEY `estimate_requests_updated_by_foreign` (`updated_by`),
  ADD KEY `estimate_requests_tenant_id_status_index` (`tenant_id`,`status`),
  ADD KEY `estimate_requests_tenant_id_priority_index` (`tenant_id`,`priority`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indexes for table `invoices`
--
ALTER TABLE `invoices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `invoices_uuid_unique` (`uuid`),
  ADD UNIQUE KEY `invoices_invoice_number_unique` (`invoice_number`),
  ADD KEY `invoices_client_id_foreign` (`client_id`),
  ADD KEY `invoices_contact_id_foreign` (`contact_id`),
  ADD KEY `invoices_user_id_foreign` (`user_id`),
  ADD KEY `invoices_created_by_foreign` (`created_by`),
  ADD KEY `invoices_updated_by_foreign` (`updated_by`),
  ADD KEY `invoices_tenant_id_status_index` (`tenant_id`,`status`),
  ADD KEY `invoices_tenant_id_client_id_index` (`tenant_id`,`client_id`),
  ADD KEY `invoices_tenant_id_due_date_index` (`tenant_id`,`due_date`),
  ADD KEY `invoices_company_id_foreign` (`company_id`),
  ADD KEY `invoices_estimate_id_foreign` (`estimate_id`),
  ADD KEY `invoices_proposal_id_foreign` (`proposal_id`),
  ADD KEY `invoices_tenant_id_credit_applied_index` (`tenant_id`,`credit_applied`);

--
-- Indexes for table `invoice_items`
--
ALTER TABLE `invoice_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `invoice_items_item_id_foreign` (`item_id`),
  ADD KEY `invoice_items_invoice_id_index` (`invoice_id`),
  ADD KEY `invoice_items_tenant_id_foreign` (`tenant_id`),
  ADD KEY `invoice_items_company_id_index` (`company_id`);

--
-- Indexes for table `items`
--
ALTER TABLE `items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `items_uuid_unique` (`uuid`),
  ADD UNIQUE KEY `items_sku_unique` (`sku`),
  ADD KEY `items_created_by_foreign` (`created_by`),
  ADD KEY `items_updated_by_foreign` (`updated_by`),
  ADD KEY `items_tenant_id_group_name_index` (`tenant_id`,`group_name`),
  ADD KEY `items_tenant_id_is_active_index` (`tenant_id`,`is_active`),
  ADD KEY `items_company_id_is_active_index` (`company_id`,`is_active`),
  ADD KEY `items_company_id_group_name_index` (`company_id`,`group_name`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_queue_index` (`queue`);

--
-- Indexes for table `job_batches`
--
ALTER TABLE `job_batches`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `knowledge_base`
--
ALTER TABLE `knowledge_base`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `knowledge_base_uuid_unique` (`uuid`),
  ADD UNIQUE KEY `knowledge_base_slug_unique` (`slug`),
  ADD KEY `knowledge_base_category_id_foreign` (`category_id`),
  ADD KEY `knowledge_base_author_id_foreign` (`author_id`),
  ADD KEY `knowledge_base_created_by_foreign` (`created_by`),
  ADD KEY `knowledge_base_updated_by_foreign` (`updated_by`),
  ADD KEY `knowledge_base_tenant_id_status_index` (`tenant_id`,`status`),
  ADD KEY `knowledge_base_tenant_id_category_id_index` (`tenant_id`,`category_id`);

--
-- Indexes for table `knowledge_base_categories`
--
ALTER TABLE `knowledge_base_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `knowledge_base_categories_uuid_unique` (`uuid`),
  ADD UNIQUE KEY `knowledge_base_categories_slug_unique` (`slug`),
  ADD KEY `knowledge_base_categories_tenant_id_index` (`tenant_id`);

--
-- Indexes for table `k_p_i_settings`
--
ALTER TABLE `k_p_i_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `k_p_i_settings_company_id_position_id_name_unique` (`company_id`,`position_id`,`name`),
  ADD KEY `k_p_i_settings_position_id_foreign` (`position_id`),
  ADD KEY `k_p_i_settings_company_id_is_active_index` (`company_id`,`is_active`);

--
-- Indexes for table `languages`
--
ALTER TABLE `languages`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `languages_code_unique` (`code`),
  ADD KEY `languages_code_index` (`code`),
  ADD KEY `languages_is_default_index` (`is_default`);

--
-- Indexes for table `leads`
--
ALTER TABLE `leads`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `leads_uuid_unique` (`uuid`),
  ADD KEY `leads_user_id_foreign` (`user_id`),
  ADD KEY `leads_converted_to_client_id_foreign` (`converted_to_client_id`),
  ADD KEY `leads_created_by_foreign` (`created_by`),
  ADD KEY `leads_updated_by_foreign` (`updated_by`),
  ADD KEY `leads_tenant_id_status_index` (`tenant_id`,`status`),
  ADD KEY `leads_tenant_id_priority_index` (`tenant_id`,`priority`),
  ADD KEY `leads_tenant_id_user_id_index` (`tenant_id`,`user_id`),
  ADD KEY `leads_company_id_foreign` (`company_id`);

--
-- Indexes for table `leaves`
--
ALTER TABLE `leaves`
  ADD PRIMARY KEY (`id`),
  ADD KEY `leaves_approved_by_foreign` (`approved_by`),
  ADD KEY `leaves_company_id_status_index` (`company_id`,`status`),
  ADD KEY `leaves_user_id_status_index` (`user_id`,`status`),
  ADD KEY `leaves_leave_type_id_foreign` (`leave_type_id`);

--
-- Indexes for table `leave_entitlements`
--
ALTER TABLE `leave_entitlements`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `leave_entitlements_employee_id_leave_type_id_year_unique` (`employee_id`,`leave_type_id`,`year`),
  ADD KEY `leave_entitlements_company_id_foreign` (`company_id`),
  ADD KEY `leave_entitlements_created_by_foreign` (`created_by`),
  ADD KEY `leave_entitlements_employee_id_year_index` (`employee_id`,`year`),
  ADD KEY `leave_entitlements_leave_type_id_index` (`leave_type_id`);

--
-- Indexes for table `leave_types`
--
ALTER TABLE `leave_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `leave_types_company_id_code_unique` (`company_id`,`code`),
  ADD KEY `leave_types_company_id_is_active_index` (`company_id`,`is_active`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `model_has_permissions`
--
ALTER TABLE `model_has_permissions`
  ADD PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  ADD KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`);

--
-- Indexes for table `model_has_roles`
--
ALTER TABLE `model_has_roles`
  ADD PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  ADD KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`);

--
-- Indexes for table `modules`
--
ALTER TABLE `modules`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `modules_code_unique` (`code`),
  ADD KEY `modules_category_index` (`category`),
  ADD KEY `modules_is_active_index` (`is_active`),
  ADD KEY `modules_is_premium_index` (`is_premium`);

--
-- Indexes for table `module_features`
--
ALTER TABLE `module_features`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `module_features_module_id_code_unique` (`module_id`,`code`),
  ADD KEY `module_features_module_id_index` (`module_id`);

--
-- Indexes for table `module_transactions`
--
ALTER TABLE `module_transactions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `module_transactions_transaction_code_unique` (`transaction_code`),
  ADD KEY `module_transactions_plan_id_foreign` (`plan_id`),
  ADD KEY `module_transactions_company_id_index` (`company_id`),
  ADD KEY `module_transactions_subscription_id_index` (`subscription_id`),
  ADD KEY `module_transactions_module_id_index` (`module_id`),
  ADD KEY `module_transactions_status_index` (`status`),
  ADD KEY `module_transactions_transaction_code_index` (`transaction_code`),
  ADD KEY `module_transactions_payment_transaction_id_foreign` (`payment_transaction_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `notifications_uuid_unique` (`uuid`),
  ADD KEY `notifications_tenant_id_notifiable_type_notifiable_id_index` (`tenant_id`,`notifiable_type`,`notifiable_id`),
  ADD KEY `notifications_tenant_id_read_at_index` (`tenant_id`,`read_at`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`);

--
-- Indexes for table `plans`
--
ALTER TABLE `plans`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `plans_module_id_code_unique` (`module_id`,`code`),
  ADD KEY `plans_module_id_index` (`module_id`),
  ADD KEY `plans_is_active_index` (`is_active`);

--
-- Indexes for table `positions`
--
ALTER TABLE `positions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `positions_company_id_is_active_index` (`company_id`,`is_active`),
  ADD KEY `positions_division_id_foreign` (`division_id`),
  ADD KEY `positions_department_id_division_id_index` (`department_id`,`division_id`);

--
-- Indexes for table `recruitments`
--
ALTER TABLE `recruitments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `recruitments_department_id_foreign` (`department_id`),
  ADD KEY `recruitments_position_id_foreign` (`position_id`),
  ADD KEY `recruitments_company_id_stage_index` (`company_id`,`stage`),
  ADD KEY `recruitments_company_id_created_at_index` (`company_id`,`created_at`);

--
-- Indexes for table `retry_logs`
--
ALTER TABLE `retry_logs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `retry_logs_uuid_unique` (`uuid`),
  ADD KEY `retry_logs_entity_type_entity_id_index` (`entity_type`,`entity_id`),
  ADD KEY `retry_logs_action_success_index` (`action`,`success`),
  ADD KEY `retry_logs_next_attempt_at_success_index` (`next_attempt_at`,`success`),
  ADD KEY `retry_logs_success_index` (`success`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`);

--
-- Indexes for table `role_has_permissions`
--
ALTER TABLE `role_has_permissions`
  ADD PRIMARY KEY (`permission_id`,`role_id`),
  ADD KEY `role_has_permissions_role_id_foreign` (`role_id`);

--
-- Indexes for table `salaries`
--
ALTER TABLE `salaries`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Indexes for table `shifts`
--
ALTER TABLE `shifts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `shifts_company_id_is_active_index` (`company_id`,`is_active`);

--
-- Indexes for table `sidebar_permissions`
--
ALTER TABLE `sidebar_permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sidebar_permissions_user_id_permission_key_unique` (`user_id`,`permission_key`),
  ADD KEY `sidebar_permissions_company_id_user_id_index` (`company_id`,`user_id`),
  ADD KEY `sidebar_permissions_permission_key_index` (`permission_key`);

--
-- Indexes for table `system_logs`
--
ALTER TABLE `system_logs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `system_logs_uuid_unique` (`uuid`),
  ADD KEY `system_logs_channel_level_index` (`channel`,`level`),
  ADD KEY `system_logs_channel_created_at_index` (`channel`,`created_at`),
  ADD KEY `system_logs_created_at_index` (`created_at`),
  ADD KEY `system_logs_user_id_foreign` (`user_id`),
  ADD KEY `system_logs_level_index` (`level`),
  ADD KEY `system_logs_channel_index` (`channel`);

--
-- Indexes for table `taggables`
--
ALTER TABLE `taggables`
  ADD PRIMARY KEY (`tag_id`,`taggable_type`,`taggable_id`),
  ADD KEY `taggables_taggable_type_taggable_id_index` (`taggable_type`,`taggable_id`);

--
-- Indexes for table `tags`
--
ALTER TABLE `tags`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `tags_uuid_unique` (`uuid`),
  ADD UNIQUE KEY `tags_slug_unique` (`slug`),
  ADD UNIQUE KEY `tags_tenant_id_name_unique` (`tenant_id`,`name`),
  ADD KEY `tags_tenant_id_index` (`tenant_id`);

--
-- Indexes for table `tenants`
--
ALTER TABLE `tenants`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `tenants_uuid_unique` (`uuid`),
  ADD UNIQUE KEY `tenants_slug_unique` (`slug`),
  ADD UNIQUE KEY `tenants_domain_unique` (`domain`),
  ADD KEY `tenants_status_index` (`status`),
  ADD KEY `tenants_slug_index` (`slug`),
  ADD KEY `tenants_domain_index` (`domain`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_uuid_unique` (`uuid`),
  ADD UNIQUE KEY `users_tenant_id_email_unique` (`tenant_id`,`email`),
  ADD UNIQUE KEY `users_username_unique` (`username`),
  ADD KEY `users_created_by_foreign` (`created_by`),
  ADD KEY `users_updated_by_foreign` (`updated_by`),
  ADD KEY `users_tenant_id_index` (`tenant_id`),
  ADD KEY `users_is_active_index` (`is_active`),
  ADD KEY `users_user_type_index` (`user_type`),
  ADD KEY `users_company_id_foreign` (`company_id`),
  ADD KEY `users_division_id_foreign` (`division_id`);

--
-- Indexes for table `webhook_configs`
--
ALTER TABLE `webhook_configs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `webhook_configs_is_active_index` (`is_active`);

--
-- Indexes for table `webhook_delivery_logs`
--
ALTER TABLE `webhook_delivery_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `webhook_delivery_logs_webhook_config_id_delivered_at_index` (`webhook_config_id`,`delivered_at`);

--
-- Indexes for table `webhook_logs`
--
ALTER TABLE `webhook_logs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `webhook_logs_uuid_unique` (`uuid`),
  ADD KEY `webhook_logs_event_delivered_at_index` (`event`,`delivered_at`),
  ADD KEY `webhook_logs_status_code_delivered_at_index` (`status_code`,`delivered_at`),
  ADD KEY `webhook_logs_event_index` (`event`);

--
-- Indexes for table `work_updates`
--
ALTER TABLE `work_updates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `work_updates_uuid_unique` (`uuid`),
  ADD KEY `work_updates_task_id_created_at_index` (`task_id`,`created_at`),
  ADD KEY `work_updates_user_id_index` (`user_id`),
  ADD KEY `work_updates_tenant_id_index` (`tenant_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=265;

--
-- AUTO_INCREMENT for table `addresses`
--
ALTER TABLE `addresses`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `api_keys`
--
ALTER TABLE `api_keys`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `attachments`
--
ALTER TABLE `attachments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `attendances`
--
ALTER TABLE `attendances`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=131;

--
-- AUTO_INCREMENT for table `attendance_histories`
--
ALTER TABLE `attendance_histories`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `attendance_requests`
--
ALTER TABLE `attendance_requests`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `attendance_settings`
--
ALTER TABLE `attendance_settings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `backups`
--
ALTER TABLE `backups`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `backup_settings`
--
ALTER TABLE `backup_settings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `clients`
--
ALTER TABLE `clients`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `comments`
--
ALTER TABLE `comments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `companies`
--
ALTER TABLE `companies`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `company_notifications`
--
ALTER TABLE `company_notifications`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=145;

--
-- AUTO_INCREMENT for table `company_subscriptions`
--
ALTER TABLE `company_subscriptions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `countries`
--
ALTER TABLE `countries`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `credit_notes`
--
ALTER TABLE `credit_notes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `crm_module_permissions`
--
ALTER TABLE `crm_module_permissions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=59;

--
-- AUTO_INCREMENT for table `crm_user_permissions`
--
ALTER TABLE `crm_user_permissions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=57;

--
-- AUTO_INCREMENT for table `crm_user_permissions_v2`
--
ALTER TABLE `crm_user_permissions_v2`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=958;

--
-- AUTO_INCREMENT for table `currencies`
--
ALTER TABLE `currencies`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `divisions`
--
ALTER TABLE `divisions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `employee_account_link_requests`
--
ALTER TABLE `employee_account_link_requests`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employee_documents`
--
ALTER TABLE `employee_documents`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `employee_placements`
--
ALTER TABLE `employee_placements`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `employee_profiles`
--
ALTER TABLE `employee_profiles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `employee_salary_components`
--
ALTER TABLE `employee_salary_components`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `employee_sidebar_permissions`
--
ALTER TABLE `employee_sidebar_permissions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=516;

--
-- AUTO_INCREMENT for table `employee_types`
--
ALTER TABLE `employee_types`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `estimates`
--
ALTER TABLE `estimates`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `estimate_items`
--
ALTER TABLE `estimate_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `estimate_requests`
--
ALTER TABLE `estimate_requests`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `invoices`
--
ALTER TABLE `invoices`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `invoice_items`
--
ALTER TABLE `invoice_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `items`
--
ALTER TABLE `items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `knowledge_base`
--
ALTER TABLE `knowledge_base`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `knowledge_base_categories`
--
ALTER TABLE `knowledge_base_categories`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `k_p_i_settings`
--
ALTER TABLE `k_p_i_settings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `languages`
--
ALTER TABLE `languages`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `leads`
--
ALTER TABLE `leads`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `leaves`
--
ALTER TABLE `leaves`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `leave_entitlements`
--
ALTER TABLE `leave_entitlements`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `leave_types`
--
ALTER TABLE `leave_types`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=199;

--
-- AUTO_INCREMENT for table `modules`
--
ALTER TABLE `modules`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `module_features`
--
ALTER TABLE `module_features`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=153;

--
-- AUTO_INCREMENT for table `module_transactions`
--
ALTER TABLE `module_transactions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `plans`
--
ALTER TABLE `plans`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `positions`
--
ALTER TABLE `positions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `recruitments`
--
ALTER TABLE `recruitments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `retry_logs`
--
ALTER TABLE `retry_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `salaries`
--
ALTER TABLE `salaries`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `shifts`
--
ALTER TABLE `shifts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `sidebar_permissions`
--
ALTER TABLE `sidebar_permissions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `system_logs`
--
ALTER TABLE `system_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tags`
--
ALTER TABLE `tags`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tenants`
--
ALTER TABLE `tenants`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `webhook_configs`
--
ALTER TABLE `webhook_configs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `webhook_delivery_logs`
--
ALTER TABLE `webhook_delivery_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `webhook_logs`
--
ALTER TABLE `webhook_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `work_updates`
--
ALTER TABLE `work_updates`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `activity_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `addresses`
--
ALTER TABLE `addresses`
  ADD CONSTRAINT `addresses_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `api_keys`
--
ALTER TABLE `api_keys`
  ADD CONSTRAINT `api_keys_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `attachments`
--
ALTER TABLE `attachments`
  ADD CONSTRAINT `attachments_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attachments_uploaded_by_foreign` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `attendances`
--
ALTER TABLE `attendances`
  ADD CONSTRAINT `attendances_placement_id_foreign` FOREIGN KEY (`placement_id`) REFERENCES `employee_placements` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `attendance_histories`
--
ALTER TABLE `attendance_histories`
  ADD CONSTRAINT `attendance_histories_attendance_id_foreign` FOREIGN KEY (`attendance_id`) REFERENCES `attendances` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendance_histories_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `attendance_requests`
--
ALTER TABLE `attendance_requests`
  ADD CONSTRAINT `attendance_requests_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `attendance_requests_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendance_requests_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employee_profiles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendance_requests_rejected_by_foreign` FOREIGN KEY (`rejected_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `attendance_requests_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `attendance_settings`
--
ALTER TABLE `attendance_settings`
  ADD CONSTRAINT `attendance_settings_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `audit_logs_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `audit_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `backups`
--
ALTER TABLE `backups`
  ADD CONSTRAINT `backups_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `backups_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `backup_settings`
--
ALTER TABLE `backup_settings`
  ADD CONSTRAINT `backup_settings_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `backup_settings_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `backup_settings_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `clients`
--
ALTER TABLE `clients`
  ADD CONSTRAINT `clients_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `clients_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `clients_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `clients_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `clients_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `comments`
--
ALTER TABLE `comments`
  ADD CONSTRAINT `comments_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `comments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comments_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `company_notifications`
--
ALTER TABLE `company_notifications`
  ADD CONSTRAINT `company_notifications_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `company_notifications_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `company_subscriptions`
--
ALTER TABLE `company_subscriptions`
  ADD CONSTRAINT `company_subscriptions_plan_id_foreign` FOREIGN KEY (`plan_id`) REFERENCES `plans` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `crm_module_permissions`
--
ALTER TABLE `crm_module_permissions`
  ADD CONSTRAINT `crm_module_permissions_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `crm_module_permissions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
