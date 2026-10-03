<?php
// api/scan_check.php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$currentUser = $_SESSION['user'];
$pdo = getDB();

$raw = file_get_contents('php://input');
$data = json_decode($raw, true) ?? $_POST;

$studentCode = trim($data['student_id'] ?? '');
$examId = intval($data['exam_id'] ?? 0);

if (empty($studentCode)) {
    echo json_encode(['success' => false, 'message' => 'Please provide or scan a Student ID.']);
    exit;
}

if ($examId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Please select an examination first.']);
    exit;
}

// 1. Fetch Exam
$examStmt = $pdo->prepare("SELECT * FROM `examinations` WHERE `id` = ?");
$examStmt->execute([$examId]);
$exam = $examStmt->fetch();

if (!$exam) {
    echo json_encode(['success' => false, 'message' => 'Selected examination was not found.']);
    exit;
}

// 2. Fetch Student
$stuStmt = $pdo->prepare("SELECT * FROM `students` WHERE `student_id` = ?");
$stuStmt->execute([$studentCode]);
$student = $stuStmt->fetch();

if (!$student) {
    echo json_encode(['success' => false, 'message' => "Student ID '{$studentCode}' is not registered in the student directory."]);
    exit;
}

// 3. Check Eligibility
$eligibility = checkEligibility($student, $exam);
$eligibilityStatus = $eligibility['eligible'] ? 'Eligible' : 'Not Eligible';
$reason = $eligibility['reason'];

// 4. Check if already marked present in this exam
$checkAtt = $pdo->prepare("SELECT id FROM `attendance_records` WHERE `student_id` = ? AND `exam_id` = ?");
$checkAtt->execute([$student['id'], $examId]);
$existing = $checkAtt->fetch();

if ($existing) {
    $attStmt = $pdo->prepare("UPDATE `attendance_records` SET `eligibility_status` = ?, `eligibility_reason` = ?, `time_stamp` = NOW(), `invigilator_id` = ? WHERE `id` = ?");
    $attStmt->execute([$eligibilityStatus, $reason, $currentUser['id'], $existing['id']]);
} else {
    $attStmt = $pdo->prepare("INSERT INTO `attendance_records` (`student_id`, `student_code`, `exam_id`, `invigilator_id`, `eligibility_status`, `eligibility_reason`, `recorded_offline`, `sync_status`, `time_stamp`) VALUES (?, ?, ?, ?, ?, ?, 0, 'Synced', NOW())");
    $attStmt->execute([$student['id'], $student['student_id'], $examId, $currentUser['id'], $eligibilityStatus, $reason]);
}

// 5. Log QR Scan
$scanLog = $pdo->prepare("INSERT INTO `qr_scans` (`student_code`, `exam_id`, `invigilator_id`, `scan_status`, `message`, `scanned_at`) VALUES (?, ?, ?, ?, ?, NOW())");
$scanLog->execute([$student['student_id'], $examId, $currentUser['id'], $eligibilityStatus, $eligibility['eligible'] ? 'Verified and recorded' : $reason]);

// Return payload
echo json_encode([
    'success' => true,
    'eligible' => $eligibility['eligible'],
    'eligibility_status' => $eligibilityStatus,
    'reason' => $reason,
    'student' => [
        'id' => $student['id'],
        'student_id' => $student['student_id'],
        'full_name' => $student['full_name'],
        'faculty' => $student['faculty'],
        'department' => $student['department'],
        'class_name' => $student['class_name'],
        'fee_status' => $student['fee_status'],
        'absence_count' => $student['absence_count'],
        'email' => $student['email'],
    ],
    'timestamp' => date('Y-m-d H:i:s'),
]);
