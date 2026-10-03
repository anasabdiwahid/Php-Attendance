<?php
// exams.php
require_once __DIR__ . '/includes/header.php';
$pdo = getDB();

$message = '';
$error = '';

// Handle Actions (Create / Edit / Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isAdmin) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'save') {
        $id = intval($_POST['id'] ?? 0);
        $examName = trim($_POST['exam_name'] ?? '');
        $courseCode = trim($_POST['course_code'] ?? '');
        $examDate = trim($_POST['exam_date'] ?? '');
        $startTime = trim($_POST['start_time'] ?? '');
        $endTime = trim($_POST['end_time'] ?? '');
        $examRoom = trim($_POST['exam_room'] ?? '');
        $faculty = trim($_POST['faculty'] ?? '');
        $department = trim($_POST['department'] ?? '');
        $requireFee = isset($_POST['require_fee_check']) ? 1 : 0;
        $status = trim($_POST['status'] ?? 'scheduled');

        if (empty($examName) || empty($courseCode) || empty($examDate)) {
            $error = 'Please fill in all required fields.';
        } else {
            if ($id > 0) {
                // Update
                $stmt = $pdo->prepare("UPDATE `examinations` SET `exam_name` = ?, `course_code` = ?, `exam_date` = ?, `start_time` = ?, `end_time` = ?, `exam_room` = ?, `faculty` = ?, `department` = ?, `require_fee_check` = ?, `status` = ? WHERE `id` = ?");
                $stmt->execute([$examName, $courseCode, $examDate, $startTime, $endTime, $examRoom, $faculty, $department, $requireFee, $status, $id]);
                $message = 'Examination updated successfully.';
            } else {
                // Insert
                $stmt = $pdo->prepare("INSERT INTO `examinations` (`exam_name`, `course_code`, `exam_date`, `start_time`, `end_time`, `exam_room`, `faculty`, `department`, `require_fee_check`, `status`, `created_by`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$examName, $courseCode, $examDate, $startTime, $endTime, $examRoom, $faculty, $department, $requireFee, $status, $currentUser['id']]);
                $message = 'Examination created successfully.';
            }
        }
    } elseif ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        if ($id > 0) {
            $pdo->prepare("DELETE FROM `attendance_records` WHERE `exam_id` = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM `exam_invigilators` WHERE `exam_id` = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM `examinations` WHERE `id` = ?")->execute([$id]);
            $message = 'Examination removed successfully.';
        }
    }
}

// Fetch examinations & faculties
if ($isAdmin) {
    $exams = $pdo->query("SELECT * FROM `examinations` ORDER BY `exam_date` DESC, `start_time` ASC")->fetchAll();
} else {
    $stmt = $pdo->prepare("
        SELECT e.* 
        FROM `examinations` e
        JOIN `exam_invigilators` ei ON e.id = ei.exam_id
        WHERE ei.user_id = ?
        ORDER BY e.exam_date ASC, e.start_time ASC
    ");
    $stmt->execute([$currentUser['id']]);
    $exams = $stmt->fetchAll();
}

$faculties = $pdo->query("SELECT * FROM `faculties` ORDER BY `name` ASC")->fetchAll();
$departments = $pdo->query("SELECT * FROM `departments` ORDER BY `name` ASC")->fetchAll();
?>

<div class="space-y-6 fade-up">
    <div class="flex items-center justify-between flex-wrap gap-3">
        <div>
            <h1 class="font-display text-2xl text-ledger-900 font-semibold">Examinations</h1>
            <p class="text-ledger-400 text-sm mt-1">All scheduled and past examination sessions.</p>
        </div>
        <?php if ($isAdmin): ?>
            <button onclick="openModal()" class="flex items-center gap-2 bg-ledger-900 hover:bg-ledger-800 text-white text-sm font-medium px-4 py-2.5 rounded-xl transition-colors shadow-sm">
                <i data-lucide="plus" class="w-4 h-4"></i> New examination
            </button>
        <?php endif; ?>
    </div>

    <?php if ($message): ?>
        <div class="p-3.5 bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm rounded-xl flex items-center gap-2">
            <i data-lucide="check-circle" class="w-4 h-4 shrink-0"></i>
            <span><?= htmlspecialchars($message) ?></span>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="p-3.5 bg-red-50 border border-red-200 text-red-800 text-sm rounded-xl flex items-center gap-2">
            <i data-lucide="alert-circle" class="w-4 h-4 shrink-0"></i>
            <span><?= htmlspecialchars($error) ?></span>
        </div>
    <?php endif; ?>

    <!-- Examinations Table -->
    <div class="bg-white rounded-2xl border border-ledger-100 shadow-card overflow-x-auto">
        <table class="w-full text-sm text-left">
            <thead class="bg-ledger-50/75 border-b border-ledger-100 text-ledger-400 text-xs font-semibold uppercase tracking-wider">
                <tr>
                    <th class="px-6 py-3.5">Course / Exam</th>
                    <th class="px-6 py-3.5">Room</th>
                    <th class="px-6 py-3.5">Date & Time</th>
                    <th class="px-6 py-3.5">Faculty / Dept</th>
                    <th class="px-6 py-3.5">Status</th>
                    <th class="px-6 py-3.5 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-ledger-100">
                <?php if (empty($exams)): ?>
                    <tr>
                        <td colspan="6" class="px-6 py-10 text-center text-ledger-400 text-sm">No examinations found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($exams as $e): ?>
                        <?php
                            $badgeClass = 'bg-ledger-100 text-ledger-600 border-ledger-200';
                            if ($e['status'] === 'ongoing') $badgeClass = 'bg-eligible/10 text-eligible border-eligible/30';
                            elseif ($e['status'] === 'scheduled') $badgeClass = 'bg-pending/10 text-pending border-pending/30';
                        ?>
                        <tr class="hover:bg-ledger-50/40 transition-colors">
                            <td class="px-6 py-4">
                                <p class="font-medium text-ledger-900"><?= htmlspecialchars($e['exam_name']) ?></p>
                                <span class="font-mono text-xs text-seal font-semibold"><?= htmlspecialchars($e['course_code']) ?></span>
                            </td>
                            <td class="px-6 py-4 font-medium text-ledger-700">
                                <?= htmlspecialchars($e['exam_room']) ?>
                            </td>
                            <td class="px-6 py-4 text-xs">
                                <p class="text-ledger-800 font-medium"><?= date('M d, Y', strtotime($e['exam_date'])) ?></p>
                                <p class="text-ledger-400"><?= htmlspecialchars($e['start_time']) ?> – <?= htmlspecialchars($e['end_time']) ?></p>
                            </td>
                            <td class="px-6 py-4 text-xs text-ledger-600">
                                <p class="font-medium text-ledger-800"><?= htmlspecialchars($e['faculty']) ?></p>
                                <p class="text-ledger-400"><?= htmlspecialchars($e['department']) ?></p>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold capitalize border <?= $badgeClass ?>">
                                    <?= htmlspecialchars($e['status']) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="scan.php?exam_id=<?= $e['id'] ?>" class="p-1.5 rounded-lg text-ledger-600 hover:bg-ledger-100 hover:text-ledger-900 transition-colors" title="Start attendance">
                                        <i data-lucide="scan-line" class="w-4 h-4"></i>
                                    </a>
                                    <?php if ($isAdmin): ?>
                                        <button onclick='editExam(<?= json_encode($e) ?>)' class="p-1.5 rounded-lg text-ledger-600 hover:bg-ledger-100 hover:text-ledger-900 transition-colors" title="Edit">
                                            <i data-lucide="pencil" class="w-4 h-4"></i>
                                        </button>
                                        <form method="POST" action="exams.php" onsubmit="return confirm('Remove this examination? This cannot be undone.');" class="inline">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= $e['id'] ?>">
                                            <button type="submit" class="p-1.5 rounded-lg text-ineligible hover:bg-red-50 transition-colors" title="Delete">
                                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal for Create/Edit -->
<div id="examModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-ledger-950/60 backdrop-blur-sm">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg border border-ledger-100 overflow-hidden fade-up">
        <div class="px-6 py-4 border-b border-ledger-100 flex items-center justify-between">
            <h3 id="modalTitle" class="font-display text-lg font-semibold text-ledger-900">New examination</h3>
            <button onclick="closeModal()" class="text-ledger-400 hover:text-ledger-700">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        <form method="POST" action="exams.php" class="p-6 space-y-4">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id" id="examIdField" value="0">

            <div>
                <label class="block text-xs font-semibold text-ledger-600 uppercase tracking-wide mb-1">Exam Name *</label>
                <input type="text" name="exam_name" id="examNameField" required class="w-full px-3 py-2 border border-ledger-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-seal/40 focus:border-seal">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-ledger-600 uppercase tracking-wide mb-1">Course Code *</label>
                    <input type="text" name="course_code" id="courseCodeField" required placeholder="e.g. CS301" class="w-full px-3 py-2 border border-ledger-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-seal/40 focus:border-seal uppercase font-mono">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-ledger-600 uppercase tracking-wide mb-1">Exam Room *</label>
                    <input type="text" name="exam_room" id="examRoomField" required placeholder="e.g. Room A101" class="w-full px-3 py-2 border border-ledger-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-seal/40 focus:border-seal">
                </div>
            </div>

            <div class="grid grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-ledger-600 uppercase tracking-wide mb-1">Date *</label>
                    <input type="date" name="exam_date" id="examDateField" required class="w-full px-3 py-2 border border-ledger-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-seal/40 focus:border-seal">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-ledger-600 uppercase tracking-wide mb-1">Start Time</label>
                    <input type="text" name="start_time" id="startTimeField" placeholder="09:00" class="w-full px-3 py-2 border border-ledger-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-seal/40 focus:border-seal">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-ledger-600 uppercase tracking-wide mb-1">End Time</label>
                    <input type="text" name="end_time" id="endTimeField" placeholder="11:00" class="w-full px-3 py-2 border border-ledger-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-seal/40 focus:border-seal">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-ledger-600 uppercase tracking-wide mb-1">Faculty *</label>
                    <select name="faculty" id="facultyField" onchange="updateDeptOptions()" required class="w-full px-3 py-2 border border-ledger-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-seal/40 focus:border-seal">
                        <option value="">Select Faculty</option>
                        <?php foreach ($faculties as $f): ?>
                            <option value="<?= htmlspecialchars($f['name']) ?>"><?= htmlspecialchars($f['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-ledger-600 uppercase tracking-wide mb-1">Department *</label>
                    <select name="department" id="departmentField" required class="w-full px-3 py-2 border border-ledger-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-seal/40 focus:border-seal">
                        <option value="">Select Department</option>
                        <?php foreach ($departments as $d): ?>
                            <option value="<?= htmlspecialchars($d['name']) ?>" data-faculty="<?= htmlspecialchars($d['faculty_name']) ?>">
                                <?= htmlspecialchars($d['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3 items-center pt-2">
                <div>
                    <label class="block text-xs font-semibold text-ledger-600 uppercase tracking-wide mb-1">Status</label>
                    <select name="status" id="statusField" class="w-full px-3 py-2 border border-ledger-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-seal/40 focus:border-seal">
                        <option value="scheduled">Scheduled</option>
                        <option value="ongoing">Ongoing</option>
                        <option value="completed">Completed</option>
                    </select>
                </div>
                <div class="pt-5">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="require_fee_check" id="requireFeeField" value="1" checked class="w-4 h-4 rounded text-seal focus:ring-seal">
                        <span class="text-xs font-medium text-ledger-700">Require Finance Clearance</span>
                    </label>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-ledger-100">
                <button type="button" onclick="closeModal()" class="px-4 py-2 text-sm text-ledger-600 hover:bg-ledger-50 rounded-xl transition-colors">
                    Cancel
                </button>
                <button type="submit" class="px-5 py-2 bg-ledger-900 hover:bg-ledger-800 text-white text-sm font-medium rounded-xl transition-colors shadow-sm">
                    Save Examination
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal() {
    document.getElementById('modalTitle').textContent = 'New examination';
    document.getElementById('examIdField').value = '0';
    document.getElementById('examNameField').value = '';
    document.getElementById('courseCodeField').value = '';
    document.getElementById('examRoomField').value = '';
    document.getElementById('examDateField').value = '<?= date('Y-m-d') ?>';
    document.getElementById('startTimeField').value = '09:00';
    document.getElementById('endTimeField').value = '11:00';
    document.getElementById('facultyField').value = '';
    document.getElementById('departmentField').value = '';
    document.getElementById('statusField').value = 'scheduled';
    document.getElementById('requireFeeField').checked = true;
    document.getElementById('examModal').classList.remove('hidden');
    lucide.createIcons();
}

function editExam(e) {
    document.getElementById('modalTitle').textContent = 'Edit examination';
    document.getElementById('examIdField').value = e.id;
    document.getElementById('examNameField').value = e.exam_name;
    document.getElementById('courseCodeField').value = e.course_code;
    document.getElementById('examRoomField').value = e.exam_room;
    document.getElementById('examDateField').value = e.exam_date;
    document.getElementById('startTimeField').value = e.start_time;
    document.getElementById('endTimeField').value = e.end_time;
    document.getElementById('facultyField').value = e.faculty;
    updateDeptOptions();
    document.getElementById('departmentField').value = e.department;
    document.getElementById('statusField').value = e.status;
    document.getElementById('requireFeeField').checked = (e.require_fee_check == 1);
    document.getElementById('examModal').classList.remove('hidden');
    lucide.createIcons();
}

function closeModal() {
    document.getElementById('examModal').classList.add('hidden');
}

function updateDeptOptions() {
    const faculty = document.getElementById('facultyField').value;
    const deptSelect = document.getElementById('departmentField');
    const options = deptSelect.querySelectorAll('option');
    
    options.forEach(opt => {
        if (!opt.value) return;
        const optFaculty = opt.getAttribute('data-faculty');
        if (!faculty || optFaculty === faculty) {
            opt.style.display = '';
        } else {
            opt.style.display = 'none';
        }
    });
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
