(function () {
    'use strict';

    const qs  = (s, ctx = document) => ctx.querySelector(s);
    const qsa = (s, ctx = document) => Array.from(ctx.querySelectorAll(s));

    /* =============== SIDEBAR =============== */
    function initSidebar() {
        const btn = qs('[data-toggle="sidebar"]') || qs('.toggle-btn');
        const sidebar = qs('#sidebar');
        if (!btn || !sidebar) return;
        const collapsed = localStorage.getItem('sidebar_collapsed') === '1';
        if (collapsed) document.body.classList.add('sidebar-collapsed');
        btn.addEventListener('click', e => {
            e.preventDefault();
            document.body.classList.toggle('sidebar-collapsed');
            localStorage.setItem(
                'sidebar_collapsed',
                document.body.classList.contains('sidebar-collapsed') ? '1' : '0'
            );
        });
    }

    /* =============== PROFILE DROPDOWN =============== */
    function initProfileDropdown() {
        const trigger = qs('#profileTrigger');
        const menu = qs('#dropdownMenu');
        if (!trigger || !menu) return;

        function open() {
            if (!menu.classList.contains('hidden')) return;
            menu.classList.remove('hidden');
            requestAnimationFrame(() => menu.classList.add('dropdown-animation'));
        }
        function close() {
            if (menu.classList.contains('hidden')) return;
            menu.classList.remove('dropdown-animation');
            menu.classList.add('hidden');
        }
        function toggle() { menu.classList.contains('hidden') ? open() : close(); }

        trigger.addEventListener('click', e => {
            e.preventDefault();
            e.stopPropagation();
            toggle();
        });

        document.addEventListener('click', e => {
            if (!menu.contains(e.target) && e.target !== trigger) close();
        });
        document.addEventListener('keydown', e => { if (e.key === 'Escape') close(); });
    }

    /* =============== MODALS =============== */
    function openModal(modal) {
        if (!modal) return;
        modal.classList.remove('hidden');
        requestAnimationFrame(() => modal.classList.add('show'));
        document.body.style.overflow = 'hidden';
        const first = modal.querySelector('input:not([type=hidden]), textarea, select, button');
        if (first) setTimeout(() => first.focus(), 40);

        // Multi-line Stock-In initializer hook
        if (modal.id === 'createStockInModal' && typeof window._stockInEnsureInit === 'function') {
            window._stockInEnsureInit(true);
        }
    }
    function closeModal(modal) {
        if (!modal) return;
        modal.classList.remove('show');
        setTimeout(() => {
            modal.classList.add('hidden');
            if (!qsa('.modal.show').length) document.body.style.overflow = '';
        }, 200);
    }
    function openModalById(id) { openModal(qs('#' + id)); }

    function initModals() {
        document.addEventListener('click', e => {
            const act = e.target.closest('[data-action]');
            if (act) {
                const a = act.getAttribute('data-action');
                if (a === 'view-profile') {
                    openModalById('viewProfileModal');
                } else if (a === 'register-employee') {
                    openModalById('createEmployeeModal');
                } else if (a === 'register-supplier') {
                    openModalById('createSupplierModal');
                } else if (a === 'register-booking') {
                    openModalById('createBookingModal');
                } else if (a === 'register-stock-in') {
                    openModalById('createStockInModal');
                } else if (a && a.startsWith('register-')) {
                    const slug = a.replace('register-', '');
                    if (slug) {
                        const pascal = slug.split(/[-_]/)
                            .map(s => s.charAt(0).toUpperCase() + s.slice(1))
                            .join('');
                        const guess = 'create' + pascal + 'Modal';
                        if (qs('#' + guess)) openModalById(guess);
                    }
                }
            }

            const closeBtn = e.target.closest('[data-close], .close-btn');
            if (closeBtn) {
                const modal = closeBtn.closest('.modal');
                if (modal) closeModal(modal);
            }
        });

        // Backdrop click
        document.addEventListener('mousedown', e => {
            const content = e.target.closest('.modal-content');
            const modal = e.target.closest('.modal');
            if (modal && !content) closeModal(modal);
        });

        // ESC closes all
        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') qsa('.modal.show').forEach(m => closeModal(m));
        });

        // Auto-open (validation errors)
        qsa('[data-modal][data-auto-open="true"]').forEach(m => openModal(m));
    }

    /* =============== EMPLOYEE IMAGE PREVIEW =============== */
    function initCreateEmployeePreview() {
        const input = qs('#createProfileInput');
        const wrap = qs('#createProfilePreview');
        if (!input || !wrap) return;
        const img = wrap.querySelector('img');
        input.addEventListener('change', () => {
            const f = input.files && input.files[0];
            if (!f) {
                wrap.style.display = 'none';
                return;
            }
            img.src = URL.createObjectURL(f);
            wrap.style.display = 'block';
        });
    }

    /* =============== ACTIVE NAV HIGHLIGHT =============== */
    function highlightActiveNav() {
        const path = location.pathname.replace(/\/+$/, '');
        qsa('.sidebar a.nav-link').forEach(a => {
            const href = a.getAttribute('href');
            if (!href) return;
            const norm = href.replace(location.origin, '').replace(/\/+$/, '');
            if (norm === path) a.classList.add('active');
        });
    }

    /* =============== MULTI-LINE STOCK-IN INITIALIZER =============== */
    function defineStockInInitializer() {
        window._stockInEnsureInit = function(forceEnsure=false) {
            const modal = qs('#createStockInModal');
            if (!modal) return;
            const tableBody = qs('#stockLinesTable tbody', modal);
            const tmpl      = qs('#stockLineTemplate');
            const addBtn    = qs('#addStockLine');
            const grandOut  = qs('#stockGrandTotal');
            const form      = qs('#stockInForm');

            // If elements for multi-line aren't there, abort (maybe different page)
            if (!tableBody || !tmpl || !form) return;

            // Prevent double init
            if (modal.dataset.multiInit === '1') {
                if (forceEnsure && tableBody.children.length === 0) addLine();
                updateTotals();
                return;
            }
            modal.dataset.multiInit = '1';

            function addLine() {
                const index = tableBody.querySelectorAll('tr').length;
                const row = tmpl.content.firstElementChild.cloneNode(true);
                row.querySelectorAll('[data-name]').forEach(el => {
                    const key = el.getAttribute('data-name');
                    el.name = `lines[${index}][${key}]`;
                });
                tableBody.appendChild(row);
                bindRow(row);
                updateTotals();
            }

            function reindex() {
                tableBody.querySelectorAll('tr').forEach((tr, i) => {
                    tr.querySelectorAll('[data-name]').forEach(el => {
                        const key = el.getAttribute('data-name');
                        el.name = `lines[${i}][${key}]`;
                    });
                });
            }

            function bindRow(row) {
                const itemSel = row.querySelector('.stock-item-select');
                const qtyInp  = row.querySelector('.stock-qty-input');
                const unitInp = row.querySelector('[data-unit]');
                const remBtn  = row.querySelector('.remove-stock-line');

                function fillUnit() {
                    const opt = itemSel.options[itemSel.selectedIndex];
                    const price = opt ? parseFloat(opt.dataset.price || '0') : 0;
                    if (!unitInp.value || unitInp.readOnly || unitInp.value === '0' || unitInp.value === '0.00') {
                        unitInp.value = price.toFixed(2);
                    }
                    updateTotals();
                }

                itemSel.addEventListener('change', fillUnit);
                qtyInp.addEventListener('input', updateTotals);
                unitInp.addEventListener('input', updateTotals);
                remBtn.addEventListener('click', () => {
                    row.remove();
                    reindex();
                    updateTotals();
                });

                fillUnit();
            }

            function updateTotals() {
                let grand = 0;
                tableBody.querySelectorAll('tr').forEach(tr => {
                    const qty  = parseFloat(tr.querySelector('.stock-qty-input')?.value || 0);
                    const unit = parseFloat(tr.querySelector('[data-unit]')?.value || 0);
                    const line = qty * unit;
                    const cell = tr.querySelector('.stock-line-total');
                    if (cell) cell.textContent = line.toFixed(2);
                    grand += line;
                });
                if (grandOut) grandOut.textContent = grand.toFixed(2);
            }

            addBtn?.addEventListener('click', addLine);

            if (tableBody.children.length === 0) addLine();

            form.addEventListener('submit', e => {
                if (tableBody.children.length === 0) {
                    e.preventDefault();
                    alert('Add at least one stock line.');
                    return;
                }
                let ok = true;
                tableBody.querySelectorAll('tr').forEach(tr => {
                    const item = tr.querySelector('.stock-item-select')?.value;
                    const sup  = tr.querySelector('.stock-supplier-select')?.value;
                    const qty  = tr.querySelector('.stock-qty-input')?.value;
                    if (!item || !sup || !qty) ok = false;
                });
                if (!ok) {
                    e.preventDefault();
                    alert('Complete all line fields.');
                }
            });

            // Expose helpers if needed
            modal._stockInAddLine = addLine;
            modal._stockInUpdateTotals = updateTotals;
        };
    }

    /* =============== INIT =============== */
    document.addEventListener('DOMContentLoaded', () => {
        initSidebar();
        initProfileDropdown();
        initModals();
        initCreateEmployeePreview();
        highlightActiveNav();
        defineStockInInitializer();

        // If modal auto-open (validation errors)
        if (qs('#createStockInModal[data-auto-open="true"]')) {
            if (typeof window._stockInEnsureInit === 'function') {
                window._stockInEnsureInit(true);
            }
        }
    });

    window._systemUI = { openModalById, openModal, closeModal };
})();