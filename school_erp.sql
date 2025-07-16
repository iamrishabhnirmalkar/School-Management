-- phpMyAdmin SQL Dump
-- version 5.2.1deb3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jul 13, 2025 at 05:55 AM
-- Server version: 8.0.42-0ubuntu0.24.04.1
-- PHP Version: 8.3.6

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Table structure for table `activity_log`
--

CREATE TABLE `activity_log` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `action` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
);

--
-- Dumping data for table `activity_log`
--

INSERT INTO `activity_log` (`id`, `user_id`, `action`, `created_at`) VALUES
(1, 1, 'Promoted 2 students from Class 1 A to Class 2 B', '2025-07-07 21:17:59'),
(2, 1, 'Promoted 2 students from Class 1  to Class 2 B', '2025-07-12 16:57:32');

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` int NOT NULL,
  `student_id` int NOT NULL,
  `date` date NOT NULL,
  `status` enum('present','absent','late') NOT NULL,
  `remarks` text
);

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`id`, `student_id`, `date`, `status`, `remarks`) VALUES
(1, 201, '2025-07-09', 'absent', ''),
(2, 202, '2025-07-09', 'present', '');

-- --------------------------------------------------------

--
-- Table structure for table `book_issues`
--

CREATE TABLE `book_issues` (
  `id` int NOT NULL,
  `book_id` int NOT NULL,
  `student_id` int NOT NULL,
  `issue_date` date NOT NULL,
  `return_date` date DEFAULT NULL,
  `due_date` date NOT NULL,
  `status` enum('issued','returned','overdue') DEFAULT 'issued'
);

-- --------------------------------------------------------

--
-- Table structure for table `buses`
--

CREATE TABLE `buses` (
  `id` int NOT NULL,
  `bus_number` varchar(20) NOT NULL,
  `route_name` varchar(100) NOT NULL,
  `driver_name` varchar(100) DEFAULT NULL,
  `driver_phone` varchar(20) DEFAULT NULL,
  `capacity` int DEFAULT NULL,
  `stops` text,
  `vehicle_type` enum('bus','minibus','auto_rickshaw') NOT NULL DEFAULT 'bus',
  `registration_number` varchar(20) DEFAULT NULL,
  `model` varchar(50) DEFAULT NULL,
  `year` int DEFAULT NULL,
  `tracking_device_id` varchar(50) DEFAULT NULL,
  `tracking_enabled` tinyint(1) DEFAULT '0',
  `last_location` point DEFAULT NULL,
  `last_update` timestamp NULL DEFAULT NULL,
  `current_location` varchar(255) DEFAULT NULL,
  `last_updated` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

--
-- Dumping data for table `buses`
--

INSERT INTO `buses` (`id`, `bus_number`, `route_name`, `driver_name`, `driver_phone`, `capacity`, `stops`, `vehicle_type`, `registration_number`, `model`, `year`, `tracking_device_id`, `tracking_enabled`, `last_location`, `last_update`, `current_location`, `last_updated`) VALUES
(1, 'CG 04 HX 1424', 'Hiran', 'salmon bhai', '0123012301', 200, '', 'bus', '0123012301', '', 0, '', 0, NULL, NULL, NULL, '2025-07-12 19:38:46');

-- --------------------------------------------------------

--
-- Table structure for table `bus_allocations`
--

CREATE TABLE `bus_allocations` (
  `id` int NOT NULL,
  `bus_id` int NOT NULL,
  `student_id` int NOT NULL,
  `stop_name` varchar(100) NOT NULL,
  `pickup_time` time NOT NULL,
  `drop_time` time NOT NULL,
  `monthly_fee` decimal(10,2) DEFAULT '0.00',
  `payment_status` enum('paid','unpaid') DEFAULT 'unpaid',
  `academic_year` varchar(20) DEFAULT NULL
);

-- --------------------------------------------------------

--
-- Table structure for table `classes`
--

CREATE TABLE `classes` (
  `id` int NOT NULL,
  `class_name` varchar(50) NOT NULL,
  `section` varchar(20) DEFAULT NULL,
  `class_teacher_id` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
);

--
-- Dumping data for table `classes`
--

INSERT INTO `classes` (`id`, `class_name`, `section`, `class_teacher_id`, `created_at`) VALUES
(1, 'Class 10', 'A', 101, '2025-07-09 17:18:34'),
(2, 'Class 2', 'B', NULL, '2025-07-07 19:53:13'),
(5, 'Class 1', '', NULL, '2025-07-08 20:12:21'),
(6, 'Class 3', '', NULL, '2025-07-08 20:12:30'),
(7, 'Class 4', '', NULL, '2025-07-08 20:12:37'),
(8, 'Class 5', '', NULL, '2025-07-08 20:12:43'),
(9, 'Class 6', '', NULL, '2025-07-08 20:12:49'),
(10, 'Class 7', '', NULL, '2025-07-08 20:12:53'),
(11, 'Class 8', '', NULL, '2025-07-08 20:12:59'),
(12, 'Class 9', '', NULL, '2025-07-08 20:13:05'),
(13, 'Class 10', '', NULL, '2025-07-08 20:13:10');

-- --------------------------------------------------------

--
-- Table structure for table `examinations`
--

CREATE TABLE `examinations` (
  `id` int NOT NULL,
  `exam_name` varchar(100) NOT NULL,
  `academic_year` varchar(20) NOT NULL DEFAULT '2024-2025',
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `description` text
);

--
-- Dumping data for table `examinations`
--

INSERT INTO `examinations` (`id`, `exam_name`, `academic_year`, `start_date`, `end_date`, `description`) VALUES
(3, 'Mid-Term Examination', '2024-2025', '2025-09-01', '2025-09-15', 'Mid-term assessment for all classes');

-- --------------------------------------------------------

--
-- Table structure for table `exam_results`
--

CREATE TABLE `exam_results` (
  `id` int NOT NULL,
  `exam_subject_id` int NOT NULL,
  `student_id` int NOT NULL,
  `marks_obtained` decimal(5,2) NOT NULL,
  `grade` varchar(5) DEFAULT NULL,
  `remarks` text
);

-- --------------------------------------------------------

--
-- Table structure for table `exam_subjects`
--

CREATE TABLE `exam_subjects` (
  `id` int NOT NULL,
  `exam_id` int NOT NULL,
  `subject_id` int NOT NULL,
  `max_marks` int NOT NULL,
  `pass_marks` int NOT NULL,
  `exam_date` date NOT NULL,
  `exam_time` time NOT NULL
);

-- --------------------------------------------------------

--
-- Table structure for table `fees`
--

CREATE TABLE `fees` (
  `id` int NOT NULL,
  `student_id` int NOT NULL,
  `class_id` int DEFAULT NULL,
  `fee_type` varchar(50) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `due_date` date NOT NULL,
  `paid_date` date DEFAULT NULL,
  `status` enum('paid','unpaid','partial') DEFAULT 'unpaid',
  `remarks` text
);

-- --------------------------------------------------------

--
-- Table structure for table `fee_payments`
--

CREATE TABLE `fee_payments` (
  `id` int NOT NULL,
  `fee_id` int NOT NULL,
  `student_id` int NOT NULL,
  `amount_paid` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `payment_date` date NOT NULL,
  `collected_by` int NOT NULL,
  `remarks` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- --------------------------------------------------------

--
-- Table structure for table `homework`
--

CREATE TABLE `homework` (
  `id` int NOT NULL,
  `teacher_id` int NOT NULL,
  `class_id` int NOT NULL,
  `subject_id` int NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `due_date` date NOT NULL,
  `file_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- --------------------------------------------------------

--
-- Table structure for table `id_card_designs`
--

CREATE TABLE `id_card_designs` (
  `id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `type` enum('student','teacher') NOT NULL,
  `background_image` varchar(255) DEFAULT NULL,
  `layout_json` text,
  `is_default` tinyint(1) DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
);

--
-- Dumping data for table `id_card_designs`
--

INSERT INTO `id_card_designs` (`id`, `name`, `type`, `background_image`, `layout_json`, `is_default`, `created_at`) VALUES
(1, 'Default Student Card', 'student', 'design_1751921041.png', '{\"school_name\":\"School Name\",\"card_title\":\"Identity Card\",\"valid_text\":\"123\",\"photo_width\":96,\"photo_height\":96,\"fields\":[]}', 1, '2025-07-07 20:29:03'),
(2, 'Default Teacher Card', 'teacher', NULL, NULL, 1, '2025-07-07 20:29:03');

-- --------------------------------------------------------

--
-- Table structure for table `id_card_print_logs`
--

CREATE TABLE `id_card_print_logs` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `user_type` enum('student','teacher') COLLATE utf8mb4_unicode_ci NOT NULL,
  `printed_by` int NOT NULL,
  `printed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `template_id` int DEFAULT NULL
);

-- --------------------------------------------------------

--
-- Table structure for table `library_books`
--

CREATE TABLE `library_books` (
  `id` int NOT NULL,
  `title` varchar(255) NOT NULL,
  `author` varchar(100) NOT NULL,
  `isbn` varchar(20) DEFAULT NULL,
  `publisher` varchar(100) DEFAULT NULL,
  `edition` varchar(20) DEFAULT NULL,
  `quantity` int NOT NULL DEFAULT '1',
  `available` int NOT NULL DEFAULT '1',
  `category` varchar(50) DEFAULT NULL
);

-- --------------------------------------------------------

--
-- Table structure for table `notices`
--

CREATE TABLE `notices` (
  `id` int NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `created_by` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
);

--
-- Dumping data for table `notices`
--

INSERT INTO `notices` (`id`, `title`, `content`, `created_by`, `created_at`) VALUES
(1, 'School Reopens', 'School will reopen on January 5th after winter break. All students must attend.', 1, '2025-07-07 19:53:13');

-- --------------------------------------------------------

--
-- Table structure for table `notice_read_status`
--

CREATE TABLE `notice_read_status` (
  `id` int NOT NULL,
  `student_id` int NOT NULL,
  `notice_id` int NOT NULL,
  `is_read` tinyint(1) DEFAULT '0',
  `read_at` timestamp NULL DEFAULT NULL
);

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `user_id` int NOT NULL,
  `class_id` int DEFAULT NULL,
  `roll_number` varchar(20) DEFAULT NULL,
  `admission_date` date DEFAULT NULL,
  `gender` enum('Male','Female','Other') DEFAULT NULL,
  `blood_group` varchar(5) DEFAULT NULL,
  `parent_name` varchar(100) DEFAULT NULL,
  `parent_phone` varchar(20) DEFAULT NULL,
  `address` text,
  `dob` date DEFAULT NULL,
  `status` enum('Active','Inactive','Alumni') DEFAULT 'Active',
  `photo` varchar(255) DEFAULT NULL,
  `id_card_printed` tinyint(1) DEFAULT '0',
  `id_card_number` varchar(20) DEFAULT NULL,
  `id_card_issue_date` date DEFAULT NULL,
  `id_card_valid_until` date DEFAULT NULL,
  `current_year` varchar(20) DEFAULT '2023-2024',
  `bus_allocation_id` int DEFAULT NULL
);

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`user_id`, `class_id`, `roll_number`, `admission_date`, `gender`, `blood_group`, `parent_name`, `parent_phone`, `address`, `dob`, `status`, `photo`, `id_card_printed`, `id_card_number`, `id_card_issue_date`, `id_card_valid_until`, `current_year`, `bus_allocation_id`) VALUES
(3, 2, '101', '2023-01-10', 'Female', 'A+', 'Robert Johnson', '7654321098', '123 School Street, Education Town', '2015-05-15', 'Active', NULL, 0, NULL, NULL, NULL, '2023-2024', NULL),
(4, 2, '1212', '2025-07-07', 'Female', 'A+', 'asd', '988008800', 'asd', '2025-07-02', 'Active', NULL, 0, NULL, NULL, NULL, '2023-2024', NULL),
(201, 1, 'A001', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, 0, NULL, NULL, NULL, '2023-2024', NULL),
(202, 1, 'A002', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, 0, NULL, NULL, NULL, '2023-2024', NULL),
(203, 2, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, 0, NULL, NULL, NULL, '2024-2025', NULL),
(205, 2, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, 0, NULL, NULL, NULL, '2024-2025', NULL),
(212, 1, '', '2025-07-12', 'Male', '', '', '', 'asd', '2025-07-05', 'Active', NULL, 0, NULL, NULL, NULL, '2023-2024', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `student_documents`
--

CREATE TABLE `student_documents` (
  `id` int NOT NULL,
  `student_id` int NOT NULL,
  `document_type` enum('report_card','fee_receipt','other') NOT NULL,
  `title` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `issued_date` date NOT NULL,
  `issued_by` int DEFAULT NULL
);

-- --------------------------------------------------------

--
-- Table structure for table `study_materials`
--

CREATE TABLE `study_materials` (
  `id` int NOT NULL,
  `teacher_id` int NOT NULL,
  `class_id` int NOT NULL,
  `subject_id` int NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `file_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('notes','question_paper','sample_paper','other') COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `id` int NOT NULL,
  `subject_name` varchar(100) NOT NULL,
  `subject_code` varchar(20) DEFAULT NULL,
  `class_id` int DEFAULT NULL,
  `teacher_id` int DEFAULT NULL
);

--
-- Dumping data for table `subjects`
--

INSERT INTO `subjects` (`id`, `subject_name`, `subject_code`, `class_id`, `teacher_id`) VALUES
(1, 'Hindi', '', 5, NULL),
(2, 'Mathematics', 'MATH101', 5, 5),
(3, 'English', 'ENG101', 5, 5),
(4, 'Science', 'SCI101', 5, 5),
(5, 'Mathematics', 'MATH201', 6, 5),
(6, 'English', 'ENG201', 6, 5),
(7, 'Science', 'SCI201', 6, 5),
(8, 'Mathematics', 'MATH301', 7, 6),
(9, 'English', 'ENG301', 7, 6),
(10, 'Science', 'SCI301', 7, 6);

-- --------------------------------------------------------

--
-- Table structure for table `teachers`
--

CREATE TABLE `teachers` (
  `user_id` int NOT NULL,
  `subject_specialization` varchar(100) DEFAULT NULL,
  `qualification` text,
  `qualification_type` enum('Diploma','Bachelor','Master','PhD','Other') DEFAULT NULL,
  `specialization` varchar(100) DEFAULT NULL,
  `joining_date` date DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `id_card_printed` tinyint(1) DEFAULT '0',
  `id_card_number` varchar(20) DEFAULT NULL,
  `id_card_issue_date` date DEFAULT NULL,
  `id_card_valid_until` date DEFAULT NULL
);

--
-- Dumping data for table `teachers`
--

INSERT INTO `teachers` (`user_id`, `subject_specialization`, `qualification`, `qualification_type`, `specialization`, `joining_date`, `photo`, `id_card_printed`, `id_card_number`, `id_card_issue_date`, `id_card_valid_until`) VALUES
(5, NULL, NULL, 'Diploma', 'asd', '2025-07-07', NULL, 0, NULL, NULL, NULL),
(6, NULL, NULL, 'Diploma', '', '2025-07-08', NULL, 0, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `teacher_attendance`
--

CREATE TABLE `teacher_attendance` (
  `id` int NOT NULL,
  `teacher_id` int NOT NULL,
  `date` date NOT NULL,
  `status` enum('present','absent','leave') COLLATE utf8mb4_unicode_ci NOT NULL,
  `remarks` text COLLATE utf8mb4_unicode_ci
);

-- --------------------------------------------------------

--
-- Table structure for table `teacher_leaves`
--

CREATE TABLE `teacher_leaves` (
  `id` int NOT NULL,
  `teacher_id` int NOT NULL,
  `leave_date` date NOT NULL,
  `reason` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('pending','approved','rejected') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- --------------------------------------------------------

--
-- Table structure for table `teacher_subjects`
--

CREATE TABLE `teacher_subjects` (
  `id` int NOT NULL,
  `teacher_id` int NOT NULL,
  `subject_id` int NOT NULL,
  `class_id` int NOT NULL,
  `academic_year` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL
);

-- --------------------------------------------------------

--
-- Table structure for table `timetable`
--

CREATE TABLE `timetable` (
  `id` int NOT NULL,
  `class_id` int NOT NULL,
  `subject_id` int NOT NULL,
  `teacher_id` int NOT NULL,
  `day_of_week` enum('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday') NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `room_number` varchar(20) DEFAULT NULL
);

--
-- Dumping data for table `timetable`
--

INSERT INTO `timetable` (`id`, `class_id`, `subject_id`, `teacher_id`, `day_of_week`, `start_time`, `end_time`, `room_number`) VALUES
(1, 5, 3, 101, 'Monday', '02:02:00', '02:05:00', '10');

-- --------------------------------------------------------

--
-- Table structure for table `time_slots`
--

CREATE TABLE `time_slots` (
  `id` int NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `label` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

--
-- Dumping data for table `time_slots`
--

INSERT INTO `time_slots` (`id`, `start_time`, `end_time`, `label`, `created_at`, `updated_at`) VALUES
(11, '08:00:00', '08:45:00', 'Period 1', '2025-07-12 21:25:38', '2025-07-12 21:25:38'),
(12, '08:45:00', '08:50:00', 'Break', '2025-07-12 21:25:38', '2025-07-12 21:25:38'),
(13, '08:50:00', '09:35:00', 'Period 2', '2025-07-12 21:25:38', '2025-07-12 21:25:38'),
(14, '09:35:00', '09:40:00', 'Break', '2025-07-12 21:25:38', '2025-07-12 21:25:38'),
(15, '09:40:00', '10:25:00', 'Period 3', '2025-07-12 21:25:38', '2025-07-12 21:25:38'),
(16, '10:25:00', '10:30:00', 'Break', '2025-07-12 21:25:38', '2025-07-12 21:25:38'),
(17, '10:30:00', '11:15:00', 'Period 4', '2025-07-12 21:25:38', '2025-07-12 21:25:38'),
(18, '11:15:00', '11:20:00', 'Break', '2025-07-12 21:25:38', '2025-07-12 21:25:38'),
(19, '11:20:00', '12:05:00', 'Period 5', '2025-07-12 21:25:38', '2025-07-12 21:25:38'),
(20, '12:05:00', '12:45:00', 'Lunch Break', '2025-07-12 21:25:38', '2025-07-12 21:25:38'),
(21, '12:45:00', '13:30:00', 'Period 6', '2025-07-12 21:25:38', '2025-07-12 21:25:38'),
(22, '13:30:00', '13:35:00', 'Break', '2025-07-12 21:25:38', '2025-07-12 21:25:38'),
(23, '13:35:00', '14:20:00', 'Period 7', '2025-07-12 21:25:38', '2025-07-12 21:25:38'),
(24, '14:20:00', '14:25:00', 'Break', '2025-07-12 21:25:38', '2025-07-12 21:25:38'),
(25, '14:25:00', '15:10:00', 'Period 8', '2025-07-12 21:25:38', '2025-07-12 21:25:38'),
(26, '15:10:00', '15:15:00', 'Break', '2025-07-12 21:25:38', '2025-07-12 21:25:38'),
(27, '15:15:00', '16:00:00', 'Period 9', '2025-07-12 21:25:38', '2025-07-12 21:25:38');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `admission_number` varchar(20) DEFAULT NULL,
  `login_id` varchar(50) NOT NULL,
  `role` enum('admin','teacher','student') NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `admission_number`, `login_id`, `role`, `full_name`, `email`, `phone`, `created_at`, `updated_at`) VALUES
(1, NULL, 'admin', 'admin', 'Admin User', 'admin@school.com', '1234567890', '2025-07-07 19:53:13', '2025-07-07 19:53:13'),
(3, 'ADM00001', 'ADM00001', 'student', 'Alice Johnson', 'alice@school.com', '8765432109', '2025-07-07 19:53:13', '2025-07-07 19:53:13'),
(4, 'ADM00002', 'ADM00002', 'student', 'Palak Dadlani', 'aa@gmail.com', '07803002963', '2025-07-07 19:54:55', '2025-07-07 19:54:55'),
(5, NULL, 'TCH0001', 'teacher', 'Palak Dadlani', 'teachera@gmail.com', '07803002963', '2025-07-07 20:13:32', '2025-07-07 20:13:32'),
(6, NULL, 'TCH0002', 'teacher', 'Palak Dadlani', 'teacher@gmail.com', '07803002963', '2025-07-08 19:55:18', '2025-07-08 19:55:18'),
(101, NULL, 'TCHR101', 'teacher', 'Dummy Teacher', 'dummy.teacher@example.com', NULL, '2025-07-09 17:17:33', '2025-07-09 17:17:33'),
(201, NULL, 'stu1', 'student', 'Student One', 'student1@example.com', NULL, '2025-07-09 17:18:16', '2025-07-09 17:18:16'),
(202, NULL, 'stu2', 'student', 'Student Two', 'student2@example.com', NULL, '2025-07-09 17:18:16', '2025-07-09 17:18:16'),
(203, NULL, 'stu101', 'student', 'Student A1', NULL, NULL, '2025-07-09 18:28:04', '2025-07-09 18:28:04'),
(205, NULL, 'stu103', 'student', 'Student A3', NULL, NULL, '2025-07-09 18:28:04', '2025-07-09 18:28:04'),
(212, 'ADM1111', 'ADM1111', 'student', 'Palak Dadlani', '', '07803002963', '2025-07-12 16:53:35', '2025-07-12 16:53:35');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `book_issues`
--
ALTER TABLE `book_issues`
  ADD PRIMARY KEY (`id`),
  ADD KEY `book_id` (`book_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `buses`
--
ALTER TABLE `buses`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `bus_allocations`
--
ALTER TABLE `bus_allocations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `bus_id` (`bus_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `classes`
--
ALTER TABLE `classes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `class_teacher_id` (`class_teacher_id`);

--
-- Indexes for table `examinations`
--
ALTER TABLE `examinations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `exam_results`
--
ALTER TABLE `exam_results`
  ADD PRIMARY KEY (`id`),
  ADD KEY `exam_subject_id` (`exam_subject_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `exam_subjects`
--
ALTER TABLE `exam_subjects`
  ADD PRIMARY KEY (`id`),
  ADD KEY `exam_id` (`exam_id`),
  ADD KEY `subject_id` (`subject_id`);

--
-- Indexes for table `fees`
--
ALTER TABLE `fees`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `fee_payments`
--
ALTER TABLE `fee_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_fee_payments_fee` (`fee_id`),
  ADD KEY `fk_fee_payments_student` (`student_id`),
  ADD KEY `fk_fee_payments_collector` (`collected_by`);

--
-- Indexes for table `homework`
--
ALTER TABLE `homework`
  ADD PRIMARY KEY (`id`),
  ADD KEY `teacher_id` (`teacher_id`),
  ADD KEY `class_id` (`class_id`),
  ADD KEY `subject_id` (`subject_id`);

--
-- Indexes for table `id_card_designs`
--
ALTER TABLE `id_card_designs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `id_card_print_logs`
--
ALTER TABLE `id_card_print_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `printed_by` (`printed_by`),
  ADD KEY `template_id` (`template_id`);

--
-- Indexes for table `library_books`
--
ALTER TABLE `library_books`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notices`
--
ALTER TABLE `notices`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `notice_read_status`
--
ALTER TABLE `notice_read_status`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `notice_id` (`notice_id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`user_id`),
  ADD KEY `class_id` (`class_id`),
  ADD KEY `students_ibfk_3` (`bus_allocation_id`);

--
-- Indexes for table `student_documents`
--
ALTER TABLE `student_documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `issued_by` (`issued_by`);

--
-- Indexes for table `study_materials`
--
ALTER TABLE `study_materials`
  ADD PRIMARY KEY (`id`),
  ADD KEY `teacher_id` (`teacher_id`),
  ADD KEY `class_id` (`class_id`),
  ADD KEY `subject_id` (`subject_id`);

--
-- Indexes for table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`id`),
  ADD KEY `class_id` (`class_id`),
  ADD KEY `teacher_id` (`teacher_id`);

--
-- Indexes for table `teachers`
--
ALTER TABLE `teachers`
  ADD PRIMARY KEY (`user_id`);

--
-- Indexes for table `teacher_attendance`
--
ALTER TABLE `teacher_attendance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `teacher_id` (`teacher_id`);

--
-- Indexes for table `teacher_leaves`
--
ALTER TABLE `teacher_leaves`
  ADD PRIMARY KEY (`id`),
  ADD KEY `teacher_id` (`teacher_id`);

--
-- Indexes for table `teacher_subjects`
--
ALTER TABLE `teacher_subjects`
  ADD PRIMARY KEY (`id`),
  ADD KEY `teacher_id` (`teacher_id`),
  ADD KEY `subject_id` (`subject_id`),
  ADD KEY `class_id` (`class_id`);

--
-- Indexes for table `timetable`
--
ALTER TABLE `timetable`
  ADD PRIMARY KEY (`id`),
  ADD KEY `class_id` (`class_id`),
  ADD KEY `subject_id` (`subject_id`),
  ADD KEY `teacher_id` (`teacher_id`);

--
-- Indexes for table `time_slots`
--
ALTER TABLE `time_slots`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `login_id` (`login_id`),
  ADD UNIQUE KEY `admission_number` (`admission_number`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `book_issues`
--
ALTER TABLE `book_issues`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `buses`
--
ALTER TABLE `buses`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `bus_allocations`
--
ALTER TABLE `bus_allocations`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `classes`
--
ALTER TABLE `classes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `examinations`
--
ALTER TABLE `examinations`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `exam_results`
--
ALTER TABLE `exam_results`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `exam_subjects`
--
ALTER TABLE `exam_subjects`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `fees`
--
ALTER TABLE `fees`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fee_payments`
--
ALTER TABLE `fee_payments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `homework`
--
ALTER TABLE `homework`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `id_card_designs`
--
ALTER TABLE `id_card_designs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `id_card_print_logs`
--
ALTER TABLE `id_card_print_logs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `library_books`
--
ALTER TABLE `library_books`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `notices`
--
ALTER TABLE `notices`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `notice_read_status`
--
ALTER TABLE `notice_read_status`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_documents`
--
ALTER TABLE `student_documents`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `study_materials`
--
ALTER TABLE `study_materials`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `teacher_attendance`
--
ALTER TABLE `teacher_attendance`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `teacher_leaves`
--
ALTER TABLE `teacher_leaves`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `teacher_subjects`
--
ALTER TABLE `teacher_subjects`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `timetable`
--
ALTER TABLE `timetable`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `time_slots`
--
ALTER TABLE `time_slots`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=213;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD CONSTRAINT `activity_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `book_issues`
--
ALTER TABLE `book_issues`
  ADD CONSTRAINT `book_issues_ibfk_1` FOREIGN KEY (`book_id`) REFERENCES `library_books` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `book_issues_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `bus_allocations`
--
ALTER TABLE `bus_allocations`
  ADD CONSTRAINT `bus_allocations_ibfk_1` FOREIGN KEY (`bus_id`) REFERENCES `buses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bus_allocations_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `classes`
--
ALTER TABLE `classes`
  ADD CONSTRAINT `classes_ibfk_1` FOREIGN KEY (`class_teacher_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `classes_ibfk_2` FOREIGN KEY (`class_teacher_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `exam_results`
--
ALTER TABLE `exam_results`
  ADD CONSTRAINT `exam_results_ibfk_1` FOREIGN KEY (`exam_subject_id`) REFERENCES `exam_subjects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `exam_results_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `exam_subjects`
--
ALTER TABLE `exam_subjects`
  ADD CONSTRAINT `exam_subjects_ibfk_1` FOREIGN KEY (`exam_id`) REFERENCES `examinations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `exam_subjects_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `fees`
--
ALTER TABLE `fees`
  ADD CONSTRAINT `fees_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `fee_payments`
--
ALTER TABLE `fee_payments`
  ADD CONSTRAINT `fk_fee_payments_collector` FOREIGN KEY (`collected_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_fee_payments_fee` FOREIGN KEY (`fee_id`) REFERENCES `fees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_fee_payments_student` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `homework`
--
ALTER TABLE `homework`
  ADD CONSTRAINT `homework_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `homework_ibfk_2` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`),
  ADD CONSTRAINT `homework_ibfk_3` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`);

--
-- Constraints for table `notices`
--
ALTER TABLE `notices`
  ADD CONSTRAINT `notices_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notice_read_status`
--
ALTER TABLE `notice_read_status`
  ADD CONSTRAINT `notice_read_status_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `notice_read_status_ibfk_2` FOREIGN KEY (`notice_id`) REFERENCES `notices` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `students_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `students_ibfk_2` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `students_ibfk_3` FOREIGN KEY (`bus_allocation_id`) REFERENCES `bus_allocations` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `student_documents`
--
ALTER TABLE `student_documents`
  ADD CONSTRAINT `student_documents_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_documents_ibfk_2` FOREIGN KEY (`issued_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `study_materials`
--
ALTER TABLE `study_materials`
  ADD CONSTRAINT `study_materials_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `study_materials_ibfk_2` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`),
  ADD CONSTRAINT `study_materials_ibfk_3` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`);

--
-- Constraints for table `subjects`
--
ALTER TABLE `subjects`
  ADD CONSTRAINT `subjects_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `subjects_ibfk_2` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `teachers`
--
ALTER TABLE `teachers`
  ADD CONSTRAINT `teachers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `teacher_attendance`
--
ALTER TABLE `teacher_attendance`
  ADD CONSTRAINT `teacher_attendance_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `teacher_leaves`
--
ALTER TABLE `teacher_leaves`
  ADD CONSTRAINT `teacher_leaves_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `teacher_subjects`
--
ALTER TABLE `teacher_subjects`
  ADD CONSTRAINT `teacher_subjects_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `teacher_subjects_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `teacher_subjects_ibfk_3` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `timetable`
--
ALTER TABLE `timetable`
  ADD CONSTRAINT `timetable_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `timetable_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `timetable_ibfk_3` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
