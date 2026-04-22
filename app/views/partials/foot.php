<footer class="mt-12 bg-white/70 backdrop-blur">
	<div class="mx-auto w-full max-w-7xl px-5 py-10 lg:px-8">
		<div class="mx-auto w-full max-w-xl border-t border-emerald-100/80 pt-4 text-center text-xs text-slate-500">
			&copy; <?= date('Y') ?> MinSU e-Registrar. All rights reserved.
		</div>
	</div>
</footer>
<?php
$sidebarBootstrapFlags = [
	'studentForceClosed' => !empty($_SESSION['ui']['student_sidebar_force_closed']),
	'adminForceClosed' => !empty($_SESSION['ui']['admin_sidebar_force_closed']),
	'adminForceOpen' => !empty($_SESSION['ui']['admin_sidebar_force_open']),
];
unset(
	$_SESSION['ui']['student_sidebar_force_closed'],
	$_SESSION['ui']['admin_sidebar_force_closed'],
	$_SESSION['ui']['admin_sidebar_force_open']
);
?>
<script>
(function () {
	const desktopBreakpoint = 1280;
	const sidebarBootstrap = <?= json_encode($sidebarBootstrapFlags) ?>;
	const sessionStorageKeys = {
		studentSidebar: 'studentSidebarInitialized',
		adminSidebar: 'adminSidebarInitialized',
	};
	const mobileCloseIds = {
		studentSidebar: 'studentSidebarMobileClose',
		adminSidebar: 'adminSidebarMobileClose',
	};

	function isDesktop() {
		return window.innerWidth >= desktopBreakpoint;
	}

	function getStorageKey(sidebarId) {
		return sidebarId + 'Collapsed';
	}

	function applyDesktopCollapse(sidebarId) {
		const sidebar = document.getElementById(sidebarId);
		if (!sidebar) return;

		const bootstrapForceClosed = sidebarId === 'studentSidebar'
			? !!sidebarBootstrap.studentForceClosed
			: sidebarId === 'adminSidebar'
				? !!sidebarBootstrap.adminForceClosed
				: false;
		const bootstrapForceOpen = sidebarId === 'adminSidebar' && !!sidebarBootstrap.adminForceOpen;

		if (bootstrapForceOpen && sessionStorage.getItem(sessionStorageKeys[sidebarId]) !== '1') {
			localStorage.setItem(getStorageKey(sidebarId), '0');
			sessionStorage.setItem(sessionStorageKeys[sidebarId], '1');
			sidebar.classList.remove('is-collapsed');
			return;
		}

		if (bootstrapForceClosed && sessionStorage.getItem(sessionStorageKeys[sidebarId]) !== '1') {
			localStorage.setItem(getStorageKey(sidebarId), '1');
			sessionStorage.setItem(sessionStorageKeys[sidebarId], '1');
			sidebar.classList.add('is-collapsed');
			return;
		}

		// Keep desktop sidebars consistently full width across all tabs/pages.
		const shouldCollapse = localStorage.getItem(getStorageKey(sidebarId)) === '1';
		sidebar.classList.toggle('is-collapsed', shouldCollapse);
		sessionStorage.setItem(sessionStorageKeys[sidebarId], '1');
	}

	function toggleDesktopCollapse(sidebarId) {
		const sidebar = document.getElementById(sidebarId);
		if (!sidebar) return;
		const isCollapsed = sidebar.classList.toggle('is-collapsed');
		localStorage.setItem(getStorageKey(sidebarId), isCollapsed ? '1' : '0');
	}

	function setMobileCloseVisible(sidebarId, visible) {
		const mobileClose = document.getElementById(mobileCloseIds[sidebarId]);
		if (!mobileClose) return;
		mobileClose.classList.toggle('hidden', !visible);
	}

	function hideSidebar(sidebarId, overlayId) {
		const sidebar = document.getElementById(sidebarId);
		const overlay = document.getElementById(overlayId);
		setMobileCloseVisible(sidebarId, false);
		if (sidebar) {
			sidebar.classList.add('-translate-x-full');
			sidebar.style.setProperty('transform', 'translateX(-100%)', 'important');
			sidebar.style.pointerEvents = 'none';
			sidebar.setAttribute('data-sidebar-state', 'closed');
		}
		if (overlay) overlay.classList.add('hidden');
	}

	function showSidebar(sidebarId, overlayId) {
		const sidebar = document.getElementById(sidebarId);
		const overlay = document.getElementById(overlayId);
		setMobileCloseVisible(sidebarId, !isDesktop());
		if (sidebar) {
			sidebar.classList.remove('-translate-x-full');
			sidebar.style.setProperty('transform', 'translateX(0)', 'important');
			sidebar.style.pointerEvents = 'auto';
			sidebar.setAttribute('data-sidebar-state', 'open');
		}
		if (overlay) overlay.classList.remove('hidden');
	}

	window.MinSUSidebar = {
		hide: hideSidebar,
		show: showSidebar,
		toggle: function (sidebarId, overlayId) {
			const sidebar = document.getElementById(sidebarId);
			const isOpen = sidebar && sidebar.getAttribute('data-sidebar-state') === 'open';
			if (isOpen) {
				hideSidebar(sidebarId, overlayId);
			} else {
				showSidebar(sidebarId, overlayId);
			}
		}
	};

	function resetDesktopSidebar(sidebarId, overlayId) {
		const sidebar = document.getElementById(sidebarId);
		const overlay = document.getElementById(overlayId);
		setMobileCloseVisible(sidebarId, false);
		if (sidebar) {
			sidebar.classList.remove('-translate-x-full');
			sidebar.style.removeProperty('transform');
			sidebar.style.pointerEvents = 'auto';
			sidebar.setAttribute('data-sidebar-state', 'open');
			applyDesktopCollapse(sidebarId);
		}
		if (overlay) overlay.classList.add('hidden');
	}

	document.addEventListener('click', function (event) {
		const openBtn = event.target.closest('[data-sidebar-target]');
		if (openBtn) {
			event.preventDefault();
			const sidebarId = openBtn.getAttribute('data-sidebar-target');
			const overlayId = openBtn.getAttribute('data-overlay-target');

			if (isDesktop()) {
				toggleDesktopCollapse(sidebarId);
				return;
			}

			window.MinSUSidebar.toggle(sidebarId, overlayId);
			return;
		}

		const closeBtn = event.target.closest('[data-sidebar-close]');
		if (closeBtn) {
			event.preventDefault();
			hideSidebar(closeBtn.getAttribute('data-sidebar-close'), closeBtn.getAttribute('data-overlay-target'));
			return;
		}

		const overlay = event.target.closest('[data-overlay]');
		if (overlay) {
			hideSidebar(overlay.getAttribute('data-overlay'), overlay.id);
			return;
		}

		const sidebarLink = event.target.closest('#studentSidebar nav a, #adminSidebar nav a');
		if (sidebarLink && !isDesktop()) {
			const sidebar = sidebarLink.closest('#studentSidebar, #adminSidebar');
			if (sidebar && sidebar.id === 'studentSidebar') {
				hideSidebar('studentSidebar', 'studentSidebarOverlay');
			}
			if (sidebar && sidebar.id === 'adminSidebar') {
				hideSidebar('adminSidebar', 'adminSidebarOverlay');
			}
		}
	});

	// Extra direct listeners for close controls in resized desktop-to-mobile mode.
	document.querySelectorAll('[data-sidebar-close]').forEach(function (btn) {
		btn.addEventListener('click', function (event) {
			event.preventDefault();
			event.stopPropagation();
			hideSidebar(this.getAttribute('data-sidebar-close'), this.getAttribute('data-overlay-target'));
		});
	});

	function bindHardClose(buttonId, sidebarId, overlayId) {
		const btn = document.getElementById(buttonId);
		if (!btn) return;

		['click', 'pointerup', 'touchend'].forEach(function (evtName) {
			btn.addEventListener(evtName, function (event) {
				event.preventDefault();
				event.stopPropagation();
				hideSidebar(sidebarId, overlayId);
			}, { passive: false });
		});
	}

	bindHardClose('studentSidebarCloseBtn', 'studentSidebar', 'studentSidebarOverlay');
	bindHardClose('adminSidebarCloseBtn', 'adminSidebar', 'adminSidebarOverlay');

	window.addEventListener('resize', function () {
		if (isDesktop()) {
			resetDesktopSidebar('studentSidebar', 'studentSidebarOverlay');
			resetDesktopSidebar('adminSidebar', 'adminSidebarOverlay');
		} else {
			hideSidebar('studentSidebar', 'studentSidebarOverlay');
			hideSidebar('adminSidebar', 'adminSidebarOverlay');
		}
	});

	// Ensure mobile sidebars are closed when restoring page from browser cache/history.
	window.addEventListener('pageshow', function () {
		if (!isDesktop()) {
			hideSidebar('studentSidebar', 'studentSidebarOverlay');
			hideSidebar('adminSidebar', 'adminSidebarOverlay');
		}
	});

	if (!isDesktop()) {
		hideSidebar('studentSidebar', 'studentSidebarOverlay');
		hideSidebar('adminSidebar', 'adminSidebarOverlay');
	} else {
		resetDesktopSidebar('studentSidebar', 'studentSidebarOverlay');
		resetDesktopSidebar('adminSidebar', 'adminSidebarOverlay');
	}
})();
</script>

<!-- Loading Screen Control -->
<script>
(function () {
	const loadingScreen = document.getElementById('loadingScreen');
	const minVisibleMs = 1200;
	const failSafeHideMs = 5000;
	let shownAt = Date.now();
	let hideTimer = null;
	let failSafeTimer = null;
	
	if (!loadingScreen) return;

	if (window.__skipInitialLoading) {
		loadingScreen.classList.add('hidden');
		loadingScreen.style.display = 'none';
		loadingScreen.style.visibility = 'hidden';
		loadingScreen.style.pointerEvents = 'none';
	}

	function doHideLoadingScreen() {
		loadingScreen.classList.add('hidden');
		loadingScreen.style.display = 'none';
		loadingScreen.style.visibility = 'hidden';
		loadingScreen.style.pointerEvents = 'none';
		if (failSafeTimer) {
			window.clearTimeout(failSafeTimer);
			failSafeTimer = null;
		}
	}

	// Hide loading screen when page is fully loaded
	function hideLoadingScreen() {
		if (!loadingScreen) return;
		const elapsed = Date.now() - shownAt;
		const delay = Math.max(0, minVisibleMs - elapsed);
		if (hideTimer) {
			window.clearTimeout(hideTimer);
		}
		hideTimer = window.setTimeout(doHideLoadingScreen, delay);
	}

	// Show loading screen
	function showLoadingScreen() {
		if (loadingScreen) {
			if (hideTimer) {
				window.clearTimeout(hideTimer);
				hideTimer = null;
			}
			if (failSafeTimer) {
				window.clearTimeout(failSafeTimer);
			}
			shownAt = Date.now();
			loadingScreen.style.display = '';
			loadingScreen.style.visibility = 'visible';
			loadingScreen.style.pointerEvents = 'auto';
			loadingScreen.classList.remove('hidden');
			failSafeTimer = window.setTimeout(doHideLoadingScreen, failSafeHideMs);
		}
	}

	// Expose to global scope for manual control
	window.LoadingScreen = {
		show: showLoadingScreen,
		hide: hideLoadingScreen
	};

	// Hide loading screen as soon as the DOM is ready instead of waiting on slow assets.
	if (document.readyState === 'interactive' || document.readyState === 'complete') {
		window.setTimeout(hideLoadingScreen, 0);
	} else {
		document.addEventListener('DOMContentLoaded', hideLoadingScreen, { once: true });
		window.addEventListener('load', hideLoadingScreen, { once: true });
	}

	// Show loading screen on all form submissions
	document.addEventListener('submit', function (e) {
		const form = e.target;
		// Don't show if form has data-no-loading attribute
		if (!form.hasAttribute('data-no-loading')) {
			showLoadingScreen();
		}
	}, true);

	// Link navigations already show the initial loader on the next page,
	// so we skip pre-navigation link loading to prevent double flashes.

	window.addEventListener('pageshow', function (event) {
		if (event.persisted) {
			doHideLoadingScreen();
		}
	});

	// Show loading screen on manual form data (API calls)
	const originalFetch = window.fetch;
	window.fetch = function (...args) {
		const options = args[1] || {};
		// Show loading for POST, PUT, DELETE requests
		if (['POST', 'PUT', 'DELETE'].includes((options.method || 'GET').toUpperCase())) {
			showLoadingScreen();
		}
		return originalFetch.apply(this, args).finally(function () {
			hideLoadingScreen();
		});
	};

	const debounce = (fn, wait = 250) => {
		let timer;
		return (...args) => {
			window.clearTimeout(timer);
			timer = window.setTimeout(() => fn(...args), wait);
		};
	};

	// ========== MANAGE REQUESTS PAGE FEATURES ==========
	const manageRequestsInit = () => {
		const table = document.getElementById('requestsTable');
		if (!table) return; // Only run on manage requests page

		const manageRequestStateKey = 'minsureg.manageRequests.ui.v1';
		const readManageRequestState = () => {
			try {
				return JSON.parse(localStorage.getItem(manageRequestStateKey) || '{}');
			} catch (e) {
				return {};
			}
		};
		const writeManageRequestState = (patch) => {
			const next = { ...readManageRequestState(), ...patch };
			localStorage.setItem(manageRequestStateKey, JSON.stringify(next));
		};

		const rows = Array.from(table.querySelectorAll('tbody tr[data-request-row]'));
		const filterSearch = document.getElementById('filterSearch');
		const filterStatus = document.getElementById('filterStatus');
		const filterPayment = document.getElementById('filterPayment');
		const filterAppointment = document.getElementById('filterAppointment');
		const filterReset = document.getElementById('filterReset');
		const selectAllCheckbox = document.getElementById('selectAllCheckbox');
		const bulkActionToolbar = document.getElementById('bulkActionToolbar');
		const bulkSelectedCount = document.getElementById('bulkSelectedCount');
		const bulkStatusSelect = document.getElementById('bulkStatusSelect');
		const bulkStatusApply = document.getElementById('bulkStatusApply');
		const bulkClearSelection = document.getElementById('bulkClearSelection');
		let sortState = { field: null, direction: 'asc' };
		let pendingUndoAction = null;

		const showUndoToast = (message, onUndo, timeoutMs = 10000) => {
			const existing = document.getElementById('undoActionToast');
			if (existing) existing.remove();

			const toast = document.createElement('div');
			toast.id = 'undoActionToast';
			toast.className = 'fixed bottom-5 right-5 z-[100] flex items-center gap-3 rounded-lg border border-slate-300 bg-white px-4 py-3 shadow-xl';
			toast.innerHTML = `
				<p class="text-xs font-semibold text-slate-700">${message}</p>
				<button type="button" class="undo-btn rounded bg-slate-800 px-3 py-1 text-xs font-bold text-white hover:bg-slate-700">Undo</button>
				<span class="undo-timer text-[11px] font-bold text-slate-500">${Math.ceil(timeoutMs / 1000)}s</span>
			`;
			document.body.appendChild(toast);

			const undoBtn = toast.querySelector('.undo-btn');
			const timerEl = toast.querySelector('.undo-timer');
			const started = Date.now();

			const intervalId = window.setInterval(() => {
				const remainingMs = Math.max(0, timeoutMs - (Date.now() - started));
				if (timerEl) timerEl.textContent = `${Math.ceil(remainingMs / 1000)}s`;
				if (remainingMs <= 0) window.clearInterval(intervalId);
			}, 250);

			const cleanup = () => {
				window.clearInterval(intervalId);
				toast.remove();
			};

			undoBtn?.addEventListener('click', () => {
				onUndo();
				cleanup();
			});

			return cleanup;
		};

		const scheduleUndoableCommit = ({ message, timeoutMs = 10000, commit }) => {
			if (pendingUndoAction) {
				alert('Please finish or undo the pending update first.');
				return;
			}

			let cancelled = false;
			const timerId = window.setTimeout(async () => {
				if (cancelled) return;
				try {
					await commit();
				} finally {
					pendingUndoAction = null;
				}
			}, timeoutMs);

			const cleanupToast = showUndoToast(message, () => {
				cancelled = true;
				window.clearTimeout(timerId);
				pendingUndoAction = null;
			}, timeoutMs);

			pendingUndoAction = {
				cancel: () => {
					cancelled = true;
					window.clearTimeout(timerId);
					cleanupToast();
					pendingUndoAction = null;
				},
			};
		};

		const viewAllBtn = document.getElementById('viewModeAllBtn');
		const viewByDateBtn = document.getElementById('viewModeByDateBtn');
		const viewAll = document.getElementById('viewAll');
		const viewByDate = document.getElementById('viewByDate');
		const filtersBar = document.getElementById('filtersBar');
		const byDateFiltersBar = document.getElementById('byDateFiltersBar');
		const byDateSearch = document.getElementById('byDateSearch');
		const byDateMonth = document.getElementById('byDateMonth');
		const byDateYear = document.getElementById('byDateYear');
		const byDateReset = document.getElementById('byDateReset');
		const byDateNoResults = document.getElementById('byDateNoResults');
		let currentManageViewMode = 'bydate';

		// ===== VIEW MODE SWITCHING =====
		const switchViewMode = (mode) => {
			currentManageViewMode = mode;
			writeManageRequestState({ viewMode: mode });
			if (mode === 'all') {
				viewAll?.classList.remove('hidden');
				viewByDate?.classList.add('hidden');
				viewAllBtn?.classList.add('border-b-emerald-500', 'text-emerald-700', 'border-b-2');
				viewAllBtn?.classList.remove('border-b-transparent', 'text-slate-600');
				viewByDateBtn?.classList.add('border-b-transparent', 'text-slate-600');
				viewByDateBtn?.classList.remove('border-b-emerald-500', 'text-emerald-700', 'border-b-2');
				filtersBar?.classList.remove('hidden');
				byDateFiltersBar?.classList.remove('hidden');
			} else if (mode === 'bydate') {
				viewAll?.classList.add('hidden');
				viewByDate?.classList.remove('hidden');
				viewByDateBtn?.classList.add('border-b-emerald-500', 'text-emerald-700', 'border-b-2');
				viewByDateBtn?.classList.remove('border-b-transparent', 'text-slate-600');
				viewAllBtn?.classList.add('border-b-transparent', 'text-slate-600');
				viewAllBtn?.classList.remove('border-b-emerald-500', 'text-emerald-700', 'border-b-2');
				filtersBar?.classList.add('hidden');
				byDateFiltersBar?.classList.remove('hidden');
			}
		};

		viewAllBtn?.addEventListener('click', () => switchViewMode('all'));
		viewByDateBtn?.addEventListener('click', () => switchViewMode('bydate'));

		// ===== DATE GROUP COLLAPSE/EXPAND =====
		const groupToggles = document.querySelectorAll('.date-group-toggle');
		groupToggles.forEach(toggle => {
			toggle.addEventListener('click', () => {
				const groupKey = toggle.dataset.group;
				const content = document.querySelector(`.date-group-content[data-group="${groupKey}"]`);
				const arrow = toggle.querySelector('.date-group-arrow');
				if (!content || !arrow) return;
				const isHidden = content.style.display === 'none';
				
				if (isHidden) {
					content.style.display = '';
					arrow.style.transform = 'rotate(0deg)';
				} else {
					content.style.display = 'none';
					arrow.style.transform = 'rotate(-90deg)';
				}

				const collapsed = new Set(readManageRequestState().collapsedDateGroups || []);
				if (content.style.display === 'none') {
					collapsed.add(groupKey);
				} else {
					collapsed.delete(groupKey);
				}
				writeManageRequestState({ collapsedDateGroups: Array.from(collapsed) });
			});
		});

		// ===== BY DATE FILTERING (search + month + year) =====
		const byDateGroupWrappers = Array.from(document.querySelectorAll('[data-date-group-wrapper="1"]'));
		const applyByDateFilters = () => {
			if (!byDateGroupWrappers.length) return;

			const searchVal = (byDateSearch?.value || '').toLowerCase().trim();
			const monthVal = byDateMonth?.value || '';
			const yearVal = byDateYear?.value || '';
			writeManageRequestState({
				byDateSearch: searchVal,
				byDateMonth: monthVal,
				byDateYear: yearVal,
			});
			let anyVisibleGroup = false;

			byDateGroupWrappers.forEach(group => {
				const groupMonth = group.dataset.month || '';
				const groupYear = group.dataset.year || '';
				const monthMatch = !monthVal || groupMonth === monthVal;
				const yearMatch = !yearVal || groupYear === yearVal;

				const groupRows = Array.from(group.querySelectorAll('.by-date-row'));
				let visibleRows = 0;

				groupRows.forEach(row => {
					const rowSearch = row.dataset.search || '';
					const searchMatch = !searchVal || rowSearch.includes(searchVal);
					const visible = monthMatch && yearMatch && searchMatch;
					row.style.display = visible ? '' : 'none';
					if (visible) visibleRows += 1;
				});

				const countEl = group.querySelector('.date-group-count');
				if (countEl) {
					countEl.textContent = String(visibleRows);
				}

				group.style.display = visibleRows > 0 ? '' : 'none';
				if (visibleRows > 0) {
					anyVisibleGroup = true;
				}
			});

			if (byDateNoResults) {
				byDateNoResults.classList.toggle('hidden', anyVisibleGroup);
			}
		};

		byDateSearch?.addEventListener('input', debounce(applyByDateFilters, 220));
		byDateMonth?.addEventListener('change', applyByDateFilters);
		byDateYear?.addEventListener('change', applyByDateFilters);
		byDateReset?.addEventListener('click', () => {
			if (byDateSearch) byDateSearch.value = '';
			if (byDateMonth) byDateMonth.value = '';
			if (byDateYear) byDateYear.value = '';
			applyByDateFilters();
		});

		// ===== FILTERING LOGIC =====
		const applyFilters = () => {
			const searchVal = (filterSearch?.value || '').toLowerCase();
			const statusVal = filterStatus?.value || '';
			const paymentVal = filterPayment?.value || '';
			const appointmentVal = filterAppointment?.value || '';
			writeManageRequestState({
				allSearch: searchVal,
				allStatus: statusVal,
				allPayment: paymentVal,
				allAppointment: appointmentVal,
			});

			rows.forEach(row => {
				const matches = {
					search: !searchVal || row.dataset.search.includes(searchVal),
					status: !statusVal || row.dataset.status === statusVal,
					payment: !paymentVal || row.dataset.payment === paymentVal,
					appointment: !appointmentVal || row.dataset.appointment === appointmentVal,
				};
				row.style.display = Object.values(matches).every(m => m) ? '' : 'none';
			});
			updateBulkSelectionUI();
		};

		filterSearch?.addEventListener('input', debounce(applyFilters, 220));
		filterStatus?.addEventListener('change', applyFilters);
		filterPayment?.addEventListener('change', applyFilters);
		filterAppointment?.addEventListener('change', applyFilters);
		filterReset?.addEventListener('click', () => {
			filterSearch.value = '';
			filterStatus.value = '';
			filterPayment.value = '';
			filterAppointment.value = '';
			applyFilters();
		});

		// ===== SORTING LOGIC =====
		const sortableHeaders = table.querySelectorAll('th[data-sort]');
		sortableHeaders.forEach(header => {
			header.style.cursor = 'pointer';
			header.addEventListener('click', () => {
				const field = header.dataset.sort;
				if (sortState.field === field) {
					sortState.direction = sortState.direction === 'asc' ? 'desc' : 'asc';
				} else {
					sortState.field = field;
					sortState.direction = 'asc';
				}
				applySort();
			});
		});

		const applySort = () => {
			if (!sortState.field) return;
			const visibleRows = rows.filter(row => row.style.display !== 'none');
			visibleRows.sort((a, b) => {
				const aVal = a.dataset[sortState.field] || '';
				const bVal = b.dataset[sortState.field] || '';
				const cmp = aVal.localeCompare(bVal, undefined, { numeric: true });
				return sortState.direction === 'asc' ? cmp : -cmp;
			});
			const tbody = table.querySelector('tbody');
			visibleRows.forEach(row => tbody.appendChild(row));
		};

		// ===== CHECKBOX SELECTION (with lock prevention) =====
		const checkboxes = table.querySelectorAll('input.request-checkbox');
		const updateBulkSelectionUI = () => {
			const visibleCheckboxes = Array.from(checkboxes).filter(cb => cb.closest('tr').style.display !== 'none' && !cb.disabled);
			const checkedCount = visibleCheckboxes.filter(cb => cb.checked).length;
			selectAllCheckbox.checked = checkedCount > 0 && checkedCount === visibleCheckboxes.length;
			selectAllCheckbox.indeterminate = checkedCount > 0 && checkedCount < visibleCheckboxes.length;
			bulkSelectedCount.textContent = Array.from(checkboxes).filter(cb => cb.checked).length;
			bulkActionToolbar.classList.toggle('hidden', Array.from(checkboxes).filter(cb => cb.checked).length === 0);
		};

		selectAllCheckbox?.addEventListener('change', () => {
			const visibleCheckboxes = Array.from(checkboxes).filter(cb => cb.closest('tr').style.display !== 'none' && !cb.disabled);
			visibleCheckboxes.forEach(cb => cb.checked = selectAllCheckbox.checked);
			updateBulkSelectionUI();
		});

		checkboxes.forEach(checkbox => {
			checkbox.addEventListener('change', updateBulkSelectionUI);
			// Prevent checking locked items
			checkbox.addEventListener('click', (e) => {
				const row = checkbox.closest('tr[data-request-row]');
				if (row?.dataset.isLocked === '1') {
					e.preventDefault();
					alert('This request is locked and cannot be modified.');
				}
			});
		});

		// ===== BULK STATUS CHANGE (with lock prevention) =====
		bulkStatusApply?.addEventListener('click', async () => {
			const selectedIds = Array.from(checkboxes).filter(cb => cb.checked && !cb.disabled).map(cb => cb.value);
			const newStatus = bulkStatusSelect?.value;
			if (!selectedIds.length || !newStatus) {
				alert('Please select at least one request and a status.');
				return;
			}

			// Verify none are locked
			const lockedCount = Array.from(checkboxes)
				.filter(cb => cb.checked)
				.filter(cb => cb.closest('tr[data-request-row]')?.dataset.isLocked === '1').length;
			
			if (lockedCount > 0) {
				alert(`${lockedCount} of the selected request(s) are locked and cannot be modified.`);
				return;
			}

			if (!confirm(`Change status of ${selectedIds.length} request(s) to "${newStatus}"?`)) return;

			scheduleUndoableCommit({
				message: `Bulk status update scheduled (${selectedIds.length} request(s)).`,
				timeoutMs: 10000,
				commit: async () => {
					try {
						const response = await fetch('<?= url("admin/manage-requests/bulk-status") ?>', {
							method: 'POST',
							headers: { 'Content-Type': 'application/json' },
							body: JSON.stringify({ request_ids: selectedIds, status: newStatus }),
						});
						const data = await response.json();
						if (data.success) {
							location.reload();
						} else {
							alert('Error: ' + (data.message || 'Bulk status update failed'));
						}
					} catch (e) {
						alert('Error: ' + e.message);
					}
				},
			});
		});

		bulkClearSelection?.addEventListener('click', () => {
			checkboxes.forEach(cb => cb.checked = false);
			selectAllCheckbox.checked = false;
			updateBulkSelectionUI();
		});

		// ===== ROW ACTION DROPDOWN MENUS =====
		const menuBtns = table.querySelectorAll('.row-action-menu-btn');
		menuBtns.forEach(btn => {
			btn.addEventListener('click', (e) => {
				e.stopPropagation();
				const requestId = btn.dataset.requestId;
				const dropdown = table.querySelector(`.row-action-dropdown[data-request-id="${requestId}"]`);
				// Close other dropdowns
				table.querySelectorAll('.row-action-dropdown').forEach(d => {
					if (d.dataset.requestId !== requestId) d.classList.add('hidden');
				});
				dropdown.classList.toggle('hidden');
			});
		});

		// Close dropdowns on outside click
		document.addEventListener('click', (e) => {
			if (!e.target.closest('.row-action-menu-btn') && !e.target.closest('.row-action-dropdown')) {
				table.querySelectorAll('.row-action-dropdown').forEach(d => d.classList.add('hidden'));
			}
		});

		// ===== QUICK STATUS CHANGE BUTTONS (with lock prevention) =====
		const quickStatusBtns = table.querySelectorAll('.quick-status-btn');
		quickStatusBtns.forEach(btn => {
			btn.addEventListener('click', async (e) => {
				e.preventDefault();
				const requestId = btn.dataset.requestId;
				const newStatus = btn.dataset.status;
				const row = table.querySelector(`tr[data-request-id="${requestId}"]`);
				
				// Check if locked
				if (row?.dataset.isLocked === '1') {
					const dropdown = table.querySelector(`.row-action-dropdown[data-request-id="${requestId}"]`);
					dropdown.classList.add('hidden');
					alert('This request is locked and cannot be modified. View full details to see why.');
					return;
				}

				const dropdown = table.querySelector(`.row-action-dropdown[data-request-id="${requestId}"]`);
				dropdown.classList.add('hidden');

				if (!confirm(`Change status to "${newStatus}"?`)) return;

				scheduleUndoableCommit({
					message: `Status update to "${newStatus}" scheduled for request #${requestId}.`,
					timeoutMs: 10000,
					commit: async () => {
						try {
							const response = await fetch('<?= url("admin/manage-requests/quick-status") ?>', {
								method: 'POST',
								headers: { 'Content-Type': 'application/json' },
								body: JSON.stringify({ request_id: requestId, status: newStatus }),
							});
							const data = await response.json();
							if (data.success) {
								location.reload();
							} else {
								alert('Error: ' + (data.message || 'Status update failed'));
							}
						} catch (e) {
							alert('Error: ' + e.message);
						}
					},
				});
			});
		});

		// ===== RESTORE SAVED UI STATE =====
		const savedState = readManageRequestState();
		if (filterSearch && typeof savedState.allSearch === 'string') filterSearch.value = savedState.allSearch;
		if (filterStatus && typeof savedState.allStatus === 'string') filterStatus.value = savedState.allStatus;
		if (filterPayment && typeof savedState.allPayment === 'string') filterPayment.value = savedState.allPayment;
		if (filterAppointment && typeof savedState.allAppointment === 'string') filterAppointment.value = savedState.allAppointment;
		if (byDateSearch && typeof savedState.byDateSearch === 'string') byDateSearch.value = savedState.byDateSearch;
		if (byDateMonth && typeof savedState.byDateMonth === 'string') byDateMonth.value = savedState.byDateMonth;
		if (byDateYear && typeof savedState.byDateYear === 'string') byDateYear.value = savedState.byDateYear;

		const collapsedGroups = new Set(savedState.collapsedDateGroups || []);
		groupToggles.forEach(toggle => {
			const key = toggle.dataset.group;
			if (!collapsedGroups.has(key)) return;
			const content = document.querySelector(`.date-group-content[data-group="${key}"]`);
			const arrow = toggle.querySelector('.date-group-arrow');
			if (!content || !arrow) return;
			content.style.display = 'none';
			arrow.style.transform = 'rotate(-90deg)';
		});

		applyFilters();
		applyByDateFilters();

		// Default to date-first mode for faster registrar flow.
		switchViewMode(savedState.viewMode === 'all' ? 'all' : 'bydate');

		// Keyboard shortcuts: / to focus active search, Alt+1 All, Alt+2 By Date
		document.addEventListener('keydown', (e) => {
			const target = e.target;
			const isTyping = target && (target.tagName === 'INPUT' || target.tagName === 'TEXTAREA' || target.isContentEditable);

			if (!isTyping && e.key === '/') {
				e.preventDefault();
				if (currentManageViewMode === 'all' && filterSearch) {
					filterSearch.focus();
					filterSearch.select();
					return;
				}
				if (currentManageViewMode === 'bydate' && byDateSearch) {
					byDateSearch.focus();
					byDateSearch.select();
				}
			}

			if (!isTyping && e.altKey && e.key === '1') {
				e.preventDefault();
				switchViewMode('all');
			}

			if (!isTyping && e.altKey && e.key === '2') {
				e.preventDefault();
				switchViewMode('bydate');
			}
		});
	};

	// ========== ADMIN APPOINTMENTS PAGE FEATURES ==========
	const adminAppointmentsInit = () => {
		const pendingView = document.getElementById('appointmentViewPending');
		const byDateView = document.getElementById('appointmentViewByDate');
		const pendingBtn = document.getElementById('appointmentViewPendingBtn');
		const byDateBtn = document.getElementById('appointmentViewByDateBtn');

		if (!pendingView || !byDateView || !pendingBtn || !byDateBtn) {
			return;
		}

		const appointmentsStateKey = 'minsureg.appointments.ui.v1';
		const readAppointmentsState = () => {
			try {
				return JSON.parse(localStorage.getItem(appointmentsStateKey) || '{}');
			} catch (e) {
				return {};
			}
		};
		const writeAppointmentsState = (patch) => {
			const next = { ...readAppointmentsState(), ...patch };
			localStorage.setItem(appointmentsStateKey, JSON.stringify(next));
		};

		const byDateSearch = document.getElementById('appointmentByDateSearch');
		const byDateMonth = document.getElementById('appointmentByDateMonth');
		const byDateYear = document.getElementById('appointmentByDateYear');
		const byDateReset = document.getElementById('appointmentByDateReset');
		const byDateNoResults = document.getElementById('appointmentByDateNoResults');
		const groupWrappers = Array.from(document.querySelectorAll('[data-appointment-date-group-wrapper="1"]'));
		let currentAppointmentViewMode = 'bydate';

		const switchAppointmentViewMode = (mode) => {
			currentAppointmentViewMode = mode;
			writeAppointmentsState({ viewMode: mode });
			if (mode === 'pending') {
				pendingView.classList.remove('hidden');
				byDateView.classList.add('hidden');
				pendingBtn.classList.add('border-b-emerald-500', 'text-emerald-700');
				pendingBtn.classList.remove('border-transparent', 'text-slate-600');
				byDateBtn.classList.add('border-transparent', 'text-slate-600');
				byDateBtn.classList.remove('border-b-emerald-500', 'text-emerald-700');
				return;
			}

			pendingView.classList.add('hidden');
			byDateView.classList.remove('hidden');
			byDateBtn.classList.add('border-b-emerald-500', 'text-emerald-700');
			byDateBtn.classList.remove('border-transparent', 'text-slate-600');
			pendingBtn.classList.add('border-transparent', 'text-slate-600');
			pendingBtn.classList.remove('border-b-emerald-500', 'text-emerald-700');
		};

		pendingBtn.addEventListener('click', () => switchAppointmentViewMode('pending'));
		byDateBtn.addEventListener('click', () => switchAppointmentViewMode('bydate'));

		document.querySelectorAll('.appointment-date-group-toggle').forEach(toggle => {
			toggle.addEventListener('click', () => {
				const groupKey = toggle.dataset.group;
				const content = document.querySelector(`.appointment-date-group-content[data-group="${groupKey}"]`);
				const arrow = toggle.querySelector('.appointment-date-group-arrow');
				const isHidden = content && content.style.display === 'none';

				if (!content || !arrow) return;

				if (isHidden) {
					content.style.display = '';
					arrow.style.transform = 'rotate(0deg)';
				} else {
					content.style.display = 'none';
					arrow.style.transform = 'rotate(-90deg)';
				}

				const collapsed = new Set(readAppointmentsState().collapsedDateGroups || []);
				if (content.style.display === 'none') {
					collapsed.add(groupKey);
				} else {
					collapsed.delete(groupKey);
				}
				writeAppointmentsState({ collapsedDateGroups: Array.from(collapsed) });
			});
		});

		const applyAppointmentByDateFilters = () => {
			const searchVal = (byDateSearch?.value || '').toLowerCase().trim();
			const monthVal = byDateMonth?.value || '';
			const yearVal = byDateYear?.value || '';
			writeAppointmentsState({ searchVal, monthVal, yearVal });
			let anyVisibleGroup = false;

			groupWrappers.forEach(group => {
				const groupMonth = group.dataset.month || '';
				const groupYear = group.dataset.year || '';
				const monthMatch = !monthVal || groupMonth === monthVal;
				const yearMatch = !yearVal || groupYear === yearVal;

				const rows = Array.from(group.querySelectorAll('.appointment-by-date-row'));
				let visibleRows = 0;

				rows.forEach(row => {
					const rowSearch = row.dataset.search || '';
					const searchMatch = !searchVal || rowSearch.includes(searchVal);
					const visible = monthMatch && yearMatch && searchMatch;
					row.style.display = visible ? '' : 'none';
					if (visible) visibleRows += 1;
				});

				const countEl = group.querySelector('.appointment-date-group-count');
				if (countEl) countEl.textContent = String(visibleRows);

				group.style.display = visibleRows > 0 ? '' : 'none';
				if (visibleRows > 0) {
					anyVisibleGroup = true;
				}
			});

			if (byDateNoResults) {
				byDateNoResults.classList.toggle('hidden', anyVisibleGroup);
			}
		};

		byDateSearch?.addEventListener('input', debounce(applyAppointmentByDateFilters, 220));
		byDateMonth?.addEventListener('change', applyAppointmentByDateFilters);
		byDateYear?.addEventListener('change', applyAppointmentByDateFilters);
		byDateReset?.addEventListener('click', () => {
			if (byDateSearch) byDateSearch.value = '';
			if (byDateMonth) byDateMonth.value = '';
			if (byDateYear) byDateYear.value = '';
			applyAppointmentByDateFilters();
		});

		const savedAppointmentsState = readAppointmentsState();
		if (byDateSearch && typeof savedAppointmentsState.searchVal === 'string') byDateSearch.value = savedAppointmentsState.searchVal;
		if (byDateMonth && typeof savedAppointmentsState.monthVal === 'string') byDateMonth.value = savedAppointmentsState.monthVal;
		if (byDateYear && typeof savedAppointmentsState.yearVal === 'string') byDateYear.value = savedAppointmentsState.yearVal;

		const collapsedAppointmentGroups = new Set(savedAppointmentsState.collapsedDateGroups || []);
		document.querySelectorAll('.appointment-date-group-toggle').forEach(toggle => {
			const key = toggle.dataset.group;
			if (!collapsedAppointmentGroups.has(key)) return;
			const content = document.querySelector(`.appointment-date-group-content[data-group="${key}"]`);
			const arrow = toggle.querySelector('.appointment-date-group-arrow');
			if (!content || !arrow) return;
			content.style.display = 'none';
			arrow.style.transform = 'rotate(-90deg)';
		});

		// Default to date-first view for registrar workflow (or restore last tab).
		switchAppointmentViewMode(savedAppointmentsState.viewMode === 'pending' ? 'pending' : 'bydate');
		applyAppointmentByDateFilters();

		// Keyboard shortcuts: / focus active search, Alt+7 Pending, Alt+8 By Date
		document.addEventListener('keydown', (e) => {
			const target = e.target;
			const isTyping = target && (target.tagName === 'INPUT' || target.tagName === 'TEXTAREA' || target.isContentEditable);

			if (!isTyping && e.key === '/' && currentAppointmentViewMode === 'bydate' && byDateSearch) {
				e.preventDefault();
				byDateSearch.focus();
				byDateSearch.select();
			}

			if (!isTyping && e.altKey && e.key === '7') {
				e.preventDefault();
				switchAppointmentViewMode('pending');
			}

			if (!isTyping && e.altKey && e.key === '8') {
				e.preventDefault();
				switchAppointmentViewMode('bydate');
			}
		});
	};

	const initAppPreferenceForms = () => {
		const forms = document.querySelectorAll('[data-app-settings-form="1"]');
		if (!forms.length || !window.MinSUAppPrefs) {
			return;
		}

		const setFormValues = (form, prefs) => {
			const fields = ['text_scale', 'contrast', 'motion', 'font', 'focus'];
			fields.forEach((name) => {
				const input = form.querySelector(`[name="${name}"]`);
				if (input) input.value = prefs[name] || '';
			});
		};

		const bindFeedback = (form, message, tone = 'success') => {
			const node = form.querySelector('[data-app-settings-feedback="1"]');
			if (!node) return;
			node.textContent = message;
			node.classList.remove('text-emerald-700', 'text-slate-600');
			node.classList.add(tone === 'success' ? 'text-emerald-700' : 'text-slate-600');
			window.setTimeout(() => {
				if (node.textContent === message) {
					node.textContent = '';
				}
			}, 2200);
		};

		forms.forEach((form) => {
			setFormValues(form, window.MinSUAppPrefs.read());

			form.addEventListener('submit', (e) => {
				e.preventDefault();
				const payload = {
					text_scale: (form.querySelector('[name="text_scale"]') || {}).value || 'normal',
					contrast: (form.querySelector('[name="contrast"]') || {}).value || 'normal',
					motion: (form.querySelector('[name="motion"]') || {}).value || 'normal',
					font: (form.querySelector('[name="font"]') || {}).value || 'default',
					focus: (form.querySelector('[name="focus"]') || {}).value || 'normal',
				};
				const saved = window.MinSUAppPrefs.save(payload);
				setFormValues(form, saved);
				bindFeedback(form, 'Preferences saved.');
			});

			const resetBtn = form.querySelector('[data-app-settings-reset="1"]');
			if (resetBtn) {
				resetBtn.addEventListener('click', () => {
					const defaults = window.MinSUAppPrefs.reset();
					setFormValues(form, defaults);
					bindFeedback(form, 'Preferences reset to default.', 'neutral');
				});
			}
		});
	};

	// Initialize when DOM is ready
	const initAdminPages = () => {
		initAppPreferenceForms();
		manageRequestsInit();
		adminAppointmentsInit();
	};

	// Load sidebar counts asynchronously after page renders
	const loadSidebarCounts = async () => {
		try {
			const response = await fetch('/admin/sidebar-counts', {
				method: 'GET',
				headers: {
					'Accept': 'application/json'
				}
			});
			
			if (!response.ok) return;
			
			const data = await response.json();
			
			// Update pending requests badge
			const requestsBadge = document.querySelector('a[href*="manage-requests"] .menu-badge');
			if (requestsBadge && data.pendingRequestsCount > 0) {
				requestsBadge.textContent = data.pendingRequestsCount;
				requestsBadge.classList.remove('hidden');
			}
			
			// Update pending appointments badge
			const appointmentsBadge = document.querySelector('a[href*="appointments"] .menu-badge');
			if (appointmentsBadge && data.pendingAppointmentsCount > 0) {
				appointmentsBadge.textContent = data.pendingAppointmentsCount;
				appointmentsBadge.classList.remove('hidden');
			}
		} catch (error) {
			// Silently fail if sidebar counts can't load - page is still usable
			console.log('Sidebar counts could not be loaded.');
		}
	};

	// Run sidebar counts load after page is ready but don't block rendering
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initAdminPages);
	} else {
		initAdminPages();
	}

	// Load sidebar counts after a short delay (page is already rendered)
	window.setTimeout(loadSidebarCounts, 500);
})();

</script>
</body>
</html>
