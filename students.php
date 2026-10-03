<?php
// students.php
require_once __DIR__ . '/includes/header.php';
requireAdmin();
$pdo = getDB();

$message = '';
$error = '';

// Handle Actions (Create / Edit / Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'save') {
        $id = intval($_POST['id'] ?? 0);
        $studentId = strtoupper(trim($_POST['student_id'] ?? ''));
        $fullName = trim($_POST['full_name'] ?? '');
        $faculty = trim($_POST['faculty'] ?? '');
        $department = trim($_POST['department'] ?? '');
        $className = trim($_POST['class_name'] ?? '');
        $feeStatus = trim($_POST['fee_status'] ?? 'Cleared');
        $absenceCount = intval($_POST['absence_count'] ?? 0);
        $email = trim($_POST['email'] ?? '');

        if (empty($studentId) || empty($fullName) || empty($faculty) || empty($department)) {
            $error = 'Please fill in all required fields.';
        } else {
            if ($id > 0) {
                // Update
                $stmt = $pdo->prepare("UPDATE `students` SET `student_id` = ?, `full_name` = ?, `faculty` = ?, `department` = ?, `class_name` = ?, `fee_status` = ?, `absence_count` = ?, `email` = ? WHERE `id` = ?");
                $stmt->execute([$studentId, $fullName, $faculty, $department, $className, $feeStatus, $absenceCount, $email, $id]);
                $message = 'Student record updated successfully.';
            } else {
                // Insert
                $check = $pdo->prepare("SELECT id FROM `students` WHERE `student_id` = ?");
                $check->execute([$studentId]);
                if ($check->fetch()) {
                    $error = "Student ID '{$studentId}' already exists in the system.";
                } else {
                    $stmt = $pdo->prepare("INSERT INTO `students` (`student_id`, `full_name`, `faculty`, `department`, `class_name`, `fee_status`, `absence_count`, `email`) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$studentId, $fullName, $faculty, $department, $className, $feeStatus, $absenceCount, $email]);
                    $message = 'Student record created successfully.';
                }
            }
        }
    } elseif ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        if ($id > 0) {
            $pdo->prepare("DELETE FROM `attendance_records` WHERE `student_id` = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM `students` WHERE `id` = ?")->execute([$id]);
            $message = 'Student removed successfully.';
        }
    }
}

// Fetch students
$students = $pdo->query("SELECT * FROM `students` ORDER BY `student_id` ASC")->fetchAll();
$faculties = $pdo->query("SELECT * FROM `faculties` ORDER BY `name` ASC")->fetchAll();
$departments = $pdo->query("SELECT * FROM `departments` ORDER BY `name` ASC")->fetchAll();
$classes = $pdo->query("SELECT * FROM `classes` ORDER BY `name` ASC")->fetchAll();
?>

<div class="space-y-6 fade-up">
    <div class="flex items-center justify-between flex-wrap gap-3">
        <div>
            <h1 class="font-display text-2xl text-ledger-900 font-semibold">Students</h1>
            <p class="text-ledger-400 text-sm mt-1">University student directory and eligibility status.</p>
        </div>
        <button onclick="openCreateModal()" class="flex items-center gap-2 bg-ledger-900 hover:bg-ledger-800 text-white text-sm font-medium px-4 py-2.5 rounded-xl transition-colors shadow-sm">
            <i data-lucide="plus" class="w-4 h-4"></i> New student
        </button>
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

    <!-- Students Table -->
    <div class="bg-white rounded-2xl border border-ledger-100 shadow-card overflow-x-auto">
        <table class="w-full text-sm text-left">
            <thead class="bg-ledger-50/75 border-b border-ledger-100 text-ledger-400 text-xs font-semibold uppercase tracking-wider">
                <tr>
                    <th class="px-6 py-3.5">ID / Name</th>
                    <th class="px-6 py-3.5">Faculty / Dept</th>
                    <th class="px-6 py-3.5">Class</th>
                    <th class="px-6 py-3.5">Fee Status</th>
                    <th class="px-6 py-3.5">Absences</th>
                    <th class="px-6 py-3.5 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-ledger-100">
                <?php if (empty($students)): ?>
                    <tr>
                        <td colspan="6" class="px-6 py-10 text-center text-ledger-400 text-sm">No students registered yet.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($students as $s): ?>
                        <tr class="hover:bg-ledger-50/40 transition-colors">
                            <td class="px-6 py-4">
                                <p class="font-medium text-ledger-900"><?= htmlspecialchars($s['full_name']) ?></p>
                                <span class="font-mono text-xs text-seal font-semibold"><?= htmlspecialchars($s['student_id']) ?></span>
                            </td>
                            <td class="px-6 py-4 text-xs text-ledger-600">
                                <p class="font-medium text-ledger-800"><?= htmlspecialchars($s['faculty']) ?></p>
                                <p class="text-ledger-400"><?= htmlspecialchars($s['department']) ?></p>
                            </td>
                            <td class="px-6 py-4 text-xs font-medium text-ledger-700">
                                <?= htmlspecialchars($s['class_name'] ?: '—') ?>
                            </td>
                            <td class="px-6 py-4">
                                <?php if ($s['fee_status'] === 'Cleared'): ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-eligible/10 text-eligible border border-eligible/30">
                                        Cleared
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-ineligible/10 text-ineligible border border-ineligible/30">
                                        Not Cleared
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-xs font-medium <?= $s['absence_count'] > 3 ? 'text-ineligible font-bold' : 'text-ledger-700' ?>">
                                    <?= $s['absence_count'] ?> / 3 max
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <button onclick='showQr(<?= json_encode($s) ?>)' class="p-1.5 rounded-lg text-seal hover:bg-amber-50 transition-colors" title="View QR Code">
                                        <i data-lucide="qr-code" class="w-4 h-4"></i>
                                    </button>
                                    <button onclick='editStudent(<?= json_encode($s) ?>)' class="p-1.5 rounded-lg text-ledger-600 hover:bg-ledger-100 transition-colors" title="Edit">
                                        <i data-lucide="pencil" class="w-4 h-4"></i>
                                    </button>
                                    <form method="POST" action="students.php" onsubmit="return confirm('Remove student record?');" class="inline">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $s['id'] ?>">
                                        <button type="submit" class="p-1.5 rounded-lg text-ineligible hover:bg-red-50 transition-colors" title="Delete">
                                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal for Create / Edit Student -->
<div id="studentModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-ledger-950/60 backdrop-blur-sm">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg border border-ledger-100 overflow-hidden fade-up">
        <div class="px-6 py-4 border-b border-ledger-100 flex items-center justify-between">
            <h3 id="modalTitle" class="font-display text-lg font-semibold text-ledger-900">New student</h3>
            <button onclick="closeStudentModal()" class="text-ledger-400 hover:text-ledger-700">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        <form method="POST" action="students.php" class="p-6 space-y-4">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id" id="studentDbId" value="0">

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-ledger-600 uppercase tracking-wide mb-1">Student ID *</label>
                    <input type="text" name="student_id" id="studentIdInput" required placeholder="HU2001" class="w-full px-3 py-2 border border-ledger-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-seal/40 focus:border-seal uppercase font-mono">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-ledger-600 uppercase tracking-wide mb-1">Full Name *</label>
                    <input type="text" name="full_name" id="fullNameInput" required placeholder="e.g. Hodan Ibrahim" class="w-full px-3 py-2 border border-ledger-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-seal/40 focus:border-seal">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-ledger-600 uppercase tracking-wide mb-1">Faculty *</label>
                    <select name="faculty" id="facultySelect" onchange="updateDeptAndClass()" required class="w-full px-3 py-2 border border-ledger-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-seal/40 focus:border-seal">
                        <option value="">Select Faculty</option>
                        <?php foreach ($faculties as $f): ?>
                            <option value="<?= htmlspecialchars($f['name']) ?>"><?= htmlspecialchars($f['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-ledger-600 uppercase tracking-wide mb-1">Department *</label>
                    <select name="department" id="deptSelect" required class="w-full px-3 py-2 border border-ledger-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-seal/40 focus:border-seal">
                        <option value="">Select Department</option>
                        <?php foreach ($departments as $d): ?>
                            <option value="<?= htmlspecialchars($d['name']) ?>" data-faculty="<?= htmlspecialchars($d['faculty_name']) ?>">
                                <?= htmlspecialchars($d['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-ledger-600 uppercase tracking-wide mb-1">Class</label>
                    <input type="text" name="class_name" id="classNameInput" placeholder="SE Year 2" class="w-full px-3 py-2 border border-ledger-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-seal/40 focus:border-seal">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-ledger-600 uppercase tracking-wide mb-1">Fee Status</label>
                    <select name="fee_status" id="feeStatusSelect" class="w-full px-3 py-2 border border-ledger-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-seal/40 focus:border-seal">
                        <option value="Cleared">Cleared</option>
                        <option value="Not Cleared">Not Cleared</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-ledger-600 uppercase tracking-wide mb-1">Absence Count</label>
                    <input type="number" name="absence_count" id="absenceInput" min="0" max="20" value="0" class="w-full px-3 py-2 border border-ledger-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-seal/40 focus:border-seal">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-ledger-600 uppercase tracking-wide mb-1">Email</label>
                <input type="email" name="email" id="emailInput" placeholder="student@university.edu" class="w-full px-3 py-2 border border-ledger-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-seal/40 focus:border-seal">
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-ledger-100">
                <button type="button" onclick="closeStudentModal()" class="px-4 py-2 text-sm text-ledger-600 hover:bg-ledger-50 rounded-xl transition-colors">
                    Cancel
                </button>
                <button type="submit" class="px-5 py-2 bg-ledger-900 hover:bg-ledger-800 text-white text-sm font-medium rounded-xl transition-colors shadow-sm">
                    Save Student
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal for Student QR Code -->
<div id="qrModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-ledger-950/60 backdrop-blur-sm">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm border border-ledger-100 overflow-hidden fade-up p-6 text-center">
        <div class="flex items-center justify-between pb-3 border-b border-ledger-100 mb-4">
            <h3 class="font-display text-base font-semibold text-ledger-900">Student Examination QR</h3>
            <button onclick="closeQrModal()" class="text-ledger-400 hover:text-ledger-700">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <!-- Canvas for QR Code -->
        <div class="flex justify-center p-3 bg-ledger-50 rounded-2xl border border-ledger-200/60 my-2">
            <canvas id="qrCanvas" class="w-48 h-48"></canvas>
        </div>

        <h4 id="qrStudentName" class="font-display text-lg font-semibold text-ledger-900 mt-3">Student Name</h4>
        <p id="qrStudentCode" class="font-mono text-sm font-bold text-seal">HU2001</p>
        <p id="qrStudentFaculty" class="text-xs text-ledger-400 mt-1">Computer Science · Software Engineering</p>

        <div class="flex items-center justify-center gap-2 pt-5 border-t border-ledger-100 mt-4">
            <button type="button" onclick="downloadQr()" class="flex-1 flex items-center justify-center gap-1.5 py-2 px-3 bg-ledger-100 hover:bg-ledger-200 text-ledger-800 rounded-xl text-xs font-semibold transition-colors">
                <i data-lucide="download" class="w-3.5 h-3.5"></i> Download
            </button>
            <button type="button" onclick="window.print()" class="flex-1 flex items-center justify-center gap-1.5 py-2 px-3 bg-ledger-900 hover:bg-ledger-800 text-white rounded-xl text-xs font-semibold transition-colors shadow-sm">
                <i data-lucide="printer" class="w-3.5 h-3.5"></i> Print
            </button>
        </div>
    </div>
</div>

<script>
function openCreateModal() {
    document.getElementById('modalTitle').textContent = 'New student';
    document.getElementById('studentDbId').value = '0';
    document.getElementById('studentIdInput').value = '';
    document.getElementById('fullNameInput').value = '';
    document.getElementById('facultySelect').value = '';
    document.getElementById('deptSelect').value = '';
    document.getElementById('classNameInput').value = '';
    document.getElementById('feeStatusSelect').value = 'Cleared';
    document.getElementById('absenceInput').value = '0';
    document.getElementById('emailInput').value = '';
    document.getElementById('studentModal').classList.remove('hidden');
    lucide.createIcons();
}

function editStudent(s) {
    document.getElementById('modalTitle').textContent = 'Edit student';
    document.getElementById('studentDbId').value = s.id;
    document.getElementById('studentIdInput').value = s.student_id;
    document.getElementById('fullNameInput').value = s.full_name;
    document.getElementById('facultySelect').value = s.faculty;
    updateDeptAndClass();
    document.getElementById('deptSelect').value = s.department;
    document.getElementById('classNameInput').value = s.class_name || '';
    document.getElementById('feeStatusSelect').value = s.fee_status;
    document.getElementById('absenceInput').value = s.absence_count;
    document.getElementById('emailInput').value = s.email || '';
    document.getElementById('studentModal').classList.remove('hidden');
    lucide.createIcons();
}

function closeStudentModal() {
    document.getElementById('studentModal').classList.add('hidden');
}

function showQr(s) {
    document.getElementById('qrStudentName').textContent = s.full_name;
    document.getElementById('qrStudentCode').textContent = s.student_id;
    document.getElementById('qrStudentFaculty').textContent = `${s.faculty} · ${s.department}`;

    const canvas = document.getElementById('qrCanvas');
    QRCode.toCanvas(canvas, s.student_id, {
        width: 200,
        margin: 2,
        color: {
            dark: '#0B1220',
            light: '#FFFFFF'
        }
    }, function (error) {
        if (error) console.error(error);
    });

    document.getElementById('qrModal').classList.remove('hidden');
    lucide.createIcons();
}

function closeQrModal() {
    document.getElementById('qrModal').classList.add('hidden');
}

function downloadQr() {
    const canvas = document.getElementById('qrCanvas');
    const studentId = document.getElementById('qrStudentCode').textContent;
    const link = document.createElement('a');
    link.download = `QR_${studentId}.png`;
    link.href = canvas.toDataURL();
    link.click();
}

function updateDeptAndClass() {
    const faculty = document.getElementById('facultySelect').value;
    const deptSelect = document.getElementById('deptSelect');
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
