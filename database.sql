-- Create database
CREATE DATABASE IF NOT EXISTS school_erp;
USE school_erp;

-- Admins table
CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(100) NOT NULL
);

-- Students table
CREATE TABLE IF NOT EXISTS students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    login_id VARCHAR(30) NOT NULL UNIQUE, -- For login
    admission_no VARCHAR(30) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    middle_name VARCHAR(100),
    surname VARCHAR(100),
    email VARCHAR(100),
    phone VARCHAR(20),
    father_name VARCHAR(100),
    father_middle_name VARCHAR(100),
    father_title ENUM('Mr', 'Late') DEFAULT 'Mr',
    mother_name VARCHAR(100),
    mother_middle_name VARCHAR(100),
    mother_title ENUM('Mrs', 'Miss', 'Late') DEFAULT 'Mrs',
    bus_no VARCHAR(20),
    travel_mode VARCHAR(20),
    travel_details TEXT,
    subject_list TEXT,
    fees DECIMAL(10,2),
    gender ENUM('Male', 'Female', 'Other'),
    dob DATE,
    class VARCHAR(20),
    section VARCHAR(10),
    address TEXT,
    admission_date DATE,
    blood_group VARCHAR(5),
    emergency_contact VARCHAR(20),
    photo VARCHAR(255),
    library_id VARCHAR(30),
    certification TEXT,
    is_active TINYINT(1) DEFAULT 1
);

-- Teachers table
CREATE TABLE IF NOT EXISTS teachers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id VARCHAR(20) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(100) NOT NULL,
    subject VARCHAR(50)
);

-- Attendance table
CREATE TABLE IF NOT EXISTS attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    date DATE NOT NULL,
    status ENUM('Present', 'Absent', 'Leave') NOT NULL,
    remarks VARCHAR(255),
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);

-- Example admin
INSERT INTO admins (username, password, name) VALUES (
    'admin1',
    '$2y$10$e0NRQw1Qw1Qw1Qw1Qw1QwOe0NRQw1Qw1Qw1Qw1Qw1Qw1Qw1Qw1Qw1Q',
    'Super Admin'
);

-- Example student
INSERT INTO students (login_id, admission_no, name, class, is_active) VALUES (
    'stu001', 'ADM001', 'John Doe', '10A', 1
);

-- Example teacher
INSERT INTO teachers (teacher_id, password, name, subject) VALUES (
    'teach001',
    '$2y$10$e0NRQw1Qw1Qw1Qw1Qw1QwOe0NRQw1Qw1Qw1Qw1Qw1Qw1Qw1Qw1Qw1Q',
    'Jane Smith',
    'Mathematics'
); 