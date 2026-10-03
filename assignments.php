<?php
// assignments.php
require_once __DIR__ . '/includes/header.php';
requireAdmin();
$pdo = getDB();

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $examId = intval($_POST['exam_id'] ?? 0);
    $selectedUsers = $_POST['invigilator_ids'] ?? [];

    if ($examId > 0) {
        // Clear previous
        $pdo->prepare("DELETE FROM `exam_invigilators` WHERE `exam_id` = ?")->execute([$examId]);
        // Insert new
        $ins = $pdo->prepare("INSERT INTO `exam_invigilators` (`exam_id`, `user_id`) VALUES (?, ?)");
        foreach ($selectedUsers as $uId) {
            $ins->execute([$examId, intval($uId)]);
        }
        $message = 'Invigilator assignments updated successfully.';
    }
}

$exams = $pdo->query("SELECT * FROM `examinations` ORDER BY `exam_date` DESC, `start_time` ASC")->fetchAll();
$invigilators = $pdo->query("SELECT id, full_name, username, invigilator_id FROM `users` WHERE `role` = 'invigilator' AND `is_active` = 1 ORDER BY `full_name` ASC")->fetchAll();

// Map assignments
$assignMap = [];
$assignedRows = $pdo->query("SELECT ei.exam_id, u.id, u.full_name, u.invigilator_id FROM `exam_invigilators` ei JOIN `users` u ON ei.user_id = u.id")->fetchAll();
foreach ($assignedRows as $r) {
    $assignMap[$r['exam_id']][] = $r;
}
?>

<div class="space-y-6 fade-up">
    <div>
        <h1 class="font-display text-2xl text-ledger-900 font-semibold">Invigilator Assignments</h1>
        <p class="text-ledger-400 text-sm mt-1">Assign invigilators to examination sessions — one exam, many invigilators.</p>
    </div>

    <?php if ($message): ?>
        <div class="p-3.5 bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm rounded-xl flex items-center gap-2">
            <i data-lucide="check-circle" class="w-4 h-4 shrink-0"></i>
            <span><?= htmlspecialchars($message) ?></span>
        </div>
    <?php endif; ?>

    <div class="bg-white rounded-2xl border border-ledger-100 shadow-card overflow-x-auto">
        <table class="w-full text-sm text-left">
            <thead class="bg-ledger-50/75 border-b border-ledger-100 text-ledger-400 text-xs font-semibold uppercase tracking-wider">
                <tr>
                    <th class="px-6 py-3.5">Examination</th>
                    <th class="px-6 py-3.5">Date & Room</th>
                    <th class="px-6 py-3.5">Assigned Invigilators</th>
                    <th class="px-6 py-3.5 text-right">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-ledger-100">
                <?php if (empty($exams)): ?>
                    <tr>
                        <td colspan="4" class="px-6 py-10 text-center text-ledger-400 text-sm">No examinations found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($exams as $e): ?>
                        <?php $assigned = $assignMap[$e['id']] ?? []; ?>
                        <tr class="hover:bg-ledger-50/40 transition-colors">
                            <td class="px-6 py-4">
                                <p class="font-medium text-ledger-900"><?= htmlspecialchars($e['exam_name']) ?></p>
                                <span class="font-mono text-xs text-seal font-semibold"><?= htmlspecialchars($e['course_code']) ?></span>
                            </td>
                            <td class="px-6 py-4 text-xs">
                                <p class="font-medium text-ledger-800"><?= date('M d, Y', strtotime($e['exam_date'])) ?></p>
                                <p class="text-ledger-400">Room <?= htmlspecialchars($e['exam_room']) ?> (<?= htmlspecialchars($e['start_time']) ?>-<?= htmlspecialchars($e['end_time']) ?>)</p>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-wrap gap-1.5">
                                    <?php if (empty($assigned)): ?>
                                        <span class="text-xs text-ledger-400 italic">No invigilators assigned</span>
                                    <?php else: ?>
                                        <?php foreach ($assigned as $inv): ?>
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-medium bg-ledger-50 text-ledger-800 border border-ledger-200">
                                                <i data-lucide="user-check" class="w-3 h-3 text-seal"></i>
                                                <?= htmlspecialchars($inv['full_name']) ?>
                                            </span>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <?php $assignedIds = array_column($assigned, 'id'); ?>
                                <button onclick='openAssignModal(<?= $e['id'] ?>, <?= json_encode($e['exam_name']) ?>, <?= json_encode($assignedIds) ?>)' class="px-3.5 py-1.5 bg-ledger-900 hover:bg-ledger-800 text-white rounded-xl text-xs font-medium transition-colors shadow-sm">
                                    Assign Invigilators
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Assign Modal -->
<div id="assignModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-ledger-950/60 backdrop-blur-sm">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md border border-ledger-100 overflow-hidden fade-up">
        <div class="px-6 py-4 border-b border-ledger-100 flex items-center justify-between">
            <div>
                <h3 class="font-display text-lg font-semibold text-ledger-900">Assign Invigilators</h3>
                <p id="modalExamName" class="text-xs text-ledger-400 mt-0.5 font-medium"></p>
            </div>
            <button onclick="closeAssignModal()" class="text-ledger-400 hover:text-ledger-700">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        <form method="POST" action="assignments.php" class="p-6 space-y-4">
            <input type="hidden" name="exam_id" id="modalExamId" value="0">
            
            <div class="space-y-2 max-h-60 overflow-y-auto pr-1">
                <?php foreach ($invigilators as $inv): ?>
                    <label class="flex items-center justify-between p-3 rounded-xl border border-ledger-100 hover:bg-ledger-50 cursor-pointer transition-colors">
                        <div class="flex items-center gap-3">
                            <input type="checkbox" name="invigilator_ids[]" value="<?= $inv['id'] ?>" class="invigCheckbox w-4 h-4 rounded text-seal focus:ring-seal">
                            <div>
                                <p class="text-xs font-semibold text-ledger-900"><?= htmlspecialchars($inv['full_name']) ?></p>
                                <p class="text-[11px] font-mono text-ledger-400"><?= htmlspecialchars($inv['username']) ?> · <?= htmlspecialchars($inv['invigilator_id'] ?: 'No ID') ?></p>
                            </div>
                        </div>
                    </label>
                <?php endforeach; ?>
            </div>

            <div class="flex items-center justify-end gap-3 pt-3 border-t border-ledger-100">
                <button type="button" onclick="closeAssignModal()" class="px-4 py-2 text-sm text-ledger-600 hover:bg-ledger-50 rounded-xl transition-colors">
                    Cancel
                </button>
                <button type="submit" class="px-5 py-2 bg-ledger-900 hover:bg-ledger-800 text-white text-sm font-medium rounded-xl transition-colors shadow-sm">
                    Save Assignments
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openAssignModal(examId, examName, assignedIds) {
    document.getElementById('modalExamId').value = examId;
    document.getElementById('modalExamName').textContent = examName;
    
    const checkboxes = document.querySelectorAll('.invigCheckbox');
    checkboxes.forEach(cb => {
        cb.checked = assignedIds.includes(parseInt(cb.value));
    });

    document.getElementById('assignModal').classList.remove('hidden');
    lucide.createIcons();
}
function closeAssignModal() {
    document.getElementById('assignModal').classList.add('hidden');
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
