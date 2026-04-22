<?php
$title = 'Track Requests - MinSU e-Registrar';
include APP_ROOT . '/app/views/partials/head.php';

$statusColors = [
    'Pending' => 'bg-amber-100 text-amber-700',
    'Processing' => 'bg-blue-100 text-blue-700',
    'Approved' => 'bg-emerald-100 text-emerald-700',
    'Ready' => 'bg-lime-100 text-lime-700',
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
];
?>

<div class="mx-auto w-full max-w-7xl px-5 py-6 lg:px-8">
    <div class="mb-6"><?php include APP_ROOT . '/app/views/partials/flash.php'; ?></div>
    <?php include APP_ROOT . '/app/views/partials/student_topbar.php'; ?>
    <div class="flex flex-col gap-6 lg:flex-row">
        <?php include APP_ROOT . '/app/views/partials/student_sidebar.php'; ?>

        <main class="min-w-0 flex-1 fade-in">
            <section class="bubble-card p-6">
                <h1 class="text-2xl font-extrabold text-slate-800">Track Requests</h1>
                <p class="mt-1 text-sm text-slate-600">Monitor your request status. Pickup date/time and payment status are updated by the registrar/admin.</p>

                <div class="mt-5 overflow-x-auto">
                    <table class="w-full min-w-[1360px] text-sm">
                        <thead>
                            <tr class="text-left text-slate-500">
                                <th class="px-3 py-2">Document Type</th>
                                <th class="px-3 py-2">Quantity</th>
                                <th class="px-3 py-2">Request Date</th>
                                <th class="px-3 py-2">Status</th>
                                <th class="px-3 py-2">Final Pickup</th>
                                <th class="px-3 py-2">Payment</th>
                                <th class="px-3 py-2">Amount</th>
                                <th class="px-3 py-2">Method</th>
                                <th class="px-3 py-2">Reference #</th>
                                <th class="px-3 py-2">Follow-up</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($requests as $row): ?>
                            <?php $color = $statusColors[$row['status']] ?? 'bg-slate-100 text-slate-700'; ?>
                            <?php
                            $paymentStatus = trim((string) ($row['payment_status_display'] ?? $row['payment_status'] ?? ''));
                            if ($paymentStatus === '') {
                                $paymentStatus = 'No Record';
                            }
                            $paymentColor = $paymentColors[$paymentStatus] ?? 'bg-slate-100 text-slate-700';
                            ?>
                            <tr class="border-t border-emerald-50">
                                <td class="px-3 py-2 text-slate-700"><?= esc($row['document_name']) ?></td>
                                <td class="px-3 py-2 text-slate-700"><?= esc((string) ($row['quantity'] ?? 1)) ?> copy/ies</td>
                                <td class="px-3 py-2 text-slate-600"><?= esc($row['request_date']) ?></td>
                                <td class="px-3 py-2"><span class="status-pill <?= esc($color) ?>"><?= esc($row['status']) ?></span></td>
                                <td class="px-3 py-2 text-xs text-slate-700">
                                    <?php if (!empty($row['appointment_date']) && !empty($row['appointment_time'])): ?>
                                        <div class="font-bold text-slate-800"><?= esc($row['appointment_date']) ?></div>
                                        <span class="mt-1 inline-flex rounded-full bg-sky-100 px-2 py-0.5 font-black text-sky-700">
                                            <?= esc(date('g:i A', strtotime((string) $row['appointment_time']))) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-slate-400">Waiting for admin schedule</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-3 py-2"><span class="status-pill <?= esc($paymentColor) ?>"><?= esc($paymentStatus) ?></span></td>
                                <td class="px-3 py-2 text-slate-700">
                                    <?= ($row['amount'] !== null && $row['amount'] !== '') ? 'PHP ' . esc(number_format((float) $row['amount'], 2)) : '-' ?>
                                </td>
                                <td class="px-3 py-2 text-slate-700"><?= esc(($row['payment_method_display'] ?? $row['payment_method']) ?: '-') ?></td>
                                <td class="px-3 py-2 text-slate-700"><?= esc($row['reference_number'] ?: '-') ?></td>
                                <td class="px-3 py-2">
                                    <?php if ($row['status'] !== 'Completed'): ?>
                                        <form method="post" action="<?= url('student/request-followup/' . $row['request_id']) ?>" enctype="multipart/form-data" class="space-y-2">
                                            <?php csrf_field(); ?>
                                            <input type="file" name="followup_file" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" class="w-full rounded-lg border border-emerald-100 bg-white px-2 py-1 text-xs">
                                            <textarea name="followup_message" rows="2" placeholder="Follow-up message to registrar..." class="w-full rounded-lg border border-emerald-100 bg-white px-2 py-1 text-xs"></textarea>
                                            <button class="rounded-lg bg-emerald-500 px-3 py-1 text-xs font-bold text-white">Send Follow-up</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-xs font-bold text-slate-400">Request completed</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($requests)): ?>
                            <tr><td colspan="10" class="px-3 py-4 text-center text-slate-500">No requests submitted yet.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>
</div>

<?php include APP_ROOT . '/app/views/partials/foot.php'; ?>

