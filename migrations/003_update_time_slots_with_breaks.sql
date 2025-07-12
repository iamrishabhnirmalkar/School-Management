-- Migration: Update time slots with breaks and lunch periods
-- Date: 2024-01-XX

-- Clear existing time slots
DELETE FROM time_slots;

-- Insert updated time slots with breaks and lunch
INSERT INTO `time_slots` (`start_time`, `end_time`, `label`) VALUES
-- Morning periods
('08:00:00', '08:45:00', 'Period 1'),
('08:45:00', '08:50:00', 'Break'),
('08:50:00', '09:35:00', 'Period 2'),
('09:35:00', '09:40:00', 'Break'),
('09:40:00', '10:25:00', 'Period 3'),
('10:25:00', '10:30:00', 'Break'),
('10:30:00', '11:15:00', 'Period 4'),
('11:15:00', '11:20:00', 'Break'),
('11:20:00', '12:05:00', 'Period 5'),

-- Lunch break
('12:05:00', '12:45:00', 'Lunch Break'),

-- Afternoon periods
('12:45:00', '13:30:00', 'Period 6'),
('13:30:00', '13:35:00', 'Break'),
('13:35:00', '14:20:00', 'Period 7'),
('14:20:00', '14:25:00', 'Break'),
('14:25:00', '15:10:00', 'Period 8'),
('15:10:00', '15:15:00', 'Break'),
('15:15:00', '16:00:00', 'Period 9'); 