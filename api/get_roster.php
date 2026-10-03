<?php
// api/get_roster.php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$pdo = getDB();
$examId = intval($_GET['exam_id'] ?? 0);

if ($examId <= 0) {
    echo json_encode(['success' => true, 'roster' => [], 'attended_count' => 0, 'total_count' => 0]);
    exit;
}

// 1. Fetch Exam
$examStmt = $pdo->prepare("SELECT * FROM `examinations` WHERE `id` = ?");
$examStmt->execute([$examId]);
$exam = $examStmt->fetch();

if (!$exam) {
    echo json_encode(['success' => false, 'message' => 'Exam not found']);
    exit;
}

// 2. Fetch all students in faculty & department
$stuStmt = $pdo->prepare("SELECT * FROM `students` WHERE `faculty` = ? AND `department` = ? ORDER BY `student_id` ASC");
$stuStmt->execute([$exam['faculty'], $exam['department']]);
$students = $stuStmt->fetchAll();

// 3. Fetch attendance records for this exam
$attStmt = $pdo->prepare("SELECT student_id, eligibility_status, time_stamp FROM `attendance_records` WHERE `exam_id` = ?");
$attStmt->execute([$examId]);
$attRecords = [];
foreach ($attStmt->fetchAll() as $row) {
    $attRecords[$row['student_id']] = $row;
}

$roster = [];
$attendedCount = 0;

foreach ($students as $s) {
    $hasAttended = isset($attRecords[$s['id']]);
    if ($hasAttended && $attRecords[$s['id']]['eligibility_status'] === 'Eligible') {
        $attendedCount++;
    }
    $roster[] = [
        'id' => $s['id'],
        'student_id' => $s['student_id'],
        'full_name' => $s['full_name'],
        'fee_status' => $s['fee_status'],
        'absence_count' => $s['absence_count'],
        'attended' => $hasAttended,
        'attendance_status' => $hasAttended ? $attRecords[$s['id']]['eligibility_status'] : 'Unchecked',
        'attended_at' => $hasAttended ? $attRecords[$s['id']]['time_stamp'] : null,
    ];
}

echo json_encode([
    'success' => true,
    'roster' => $roster,
    'total_count' => count($students),
    'attended_count' => $attendedCount,
]);
