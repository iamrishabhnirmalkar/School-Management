-- Migration: Create time_slots table for teacher timetable
-- Date: 2024-01-XX

-- Create the time_slots table
CREATE TABLE IF NOT EXISTS `time_slots` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `start_time` TIME NOT NULL,
  `end_time` TIME NOT NULL,
  `label` VARCHAR(50) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert sample time slots for a typical school day
INSERT INTO `time_slots` (`start_time`, `end_time`, `label`) VALUES
('08:00:00', '08:45:00', 'Period 1'),
('08:50:00', '09:35:00', 'Period 2'),
('09:40:00', '10:25:00', 'Period 3'),
('10:30:00', '11:15:00', 'Period 4'),
('11:20:00', '12:05:00', 'Period 5'),
('12:10:00', '12:55:00', 'Period 6'),
('13:00:00', '13:45:00', 'Period 7'),
('13:50:00', '14:35:00', 'Period 8'),
('14:40:00', '15:25:00', 'Period 9'),
('15:30:00', '16:15:00', 'Period 10'); 