<?php
// faculties.php
require_once __DIR__ . '/includes/header.php';
requireAdmin();
$pdo = getDB();

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'save_faculty') {
        $facultyName = trim($_POST['faculty_name'] ?? '');
        $departmentsStr = trim($_POST['departments'] ?? '');
        $depts = array_filter(array_map('trim', explode(',', $departmentsStr)));

        if (!empty($facultyName)) {
            $stmt = $pdo->prepare("INSERT INTO `faculties` (`name`) VALUES (?) ON DUPLICATE KEY UPDATE `name` = `name`");
            $stmt->execute([$facultyName]);

            foreach ($depts as $d) {
                if (!empty($d)) {
                    $dStmt = $pdo->prepare("INSERT IGNORE INTO `departments` (`faculty_name`, `name`) VALUES (?, ?)");
                    $dStmt->execute([$facultyName, $d]);
                }
            }
            $message = 'Faculty and departments saved successfully.';
        }
    } elseif ($action === 'delete') {
        $name = trim($_POST['name'] ?? '');
        if (!empty($name)) {
            $pdo->prepare("DELETE FROM `departments` WHERE `faculty_name` = ?")->execute([$name]);
            $pdo->prepare("DELETE FROM `faculties` WHERE `name` = ?")->execute([$name]);
            $message = 'Faculty removed successfully.';
        }
    }
}

$faculties = $pdo->query("SELECT * FROM `faculties` ORDER BY `name` ASC")->fetchAll();
$allDepts = $pdo->query("SELECT * FROM `departments` ORDER BY `name` ASC")->fetchAll();
$deptMap = [];
foreach ($allDepts as $d) {
    $deptMap[$d['faculty_name']][] = $d['name'];
}
?>

<div class="space-y-6 fade-up">
    <div class="flex items-center justify-between flex-wrap gap-3">
        <div>
            <h1 class="font-display text-2xl text-ledger-900 font-semibold">Faculties & Departments</h1>
            <p class="text-ledger-400 text-sm mt-1">Academic faculties and their degree departments.</p>
        </div>
        <button onclick="openFacultyModal()" class="flex items-center gap-2 bg-ledger-900 hover:bg-ledger-800 text-white text-sm font-medium px-4 py-2.5 rounded-xl transition-colors shadow-sm">
            <i data-lucide="plus" class="w-4 h-4"></i> New faculty
        </button>
    </div>

    <?php if ($message): ?>
        <div class="p-3.5 bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm rounded-xl flex items-center gap-2">
            <i data-lucide="check-circle" class="w-4 h-4 shrink-0"></i>
            <span><?= htmlspecialchars($message) ?></span>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
        <?php foreach ($faculties as $f): ?>
            <?php $depts = $deptMap[$f['name']] ?? []; ?>
            <div class="bg-white rounded-2xl border border-ledger-100 shadow-card p-6 flex flex-col justify-between">
                <div>
                    <div class="flex items-start justify-between gap-2 mb-3">
                        <div class="flex items-center gap-2.5">
                            <div class="w-9 h-9 rounded-xl bg-ledger-50 text-ledger-700 flex items-center justify-center font-bold">
                                <i data-lucide="landmark" class="w-5 h-5"></i>
                            </div>
                            <h3 class="font-display text-lg font-semibold text-ledger-900"><?= htmlspecialchars($f['name']) ?></h3>
                        </div>
                        <form method="POST" action="faculties.php" onsubmit="return confirm('Remove faculty and its departments?');" class="inline">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="name" value="<?= htmlspecialchars($f['name']) ?>">
                            <button type="submit" class="text-ledger-300 hover:text-red-600 transition-colors p-1" title="Delete">
                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                            </button>
                        </form>
                    </div>

                    <div class="space-y-1.5 pt-2">
                        <span class="text-xs font-semibold uppercase tracking-wider text-ledger-400 block mb-2">Departments (<?= count($depts) ?>)</span>
                        <div class="flex flex-wrap gap-1.5">
                            <?php if (empty($depts)): ?>
                                <span class="text-xs text-ledger-400 italic">No departments listed</span>
                            <?php else: ?>
                                <?php foreach ($depts as $d): ?>
                                    <span class="px-2.5 py-1 rounded-lg text-xs font-medium bg-ledger-50 text-ledger-700 border border-ledger-100">
                                        <?= htmlspecialchars($d) ?>
                                    </span>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Modal -->
<div id="facultyModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-ledger-950/60 backdrop-blur-sm">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md border border-ledger-100 overflow-hidden fade-up">
        <div class="px-6 py-4 border-b border-ledger-100 flex items-center justify-between">
            <h3 class="font-display text-lg font-semibold text-ledger-900">Add Faculty & Departments</h3>
            <button onclick="closeFacultyModal()" class="text-ledger-400 hover:text-ledger-700">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        <form method="POST" action="faculties.php" class="p-6 space-y-4">
            <input type="hidden" name="action" value="save_faculty">
            <div>
                <label class="block text-xs font-semibold text-ledger-600 uppercase tracking-wide mb-1">Faculty Name *</label>
                <input type="text" name="faculty_name" required placeholder="e.g. Health Sciences" class="w-full px-3 py-2 border border-ledger-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-seal/40 focus:border-seal">
            </div>
            <div>
                <label class="block text-xs font-semibold text-ledger-600 uppercase tracking-wide mb-1">Departments (comma separated)</label>
                <input type="text" name="departments" placeholder="Nursing, Pharmacy, Medicine" class="w-full px-3 py-2 border border-ledger-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-seal/40 focus:border-seal">
            </div>
            <div class="flex items-center justify-end gap-3 pt-3 border-t border-ledger-100">
                <button type="button" onclick="closeFacultyModal()" class="px-4 py-2 text-sm text-ledger-600 hover:bg-ledger-50 rounded-xl transition-colors">
                    Cancel
                </button>
                <button type="submit" class="px-5 py-2 bg-ledger-900 hover:bg-ledger-800 text-white text-sm font-medium rounded-xl transition-colors shadow-sm">
                    Save Faculty
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openFacultyModal() {
    document.getElementById('facultyModal').classList.remove('hidden');
    lucide.createIcons();
}
function closeFacultyModal() {
    document.getElementById('facultyModal').classList.add('hidden');
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
