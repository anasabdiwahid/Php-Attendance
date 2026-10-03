<?php
// login.php
require_once __DIR__ . '/config/db.php';

if (isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT * FROM `users` WHERE `username` = ? AND `is_active` = 1 LIMIT 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user'] = [
                'id' => $user['id'],
                'full_name' => $user['full_name'],
                'username' => $user['username'],
                'email' => $user['email'],
                'role' => $user['role'],
                'invigilator_id' => $user['invigilator_id'],
            ];
            header('Location: index.php');
            exit;
        } else {
            $error = 'Invalid username or password. Check your credentials and try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign in - SEAMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        ledger: {
                            950: '#0B1220',
                            900: '#111C33',
                            800: '#16223F',
                            700: '#1E2C4D',
                            600: '#2B3E68',
                            400: '#6B7FA8',
                            200: '#C7D0E4',
                            100: '#EDF1F9',
                            50: '#F7F8FB',
                        },
                        seal: {
                            DEFAULT: '#B8862E',
                            light: '#D6A64B',
                            dark: '#8C6420',
                        },
                        eligible: '#1E7A4C',
                        ineligible: '#B33A3A',
                        pending: '#C08A1E',
                    },
                    fontFamily: {
                        display: ['"Newsreader"', 'ui-serif', 'Georgia', 'serif'],
                        body: ['"Inter"', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                        mono: ['"JetBrains Mono"', 'ui-monospace', 'SFMono-Regular', 'monospace'],
                    },
                    boxShadow: {
                        card: '0 1px 2px rgba(11,18,32,0.06), 0 8px 24px -12px rgba(11,18,32,0.18)',
                    },
                }
            }
        }
    </script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500;600&family=Newsreader:ital,opsz,wght@0,6..72,400;0,6..72,600;1,6..72,400&display=swap');
        body { font-family: 'Inter', system-ui, sans-serif; }
        .font-display { font-family: 'Newsreader', Georgia, serif; }
        .font-mono { font-family: 'JetBrains Mono', monospace; }
        .fade-up { animation: fadeUp 0.3s ease-out forwards; }
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(6px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body class="min-h-screen grid md:grid-cols-2 bg-ledger-50">

    <!-- Left: brand / context panel -->
    <div class="hidden md:flex flex-col justify-between bg-ledger-950 text-white p-12 relative overflow-hidden">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-seal flex items-center justify-center text-ledger-950 font-bold shadow-md">
                <i data-lucide="shield-check" class="w-6 h-6"></i>
            </div>
            <div>
                <p class="font-display text-lg font-bold">SEAMS</p>
                <p class="text-ledger-400 text-xs">Smart Examination Attendance Management System</p>
            </div>
        </div>

        <div class="max-w-md my-auto py-12">
            <h2 class="font-display text-4xl leading-tight mb-4 font-normal">
                Every seat verified.<br />Every record trusted.
            </h2>
            <p class="text-ledger-300 text-sm leading-relaxed">
                Scan an ID, confirm eligibility instantly, and keep working even
                when the room loses connection — every record syncs the moment
                it's back.
            </p>
        </div>

        <p class="text-ledger-500 text-xs">Faculty of Computer Science / Information Technology</p>
    </div>

    <!-- Right: login form -->
    <div class="flex items-center justify-center p-6 sm:p-12">
        <div class="w-full max-w-sm fade-up">
            <div class="md:hidden flex items-center gap-2.5 mb-8">
                <div class="w-9 h-9 rounded-lg bg-seal flex items-center justify-center text-ledger-950 font-bold">
                    <i data-lucide="shield-check" class="w-5 h-5"></i>
                </div>
                <p class="font-display text-lg text-ledger-900 font-semibold">SEAMS</p>
            </div>

            <h1 class="font-display text-2xl text-ledger-900 mb-1 font-semibold">Sign in</h1>
            <p class="text-ledger-400 text-sm mb-8">Enter your Admin or Invigilator credentials.</p>

            <?php if ($error): ?>
                <div class="mb-5 flex items-start gap-2 bg-ineligible/10 border border-ineligible/30 text-ineligible text-sm rounded-xl px-3.5 py-3">
                    <i data-lucide="alert-circle" class="w-4 h-4 mt-0.5 shrink-0"></i>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" action="login.php" class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wide text-ledger-400 mb-1.5">
                        Username
                    </label>
                    <div class="relative">
                        <i data-lucide="user" class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-ledger-400"></i>
                        <input
                            type="text"
                            name="username"
                            required
                            autofocus
                            value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                            placeholder="e.g. admin1 or invig1"
                            class="w-full pl-10 pr-3.5 py-2.5 rounded-xl border border-ledger-200 bg-white text-sm focus:outline-none focus:ring-2 focus:ring-seal/40 focus:border-seal transition-shadow"
                        />
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wide text-ledger-400 mb-1.5">
                        Password
                    </label>
                    <div class="relative">
                        <i data-lucide="lock" class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-ledger-400"></i>
                        <input
                            type="password"
                            id="passwordInput"
                            name="password"
                            required
                            placeholder="••••••••"
                            class="w-full pl-10 pr-10 py-2.5 rounded-xl border border-ledger-200 bg-white text-sm focus:outline-none focus:ring-2 focus:ring-seal/40 focus:border-seal transition-shadow"
                        />
                        <button
                            type="button"
                            onclick="togglePassword()"
                            title="Toggle password"
                            class="absolute right-3.5 top-1/2 -translate-y-1/2 text-ledger-400 hover:text-ledger-700 focus:outline-none"
                        >
                            <i id="eyeIcon" data-lucide="eye" class="w-4 h-4"></i>
                        </button>
                    </div>
                </div>

                <button
                    type="submit"
                    class="w-full flex items-center justify-center gap-2 bg-ledger-900 hover:bg-ledger-800 text-white font-medium text-sm py-2.5 rounded-xl transition-colors shadow-sm"
                >
                    Sign in
                </button>
            </form>

            <div class="mt-8 border-t border-ledger-100 pt-5">
                <p class="text-xs text-ledger-400 mb-2 font-semibold uppercase tracking-wide">Demo accounts</p>
                <ul class="text-xs text-ledger-500 space-y-1 font-mono">
                    <li><span class="text-ledger-700 font-semibold">Admin:</span> admin1 / Admin@123</li>
                    <li><span class="text-ledger-700 font-semibold">Invigilator:</span> invig1 / Invigilator@123</li>
                </ul>
            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            const input = document.getElementById('passwordInput');
            const icon = document.getElementById('eyeIcon');
            if (input.type === 'password') {
                input.type = 'text';
                icon.setAttribute('data-lucide', 'eye-off');
            } else {
                input.type = 'password';
                icon.setAttribute('data-lucide', 'eye');
            }
            lucide.createIcons();
        }
        document.addEventListener('DOMContentLoaded', () => {
            lucide.createIcons();
        });
    </script>
</body>
</html>
