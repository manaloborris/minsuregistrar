<?php
$title = 'Request Actions - MinSU e-Registrar';
include APP_ROOT . '/app/views/partials/head.php';

$transitions = [
    'Pending' => ['Processing', 'Approved', 'Rejected', 'Cancelled'],
    'Processing' => ['Approved', 'Ready for Pickup', 'Rejected', 'Cancelled'],
    'Approved' => ['Ready for Pickup', 'Completed', 'Cancelled'],
    'Ready for Pickup' => ['Completed', 'Cancelled'],
    'Completed' => [],
    'Rejected' => [],
    'Cancelled' => [],
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

$paymentColors = [
    'Paid' => 'bg-emerald-100 text-emerald-700',
    'Pending' => 'bg-amber-100 text-amber-700',
    'Unpaid' => 'bg-slate-100 text-slate-700',
    'Failed' => 'bg-red-100 text-red-700',
    'Cancelled' => 'bg-slate-100 text-slate-700',
    'No Record' => 'bg-slate-100 text-slate-700',
];

$paymentStatus = trim((string) ($request['payment_status_display'] ?? $request['payment_status'] ?? ''));
if ($paymentStatus === '') {
    $paymentStatus = 'No Record';
}
$paymentColor = $paymentColors[$paymentStatus] ?? 'bg-slate-100 text-slate-700';
$paymentMethodDisplay = trim((string) ($request['payment_method_display'] ?? $request['payment_method'] ?? ''));
if ($paymentMethodDisplay === '') {
    $paymentMethodDisplay = '-';
}
$processAppointments = $processAppointments ?? [];
$processAppointmentAccess = $processAppointmentAccess ?? [];
$appointmentTimeSlots = $appointmentTimeSlots ?? [];
$occupiedAppointmentTimesByDate = $occupiedAppointmentTimesByDate ?? [];
$bookedAppointmentDetailsByDate = $bookedAppointmentDetailsByDate ?? [];
$paymentId = (int) ($request['payment_id'] ?? 0);
$nextOptions = $transitions[$request['status']] ?? [];
$canEditPickup = in_array($request['status'], ['Processing', 'Approved', 'Ready for Pickup'], true);
$statusColor = $statusColors[$request['status']] ?? 'bg-slate-100 text-slate-700';
$isPaymentLocked = strtolower((string) ($request['payment_status'] ?? $paymentStatus)) === 'paid';
$isCashPayment = strtolower((string) ($request['payment_method'] ?? '')) === 'cash';
$cashPaymentDeadline = trim((string) ($request['cash_payment_deadline'] ?? ''));
$paymentAppointmentSelected = false;
foreach ($processAppointments as $pa) {
    if (strtolower((string) ($pa['appointment_type'] ?? '')) === 'payment') {
        $paymentAppointmentSelected = true;
        break;
    }
}

$hasPickupSchedule = !empty($request['appointment_date']) && !empty($request['appointment_time']);
$pickupDateValue = trim((string) ($request['appointment_date'] ?? ''));
$pickupTimeValue = substr(trim((string) ($request['appointment_time'] ?? '')), 0, 5);
$pickupTakenTimes = $occupiedAppointmentTimesByDate[$pickupDateValue] ?? [];
$pickupBookedDetails = $bookedAppointmentDetailsByDate[$pickupDateValue] ?? [];
$hasSelectedProcessAppointment = !empty($processAppointments);
$isTerminalStatus = in_array($request['status'], ['Completed', 'Rejected', 'Cancelled'], true);

$recommendedTab = 'appointment';
if ($isTerminalStatus) {
    $recommendedTab = 'status';
} elseif (in_array($request['status'], ['Approved', 'Ready for Pickup'], true)) {
    $recommendedTab = ($canEditPickup && !$hasPickupSchedule) ? 'pickup' : 'status';
} elseif (!$hasSelectedProcessAppointment) {
    $recommendedTab = 'appointment';
} elseif ($paymentStatus !== 'Paid') {
    $recommendedTab = 'payment';
} elseif ($canEditPickup && !$hasPickupSchedule) {
    $recommendedTab = 'pickup';
} else {
    $recommendedTab = 'status';
}

$workflowHint = 'Student should select payment/follow-up schedule first.';
if ($isTerminalStatus) {
    $workflowHint = 'Request is already terminal. Review status notes only.';
} elseif ($recommendedTab === 'payment') {
    $workflowHint = 'Update payment status after schedule/verification.';
} elseif ($recommendedTab === 'pickup') {
    $workflowHint = 'Set final pickup schedule once requirements are cleared.';
} elseif ($recommendedTab === 'status') {
    $workflowHint = 'Adjust status/instructions if process is complete.';
}
?>

<div class="mx-auto w-full max-w-7xl px-5 py-6 lg:px-8">
    <div class="mb-6"><?php include APP_ROOT . '/app/views/partials/flash.php'; ?></div>
    <?php include APP_ROOT . '/app/views/partials/admin_topbar.php'; ?>
    <div class="flex flex-col gap-6 lg:flex-row">
        <?php include APP_ROOT . '/app/views/partials/admin_sidebar.php'; ?>

        <main class="min-w-0 flex-1 space-y-6 fade-in">
            <section class="bubble-card p-6">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h1 class="text-2xl font-extrabold text-slate-800">Request Actions</h1>
                        <p class="mt-1 text-sm text-slate-600">Manage payment/follow-up appointments, status updates, and final pickup schedule from one page.</p>
                    </div>
                    <a href="<?= url('admin/manage-requests') ?>" class="page-back-link">Back to Manage Requests</a>
                </div>
            </section>

            <section class="grid gap-6 lg:grid-cols-2">
                <div class="bubble-card p-6">
                    <h2 class="text-sm font-black uppercase tracking-[0.08em] text-slate-500">Request Summary</h2>
                    <div class="mt-4 space-y-2 text-sm text-slate-700">
                        <p><span class="font-bold">Student:</span> <?= esc($request['first_name'] . ' ' . $request['last_name']) ?> (<?= esc($request['student_id']) ?>)</p>
                        <p><span class="font-bold">Document:</span> <?= esc($request['document_name']) ?></p>
                        <p><span class="font-bold">Request Date:</span> <?= esc($request['request_date']) ?></p>
                        <p><span class="font-bold">Status:</span> <span class="status-pill <?= esc($statusColor) ?>"><?= esc($request['status']) ?></span></p>
                        <p>
                            <span class="font-bold">Payment:</span>
                            <span class="status-pill <?= esc($paymentColor) ?>"><?= esc($paymentStatus) ?></span>
                        </p>
                        <p><span class="font-bold">Amount:</span> <?= ($request['amount'] !== null && $request['amount'] !== '') ? 'PHP ' . esc(number_format((float) $request['amount'], 2)) : '-' ?></p>
                        <p><span class="font-bold">Method:</span> <?= esc(($request['payment_method_display'] ?? $request['payment_method']) ?: '-') ?></p>
                        <p><span class="font-bold">Reference #:</span> <?= esc($request['reference_number'] ?: '-') ?></p>
                        <p><span class="font-bold">Follow-up Files:</span> <?= esc((string) ($request['file_count'] ?? 0)) ?> file(s)</p>
                        <?php if (!empty($request['last_file_upload'])): ?>
                            <p><span class="font-bold">Last Upload:</span> <?= esc($request['last_file_upload']) ?></p>
                        <?php endif; ?>
                        <a href="<?= url('admin/request-files/' . $request['request_id']) ?>" class="inline-block font-bold text-emerald-700">View uploaded files</a>
                    </div>

                    <div class="mt-5 rounded-xl border border-emerald-100 bg-emerald-50 p-3 text-xs text-emerald-800">
                        <p class="font-black uppercase tracking-wide">Workflow Recommendation</p>
                        <p class="mt-1">Next best action: <span class="font-bold"><?= esc(strtoupper($recommendedTab)) ?></span></p>
                        <p class="mt-1 text-emerald-700"><?= esc($workflowHint) ?></p>
                    </div>
                </div>

                <div class="space-y-6">
                    <section class="bubble-card p-4">
                        <div class="flex flex-wrap gap-2" id="adminActionTabs">
                            <button type="button" data-admin-action-tab="appointment" class="rounded-lg px-3 py-2 text-xs font-bold bg-indigo-100 text-indigo-700">Appointment</button>
                            <button type="button" data-admin-action-tab="payment" class="rounded-lg px-3 py-2 text-xs font-bold bg-emerald-100 text-emerald-700">Payment</button>
                            <button type="button" data-admin-action-tab="status" class="rounded-lg px-3 py-2 text-xs font-bold bg-amber-100 text-amber-700">Status</button>
                            <button type="button" data-admin-action-tab="pickup" class="rounded-lg px-3 py-2 text-xs font-bold bg-sky-100 text-sky-700">Pickup</button>
                        </div>
                    </section>

                    <section class="bubble-card p-6 admin-action-panel" data-admin-action-panel="appointment" <?= $recommendedTab === 'appointment' ? '' : 'hidden' ?>>
                        <h2 class="text-sm font-black uppercase tracking-[0.08em] text-slate-500">Enable Student Appointment Selection</h2>
                        <form method="post" action="<?= url('admin/manage-requests/process-appointment') ?>" class="mt-4 space-y-3" data-undo-submit="1" data-undo-message="Appointment instruction will be sent in 10 seconds.">
                            <?php csrf_field(); ?>
                            <input type="hidden" name="request_id" value="<?= esc((string) $request['request_id']) ?>">
                            <div class="grid gap-2 sm:grid-cols-1">
                                <select name="appointment_type" class="w-full rounded-lg border border-emerald-100 px-3 py-2 text-sm">
                                    <option value="payment" <?= $isPaymentLocked ? 'disabled' : '' ?>>Payment Appointment<?= $isPaymentLocked ? ' (Locked: Paid)' : '' ?></option>
                                    <option value="followup">Follow-up Documents Appointment</option>
                                </select>
                            </div>
                            <input type="date" name="appointment_date" min="<?= date('Y-m-d') ?>" required class="w-full rounded-lg border border-emerald-100 px-3 py-2 text-sm">
                            <textarea name="appointment_note" rows="2" placeholder="Instruction for student (e.g., bring receipt/original ID)." class="w-full rounded-lg border border-emerald-100 px-3 py-2 text-sm"></textarea>
                            <?php if ($isCashPayment): ?>
                                <p class="text-xs text-amber-700">For cash payments, the selected appointment date is also the auto-cancel date if still unpaid.</p>
                            <?php endif; ?>
                            <button class="w-full rounded-lg bg-indigo-500 px-4 py-2 text-sm font-bold text-white hover:bg-indigo-600">Notify Student to Select Time</button>
                            <p class="text-xs text-slate-500">Admin sends instruction only. Student will choose date/time in their Appointments page.</p>
                        </form>

                        <div class="mt-4 overflow-x-auto">
                            <table class="w-full min-w-[520px] text-xs">
                                <thead>
                                    <tr class="text-left text-slate-500">
                                        <th class="px-2 py-1">Type</th>
                                        <th class="px-2 py-1">Date</th>
                                        <th class="px-2 py-1">State</th>
                                        <th class="px-2 py-1">Note</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($processAppointmentAccess as $item): ?>
                                    <?php
                                    $typeLabel = (($item['appointment_type'] ?? '') === 'payment')
                                        ? 'Payment Appointment'
                                        : 'Follow-up Documents Appointment';
                                    $state = ((int) ($item['is_enabled'] ?? 0) === 1)
                                        ? 'Student may select schedule'
                                        : 'Closed';
                                    ?>
                                    <tr class="border-t border-emerald-50">
                                        <td class="px-2 py-1 font-bold text-slate-700"><?= esc($typeLabel) ?></td>
                                        <td class="px-2 py-1 text-slate-600"><?= esc((string) (($item['preferred_date'] ?? '') !== '' ? $item['preferred_date'] : '-')) ?></td>
                                        <td class="px-2 py-1 text-slate-600"><?= esc($state) ?></td>
                                        <td class="px-2 py-1 text-slate-600"><?= esc((string) ($item['note'] ?? '-')) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($processAppointmentAccess)): ?>
                                    <tr><td colspan="4" class="px-2 py-2 text-center text-slate-500">No self-scheduling instruction sent yet.</td></tr>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4 overflow-x-auto">
                            <p class="mb-2 text-xs font-bold uppercase tracking-wide text-slate-500">Selected Appointments</p>
                            <table class="w-full min-w-[420px] text-xs">
                                <thead>
                                    <tr class="text-left text-slate-500">
                                        <th class="px-2 py-1">Type</th>
                                        <th class="px-2 py-1">Date</th>
                                        <th class="px-2 py-1">Time</th>
                                        <th class="px-2 py-1">Note</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($processAppointments as $item): ?>
                                    <?php
                                    $typeLabel = (($item['appointment_type'] ?? '') === 'payment')
                                        ? 'Payment Appointment'
                                        : 'Follow-up Documents Appointment';
                                    ?>
                                    <tr class="border-t border-emerald-50">
                                        <td class="px-2 py-1 font-bold text-slate-700"><?= esc($typeLabel) ?></td>
                                        <td class="px-2 py-1 text-slate-600"><?= esc((string) ($item['appointment_date'] ?? '-')) ?></td>
                                        <td class="px-2 py-1 text-slate-700">
                                            <span class="inline-flex rounded-full bg-sky-100 px-2 py-0.5 font-black text-sky-700">
                                                <?= esc(date('g:i A', strtotime((string) ($item['appointment_time'] ?? '')))) ?>
                                            </span>
                                        </td>
                                        <td class="px-2 py-1 text-slate-600"><?= esc((string) ($item['note'] ?? '-')) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($processAppointments)): ?>
                                    <tr><td colspan="4" class="px-2 py-2 text-center text-slate-500">No selected process appointments yet.</td></tr>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <section class="bubble-card p-6 admin-action-panel" data-admin-action-panel="payment" <?= $recommendedTab === 'payment' ? '' : 'hidden' ?>>
                        <h2 class="text-sm font-black uppercase tracking-[0.08em] text-slate-500">Payment Status</h2>
                        <div class="mt-4 space-y-2 text-sm text-slate-700">
                            <p><span class="font-bold">Current Payment:</span> <span class="status-pill <?= esc($paymentColor) ?>"><?= esc($paymentStatus) ?></span></p>
                            <p><span class="font-bold">Method:</span> <?= esc($paymentMethodDisplay) ?></p>
                            <p><span class="font-bold">Amount:</span> <?= ($request['amount'] !== null && $request['amount'] !== '') ? 'PHP ' . esc(number_format((float) $request['amount'], 2)) : '-' ?></p>
                            <?php if ($cashPaymentDeadline !== ''): ?>
                                <p><span class="font-bold">Cash Auto-cancel Date:</span> <?= esc(substr($cashPaymentDeadline, 0, 10)) ?></p>
                            <?php endif; ?>
                        </div>

                        <form method="post" action="<?= url('admin/manage-requests/payment-status') ?>" class="mt-4 space-y-3" data-undo-submit="1" data-undo-message="Payment status update will be submitted in 10 seconds.">
                            <?php csrf_field(); ?>
                            <input type="hidden" name="request_id" value="<?= esc((string) $request['request_id']) ?>">
                            <select name="payment_status" class="w-full rounded-lg border border-emerald-100 px-3 py-2 text-sm" <?= $isPaymentLocked ? 'disabled' : '' ?>>
                                <option value="Pending" <?= $paymentStatus === 'Pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="Paid" <?= $paymentStatus === 'Paid' ? 'selected' : '' ?>>Paid</option>
                                <option value="Unpaid" <?= $paymentStatus === 'Unpaid' ? 'selected' : '' ?>>Unpaid</option>
                                <option value="Failed" <?= $paymentStatus === 'Failed' ? 'selected' : '' ?>>Failed</option>
                                <option value="Cancelled" <?= $paymentStatus === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                            </select>
                            <button class="w-full rounded-lg bg-emerald-500 px-4 py-2 text-sm font-bold text-white hover:bg-emerald-600 disabled:cursor-not-allowed disabled:bg-slate-300" <?= $isPaymentLocked ? 'disabled' : '' ?>>Update Payment Status</button>
                            <?php if ($isPaymentLocked): ?>
                                <p class="text-xs font-semibold text-slate-500">Payment status is locked because it is already marked as Paid.</p>
                            <?php else: ?>
                                <p class="text-xs text-slate-500">If payment becomes Unpaid or Cancelled, go to Appointment tab and send a new payment date with note so student can reschedule.</p>
                            <?php endif; ?>
                        </form>
                    </section>

                    <section class="bubble-card p-6 admin-action-panel" data-admin-action-panel="status" <?= $recommendedTab === 'status' ? '' : 'hidden' ?>>
                        <h2 class="text-sm font-black uppercase tracking-[0.08em] text-slate-500">Status Action</h2>
                        <form method="post" action="<?= url('admin/manage-requests/status') ?>" class="mt-4 space-y-3" data-undo-submit="1" data-undo-message="Status action will be submitted in 10 seconds.">
                            <?php csrf_field(); ?>
                            <input type="hidden" name="request_id" value="<?= esc((string) $request['request_id']) ?>">
                            <div class="flex gap-2">
                                <select name="status" class="w-full rounded-lg border border-emerald-100 px-3 py-2 text-sm">
                                    <option value="<?= esc($request['status']) ?>">Keep current status (<?= esc($request['status']) ?>) and send instruction only</option>
                                    <?php foreach ($nextOptions as $opt): ?>
                                        <option value="<?= esc($opt) ?>"><?= esc($opt) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button class="rounded-lg bg-emerald-500 px-4 py-2 text-sm font-bold text-white">Save</button>
                            </div>
                            <input type="text" name="required_files" placeholder="Required file(s), e.g. Valid ID, Receipt" class="w-full rounded-lg border border-emerald-100 px-3 py-2 text-sm">
                            <textarea name="admin_message" rows="3" placeholder="Admin instructions (payment steps, follow-up documents, or correction note)." class="w-full rounded-lg border border-emerald-100 px-3 py-2 text-sm"></textarea>
                            <p class="text-xs text-slate-500">Tip: Keep current status if you only need to send instructions. Use Cancelled when request was approved by mistake.</p>
                        </form>
                    </section>

                    <section class="bubble-card p-6 admin-action-panel" data-admin-action-panel="pickup" <?= $recommendedTab === 'pickup' ? '' : 'hidden' ?>>
                        <h2 class="text-sm font-black uppercase tracking-[0.08em] text-slate-500">Pickup Schedule</h2>
                        <form method="post" action="<?= url('admin/manage-requests/pickup-schedule') ?>" class="mt-4 space-y-3" data-undo-submit="1" data-undo-message="Pickup schedule update will be submitted in 10 seconds.">
                            <?php csrf_field(); ?>
                            <input type="hidden" name="request_id" value="<?= esc((string) $request['request_id']) ?>">
                            <div class="grid gap-2 sm:grid-cols-2">
                                <input type="date" name="pickup_date" value="<?= esc($pickupDateValue) ?>" class="w-full rounded-lg border border-emerald-100 px-3 py-2 text-sm" <?= $canEditPickup ? '' : 'disabled' ?> data-pickup-date-input>
                                <select name="pickup_time" class="w-full rounded-lg border border-emerald-100 px-3 py-2 text-sm" <?= $canEditPickup ? '' : 'disabled' ?> data-pickup-time-select>
                                    <option value="">Choose a 15-minute slot</option>
                                    <?php foreach ($appointmentTimeSlots as $timeSlot): ?>
                                        <option value="<?= esc($timeSlot) ?>" <?= $pickupTimeValue === $timeSlot ? 'selected' : '' ?> <?= isset($pickupTakenTimes[$timeSlot]) ? 'disabled' : '' ?>>
                                            <?= esc(date('g:i A', strtotime($timeSlot))) ?><?= isset($pickupTakenTimes[$timeSlot]) ? ' - Booked' : '' ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="rounded-xl border border-sky-100 bg-sky-50 p-3 text-xs text-slate-700" data-pickup-booked-summary>
                                <?php if ($pickupDateValue !== '' && !empty($pickupBookedDetails)): ?>
                                    <p class="font-black uppercase tracking-wide text-sky-700">Booked on <?= esc($pickupDateValue) ?></p>
                                    <div class="mt-2 space-y-2">
                                        <?php foreach ($pickupBookedDetails as $timeValue => $bookings): ?>
                                            <?php foreach ($bookings as $booking): ?>
                                                <div class="rounded-lg border border-sky-100 bg-white px-3 py-2">
                                                    <div class="font-black text-sky-700"><?= esc(date('g:i A', strtotime((string) $timeValue))) ?></div>
                                                    <div class="mt-1 text-slate-700">
                                                        Request #<?= esc((string) ($booking['request_id'] ?? '')) ?> • <?= esc((string) ($booking['student_id'] ?? '')) ?>
                                                    </div>
                                                    <div class="text-slate-500"><?= esc((string) ($booking['document_name'] ?? '-')) ?> • <?= esc((string) ($booking['schedule_label'] ?? 'Appointment')) ?></div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endforeach; ?>
                                    </div>
                                <?php elseif ($pickupDateValue !== ''): ?>
                                    <p class="font-black uppercase tracking-wide text-slate-500">No bookings found on this date.</p>
                                <?php else: ?>
                                    <p class="font-black uppercase tracking-wide text-slate-500">Pick a date to see booked slots immediately.</p>
                                <?php endif; ?>
                            </div>
                            <button class="w-full rounded-lg bg-sky-500 px-4 py-2 text-sm font-bold text-white disabled:cursor-not-allowed disabled:bg-slate-300" <?= $canEditPickup ? '' : 'disabled' ?>>Save Pickup Schedule</button>
                            <?php if (!$canEditPickup): ?>
                                <p class="text-xs text-slate-500">Editable only when status is Processing, Approved, or Ready for Pickup.</p>
                            <?php else: ?>
                                <p class="text-xs text-slate-500">Pickup times use 15-minute slots only. Booked times are disabled as soon as you choose a date.</p>
                            <?php endif; ?>
                        </form>
                    </section>
                </div>
            </section>
        </main>
    </div>
</div>

<script>
(function () {
    const tabButtons = Array.from(document.querySelectorAll('[data-admin-action-tab]'));
    const tabPanels = Array.from(document.querySelectorAll('[data-admin-action-panel]'));
    if (tabButtons.length === 0 || tabPanels.length === 0) {
        return;
    }

    function setActiveTab(tabName) {
        tabPanels.forEach(function (panel) {
            panel.hidden = panel.getAttribute('data-admin-action-panel') !== tabName;
        });

        tabButtons.forEach(function (btn) {
            const isActive = btn.getAttribute('data-admin-action-tab') === tabName;
            btn.classList.toggle('ring-2', isActive);
            btn.classList.toggle('ring-offset-1', isActive);
            btn.classList.toggle('ring-slate-400', isActive);
        });
    }

    tabButtons.forEach(function (btn) {
        btn.addEventListener('click', function () {
            setActiveTab(btn.getAttribute('data-admin-action-tab'));
        });
    });

    setActiveTab('<?= esc($recommendedTab) ?>');
})();

(function () {
    const dateInput = document.querySelector('[data-pickup-date-input]');
    const timeSelect = document.querySelector('[data-pickup-time-select]');
    if (!dateInput || !timeSelect) {
        return;
    }

    const timeSlots = <?= json_encode(array_values($appointmentTimeSlots), JSON_UNESCAPED_SLASHES) ?>;
    const occupiedTimesByDate = <?= json_encode($occupiedAppointmentTimesByDate, JSON_UNESCAPED_SLASHES) ?>;
    const bookedDetailsByDate = <?= json_encode($bookedAppointmentDetailsByDate, JSON_UNESCAPED_SLASHES) ?>;
    const currentTime = <?= json_encode($pickupTimeValue, JSON_UNESCAPED_SLASHES) ?>;
    const summaryBox = document.querySelector('[data-pickup-booked-summary]');

    function formatTimeLabel(timeValue) {
        const parts = timeValue.split(':');
        const hours = parseInt(parts[0], 10);
        const minutes = parseInt(parts[1], 10);
        const suffix = hours >= 12 ? 'PM' : 'AM';
        const displayHour = hours % 12 || 12;
        return displayHour + ':' + String(minutes).padStart(2, '0') + ' ' + suffix;
    }

    function renderTimeOptions(dateValue) {
        const bookedTimes = Object.keys(occupiedTimesByDate[dateValue] || {});
        timeSelect.innerHTML = '';

        const placeholder = document.createElement('option');
        placeholder.value = '';
        placeholder.textContent = dateValue ? 'Choose a 15-minute slot' : 'Pick a date first';
        timeSelect.appendChild(placeholder);

        timeSlots.forEach(function (timeValue) {
            const option = document.createElement('option');
            option.value = timeValue;
            option.textContent = formatTimeLabel(timeValue) + (bookedTimes.includes(timeValue) ? ' - Booked' : '');
            option.disabled = bookedTimes.includes(timeValue);
            if (timeValue === currentTime) {
                option.selected = true;
            }
            timeSelect.appendChild(option);
        });

        if (!dateValue) {
            timeSelect.value = '';
            timeSelect.disabled = true;
            return;
        }

        timeSelect.disabled = false;
        if (bookedTimes.includes(timeSelect.value)) {
            timeSelect.value = '';
        }

        if (summaryBox) {
            const dateBookings = bookedDetailsByDate[dateValue] || {};
            const bookingTimes = Object.keys(dateBookings);
            if (!dateValue) {
                summaryBox.innerHTML = '<p class="font-black uppercase tracking-wide text-slate-500">Pick a date to see booked slots immediately.</p>';
            } else if (bookingTimes.length === 0) {
                summaryBox.innerHTML = '<p class="font-black uppercase tracking-wide text-slate-500">No bookings found on this date.</p>';
            } else {
                let html = '<p class="font-black uppercase tracking-wide text-sky-700">Booked on ' + dateValue + '</p><div class="mt-2 space-y-2">';
                bookingTimes.forEach(function (timeValue) {
                    dateBookings[timeValue].forEach(function (booking) {
                        html += '<div class="rounded-lg border border-sky-100 bg-white px-3 py-2">';
                        html += '<div class="font-black text-sky-700">' + formatTimeLabel(timeValue) + '</div>';
                        html += '<div class="mt-1 text-slate-700">Request #' + (booking.request_id || '') + ' • ' + (booking.student_id || '') + '</div>';
                        html += '<div class="text-slate-500">' + (booking.document_name || '-') + ' • ' + (booking.schedule_label || 'Appointment') + '</div>';
                        html += '</div>';
                    });
                });
                html += '</div>';
                summaryBox.innerHTML = html;
            }
        }
    }

    dateInput.addEventListener('change', function () {
        renderTimeOptions(dateInput.value);
    });

    renderTimeOptions(dateInput.value);
})();

(function () {
    const forms = Array.from(document.querySelectorAll('form[data-undo-submit="1"]'));
    if (forms.length === 0) {
        return;
    }

    let pending = null;

    function removeToast() {
        const existing = document.getElementById('requestDetailUndoToast');
        if (existing) {
            existing.remove();
        }
    }

    function showUndoToast(message, timeoutMs, onUndo) {
        removeToast();

        const toast = document.createElement('div');
        toast.id = 'requestDetailUndoToast';
        toast.className = 'fixed bottom-5 right-5 z-[100] flex items-center gap-3 rounded-lg border border-slate-300 bg-white px-4 py-3 shadow-xl';
        toast.innerHTML = '' +
            '<p class="text-xs font-semibold text-slate-700">' + message + '</p>' +
            '<button type="button" class="undo-btn rounded bg-slate-800 px-3 py-1 text-xs font-bold text-white hover:bg-slate-700">Undo</button>' +
            '<span class="undo-timer text-[11px] font-bold text-slate-500">' + Math.ceil(timeoutMs / 1000) + 's</span>';
        document.body.appendChild(toast);

        const undoBtn = toast.querySelector('.undo-btn');
        const timerEl = toast.querySelector('.undo-timer');
        const started = Date.now();
        const intervalId = window.setInterval(function () {
            const remainingMs = Math.max(0, timeoutMs - (Date.now() - started));
            if (timerEl) {
                timerEl.textContent = Math.ceil(remainingMs / 1000) + 's';
            }
            if (remainingMs <= 0) {
                window.clearInterval(intervalId);
            }
        }, 250);

        undoBtn?.addEventListener('click', function () {
            window.clearInterval(intervalId);
            onUndo();
            removeToast();
        });
    }

    forms.forEach(function (form) {
        form.addEventListener('submit', function (e) {
            if (form.dataset.undoConfirmed === '1') {
                return;
            }

            e.preventDefault();

            if (pending) {
                alert('Please finish or undo the pending action first.');
                return;
            }

            const timeoutMs = 10000;
            const message = form.dataset.undoMessage || 'Action will be submitted in 10 seconds.';
            const timerId = window.setTimeout(function () {
                form.dataset.undoConfirmed = '1';
                removeToast();
                pending = null;
                form.submit();
            }, timeoutMs);

            pending = {
                cancel: function () {
                    window.clearTimeout(timerId);
                    pending = null;
                }
            };

            showUndoToast(message, timeoutMs, function () {
                if (pending) {
                    pending.cancel();
                }
            });
        });
    });
})();
</script>

<?php include APP_ROOT . '/app/views/partials/foot.php'; ?>
