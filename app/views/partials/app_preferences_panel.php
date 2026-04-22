<section class="bubble-card p-6">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h2 class="text-lg font-extrabold text-slate-800">Web App Preferences</h2>
            <p class="mt-1 text-sm text-slate-600">Accessibility and interface settings for this browser session profile.</p>
        </div>
        <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold uppercase tracking-wide text-emerald-700"><?= esc($settingsRoleLabel ?? 'User') ?></span>
    </div>

    <form class="mt-5 space-y-5" data-app-settings-form="1" novalidate>
        <div class="grid gap-4 md:grid-cols-2">
            <label class="soft-panel block p-4">
                <span class="text-xs font-bold uppercase tracking-wide text-slate-500">Text Size</span>
                <select name="text_scale" class="mt-2 w-full rounded-lg border border-emerald-100 bg-white px-3 py-2 text-sm text-slate-700">
                    <option value="normal">Normal</option>
                    <option value="large">Large</option>
                    <option value="xlarge">Extra Large</option>
                </select>
            </label>

            <label class="soft-panel block p-4">
                <span class="text-xs font-bold uppercase tracking-wide text-slate-500">Theme</span>
                <select name="theme" class="mt-2 w-full rounded-lg border border-emerald-100 bg-white px-3 py-2 text-sm text-slate-700">
                    <option value="system">System Default</option>
                    <option value="light">Light</option>
                    <option value="dark">Dark</option>
                </select>
            </label>

            <label class="soft-panel block p-4">
                <span class="text-xs font-bold uppercase tracking-wide text-slate-500">Contrast</span>
                <select name="contrast" class="mt-2 w-full rounded-lg border border-emerald-100 bg-white px-3 py-2 text-sm text-slate-700">
                    <option value="normal">Normal</option>
                    <option value="high">High Contrast</option>
                </select>
            </label>

            <label class="soft-panel block p-4">
                <span class="text-xs font-bold uppercase tracking-wide text-slate-500">Motion</span>
                <select name="motion" class="mt-2 w-full rounded-lg border border-emerald-100 bg-white px-3 py-2 text-sm text-slate-700">
                    <option value="normal">Standard Motion</option>
                    <option value="reduced">Reduce Motion</option>
                </select>
            </label>

            <label class="soft-panel block p-4">
                <span class="text-xs font-bold uppercase tracking-wide text-slate-500">Reading Font</span>
                <select name="font" class="mt-2 w-full rounded-lg border border-emerald-100 bg-white px-3 py-2 text-sm text-slate-700">
                    <option value="default">Default Font</option>
                    <option value="readable">Readable Font</option>
                </select>
            </label>

            <label class="soft-panel block p-4 md:col-span-2">
                <span class="text-xs font-bold uppercase tracking-wide text-slate-500">Keyboard Focus</span>
                <select name="focus" class="mt-2 w-full rounded-lg border border-emerald-100 bg-white px-3 py-2 text-sm text-slate-700 md:max-w-md">
                    <option value="normal">Normal Focus Ring</option>
                    <option value="strong">Strong Focus Highlight</option>
                </select>
            </label>
        </div>

        <div class="soft-panel p-4">
            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Live Preview</p>
            <div class="mt-2 rounded-lg border border-emerald-100 bg-white p-3">
                <p class="font-bold text-slate-800">Preview sentence for readability and contrast.</p>
                <p class="text-sm text-slate-600">Use these settings for easier reading, lower visual motion, and stronger keyboard navigation cues.</p>
                <button type="button" class="mt-3 rounded-lg bg-emerald-500 px-3 py-2 text-xs font-bold text-white">Sample Button</button>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <button type="submit" class="rounded-xl bg-emerald-500 px-5 py-2.5 text-sm font-bold text-white hover:bg-emerald-600">Save Preferences</button>
            <button type="button" data-app-settings-reset="1" class="rounded-xl bg-white px-5 py-2.5 text-sm font-bold text-slate-700 ring-1 ring-slate-200 hover:bg-slate-50">Reset to Default</button>
            <p data-app-settings-feedback="1" class="text-xs font-bold text-emerald-700"></p>
        </div>

        <p class="text-xs text-slate-500">Preferences are stored in this browser using local storage.</p>
    </form>

    <script>
        // Instant theme switching (force apply)
        document.addEventListener('DOMContentLoaded', function() {
            var form = document.querySelector('[data-app-settings-form]');
            if (!form) return;
            form.addEventListener('change', function(e) {
                if (e.target && e.target.name === 'theme') {
                    var prefs = window.MinSUAppPrefs.read();
                    prefs.theme = e.target.value;
                    window.MinSUAppPrefs.save(prefs);
                    window.MinSUAppPrefs.apply(window.MinSUAppPrefs.read()); // Force apply immediately
                }
            });
            // Set initial value
            var themeSelect = form.querySelector('select[name="theme"]');
            if (themeSelect) {
                themeSelect.value = window.MinSUAppPrefs.read().theme || 'system';
            }
        });
    </script>
</section>
