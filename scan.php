<?php
// scan.php
require_once __DIR__ . '/includes/header.php';
$pdo = getDB();

$selectedExamId = intval($_GET['exam_id'] ?? 0);

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

// Default to first exam if none selected
if ($selectedExamId === 0 && !empty($exams)) {
    $selectedExamId = $exams[0]['id'];
}

$selectedExam = null;
foreach ($exams as $e) {
    if ($e['id'] === $selectedExamId) {
        $selectedExam = $e;
        break;
    }
}
?>

<div class="space-y-6 fade-up">
    <!-- Header & Exam Selector -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="font-display text-2xl text-ledger-900 font-semibold">
                <?= $isAdmin ? 'Scan Attendance' : 'Scan Student QR' ?>
            </h1>
            <p class="text-ledger-400 text-sm mt-0.5">Live examination attendance verification and eligibility check.</p>
        </div>

        <!-- Exam Dropdown -->
        <div class="w-full md:w-80">
            <label class="block text-xs font-semibold uppercase tracking-wider text-ledger-400 mb-1">Select Examination</label>
            <select id="examSelect" onchange="changeExam(this.value)" class="w-full px-3.5 py-2.5 bg-white border border-ledger-200 rounded-xl text-sm font-medium text-ledger-900 focus:outline-none focus:ring-2 focus:ring-seal/40 focus:border-seal transition-shadow">
                <?php if (empty($exams)): ?>
                    <option value="">No examinations available</option>
                <?php else: ?>
                    <?php foreach ($exams as $e): ?>
                        <option value="<?= $e['id'] ?>" <?= $e['id'] === $selectedExamId ? 'selected' : '' ?>>
                            <?= htmlspecialchars($e['course_code']) ?> - <?= htmlspecialchars($e['exam_name']) ?> (<?= htmlspecialchars($e['exam_room']) ?>)
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
        </div>
    </div>

    <?php if ($selectedExam): ?>
        <!-- Selected Exam Info Banner -->
        <div class="bg-white rounded-2xl p-4 md:p-5 border border-ledger-100 shadow-card flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-ledger-900 text-seal flex items-center justify-center font-bold">
                    <i data-lucide="calendar-check" class="w-5 h-5"></i>
                </div>
                <div>
                    <h2 class="font-display text-base md:text-lg text-ledger-900 font-semibold"><?= htmlspecialchars($selectedExam['exam_name']) ?></h2>
                    <p class="text-ledger-400 text-xs mt-0.5">
                        <span class="font-medium text-ledger-700"><?= htmlspecialchars($selectedExam['course_code']) ?></span> · Room <?= htmlspecialchars($selectedExam['exam_room']) ?> · <?= htmlspecialchars($selectedExam['faculty']) ?> (<?= htmlspecialchars($selectedExam['department']) ?>)
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-xs text-ledger-500 font-medium px-2.5 py-1 bg-ledger-50 border border-ledger-200/60 rounded-lg">
                    <?= htmlspecialchars($selectedExam['start_time']) ?> – <?= htmlspecialchars($selectedExam['end_time']) ?>
                </span>
                <span class="text-xs font-semibold px-2.5 py-1 rounded-lg border <?= $selectedExam['status'] === 'ongoing' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-amber-50 text-amber-700 border-amber-200' ?>">
                    <?= ucfirst($selectedExam['status']) ?>
                </span>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <!-- Left Column: Verification Scanner / Input -->
            <div class="lg:col-span-7 space-y-6">
                <!-- Mode Toggle & Input Box -->
                <div class="bg-white rounded-2xl border border-ledger-100 shadow-card p-5">
                    <!-- Mode Tabs -->
                    <div class="flex items-center gap-2 p-1 bg-ledger-50 rounded-xl border border-ledger-100 mb-5">
                        <button type="button" id="tabQr" onclick="switchMode('qr')" class="flex-1 flex items-center justify-center gap-2 py-2 rounded-lg text-xs font-semibold transition-all bg-white text-ledger-900 shadow-sm">
                            <i data-lucide="camera" class="w-4 h-4"></i> Scan QR Camera
                        </button>
                        <button type="button" id="tabManual" onclick="switchMode('manual')" class="flex-1 flex items-center justify-center gap-2 py-2 rounded-lg text-xs font-semibold transition-all text-ledger-400 hover:text-ledger-700">
                            <i data-lucide="keyboard" class="w-4 h-4"></i> Manual ID Entry
                        </button>
                    </div>

                    <!-- QR Scanner Mode -->
                    <div id="qrScannerContainer" class="space-y-3">
                        <div class="relative bg-slate-950 rounded-xl overflow-hidden min-h-[260px] flex flex-col items-center justify-center border border-ledger-200">
                            <div id="reader" class="w-full max-w-sm"></div>
                            <div id="qrPlaceholder" class="p-8 text-center text-slate-400">
                                <i data-lucide="qr-code" class="w-12 h-12 mx-auto mb-3 text-slate-500 animate-pulse"></i>
                                <p class="text-sm font-medium text-slate-200">Camera Scanner Ready</p>
                                <p class="text-xs text-slate-400 mt-1">Point the camera at student QR code to verify instantly.</p>
                                <button type="button" id="btnStartCamera" onclick="startScanner()" class="mt-4 px-4 py-2 bg-seal hover:bg-seal-dark text-ledger-950 font-semibold text-xs rounded-xl shadow-sm transition-colors">
                                    Start Camera
                                </button>
                            </div>
                        </div>
                        <p class="text-center text-xs text-ledger-400">
                            Camera reads student QR code containing their registered University ID.
                        </p>
                    </div>

                    <!-- Manual Entry Mode -->
                    <div id="manualInputContainer" class="hidden space-y-3">
                        <form onsubmit="handleManualSubmit(event)" class="space-y-3">
                            <label class="block text-xs font-semibold uppercase tracking-wider text-ledger-400">
                                Student ID
                            </label>
                            <div class="flex gap-2">
                                <div class="relative flex-1">
                                    <i data-lucide="search" class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-ledger-400"></i>
                                    <input 
                                        type="text" 
                                        id="manualStudentId" 
                                        placeholder="e.g. HU2001, HU2003..." 
                                        class="w-full pl-10 pr-3.5 py-2.5 rounded-xl border border-ledger-200 font-mono text-sm focus:outline-none focus:ring-2 focus:ring-seal/40 focus:border-seal transition-shadow uppercase"
                                    />
                                </div>
                                <button type="submit" id="btnVerify" class="px-5 py-2.5 bg-ledger-900 hover:bg-ledger-800 text-white text-sm font-medium rounded-xl transition-colors shrink-0 shadow-sm flex items-center gap-1.5">
                                    <span>Verify</span>
                                    <i data-lucide="arrow-right" class="w-4 h-4"></i>
                                </button>
                            </div>
                            <div class="flex items-center gap-2 pt-1">
                                <span class="text-xs text-ledger-400">Quick test:</span>
                                <button type="button" onclick="testId('HU2001')" class="text-xs px-2 py-0.5 rounded bg-emerald-50 text-emerald-700 font-mono hover:bg-emerald-100">HU2001 (Eligible)</button>
                                <button type="button" onclick="testId('HU2003')" class="text-xs px-2 py-0.5 rounded bg-red-50 text-red-700 font-mono hover:bg-red-100">HU2003 (Fee Blocked)</button>
                                <button type="button" onclick="testId('HU3002')" class="text-xs px-2 py-0.5 rounded bg-amber-50 text-amber-700 font-mono hover:bg-amber-100">HU3002 (Absences)</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Verification Result Display -->
                <div id="resultCard" class="hidden bg-white rounded-2xl border border-ledger-100 shadow-card p-6 fade-up">
                    <div class="flex items-start justify-between gap-4 border-b border-ledger-100 pb-5">
                        <div class="flex items-center gap-4">
                            <div id="resAvatar" class="w-14 h-14 rounded-2xl bg-ledger-100 text-ledger-700 font-bold text-xl flex items-center justify-center font-display border border-ledger-200">
                                HI
                            </div>
                            <div>
                                <h3 id="resName" class="font-display text-xl text-ledger-900 font-semibold">Student Name</h3>
                                <p id="resId" class="text-xs font-mono font-semibold text-seal mt-0.5 tracking-wider">HU2001</p>
                                <p id="resFacultyDept" class="text-xs text-ledger-400 mt-0.5">Faculty · Department</p>
                            </div>
                        </div>

                        <!-- Eligibility Stamp Badge (Ink Stamp Seal) -->
                        <div id="resStamp" class="rounded-full border-4 border-eligible text-eligible bg-eligible/5 w-28 h-28 flex flex-col items-center justify-center gap-1 font-display tracking-widest uppercase -rotate-6 shadow-sm shrink-0" style="border-style: double;">
                            <i id="resStampIcon" data-lucide="check-circle-2" class="w-6 h-6 stroke-[1.8]"></i>
                            <span id="resStampText" class="text-center text-[10px] leading-tight px-1 font-semibold">ELIGIBLE · RECORDED</span>
                        </div>
                    </div>

                    <!-- Status Details -->
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 pt-5 text-xs">
                        <div class="bg-ledger-50 rounded-xl p-3 border border-ledger-100">
                            <span class="text-ledger-400 font-medium block mb-1">Fee Clearance</span>
                            <span id="resFee" class="font-semibold px-2 py-0.5 rounded-full inline-block">Cleared</span>
                        </div>
                        <div class="bg-ledger-50 rounded-xl p-3 border border-ledger-100">
                            <span class="text-ledger-400 font-medium block mb-1">Semester Absences</span>
                            <span id="resAbsences" class="font-semibold text-ledger-800">0 of 3 Allowed</span>
                        </div>
                        <div class="bg-ledger-50 rounded-xl p-3 border border-ledger-100 col-span-2 sm:col-span-1">
                            <span class="text-ledger-400 font-medium block mb-1">Verification Time</span>
                            <span id="resTime" class="font-semibold font-mono text-ledger-700">12:00:00</span>
                        </div>
                    </div>

                    <!-- Error Reason Banner (if not eligible) -->
                    <div id="resReasonBox" class="hidden mt-4 p-3.5 bg-red-50 border border-red-200/80 rounded-xl text-red-700 text-xs flex items-start gap-2">
                        <i data-lucide="alert-triangle" class="w-4 h-4 mt-0.5 shrink-0"></i>
                        <span id="resReason">Reason for rejection</span>
                    </div>
                </div>
            </div>

            <!-- Right Column: Exam Roster & Progress -->
            <div class="lg:col-span-5 space-y-6">
                <div class="bg-white rounded-2xl border border-ledger-100 shadow-card p-5">
                    <div class="flex items-center justify-between pb-3 border-b border-ledger-100">
                        <div>
                            <h3 class="font-display text-base text-ledger-900 font-semibold">Registered Roster</h3>
                            <p class="text-ledger-400 text-xs">Students assigned to this examination</p>
                        </div>
                        <span id="rosterCountBadge" class="text-xs font-semibold px-2.5 py-1 rounded-full bg-ledger-100 text-ledger-700">
                            0 / 0 Attended
                        </span>
                    </div>

                    <!-- Progress bar -->
                    <div class="w-full bg-ledger-100 rounded-full h-2 my-4 overflow-hidden">
                        <div id="rosterProgress" class="bg-emerald-500 h-2 rounded-full transition-all duration-500" style="width: 0%"></div>
                    </div>

                    <!-- Roster List -->
                    <div id="rosterList" class="divide-y divide-ledger-100 max-h-[380px] overflow-y-auto pr-1">
                        <p class="py-6 text-center text-ledger-400 text-xs">Loading roster...</p>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="bg-white rounded-2xl border border-ledger-100 shadow-card p-12 text-center">
            <i data-lucide="calendar-x" class="w-12 h-12 text-ledger-300 mx-auto mb-3"></i>
            <h2 class="font-display text-lg font-semibold text-ledger-900">No Examination Selected</h2>
            <p class="text-ledger-400 text-sm mt-1">Please select an ongoing examination from the list or create one.</p>
        </div>
    <?php endif; ?>
</div>

<script>
let currentExamId = <?= $selectedExamId ?>;
let html5QrCode = null;

function changeExam(newExamId) {
    if (newExamId) {
        window.location.href = 'scan.php?exam_id=' + newExamId;
    }
}

function switchMode(mode) {
    const tabQr = document.getElementById('tabQr');
    const tabManual = document.getElementById('tabManual');
    const qrContainer = document.getElementById('qrScannerContainer');
    const manualContainer = document.getElementById('manualInputContainer');

    if (mode === 'qr') {
        tabQr.className = 'flex-1 flex items-center justify-center gap-2 py-2 rounded-lg text-xs font-semibold transition-all bg-white text-ledger-900 shadow-sm';
        tabManual.className = 'flex-1 flex items-center justify-center gap-2 py-2 rounded-lg text-xs font-semibold transition-all text-ledger-400 hover:text-ledger-700';
        qrContainer.classList.remove('hidden');
        manualContainer.classList.add('hidden');
    } else {
        tabManual.className = 'flex-1 flex items-center justify-center gap-2 py-2 rounded-lg text-xs font-semibold transition-all bg-white text-ledger-900 shadow-sm';
        tabQr.className = 'flex-1 flex items-center justify-center gap-2 py-2 rounded-lg text-xs font-semibold transition-all text-ledger-400 hover:text-ledger-700';
        manualContainer.classList.remove('hidden');
        qrContainer.classList.add('hidden');
        document.getElementById('manualStudentId').focus();
    }
}

function testId(id) {
    document.getElementById('manualStudentId').value = id;
    verifyStudent(id);
}

function handleManualSubmit(e) {
    e.preventDefault();
    const id = document.getElementById('manualStudentId').value.trim();
    if (id) {
        verifyStudent(id);
    }
}

async function verifyStudent(studentId) {
    if (!currentExamId) {
        alert('Please select an examination first.');
        return;
    }

    try {
        const res = await fetch('api/scan_check.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ student_id: studentId, exam_id: currentExamId })
        });
        const data = await res.json();

        if (!data.success) {
            alert(data.message || 'Verification error');
            return;
        }

        displayResult(data);
        loadRoster();
    } catch (err) {
        alert('Communication error with server: ' + err.message);
    }
}

function displayResult(data) {
    const card = document.getElementById('resultCard');
    const s = data.student;
    card.classList.remove('hidden');

    document.getElementById('resName').textContent = s.full_name;
    document.getElementById('resId').textContent = s.student_id;
    document.getElementById('resFacultyDept').textContent = `${s.faculty} · ${s.department} (${s.class_name || 'No Class'})`;
    document.getElementById('resAvatar').textContent = s.full_name.split(' ').map(n => n[0]).join('').substring(0, 2);
    document.getElementById('resTime').textContent = new Date().toLocaleTimeString();

    const feeEl = document.getElementById('resFee');
    if (s.fee_status === 'Cleared') {
        feeEl.textContent = 'Cleared';
        feeEl.className = 'font-semibold px-2 py-0.5 rounded-full inline-block bg-emerald-100 text-emerald-800';
    } else {
        feeEl.textContent = 'Not Cleared';
        feeEl.className = 'font-semibold px-2 py-0.5 rounded-full inline-block bg-red-100 text-red-800';
    }

    document.getElementById('resAbsences').textContent = `${s.absence_count} of 3 Allowed`;

    const stamp = document.getElementById('resStamp');
    const stampText = document.getElementById('resStampText');
    const stampIcon = document.getElementById('resStampIcon');
    const reasonBox = document.getElementById('resReasonBox');

    if (data.eligible) {
        stamp.className = 'select-none rounded-full border-4 border-eligible text-eligible bg-eligible/5 w-28 h-28 flex flex-col items-center justify-center gap-1 font-display tracking-widest uppercase -rotate-6 shadow-sm shrink-0';
        stamp.style.borderStyle = 'double';
        stampText.textContent = 'ELIGIBLE · RECORDED';
        stampIcon.setAttribute('data-lucide', 'check-circle-2');
        reasonBox.classList.add('hidden');
    } else {
        stamp.className = 'select-none rounded-full border-4 border-ineligible text-ineligible bg-ineligible/5 w-28 h-28 flex flex-col items-center justify-center gap-1 font-display tracking-widest uppercase -rotate-6 shadow-sm shrink-0';
        stamp.style.borderStyle = 'double';
        stampText.textContent = 'NOT ELIGIBLE';
        stampIcon.setAttribute('data-lucide', 'x-circle');
        reasonBox.classList.remove('hidden');
        document.getElementById('resReason').textContent = data.reason || 'Student does not meet exam eligibility criteria.';
    }

    lucide.createIcons();
    card.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

async function loadRoster() {
    if (!currentExamId) return;
    try {
        const res = await fetch(`api/get_roster.php?exam_id=${currentExamId}`);
        const data = await res.json();
        if (!data.success) return;

        const roster = data.roster;
        const total = data.total_count;
        const attended = data.attended_count;

        document.getElementById('rosterCountBadge').textContent = `${attended} / ${total} Attended`;
        const percent = total > 0 ? Math.round((attended / total) * 100) : 0;
        document.getElementById('rosterProgress').style.width = percent + '%';

        const list = document.getElementById('rosterList');
        if (roster.length === 0) {
            list.innerHTML = '<p class="py-6 text-center text-ledger-400 text-xs">No students registered in this department.</p>';
            return;
        }

        list.innerHTML = roster.map(s => {
            const isAttended = s.attended;
            const isEligible = s.attendance_status === 'Eligible';
            
            let statusBadge = '<span class="text-[11px] font-semibold px-2 py-0.5 rounded bg-slate-100 text-slate-500">Unchecked</span>';
            if (isAttended) {
                statusBadge = isEligible 
                    ? '<span class="text-[11px] font-semibold px-2 py-0.5 rounded bg-emerald-50 text-emerald-700 flex items-center gap-1"><i data-lucide="check" class="w-3 h-3"></i> Eligible</span>'
                    : '<span class="text-[11px] font-semibold px-2 py-0.5 rounded bg-red-50 text-red-700 flex items-center gap-1"><i data-lucide="x" class="w-3 h-3"></i> Blocked</span>';
            }

            return `
                <div class="flex items-center justify-between py-2.5 px-2 hover:bg-ledger-50 rounded-lg transition-colors cursor-pointer" onclick="testId('${s.student_id}')">
                    <div class="flex items-center gap-2.5">
                        <div class="w-7 h-7 rounded-lg ${isAttended ? (isEligible ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800') : 'bg-ledger-100 text-ledger-600'} font-semibold text-xs flex items-center justify-center font-mono">
                            ${s.student_id.substring(2)}
                        </div>
                        <div>
                            <p class="font-medium text-xs text-ledger-900">${s.full_name}</p>
                            <p class="text-[11px] font-mono text-ledger-400">${s.student_id} · ${s.fee_status}</p>
                        </div>
                    </div>
                    <div>${statusBadge}</div>
                </div>
            `;
        }).join('');

        lucide.createIcons();
    } catch (e) {
        console.error('Roster fetch error', e);
    }
}

function startScanner() {
    document.getElementById('qrPlaceholder').classList.add('hidden');
    html5QrCode = new Html5Qrcode("reader");
    html5QrCode.start(
        { facingMode: "environment" },
        { fps: 10, qrbox: { width: 220, height: 220 } },
        (decodedText) => {
            // Success
            verifyStudent(decodedText.trim());
        },
        (errorMessage) => {
            // Scanning in progress
        }
    ).catch(err => {
        alert("Camera access failed or unavailable: " + err);
        document.getElementById('qrPlaceholder').classList.remove('hidden');
    });
}

document.addEventListener('DOMContentLoaded', () => {
    loadRoster();
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
