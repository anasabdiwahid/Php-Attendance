-- Database: `exam_attendance_db`
CREATE DATABASE IF NOT EXISTS `exam_attendance_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `exam_attendance_db`;

-- Table structure for `users`
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `full_name` VARCHAR(150) NOT NULL,
    `username` VARCHAR(60) NOT NULL UNIQUE,
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `phone` VARCHAR(30) DEFAULT '',
    `invigilator_id` VARCHAR(50) DEFAULT '',
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('admin', 'invigilator') NOT NULL DEFAULT 'invigilator',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table structure for `faculties`
CREATE TABLE IF NOT EXISTS `faculties` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL UNIQUE,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table structure for `departments`
CREATE TABLE IF NOT EXISTS `departments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `faculty_name` VARCHAR(100) NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table structure for `classes`
CREATE TABLE IF NOT EXISTS `classes` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `faculty` VARCHAR(100) NOT NULL,
    `department` VARCHAR(100) NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table structure for `students`
CREATE TABLE IF NOT EXISTS `students` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `student_id` VARCHAR(50) NOT NULL UNIQUE,
    `full_name` VARCHAR(150) NOT NULL,
    `faculty` VARCHAR(100) NOT NULL,
    `department` VARCHAR(100) NOT NULL,
    `class_name` VARCHAR(100) DEFAULT '',
    `fee_status` ENUM('Cleared', 'Not Cleared') NOT NULL DEFAULT 'Cleared',
    `absence_count` INT NOT NULL DEFAULT 0,
    `photo_url` VARCHAR(255) DEFAULT '',
    `email` VARCHAR(150) DEFAULT '',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table structure for `examinations`
CREATE TABLE IF NOT EXISTS `examinations` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `exam_name` VARCHAR(150) NOT NULL,
    `course_code` VARCHAR(50) NOT NULL,
    `exam_date` DATE NOT NULL,
    `start_time` VARCHAR(10) NOT NULL,
    `end_time` VARCHAR(10) NOT NULL,
    `exam_room` VARCHAR(50) NOT NULL,
    `faculty` VARCHAR(100) NOT NULL,
    `department` VARCHAR(100) NOT NULL,
    `require_fee_check` TINYINT(1) NOT NULL DEFAULT 1,
    `status` ENUM('scheduled', 'ongoing', 'completed') NOT NULL DEFAULT 'scheduled',
    `created_by` INT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table structure for `exam_invigilators`
CREATE TABLE IF NOT EXISTS `exam_invigilators` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `exam_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    UNIQUE KEY `exam_user_unique` (`exam_id`, `user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table structure for `attendance_records`
CREATE TABLE IF NOT EXISTS `attendance_records` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `student_id` INT NOT NULL,
    `student_code` VARCHAR(50) NOT NULL,
    `exam_id` INT NOT NULL,
    `invigilator_id` INT NULL,
    `time_stamp` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `eligibility_status` ENUM('Eligible', 'Not Eligible') NOT NULL DEFAULT 'Eligible',
    `eligibility_reason` TEXT DEFAULT NULL,
    `recorded_offline` TINYINT(1) NOT NULL DEFAULT 0,
    `sync_status` ENUM('Synced', 'Pending') NOT NULL DEFAULT 'Synced',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table structure for `qr_scans`
CREATE TABLE IF NOT EXISTS `qr_scans` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `student_code` VARCHAR(50) NOT NULL,
    `exam_id` INT NULL,
    `invigilator_id` INT NULL,
    `scan_status` VARCHAR(50) NOT NULL,
    `message` TEXT DEFAULT NULL,
    `scanned_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed Data
-- Passwords: Admin@123 for admins, Invigilator@123 for invigilators
INSERT INTO `users` (`id`, `full_name`, `username`, `email`, `phone`, `invigilator_id`, `password`, `role`, `is_active`) VALUES
(1, 'Amina Hassan (System Admin)', 'admin1', 'admin1@university.edu', '', '', '$2y$10$wTfH.rW.qZt5wJjKkWvDfe7PjG20N70Z1sR8Jj5oQ86I2EwYh6G2K', 'admin', 1),
(2, 'Mohamed Ali (Exams Officer)', 'admin2', 'admin2@university.edu', '', '', '$2y$10$wTfH.rW.qZt5wJjKkWvDfe7PjG20N70Z1sR8Jj5oQ86I2EwYh6G2K', 'admin', 1),
(3, 'Fartun Warsame', 'invig1', 'invig1@university.edu', '+252610000001', 'INV-001', '$2y$10$tZ2i8U85z4qEa9cOmgQG4O9N9JvhbHh0w1LdKl1pLmI5iZ1K6k7a.', 'invigilator', 1),
(4, 'Abdirahman Yusuf', 'invig2', 'invig2@university.edu', '+252610000002', 'INV-002', '$2y$10$tZ2i8U85z4qEa9cOmgQG4O9N9JvhbHh0w1LdKl1pLmI5iZ1K6k7a.', 'invigilator', 1),
(5, 'Sahra Nur', 'invig3', 'invig3@university.edu', '+252610000003', 'INV-003', '$2y$10$tZ2i8U85z4qEa9cOmgQG4O9N9JvhbHh0w1LdKl1pLmI5iZ1K6k7a.', 'invigilator', 1);

-- Faculties
INSERT INTO `faculties` (`id`, `name`) VALUES
(1, 'Computer Science'),
(2, 'Business'),
(3, 'Engineering');

-- Departments
INSERT INTO `departments` (`id`, `faculty_name`, `name`) VALUES
(1, 'Computer Science', 'Software Engineering'),
(2, 'Computer Science', 'Information Technology'),
(3, 'Business', 'Accounting'),
(4, 'Business', 'Marketing'),
(5, 'Engineering', 'Civil Engineering');

-- Classes
INSERT INTO `classes` (`id`, `name`, `faculty`, `department`) VALUES
(1, 'SE Year 2', 'Computer Science', 'Software Engineering'),
(2, 'IT Year 2', 'Computer Science', 'Information Technology'),
(3, 'Accounting Year 3', 'Business', 'Accounting'),
(4, 'Marketing Year 3', 'Business', 'Marketing'),
(5, 'Civil Year 4', 'Engineering', 'Civil Engineering');

-- Students
INSERT INTO `students` (`id`, `student_id`, `full_name`, `faculty`, `department`, `class_name`, `fee_status`, `absence_count`, `email`) VALUES
(1, 'HU2001', 'Hodan Ibrahim', 'Computer Science', 'Software Engineering', 'SE Year 2', 'Cleared', 0, 'hodan.ibrahim@student.edu'),
(2, 'HU2002', 'Khalid Omar', 'Computer Science', 'Software Engineering', 'SE Year 2', 'Cleared', 0, 'khalid.omar@student.edu'),
(3, 'HU2003', 'Ifrah Abdullahi', 'Computer Science', 'Software Engineering', 'SE Year 2', 'Not Cleared', 0, 'ifrah.abdullahi@student.edu'),
(4, 'HU2004', 'Yusuf Ahmed', 'Computer Science', 'Information Technology', 'IT Year 2', 'Cleared', 0, 'yusuf.ahmed@student.edu'),
(5, 'HU2005', 'Nasra Mohamed', 'Computer Science', 'Information Technology', 'IT Year 2', 'Cleared', 0, 'nasra.mohamed@student.edu'),
(6, 'HU2006', 'Bashir Adan', 'Computer Science', 'Information Technology', 'IT Year 2', 'Not Cleared', 0, 'bashir.adan@student.edu'),
(7, 'HU3001', 'Faadumo Ali', 'Business', 'Accounting', 'Accounting Year 3', 'Cleared', 0, 'faadumo.ali@student.edu'),
(8, 'HU3002', 'Cabdiraxmaan Sheikh', 'Business', 'Accounting', 'Accounting Year 3', 'Cleared', 5, 'cabdiraxmaan.sheikh@student.edu'),
(9, 'HU3003', 'Halima Warsame', 'Business', 'Marketing', 'Marketing Year 3', 'Cleared', 0, 'halima.warsame@student.edu'),
(10, 'HU4001', 'Deeqa Farah', 'Engineering', 'Civil Engineering', 'Civil Year 4', 'Cleared', 0, 'deeqa.farah@student.edu');

-- Exams
INSERT INTO `examinations` (`id`, `exam_name`, `course_code`, `exam_date`, `start_time`, `end_time`, `exam_room`, `faculty`, `department`, `require_fee_check`, `status`, `created_by`) VALUES
(1, 'Data Structures & Algorithms Final', 'CS301', CURDATE(), '09:00', '11:00', 'Room A101', 'Computer Science', 'Software Engineering', 1, 'ongoing', 1),
(2, 'Database Systems Midterm', 'CS210', CURDATE(), '13:00', '15:00', 'Room A102', 'Computer Science', 'Information Technology', 1, 'scheduled', 1),
(3, 'Principles of Accounting Final', 'BUS205', DATE_ADD(CURDATE(), INTERVAL 1 DAY), '09:00', '11:30', 'Room B201', 'Business', 'Accounting', 1, 'scheduled', 2),
(4, 'Structural Engineering Final', 'ENG410', DATE_SUB(CURDATE(), INTERVAL 2 DAY), '09:00', '12:00', 'Room C301', 'Engineering', 'Civil Engineering', 0, 'completed', 2);

-- Exam Invigilators
INSERT INTO `exam_invigilators` (`exam_id`, `user_id`) VALUES
(1, 3), (2, 4), (3, 5), (4, 3);
