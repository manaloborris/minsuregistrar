<?php if ($msg = get_flash('success')): ?>
    <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-700">
        <?= esc($msg) ?>
    </div>
<?php endif; ?>

<?php if ($msg = get_flash('error')): ?>
    <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-bold text-red-700">
        <?= esc($msg) ?>
    </div>
<?php endif; ?>
