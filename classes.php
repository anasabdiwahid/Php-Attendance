<?php
// classes.php
require_once __DIR__ . '/includes/header.php';
requireAdmin();
$pdo = getDB();

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $id = intval($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $faculty = trim($_POST['faculty'] ?? '');
        $department = trim($_POST['department'] ?? '');

        if (!empty($name) && !empty($faculty) && !empty($department)) {
            if ($id > 0) {
                $stmt = $pdo->prepare("UPDATE `classes` SET `name` = ?, `faculty` = ?, `department` = ? WHERE `id` = ?");
                $stmt->execute([$name, $faculty, $department, $id]);
                $message = 'Class updated successfully.';
            } else {
                $stmt = $pdo->prepare("INSERT INTO `classes` (`name`, `faculty`, `department`) VALUES (?, ?, ?)");
                $stmt->execute([$name, $faculty, $department]);
                $message = 'Class created successfully.';
            }
        } else {
            $error = 'All fields are required.';
        }
    } elseif ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        if ($id > 0) {
            $pdo->prepare("DELETE FROM `classes` WHERE `id` = ?")->execute([$id]);
            $message = 'Class removed.';
        }
    }
}

$classes = $pdo->query("SELECT * FROM `classes` ORDER BY `name` ASC")->fetchAll();
$faculties = $pdo->query("SELECT * FROM `faculties` ORDER BY `name` ASC")->fetchAll();
$departments = $pdo->query("SELECT * FROM `departments` ORDER BY `name` ASC")->fetchAll();
?>

<div class="space-y-6 fade-up">
    <div class="flex items-center justify-between flex-wrap gap-3">
        <div>
            <h1 class="font-display text-2xl text-ledger-900 font-semibold">Classes & Cohorts</h1>
            <p class="text-ledger-400 text-sm mt-1">Manage academic batches and class groupings.</p>
        </div>
        <button onclick="openClassModal()" class="flex items-center gap-2 bg-ledger-900 hover:bg-ledger-800 text-white text-sm font-medium px-4 py-2.5 rounded-xl transition-colors shadow-sm">
            <i data-lucide="plus" class="w-4 h-4"></i> New class
        </button>
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
                    <th class="px-6 py-3.5">Class Name</th>
                    <th class="px-6 py-3.5">Faculty</th>
                    <th class="px-6 py-3.5">Department</th>
                    <th class="px-6 py-3.5 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-ledger-100">
                <?php if (empty($classes)): ?>
                    <tr>
                        <td colspan="4" class="px-6 py-10 text-center text-ledger-400 text-sm">No classes registered yet.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($classes as $c): ?>
                        <tr class="hover:bg-ledger-50/40 transition-colors">
                            <td class="px-6 py-4 font-medium text-ledger-900 flex items-center gap-2">
                                <i data-lucide="school" class="w-4 h-4 text-seal"></i>
                                <?= htmlspecialchars($c['name']) ?>
                            </td>
                            <td class="px-6 py-4 text-xs font-medium text-ledger-700">
                                <?= htmlspecialchars($c['faculty']) ?>
                            </td>
                            <td class="px-6 py-4 text-xs text-ledger-600">
                                <?= htmlspecialchars($c['department']) ?>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <form method="POST" action="classes.php" onsubmit="return confirm('Remove class?');" class="inline">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                    <button type="submit" class="p-1.5 rounded-lg text-ineligible hover:bg-red-50 transition-colors" title="Delete">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal -->
<div id="classModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-ledger-950/60 backdrop-blur-sm">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md border border-ledger-100 overflow-hidden fade-up">
        <div class="px-6 py-4 border-b border-ledger-100 flex items-center justify-between">
            <h3 class="font-display text-lg font-semibold text-ledger-900">New Class</h3>
            <button onclick="closeClassModal()" class="text-ledger-400 hover:text-ledger-700">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        <form method="POST" action="classes.php" class="p-6 space-y-4">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id" value="0">
            <div>
                <label class="block text-xs font-semibold text-ledger-600 uppercase tracking-wide mb-1">Class Name *</label>
                <input type="text" name="name" required placeholder="e.g. SE Year 3" class="w-full px-3 py-2 border border-ledger-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-seal/40 focus:border-seal">
            </div>
            <div>
                <label class="block text-xs font-semibold text-ledger-600 uppercase tracking-wide mb-1">Faculty *</label>
                <select name="faculty" id="modalFaculty" required onchange="filterDepts()" class="w-full px-3 py-2 border border-ledger-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-seal/40 focus:border-seal">
                    <option value="">Select Faculty</option>
                    <?php foreach ($faculties as $f): ?>
                        <option value="<?= htmlspecialchars($f['name']) ?>"><?= htmlspecialchars($f['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-ledger-600 uppercase tracking-wide mb-1">Department *</label>
                <select name="department" id="modalDept" required class="w-full px-3 py-2 border border-ledger-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-seal/40 focus:border-seal">
                    <option value="">Select Department</option>
                    <?php foreach ($departments as $d): ?>
                        <option value="<?= htmlspecialchars($d['name']) ?>" data-faculty="<?= htmlspecialchars($d['faculty_name']) ?>">
                            <?= htmlspecialchars($d['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex items-center justify-end gap-3 pt-3 border-t border-ledger-100">
                <button type="button" onclick="closeClassModal()" class="px-4 py-2 text-sm text-ledger-600 hover:bg-ledger-50 rounded-xl transition-colors">
                    Cancel
                </button>
                <button type="submit" class="px-5 py-2 bg-ledger-900 hover:bg-ledger-800 text-white text-sm font-medium rounded-xl transition-colors shadow-sm">
                    Save Class
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openClassModal() {
    document.getElementById('classModal').classList.remove('hidden');
    lucide.createIcons();
}
function closeClassModal() {
    document.getElementById('classModal').classList.add('hidden');
}
function filterDepts() {
    const f = document.getElementById('modalFaculty').value;
    const opts = document.getElementById('modalDept').querySelectorAll('option');
    opts.forEach(o => {
        if (!o.value) return;
        o.style.display = (!f || o.getAttribute('data-faculty') === f) ? '' : 'none';
    });
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
