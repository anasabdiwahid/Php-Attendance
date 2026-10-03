<?php
// users.php
require_once __DIR__ . '/includes/header.php';
requireAdmin();
$pdo = getDB();

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id = intval($_POST['id'] ?? 0);
        $fullName = trim($_POST['full_name'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $invigilatorId = trim($_POST['invigilator_id'] ?? '');
        $role = trim($_POST['role'] ?? 'invigilator');
        $password = trim($_POST['password'] ?? '');

        if (empty($fullName) || empty($username) || empty($email)) {
            $error = 'Full name, username, and email are required.';
        } else {
            if ($id > 0) {
                // Update
                if (!empty($password)) {
                    $hashed = password_hash($password, PASSWORD_BCRYPT);
                    $stmt = $pdo->prepare("UPDATE `users` SET `full_name` = ?, `username` = ?, `email` = ?, `phone` = ?, `invigilator_id` = ?, `role` = ?, `password` = ? WHERE `id` = ?");
                    $stmt->execute([$fullName, $username, $email, $phone, $invigilatorId, $role, $hashed, $id]);
                } else {
                    $stmt = $pdo->prepare("UPDATE `users` SET `full_name` = ?, `username` = ?, `email` = ?, `phone` = ?, `invigilator_id` = ?, `role` = ? WHERE `id` = ?");
                    $stmt->execute([$fullName, $username, $email, $phone, $invigilatorId, $role, $id]);
                }
                $message = 'User updated successfully.';
            } else {
                // Insert
                if (empty($password)) {
                    $error = 'Password is required for new users.';
                } else {
                    $hashed = password_hash($password, PASSWORD_BCRYPT);
                    $stmt = $pdo->prepare("INSERT INTO `users` (`full_name`, `username`, `email`, `phone`, `invigilator_id`, `role`, `password`, `is_active`) VALUES (?, ?, ?, ?, ?, ?, ?, 1)");
                    $stmt->execute([$fullName, $username, $email, $phone, $invigilatorId, $role, $hashed]);
                    $message = 'User account created successfully.';
                }
            }
        }
    } elseif ($action === 'toggle_status') {
        $id = intval($_POST['id'] ?? 0);
        if ($id > 0 && $id !== $currentUser['id']) {
            $pdo->prepare("UPDATE `users` SET `is_active` = 1 - `is_active` WHERE `id` = ?")->execute([$id]);
            $message = 'User status updated.';
        }
    }
}

$users = $pdo->query("SELECT * FROM `users` ORDER BY `role` ASC, `full_name` ASC")->fetchAll();
?>

<div class="space-y-6 fade-up">
    <div class="flex items-center justify-between flex-wrap gap-3">
        <div>
            <h1 class="font-display text-2xl text-ledger-900 font-semibold">Manage Users</h1>
            <p class="text-ledger-400 text-sm mt-1">Admin officers and registered examination invigilators.</p>
        </div>
        <button onclick="openUserModal()" class="flex items-center gap-2 bg-ledger-900 hover:bg-ledger-800 text-white text-sm font-medium px-4 py-2.5 rounded-xl transition-colors shadow-sm">
            <i data-lucide="plus" class="w-4 h-4"></i> New user
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

    <div class="bg-white rounded-2xl border border-ledger-100 shadow-card overflow-x-auto">
        <table class="w-full text-sm text-left">
            <thead class="bg-ledger-50/75 border-b border-ledger-100 text-ledger-400 text-xs font-semibold uppercase tracking-wider">
                <tr>
                    <th class="px-6 py-3.5">User</th>
                    <th class="px-6 py-3.5">Role</th>
                    <th class="px-6 py-3.5">Invigilator ID</th>
                    <th class="px-6 py-3.5">Contact</th>
                    <th class="px-6 py-3.5">Status</th>
                    <th class="px-6 py-3.5 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-ledger-100">
                <?php foreach ($users as $u): ?>
                    <tr class="hover:bg-ledger-50/40 transition-colors">
                        <td class="px-6 py-4">
                            <p class="font-medium text-ledger-900"><?= htmlspecialchars($u['full_name']) ?></p>
                            <p class="text-xs font-mono text-ledger-400">@<?= htmlspecialchars($u['username']) ?></p>
                        </td>
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold capitalize <?= $u['role'] === 'admin' ? 'bg-purple-50 text-purple-700 border border-purple-200' : 'bg-blue-50 text-blue-700 border border-blue-200' ?>">
                                <?= htmlspecialchars($u['role']) ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 font-mono text-xs text-ledger-700">
                            <?= htmlspecialchars($u['invigilator_id'] ?: '—') ?>
                        </td>
                        <td class="px-6 py-4 text-xs">
                            <p class="text-ledger-800"><?= htmlspecialchars($u['email']) ?></p>
                            <p class="text-ledger-400"><?= htmlspecialchars($u['phone'] ?: 'No phone') ?></p>
                        </td>
                        <td class="px-6 py-4">
                            <?php if ($u['is_active']): ?>
                                <span class="inline-flex items-center gap-1 text-xs font-medium text-emerald-700">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Active
                                </span>
                            <?php else: ?>
                                <span class="inline-flex items-center gap-1 text-xs font-medium text-slate-400">
                                    <span class="w-1.5 h-1.5 rounded-full bg-slate-300"></span> Inactive
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <button onclick='editUser(<?= json_encode($u) ?>)' class="p-1.5 rounded-lg text-ledger-600 hover:bg-ledger-100 transition-colors" title="Edit">
                                    <i data-lucide="pencil" class="w-4 h-4"></i>
                                </button>
                                <?php if ($u['id'] !== $currentUser['id']): ?>
                                    <form method="POST" action="users.php" class="inline">
                                        <input type="hidden" name="action" value="toggle_status">
                                        <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                        <button type="submit" class="p-1.5 rounded-lg <?= $u['is_active'] ? 'text-ledger-400 hover:text-red-600' : 'text-emerald-600 hover:text-emerald-800' ?>" title="Toggle Active">
                                            <i data-lucide="<?= $u['is_active'] ? 'user-x' : 'user-check' ?>" class="w-4 h-4"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal -->
<div id="userModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-ledger-950/60 backdrop-blur-sm">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md border border-ledger-100 overflow-hidden fade-up">
        <div class="px-6 py-4 border-b border-ledger-100 flex items-center justify-between">
            <h3 id="userModalTitle" class="font-display text-lg font-semibold text-ledger-900">New User</h3>
            <button onclick="closeUserModal()" class="text-ledger-400 hover:text-ledger-700">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        <form method="POST" action="users.php" class="p-6 space-y-4">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id" id="userIdField" value="0">

            <div>
                <label class="block text-xs font-semibold text-ledger-600 uppercase tracking-wide mb-1">Full Name *</label>
                <input type="text" name="full_name" id="userFullName" required class="w-full px-3 py-2 border border-ledger-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-seal/40 focus:border-seal">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-ledger-600 uppercase tracking-wide mb-1">Username *</label>
                    <input type="text" name="username" id="userUsername" required class="w-full px-3 py-2 border border-ledger-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-seal/40 focus:border-seal">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-ledger-600 uppercase tracking-wide mb-1">Role *</label>
                    <select name="role" id="userRole" required class="w-full px-3 py-2 border border-ledger-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-seal/40 focus:border-seal">
                        <option value="invigilator">Invigilator</option>
                        <option value="admin">System Admin</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-ledger-600 uppercase tracking-wide mb-1">Email *</label>
                    <input type="email" name="email" id="userEmail" required class="w-full px-3 py-2 border border-ledger-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-seal/40 focus:border-seal">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-ledger-600 uppercase tracking-wide mb-1">Phone</label>
                    <input type="text" name="phone" id="userPhone" placeholder="+252..." class="w-full px-3 py-2 border border-ledger-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-seal/40 focus:border-seal">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-ledger-600 uppercase tracking-wide mb-1">Invigilator ID (if invigilator)</label>
                <input type="text" name="invigilator_id" id="userInvigId" placeholder="INV-001" class="w-full px-3 py-2 border border-ledger-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-seal/40 focus:border-seal font-mono">
            </div>

            <div>
                <label class="block text-xs font-semibold text-ledger-600 uppercase tracking-wide mb-1" id="pwLabel">Password</label>
                <input type="password" name="password" id="userPassword" placeholder="••••••••" class="w-full px-3 py-2 border border-ledger-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-seal/40 focus:border-seal">
                <span id="pwHint" class="text-[11px] text-ledger-400 mt-0.5 block hidden">Leave empty to keep existing password</span>
            </div>

            <div class="flex items-center justify-end gap-3 pt-3 border-t border-ledger-100">
                <button type="button" onclick="closeUserModal()" class="px-4 py-2 text-sm text-ledger-600 hover:bg-ledger-50 rounded-xl transition-colors">
                    Cancel
                </button>
                <button type="submit" class="px-5 py-2 bg-ledger-900 hover:bg-ledger-800 text-white text-sm font-medium rounded-xl transition-colors shadow-sm">
                    Save User
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openUserModal() {
    document.getElementById('userModalTitle').textContent = 'New User';
    document.getElementById('userIdField').value = '0';
    document.getElementById('userFullName').value = '';
    document.getElementById('userUsername').value = '';
    document.getElementById('userEmail').value = '';
    document.getElementById('userPhone').value = '';
    document.getElementById('userInvigId').value = '';
    document.getElementById('userRole').value = 'invigilator';
    document.getElementById('userPassword').required = true;
    document.getElementById('pwHint').classList.add('hidden');
    document.getElementById('userModal').classList.remove('hidden');
    lucide.createIcons();
}
function editUser(u) {
    document.getElementById('userModalTitle').textContent = 'Edit User';
    document.getElementById('userIdField').value = u.id;
    document.getElementById('userFullName').value = u.full_name;
    document.getElementById('userUsername').value = u.username;
    document.getElementById('userEmail').value = u.email;
    document.getElementById('userPhone').value = u.phone || '';
    document.getElementById('userInvigId').value = u.invigilator_id || '';
    document.getElementById('userRole').value = u.role;
    document.getElementById('userPassword').required = false;
    document.getElementById('pwHint').classList.remove('hidden');
    document.getElementById('userModal').classList.remove('hidden');
    lucide.createIcons();
}
function closeUserModal() {
    document.getElementById('userModal').classList.add('hidden');
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
