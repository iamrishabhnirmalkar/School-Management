-- Add fee_payments table for tracking payment history
CREATE TABLE `fee_payments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `fee_id` int NOT NULL,
  `student_id` int NOT NULL,
  `amount_paid` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `payment_date` date NOT NULL,
  `collected_by` int NOT NULL,
  `remarks` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_fee_payments_fee` (`fee_id`),
  KEY `fk_fee_payments_student` (`student_id`),
  KEY `fk_fee_payments_collector` (`collected_by`),
  CONSTRAINT `fk_fee_payments_fee` FOREIGN KEY (`fee_id`) REFERENCES `fees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_fee_payments_student` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_fee_payments_collector` FOREIGN KEY (`collected_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Add class_id column to fees table
ALTER TABLE `fees` ADD COLUMN `class_id` int DEFAULT NULL AFTER `student_id`;

-- Add foreign key constraint for class_id
ALTER TABLE `fees` ADD CONSTRAINT `fk_fees_class` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE; 