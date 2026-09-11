import './bootstrap';

/* ═══════════════════════════════════════════════════════════
   Dark Admin Dashboard v3 — módulos de interacción
   ═══════════════════════════════════════════════════════════ */

const body = document.body;

/* ─── Tema (light / dark / system) ─── */
const Theme = (() => {
	const KEY = 'dash-theme';
	const root = document.documentElement;
	const modes = ['light', 'dark', 'system'];

	const apply = (theme, persist = true) => {
		const preference = modes.includes(theme) ? theme : 'system';
		const nextTheme = preference === 'system'
			? (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light')
			: preference;
		root.setAttribute('data-theme', nextTheme);
		root.setAttribute('data-theme-preference', preference);
		if (persist) {
			try { localStorage.setItem(KEY, preference); } catch (e) { /* noop */ }
		}
		document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
			const labels = { light: 'Tema claro', dark: 'Tema oscuro', system: 'Tema del sistema' };
			button.setAttribute('aria-label', `Cambiar tema. ${labels[preference]}`);
			button.setAttribute('title', labels[preference]);
			button.setAttribute('aria-pressed', preference === 'dark' ? 'true' : 'false');
		});
		document.dispatchEvent(new CustomEvent('themechange', { detail: { theme: nextTheme, preference } }));
	};

	const toggle = () => {
		const current = root.getAttribute('data-theme-preference') || 'system';
		apply(modes[(modes.indexOf(current) + 1) % modes.length]);
		return root.getAttribute('data-theme');
	};

	const init = () => {
		const preference = root.getAttribute('data-theme-preference') || 'system';
		apply(preference, false);

		const prefers = window.matchMedia('(prefers-color-scheme: dark)');
		prefers.addEventListener('change', (e) => {
			if (root.getAttribute('data-theme-preference') === 'system') {
				root.setAttribute('data-theme', e.matches ? 'dark' : 'light');
				document.dispatchEvent(new CustomEvent('themechange', { detail: { theme: e.matches ? 'dark' : 'light', preference: 'system' } }));
			}
		});
	};

	const bind = () => {
		document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
			button.onclick = (event) => {
				event.preventDefault();
				Theme.toggle();
			};
		});
	};

	return { toggle, init, apply, bind };
})();

Theme.init();
Theme.bind();

/* ─── Heartbeat AJAX: badge de notificaciones + KPIs en vivo ─── */
const Heartbeat = (() => {
	const interval = 20000; // 20s — lectura directa de BD, barata (5 counts indexados)
	let timer = null;
	let inFlight = false;

	const request = async (url) => {
		try {
			const response = await fetch(url, {
				headers: { 'Accept': 'application/json' },
				method: 'GET',
				credentials: 'same-origin'
			});
			if (!response.ok) throw new Error('HTTP ' + response.status);
			return await response.json();
		} catch (e) {
			console.warn('Heartbeat error:', e.message);
			return null;
		}
	};

	const updateBadge = (data) => {
		const bell = document.querySelector('[data-notifications-toggle]');
		const badge = bell?.querySelector('[data-notif-badge]');
		if (!badge) return;

		const unread = (data?.unreadCount ?? 0);
		if (unread === 0) { badge.style.display = 'none'; }
		else {
			badge.style.display = 'grid';
			badge.textContent = unread > 99 ? '99+' : unread;
		}
	};

	/* Actualiza las tarjetas KPI del dashboard con datos frescos del
	 * endpoint /kpis/json. Empareja por etiqueta (no por índice) para que
	 * un reordenamiento futuro de tarjetas no muestre datos equivocados. */
	const refreshKpis = async () => {
		const grid = document.querySelector('.kpi-grid');
		if (!grid) return;

		const data = await request(grid.dataset.kpisUrl);
		if (!data || !Array.isArray(data.kpis)) return;

		const byLabel = new Map(data.kpis.map((kpi) => [kpi.label, kpi]));
		grid.querySelectorAll('[data-stat-card]').forEach((card) => {
			const label = card.querySelector('.kpi-label')?.textContent.trim();
			const kpi = label ? byLabel.get(label) : null;
			if (!kpi) return;

			const valueEl = card.querySelector('[data-stat-value]');
			if (valueEl && String(valueEl.textContent.trim()) !== String(kpi.value)) {
				valueEl.textContent = kpi.value;
				valueEl.classList.remove('kpi-flash');
				void valueEl.offsetWidth; // reinicia la animación
				valueEl.classList.add('kpi-flash');
			}

			const trendEl = card.querySelector('[data-kpi-trend]');
			if (trendEl && kpi.trend) {
				trendEl.classList.remove('up', 'down', 'flat');
				trendEl.classList.add(kpi.trend.dir || 'flat');
				const arrow = kpi.trend.dir === 'up' ? '▲' : (kpi.trend.dir === 'down' ? '▼' : '–');
				const pct = kpi.trend.dir === 'flat' ? '' : ` ${kpi.trend.value}%`;
				const caption = kpi.caption
					? ` <span class="text-tertiary-token ms-1" style="font-weight:500">${kpi.caption}</span>`
					: '';
				trendEl.innerHTML = `${arrow}${pct}${caption}`;
			}
		});
	};

	const tick = async () => {
		if (inFlight) return; // evita solaparse si la red va lenta
		inFlight = true;
		try {
			// Actualizar badge de notificaciones usando datos del Layout
			const dash = window.__dash;
			if (dash && dash.notifications) {
				const unread = dash.notifications.filter((n) => !n.read).length;
				updateBadge({ unreadCount: unread });
			}
			// Tarjetas KPI en vivo (endpoint /kpis/json, directo de BD)
			await refreshKpis();
		} finally {
			inFlight = false;
		}
	};

	return {
		start: () => {
			if (timer) clearInterval(timer);
			timer = setInterval(tick, interval);
			tick(); // ejecución inicial inmediata
		},
		stop: () => { if (timer) { clearInterval(timer); timer = null; } }
	};
})();

document.addEventListener('DOMContentLoaded', () => {
	Heartbeat.start();
	window.addEventListener('beforeunload', () => Heartbeat.stop());
});

/* ─── Sidebar ─── */
const sidebarCollapseKey = 'dash-sidebar-collapsed';
const Sidebar = (() => {
	const updateToggleButtons = () => {
		const collapsed = body.classList.contains('sidebar-collapsed');
		document.querySelectorAll('[data-sidebar-control]').forEach((btn) => {
			const icon = btn.querySelector('i');
			if (icon) {
				const mobile = window.matchMedia('(max-width: 640px)').matches;
				icon.classList.toggle('bi-list', mobile && !body.classList.contains('sidebar-mobile-open'));
				icon.classList.toggle('bi-x-lg', mobile && body.classList.contains('sidebar-mobile-open'));
				icon.classList.toggle('bi-chevron-left', !mobile && !collapsed);
				icon.classList.toggle('bi-chevron-right', !mobile && collapsed);
			}
			const mobile = window.matchMedia('(max-width: 640px)').matches;
			const mobileOpen = body.classList.contains('sidebar-mobile-open');
			btn.setAttribute('aria-label', mobile ? (mobileOpen ? 'Cerrar navegación' : 'Abrir navegación') : (collapsed ? 'Expandir navegación' : 'Contraer navegación'));
			btn.setAttribute('title', mobile ? (mobileOpen ? 'Cerrar navegación' : 'Abrir navegación') : (collapsed ? 'Expandir (Ctrl+B)' : 'Contraer (Ctrl+B)'));
		});
	};

	const setCollapsed = (collapsed) => {
		body.classList.toggle('sidebar-collapsed', collapsed);
		try { localStorage.setItem(sidebarCollapseKey, String(collapsed)); } catch (e) { /* noop */ }
		updateToggleButtons();
	};

	const toggle = () => setCollapsed(!body.classList.contains('sidebar-collapsed'));

	const init = () => {
		const stored = (() => { try { return localStorage.getItem(sidebarCollapseKey); } catch (e) { return null; } })();
		const tabletDefault = window.matchMedia('(min-width: 641px) and (max-width: 1024px)').matches;
		setCollapsed(stored === null ? tabletDefault : stored === 'true');
	};

	return { init, toggle, setCollapsed, updateToggleButtons };
})();

Sidebar.init();

document.querySelectorAll('[data-sidebar-control]').forEach((btn) => {
	btn.addEventListener('click', () => {
		if (window.matchMedia('(max-width: 640px)').matches) {
			body.classList.toggle('sidebar-mobile-open');
			Sidebar.updateToggleButtons();
			return;
		}
		Sidebar.toggle();
	});
});
window.addEventListener('keydown', (e) => {
	if ((e.ctrlKey || e.metaKey) && (e.key === 'b' || e.key === 'B')) {
		e.preventDefault();
		Sidebar.toggle();
	}
});
document.querySelector('body')?.addEventListener('click', (e) => {
	if (e.target === body || (e.target.closest && e.target.closest('[data-mobile-scrim]'))) {
		body.classList.remove('sidebar-mobile-open');
	}
});

/* ─── Toasts ─── */
const Toast = (() => {
	const stack = document.querySelector('[data-toast-stack]');
	if (!stack) return () => {};

	const icons = {
		success: 'bi-check-circle-fill',
		error: 'bi-x-circle-fill',
		warning: 'bi-exclamation-triangle-fill',
		info: 'bi-info-circle-fill',
	};
	const durations = { success: 5000, info: 5000, warning: 8000, error: -1 };
	const escapeHtml = (value) => String(value ?? '').replace(/[&<>'"]/g, (char) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;' }[char]));

	const show = ({ type = 'success', title = '', message = '', action = null, duration }) => {
		const el = document.createElement('div');
		el.className = `toast toast-${type}`;
		el.setAttribute('role', type === 'error' ? 'alert' : 'status');
		el.innerHTML = `
			<span class="toast-bar"></span>
			<div class="toast-body">
				<div class="toast-icon d-inline-flex align-items-center gap-2 mb-1">
					<i class="bi ${icons[type] || icons.info}"></i>
					<span class="toast-title">${escapeHtml(title)}</span>
				</div>
				${message ? `<div class="toast-message">${escapeHtml(message)}</div>` : ''}
				${action ? `<button type="button" class="toast-action" data-toast-action>${escapeHtml(action.label)}</button>` : ''}
				<div class="toast-time">${new Date().toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit' })}</div>
			</div>
			<button type="button" class="toast-close" aria-label="Cerrar"><i class="bi bi-x"></i></button>
			<span class="toast-progress"></span>`;

		if (type === 'success') el.querySelector('.toast-progress').style.background = 'var(--success)';
		if (type === 'error') el.querySelector('.toast-progress').style.background = 'var(--error)';
		if (type === 'warning') el.querySelector('.toast-progress').style.background = 'var(--warning)';
		if (type === 'info') el.querySelector('.toast-progress').style.background = 'var(--info)';

		while (stack.children.length >= 3) stack.removeChild(stack.firstChild);
		stack.appendChild(el);

const remove = () => {
			if (el.dataset.gone) return;
			el.dataset.gone = '1';
			el.classList.add('leaving');
			setTimeout(() => el.remove(), 190);
		};

		el.querySelector('.toast-close').addEventListener('click', remove);
		el.querySelector('[data-toast-action]')?.addEventListener('click', () => {
			try { action.onClick(); } catch (e) { /* noop */ }
			remove();
		});

		const pause = () => el.classList.add('paused');
		const resume = () => el.classList.remove('paused');
		el.addEventListener('mouseenter', pause);
		el.addEventListener('mouseleave', resume);

		const total = duration ?? durations[type];
		if (total > 0) {
			const bar = el.querySelector('.toast-progress');
			bar.style.animation = `toast-progress ${total}ms linear forwards`;
			el.timer = setTimeout(remove, total);
		}
	};

	return { show };
})();
window.dashToast = Toast.show;

/**
 * Show a toast notification with the specified type and message.
 * @param {string} type - Toast type: 'success', 'error', 'warning', 'info'
 * @param {string} message - Toast message
 * @param {number} [durationMs=4000] - Duration in milliseconds (negative means no auto-dismiss)
 */
window.showToast = function (type, message, durationMs) {
    const typeMap = {
        success: 'success',
        error: 'error',
        warning: 'warning',
        info: 'info',
    };
    Toast.show({
        type: typeMap[type] || 'info',
        title: type.charAt(0).toUpperCase() + type.slice(1),
        message: message,
		duration: durationMs,
    });
};

/* ─── Sesión flash → toasts / alertas ─── */
(() => {
	const fb = document.querySelector('[data-session-feedback]');
	if (!fb) return;
	const success = fb.dataset.success;
	const error = fb.dataset.error;
	if (success) Toast.show({ type: 'success', title: 'Operación completada', message: success });
	if (error) Toast.show({ type: 'error', title: 'No se pudo completar', message: error, action: { label: 'Reintentar', onClick: () => window.location.reload() } });
})();

/* ─── Diálogo de confirmación ─── */
const Confirm = (() => {
	let pending = null;

	const dialogEl = document.querySelector('[data-confirm-root]');
	if (!dialogEl) return { ask: (o) => window.confirm(o.message ?? '¿Confirmar acción?'), confirmAll: () => {} };

	const open = (opts) => new Promise((resolve) => {
		pending = { resolve, opts };
		const danger = !!opts.danger;
		const requireType = danger && opts.requireType;

		dialogEl.innerHTML = `
			<div class="modal-scrim" data-mc>
				<div class="confirm-dialog" role="alertdialog" aria-modal="true">
					<div class="confirm-icon ${danger ? 'danger' : 'info'}"><i class="bi ${danger ? 'bi-exclamation-triangle' : 'bi-question-circle'}"></i></div>
					<h3 class="confirm-title">${opts.title || '¿Confirmar acción?'}</h3>
					<p class="confirm-message">${opts.message || ''}</p>
					${requireType ? `
						<div class="confirm-type-field">
							<label for="confirm-type-input">Escribe '${opts.requireType}' para confirmar</label>
							<input id="confirm-type-input" type="text" class="form-control" autocomplete="off" placeholder="${opts.requireType}">
						</div>` : ''}
					<div class="confirm-actions">
						<button type="button" class="btn btn-outline-secondary" data-cancel>Cancelar</button>
						<button type="button" class="btn ${danger ? 'btn-danger' : 'btn-primary'}" data-ok ${requireType ? 'disabled' : ''}>${opts.okLabel || (danger ? 'Eliminar' : 'Confirmar')}</button>
					</div>
				</div>
			</div>`;

		const scrim = dialogEl.querySelector('[data-mc]');
		const okBtn = dialogEl.querySelector('[data-ok]');
		const cancelBtn = dialogEl.querySelector('[data-cancel]');
		const typeInput = dialogEl.querySelector('#confirm-type-input');

		const close = (value) => {
			const d = dialogEl.querySelector('.confirm-dialog');
			d.classList.add('leaving');
			setTimeout(() => { dialogEl.innerHTML = ''; }, 150);
			pending.resolve(value);
			pending = null;
			document.removeEventListener('keydown', onKey);
		};

		const onKey = (e) => {
			if (e.key === 'Escape' && !requireType) { e.preventDefault(); close(false); }
			if (e.key === 'Enter' && !requireType && !e.shiftKey) { e.preventDefault(); close(true); }
			if (e.key === 'Enter' && typeInput && typeInput.value === requireType) { e.preventDefault(); close(true); }
		};

		if (scrim) {
			scrim.addEventListener('mousedown', (e) => { if (e.target === scrim && !requireType) close(false); });
		}

		cancelBtn.addEventListener('click', () => close(false));
		okBtn.addEventListener('click', () => close(true));
		if (requireType && typeInput) {
			typeInput.addEventListener('input', () => {
				okBtn.disabled = typeInput.value !== requireType;
			});
			typeInput.focus();
		} else {
			okBtn.focus();
		}
		document.addEventListener('keydown', onKey);
	});

	/* intercepta forms con [data-confirm] */
	const confirmAll = (scope = document) => {
		scope.querySelectorAll('form[data-confirm]').forEach((form) => {
			form.addEventListener('submit', (e) => {
				e.preventDefault();
				open({
					title: form.dataset.confirmTitle || '¿Confirmar acción?',
					message: form.dataset.confirmMessage || 'Esta acción se aplicará de inmediato.',
					danger: form.hasAttribute('data-confirm-danger'),
					requireType: form.dataset.confirmType || null,
					okLabel: form.dataset.confirmOk || (form.hasAttribute('data-confirm-danger') ? 'Eliminar' : 'Confirmar'),
				}).then((ok) => { if (ok) form.submit(); });
			});
		});
	};

	return { ask: open, confirmAll };
})();
window.dashConfirm = Confirm.ask;
window.dashConfirmAll = Confirm.confirmAll;

/* ─── Centro de notificaciones ─── */
const Notifications = (() => {
	const data = window.__dash?.notifications ?? [];
	const flyout = document.querySelector('[data-notify-flyout]');
	const bell = document.querySelector('[data-notifications-toggle]');
	const badge = bell?.querySelector('[data-notif-badge]');
	const readKey = 'dash-notif-read';

	let currentTab = 'all';
	const persistedRead = (() => { try { return JSON.parse(localStorage.getItem(readKey)) || []; } catch (e) { return []; } })();
	const readIds = new Set([...persistedRead, ...data.filter((n) => n.read).map((n) => n.id)]);

	const isRead = (n) => readIds.has(n.id);
	const badgeCount = () => data.filter((n) => !isRead(n)).length;

	const ticks = () => {
		const unreadCount = badgeCount();
		if (badge) {
			if (unreadCount === 0) { badge.style.display = 'none'; }
			else {
				badge.style.display = 'grid';
				badge.textContent = unreadCount > 99 ? '99+' : unreadCount;
			}
		}
	};

	const render = () => {
		const list = flyout.querySelector('[data-notify-list]');
		const items = data.filter((n) => {
			if (currentTab === 'unread') return !isRead(n);
			return true;
		});
		const empty = flyout.querySelector('[data-notify-empty]');
		if (items.length === 0) {
			list.style.display = 'none';
			empty.style.display = 'block';
		} else {
			list.style.display = '';
			empty.style.display = 'none';
			list.innerHTML = items.map((n) => `
				<div class="notify-item ${isRead(n) ? 'is-read' : ''}" data-notif-id="${n.id}" role="button">
					${isRead(n) ? '' : '<span class="notify-unread-dot"></span>'}
					<span class="avatar is-sm ${n.online ? 'avatar-online' : ''}">${n.initials || 'SYS'}</span>
					<div>
						<div class="notify-title">${n.title}</div>
						${n.desc ? `<div class="notify-desc">${n.desc}</div>` : ''}
						<div class="notify-meta">${n.time}${n.category ? ` · ${n.category}` : ''}</div>
					</div>
				</div>`).join('');
		}
	};

	const openFlyout = () => {
		flyout.classList.remove('d-none');
		document.body.insertAdjacentHTML('beforeend', '<div class="scrim-soft" data-notif-scrim></div>');
		render();
	};
	const closeFlyout = () => {
		flyout.classList.add('d-none');
		document.querySelector('[data-notif-scrim]')?.remove();
	};

	bell?.addEventListener('click', () => {
		if (!flyout.classList.contains('d-none')) { closeFlyout(); return; }
		openFlyout();
	});
	document.addEventListener('click', (e) => {
		if (e.target.closest('[data-notif-scrim]')) closeFlyout();
		if (e.target.closest('[data-mark-all]') && !flyout.classList.contains('d-none')) {
			data.forEach((n) => readIds.add(n.id));
			try { localStorage.setItem(readKey, JSON.stringify([...readIds])); } catch (e) { /* noop */ }
			ticks();
			render();
		}
		if (e.target.closest('[data-notify-tab]')) {
			flyout.querySelectorAll('[data-notify-tab]').forEach((t) => t.classList.remove('active'));
			e.target.closest('[data-notify-tab]').classList.add('active');
			currentTab = e.target.closest('[data-notify-tab]').dataset.notifyTab;
			render();
		}
		if (e.target.closest('[data-notif-id]')) {
			const item = e.target.closest('[data-notif-id]');
			const itemData = data.find((n) => String(n.id) === item.dataset.notifId);
			if (itemData && itemData.url) window.location.href = itemData.url;
			else {
				readIds.add(item.dataset.notifId);
				try { localStorage.setItem(readKey, JSON.stringify([...readIds])); } catch (e) { /* noop */ }
				ticks();
				render();
			}
		}
	});

	window.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeFlyout(); });

	ticks();
	return { ticks };
})();

/* ─── Command palette (Ctrl+K) ─── */
const CommandPalette = (() => {
	const data = window.__dash?.search ?? {};
	const palette = document.querySelector('[data-cmd-palette]');
	const input = palette?.querySelector('input');
	const bodyEl = palette?.querySelector('[data-cmd-body]');
	let items = [];
	let focused = -1;

	const pages = data.pages ?? [];
	const devices = data.devices ?? [];
	const employees = data.employees ?? [];

	const typeLabel = { device: 'Dispositivo', employee: 'Empleado', page: 'Acción', page_nav: 'Sección' };

	const buildResults = (query) => {
		const q = query.trim().toLowerCase();
		const groups = [];

		if (!q || pages.some((p) => (p.label + ' ' + (p.keywords || '')).toLowerCase().includes(q))) {
			groups.push({ title: 'SECCIONES Y ACCIONES', items: pages.map((p) => ({ ...p, type: 'page', icon: p.icon || 'bi-grid-1x2' })) });
		}
		if (!q || devices.some((d) => (d.name + ' ' + d.ip).toLowerCase().includes(q))) {
			groups.push({ title: 'DISPOSITIVOS', items: devices.map((d) => ({ label: d.name, desc: d.ip, url: d.url, type: 'device', icon: 'bi-hdd-network' })) });
		}
		if (!q || employees.some((e) => (e.name + ' ' + e.id + ' ' + (e.device || '')).toLowerCase().includes(q))) {
			groups.push({ title: 'EMPLEADOS', items: employees.map((e) => ({ label: e.name, desc: '#' + e.id + (e.device ? ' - ' + e.device : ''), url: e.url, type: 'employee', icon: 'bi-person' })) });
		}

		const flat = [];
		items = [];
		let key = 0;
		groups.forEach((g) => {
			if (g.items.length === 0) return;
			flat.push({ kind: 'header', key: key++, label: g.title });
			g.items.forEach((i) => {
				flat.push({ kind: 'item', key: key++, ...i });
				items.push({ kind: 'item', key: key - 1, ...i });
			});
		});
		return flat;
	};

	const render = (list) => {
		if (!bodyEl) return;
		bodyEl.innerHTML = list.map((row) =>
			row.kind === 'header'
				? `<div class="cmd-group-title">${row.label}</div>`
				: `<button type="button" class="cmd-item" data-cmd-key="${row.key}">
						<i class="bi ${row.icon || 'bi-circle'} cmd-icon"></i>
						<span class="cmd-label">${row.label}</span>
						<span class="cmd-desc">${typeLabel[row.type] || ''}${row.desc ? ' · ' + row.desc : ''}</span>
					</button>`
		).join('');
	};

	const open = () => {
		palette.classList.remove('d-none');
		input.value = '';
		focused = -1;
		render(buildResults(''));
		input.focus();
	};
	const close = () => palette.classList.add('d-none');

	document.querySelectorAll('[data-open-cmd]').forEach((el) => el.addEventListener('click', open));
	window.addEventListener('keydown', (e) => {
		if ((e.ctrlKey || e.metaKey) && (e.key === 'k' || e.key === 'K')) { e.preventDefault(); open(); }
		if (e.key === 'Escape' && palette && !palette.classList.contains('d-none')) close();
	});

	input?.addEventListener('input', () => {
		focused = -1;
		render(buildResults(input.value));
	});

	palette?.addEventListener('click', (e) => {
		if (e.target.closest('[data-cmd-scrim]')) close();
		const item = e.target.closest('.cmd-item');
		if (item) {
			const row = items.find((i) => i.key === Number(item.dataset.cmdKey));
			if (row) { window.location.href = row.url; }
		}
	});
	palette?.addEventListener('keydown', (e) => {
		if (e.key !== 'ArrowDown' && e.key !== 'ArrowUp' && e.key !== 'Enter') return;
		e.preventDefault();
		if (items.length === 0) return;
		if (e.key === 'ArrowDown') focused = (focused + 1) % items.length;
		if (e.key === 'ArrowUp') focused = (focused - 1 + items.length) % items.length;
		bodyEl.querySelectorAll('.cmd-item').forEach((el) => el.classList.remove('focused'));
		const el = bodyEl.querySelector(`[data-cmd-key="${items[focused].key}"]`);
		el?.classList.add('focused');
		el?.scrollIntoView({ block: 'nearest' });
		if (e.key === 'Enter') { window.location.href = items[focused].url; }
	});

	return { open, close };
})();

/* ─── Alertas globales (dismiss memorable en sesión) ─── */
(() => {
	document.querySelectorAll('[data-global-alert]').forEach((alertEl) => {
		const key = alertEl.dataset.globalAlert;
		if (key && sessionStorage.getItem(key)) { alertEl.remove(); return; }
		const btn = alertEl.querySelector('[data-ga-close]');
		btn?.addEventListener('click', () => {
			if (key) { try { sessionStorage.setItem(key, '1'); } catch (e) { /* noop */ } }
			alertEl.remove();
		});
	});
})();

/* ─── Formularios de sincronización (endpoints JSON) → toast ─── */
/* Cualquier <form data-sync> se envía por fetch y muestra el mensaje
   de la cola como toast en vez de navegar al JSON crudo del endpoint. */
(() => {
	document.addEventListener('submit', async (event) => {
		const form = event.target instanceof Element ? event.target.closest('form[data-sync]') : null;
		if (!form) return;
		event.preventDefault();

		const button = form.querySelector('button');
		const original = button ? button.innerHTML : '';
		if (button) button.disabled = true;

		try {
			const response = await fetch(form.action, {
				method: 'POST',
				headers: {
					'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
					'X-Requested-With': 'XMLHttpRequest',
					Accept: 'application/json',
				},
				body: new FormData(form),
			});
			const payload = await response.json().catch(() => null);
			if (!response.ok) {
				throw new Error(payload?.message ?? 'No se pudo encolar la sincronización.');
			}
			showToast('success', payload?.message ?? 'Sincronización enviada a la cola.');
		} catch (error) {
			showToast('error', error instanceof Error && error.message ? error.message : 'No se pudo enviar la solicitud.');
		} finally {
			setTimeout(() => {
				if (button) { button.innerHTML = original; button.disabled = false; }
			}, 1200);
		}
	});
})();

/* ─── Declarar utilidades globales ─── */
window.dashTheme = Theme;
window.App = { Toast, Confirm, Notifications, CommandPalette };

/* init already called above */
body.addEventListener('click', (e) => {
	if (e.target.closest('[data-mobile-scrim]')) body.classList.remove('sidebar-mobile-open');
});
Confirm.confirmAll();

/* ─── Lazy-load view-specific modules (progressive enhancement) ─── */
if (document.getElementById('employees-table')) {
	import('./employees-index.js');
}