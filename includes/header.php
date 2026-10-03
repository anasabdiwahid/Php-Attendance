<?php
// includes/header.php
require_once __DIR__ . '/../config/db.php';
$currentUser = requireAuth();
$isAdmin = ($currentUser['role'] ?? '') === 'admin';
$currentPage = basename($_SERVER['PHP_SELF']);

function navItem($url, $label, $iconName, $currentPage) {
    $isActive = ($currentPage === $url || ($url === 'index.php' && ($currentPage === '' || $currentPage === 'index.php')));
    $activeClass = $isActive 
        ? 'bg-ledger-800 text-white' 
        : 'text-ledger-200 hover:bg-ledger-800/60 hover:text-white';
    
    echo '<a href="' . htmlspecialchars($url) . '" class="flex items-center gap-3 px-3.5 py-2 rounded-xl text-sm font-medium transition-colors ' . $activeClass . '">';
    echo '<i data-lucide="' . htmlspecialchars($iconName) . '" class="w-[17px] h-[17px] stroke-[1.8] shrink-0"></i>';
    echo '<span>' . htmlspecialchars($label) . '</span>';
    echo '</a>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SEAMS - Smart Examination Attendance Management System</title>
    <!-- Tailwind CSS with custom SEAMS configuration -->
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
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500;600&family=Newsreader:ital,opsz,wght@0,6..72,400;0,6..72,500;0,6..72,600;1,6..72,400&display=swap" rel="stylesheet">
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <!-- QR Tools -->
    <script src="https://unpkg.com/html5-qrcode"></script>
    <script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js"></script>
    <style>
        body { font-family: 'Inter', ui-sans-serif, system-ui, sans-serif; }
        .font-display { font-family: 'Newsreader', ui-serif, Georgia, serif; }
        .font-mono { font-family: 'JetBrains Mono', ui-monospace, monospace; }
        
        /* Sleek custom scrollbars */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #CBD5E1; border-radius: 9999px; }
        ::-webkit-scrollbar-thumb:hover { background: #94A3B8; }
        
        aside::-webkit-scrollbar { width: 4px; }
        aside::-webkit-scrollbar-thumb { background: #16223F; border-radius: 9999px; }
        
        .fade-up { animation: fadeUp 0.25s ease-out forwards; }
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(4px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body class="min-h-screen flex bg-ledger-50 text-ledger-900 antialiased selection:bg-seal/20 selection:text-seal-dark">

    <!-- Sidebar (Desktop) -->
    <aside class="hidden md:flex flex-col w-64 bg-ledger-950 shrink-0 px-4 py-6 border-r border-ledger-900 min-h-screen">
        <!-- Logo & Header -->
        <div class="flex items-center gap-2.5 px-2 mb-8">
            <div class="w-9 h-9 rounded-lg bg-seal flex items-center justify-center text-ledger-950">
                <i data-lucide="shield-check" class="w-5 h-5 stroke-[2]"></i>
            </div>
            <div class="leading-tight">
                <p class="font-display text-white text-[15px] font-medium tracking-tight">SEAMS</p>
                <p class="text-ledger-400 text-[11px]">Exam Attendance</p>
            </div>
        </div>

        <!-- Navigation Links -->
        <nav class="flex flex-col gap-1 flex-1">
            <?php navItem('index.php', 'Dashboard', 'layout-dashboard', $currentPage); ?>
            <?php navItem('scan.php', $isAdmin ? 'Scan Attendance' : 'Scan Student QR', 'scan-line', $currentPage); ?>
            <?php navItem('exams.php', 'Examinations', 'calendar-clock', $currentPage); ?>
            <?php if ($isAdmin): ?>
                <?php navItem('students.php', 'Students', 'graduation-cap', $currentPage); ?>
                <?php navItem('faculties.php', 'Faculties', 'landmark', $currentPage); ?>
                <?php navItem('classes.php', 'Classes', 'school', $currentPage); ?>
                <?php navItem('assignments.php', 'Assignments', 'user-cog', $currentPage); ?>
                <?php navItem('users.php', 'Manage Users', 'users', $currentPage); ?>
                <?php navItem('reports.php', 'Reports', 'file-bar-chart-2', $currentPage); ?>
            <?php endif; ?>
        </nav>

        <!-- User Profile & Sign Out Footer -->
        <div class="mt-auto pt-6 border-t border-ledger-800/80">
            <div class="px-2 mb-3">
                <p class="text-white text-sm font-medium truncate"><?= htmlspecialchars($currentUser['full_name'] ?? 'Admin') ?></p>
                <p class="text-ledger-400 text-xs capitalize"><?= htmlspecialchars($currentUser['role'] ?? 'admin') ?></p>
            </div>
            <a href="logout.php" class="w-full flex items-center gap-2 px-3.5 py-2 rounded-xl text-sm text-ledger-200 hover:bg-ledger-800/60 hover:text-white transition-colors">
                <i data-lucide="log-out" class="w-4 h-4"></i> Sign out
            </a>
        </div>
    </aside>

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col min-w-0">
        <!-- Top App Bar -->
        <header class="h-16 border-b border-ledger-100 bg-white/80 backdrop-blur px-4 md:px-8 flex items-center justify-between sticky top-0 z-30">
            <div class="md:hidden font-display text-lg text-ledger-900 font-semibold">SEAMS</div>
            <div class="hidden md:block"></div>
            <div class="flex items-center gap-3">
                <!-- Connection Badge -->
                <div class="flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium border bg-emerald-500/10 text-eligible border-emerald-500/30" title="Connected to server">
                    <i data-lucide="wifi" class="w-3.5 h-3.5"></i>
                    <span>Online</span>
                </div>
                <!-- User Avatar Initial -->
                <div class="w-8 h-8 rounded-full bg-ledger-800 text-white flex items-center justify-center text-xs font-semibold uppercase shadow-sm">
                    <?= htmlspecialchars(mb_substr($currentUser['full_name'] ?? 'U', 0, 1)) ?>
                </div>
            </div>
        </header>

        <!-- Main Page Container -->
        <main class="flex-1 p-4 md:p-8 max-w-7xl w-full mx-auto">
