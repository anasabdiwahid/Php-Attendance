<?php
// config/db.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'exam_attendance_db');

function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            // First connect without DB to ensure DB exists
            $pdo = new PDO("mysql:host=" . DB_HOST . ";charset=utf8mb4", DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE `" . DB_NAME . "`");

            // Ensure tables and seed exist
            initTablesAndSeed($pdo);
        } catch (PDOException $e) {
            die("Database Connection Error: " . $e->getMessage());
        }
    }
    return $pdo;
}

function initTablesAndSeed($pdo) {
    // Check if users table exists
    $tableExists = $pdo->query("SHOW TABLES LIKE 'users'")->rowCount() > 0;
    if (!$tableExists) {
        // Create tables
        $pdo->exec("
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
            ) ENGINE=InnoDB;

            CREATE TABLE IF NOT EXISTS `faculties` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `name` VARCHAR(100) NOT NULL UNIQUE,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB;

            CREATE TABLE IF NOT EXISTS `departments` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `faculty_name` VARCHAR(100) NOT NULL,
                `name` VARCHAR(100) NOT NULL,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB;

            CREATE TABLE IF NOT EXISTS `classes` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `name` VARCHAR(100) NOT NULL,
                `faculty` VARCHAR(100) NOT NULL,
                `department` VARCHAR(100) NOT NULL,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB;

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
            ) ENGINE=InnoDB;

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
            ) ENGINE=InnoDB;

            CREATE TABLE IF NOT EXISTS `exam_invigilators` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `exam_id` INT NOT NULL,
                `user_id` INT NOT NULL,
                UNIQUE KEY `exam_user_unique` (`exam_id`, `user_id`)
            ) ENGINE=InnoDB;

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
            ) ENGINE=InnoDB;

            CREATE TABLE IF NOT EXISTS `qr_scans` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `student_code` VARCHAR(50) NOT NULL,
                `exam_id` INT NULL,
                `invigilator_id` INT NULL,
                `scan_status` VARCHAR(50) NOT NULL,
                `message` TEXT DEFAULT NULL,
                `scanned_at` DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB;
        ");

        // Seed initial data identical to SEAMS
        seedInitialData($pdo);
    }
}

function seedInitialData($pdo) {
    // Passwords hashed with bcrypt
    $passAdmin = password_hash('Admin@123', PASSWORD_BCRYPT);
    $passInvig = password_hash('Invigilator@123', PASSWORD_BCRYPT);

    // 1. Users
    $userStmt = $pdo->prepare("INSERT INTO `users` (`full_name`, `username`, `email`, `phone`, `invigilator_id`, `password`, `role`, `is_active`) VALUES (?, ?, ?, ?, ?, ?, ?, 1)");
    $userStmt->execute(['Amina Hassan (System Admin)', 'admin1', 'admin1@university.edu', '', '', $passAdmin, 'admin']);
    $userStmt->execute(['Mohamed Ali (Exams Officer)', 'admin2', 'admin2@university.edu', '', '', $passAdmin, 'admin']);
    $userStmt->execute(['Fartun Warsame', 'invig1', 'invig1@university.edu', '+252610000001', 'INV-001', $passInvig, 'invigilator']);
    $userStmt->execute(['Abdirahman Yusuf', 'invig2', 'invig2@university.edu', '+252610000002', 'INV-002', $passInvig, 'invigilator']);
    $userStmt->execute(['Sahra Nur', 'invig3', 'invig3@university.edu', '+252610000003', 'INV-003', $passInvig, 'invigilator']);

    // 2. Faculties & Departments
    $pdo->exec("INSERT INTO `faculties` (`name`) VALUES ('Computer Science'), ('Business'), ('Engineering')");
    $deptStmt = $pdo->prepare("INSERT INTO `departments` (`faculty_name`, `name`) VALUES (?, ?)");
    $deptStmt->execute(['Computer Science', 'Software Engineering']);
    $deptStmt->execute(['Computer Science', 'Information Technology']);
    $deptStmt->execute(['Business', 'Accounting']);
    $deptStmt->execute(['Business', 'Marketing']);
    $deptStmt->execute(['Engineering', 'Civil Engineering']);

    // 3. Classes
    $classStmt = $pdo->prepare("INSERT INTO `classes` (`name`, `faculty`, `department`) VALUES (?, ?, ?)");
    $classStmt->execute(['SE Year 2', 'Computer Science', 'Software Engineering']);
    $classStmt->execute(['IT Year 2', 'Computer Science', 'Information Technology']);
    $classStmt->execute(['Accounting Year 3', 'Business', 'Accounting']);
    $classStmt->execute(['Marketing Year 3', 'Business', 'Marketing']);
    $classStmt->execute(['Civil Year 4', 'Engineering', 'Civil Engineering']);

    // 4. Students
    $studentStmt = $pdo->prepare("INSERT INTO `students` (`student_id`, `full_name`, `faculty`, `department`, `class_name`, `fee_status`, `absence_count`, `email`) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $studentStmt->execute(['HU2001', 'Hodan Ibrahim', 'Computer Science', 'Software Engineering', 'SE Year 2', 'Cleared', 0, 'hodan.ibrahim@student.edu']);
    $studentStmt->execute(['HU2002', 'Khalid Omar', 'Computer Science', 'Software Engineering', 'SE Year 2', 'Cleared', 0, 'khalid.omar@student.edu']);
    $studentStmt->execute(['HU2003', 'Ifrah Abdullahi', 'Computer Science', 'Software Engineering', 'SE Year 2', 'Not Cleared', 0, 'ifrah.abdullahi@student.edu']);
    $studentStmt->execute(['HU2004', 'Yusuf Ahmed', 'Computer Science', 'Information Technology', 'IT Year 2', 'Cleared', 0, 'yusuf.ahmed@student.edu']);
    $studentStmt->execute(['HU2005', 'Nasra Mohamed', 'Computer Science', 'Information Technology', 'IT Year 2', 'Cleared', 0, 'nasra.mohamed@student.edu']);
    $studentStmt->execute(['HU2006', 'Bashir Adan', 'Computer Science', 'Information Technology', 'IT Year 2', 'Not Cleared', 0, 'bashir.adan@student.edu']);
    $studentStmt->execute(['HU3001', 'Faadumo Ali', 'Business', 'Accounting', 'Accounting Year 3', 'Cleared', 0, 'faadumo.ali@student.edu']);
    $studentStmt->execute(['HU3002', 'Cabdiraxmaan Sheikh', 'Business', 'Accounting', 'Accounting Year 3', 'Cleared', 5, 'cabdiraxmaan.sheikh@student.edu']);
    $studentStmt->execute(['HU3003', 'Halima Warsame', 'Business', 'Marketing', 'Marketing Year 3', 'Cleared', 0, 'halima.warsame@student.edu']);
    $studentStmt->execute(['HU4001', 'Deeqa Farah', 'Engineering', 'Civil Engineering', 'Civil Year 4', 'Cleared', 0, 'deeqa.farah@student.edu']);

    // 5. Examinations
    $today = date('Y-m-d');
    $tomorrow = date('Y-m-d', strtotime('+1 day'));
    $twoDaysAgo = date('Y-m-d', strtotime('-2 days'));

    $examStmt = $pdo->prepare("INSERT INTO `examinations` (`id`, `exam_name`, `course_code`, `exam_date`, `start_time`, `end_time`, `exam_room`, `faculty`, `department`, `require_fee_check`, `status`, `created_by`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $examStmt->execute([1, 'Data Structures & Algorithms Final', 'CS301', $today, '09:00', '11:00', 'Room A101', 'Computer Science', 'Software Engineering', 1, 'ongoing', 1]);
    $examStmt->execute([2, 'Database Systems Midterm', 'CS210', $today, '13:00', '15:00', 'Room A102', 'Computer Science', 'Information Technology', 1, 'scheduled', 1]);
    $examStmt->execute([3, 'Principles of Accounting Final', 'BUS205', $tomorrow, '09:00', '11:30', 'Room B201', 'Business', 'Accounting', 1, 'scheduled', 2]);
    $examStmt->execute([4, 'Structural Engineering Final', 'ENG410', $twoDaysAgo, '09:00', '12:00', 'Room C301', 'Engineering', 'Civil Engineering', 0, 'completed', 2]);

    // Assigned Invigilators
    $pdo->exec("INSERT INTO `exam_invigilators` (`exam_id`, `user_id`) VALUES (1, 3), (2, 4), (3, 5), (4, 3)");

    // 6. Attendance records
    $attStmt = $pdo->prepare("INSERT INTO `attendance_records` (`student_id`, `student_code`, `exam_id`, `invigilator_id`, `eligibility_status`, `eligibility_reason`, `recorded_offline`, `sync_status`) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $attStmt->execute([1, 'HU2001', 1, 3, 'Eligible', '', 0, 'Synced']);
    $attStmt->execute([2, 'HU2002', 1, 3, 'Eligible', '', 0, 'Synced']);
    $attStmt->execute([3, 'HU2003', 1, 3, 'Not Eligible', "Rejected by finance: this month's payment has not been cleared.", 0, 'Synced']);
    $attStmt->execute([4, 'HU2004', 2, 4, 'Eligible', '', 0, 'Synced']);
    $attStmt->execute([5, 'HU2005', 2, 4, 'Eligible', '', 1, 'Pending']);
    $attStmt->execute([10, 'HU4001', 4, 3, 'Eligible', '', 0, 'Synced']);

    // 7. QR Scan records
    $qrStmt = $pdo->prepare("INSERT INTO `qr_scans` (`student_code`, `exam_id`, `invigilator_id`, `scan_status`, `message`) VALUES (?, ?, ?, ?, ?)");
    $qrStmt->execute(['HU2001', 1, 3, 'Eligible', 'Verified successfully']);
    $qrStmt->execute(['HU2002', 1, 3, 'Eligible', 'Verified successfully']);
    $qrStmt->execute(['HU2003', 1, 3, 'Not Eligible', "Rejected by finance: this month's payment has not been cleared."]);
    $qrStmt->execute(['HU2004', 2, 4, 'Eligible', 'Verified successfully']);
}

// Helper to check authentication
function requireAuth() {
    if (!isset($_SESSION['user'])) {
        header('Location: login.php');
        exit;
    }

    // Ensure all keys exist by syncing with database
    $pdo = getDB();
    $userId = $_SESSION['user']['id'] ?? 0;
    $username = $_SESSION['user']['username'] ?? '';

    if ($userId > 0) {
        $stmt = $pdo->prepare("SELECT id, full_name, username, email, phone, role, invigilator_id FROM `users` WHERE `id` = ?");
        $stmt->execute([$userId]);
        $u = $stmt->fetch();
        if ($u) {
            $_SESSION['user'] = $u;
        }
    } elseif (!empty($username)) {
        $stmt = $pdo->prepare("SELECT id, full_name, username, email, phone, role, invigilator_id FROM `users` WHERE `username` = ?");
        $stmt->execute([$username]);
        $u = $stmt->fetch();
        if ($u) {
            $_SESSION['user'] = $u;
        }
    }

    if (empty($_SESSION['user']['full_name'])) {
        $_SESSION['user']['full_name'] = $_SESSION['user']['username'] ?? 'User';
    }
    if (empty($_SESSION['user']['role'])) {
        $_SESSION['user']['role'] = 'admin';
    }

    return $_SESSION['user'];
}

function requireAdmin() {
    $user = requireAuth();
    if ($user['role'] !== 'admin') {
        header('Location: index.php');
        exit;
    }
    return $user;
}

// Student eligibility checker logic matching MERN
function checkEligibility($student, $exam) {
    $maxAbsences = 3;
    $absences = (int)($student['absence_count'] ?? 0);

    if ($absences > $maxAbsences) {
        return [
            'eligible' => false,
            'reason' => "Rejected by attendance: {$absences} absences recorded (more than {$maxAbsences} is not allowed)."
        ];
    }

    if ($exam['require_fee_check'] && $student['fee_status'] !== 'Cleared') {
        return [
            'eligible' => false,
            'reason' => "Rejected by finance: this month's payment has not been cleared."
        ];
    }

    if ($student['faculty'] !== $exam['faculty']) {
        return [
            'eligible' => false,
            'reason' => "Student faculty ({$student['faculty']}) does not match exam faculty ({$exam['faculty']})"
        ];
    }

    if ($student['department'] !== $exam['department']) {
        return [
            'eligible' => false,
            'reason' => "Student department ({$student['department']}) does not match exam department ({$exam['department']})"
        ];
    }

    return [
        'eligible' => true,
        'reason' => ''
    ];
}

// Auto-initialize connection
$db = getDB();
