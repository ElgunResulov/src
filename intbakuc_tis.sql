-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Anamakine: localhost:3306
-- Üretim Zamanı: 30 Haz 2026, 19:11:43
-- Sunucu sürümü: 10.11.16-MariaDB-cll-lve-log
-- PHP Sürümü: 8.4.21

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Veritabanı: `intbakuc_tis`
--

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `davamiyyet`
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
-- Tablo için tablo yapısı `dersler`
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
-- Tablo için tablo yapısı `elanlar`
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
-- Tablo için tablo yapısı `emekdaslar`
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
-- Tablo için tablo yapısı `fennler_new`
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
-- Tablo döküm verisi `fennler_new`
--

INSERT INTO `fennler_new` (`id`, `u_id`, `company_id`, `fenn_adi`, `fenn_id`, `created_at`, `updated_at`) VALUES
(9, 0, 0, 'MƏNTİQ', 0, '2025-07-20 00:40:41', '2025-07-20 00:40:41'),
(10, 0, 0, 'IKT', 0, '2025-07-20 00:44:26', '2025-07-20 00:44:26');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `filiallar`
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

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `imtahanlar_exam`
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
-- Tablo için tablo yapısı `imtahan_melumat`
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
-- Tablo için tablo yapısı `imtahan_neticeler`
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
-- Tablo için tablo yapısı `imtahan_nezaret`
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
-- Tablo için tablo yapısı `ixtisas`
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
-- Tablo döküm verisi `ixtisas`
--

INSERT INTO `ixtisas` (`id`, `u_id`, `company_id`, `ixtisas_adi`, `ixtisas_id`, `ixtisas_kodu`, `fakulte`, `tehsil_seviyyesi`, `tesvir`, `sekil`, `sekil_type`, `active`, `created_at`, `updated_at`) VALUES
(22, 208, 0, 'Magistratura', 0, 'Mag_01', '', 'master', 'TÉ™svir', '1768635542_photo_2025-12-22_01-14-22.jpg', 'file', 1, '2026-01-17 07:39:02', NULL);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `materiallar`
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
-- Tablo için tablo yapısı `movzular_new`
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

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `muellimler_new`
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

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `otaqlar`
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
-- Tablo için tablo yapısı `qeydiyyatar`
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
  `form_ad_soyad` varchar(200) DEFAULT NULL,
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
-- Tablo döküm verisi `qeydiyyatar`
--

INSERT INTO `qeydiyyatar` (`id`, `u_id`, `company_id`, `telebe_ad_soyad`, `baslama_tarixi`, `tehsil_haqqi`, `odenis_novu`, `ilkin_odenis`, `qeydiyyat_tarixi`, `tedris_ili`, `ders_sayi`, `vetandasliq`, `muellim_adi`, `ixtisas_adi`, `created_at`, `updated_at`, `form_ad_soyad`, `form_ata_adi`, `form_universitet`, `form_ixtisas`, `form_qebul_ili`, `form_dogum_tarixi`, `form_is_nomresi`, `form_telefon`, `form_fin_kod`, `form_email`, `form_bakalavr_bali`, `form_magistr_bali`, `form_bolme`, `form_tedris`, `form_vaxt`, `form_services`, `form_sinif_qeyd`, `form_menbe`, `form_elave_qeyd_1`, `form_elave_qeyd_2`, `form_elave_qeyd_3`) VALUES
(117, 'oe9U4r7kBJ0L', 0, 'Salam.MrS', '0000-00-00', 0.00, 'paket', 0.00, '2026-01-13 10:56:49', '', NULL, '', '', '', '2026-01-13 10:56:49', '2026-01-13 10:56:49', NULL, 'Mahir', 'Azerbaycan Universiteti', 'Ä°xtisas', '2026', '2003-06-10', '0709907426', '+994 70 990 74 26', 'AZ12345', 'omarlezgin05@gmail.com', '121', '212', '', '', '[]', '[]', '21', '[]', '', '', '');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `qruplar`
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

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `qr_scans`
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
-- Tablo için tablo yapısı `sinifler`
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
-- Tablo döküm verisi `sinifler`
--

INSERT INTO `sinifler` (`id`, `u_id`, `company_id`, `sinif_number`, `tutum`, `created_at`, `updated_at`) VALUES
(1, 1, 1, '11A', '12', '2025-06-29 19:38:40', '2025-06-29 19:38:40');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `sual_banki`
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

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `tapsiriqlar`
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
-- Tablo için tablo yapısı `telebeler`
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
  `reg_ad_soyad` varchar(200) DEFAULT NULL,
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
-- Tablo döküm verisi `telebeler`
--

INSERT INTO `telebeler` (`id`, `u_id`, `company_id`, `username`, `number`, `poct`, `active_status`, `dogum_tarixi`, `years`, `cins`, `unvan`, `sinif`, `vetandasliq`, `qebul_tarixi`, `ata`, `elaqe_nomre_ata`, `ana`, `elaqe_nomre_ana`, `photo`, `muellim_adi`, `ixtisas_adi`, `orta_bal`, `created_at`, `updated_at`, `davamiyyet`, `status`, `cedvel`, `riyaziyyat`, `fizika`, `kimya`, `biologiya`, `tarix`, `edebiyyat`, `qeyd`, `reg_ad_soyad`, `reg_ata_adi`, `reg_universitet`, `reg_ixtisas`, `reg_qebul_ili`, `reg_dogum_tarixi`, `reg_is_nomresi`, `reg_telefon`, `reg_fin_kod`, `reg_email`, `reg_bakalavr_bali`, `reg_magistr_bali`, `reg_bolme`, `reg_tedris`, `reg_vaxt`, `reg_services`, `reg_sinif_qeyd`, `reg_menbe`, `reg_elave_qeyd_1`, `reg_elave_qeyd_2`, `reg_elave_qeyd_3`, `reg_years`) VALUES
(86, 'oe9U4r7kBJ0L', 0, 'Salam.MrS', NULL, NULL, 'active', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', '', '', '2026-01-13 10:56:49', '2026-01-13 10:57:40', '', 'Istirak_edir', '', '', '', '', '', '', '', 'Okeydi', NULL, 'Mahir', 'Azerbaycan Universiteti', 'Ä°xtisas', '2026', '2003-06-10', '0709907426', '+994 70 990 74 26', 'AZ12345', 'omarlezgin05@gmail.com', '121', '212', '', '', '[]', '[]', '21', '[]', '', '', '', '22');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `users`
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
-- Tablo döküm verisi `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`, `company_id`, `u_id`, `created_at`, `updated_at`) VALUES
(218, 'Salam.MrS', 'miRtDw5x', 'super_admin', NULL, 'oe9U4r7kBJ0L', '2026-01-13 10:56:49', '2026-02-02 15:24:14'),
(220, 'instagramdan mene yazÄ±n @elliotwhoami', 'n9SkYmcD', 'super_admin', 0, '559158', '2026-02-02 15:13:21', '2026-02-02 15:13:21'),
(221, 'peysel ibrahim', 'Xp9MqInv', 'super_admin', 0, '891398', '2026-02-02 15:24:50', '2026-02-12 11:36:42'),
(223, 'pekaka', 'Hy4goLLo', 'super_admin', 0, '547207', '2026-02-02 21:15:21', '2026-02-02 21:20:12'),
(228, 'asd', '48lKeZZo', 'super_admin', 0, '739676', '2026-02-05 14:33:24', '2026-02-05 14:33:24'),
(229, 'milana', 'RXddWv7l', 'student', 0, '189546', '2026-02-12 11:38:13', '2026-02-12 11:40:58');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `user_devices`
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
-- Tablo döküm verisi `user_devices`
--

INSERT INTO `user_devices` (`id`, `u_id`, `company_id`, `user_id`, `device_hash`, `ip_address`, `user_agent`, `device_model`, `created_at`, `updated_at`) VALUES
(105, 0, 0, 220, '917971810b503e2d5481b64c44d809e835c3419e', '185.146.115.56', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'Windows PC', '2026-02-02 12:13:41', '2026-02-02 15:13:41'),
(106, 0, 0, 218, '57e8a35cb33f109e093e04af2fc74216af8a80eb', '188.253.221.154', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', 'iPhone', '2026-02-02 12:21:55', '2026-02-02 15:21:55'),
(108, 0, 0, 223, '78671f6c2d9f9d6a7df1d28cf8d01ce6db821f59', '37.61.117.135', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'Windows PC', '2026-02-02 18:15:31', '2026-02-02 21:15:31'),
(109, 0, 0, 221, '539e8be8f2e138f7c1bf1a4db5da77faa1b5d6bd', '185.91.210.232', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', 'iPhone', '2026-02-12 08:38:06', '2026-02-13 14:27:06');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `user_permissions`
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
-- Tablo döküm verisi `user_permissions`
--

INSERT INTO `user_permissions` (`id`, `u_id`, `user_id`, `permissions`, `company_id`, `created_at`, `updated_at`) VALUES
(20, 0, 229, '[\"Elanlar\",\"Academic Calendar Telebe\",\"DÉ™rs CÉ™dvÉ™li Telebe\",\"Zoom cÉ™dvÉ™li\",\"Ä°mtahan cÉ™dvÉ™li\",\"Ä°mtahan nÉ™ticÉ™lÉ™ri\",\"Elektron jurnal\",\"TÉ™dris materiallarÄ±\",\"MÉ™mnunluq anketi\",\"Apellyasiya\",\"Ä°mtahan SuallarÄ±\",\"SÉ™rbÉ™st iÅŸlÉ™r\"]', 0, '2026-02-12 08:38:28', '2026-02-12 08:40:58');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `user_sessions`
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
-- Tablo döküm verisi `user_sessions`
--

INSERT INTO `user_sessions` (`id`, `user_id`, `session_id`, `username`, `ip_address`, `user_agent`, `created_at`, `expires_at`) VALUES
(337, 218, '3tjlb7bvi2hc834nfe9bu8v453', 'Salam.MrS', '188.253.221.154', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', '2026-02-02 12:21:56', '2026-02-03 15:21:56'),
(340, 223, '0l7kptahh0c97f6s2tt13em8hu', 'pekaka', '37.61.117.135', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-02 18:15:31', '2026-02-03 21:15:31'),
(346, 220, 'hosvhethk0523ocp39vn6lg503', 'instagramdan mene yazÄ±n @elliotwhoami', '185.146.115.56', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-12 08:40:24', '2026-02-13 11:40:24'),
(347, 221, 'nan9jfvubf0q2sdonov0ij581n', 'peysel ibrahim', '185.91.210.232', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', '2026-02-13 11:27:06', '2026-02-14 14:27:06');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `valideyn`
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
-- Tablo için tablo yapısı `vetandasliq`
--

CREATE TABLE `vetandasliq` (
  `id` int(11) NOT NULL,
  `country_name` varchar(100) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `vetandasliq`
--

INSERT INTO `vetandasliq` (`id`, `country_name`, `created_at`, `updated_at`) VALUES
(1, 'Azerbaijan', '2025-06-11 22:08:53', '2025-06-12 00:08:53');

--
-- Dökümü yapılmış tablolar için indeksler
--

--
-- Tablo için indeksler `davamiyyet`
--
ALTER TABLE `davamiyyet`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `dersler`
--
ALTER TABLE `dersler`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `elanlar`
--
ALTER TABLE `elanlar`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `emekdaslar`
--
ALTER TABLE `emekdaslar`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `fennler_new`
--
ALTER TABLE `fennler_new`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `filiallar`
--
ALTER TABLE `filiallar`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `imtahanlar_exam`
--
ALTER TABLE `imtahanlar_exam`
  ADD PRIMARY KEY (`id`),
  ADD KEY `u_id` (`u_id`);

--
-- Tablo için indeksler `imtahan_melumat`
--
ALTER TABLE `imtahan_melumat`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `imtahan_neticeler`
--
ALTER TABLE `imtahan_neticeler`
  ADD PRIMARY KEY (`id`),
  ADD KEY `imtahan_neticeler_uid_fk` (`u_id`);

--
-- Tablo için indeksler `imtahan_nezaret`
--
ALTER TABLE `imtahan_nezaret`
  ADD PRIMARY KEY (`id`),
  ADD KEY `u_id` (`u_id`);

--
-- Tablo için indeksler `ixtisas`
--
ALTER TABLE `ixtisas`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `materiallar`
--
ALTER TABLE `materiallar`
  ADD PRIMARY KEY (`id`),
  ADD KEY `materiallar` (`u_id`);

--
-- Tablo için indeksler `movzular_new`
--
ALTER TABLE `movzular_new`
  ADD PRIMARY KEY (`id`),
  ADD KEY `u_id` (`u_id`);

--
-- Tablo için indeksler `muellimler_new`
--
ALTER TABLE `muellimler_new`
  ADD PRIMARY KEY (`id`),
  ADD KEY `u_id` (`u_id`);

--
-- Tablo için indeksler `otaqlar`
--
ALTER TABLE `otaqlar`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `qeydiyyatar`
--
ALTER TABLE `qeydiyyatar`
  ADD PRIMARY KEY (`id`),
  ADD KEY `u_id` (`u_id`) USING BTREE;

--
-- Tablo için indeksler `qruplar`
--
ALTER TABLE `qruplar`
  ADD PRIMARY KEY (`id`),
  ADD KEY `qruplar` (`u_id`);

--
-- Tablo için indeksler `qr_scans`
--
ALTER TABLE `qr_scans`
  ADD PRIMARY KEY (`id`),
  ADD KEY `qr_scans` (`u_id`);

--
-- Tablo için indeksler `sinifler`
--
ALTER TABLE `sinifler`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `sual_banki`
--
ALTER TABLE `sual_banki`
  ADD PRIMARY KEY (`id`),
  ADD KEY `subject` (`subject`),
  ADD KEY `topic` (`topic`),
  ADD KEY `u_id` (`u_id`);

--
-- Tablo için indeksler `tapsiriqlar`
--
ALTER TABLE `tapsiriqlar`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tapsiriqlar` (`u_id`);

--
-- Tablo için indeksler `telebeler`
--
ALTER TABLE `telebeler`
  ADD PRIMARY KEY (`id`),
  ADD KEY `u_id` (`u_id`);

--
-- Tablo için indeksler `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_unique_u_id` (`u_id`),
  ADD KEY `u_id` (`u_id`);

--
-- Tablo için indeksler `user_devices`
--
ALTER TABLE `user_devices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_agent` (`user_id`,`user_agent`(255));

--
-- Tablo için indeksler `user_permissions`
--
ALTER TABLE `user_permissions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Tablo için indeksler `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Tablo için indeksler `valideyn`
--
ALTER TABLE `valideyn`
  ADD PRIMARY KEY (`id`),
  ADD KEY `u_id` (`u_id`);

--
-- Tablo için indeksler `vetandasliq`
--
ALTER TABLE `vetandasliq`
  ADD PRIMARY KEY (`id`);

--
-- Dökümü yapılmış tablolar için AUTO_INCREMENT değeri
--

--
-- Tablo için AUTO_INCREMENT değeri `davamiyyet`
--
ALTER TABLE `davamiyyet`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `dersler`
--
ALTER TABLE `dersler`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Tablo için AUTO_INCREMENT değeri `elanlar`
--
ALTER TABLE `elanlar`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- Tablo için AUTO_INCREMENT değeri `emekdaslar`
--
ALTER TABLE `emekdaslar`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Tablo için AUTO_INCREMENT değeri `fennler_new`
--
ALTER TABLE `fennler_new`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Tablo için AUTO_INCREMENT değeri `filiallar`
--
ALTER TABLE `filiallar`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- Tablo için AUTO_INCREMENT değeri `imtahanlar_exam`
--
ALTER TABLE `imtahanlar_exam`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Tablo için AUTO_INCREMENT değeri `imtahan_melumat`
--
ALTER TABLE `imtahan_melumat`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `imtahan_neticeler`
--
ALTER TABLE `imtahan_neticeler`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- Tablo için AUTO_INCREMENT değeri `imtahan_nezaret`
--
ALTER TABLE `imtahan_nezaret`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `ixtisas`
--
ALTER TABLE `ixtisas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- Tablo için AUTO_INCREMENT değeri `materiallar`
--
ALTER TABLE `materiallar`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Tablo için AUTO_INCREMENT değeri `movzular_new`
--
ALTER TABLE `movzular_new`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=57;

--
-- Tablo için AUTO_INCREMENT değeri `muellimler_new`
--
ALTER TABLE `muellimler_new`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- Tablo için AUTO_INCREMENT değeri `otaqlar`
--
ALTER TABLE `otaqlar`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `qeydiyyatar`
--
ALTER TABLE `qeydiyyatar`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=118;

--
-- Tablo için AUTO_INCREMENT değeri `qruplar`
--
ALTER TABLE `qruplar`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- Tablo için AUTO_INCREMENT değeri `qr_scans`
--
ALTER TABLE `qr_scans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- Tablo için AUTO_INCREMENT değeri `sinifler`
--
ALTER TABLE `sinifler`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Tablo için AUTO_INCREMENT değeri `sual_banki`
--
ALTER TABLE `sual_banki`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- Tablo için AUTO_INCREMENT değeri `tapsiriqlar`
--
ALTER TABLE `tapsiriqlar`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Tablo için AUTO_INCREMENT değeri `telebeler`
--
ALTER TABLE `telebeler`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=87;

--
-- Tablo için AUTO_INCREMENT değeri `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=231;

--
-- Tablo için AUTO_INCREMENT değeri `user_devices`
--
ALTER TABLE `user_devices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=111;

--
-- Tablo için AUTO_INCREMENT değeri `user_permissions`
--
ALTER TABLE `user_permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- Tablo için AUTO_INCREMENT değeri `user_sessions`
--
ALTER TABLE `user_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=351;

--
-- Tablo için AUTO_INCREMENT değeri `valideyn`
--
ALTER TABLE `valideyn`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Tablo için AUTO_INCREMENT değeri `vetandasliq`
--
ALTER TABLE `vetandasliq`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Dökümü yapılmış tablolar için kısıtlamalar
--

--
-- Tablo kısıtlamaları `imtahanlar_exam`
--
ALTER TABLE `imtahanlar_exam`
  ADD CONSTRAINT `exam` FOREIGN KEY (`u_id`) REFERENCES `users` (`u_id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `imtahan_neticeler`
--
ALTER TABLE `imtahan_neticeler`
  ADD CONSTRAINT `imtahan_neticeler_uid_fk` FOREIGN KEY (`u_id`) REFERENCES `users` (`u_id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `imtahan_nezaret`
--
ALTER TABLE `imtahan_nezaret`
  ADD CONSTRAINT `imtahan_nezaret_uid_fk` FOREIGN KEY (`u_id`) REFERENCES `users` (`u_id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `materiallar`
--
ALTER TABLE `materiallar`
  ADD CONSTRAINT `materiallar` FOREIGN KEY (`u_id`) REFERENCES `users` (`u_id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `movzular_new`
--
ALTER TABLE `movzular_new`
  ADD CONSTRAINT `movzular` FOREIGN KEY (`u_id`) REFERENCES `users` (`u_id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `muellimler_new`
--
ALTER TABLE `muellimler_new`
  ADD CONSTRAINT `muellimler_new_u_id_fk` FOREIGN KEY (`u_id`) REFERENCES `users` (`u_id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `qeydiyyatar`
--
ALTER TABLE `qeydiyyatar`
  ADD CONSTRAINT `qeydiyyatar_u_id_fk` FOREIGN KEY (`u_id`) REFERENCES `users` (`u_id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `qruplar`
--
ALTER TABLE `qruplar`
  ADD CONSTRAINT `qruplar` FOREIGN KEY (`u_id`) REFERENCES `users` (`u_id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `qr_scans`
--
ALTER TABLE `qr_scans`
  ADD CONSTRAINT `qr_scans` FOREIGN KEY (`u_id`) REFERENCES `users` (`u_id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `sual_banki`
--
ALTER TABLE `sual_banki`
  ADD CONSTRAINT `sual_banki` FOREIGN KEY (`u_id`) REFERENCES `users` (`u_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sual_banki_ibfk_1` FOREIGN KEY (`subject`) REFERENCES `fennler_new` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sual_banki_ibfk_2` FOREIGN KEY (`topic`) REFERENCES `movzular_new` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `tapsiriqlar`
--
ALTER TABLE `tapsiriqlar`
  ADD CONSTRAINT `tapsiriqlar` FOREIGN KEY (`u_id`) REFERENCES `users` (`u_id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `telebeler`
--
ALTER TABLE `telebeler`
  ADD CONSTRAINT `telebeler_u_id_fk` FOREIGN KEY (`u_id`) REFERENCES `users` (`u_id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `user_devices`
--
ALTER TABLE `user_devices`
  ADD CONSTRAINT `user_devices_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `user_permissions`
--
ALTER TABLE `user_permissions`
  ADD CONSTRAINT `user_permissions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD CONSTRAINT `user_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `valideyn`
--
ALTER TABLE `valideyn`
  ADD CONSTRAINT `valideyn_u_id_fk` FOREIGN KEY (`u_id`) REFERENCES `users` (`u_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
