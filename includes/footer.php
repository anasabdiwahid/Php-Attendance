        </main>

        <!-- Mobile Bottom Nav -->
        <nav class="md:hidden sticky bottom-0 bg-ledger-950 border-t border-ledger-800 flex items-center justify-around py-2 z-40">
            <a href="index.php" class="p-2.5 rounded-xl <?= $currentPage === 'index.php' ? 'text-seal-light' : 'text-ledger-300' ?>" title="Dashboard">
                <i data-lucide="layout-dashboard" class="w-5 h-5"></i>
            </a>
            <a href="scan.php" class="p-2.5 rounded-xl <?= $currentPage === 'scan.php' ? 'text-seal-light' : 'text-ledger-300' ?>" title="Scan">
                <i data-lucide="scan-line" class="w-5 h-5"></i>
            </a>
            <a href="exams.php" class="p-2.5 rounded-xl <?= $currentPage === 'exams.php' ? 'text-seal-light' : 'text-ledger-300' ?>" title="Examinations">
                <i data-lucide="calendar-clock" class="w-5 h-5"></i>
            </a>
            <?php if ($isAdmin): ?>
            <a href="reports.php" class="p-2.5 rounded-xl <?= $currentPage === 'reports.php' ? 'text-seal-light' : 'text-ledger-300' ?>" title="Reports">
                <i data-lucide="file-bar-chart-2" class="w-5 h-5"></i>
            </a>
            <?php endif; ?>
            <a href="logout.php" class="p-2.5 rounded-xl text-ledger-300" title="Logout">
                <i data-lucide="log-out" class="w-5 h-5"></i>
            </a>
        </nav>
    </div>

    <!-- Initialize Lucide Icons -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (window.lucide) {
                lucide.createIcons();
            }
        });
    </script>
</body>
</html>
