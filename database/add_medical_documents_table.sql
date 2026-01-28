-- Add medical_documents table to chuduyit_medical_k73 database

CREATE TABLE `medical_documents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pid` int(11) NOT NULL,
  `doctor` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `appointment_id` int(11) DEFAULT NULL,
  `document_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_path` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_size` int(11) DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `uploaded_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `pid` (`pid`),
  KEY `doctor` (`doctor`),
  KEY `appointment_id` (`appointment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add foreign key constraints if needed
-- ALTER TABLE `medical_documents` ADD CONSTRAINT `fk_medical_docs_pid` FOREIGN KEY (`pid`) REFERENCES `patreg` (`pid`) ON DELETE CASCADE;
-- ALTER TABLE `medical_documents` ADD CONSTRAINT `fk_medical_docs_appointment` FOREIGN KEY (`appointment_id`) REFERENCES `appointmenttb` (`ID`) ON DELETE SET NULL;