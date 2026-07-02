-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Хост: 127.0.0.1
-- Время создания: Янв 15 2026 г., 12:57
-- Версия сервера: 10.4.32-MariaDB
-- Версия PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `tis_system`
--

-- --------------------------------------------------------

--
-- Структура таблицы `davamiyyet`
--

CREATE TABLE `davamiyyet` (
  `id` int(11) NOT NULL,
  `u_id` varchar(110) NOT NULL,
  `company_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `tarix` date NOT NULL,
  `status` enum('Istirak_edir','Qeyri-istikamet','Gecikib','Xəstədir','Izinsiz') DEFAULT 'Istirak_edir',
  `qeyd` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `dersler`
--

CREATE TABLE `dersler` (
  `id` int(11) NOT NULL,
  `u_id` varchar(110) DEFAULT NULL,
  `company_id` int(11) NOT NULL,
  `fenn` varchar(255) NOT NULL,
  `sinif` varchar(255) NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `otaq` varchar(255) NOT NULL,
  `muellim` varchar(255) NOT NULL,
  `sagird_sayi` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL,
  `movzu` text NOT NULL,
  `active_status` tinyint(1) NOT NULL DEFAULT 1,
  `tesvir` text NOT NULL,
  `materiallar` text NOT NULL,
  `tarix` date NOT NULL,
  `muellim_id` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `elanlar`
--

CREATE TABLE `elanlar` (
  `id` int(11) NOT NULL,
  `u_id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `movzu` varchar(255) NOT NULL,
  `text` text NOT NULL,
  `status` varchar(50) NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `file` varchar(255) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `emekdaslar`
--

CREATE TABLE `emekdaslar` (
  `id` int(11) NOT NULL,
  `u_id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `ad_soyad` varchar(255) NOT NULL,
  `sobe` varchar(255) NOT NULL,
  `vezife` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `telefon` varchar(50) DEFAULT NULL,
  `ise_baslama_tarixi` date NOT NULL,
  `status` varchar(50) DEFAULT NULL,
  `unvan` varchar(255) DEFAULT NULL,
  `sekil` varchar(255) DEFAULT NULL,
  `tehsil` text DEFAULT NULL,
  `is_tecrubesi` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `fennler_new`
--

CREATE TABLE `fennler_new` (
  `id` int(11) NOT NULL,
  `u_id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `fenn_adi` varchar(300) NOT NULL,
  `fenn_id` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `fennler_new`
--

INSERT INTO `fennler_new` (`id`, `u_id`, `company_id`, `fenn_adi`, `fenn_id`, `created_at`, `updated_at`) VALUES
(9, 0, 0, 'MƏNTİQ', 0, '2025-07-20 00:40:41', '2025-07-20 00:40:41'),
(10, 0, 0, 'IKT', 0, '2025-07-20 00:44:26', '2025-07-20 00:44:26'),
(11, 0, 0, 'İxtisas Adı', 0, '2026-01-15 15:42:23', '2026-01-15 15:42:23');

-- --------------------------------------------------------

--
-- Структура таблицы `filiallar`
--

CREATE TABLE `filiallar` (
  `id` int(11) NOT NULL,
  `u_id` int(10) NOT NULL,
  `company_id` int(11) NOT NULL,
  `filial_adi` varchar(2000) NOT NULL,
  `unvan` varchar(2000) NOT NULL,
  `telefon` varchar(2000) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `filiallar`
--

INSERT INTO `filiallar` (`id`, `u_id`, `company_id`, `filial_adi`, `unvan`, `telefon`, `created_at`, `updated_at`) VALUES
(20, 1, 0, 'Xalqlar', 'Xalqlar', 'Xalqlar', '2025-07-27 13:10:17', '2025-07-27 13:10:17'),
(21, 1, 0, 'ECEMI', 'ECEMI', 'ECEMI', '2025-07-27 13:21:49', '2025-07-27 13:21:49'),
(22, 1, 0, 'Narimanov', 'Narimanov', 'Narimanov', '2025-07-28 22:55:27', '2025-07-28 22:55:27');

-- --------------------------------------------------------

--
-- Структура таблицы `imtahanlar_exam`
--

CREATE TABLE `imtahanlar_exam` (
  `id` int(11) NOT NULL,
  `u_id` varchar(110) NOT NULL,
  `company_id` int(11) NOT NULL,
  `exam_name` varchar(255) NOT NULL,
  `fenn_adi` text DEFAULT NULL,
  `sinif` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `exam_date` date NOT NULL,
  `duration` int(11) NOT NULL,
  `passing_score` int(11) NOT NULL,
  `groups` text NOT NULL,
  `questions` text DEFAULT NULL,
  `status` enum('upcoming','completed','active') NOT NULL,
  `movzular` text DEFAULT NULL,
  `sual_secimi` enum('manual','random') DEFAULT NULL,
  `sual_sayi` int(11) DEFAULT NULL,
  `cetinlik_seviyyesi` text DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `imtahan_melumat`
--

CREATE TABLE `imtahan_melumat` (
  `id` int(11) NOT NULL,
  `u_id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `imtahan_neticeler`
--

CREATE TABLE `imtahan_neticeler` (
  `id` int(11) NOT NULL,
  `u_id` varchar(255) NOT NULL,
  `company_id` int(11) DEFAULT NULL,
  `imtahan_id` int(11) DEFAULT NULL,
  `telebe_id` int(11) DEFAULT NULL,
  `telebe_adi` varchar(1000) NOT NULL,
  `dogru_cavablar` int(11) DEFAULT NULL,
  `sehv_cavablar` int(11) DEFAULT NULL,
  `umumui_sual_sayi` int(11) DEFAULT NULL,
  `faiz` double DEFAULT NULL,
  `kecid_statusu` varchar(50) DEFAULT NULL,
  `cavablar` text DEFAULT NULL,
  `baslama_vaxti` datetime DEFAULT NULL,
  `bitme_vaxti` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `imtahan_nezaret`
--

CREATE TABLE `imtahan_nezaret` (
  `id` int(11) NOT NULL,
  `u_id` varchar(110) NOT NULL,
  `company_id` int(11) NOT NULL,
  `username` varchar(1000) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `ixtisas`
--

CREATE TABLE `ixtisas` (
  `id` int(11) NOT NULL,
  `u_id` int(11) DEFAULT NULL,
  `company_id` int(11) NOT NULL,
  `ixtisas_adi` varchar(255) NOT NULL,
  `ixtisas_id` int(11) NOT NULL,
  `ixtisas_kodu` varchar(50) NOT NULL,
  `fakulte` varchar(100) DEFAULT NULL,
  `tehsil_seviyyesi` varchar(50) NOT NULL,
  `tesvir` text DEFAULT NULL,
  `sekil` varchar(255) DEFAULT NULL,
  `sekil_type` varchar(50) DEFAULT 'placeholder',
  `active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `ixtisas`
--

INSERT INTO `ixtisas` (`id`, `u_id`, `company_id`, `ixtisas_adi`, `ixtisas_id`, `ixtisas_kodu`, `fakulte`, `tehsil_seviyyesi`, `tesvir`, `sekil`, `sekil_type`, `active`, `created_at`, `updated_at`) VALUES
(21, 1, 0, 'İxtisas Adı', 0, 'İxtisas Kodu', '', 'master', 'Təsvir', '1768249809_Spotify_icon.svg.png', 'file', 1, '2026-01-12 20:30:09', NULL);

-- --------------------------------------------------------

--
-- Структура таблицы `materiallar`
--

CREATE TABLE `materiallar` (
  `id` int(11) NOT NULL,
  `u_id` varchar(110) NOT NULL,
  `company_id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `material_adi` varchar(255) NOT NULL,
  `movzu` varchar(255) DEFAULT NULL,
  `tipi` enum('document','presentation','video','image') NOT NULL,
  `file` varchar(255) NOT NULL,
  `size` int(11) NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `movzular_new`
--

CREATE TABLE `movzular_new` (
  `id` int(11) NOT NULL,
  `u_id` varchar(110) NOT NULL,
  `company_id` int(11) NOT NULL,
  `movzu_adi` varchar(300) NOT NULL,
  `fenn` varchar(300) NOT NULL,
  `fenn_id` int(11) NOT NULL,
  `tesvir` varchar(1100) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `movzular_new`
--

INSERT INTO `movzular_new` (`id`, `u_id`, `company_id`, `movzu_adi`, `fenn`, `fenn_id`, `tesvir`, `created_at`, `updated_at`) VALUES
(55, '1', 0, 'test2', 'IKT', 18, '123', '2025-07-19 23:24:43', '2025-07-19 23:24:43'),
(56, '1', 0, 'test movzu', 'İxtisas Adı', 21, 'salam', '2025-12-24 01:48:00', '2026-01-15 15:41:58');

-- --------------------------------------------------------

--
-- Структура таблицы `muellimler_new`
--

CREATE TABLE `muellimler_new` (
  `id` int(11) NOT NULL,
  `u_id` varchar(8) NOT NULL,
  `company_id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `fenn` varchar(100) NOT NULL,
  `active_status` enum('active','inactive') DEFAULT 'active',
  `email` varchar(100) NOT NULL,
  `telefon` varchar(20) DEFAULT NULL,
  `tecrube` text DEFAULT NULL,
  `ise_baslama_tarixi` date DEFAULT NULL,
  `unvan` text DEFAULT NULL,
  `tehsil_ve_ixtisas` varchar(255) NOT NULL,
  `profile` varchar(255) DEFAULT NULL,
  `qr_code` varchar(255) DEFAULT NULL,
  `filial_adi` longtext NOT NULL,
  `filial_adi_second` varchar(2000) NOT NULL,
  `telebe_limit` varchar(2000) NOT NULL,
  `telebeler` longtext NOT NULL,
  `cedvel` longtext NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `muellimler_new`
--

INSERT INTO `muellimler_new` (`id`, `u_id`, `company_id`, `username`, `fenn`, `active_status`, `email`, `telefon`, `tecrube`, `ise_baslama_tarixi`, `unvan`, `tehsil_ve_ixtisas`, `profile`, `qr_code`, `filial_adi`, `filial_adi_second`, `telebe_limit`, `telebeler`, `cedvel`, `created_at`, `updated_at`) VALUES
(49, '519e44d4', 0, 'Omar.Muellim', 'null', 'active', 'omarlezgin05@gmail.com', '0705907426', '2', '2003-06-10', 'Ünvan', 'İxtisas Adı', 'profile_696559e9c54fe.png', 'qr_696559e9c615a.png', '[\"ECEMI\",\"Narimanov\"]', '', '', '[[\"ECEMI\",\"08:00\",\"Bazar\",\"ccczc.add\"],[\"ECEMI\",\"08:00\",\"Bazar ertəsi\",\"ccczc.add\"]]', '[[\"ECEMI\",\"08:00\",\"Bazar ertəsi\",\"\"],[\"ECEMI\",\"08:00\",\"Çərşənbə axşamı\",\"\"],[\"ECEMI\",\"08:00\",\"Çərşənbə\",\"\"],[\"ECEMI\",\"08:00\",\"Cümə axşamı\",\"\"],[\"ECEMI\",\"08:00\",\"Cümə\",\"\"],[\"ECEMI\",\"08:00\",\"Şənbə\",\"\"],[\"ECEMI\",\"08:00\",\"Bazar\",\"\"]]', '2026-01-13 00:30:33', '2026-01-13 01:01:52');

-- --------------------------------------------------------

--
-- Структура таблицы `otaqlar`
--

CREATE TABLE `otaqlar` (
  `id` int(11) NOT NULL,
  `u_id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `otaq_number` varchar(50) NOT NULL,
  `tutum` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `qeydiyyatar`
--

CREATE TABLE `qeydiyyatar` (
  `id` int(11) NOT NULL,
  `u_id` varchar(110) NOT NULL,
  `company_id` int(11) NOT NULL,
  `telebe_ad_soyad` varchar(100) NOT NULL,
  `baslama_tarixi` date NOT NULL,
  `tehsil_haqqi` decimal(10,2) NOT NULL,
  `odenis_novu` enum('paket','ayliq') NOT NULL DEFAULT 'paket',
  `ilkin_odenis` decimal(10,2) NOT NULL,
  `qeydiyyat_tarixi` datetime NOT NULL DEFAULT current_timestamp(),
  `tedris_ili` varchar(20) NOT NULL,
  `ders_sayi` int(200) DEFAULT NULL,
  `vetandasliq` varchar(100) NOT NULL,
  `muellim_adi` varchar(100) NOT NULL,
  `ixtisas_adi` varchar(100) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `form_ad_soyad` varchar(200) NOT NULL,
  `form_ata_adi` varchar(100) DEFAULT NULL,
  `form_universitet` varchar(200) DEFAULT NULL,
  `form_ixtisas` varchar(200) DEFAULT NULL,
  `form_qebul_ili` varchar(20) DEFAULT NULL,
  `form_dogum_tarixi` date DEFAULT NULL,
  `form_is_nomresi` varchar(50) DEFAULT NULL,
  `form_telefon` varchar(50) DEFAULT NULL,
  `form_fin_kod` varchar(7) DEFAULT NULL,
  `form_email` varchar(100) DEFAULT NULL,
  `form_bakalavr_bali` varchar(20) DEFAULT NULL,
  `form_magistr_bali` varchar(20) DEFAULT NULL,
  `form_bolme` varchar(50) DEFAULT NULL,
  `form_tedris` varchar(50) DEFAULT NULL,
  `form_vaxt` text DEFAULT NULL,
  `form_services` text DEFAULT NULL,
  `form_sinif_qeyd` varchar(50) DEFAULT NULL,
  `form_menbe` text DEFAULT NULL,
  `form_elave_qeyd_1` text DEFAULT NULL,
  `form_elave_qeyd_2` text DEFAULT NULL,
  `form_elave_qeyd_3` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `qeydiyyatar`
--

INSERT INTO `qeydiyyatar` (`id`, `u_id`, `company_id`, `telebe_ad_soyad`, `baslama_tarixi`, `tehsil_haqqi`, `odenis_novu`, `ilkin_odenis`, `qeydiyyat_tarixi`, `tedris_ili`, `ders_sayi`, `vetandasliq`, `muellim_adi`, `ixtisas_adi`, `created_at`, `updated_at`, `form_ad_soyad`, `form_ata_adi`, `form_universitet`, `form_ixtisas`, `form_qebul_ili`, `form_dogum_tarixi`, `form_is_nomresi`, `form_telefon`, `form_fin_kod`, `form_email`, `form_bakalavr_bali`, `form_magistr_bali`, `form_bolme`, `form_tedris`, `form_vaxt`, `form_services`, `form_sinif_qeyd`, `form_menbe`, `form_elave_qeyd_1`, `form_elave_qeyd_2`, `form_elave_qeyd_3`) VALUES
(134, 'A3QhzB1nCvrm', 0, 'New.News', '0000-00-00', 0.00, 'paket', 0.00, '2026-01-13 00:52:34', '', NULL, '', '', '', '2026-01-13 00:52:34', '2026-01-13 00:52:34', '', 'Mahir', 'idk', 'salam', '2026', '2003-06-10', '+994709907426', '+994709907426', 'ABC1234', 'omarlezgin05@gmail.com', '32', '45', 'azerbaycan', 'enenevi', '[\"seher\",\"axsam\"]', '[\"\\u0130nformatika\",\"\\u0130ngilis\",\"Rus\",\"Alman\"]', '', '[\"dostlar\"]', 'wq', 'wq', 'wq'),
(135, 'Bfl7sIQGHKqw', 0, 'cvxv.xvv', '0000-00-00', 0.00, 'paket', 0.00, '2026-01-13 00:55:29', '', NULL, '', '', '', '2026-01-13 00:55:29', '2026-01-13 00:55:29', '', 'vxv', 'vxv', 'vv', '2026', '2026-02-08', '+994709907426', '+994709907426', 'ABC1234', 'omarlezgin05@gmail.com', '3232', '2', '', '', '[]', '[]', '', '[]', '', '', ''),
(136, 'oeKiGg45Q1hD', 0, 'ccczc.add', '0000-00-00', 0.00, 'paket', 0.00, '2026-01-13 00:57:48', '', NULL, '', '', '', '2026-01-13 00:57:48', '2026-01-13 00:57:48', '', 'ada', 'c', 'zcczc', '2026', '2026-01-20', '+994709907426', '+994709907426', 'ABC1234', 'omarlezgin05@gmail.com', '32', '45', '', '', '[]', '[]', '212', '[]', '', '', ''),
(138, 'ojntQ3vRGc1l', 0, 'ccczc12.add212', '0000-00-00', 0.00, 'paket', 0.00, '2026-01-13 00:59:11', '', NULL, '', '', '', '2026-01-13 00:59:11', '2026-01-13 00:59:11', '', 'ada212', 'cda', 'zcczcdda', '2026', '2003-06-10', '+994709907426', '+994709907426', 'ABC1234', 'omarlezgin05@gmail.com', '32', '45', 'rus', 'onlayn', '[]', '[\"Abituriyent\",\"Blok\"]', 'A12', '[\"dostlar\",\"telebeleden\"]', '', '', '');

-- --------------------------------------------------------

--
-- Структура таблицы `qruplar`
--

CREATE TABLE `qruplar` (
  `id` int(11) NOT NULL,
  `u_id` varchar(110) NOT NULL,
  `company_id` int(11) NOT NULL,
  `qrup_adi` varchar(100) NOT NULL,
  `telebe_sayi` int(11) NOT NULL,
  `gunler` varchar(255) NOT NULL,
  `muellim_adi` varchar(1000) NOT NULL,
  `muellim_u_id` varchar(110) NOT NULL,
  `telebe_adi` varchar(1000) NOT NULL,
  `telebe_id` varchar(1000) NOT NULL,
  `tarix` date NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `qruplar`
--

INSERT INTO `qruplar` (`id`, `u_id`, `company_id`, `qrup_adi`, `telebe_sayi`, `gunler`, `muellim_adi`, `muellim_u_id`, `telebe_adi`, `telebe_id`, `tarix`, `created_at`, `updated_at`) VALUES
(13, '1', 0, 'salam', 12, 'Bazar ertəsi', '', '', '', '', '2026-01-15', '2026-01-15 12:43:58', '2026-01-15 15:43:58');

-- --------------------------------------------------------

--
-- Структура таблицы `qr_scans`
--

CREATE TABLE `qr_scans` (
  `id` int(11) NOT NULL,
  `u_id` varchar(110) NOT NULL,
  `company_id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `teacher_username` varchar(100) NOT NULL,
  `teacher_fenn` varchar(100) DEFAULT NULL,
  `student_username` varchar(100) NOT NULL,
  `student_u_id` varchar(150) NOT NULL,
  `scan_date` date NOT NULL,
  `scan_time` datetime NOT NULL,
  `lesson_count` int(200) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `sinifler`
--

CREATE TABLE `sinifler` (
  `id` int(11) NOT NULL,
  `u_id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `sinif_number` varchar(200) NOT NULL,
  `tutum` varchar(200) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `sinifler`
--

INSERT INTO `sinifler` (`id`, `u_id`, `company_id`, `sinif_number`, `tutum`, `created_at`, `updated_at`) VALUES
(1, 1, 1, '11A', '12', '2025-06-29 19:38:40', '2025-06-29 19:38:40');

-- --------------------------------------------------------

--
-- Структура таблицы `sual_banki`
--

CREATE TABLE `sual_banki` (
  `id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `subject` int(11) NOT NULL,
  `topic` int(11) NOT NULL,
  `question_type` varchar(50) NOT NULL,
  `question_text` text DEFAULT NULL,
  `question_image` text DEFAULT NULL,
  `options` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`options`)),
  `correct_answer` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`correct_answer`)),
  `difficulty` int(11) NOT NULL,
  `image_path` text DEFAULT NULL,
  `u_id` varchar(110) NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `sual_banki`
--

INSERT INTO `sual_banki` (`id`, `company_id`, `subject`, `topic`, `question_type`, `question_text`, `question_image`, `options`, `correct_answer`, `difficulty`, `image_path`, `u_id`, `created_at`, `updated_at`) VALUES
(15, 0, 11, 56, 'multiple_choice', '<p>salam </p>', 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQAAAQABAAD/4gHYSUNDX1BST0ZJTEUAAQEAAAHIAAAAAAQwAABtbnRyUkdCIFhZWiAH4AABAAEAAAAAAABhY3NwAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAQAA9tYAAQAAAADTLQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAlkZXNjAAAA8AAAACRyWFlaAAABFAAAABRnWFlaAAABKAAAABRiWFlaAAABPAAAABR3dHB0AAABUAAAABRyVFJDAAABZAAAAChnVFJDAAABZAAAAChiVFJDAAABZAAAAChjcHJ0AAABjAAAADxtbHVjAAAAAAAAAAEAAAAMZW5VUwAAAAgAAAAcAHMAUgBHAEJYWVogAAAAAAAAb6IAADj1AAADkFhZWiAAAAAAAABimQAAt4UAABjaWFlaIAAAAAAAACSgAAAPhAAAts9YWVogAAAAAAAA9tYAAQAAAADTLXBhcmEAAAAAAAQAAAACZmYAAPKnAAANWQAAE9AAAApbAAAAAAAAAABtbHVjAAAAAAAAAAEAAAAMZW5VUwAAACAAAAAcAEcAbwBvAGcAbABlACAASQBuAGMALgAgADIAMAAxADb/2wBDAAYEBQYFBAYGBQYHBwYIChAKCgkJChQODwwQFxQYGBcUFhYaHSUfGhsjHBYWICwgIyYnKSopGR8tMC0oMCUoKSj/2wBDAQcHBwoIChMKChMoGhYaKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCj/wAARCAKtBLADASIAAhEBAxEB/8QAHQABAAEFAQEBAAAAAAAAAAAAAAECAwYHCAUECf/EAFgQAQABAwICBQQKDgYHBwQDAAABAgMEBREGIQcSMUFRCGGRkhMVFyJSVHGBsdEUFiMyN1Vyc3STobKzwTVCU2KU4RglMzZEVsImJzRDRWSERmOCoqPS8P/EABsBAQEAAwEBAQAAAAAAAAAAAAABAgMEBQYH/8QANBEBAAEDAQYDBwQCAgMAAAAAAAECAxEEBRITITFSFUFRFBYiMnGRoQZTYYEzQiTRQ7HB/9oADAMBAAIRAxEAPwDmMB7TnE9yE9yIgAAAAAAAAAAAAAAAAAAAAAyAAAAAAAAAAABkADIAAAAAAAGQAMgAZAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAyABkAAADIAAAGQAAAAAMgAZAAyAAAAAAAAAJBAAAAACAAuQAAAAAAAAAAAAAAAAAAAAAAAAAAABIAIAFABAAAAABAAUAAAAAAANgAEABQAQAFAAAAAAABAAAAAAAAAAAAAAAAAAUAAAAAAAAAAAAAAAAAAAAAAAAAEAAAAABQAQAAAAAAAAAAAAAAAFAAAAAAAAAAABAAAAAAAAAAAAAAAAUAAAAAAAAAEAAABQABIhO4IAFABAAABAAUAAAANjZO6ANjZO5uABsCAJQATIIAUAAAAAAAAAAAAAAAAAEAAAAAAABQAAAQAFABAAUAEAAABQAAAAAAAAAQAFABAAAAAAAAAAAAUAEAAAAAAABQAAAAAQAFABAAAAAAAAUAEAAABQAQAAAFAAABAAAAAAAAAAAAAAUkAAAAAQAFAAAAAABMITABuSgAkACQAAAAAAAAAAAAAAAAAAQAAAAAAADAAKAAAAAAACAAAAAAAAAAAAAAoAAAAAAAIAAAAAAACgAgAAAAAAAAAAAAAAAAAAAAAAAAAKAnY2BAAAAACAAAAAAAAoAAAAAAAIAAAAAAAAACkgAAAAAAAAAAAAACYQAmUAAAAAAAAAAAAAAAAAAAAAgAAAAAKAAAnY2BAAAAAAACAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAoAIAAAAAAAAAAAAAAAJ2AA3UQAAAAAAAAAgAAAKAAAAAAACAAAAAAAAAAqgAgAAAAAAAAAAAAAAAAAACYBAnZAAAAAAAAAAAAAACAAAAAAonZGyYAAJBAlAAAAAAAACAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAoAIAAAAAAAAAAAAAAAAAACUJgBCZQoAAAAAAAIAAACgAgAKABIAIAAAAAAAAAAACqACAAAAAAAAAAAAAAAAAACYQmAESlEgAAAAAAAAAAAAAIAAAAAAJg3QlQAAlCZQAAAAgAAAAAAAAAAAAAAAAAAAkECQECQECQEAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAJQlQQlAAAAAAAACAAAAAAAAAAAAAAAAAAAAAAAjvSKoAIAAAIAAAAAAACgAAAAAAAAmEAEgAAAAAAAAIAAAAAAAAAAAAEJlAAAAAAAA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', '[{\"text\":\"<p>1<\\/p>\",\"isCorrect\":false},{\"text\":\"<p>1<\\/p>\",\"isCorrect\":true}]', '0', 1, '0', '1', '2026-01-15 15:42:23', '2026-01-15 15:42:23');

-- --------------------------------------------------------

--
-- Структура таблицы `tapsiriqlar`
--

CREATE TABLE `tapsiriqlar` (
  `id` int(11) NOT NULL,
  `u_id` varchar(110) NOT NULL,
  `company_id` int(11) NOT NULL,
  `ad` varchar(255) NOT NULL,
  `movzu` int(11) NOT NULL,
  `qrup` int(11) NOT NULL,
  `tesvir` text DEFAULT NULL,
  `son_tarix` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `yaradilma_tarixi` datetime NOT NULL,
  `fayllar` text DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `telebeler`
--

CREATE TABLE `telebeler` (
  `id` int(10) UNSIGNED NOT NULL,
  `u_id` varchar(110) NOT NULL,
  `company_id` int(11) NOT NULL,
  `username` varchar(1000) NOT NULL,
  `number` varchar(200) DEFAULT NULL,
  `poct` varchar(100) DEFAULT NULL,
  `active_status` varchar(50) DEFAULT NULL,
  `dogum_tarixi` date DEFAULT NULL,
  `years` int(11) DEFAULT NULL,
  `cins` tinyint(1) DEFAULT NULL,
  `unvan` text DEFAULT NULL,
  `sinif` varchar(5000) DEFAULT NULL,
  `vetandasliq` varchar(1000) DEFAULT NULL,
  `qebul_tarixi` date DEFAULT NULL,
  `ata` varchar(100) DEFAULT NULL,
  `elaqe_nomre_ata` varchar(200) DEFAULT NULL,
  `ana` varchar(100) DEFAULT NULL,
  `elaqe_nomre_ana` varchar(200) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `muellim_adi` varchar(1000) NOT NULL,
  `ixtisas_adi` varchar(1000) NOT NULL,
  `orta_bal` varchar(1000) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `davamiyyet` varchar(200) NOT NULL,
  `status` varchar(200) NOT NULL,
  `cedvel` longtext NOT NULL,
  `riyaziyyat` varchar(200) NOT NULL,
  `fizika` varchar(200) NOT NULL,
  `kimya` varchar(200) NOT NULL,
  `biologiya` varchar(200) NOT NULL,
  `tarix` varchar(200) NOT NULL,
  `edebiyyat` varchar(200) NOT NULL,
  `qeyd` varchar(200) NOT NULL,
  `reg_ad_soyad` varchar(200) NOT NULL,
  `reg_ata_adi` varchar(100) DEFAULT NULL,
  `reg_universitet` varchar(200) DEFAULT NULL,
  `reg_ixtisas` varchar(200) DEFAULT NULL,
  `reg_qebul_ili` varchar(20) DEFAULT NULL,
  `reg_dogum_tarixi` date DEFAULT NULL,
  `reg_is_nomresi` varchar(50) DEFAULT NULL,
  `reg_telefon` varchar(50) DEFAULT NULL,
  `reg_fin_kod` varchar(7) DEFAULT NULL,
  `reg_email` varchar(100) DEFAULT NULL,
  `reg_bakalavr_bali` varchar(20) DEFAULT NULL,
  `reg_magistr_bali` varchar(20) DEFAULT NULL,
  `reg_bolme` varchar(50) DEFAULT NULL,
  `reg_tedris` varchar(50) DEFAULT NULL,
  `reg_vaxt` text DEFAULT NULL,
  `reg_services` text DEFAULT NULL,
  `reg_sinif_qeyd` varchar(50) DEFAULT NULL,
  `reg_menbe` text DEFAULT NULL,
  `reg_elave_qeyd_1` text DEFAULT NULL,
  `reg_elave_qeyd_2` text DEFAULT NULL,
  `reg_elave_qeyd_3` text DEFAULT NULL,
  `reg_years` varchar(1100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `telebeler`
--

INSERT INTO `telebeler` (`id`, `u_id`, `company_id`, `username`, `number`, `poct`, `active_status`, `dogum_tarixi`, `years`, `cins`, `unvan`, `sinif`, `vetandasliq`, `qebul_tarixi`, `ata`, `elaqe_nomre_ata`, `ana`, `elaqe_nomre_ana`, `photo`, `muellim_adi`, `ixtisas_adi`, `orta_bal`, `created_at`, `updated_at`, `davamiyyet`, `status`, `cedvel`, `riyaziyyat`, `fizika`, `kimya`, `biologiya`, `tarix`, `edebiyyat`, `qeyd`, `reg_ad_soyad`, `reg_ata_adi`, `reg_universitet`, `reg_ixtisas`, `reg_qebul_ili`, `reg_dogum_tarixi`, `reg_is_nomresi`, `reg_telefon`, `reg_fin_kod`, `reg_email`, `reg_bakalavr_bali`, `reg_magistr_bali`, `reg_bolme`, `reg_tedris`, `reg_vaxt`, `reg_services`, `reg_sinif_qeyd`, `reg_menbe`, `reg_elave_qeyd_1`, `reg_elave_qeyd_2`, `reg_elave_qeyd_3`, `reg_years`) VALUES
(97, 'A3QhzB1nCvrm', 0, 'New.News', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', '', '', '2026-01-13 00:52:34', '2026-01-13 00:52:34', '', '', '', '', '', '', '', '', '', '', '', 'Mahir', 'idk', 'salam', '2026', '2003-06-10', '+994709907426', '+994709907426', 'ABC1234', 'omarlezgin05@gmail.com', '32', '45', 'azerbaycan', 'enenevi', '[\"seher\",\"axsam\"]', '[\"\\u0130nformatika\",\"\\u0130ngilis\",\"Rus\",\"Alman\"]', '', '[\"dostlar\"]', 'wq', 'wq', 'wq', '22'),
(98, 'Bfl7sIQGHKqw', 0, 'cvxv.xvv', NULL, NULL, 'active', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', '', '', '2026-01-13 00:55:29', '2026-01-13 00:55:29', '', '', '', '', '', '', '', '', '', '', '', 'vxv', 'vxv', 'vv', '2026', '2026-02-08', '+994709907426', '+994709907426', 'ABC1234', 'omarlezgin05@gmail.com', '3232', '2', '', '', '[]', '[]', '', '[]', '', '', '', '0'),
(99, 'oeKiGg45Q1hD', 0, 'ccczc.add', NULL, NULL, 'active', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '[\"Omar.Muellim\"]', '', '', '2026-01-13 00:57:48', '2026-01-13 01:01:53', '', '', '', '', '', '', '', '', '', '', '', 'ada', 'c', 'zcczc', '2026', '2026-01-20', '+994709907426', '+994709907426', 'ABC1234', 'omarlezgin05@gmail.com', '32', '45', '', '', '[]', '[]', '212', '[]', '', '', '', '0'),
(101, 'ojntQ3vRGc1l', 0, 'ccczc12.add212', NULL, NULL, 'active', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', '', '', '2026-01-13 00:59:11', '2026-01-13 00:59:11', '', '', '', '', '', '', '', '', '', '', '', 'ada212', 'cda', 'zcczcdda', '2026', '2003-06-10', '+994709907426', '+994709907426', 'ABC1234', 'omarlezgin05@gmail.com', '32', '45', 'rus', 'onlayn', '[]', '[\"Abituriyent\",\"Blok\"]', 'A12', '[\"dostlar\",\"telebeleden\"]', '', '', '', '22');

-- --------------------------------------------------------

--
-- Структура таблицы `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('super_admin','admin','staff','teacher','student','examiner','parent') NOT NULL,
  `company_id` int(11) DEFAULT NULL,
  `u_id` varchar(110) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`, `company_id`, `u_id`, `created_at`, `updated_at`) VALUES
(1, 'Omar', '1', 'super_admin', 1, '1', '2025-06-11 18:38:24', '2025-06-11 22:36:09'),
(105, 'demo', '123456', 'student', 3020, '964855', '2025-07-16 20:52:46', '2025-07-16 20:53:09'),
(238, '', 'eP7**1P', 'teacher', NULL, '519e44d4', '2026-01-13 00:30:33', '2026-01-15 15:56:29'),
(242, 'New.News', 'Rhslx81J', 'student', NULL, 'A3QhzB1nCvrm', '2026-01-13 00:52:34', '2026-01-13 00:52:34'),
(243, 'cvxv.xvv', 'o10U5Lhe', 'student', NULL, 'Bfl7sIQGHKqw', '2026-01-13 00:55:29', '2026-01-13 00:55:29'),
(244, 'ccczc.add', 'eNrspSlb', 'student', NULL, 'oeKiGg45Q1hD', '2026-01-13 00:57:48', '2026-01-13 00:57:48'),
(246, 'ccczc12.add212', 'AFO5rBxw', 'student', NULL, 'ojntQ3vRGc1l', '2026-01-13 00:59:11', '2026-01-13 00:59:11');

-- --------------------------------------------------------

--
-- Структура таблицы `user_devices`
--

CREATE TABLE `user_devices` (
  `id` int(11) NOT NULL,
  `u_id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `device_hash` varchar(64) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text NOT NULL,
  `device_model` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `user_devices`
--

INSERT INTO `user_devices` (`id`, `u_id`, `company_id`, `user_id`, `device_hash`, `ip_address`, `user_agent`, `device_model`, `created_at`, `updated_at`) VALUES
(98, 0, 0, 1, '8e77f98f58a293e6379d1296ae2022a34fad2048', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'Windows PC', '2025-12-17 10:32:20', '2025-12-17 14:32:20'),
(100, 0, 0, 244, '8e77f98f58a293e6379d1296ae2022a34fad2048', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'Windows PC', '2026-01-12 21:04:37', '2026-01-13 01:04:37'),
(101, 0, 0, 246, '8e77f98f58a293e6379d1296ae2022a34fad2048', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'Windows PC', '2026-01-13 14:47:04', '2026-01-13 18:47:04'),
(102, 0, 0, 238, '8e77f98f58a293e6379d1296ae2022a34fad2048', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'Windows PC', '2026-01-15 11:45:09', '2026-01-15 15:45:09');

-- --------------------------------------------------------

--
-- Структура таблицы `user_permissions`
--

CREATE TABLE `user_permissions` (
  `id` int(11) NOT NULL,
  `u_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `permissions` text DEFAULT NULL,
  `company_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `user_permissions`
--

INSERT INTO `user_permissions` (`id`, `u_id`, `user_id`, `permissions`, `company_id`, `created_at`, `updated_at`) VALUES
(13, 0, 105, '[\"Elanlar\",\"Academic Calendar Telebe\",\"Elektron jurnal\",\"Apellyasiya\"]', 3020, '2025-07-16 17:53:09', '2025-07-24 19:23:45'),
(20, 0, 238, '[\"Mövzular\"]', NULL, '2026-01-15 11:46:21', '2026-01-15 11:46:21');

-- --------------------------------------------------------

--
-- Структура таблицы `user_sessions`
--

CREATE TABLE `user_sessions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `session_id` varchar(255) NOT NULL,
  `username` varchar(200) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `user_sessions`
--

INSERT INTO `user_sessions` (`id`, `user_id`, `session_id`, `username`, `ip_address`, `user_agent`, `created_at`, `expires_at`) VALUES
(314, 244, 'inct0sk67nut3oui2g2tluml62', 'ccczc.add', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-12 21:04:37', '2026-01-13 22:04:37'),
(316, 246, 'u756iqv8ehpevfh8991940knoj', 'ccczc12.add212', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-13 14:47:04', '2026-01-14 15:47:04'),
(318, 1, 'uav5cdu6i7tfccndanobb56bci', 'Omar', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-15 11:45:25', '2026-01-16 12:45:25'),
(319, 238, '8ve44pmpgs4lt93bo51o91pdoo', 'Omar.Muellim', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-15 11:55:45', '2026-01-16 12:55:45');

-- --------------------------------------------------------

--
-- Структура таблицы `valideyn`
--

CREATE TABLE `valideyn` (
  `id` int(11) NOT NULL,
  `u_id` varchar(255) NOT NULL,
  `telebe_name` varchar(255) NOT NULL,
  `parent_name` varchar(255) NOT NULL,
  `parent_type` enum('Ana','Ata','Qəyyum') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `vetandasliq`
--

CREATE TABLE `vetandasliq` (
  `id` int(11) NOT NULL,
  `country_name` varchar(100) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `vetandasliq`
--

INSERT INTO `vetandasliq` (`id`, `country_name`, `created_at`, `updated_at`) VALUES
(1, 'Azerbaijan', '2025-06-11 22:08:53', '2025-06-12 00:08:53');

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `davamiyyet`
--
ALTER TABLE `davamiyyet`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `dersler`
--
ALTER TABLE `dersler`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `elanlar`
--
ALTER TABLE `elanlar`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `emekdaslar`
--
ALTER TABLE `emekdaslar`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `fennler_new`
--
ALTER TABLE `fennler_new`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `filiallar`
--
ALTER TABLE `filiallar`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `imtahanlar_exam`
--
ALTER TABLE `imtahanlar_exam`
  ADD PRIMARY KEY (`id`),
  ADD KEY `u_id` (`u_id`);

--
-- Индексы таблицы `imtahan_melumat`
--
ALTER TABLE `imtahan_melumat`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `imtahan_neticeler`
--
ALTER TABLE `imtahan_neticeler`
  ADD PRIMARY KEY (`id`),
  ADD KEY `imtahan_neticeler_uid_fk` (`u_id`);

--
-- Индексы таблицы `imtahan_nezaret`
--
ALTER TABLE `imtahan_nezaret`
  ADD PRIMARY KEY (`id`),
  ADD KEY `u_id` (`u_id`);

--
-- Индексы таблицы `ixtisas`
--
ALTER TABLE `ixtisas`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `materiallar`
--
ALTER TABLE `materiallar`
  ADD PRIMARY KEY (`id`),
  ADD KEY `materiallar` (`u_id`);

--
-- Индексы таблицы `movzular_new`
--
ALTER TABLE `movzular_new`
  ADD PRIMARY KEY (`id`),
  ADD KEY `u_id` (`u_id`);

--
-- Индексы таблицы `muellimler_new`
--
ALTER TABLE `muellimler_new`
  ADD PRIMARY KEY (`id`),
  ADD KEY `u_id` (`u_id`);

--
-- Индексы таблицы `otaqlar`
--
ALTER TABLE `otaqlar`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `qeydiyyatar`
--
ALTER TABLE `qeydiyyatar`
  ADD PRIMARY KEY (`id`),
  ADD KEY `u_id` (`u_id`) USING BTREE;

--
-- Индексы таблицы `qruplar`
--
ALTER TABLE `qruplar`
  ADD PRIMARY KEY (`id`),
  ADD KEY `qruplar` (`u_id`);

--
-- Индексы таблицы `qr_scans`
--
ALTER TABLE `qr_scans`
  ADD PRIMARY KEY (`id`),
  ADD KEY `qr_scans` (`u_id`);

--
-- Индексы таблицы `sinifler`
--
ALTER TABLE `sinifler`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `sual_banki`
--
ALTER TABLE `sual_banki`
  ADD PRIMARY KEY (`id`),
  ADD KEY `subject` (`subject`),
  ADD KEY `topic` (`topic`),
  ADD KEY `u_id` (`u_id`);

--
-- Индексы таблицы `tapsiriqlar`
--
ALTER TABLE `tapsiriqlar`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tapsiriqlar` (`u_id`);

--
-- Индексы таблицы `telebeler`
--
ALTER TABLE `telebeler`
  ADD PRIMARY KEY (`id`),
  ADD KEY `u_id` (`u_id`);

--
-- Индексы таблицы `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_unique_u_id` (`u_id`),
  ADD KEY `u_id` (`u_id`);

--
-- Индексы таблицы `user_devices`
--
ALTER TABLE `user_devices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_agent` (`user_id`,`user_agent`(255));

--
-- Индексы таблицы `user_permissions`
--
ALTER TABLE `user_permissions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Индексы таблицы `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Индексы таблицы `valideyn`
--
ALTER TABLE `valideyn`
  ADD PRIMARY KEY (`id`),
  ADD KEY `u_id` (`u_id`);

--
-- Индексы таблицы `vetandasliq`
--
ALTER TABLE `vetandasliq`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `davamiyyet`
--
ALTER TABLE `davamiyyet`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `dersler`
--
ALTER TABLE `dersler`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT для таблицы `elanlar`
--
ALTER TABLE `elanlar`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT для таблицы `emekdaslar`
--
ALTER TABLE `emekdaslar`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT для таблицы `fennler_new`
--
ALTER TABLE `fennler_new`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT для таблицы `filiallar`
--
ALTER TABLE `filiallar`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT для таблицы `imtahanlar_exam`
--
ALTER TABLE `imtahanlar_exam`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT для таблицы `imtahan_melumat`
--
ALTER TABLE `imtahan_melumat`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `imtahan_neticeler`
--
ALTER TABLE `imtahan_neticeler`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT для таблицы `imtahan_nezaret`
--
ALTER TABLE `imtahan_nezaret`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `ixtisas`
--
ALTER TABLE `ixtisas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT для таблицы `materiallar`
--
ALTER TABLE `materiallar`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT для таблицы `movzular_new`
--
ALTER TABLE `movzular_new`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=57;

--
-- AUTO_INCREMENT для таблицы `muellimler_new`
--
ALTER TABLE `muellimler_new`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- AUTO_INCREMENT для таблицы `otaqlar`
--
ALTER TABLE `otaqlar`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `qeydiyyatar`
--
ALTER TABLE `qeydiyyatar`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=139;

--
-- AUTO_INCREMENT для таблицы `qruplar`
--
ALTER TABLE `qruplar`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT для таблицы `qr_scans`
--
ALTER TABLE `qr_scans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT для таблицы `sinifler`
--
ALTER TABLE `sinifler`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT для таблицы `sual_banki`
--
ALTER TABLE `sual_banki`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT для таблицы `tapsiriqlar`
--
ALTER TABLE `tapsiriqlar`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT для таблицы `telebeler`
--
ALTER TABLE `telebeler`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=102;

--
-- AUTO_INCREMENT для таблицы `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=247;

--
-- AUTO_INCREMENT для таблицы `user_devices`
--
ALTER TABLE `user_devices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=103;

--
-- AUTO_INCREMENT для таблицы `user_permissions`
--
ALTER TABLE `user_permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT для таблицы `user_sessions`
--
ALTER TABLE `user_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=320;

--
-- AUTO_INCREMENT для таблицы `valideyn`
--
ALTER TABLE `valideyn`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT для таблицы `vetandasliq`
--
ALTER TABLE `vetandasliq`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `imtahanlar_exam`
--
ALTER TABLE `imtahanlar_exam`
  ADD CONSTRAINT `exam` FOREIGN KEY (`u_id`) REFERENCES `users` (`u_id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `imtahan_neticeler`
--
ALTER TABLE `imtahan_neticeler`
  ADD CONSTRAINT `imtahan_neticeler_uid_fk` FOREIGN KEY (`u_id`) REFERENCES `users` (`u_id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `imtahan_nezaret`
--
ALTER TABLE `imtahan_nezaret`
  ADD CONSTRAINT `imtahan_nezaret_uid_fk` FOREIGN KEY (`u_id`) REFERENCES `users` (`u_id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `materiallar`
--
ALTER TABLE `materiallar`
  ADD CONSTRAINT `materiallar` FOREIGN KEY (`u_id`) REFERENCES `users` (`u_id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `movzular_new`
--
ALTER TABLE `movzular_new`
  ADD CONSTRAINT `movzular` FOREIGN KEY (`u_id`) REFERENCES `users` (`u_id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `muellimler_new`
--
ALTER TABLE `muellimler_new`
  ADD CONSTRAINT `muellimler_new_u_id_fk` FOREIGN KEY (`u_id`) REFERENCES `users` (`u_id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `qeydiyyatar`
--
ALTER TABLE `qeydiyyatar`
  ADD CONSTRAINT `qeydiyyatar_u_id_fk` FOREIGN KEY (`u_id`) REFERENCES `users` (`u_id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `qruplar`
--
ALTER TABLE `qruplar`
  ADD CONSTRAINT `qruplar` FOREIGN KEY (`u_id`) REFERENCES `users` (`u_id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `qr_scans`
--
ALTER TABLE `qr_scans`
  ADD CONSTRAINT `qr_scans` FOREIGN KEY (`u_id`) REFERENCES `users` (`u_id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `sual_banki`
--
ALTER TABLE `sual_banki`
  ADD CONSTRAINT `sual_banki` FOREIGN KEY (`u_id`) REFERENCES `users` (`u_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sual_banki_ibfk_1` FOREIGN KEY (`subject`) REFERENCES `fennler_new` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sual_banki_ibfk_2` FOREIGN KEY (`topic`) REFERENCES `movzular_new` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `tapsiriqlar`
--
ALTER TABLE `tapsiriqlar`
  ADD CONSTRAINT `tapsiriqlar` FOREIGN KEY (`u_id`) REFERENCES `users` (`u_id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `telebeler`
--
ALTER TABLE `telebeler`
  ADD CONSTRAINT `telebeler_u_id_fk` FOREIGN KEY (`u_id`) REFERENCES `users` (`u_id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `user_devices`
--
ALTER TABLE `user_devices`
  ADD CONSTRAINT `user_devices_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `user_permissions`
--
ALTER TABLE `user_permissions`
  ADD CONSTRAINT `user_permissions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD CONSTRAINT `user_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `valideyn`
--
ALTER TABLE `valideyn`
  ADD CONSTRAINT `valideyn_u_id_fk` FOREIGN KEY (`u_id`) REFERENCES `users` (`u_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
