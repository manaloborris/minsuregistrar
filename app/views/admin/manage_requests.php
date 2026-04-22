<?php
$title = 'Manage Requests - MinSU e-Registrar';
include APP_ROOT . '/app/views/partials/head.php';

$paymentColors = [
    'Paid' => 'bg-emerald-100 text-emerald-700',
    'Pending' => 'bg-amber-100 text-amber-700',
    'Unpaid' => 'bg-slate-100 text-slate-700',
    'Failed' => 'bg-red-100 text-red-700',
    'Cancelled' => 'bg-slate-100 text-slate-700',
    'No Record' => 'bg-slate-100 text-slate-700',
];

$statusColors = [
    'Pending' => 'bg-amber-100 text-amber-700',
    'Processing' => 'bg-blue-100 text-blue-700',
    'Approved' => 'bg-emerald-100 text-emerald-700',
    'Ready for Pickup' => 'bg-lime-100 text-lime-700',
    'Completed' => 'bg-green-100 text-green-700',
    'Rejected' => 'bg-red-100 text-red-700',
    'Cancelled' => 'bg-slate-200 text-slate-700',
];

$departmentCode = trim((string) ($departmentCode ?? ''));

// Extract unique statuses and payment statuses for filter dropdowns
$uniqueStatuses = [];
$uniquePayments = [];
$uniqueAppointments = ['Set', 'Not Set'];
foreach ($requests as $row) {
    $status = $row['status'] ?? '';
    if ($status && !in_array($status, $uniqueStatuses)) {
        $uniqueStatuses[] = $status;
    }
    $paymentStatus = trim((string) ($row['payment_status_display'] ?? $row['payment_status'] ?? 'No Record'));
    if ($paymentStatus && !in_array($paymentStatus, $uniquePayments)) {
        $uniquePayments[] = $paymentStatus;
    }
}
sort($uniqueStatuses);
sort($uniquePayments);

$availableYears = [];
foreach ($requests as $row) {
    $requestDateTs = strtotime((string) ($row['request_date'] ?? ''));
    if ($requestDateTs !== false) {
        $availableYears[(int) date('Y', $requestDateTs)] = true;
    }
}
$availableYears = array_keys($availableYears);
rsort($availableYears);
?>

<div class="mx-auto w-full max-w-7xl px-5 py-6 lg:px-8">
    <div class="mb-6"><?php include APP_ROOT . '/app/views/partials/flash.php'; ?></div>
    <?php include APP_ROOT . '/app/views/partials/admin_topbar.php'; ?>
    <div class="flex flex-col gap-6 lg:flex-row">
        <?php include APP_ROOT . '/app/views/partials/admin_sidebar.php'; ?>

        <main class="min-w-0 flex-1 fade-in">
            <section class="bubble-card p-6">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h1 class="text-2xl font-extrabold text-slate-800">Manage Requests</h1>
                        <p class="mt-1 text-sm text-slate-600">Approve, reject, and update student document requests.</p>
                        <?php if ($departmentCode !== ''): ?>
                            <p class="mt-1 text-xs font-bold uppercase tracking-wide text-emerald-700">Scoped to department: <?= esc($departmentCode) ?></p>
                        <?php endif; ?>
                    </div>
                    <div id="bulkActionToolbar" class="hidden sticky top-2 z-20 rounded-lg border border-blue-200 bg-blue-50/95 p-3 backdrop-blur supports-[backdrop-filter]:bg-blue-50/85">
                        <p class="text-xs font-bold text-blue-700"><span id="bulkSelectedCount">0</span> selected</p>
                        <div class="mt-2 flex flex-wrap gap-2">
                            <select id="bulkStatusSelect" class="rounded px-2 py-1 text-xs border border-blue-300">
                                <option value="">Change Status to...</option>
                                <option value="Processing">Processing</option>
                                <option value="Approved">Approved</option>
                                <option value="Ready for Pickup">Ready for Pickup</option>
                                <option value="Completed">Completed</option>
                                <option value="Rejected">Rejected</option>
                                <option value="Cancelled">Cancelled</option>
                            </select>
                            <button id="bulkStatusApply" class="rounded bg-blue-500 px-3 py-1 text-xs font-bold text-white hover:bg-blue-600">Apply</button>
                            <button id="bulkClearSelection" class="rounded bg-slate-300 px-3 py-1 text-xs font-bold text-slate-700 hover:bg-slate-400">Clear</button>
                        </div>
                    </div>
                </div>

                <!-- View Mode Tabs -->
                <div class="mt-5 border-b border-slate-200">
                    <div class="flex gap-4">
                        <button id="viewModeAllBtn" class="view-mode-btn border-b-2 border-emerald-500 px-4 py-2 text-sm font-bold text-emerald-700 -mb-0.5" data-view="all">
                            All Requests
                        </button>
                        <button id="viewModeByDateBtn" class="view-mode-btn border-b-2 border-transparent px-4 py-2 text-sm font-bold text-slate-600 hover:text-slate-800 -mb-0.5" data-view="bydate">
                            By Date
                        </button>
                    </div>
                </div>

                <!-- Filters Bar -->
                <div id="filtersBar" class="mt-5 rounded-lg border border-slate-200 bg-slate-50 p-4">
                    <p class="mb-3 text-xs font-bold uppercase tracking-wide text-slate-600">Filters</p>
                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
                        <input type="text" id="filterSearch" placeholder="Search student ID or name..." class="rounded-lg border border-slate-300 px-3 py-2 text-xs placeholder-slate-400">
                        <select id="filterStatus" class="rounded-lg border border-slate-300 px-3 py-2 text-xs">
                            <option value="">All Status</option>
                            <?php foreach ($uniqueStatuses as $st): ?>
                                <option value="<?= esc($st) ?>"><?= esc($st) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select id="filterPayment" class="rounded-lg border border-slate-300 px-3 py-2 text-xs">
                            <option value="">All Payment</option>
                            <?php foreach ($uniquePayments as $pm): ?>
                                <option value="<?= esc($pm) ?>"><?= esc($pm) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select id="filterAppointment" class="rounded-lg border border-slate-300 px-3 py-2 text-xs">
                            <option value="">All Appointments</option>
                            <option value="Set">Set</option>
                            <option value="Not Set">Not Set</option>
                        </select>
                        <button id="filterReset" class="rounded-lg bg-slate-300 px-3 py-2 text-xs font-bold text-slate-700 hover:bg-slate-400">Reset Filters</button>
                    </div>
                </div>

                <!-- All Requests View (Table) -->
                <div id="viewAll" class="mt-5">
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[1200px] text-sm" id="requestsTable">
                        <thead>
                            <tr class="bg-slate-100 text-left text-slate-600">
                                <th class="px-3 py-3">
                                    <input type="checkbox" id="selectAllCheckbox" class="rounded border-slate-300 cursor-pointer">
                                </th>
                                <th class="px-3 py-3 cursor-pointer hover:bg-slate-200 select-none font-bold" data-sort="student_id">
                                    Student ID <span class="text-xs text-slate-400">^v</span>
                                </th>
                                <th class="px-3 py-3 cursor-pointer hover:bg-slate-200 select-none font-bold" data-sort="first_name">
                                    Student Name <span class="text-xs text-slate-400">^v</span>
                                </th>
                                <th class="px-3 py-3">Document Type</th>
                                <th class="px-3 py-3">Quantity</th>
                                <th class="px-3 py-3 cursor-pointer hover:bg-slate-200 select-none font-bold" data-sort="request_date">
                                    Request Date <span class="text-xs text-slate-400">^v</span>
                                </th>
                                <th class="px-3 py-3">Status</th>
                                <th class="px-3 py-3">Payment</th>
                                <th class="px-3 py-3">Amount</th>
                                <th class="px-3 py-3">Pickup Schedule</th>
                                <th class="px-3 py-3">Files</th>
                                <th class="px-3 py-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($requests as $row): ?>
                            <?php
                            $paymentStatus = trim((string) ($row['payment_status_display'] ?? $row['payment_status'] ?? ''));
                            if ($paymentStatus === '') {
                                $paymentStatus = 'No Record';
                            }
                            $paymentColor = $paymentColors[$paymentStatus] ?? 'bg-slate-100 text-slate-700';
                            $statusColor = $statusColors[$row['status']] ?? 'bg-slate-100 text-slate-700';
                            $hasPickupSchedule = !empty($row['appointment_date']) && !empty($row['appointment_time']);
                            
                            // Terminal states: can't change status anymore
                            $isTerminalStatus = in_array($row['status'], ['Completed', 'Rejected', 'Cancelled'], true);
                            $isPaymentLocked = strtolower((string) ($row['payment_status'] ?? '')) === 'paid';
                            $isLocked = $isTerminalStatus || $isPaymentLocked;
                            ?>
                            <tr class="border-t border-emerald-50 hover:bg-slate-50 transition data-request-row <?= $isLocked ? 'opacity-75' : '' ?>" 
                                data-request-id="<?= esc((string) $row['request_id']) ?>"
                                data-status="<?= esc($row['status']) ?>"
                                data-payment="<?= esc($paymentStatus) ?>"
                                data-appointment="<?= $hasPickupSchedule ? 'Set' : 'Not Set' ?>"
                                data-search="<?= esc(strtolower($row['student_id'] . ' ' . $row['first_name'] . ' ' . $row['last_name'])) ?>"
                                data-is-locked="<?= $isLocked ? '1' : '0' ?>">
                                <td class="px-3 py-3">
                                    <input type="checkbox" class="request-checkbox rounded border-slate-300 cursor-pointer" value="<?= esc((string) $row['request_id']) ?>" <?= $isLocked ? 'disabled' : '' ?>>
                                </td>
                                <td class="px-3 py-3 font-bold text-slate-700"><?= esc($row['student_id']) ?></td>
                                <td class="px-3 py-3 text-slate-700"><?= esc($row['first_name'] . ' ' . $row['last_name']) ?></td>
                                <td class="px-3 py-3 text-slate-700"><?= esc($row['document_name']) ?></td>
                                <td class="px-3 py-3 text-slate-700"><?= esc((string) ($row['quantity'] ?? 1)) ?></td>
                                <td class="px-3 py-3 text-xs text-slate-600"><?= esc($row['request_date']) ?></td>
                                <td class="px-3 py-3">
                                    <div class="flex items-center gap-2">
                                        <span class="status-pill <?= esc($statusColor) ?>"><?= esc($row['status']) ?></span>
                                        <?php if ($isTerminalStatus): ?>
                                            <span class="inline-flex items-center gap-1 rounded-full bg-slate-200 px-2 py-0.5 text-xs font-bold text-slate-700" title="This request is locked. No changes allowed.">
                                                Locked
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="px-3 py-3">
                                    <div class="flex items-center gap-2">
                                        <span class="status-pill <?= esc($paymentColor) ?>"><?= esc($paymentStatus) ?></span>
                                        <?php if ($isPaymentLocked): ?>
                                            <span class="inline-flex items-center gap-1 rounded-full bg-emerald-200 px-2 py-0.5 text-xs font-bold text-emerald-700" title="Payment is locked. Status cannot be changed.">
                                                Locked
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="px-3 py-3 text-slate-700 text-xs">
                                    <?= ($row['amount'] !== null && $row['amount'] !== '') ? 'PHP ' . esc(number_format((float) $row['amount'], 2)) : '-' ?>
                                </td>
                                <td class="px-3 py-3 text-xs text-slate-700">
                                    <?php if ($hasPickupSchedule): ?>
                                        <div class="font-bold text-slate-800"><?= esc($row['appointment_date']) ?></div>
                                        <span class="inline-flex rounded-full bg-sky-100 px-2 py-0.5 text-xs font-black text-sky-700">
                                            <?= esc(date('g:i A', strtotime((string) $row['appointment_time']))) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-slate-400">Not set</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-3 py-3 text-xs text-slate-700">
                                    <div class="font-bold"><?= esc((string) ($row['file_count'] ?? 0)) ?></div>
                                    <a href="<?= url('admin/request-files/' . $row['request_id']) ?>" class="text-emerald-700 hover:underline font-bold">View</a>
                                </td>
                                <td class="px-3 py-3 relative">
                                    <?php if ($isLocked): ?>
                                        <button class="inline-flex items-center gap-1 rounded-lg px-2 py-1.5 bg-slate-300 text-slate-600 cursor-not-allowed text-xs font-bold" title="Request is locked. View details to see why." disabled>
                                            Locked
                                        </button>
                                        <div class="absolute right-0 top-10 hidden z-50 rounded-lg border border-slate-300 bg-slate-100 shadow-lg min-w-[200px] pointer-events-none">
                                            <p class="px-4 py-3 text-xs text-slate-700">This request cannot be modified.</p>
                                            <a href="<?= url('admin/manage-requests/' . $row['request_id']) ?>" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-200 border-t border-slate-300">
                                                View Details
                                            </a>
                                        </div>
                                    <?php else: ?>
                                        <button class="row-action-menu-btn inline-flex items-center gap-1 rounded-lg px-2 py-1.5 hover:bg-slate-200" data-request-id="<?= esc((string) $row['request_id']) ?>">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><circle cx="6" cy="12" r="2"/><circle cx="12" cy="12" r="2"/><circle cx="18" cy="12" r="2"/></svg>
                                        </button>
                                        <div class="row-action-dropdown absolute right-0 top-10 hidden z-50 rounded-lg border border-slate-200 bg-white shadow-lg min-w-[180px]" data-request-id="<?= esc((string) $row['request_id']) ?>">
                                            <a href="<?= url('admin/manage-requests/' . $row['request_id']) ?>" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-100 rounded-t-lg">
                                                View Full Details
                                            </a>
                                            <div class="border-t border-slate-200"></div>
                                            <button class="quick-status-btn w-full text-left px-4 py-2 text-sm text-slate-700 hover:bg-slate-100" data-status="Processing" data-request-id="<?= esc((string) $row['request_id']) ?>">
                                                Mark Processing
                                            </button>
                                            <button class="quick-status-btn w-full text-left px-4 py-2 text-sm text-slate-700 hover:bg-slate-100" data-status="Approved" data-request-id="<?= esc((string) $row['request_id']) ?>">
                                                Mark Approved
                                            </button>
                                            <button class="quick-status-btn w-full text-left px-4 py-2 text-sm text-slate-700 hover:bg-slate-100" data-status="Ready for Pickup" data-request-id="<?= esc((string) $row['request_id']) ?>">
                                                Ready for Pickup
                                            </button>
                                            <button class="quick-status-btn w-full text-left px-4 py-2 text-sm text-slate-700 hover:bg-slate-100" data-status="Completed" data-request-id="<?= esc((string) $row['request_id']) ?>">
                                                Mark Completed
                                            </button>
                                            <button class="quick-status-btn w-full text-left px-4 py-2 text-sm text-red-700 hover:bg-red-50" data-status="Rejected" data-request-id="<?= esc((string) $row['request_id']) ?>">
                                                Mark Rejected
                                            </button>
                                            <div class="border-t border-slate-200"></div>
                                            <a href="<?= url('admin/request-files/' . $row['request_id']) ?>" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-100 rounded-b-lg">
                                                View Files
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($requests)): ?>
                            <tr><td colspan="12" class="px-3 py-4 text-center text-slate-500">No request records found.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                    </div>
                </div>

                <!-- By Date View (Grouped) -->
                <div id="viewByDate" class="hidden mt-5 space-y-4">
                    <div id="byDateFiltersBar" class="sticky top-0 z-20 rounded-lg border border-slate-200 bg-white/95 p-4 backdrop-blur supports-[backdrop-filter]:bg-white/80">
                        <p class="mb-3 text-xs font-bold uppercase tracking-wide text-slate-600">Date-first Filters</p>
                        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                            <input type="text" id="byDateSearch" placeholder="Search student ID, name, or document..." class="rounded-lg border border-slate-300 px-3 py-2 text-xs placeholder-slate-400">
                            <select id="byDateMonth" class="rounded-lg border border-slate-300 px-3 py-2 text-xs">
                                <option value="">All Months</option>
                                <option value="01">January</option>
                                <option value="02">February</option>
                                <option value="03">March</option>
                                <option value="04">April</option>
                                <option value="05">May</option>
                                <option value="06">June</option>
                                <option value="07">July</option>
                                <option value="08">August</option>
                                <option value="09">September</option>
                                <option value="10">October</option>
                                <option value="11">November</option>
                                <option value="12">December</option>
                            </select>
                            <select id="byDateYear" class="rounded-lg border border-slate-300 px-3 py-2 text-xs">
                                <option value="">All Years</option>
                                <?php foreach ($availableYears as $year): ?>
                                    <option value="<?= esc((string) $year) ?>"><?= esc((string) $year) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button id="byDateReset" class="rounded-lg bg-slate-300 px-3 py-2 text-xs font-bold text-slate-700 hover:bg-slate-400">Reset Date Filters</button>
                        </div>
                    </div>

                    <?php
                    // Group requests by exact request date (date-first view)
                    $groupedRequests = [];
                    foreach ($requests as $row) {
                        $requestDateRaw = (string) ($row['request_date'] ?? '');
                        if ($requestDateRaw === '') {
                            continue;
                        }

                        $requestDateTs = strtotime($requestDateRaw);
                        $requestDate = $requestDateTs !== false ? date('Y-m-d', $requestDateTs) : $requestDateRaw;

                        if (!isset($groupedRequests[$requestDate])) {
                            $groupedRequests[$requestDate] = [];
                        }
                        $groupedRequests[$requestDate][] = $row;
                    }

                    krsort($groupedRequests);
                    
                    foreach ($groupedRequests as $requestDate => $groupRows):
                        $requestCount = count($groupRows);
                        $dateTs = strtotime($requestDate);
                        $groupLabel = $dateTs !== false ? date('F d, Y', $dateTs) : $requestDate;
                        $groupMonth = $dateTs !== false ? date('m', $dateTs) : '';
                        $groupYear = $dateTs !== false ? date('Y', $dateTs) : '';
                    ?>
                    <div class="date-group-wrapper rounded-lg border border-slate-200 bg-white" data-date-group-wrapper="1" data-month="<?= esc($groupMonth) ?>" data-year="<?= esc($groupYear) ?>" data-date="<?= esc($requestDate) ?>">
                        <button class="date-group-toggle sticky top-0 z-10 w-full flex items-center justify-between px-4 py-3 bg-white hover:bg-slate-50 border-b border-slate-200 font-bold text-slate-700 cursor-pointer" data-group="<?= esc($requestDate) ?>">
                            <span><?= esc($groupLabel) ?> <span class="text-xs font-normal text-slate-500">(<span class="date-group-count" data-original-count="<?= $requestCount ?>"><?= $requestCount ?></span> request<?= $requestCount !== 1 ? 's' : '' ?>)</span></span>
                            <span class="date-group-arrow transition text-slate-600">v</span>
                        </button>
                        <div class="date-group-content overflow-x-auto" data-group="<?= esc($requestDate) ?>">
                            <table class="w-full min-w-[1200px] text-sm">
                                <thead>
                                    <tr class="bg-slate-50 text-left text-slate-600 text-xs">
                                        <th class="px-3 py-2">Student</th>
                                        <th class="px-3 py-2">Document</th>
                                        <th class="px-3 py-2">Qty</th>
                                        <th class="px-3 py-2">Date</th>
                                        <th class="px-3 py-2">Status</th>
                                        <th class="px-3 py-2">Payment</th>
                                        <th class="px-3 py-2">Amount</th>
                                        <th class="px-3 py-2">Pickup</th>
                                        <th class="px-3 py-2">Files</th>
                                        <th class="px-3 py-2">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($groupRows as $row): ?>
                                    <?php
                                    $paymentStatus = trim((string) ($row['payment_status_display'] ?? $row['payment_status'] ?? ''));
                                    if ($paymentStatus === '') {
                                        $paymentStatus = 'No Record';
                                    }
                                    $paymentColor = $paymentColors[$paymentStatus] ?? 'bg-slate-100 text-slate-700';
                                    $statusColor = $statusColors[$row['status']] ?? 'bg-slate-100 text-slate-700';
                                    $hasPickupSchedule = !empty($row['appointment_date']) && !empty($row['appointment_time']);
                                    $isTerminalStatus = in_array($row['status'], ['Completed', 'Rejected', 'Cancelled'], true);
                                    $isPaymentLocked = strtolower((string) ($row['payment_status'] ?? '')) === 'paid';
                                    $isLocked = $isTerminalStatus || $isPaymentLocked;
                                    $rowDateTs = strtotime((string) ($row['request_date'] ?? ''));
                                    $rowMonth = $rowDateTs !== false ? date('m', $rowDateTs) : '';
                                    $rowYear = $rowDateTs !== false ? date('Y', $rowDateTs) : '';
                                    $rowSearch = strtolower(
                                        (string) ($row['student_id'] ?? '') . ' ' .
                                        (string) ($row['first_name'] ?? '') . ' ' .
                                        (string) ($row['last_name'] ?? '') . ' ' .
                                        (string) ($row['document_name'] ?? '')
                                    );
                                    ?>
                                    <tr class="by-date-row border-t border-slate-100 hover:bg-slate-50 transition"
                                        data-month="<?= esc($rowMonth) ?>"
                                        data-year="<?= esc($rowYear) ?>"
                                        data-search="<?= esc($rowSearch) ?>">
                                        <td class="px-3 py-2 text-xs">
                                            <div class="font-bold text-slate-800"><?= esc($row['student_id']) ?></div>
                                            <div class="text-slate-600"><?= esc($row['first_name'] . ' ' . $row['last_name']) ?></div>
                                        </td>
                                        <td class="px-3 py-2 text-xs text-slate-700"><?= esc($row['document_name']) ?></td>
                                        <td class="px-3 py-2 text-xs text-slate-700"><?= esc((string) ($row['quantity'] ?? 1)) ?></td>
                                        <td class="px-3 py-2 text-xs text-slate-600"><?= esc($row['request_date']) ?></td>
                                        <td class="px-3 py-2"><span class="status-pill text-xs <?= esc($statusColor) ?>"><?= esc($row['status']) ?></span></td>
                                        <td class="px-3 py-2"><span class="status-pill text-xs <?= esc($paymentColor) ?>"><?= esc($paymentStatus) ?></span></td>
                                        <td class="px-3 py-2 text-xs text-slate-700">
                                            <?= ($row['amount'] !== null && $row['amount'] !== '') ? 'PHP ' . esc(number_format((float) $row['amount'], 2)) : '-' ?>
                                        </td>
                                        <td class="px-3 py-2 text-xs text-slate-700">
                                            <?php if ($hasPickupSchedule): ?>
                                                <div class="font-bold"><?= esc($row['appointment_date']) ?></div>
                                                <span class="text-sky-700 font-bold"><?= esc(date('g:i A', strtotime((string) $row['appointment_time']))) ?></span>
                                            <?php else: ?>
                                                <span class="text-slate-400">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-3 py-2 text-xs text-slate-700">
                                            <a href="<?= url('admin/request-files/' . $row['request_id']) ?>" class="text-emerald-700 hover:underline font-bold">
                                                <?= esc((string) ($row['file_count'] ?? 0)) ?> files
                                            </a>
                                        </td>
                                        <td class="px-3 py-2 relative">
                                            <?php if ($isLocked): ?>
                                                <button class="inline-flex items-center gap-1 rounded-lg px-2 py-1.5 bg-slate-300 text-slate-600 cursor-not-allowed text-xs font-bold" disabled title="Locked">
                                                    Locked
                                                </button>
                                            <?php else: ?>
                                                <a href="<?= url('admin/manage-requests/' . $row['request_id']) ?>" class="inline-flex items-center gap-1 rounded-lg px-2 py-1.5 bg-emerald-100 text-emerald-700 hover:bg-emerald-200 text-xs font-bold">
                                                    Open
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    
                    <div id="byDateNoResults" class="hidden rounded-lg border border-slate-200 bg-slate-50 p-6 text-center text-slate-600">
                        No requests match the current date filters.
                    </div>

                    <?php if (empty($groupedRequests)): ?>
                        <div class="rounded-lg border border-slate-200 bg-slate-50 p-6 text-center text-slate-600">
                            No requests found.
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </main>
    </div>
</div>

<?php include APP_ROOT . '/app/views/partials/foot.php'; ?>

