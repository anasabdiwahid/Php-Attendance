<?php
// reports.php
require_once __DIR__ . '/includes/header.php';
requireAdmin();
$pdo = getDB();

// Handle CSV Export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=exam_attendance_report_' . date('Y-m-d') . '.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Record ID', 'Student ID', 'Student Name', 'Exam Name', 'Course Code', 'Room', 'Timestamp', 'Eligibility', 'Reason', 'Sync Status']);

    $sql = "
        SELECT ar.*, s.full_name as student_name, e.exam_name, e.course_code, e.exam_room
        FROM `attendance_records` ar
        LEFT JOIN `students` s ON ar.student_id = s.id
        LEFT JOIN `examinations` e ON ar.exam_id = e.id
        ORDER BY ar.time_stamp DESC
    ";
    $stmt = $pdo->query($sql);
    while ($row = $stmt->fetch()) {
        fputcsv($output, [
            $row['id'],
            $row['student_code'],
            $row['student_name'],
            $row['exam_name'],
            $row['course_code'],
            $row['exam_room'],
            $row['time_stamp'],
            $row['eligibility_status'],
            $row['eligibility_reason'],
            $row['sync_status']
        ]);
    }
    fclose($output);
    exit;
}

$examFilter = intval($_GET['exam_id'] ?? 0);
$statusFilter = trim($_GET['status'] ?? '');

$where = [];
$params = [];

if ($examFilter > 0) {
    $where[] = "ar.exam_id = ?";
    $params[] = $examFilter;
}
if (!empty($statusFilter)) {
    $where[] = "ar.eligibility_status = ?";
    $params[] = $statusFilter;
}

$whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

$recordsStmt = $pdo->prepare("
    SELECT ar.*, s.full_name as student_name, s.faculty, s.department, e.exam_name, e.course_code, e.exam_room, u.full_name as invigilator_name
    FROM `attendance_records` ar
    LEFT JOIN `students` s ON ar.student_id = s.id
    LEFT JOIN `examinations` e ON ar.exam_id = e.id
    LEFT JOIN `users` u ON ar.invigilator_id = u.id
    {$whereClause}
    ORDER BY ar.time_stamp DESC
");
$recordsStmt->execute($params);
$records = $recordsStmt->fetchAll();

$exams = $pdo->query("SELECT id, exam_name, course_code FROM `examinations` ORDER BY `exam_name` ASC")->fetchAll();
$qrScans = $pdo->query("SELECT qs.*, e.course_code FROM `qr_scans` qs LEFT JOIN `examinations` e ON qs.exam_id = e.id ORDER BY qs.scanned_at DESC LIMIT 10")->fetchAll();
?>

<div class="space-y-6 fade-up">
    <div class="flex items-center justify-between flex-wrap gap-3">
        <div>
            <h1 class="font-display text-2xl text-ledger-900 font-semibold">Attendance Reports</h1>
            <p class="text-ledger-400 text-sm mt-1">Audit trail, verified attendance records, and export reports.</p>
        </div>
        <a href="reports.php?export=csv" class="flex items-center gap-2 bg-ledger-900 hover:bg-ledger-800 text-white text-sm font-medium px-4 py-2.5 rounded-xl transition-colors shadow-sm">
            <i data-lucide="download" class="w-4 h-4"></i> Export CSV
        </a>
    </div>

    <!-- Filters Bar -->
    <div class="bg-white rounded-2xl border border-ledger-100 shadow-card p-4">
        <form method="GET" action="reports.php" class="flex flex-wrap items-center gap-4">
            <div class="w-full sm:w-64">
                <label class="block text-[11px] font-semibold uppercase text-ledger-400 mb-1">Filter by Exam</label>
                <select name="exam_id" class="w-full px-3 py-2 border border-ledger-200 rounded-xl text-xs font-medium focus:outline-none focus:ring-2 focus:ring-seal/40 focus:border-seal">
                    <option value="0">All Examinations</option>
                    <?php foreach ($exams as $e): ?>
                        <option value="<?= $e['id'] ?>" <?= $examFilter === $e['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($e['course_code']) ?> - <?= htmlspecialchars($e['exam_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="w-full sm:w-48">
                <label class="block text-[11px] font-semibold uppercase text-ledger-400 mb-1">Eligibility Status</label>
                <select name="status" class="w-full px-3 py-2 border border-ledger-200 rounded-xl text-xs font-medium focus:outline-none focus:ring-2 focus:ring-seal/40 focus:border-seal">
                    <option value="">All Statuses</option>
                    <option value="Eligible" <?= $statusFilter === 'Eligible' ? 'selected' : '' ?>>Eligible</option>
                    <option value="Not Eligible" <?= $statusFilter === 'Not Eligible' ? 'selected' : '' ?>>Not Eligible</option>
                </select>
            </div>

            <div class="pt-5">
                <button type="submit" class="px-4 py-2 bg-ledger-900 hover:bg-ledger-800 text-white text-xs font-medium rounded-xl transition-colors shadow-sm">
                    Apply Filter
                </button>
                <?php if ($examFilter || $statusFilter): ?>
                    <a href="reports.php" class="ml-2 text-xs text-ledger-400 hover:text-ledger-700 underline">Reset</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Attendance Records Table -->
    <div class="bg-white rounded-2xl border border-ledger-100 shadow-card overflow-x-auto">
        <div class="px-6 py-4 border-b border-ledger-100 flex items-center justify-between">
            <h2 class="font-display text-base font-semibold text-ledger-900">Attendance Log (<?= count($records) ?> records)</h2>
        </div>
        <table class="w-full text-sm text-left">
            <thead class="bg-ledger-50/75 border-b border-ledger-100 text-ledger-400 text-xs font-semibold uppercase tracking-wider">
                <tr>
                    <th class="px-6 py-3.5">Student</th>
                    <th class="px-6 py-3.5">Exam</th>
                    <th class="px-6 py-3.5">Timestamp</th>
                    <th class="px-6 py-3.5">Eligibility</th>
                    <th class="px-6 py-3.5">Invigilator</th>
                    <th class="px-6 py-3.5">Sync</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-ledger-100">
                <?php if (empty($records)): ?>
                    <tr>
                        <td colspan="6" class="px-6 py-10 text-center text-ledger-400 text-sm">No records match the current filter.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($records as $r): ?>
                        <tr class="hover:bg-ledger-50/40 transition-colors">
                            <td class="px-6 py-4">
                                <p class="font-medium text-ledger-900"><?= htmlspecialchars($r['student_name'] ?: 'Unknown') ?></p>
                                <p class="font-mono text-xs text-seal font-semibold"><?= htmlspecialchars($r['student_code']) ?></p>
                            </td>
                            <td class="px-6 py-4 text-xs">
                                <p class="font-medium text-ledger-800"><?= htmlspecialchars($r['exam_name']) ?></p>
                                <p class="text-ledger-400"><?= htmlspecialchars($r['course_code']) ?> · Room <?= htmlspecialchars($r['exam_room']) ?></p>
                            </td>
                            <td class="px-6 py-4 text-xs font-mono text-ledger-600">
                                <?= date('M d, H:i', strtotime($r['time_stamp'])) ?>
                            </td>
                            <td class="px-6 py-4">
                                <?php if ($r['eligibility_status'] === 'Eligible'): ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-eligible/10 text-eligible border border-eligible/30">
                                        Eligible
                                    </span>
                                <?php else: ?>
                                    <div>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-ineligible/10 text-ineligible border border-ineligible/30">
                                            Not Eligible
                                        </span>
                                        <?php if (!empty($r['eligibility_reason'])): ?>
                                            <p class="text-[11px] text-ineligible mt-1 max-w-xs"><?= htmlspecialchars($r['eligibility_reason']) ?></p>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-xs text-ledger-700">
                                <?= htmlspecialchars($r['invigilator_name'] ?: 'System') ?>
                            </td>
                            <td class="px-6 py-4 text-xs">
                                <span class="inline-flex items-center gap-1 font-medium <?= $r['sync_status'] === 'Synced' ? 'text-emerald-700' : 'text-amber-700' ?>">
                                    <span class="w-1.5 h-1.5 rounded-full <?= $r['sync_status'] === 'Synced' ? 'bg-emerald-500' : 'bg-amber-500' ?>"></span>
                                    <?= htmlspecialchars($r['sync_status']) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Recent Scans Audit Log -->
    <div class="bg-white rounded-2xl border border-ledger-100 shadow-card overflow-x-auto">
        <div class="px-6 py-4 border-b border-ledger-100">
            <h2 class="font-display text-base font-semibold text-ledger-900">Recent QR Scans Audit Trail</h2>
        </div>
        <table class="w-full text-sm text-left">
            <thead class="bg-ledger-50/75 border-b border-ledger-100 text-ledger-400 text-xs font-semibold uppercase tracking-wider">
                <tr>
                    <th class="px-6 py-3">Student ID</th>
                    <th class="px-6 py-3">Exam</th>
                    <th class="px-6 py-3">Scan Result</th>
                    <th class="px-6 py-3">Time</th>
                    <th class="px-6 py-3">Message</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-ledger-100 text-xs">
                <?php foreach ($qrScans as $qs): ?>
                    <tr class="hover:bg-ledger-50/40">
                        <td class="px-6 py-3 font-mono font-semibold text-ledger-900"><?= htmlspecialchars($qs['student_code']) ?></td>
                        <td class="px-6 py-3 text-ledger-600"><?= htmlspecialchars($qs['course_code'] ?: '—') ?></td>
                        <td class="px-6 py-3">
                            <span class="font-medium <?= $qs['scan_status'] === 'Eligible' ? 'text-emerald-700' : 'text-red-700' ?>">
                                <?= htmlspecialchars($qs['scan_status']) ?>
                            </span>
                        </td>
                        <td class="px-6 py-3 font-mono text-ledger-400"><?= date('H:i:s', strtotime($qs['scanned_at'])) ?></td>
                        <td class="px-6 py-3 text-ledger-500"><?= htmlspecialchars($qs['message'] ?: '—') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
