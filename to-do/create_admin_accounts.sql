-- ============================================================
-- Login formuna uyğun hesablar (FIN KOD = 7 simvol, A-Z0-9)
-- Login.php: app_normalize_fin_kod → UPPERCASE, dəqiq 7 simvol
-- ============================================================
--
-- GİRİŞ:
--   super_admin → FIN: SUP0001   Şifrə: super26
--   admin       → FIN: ADM0001   Şifrə: admin26
--
-- ============================================================

SET @col_exists := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'users'
      AND COLUMN_NAME = 'plain_password'
);

SET @sql := IF(
    @col_exists = 0,
    'ALTER TABLE `users` ADD COLUMN `plain_password` VARCHAR(255) NULL DEFAULT NULL AFTER `password`',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Köhnə test hesablarını təmizlə
DELETE FROM `users`
WHERE `username` IN (
    'superadmin', 'admin', 'SUPERADMIN', 'ADMIN',
    'SUP0001', 'ADM0001'
)
OR `u_id` IN (
    'SA_ADMIN_001', 'AD_ADMIN_001', 'SAADMIN01', 'ADADMIN01',
    'UIDSUP01', 'UIDADM01'
);

-- super_admin (FIN: SUP0001)
INSERT INTO `users` (
    `username`, `password`, `plain_password`, `role`, `company_id`, `u_id`, `created_at`, `updated_at`
) VALUES (
    'SUP0001',
    '$2y$10$w/Zdf43mlYCyi23oVT15F.j73HijOTI2682.BIJQYHvCFKKRTR3ju',
    'super26',
    'super_admin',
    0,
    'UIDSUP01',
    NOW(),
    NOW()
);

-- admin (FIN: ADM0001)
INSERT INTO `users` (
    `username`, `password`, `plain_password`, `role`, `company_id`, `u_id`, `created_at`, `updated_at`
) VALUES (
    'ADM0001',
    '$2y$10$Imjxl.JtmbRrzHlVIp8vf.VxcGSfBs7fmvj5GsvcuAwDY9Jv60go.',
    'admin26',
    'admin',
    0,
    'UIDADM01',
    NOW(),
    NOW()
);

SELECT id, username, role, u_id, plain_password
FROM `users`
WHERE username IN ('SUP0001', 'ADM0001');
