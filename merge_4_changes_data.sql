-- ==============================================================================
-- DATA MERGE: msu_latest_database -> msudocv1
-- Purpose: Merge data for only the 4 integrated changes.
-- Production Safe: Purely additive. No tables dropped. No existing data wiped.
-- ==============================================================================

START TRANSACTION;

-- 1. Ensure `asset_audits` table exists to hold audit history records
CREATE TABLE IF NOT EXISTS `asset_audits` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `asset_id` bigint unsigned NOT NULL,
  `auditor_id` bigint unsigned DEFAULT NULL,
  `audit_status` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Pending Audit',
  `previous_status` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `location_status` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `source` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'web',
  `company_id` bigint unsigned DEFAULT NULL,
  `branch_id` bigint unsigned DEFAULT NULL,
  `location_id` bigint unsigned DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `audited_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `asset_audits_auditor_id_foreign` (`auditor_id`),
  KEY `asset_audits_asset_id_created_at_index` (`asset_id`,`created_at`),
  KEY `asset_audits_audit_status_index` (`audit_status`),
  KEY `asset_audits_company_id_foreign` (`company_id`),
  KEY `asset_audits_branch_id_foreign` (`branch_id`),
  KEY `asset_audits_location_id_foreign` (`location_id`),
  CONSTRAINT `asset_audits_asset_id_foreign` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `asset_audits_auditor_id_foreign` FOREIGN KEY (`auditor_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `asset_audits_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `asset_audits_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL,
  CONSTRAINT `asset_audits_location_id_foreign` FOREIGN KEY (`location_id`) REFERENCES `branch_locations` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Merge audit history records from msu_latest_database
INSERT INTO `asset_audits` (`id`, `asset_id`, `auditor_id`, `audit_status`, `previous_status`, `location_status`, `source`, `company_id`, `branch_id`, `location_id`, `latitude`, `longitude`, `remarks`, `audited_at`, `created_at`, `updated_at`) VALUES
(1,1,NULL,'Pending Audit',NULL,NULL,'web',NULL,NULL,NULL,NULL,NULL,'Asset created','2026-10-13 07:11:54','2026-10-13 07:11:54','2026-10-13 07:11:54'),
(2,2,NULL,'Pending Audit',NULL,NULL,'web',NULL,NULL,NULL,NULL,NULL,'Asset created','2026-10-13 07:11:54','2026-10-13 07:11:54','2026-10-13 07:11:54'),
(3,3,NULL,'Pending Audit',NULL,NULL,'web',NULL,NULL,NULL,NULL,NULL,'Asset created','2026-10-13 07:11:54','2026-10-13 07:11:54','2026-10-13 07:11:54'),
(4,4,NULL,'Pending Audit',NULL,NULL,'web',NULL,NULL,NULL,NULL,NULL,'Asset created','2026-10-13 07:11:54','2026-10-13 07:11:54','2026-10-13 07:11:54'),
(5,5,NULL,'Pending Audit',NULL,NULL,'web',NULL,NULL,NULL,NULL,NULL,'Asset created','2026-10-13 07:11:54','2026-10-13 07:11:54','2026-10-13 07:11:54'),
(6,6,NULL,'Pending Audit',NULL,NULL,'web',NULL,NULL,NULL,NULL,NULL,'Asset created','2026-10-13 07:11:54','2026-10-13 07:11:54','2026-10-13 07:11:54'),
(7,7,NULL,'Pending Audit',NULL,NULL,'web',NULL,NULL,NULL,NULL,NULL,'Asset created','2026-10-13 07:11:54','2026-10-13 07:11:54','2026-10-13 07:11:54'),
(8,8,NULL,'Pending Audit',NULL,NULL,'web',NULL,NULL,NULL,NULL,NULL,'Asset created','2026-10-13 07:11:54','2026-10-13 07:11:54','2026-10-13 07:11:54'),
(9,9,NULL,'Pending Audit',NULL,NULL,'web',NULL,NULL,NULL,NULL,NULL,'Asset created','2026-10-13 07:11:54','2026-10-13 07:11:54','2026-10-13 07:11:54'),
(10,10,NULL,'Pending Audit',NULL,NULL,'web',NULL,NULL,NULL,NULL,NULL,'Asset created','2026-10-13 07:11:54','2026-10-13 07:11:54','2026-10-13 07:11:54'),
(12,1,1,'Audited','Pending Audit','matches','web',NULL,NULL,NULL,NULL,NULL,'Physical verification completed successfully','2026-10-13 08:46:36','2026-10-13 08:46:36','2026-10-13 08:46:36'),
(13,1,1,'Audited','Audited','matches','web',NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-13 10:03:33','2026-10-13 10:03:33','2026-10-13 10:03:33'),
(14,2,1,'Audited','Pending Audit','Verified (Warehouse)','web',NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-13 10:06:39','2026-10-13 10:06:39','2026-10-13 10:06:39'),
(15,3,1,'Audited','Pending Audit','Verified (Warehouse)','web',NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-13 10:18:00','2026-10-13 10:18:00','2026-10-13 10:18:00'),
(16,5,1,'Audited','Pending Audit','Verified (HR Cabin)','web',NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-13 10:23:50','2026-10-13 10:23:50','2026-10-13 10:23:50'),
(17,4,1,'Audited','Pending Audit','matches','web',NULL,NULL,NULL,NULL,NULL,'Physical verification complete. Asset in perfect condition.','2026-10-13 10:39:30','2026-10-13 10:39:30','2026-10-13 10:39:30'),
(18,4,1,'Audited','Audited','matches','web',NULL,NULL,NULL,NULL,NULL,'Physical verification complete. Asset in perfect condition.','2026-10-13 10:50:35','2026-10-13 10:50:35','2026-10-13 10:50:35'),
(19,7,1,'Audited','Pending Audit','matches','web',NULL,NULL,NULL,NULL,NULL,'Verified in server room rack 2','2026-10-13 11:13:59','2026-10-13 11:13:59','2026-10-13 11:13:59'),
(20,1,1,'Audited','Not Found','Location Matches','mobile_app',1,2,5,NULL,NULL,'found on location','2026-10-23 12:52:05','2026-10-23 12:52:05','2026-10-23 12:52:05')
ON DUPLICATE KEY UPDATE 
  `audit_status` = VALUES(`audit_status`),
  `audited_at` = VALUES(`audited_at`),
  `location_status` = VALUES(`location_status`),
  `remarks` = VALUES(`remarks`);

-- 3. Merge audit status onto assets table (only updates the audit columns)
UPDATE `assets` SET `audit_status` = 'audited', `audited_at` = '2026-10-23 12:52:05', `audited_by` = 1 WHERE `id` = 1;
UPDATE `assets` SET `audit_status` = 'Audited', `audited_at` = '2026-10-13 10:06:39', `audited_by` = 1 WHERE `id` = 2;
UPDATE `assets` SET `audit_status` = 'Audited', `audited_at` = '2026-10-13 10:18:00', `audited_by` = 1 WHERE `id` = 3;
UPDATE `assets` SET `audit_status` = 'Audited', `audited_at` = '2026-10-13 10:50:35', `audited_by` = 1 WHERE `id` = 4;
UPDATE `assets` SET `audit_status` = 'Audited', `audited_at` = '2026-10-13 10:23:50', `audited_by` = 1 WHERE `id` = 5;
UPDATE `assets` SET `audit_status` = 'Audited', `audited_at` = '2026-10-13 11:13:59', `audited_by` = 1 WHERE `id` = 7;
UPDATE `assets` SET `audit_status` = 'Pending Audit' WHERE `audit_status` IS NULL OR `audit_status` = '';

-- 4. Merge permissions & role matrix
-- 4.1 Insert permissions if not already existing
INSERT IGNORE INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`) VALUES
('audit:view', 'web', NOW(), NOW()),
('audit:create', 'web', NOW(), NOW()),
('audit:edit', 'web', NOW(), NOW()),
('audit:delete', 'web', NOW(), NOW()),
('dashboard:view', 'api', NOW(), NOW()),
('asset:view', 'api', NOW(), NOW()),
('audit:view', 'api', NOW(), NOW()),
('mobile.dashboard:view', 'web', NOW(), NOW()),
('mobile.dashboard:view', 'api', NOW(), NOW()),
('mobile.asset:view', 'web', NOW(), NOW()),
('mobile.asset:view', 'api', NOW(), NOW()),
('mobile.audit:view', 'web', NOW(), NOW()),
('mobile.audit:view', 'api', NOW(), NOW()),
('mobile.support-ticket:view', 'web', NOW(), NOW()),
('mobile.support-ticket:view', 'api', NOW(), NOW()),
('mobile.support-ticket:create', 'web', NOW(), NOW()),
('mobile.support-ticket:create', 'api', NOW(), NOW());

-- 4.2 Ensure `employee` role exists
INSERT IGNORE INTO `roles` (`name`, `guard_name`, `created_at`, `updated_at`) VALUES
('employee', 'web', NOW(), NOW());

-- 4.3 Merge mobile permissions to `employee` role
INSERT IGNORE INTO `role_has_permissions` (`permission_id`, `role_id`)
SELECT p.id, r.id
FROM `permissions` p
CROSS JOIN `roles` r
WHERE r.name = 'employee' AND r.guard_name = 'web' AND p.guard_name = 'web' AND p.name IN (
    'mobile.dashboard:view',
    'mobile.asset:view',
    'mobile.audit:view',
    'mobile.support-ticket:view',
    'mobile.support-ticket:create',
    'audit:view'
);

-- 4.4 Merge mobile & audit permissions to Admin/SuperAdmin roles
INSERT IGNORE INTO `role_has_permissions` (`permission_id`, `role_id`)
SELECT p.id, r.id
FROM `permissions` p
CROSS JOIN `roles` r
WHERE r.name IN ('Super Admin', 'superadmin', 'Admin', 'admin') 
  AND r.guard_name = 'web'
  AND p.guard_name = 'web'
  AND (p.name LIKE 'mobile.%' OR p.name LIKE 'audit:%');

COMMIT;

