<?php
// index.php
require_once __DIR__ . '/includes/header.php';
$pdo = getDB();

if ($isAdmin) {
    // Admin overview metrics
    $totalExams = $pdo->query("SELECT COUNT(*) FROM `examinations`")->fetchColumn();
    $totalPresent = $pdo->query("SELECT COUNT(*) FROM `attendance_records` WHERE `eligibility_status` = 'Eligible'")->fetchColumn();
    $totalNotEligible = $pdo->query("SELECT COUNT(*) FROM `attendance_records` WHERE `eligibility_status` = 'Not Eligible'")->fetchColumn();
    $totalPending = $pdo->query("SELECT COUNT(*) FROM `attendance_records` WHERE `sync_status` = 'Pending'")->fetchColumn();

    $recentExams = $pdo->query("SELECT * FROM `examinations` ORDER BY `exam_date` DESC, `start_time` ASC LIMIT 5")->fetchAll();
} else {
    // Invigilator Dashboard data
    $stmt = $pdo->prepare("
        SELECT e.* 
        FROM `examinations` e
        JOIN `exam_invigilators` ei ON e.id = ei.exam_id
        WHERE ei.user_id = ?
        ORDER BY e.exam_date ASC, e.start_time ASC
    ");
    $stmt->execute([$currentUser['id']]);
    $assignedExams = $stmt->fetchAll();
}
?>

<?php if ($isAdmin): ?>
    <div class="space-y-6 fade-up">
        <div>
            <h1 class="font-display text-2xl text-ledger-900 font-normal">Admin overview</h1>
            <p class="text-ledger-400 text-sm mt-1">System-wide attendance and examination status.</p>
        </div>

        <!-- 4 Stat Cards matching SEAMS StatCard.jsx exactly -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Total Exams -->
            <div class="bg-white rounded-2xl border border-ledger-100 shadow-card p-5 flex flex-col gap-3 fade-up">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wider text-ledger-400">Examinations</span>
                    <div class="w-8 h-8 rounded-lg bg-ledger-50 flex items-center justify-center text-ledger-600">
                        <i data-lucide="calendar-clock" class="w-4 h-4"></i>
                    </div>
                </div>
                <div class="font-display text-3xl text-ledger-900"><?= (int)$totalExams ?></div>
            </div>

            <!-- Present (Eligible) -->
            <div class="bg-white rounded-2xl border border-ledger-100 shadow-card p-5 flex flex-col gap-3 fade-up">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wider text-ledger-400">Present (Eligible)</span>
                    <div class="w-8 h-8 rounded-lg bg-ledger-50 flex items-center justify-center text-eligible">
                        <i data-lucide="check-circle-2" class="w-4 h-4"></i>
                    </div>
                </div>
                <div class="font-display text-3xl text-eligible"><?= (int)$totalPresent ?></div>
            </div>

            <!-- Not Eligible -->
            <div class="bg-white rounded-2xl border border-ledger-100 shadow-card p-5 flex flex-col gap-3 fade-up">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wider text-ledger-400">Not Eligible</span>
                    <div class="w-8 h-8 rounded-lg bg-ledger-50 flex items-center justify-center text-ineligible">
                        <i data-lucide="users" class="w-4 h-4"></i>
                    </div>
                </div>
                <div class="font-display text-3xl text-ineligible"><?= (int)$totalNotEligible ?></div>
            </div>

            <!-- Pending Sync -->
            <div class="bg-white rounded-2xl border border-ledger-100 shadow-card p-5 flex flex-col gap-3 fade-up">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wider text-ledger-400">Pending Sync</span>
                    <div class="w-8 h-8 rounded-lg bg-ledger-50 flex items-center justify-center text-pending">
                        <i data-lucide="clock" class="w-4 h-4"></i>
                    </div>
                </div>
                <div class="font-display text-3xl text-pending"><?= (int)$totalPending ?></div>
            </div>
        </div>

        <!-- Upcoming & Recent Examinations Card -->
        <div class="bg-white rounded-2xl border border-ledger-100 shadow-card">
            <div class="flex items-center justify-between px-6 py-4 border-b border-ledger-100">
                <h2 class="font-display text-lg text-ledger-900 font-normal">Upcoming & recent examinations</h2>
                <a href="exams.php" class="text-sm text-seal-dark hover:underline flex items-center gap-1 font-medium">
                    View all <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                </a>
            </div>
            <div class="divide-y divide-ledger-100">
                <?php if (empty($recentExams)): ?>
                    <p class="px-6 py-8 text-center text-ledger-400 text-sm">No examinations scheduled yet.</p>
                <?php else: ?>
                    <?php foreach ($recentExams as $exam): ?>
                        <?php
                            $toneClass = 'bg-ledger-100 text-ledger-600 border-ledger-200';
                            if ($exam['status'] === 'ongoing') $toneClass = 'bg-eligible/10 text-eligible border-eligible/30';
                            elseif ($exam['status'] === 'scheduled') $toneClass = 'bg-pending/10 text-pending border-pending/30';
                        ?>
                        <div class="flex items-center justify-between px-6 py-4 hover:bg-ledger-50/50 transition-colors">
                            <div>
                                <p class="font-medium text-ledger-900 text-sm"><?= htmlspecialchars($exam['exam_name']) ?></p>
                                <p class="text-ledger-400 text-xs mt-0.5">
                                    <?= htmlspecialchars($exam['course_code']) ?> · <?= htmlspecialchars($exam['exam_room']) ?> · <?= date('M j, Y', strtotime($exam['exam_date'])) ?>
                                </p>
                            </div>
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium border capitalize <?= $toneClass ?>">
                                <?= htmlspecialchars($exam['status']) ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php else: ?>
    <!-- Invigilator Dashboard matching InvigilatorDashboard from React -->
    <div class="space-y-6 fade-up">
        <div>
            <?php 
                $fullName = $currentUser['full_name'] ?? 'Invigilator';
                $firstName = explode(' ', $fullName)[0]; 
            ?>
            <h1 class="font-display text-2xl text-ledger-900 font-normal">Welcome, <?= htmlspecialchars($firstName) ?></h1>
            <p class="text-ledger-400 text-sm mt-1">Your assigned examinations for today.</p>
        </div>

        <div class="grid gap-4">
            <?php if (empty($assignedExams)): ?>
                <div class="bg-white rounded-2xl border border-ledger-100 shadow-card p-8 text-center">
                    <p class="text-ledger-400 text-sm">No examinations are currently assigned to you.</p>
                </div>
            <?php else: ?>
                <?php foreach ($assignedExams as $exam): ?>
                    <?php
                        $toneClass = 'bg-ledger-100 text-ledger-600 border-ledger-200';
                        if ($exam['status'] === 'ongoing') $toneClass = 'bg-eligible/10 text-eligible border-eligible/30';
                        elseif ($exam['status'] === 'scheduled') $toneClass = 'bg-pending/10 text-pending border-pending/30';
                    ?>
                    <div class="bg-white rounded-2xl border border-ledger-100 shadow-card p-5 flex items-center justify-between flex-wrap gap-4 fade-up">
                        <div>
                            <p class="font-display text-lg text-ledger-900 font-medium"><?= htmlspecialchars($exam['exam_name']) ?></p>
                            <p class="text-ledger-400 text-sm mt-1">
                                <?= htmlspecialchars($exam['course_code']) ?> · Room <?= htmlspecialchars($exam['exam_room']) ?> · <?= htmlspecialchars($exam['start_time']) ?>–<?= htmlspecialchars($exam['end_time']) ?> · <?= date('M j, Y', strtotime($exam['exam_date'])) ?>
                            </p>
                            <div class="mt-2">
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium border capitalize <?= $toneClass ?>">
                                    <?= htmlspecialchars($exam['status']) ?>
                                </span>
                            </div>
                        </div>
                        <a href="scan.php?exam_id=<?= $exam['id'] ?>" class="flex items-center gap-2 bg-ledger-900 hover:bg-ledger-800 text-white text-sm font-medium px-4 py-2.5 rounded-xl transition-colors shrink-0 shadow-sm">
                            <i data-lucide="scan-line" class="w-4 h-4"></i> Start attendance
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
