/**
 * Employees Index — progressive enhancement
 *
 * SSR rendered the initial table. This JS handles:
 * - Live search with debounce
 * - AJAX filter changes (cargo, departamento, sede)
 * - AJAX pagination
 * - Loading / error states on tbody
 *
 * If JS fails to load, the form-based SSR still works.
 */
(function () {
    'use strict';

    const SEARCH_DEBOUNCE_MS = 300;
    const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

    // ── DOM refs ──
    const searchInput = document.getElementById('employeeSearch');
    const tbody = document.querySelector('#employees-table tbody');
    const paginationEl = document.querySelector('#employees-pagination');
    const filterForm = document.getElementById('employeeFilters');

    if (!tbody) return; // no table on page

    // ── Helpers ──
    function debounce(fn, ms) {
        let timer;
        return function (...args) {
            clearTimeout(timer);
            timer = setTimeout(() => fn.apply(this, args), ms);
        };
    }

    function setLoading(isLoading) {
        if (isLoading) {
            tbody.setAttribute('aria-busy', 'true');
            tbody.innerHTML =
                '<tr><td colspan="6" class="text-center py-4">' +
                '<div class="spinner-border spinner-border-sm text-primary me-2" role="status">' +
                '<span class="visually-hidden">Cargando…</span></div>' +
                '<span class="text-tertiary-token">Buscando empleados…</span></td></tr>';
            if (searchInput) searchInput.disabled = true;
        } else {
            tbody.removeAttribute('aria-busy');
            if (searchInput) searchInput.disabled = false;
        }
    }

    function buildEmptyRow(isFiltered) {
        const icon = isFiltered ? 'bi-search' : 'bi-people';
        const title = isFiltered ? 'Sin resultados para tu filtro' : 'No hay empleados';
        const desc = isFiltered
            ? 'Prueba con otro nombre, ID, puesto o departamento, o limpia los filtros.'
            : 'Vacía los checadores con «Traer usuarios» o sincroniza Firebird EMPLEADOS para poblar el catálogo.';
        return (
            '<tr><td colspan="6" class="text-center py-4">' +
            '<i class="bi ' + icon + ' fs-1 text-secondary d-block mb-2"></i>' +
            '<p class="fw-semibold mb-1">' + title + '</p>' +
            '<p class="small text-tertiary-token mb-0">' + desc + '</p>' +
            '</td></tr>'
        );
    }

    function buildErrorRow() {
        return (
            '<tr><td colspan="6" class="text-center py-4 text-danger">' +
            '<i class="bi bi-exclamation-triangle me-1"></i> Error al cargar empleados. ' +
            '<button class="btn btn-sm btn-link" data-employees-retry>Reintentar</button></td></tr>'
        );
    }

    // ── Build a single <tr> from JSON employee data ──
    function buildRow(emp, isAdmin) {
        const initial = emp.name ? emp.name.charAt(0).toUpperCase() : '?';
        const antiguedad = emp.fecha_ingreso
            ? '<span class="mono small text-tertiary-token" title="Fecha ingreso ' + emp.fecha_ingreso + '">' + emp.fecha_ingreso + '</span>'
            : '';

        const empleadoHtml =
            '<td data-label="Empleado">' +
            '<div class="d-flex align-items-center gap-2 min-w-0">' +
            '<span class="avatar is-sm flex-shrink-0">' + initial + '</span>' +
            '<div class="min-w-0">' +
            '<div class="fw-semibold text-truncate" title="' + (emp.name || '') + '" style="max-width:18ch">' + (emp.name || '—') + '</div>' +
            '<div class="d-flex align-items-center gap-2">' +
            '<code class="small">' + (emp.user_id || '') + '</code>' +
            antiguedad +
            '</div></div></div></td>';

        // Puesto
        const cargo = emp.cargo || '';
        const depto = emp.departamento || '';
        let puestoHtml;
        if (cargo) {
            puestoHtml =
                '<td data-label="Puesto">' +
                '<div class="fw-semibold small text-truncate" title="' + cargo + '" style="max-width:20ch"><i class="bi bi-briefcase me-1 text-tertiary-token"></i>' + cargo + '</div>' +
                (depto ? '<div class="small text-secondary-token text-truncate" style="max-width:20ch"><i class="bi bi-building me-1"></i>' + depto + '</div>' : '') +
                '</td>';
        } else {
            puestoHtml =
                '<td data-label="Puesto">' +
                '<span class="small text-tertiary-token">—</span>' +
                (depto ? '<div class="small text-secondary-token text-truncate" style="max-width:20ch"><i class="bi bi-building me-1"></i>' + depto + '</div>' : '') +
                '</td>';
        }

        // Adscripción
        const sedeLabel = emp.sede_label || emp.id_campus || '';
        const contrato = emp.contrato || '';
        const nivel = emp.nivel || '';
        let adscripcionHtml = '<td data-label="Sede"><div class="d-flex flex-wrap gap-1 align-items-center">';
        if (sedeLabel) {
            adscripcionHtml += '<span class="badge cat-blue" title="' + (emp.id_campus ? 'ID_CAMPUS ' + emp.id_campus : '') + '"><i class="bi bi-geo-alt me-1"></i>' + sedeLabel + '</span>';
        } else {
            adscripcionHtml += '<span class="small text-tertiary-token">—</span>';
        }
        if (contrato) adscripcionHtml += '<span class="badge cat-gray">' + contrato + '</span>';
        if (nivel) adscripcionHtml += '<span class="badge cat-purple" title="Nivel ' + nivel + '">' + nivel + '</span>';
        adscripcionHtml += '</div></td>';

        // Hardware
        const devices = emp.devices || [];
        const devicesCount = devices.length;
        let hardwareHtml = '<td data-label="Hardware"><div class="d-flex flex-wrap align-items-center gap-1">';

        if (devicesCount === 0) {
            hardwareHtml += '<span class="badge cat-gray">Sin enrolar</span>';
        } else {
            devices.slice(0, 2).forEach(function (d) {
                const pivot = d.pivot || {};
                const tipParts = ['UID ' + (pivot.device_uid || '')];
                if (pivot.card_number) tipParts.push('Tarjeta ' + pivot.card_number);
                hardwareHtml += '<a href="' + window.location.origin + '/devices/' + d.id + '" class="ref-chip" title="' + tipParts.join(' · ') + '"><i class="bi bi-hdd-network"></i>' + d.name + '</a>';
            });
            if (devicesCount > 2) {
                const extraNames = devices.slice(2).map(function (d) { return d.name; }).join(', ');
                hardwareHtml += '<span class="badge cat-gray" title="' + extraNames + '">+' + (devicesCount - 2) + '</span>';
            }
        }

        // Huellas
        const fpCount = emp.fingerprints_count || 0;
        const fpCat = fpCount === 0 ? 'cat-gray' : (fpCount < 3 ? 'cat-amber' : 'cat-green');
        hardwareHtml += '<span class="badge ' + fpCat + '" title="' + fpCount + ' huellas guardadas"><i class="bi bi-fingerprint me-1"></i>' + fpCount + '</span>';

        // Tarjeta icon
        if (emp.has_card) {
            hardwareHtml += '<span class="small text-tertiary-token" title="Con tarjeta RFID"><i class="bi bi-credit-card"></i></span>';
        }
        hardwareHtml += '</div>';

        // Último sync
        if (emp.last_sync) {
            var syncMap = { completed: 'cat-green', failed: 'cat-red', running: 'cat-amber', queued: 'cat-gray' };
            var sc = syncMap[emp.last_sync.status] || 'cat-gray';
            var syncDate = emp.last_sync.finished_at || emp.last_sync.created_at || '';
            hardwareHtml += '<div class="small mono text-tertiary-token mt-1 text-truncate" style="max-width:22ch" title="' + (emp.last_sync.stage || '') + (emp.last_sync.error_message ? ' · ' + emp.last_sync.error_message : '') + '">' +
                '<span class="badge ' + sc + '" style="font-size:10px">' + emp.last_sync.status + '</span> ' +
                syncDate + '</div>';
        }
        hardwareHtml += '</td>';

        // Estado compuesto
        const isBaja = emp.is_baja || emp.status_actual === 'B';
        const fbStatus = emp.status_actual;
        const enrolled = devicesCount > 0;
        const anyActive = enrolled && devices.some(function (d) { return d.pivot && d.pivot.active; });

        let estadoHtml = '<td data-label="Estado">';
        if (isBaja) {
            estadoHtml += '<span class="badge badge-with-dot cat-gray" title="STATUSACTUAL=B Baja nómina">Baja</span>';
        } else {
            estadoHtml += '<span class="badge badge-with-dot cat-green" title="STATUSACTUAL=' + (fbStatus || 'NULL→Activo') + '">Activo</span>';
        }
        estadoHtml += '<div class="small mt-1">';
        if (!enrolled) {
            estadoHtml += '<span class="badge badge-with-dot cat-gray">Sin enrolar</span>';
        } else if (anyActive) {
            estadoHtml += '<span class="badge badge-with-dot cat-green">Enrolado</span>';
        } else {
            estadoHtml += '<span class="badge badge-with-dot cat-amber">Inactivo</span>';
        }
        estadoHtml += '</div></td>';

        // Acciones
        let accionesHtml = '<td data-label=""><div class="table-row-actions justify-content-end">';
        accionesHtml += '<a href="' + (emp.edit_url || '#') + '" class="btn btn-sm btn-ghost" title="Ver detalle / editar" aria-label="Ver ' + (emp.name || '') + '"><i class="bi bi-eye"></i></a>';

        if (isAdmin) {
            accionesHtml += '<a href="' + (emp.edit_url || '#') + '" class="btn btn-sm btn-ghost" title="Editar" aria-label="Editar"><i class="bi bi-pencil"></i></a>';

            if (enrolled) {
                var deviceIds = devices.map(function (d) { return '<input type="hidden" name="device_ids[]" value="' + d.id + '">'; }).join('');
                accionesHtml += '<form action="' + (emp.sync_url || '#') + '" method="POST" class="d-inline" data-sync>' +
                    '<input type="hidden" name="_token" value="' + CSRF_TOKEN + '">' +
                    deviceIds +
                    '<button class="btn btn-sm btn-ghost" title="Re-sincronizar en ' + devicesCount + ' checador(es)" aria-label="Sincronizar"><i class="bi bi-cloud-arrow-up"></i></button></form>';
            }

            accionesHtml += '<form action="' + (emp.destroy_url || '#') + '" method="POST" class="d-inline" data-confirm data-confirm-danger ' +
                'data-confirm-title="¿Quitar a ' + (emp.name || '') + '?" ' +
                'data-confirm-message="Se dará de baja en todos sus checadores y, si no queda enrolado en ninguno, también del catálogo. Sus checadas históricas se conservan.">' +
                '<input type="hidden" name="_token" value="' + CSRF_TOKEN + '">' +
                '<input type="hidden" name="_method" value="DELETE">' +
                '<button class="btn btn-sm btn-icon-danger" title="Dar de baja" aria-label="Dar de baja"><i class="bi bi-person-x"></i></button></form>';
        } else {
            devices.forEach(function (d) {
                accionesHtml += '<a href="/devices/' + d.id + '" class="btn btn-sm btn-ghost" title="Ver ' + d.name + '" aria-label="Ver dispositivo ' + d.name + '"><i class="bi bi-box-arrow-up-right"></i></a>';
            });
        }
        accionesHtml += '</div></td>';

        return '<tr>' + empleadoHtml + puestoHtml + adscripcionHtml + hardwareHtml + estadoHtml + accionesHtml + '</tr>';
    }

    // ── Pagination rendering ──
    function renderPagination(p) {
        if (!paginationEl || !p || p.last_page <= 1) {
            if (paginationEl) paginationEl.innerHTML = '';
            return;
        }

        var html = '<nav><ul class="pagination pagination-sm justify-content-center">';
        if (p.current_page > 1) {
            html += '<li class="page-item"><a class="page-link" href="#" data-page="' + (p.current_page - 1) + '">&laquo;</a></li>';
        }
        for (var i = 1; i <= p.last_page; i++) {
            if (i === 1 || i === p.last_page || (i >= p.current_page - 2 && i <= p.current_page + 2)) {
                html += '<li class="page-item ' + (i === p.current_page ? 'active' : '') + '"><a class="page-link" href="#" data-page="' + i + '">' + i + '</a></li>';
            } else if (i === p.current_page - 3 || i === p.current_page + 3) {
                html += '<li class="page-item disabled"><span class="page-link">…</span></li>';
            }
        }
        if (p.current_page < p.last_page) {
            html += '<li class="page-item"><a class="page-link" href="#" data-page="' + (p.current_page + 1) + '">&raquo;</a></li>';
        }
        html += '</ul></nav>';
        paginationEl.innerHTML = html;

        // Bind pagination clicks
        paginationEl.querySelectorAll('a.page-link[data-page]').forEach(function (link) {
            link.addEventListener('click', function (e) {
                e.preventDefault();
                fetchEmployees(link.dataset.page);
            });
        });
    }

    // ── Update counter text ──
    function updateCounter(total, filterLabel) {
        var counterEl = document.getElementById('employees-counter');
        if (!counterEl) return;
        var html = '<i class="bi bi-people me-1"></i> ' + total + ' empleados';
        if (filterLabel) {
            html += ' · filtrado por <span class="text-secondary-token">' + filterLabel + '</span>';
        }
        counterEl.innerHTML = html;
    }

    // ── Active filter label ──
    function getActiveFilterLabel() {
        var params = new URLSearchParams(window.location.search);
        if (params.get('q')) return params.get('q');
        if (params.get('cargo')) return params.get('cargo');
        if (params.get('departamento')) return params.get('departamento');
        if (params.get('id_campus')) return 'sede ' + params.get('id_campus');
        if (params.get('sin_huella')) return 'sin huellas';
        if (params.get('sin_device')) return 'sin enrolar';
        return '';
    }

    // ── Build fetch URL with all current filters ──
    function buildFetchUrl(page) {
        var searchUrl = new URL('/employees/search', window.location.origin);

        var q = searchInput ? searchInput.value : '';
        var cargoEl = document.getElementById('filterCargo');
        var deptoEl = document.getElementById('filterDepto');
        var sedeEl = document.getElementById('filterSede');
        var statusEl = document.querySelector('[data-status-hidden]') || document.querySelector('input[name="status"]');

        searchUrl.searchParams.set('q', q);
        searchUrl.searchParams.set('page', page || 1);

        // Get status from active tab or hidden input
        var status = 'todos';
        var activeTab = document.querySelector('.nav-tabs .nav-link.active[href*="status="]');
        if (activeTab) {
            var match = activeTab.href.match(/status=([^&]+)/);
            if (match) status = match[1];
        }
        searchUrl.searchParams.set('status', status);

        if (cargoEl && cargoEl.value) searchUrl.searchParams.set('cargo', cargoEl.value);
        if (deptoEl && deptoEl.value) searchUrl.searchParams.set('departamento', deptoEl.value);
        if (sedeEl && sedeEl.value) searchUrl.searchParams.set('id_campus', sedeEl.value);

        return searchUrl;
    }

    // ── Main fetch function ──
    async function fetchEmployees(page) {
        page = page || 1;
        setLoading(true);

        try {
            var url = buildFetchUrl(page);
            var response = await fetch(url, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin'
            });

            if (!response.ok) throw new Error('HTTP ' + response.status);

            var data = await response.json();
            var isAdmin = tbody.getAttribute('data-is-admin') === '1';

            if (!data.employees || data.employees.length === 0) {
                tbody.innerHTML = buildEmptyRow(true);
                renderPagination(null);
                updateCounter(0, getActiveFilterLabel());
                return;
            }

            var rows = data.employees.map(function (emp) { return buildRow(emp, isAdmin); }).join('');
            tbody.innerHTML = rows;
            renderPagination(data.pagination);
            updateCounter(data.pagination.total, getActiveFilterLabel());

            // Re-init confirm dialogs (from app.js)
            if (window.dashConfirmAll) {
                window.dashConfirmAll(tbody);
            }
        } catch (error) {
            console.error('Error fetching employees:', error);
            tbody.innerHTML = buildErrorRow();
            // Bind retry button
            var retryBtn = tbody.querySelector('[data-employees-retry]');
            if (retryBtn) {
                retryBtn.addEventListener('click', function () { fetchEmployees(page); });
            }
        } finally {
            setLoading(false);
        }
    }

    // ── Event listeners ──

    // Live search with debounce
    if (searchInput) {
        searchInput.addEventListener('input', debounce(function () {
            fetchEmployees(1);
        }, SEARCH_DEBOUNCE_MS));
    }

    // Filter changes trigger immediate fetch
    ['filterCargo', 'filterDepto', 'filterSede'].forEach(function (id) {
        var el = document.getElementById(id);
        if (el) {
            el.addEventListener('change', function () {
                fetchEmployees(1);
            });
        }
    });

    // Pagination clicks (delegated)
    document.addEventListener('click', function (e) {
        var link = e.target.closest('.pagination a.page-link[data-page]');
        if (link) {
            e.preventDefault();
            fetchEmployees(link.dataset.page);
        }
    });

    // Form submit → AJAX instead of full page reload
    if (filterForm) {
        filterForm.addEventListener('submit', function (e) {
            e.preventDefault();
            fetchEmployees(1);
        });
    }

    // Clear filters link — let SSR handle it (full page reload)
    // The existing <a> with href already does this correctly.

})();
