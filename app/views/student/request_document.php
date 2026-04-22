<?php $title = 'Request Document - MinSU e-Registrar'; include APP_ROOT . '/app/views/partials/head.php'; ?>

<div class="mx-auto w-full max-w-7xl px-5 py-6 lg:px-8">
    <div class="mb-6"><?php include APP_ROOT . '/app/views/partials/flash.php'; ?></div>
    <?php include APP_ROOT . '/app/views/partials/student_topbar.php'; ?>
    <div class="flex flex-col gap-6 lg:flex-row">
        <?php include APP_ROOT . '/app/views/partials/student_sidebar.php'; ?>

        <main class="flex-1 fade-in">
            <section class="bubble-card p-6">
                <h1 class="text-2xl font-extrabold text-slate-800">Request Registrar Document</h1>
                <p class="mt-1 text-sm text-slate-600">Submit your request details. Pickup schedule and payment confirmation will be set by the registrar/admin.</p>

                <form method="post" action="<?= url('student/request-document') ?>" enctype="multipart/form-data" class="mt-6 grid gap-4 md:grid-cols-2">
                    <?php csrf_field(); ?>
                    <div>
                        <label class="mb-1 block text-sm font-bold text-slate-700">Document Type</label>
                        <select name="request_type_id" required class="w-full rounded-xl border border-emerald-100 bg-white px-4 py-3">
                            <option value="">Select document</option>
                            <?php foreach ($requestTypes as $type): ?>
                                <option value="<?= esc((string) $type['id']) ?>">
                                    <?= esc($type['document_name']) ?> - PHP <?= esc(number_format((float) ($type['amount'] ?? 0), 2)) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-bold text-slate-700">Quantity</label>
                        <input type="number" name="quantity" min="1" max="10" value="1" required class="w-full rounded-xl border border-emerald-100 bg-white px-4 py-3">
                        <p class="mt-1 text-xs text-slate-500">How many copies of this document do you need? (1-10)</p>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-bold text-slate-700">Requirement File (Optional)</label>
                        <input type="file" name="requirement_file" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" class="w-full rounded-xl border border-emerald-100 bg-white px-4 py-3">
                        <p class="mt-1 text-xs text-slate-500">Upload supporting file for document requirements.</p>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-bold text-slate-700">Payment Method</label>
                        <select name="payment_method" id="paymentMethod" required class="w-full rounded-xl border border-emerald-100 bg-white px-4 py-3">
                            <option value="">Select payment method</option>
                            <option value="Cash">Cash</option>
                            <option value="GCash">GCash</option>
                        </select>
                        <p class="mt-1 text-xs text-slate-500">Choose how you plan to pay for this request.</p>
                    </div>

                    <div id="gcashInfoBox" class="hidden md:col-span-2 rounded-xl border-2 border-blue-200 bg-blue-50 p-4">
                        <p class="text-sm font-bold text-blue-900">💳 GCash Payment Details:</p>
                        <p class="mt-3 text-sm text-blue-800"><strong>Account Name:</strong> Registrar Office</p>
                        <p class="mt-1 text-sm text-blue-800"><strong>GCash Number:</strong> <span class="font-mono font-bold text-lg">09350454682</span></p>
                        <p class="mt-3 text-sm font-semibold text-blue-900">📝 Steps:</p>
                        <ol class="mt-2 ml-4 list-decimal space-y-1 text-sm text-blue-800">
                            <li>Send your payment to <span class="font-mono font-bold">09350454682</span></li>
                            <li>Copy your transaction reference number</li>
                            <li>Paste it in the Reference Number field below</li>
                        </ol>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-bold text-slate-700">Reference Number (for GCash)</label>
                        <input type="text" name="reference_number" id="referenceNumber" placeholder="e.g., 123456789012" class="w-full rounded-xl border border-emerald-100 bg-white px-4 py-3">
                        <p class="mt-1 text-xs text-slate-500">Required only if payment method is GCash.</p>
                    </div>

                    <div class="md:col-span-2">
                        <label class="mb-1 block text-sm font-bold text-slate-700">Purpose</label>
                        <textarea name="purpose" rows="4" required class="w-full rounded-xl border border-emerald-100 bg-white px-4 py-3" placeholder="State your reason for requesting this document..."></textarea>
                    </div>

                    <div class="md:col-span-2">
                        <button class="rounded-xl bg-emerald-500 px-6 py-3 text-sm font-bold text-white transition hover:bg-emerald-600">Submit Request</button>
                    </div>
                </form>
            </section>
        </main>
    </div>
</div>

<script>
(function () {
    const paymentMethod = document.getElementById('paymentMethod');
    const referenceNumber = document.getElementById('referenceNumber');

    if (paymentMethod && referenceNumber) {
        const gcashInfoBox = document.getElementById('gcashInfoBox');
        function syncReferenceRequirement() {
            const gcashSelected = paymentMethod.value === 'GCash';
            referenceNumber.required = gcashSelected;
            if (gcashInfoBox) {
                gcashInfoBox.classList.toggle('hidden', !gcashSelected);
            }
            if (!gcashSelected) {
                referenceNumber.value = '';
            }
        }

        paymentMethod.addEventListener('change', syncReferenceRequirement);
        syncReferenceRequirement();
    }
})();
</script>

<?php include APP_ROOT . '/app/views/partials/foot.php'; ?>

