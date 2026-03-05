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
        // Handle both .modal.hidden pattern and .app-modal pattern
        modal.classList.remove('hidden');
        modal.classList.remove('hide');
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
            // For .modal.hidden pattern, add hidden class
            if (modal.classList.contains('modal')) {
                modal.classList.add('hidden');
            }
            // For .app-modal pattern, it hides via CSS (display:none when no .show)
            if (!qsa('.modal.show, .app-modal.show').length) document.body.style.overflow = '';
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

            const closeBtn = e.target.closest('[data-close], .close-btn, .emp-modal-close, .emp-profile-close');
            if (closeBtn) {
                const modal = closeBtn.closest('.modal, .app-modal');
                if (modal) closeModal(modal);
            }
        });

        // Backdrop click - handle both .modal and .app-modal
        document.addEventListener('mousedown', e => {
            const content = e.target.closest('.modal-content, .app-modal-content');
            const modal = e.target.closest('.modal, .app-modal');
            if (modal && !content) closeModal(modal);
        });

        // ESC closes all - handle both .modal and .app-modal
        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') {
                qsa('.modal.show, .app-modal.show').forEach(m => closeModal(m));
            }
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

    /* =============== EMPLOYEE FORM VALIDATION & AJAX =============== */
    function initEmployeeFormValidation() {
        const contactInput = qs('#empContactNumber');
        const sssInput = qs('#empSSSNumber');
        const form = qs('#employeeCreateForm');
        const errorsDiv = qs('#employeeFormErrors');
        const successDiv = qs('#employeeFormSuccess');
        const submitBtn = qs('#employeeSubmitBtn');

        // Contact Number: digits only, max 11
        if (contactInput) {
            contactInput.addEventListener('input', function(e) {
                // Remove any non-digit characters
                let value = this.value.replace(/\D/g, '');
                // Limit to 11 digits
                if (value.length > 11) value = value.substring(0, 11);
                this.value = value;
            });
            contactInput.addEventListener('keypress', function(e) {
                // Only allow digit keys
                if (!/[0-9]/.test(e.key)) {
                    e.preventDefault();
                }
            });
            // Prevent paste of non-numeric content
            contactInput.addEventListener('paste', function(e) {
                e.preventDefault();
                const paste = (e.clipboardData || window.clipboardData).getData('text');
                const digits = paste.replace(/\D/g, '').substring(0, 11);
                this.value = digits;
            });
        }

        // SSS Number: auto-format XX-XXXXXXX-X
        if (sssInput) {
            sssInput.addEventListener('input', function(e) {
                // Remove any non-digit characters
                let value = this.value.replace(/\D/g, '');
                // Limit to 10 digits
                if (value.length > 10) value = value.substring(0, 10);
                
                // Format as XX-XXXXXXX-X
                let formatted = '';
                if (value.length > 0) {
                    formatted = value.substring(0, 2);
                }
                if (value.length > 2) {
                    formatted += '-' + value.substring(2, 9);
                }
                if (value.length > 9) {
                    formatted += '-' + value.substring(9, 10);
                }
                this.value = formatted;
            });
            sssInput.addEventListener('keypress', function(e) {
                // Only allow digit keys
                if (!/[0-9]/.test(e.key)) {
                    e.preventDefault();
                }
            });
            // Prevent paste of non-numeric content
            sssInput.addEventListener('paste', function(e) {
                e.preventDefault();
                const paste = (e.clipboardData || window.clipboardData).getData('text');
                let digits = paste.replace(/\D/g, '').substring(0, 10);
                // Format
                let formatted = '';
                if (digits.length > 0) formatted = digits.substring(0, 2);
                if (digits.length > 2) formatted += '-' + digits.substring(2, 9);
                if (digits.length > 9) formatted += '-' + digits.substring(9, 10);
                this.value = formatted;
            });
        }

        // Form AJAX submission
        if (form && errorsDiv && submitBtn) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Hide previous messages
                errorsDiv.style.display = 'none';
                successDiv.style.display = 'none';
                errorsDiv.querySelector('ul').innerHTML = '';
                
                // Disable submit button
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Registering...';
                
                const formData = new FormData(form);
                
                fetch(form.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                })
                .then(response => {
                    if (response.ok) {
                        return response.json().then(data => ({ ok: true, data }));
                    } else if (response.status === 422) {
                        return response.json().then(data => ({ ok: false, data }));
                    } else {
                        throw new Error('Server error');
                    }
                })
                .then(result => {
                    if (result.ok) {
                        // Success
                        successDiv.textContent = result.data.message || 'Employee registered successfully!';
                        successDiv.style.display = 'block';
                        form.reset();
                        qs('#createProfilePreview').style.display = 'none';
                        
                        // Reload page after short delay to show new employee
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    } else {
                        // Validation errors
                        const errors = result.data.errors || {};
                        const errorList = errorsDiv.querySelector('ul');
                        for (const field in errors) {
                            errors[field].forEach(msg => {
                                const li = document.createElement('li');
                                li.textContent = msg;
                                errorList.appendChild(li);
                            });
                        }
                        errorsDiv.style.display = 'block';
                        // Scroll to errors
                        errorsDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                })
                .catch(err => {
                    console.error('Employee form error:', err);
                    errorsDiv.querySelector('ul').innerHTML = '<li>An unexpected error occurred. Please try again.</li>';
                    errorsDiv.style.display = 'block';
                })
                .finally(() => {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="bi bi-check-lg"></i> Register Employee';
                });
            });
        }
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

    /* =============== EMPLOYEE MODAL HELPERS =============== */
    function initEmployeeModals() {
        // View Employee Profile (for employees page - shows selected employee)
        window.viewEmployeeProfile = function(id) {
            fetch(`/employees/${id}`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    const e = data.employee, u = data.user;
                    const profileModal = qs('#employeeProfileModal');
                    if (!profileModal) return;
                    
                    const avatar = qs('#empProfileAvatar', profileModal);
                    if (avatar) avatar.src = e.profile_picture 
                        ? `/storage/${e.profile_picture}` 
                        : '/images/EmployeeProfile.png';
                    
                    const nameEl = qs('#empProfileName', profileModal);
                    if (nameEl) nameEl.textContent = e.first_name + ' ' + e.last_name;
                    
                    const roleEl = qs('#empProfileRole', profileModal);
                    if (roleEl) {
                        roleEl.textContent = u.role ? u.role.charAt(0).toUpperCase() + u.role.slice(1) : 'Employee';
                        roleEl.className = 'emp-badge emp-badge-' + (u.role || 'employee');
                    }
                    
                    const statusEl = qs('#empProfileStatus', profileModal);
                    if (statusEl) {
                        statusEl.innerHTML = u.is_active 
                            ? '<span class="emp-status-dot"></span> Active' 
                            : '<span class="emp-status-dot"></span> Inactive';
                        statusEl.className = 'emp-status ' + (u.is_active ? 'emp-status-active' : 'emp-status-inactive');
                    }
                    
                    const emailEl = qs('#empProfileEmail', profileModal);
                    if (emailEl) emailEl.textContent = u.email || '—';
                    
                    const contactEl = qs('#empProfileContact', profileModal);
                    if (contactEl) contactEl.textContent = e.contact_number || '—';
                    
                    const addressEl = qs('#empProfileAddress', profileModal);
                    if (addressEl) addressEl.textContent = e.address || '—';
                    
                    const sssEl = qs('#empProfileSSS', profileModal);
                    if (sssEl) sssEl.textContent = e.sss_number || '—';
                    
                    const regEl = qs('#empProfileRegistered', profileModal);
                    if (regEl) regEl.textContent = u.created_at 
                        ? new Date(u.created_at).toLocaleDateString('en-US', {month:'short',day:'numeric',year:'numeric'}) 
                        : '—';
                    
                    const editLink = qs('#empProfileEditLink', profileModal);
                    if (editLink) editLink.href = `/employees/${id}/edit`;
                    
                    openModal(profileModal);
                }
            })
            .catch(err => console.error('Error loading employee profile:', err));
        };

        // Close Employee Profile Modal (employees page)
        window.closeEmployeeProfileModal = function() {
            const modal = qs('#employeeProfileModal');
            if (modal) closeModal(modal);
        };

        // Legacy function for backward compatibility
        window.closeViewProfileModal = function() {
            const modal = qs('#viewProfileModal');
            if (modal) closeModal(modal);
        };

        // Confirm Deactivate Employee
        window.confirmDeactivate = function(id, name) {
            const nameEl = qs('#deactivateName');
            if (nameEl) nameEl.textContent = name;
            
            const form = qs('#deactivateForm');
            if (form) form.action = `/employees/${id}/deactivate`;
            
            const modal = qs('#deactivateModal');
            if (modal) openModal(modal);
        };

        // Close Deactivate Modal
        window.closeDeactivateModal = function() {
            const modal = qs('#deactivateModal');
            if (modal) closeModal(modal);
        };
    }

    /* =============== EMPTY FIELD VALIDATION =============== */
    function initEmptyFieldValidation() {
        // Selector for forms that should have validation
        // Excludes search forms, filter forms, and logout form
        const formSelector = 'form:not(.search-bar):not(.filter-row):not(.month-selector):not([action*="logout"])';
        
        // Get display name for a field
        function getFieldLabel(field) {
            // Try to find associated label
            const id = field.id;
            if (id) {
                const label = document.querySelector(`label[for="${id}"]`);
                if (label) {
                    // Remove asterisk indicator
                    return label.textContent.replace(/\s*\*\s*$/, '').trim();
                }
            }
            
            // Try parent label
            const parentLabel = field.closest('label');
            if (parentLabel) {
                const text = parentLabel.textContent.replace(/\s*\*\s*$/, '').trim();
                if (text) return text;
            }
            
            // Try previous sibling label
            const prevLabel = field.previousElementSibling;
            if (prevLabel && prevLabel.tagName === 'LABEL') {
                return prevLabel.textContent.replace(/\s*\*\s*$/, '').trim();
            }
            
            // Try finding label in parent .form-group or similar
            const group = field.closest('.form-group, .form-row, .emp-form-group, .emp-field') || field.parentElement;
            if (group) {
                const groupLabel = group.querySelector('label');
                if (groupLabel && groupLabel !== parentLabel) {
                    return groupLabel.textContent.replace(/\s*\*\s*$/, '').trim();
                }
            }
            
            // Fallback to placeholder or name
            if (field.placeholder) return field.placeholder;
            if (field.name) {
                // Convert snake_case to Title Case
                return field.name.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
            }
            
            return 'Required field';
        }
        
        // Get all required fields in a form
        function getRequiredFields(form) {
            return qsa('input[required], select[required], textarea[required]', form);
        }
        
        // Get empty required fields
        function getEmptyFields(form) {
            return getRequiredFields(form).filter(field => {
                const value = field.value.trim();
                return !value;
            });
        }
        
        // Find submit button in form
        function findSubmitButton(form) {
            return form.querySelector('button[type="submit"], input[type="submit"], .btn-primary[type="submit"], button.btn-primary:not([type="button"])');
        }
        
        // Wrap submit button with tooltip container if not already wrapped
        function wrapSubmitButton(btn) {
            if (btn.parentElement.classList.contains('submit-btn-wrapper')) {
                return btn.parentElement;
            }
            
            const wrapper = document.createElement('div');
            wrapper.className = 'submit-btn-wrapper';
            
            // Preserve button's width style on wrapper
            const btnWidth = btn.style.width;
            if (btnWidth) {
                wrapper.style.width = btnWidth;
            }
            
            btn.parentNode.insertBefore(wrapper, btn);
            wrapper.appendChild(btn);
            
            // Create tooltip element
            const tooltip = document.createElement('div');
            tooltip.className = 'submit-tooltip';
            wrapper.appendChild(tooltip);
            
            return wrapper;
        }
        
        // Update submit button state based on form validity
        function updateSubmitState(form) {
            const submitBtn = findSubmitButton(form);
            if (!submitBtn) return;
            
            const emptyFields = getEmptyFields(form);
            const hasEmptyFields = emptyFields.length > 0;
            
            if (hasEmptyFields) {
                submitBtn.disabled = true;
                
                const wrapper = wrapSubmitButton(submitBtn);
                wrapper.classList.add('has-tooltip');
                
                const tooltip = wrapper.querySelector('.submit-tooltip');
                if (tooltip) {
                    const fieldNames = emptyFields.map(f => getFieldLabel(f));
                    const uniqueNames = [...new Set(fieldNames)]; // Remove duplicates
                    tooltip.innerHTML = `<span class="tooltip-title">Please fill in:</span><span class="tooltip-fields">${uniqueNames.join(', ')}</span>`;
                }
            } else {
                submitBtn.disabled = false;
                
                if (submitBtn.parentElement.classList.contains('submit-btn-wrapper')) {
                    submitBtn.parentElement.classList.remove('has-tooltip');
                }
            }
        }
        
        // Initialize validation for a form
        function initFormValidation(form) {
            // Skip forms without required fields
            const requiredFields = getRequiredFields(form);
            if (requiredFields.length === 0) return;
            
            // Mark form as initialized
            if (form.dataset.validationInit === '1') return;
            form.dataset.validationInit = '1';
            
            // Initial state check
            updateSubmitState(form);
            
            // Add input listeners to all required fields
            requiredFields.forEach(field => {
                field.addEventListener('input', () => updateSubmitState(form));
                field.addEventListener('change', () => updateSubmitState(form));
            });
        }
        
        // Initialize all forms on page
        function initAllForms() {
            qsa(formSelector).forEach(form => initFormValidation(form));
        }
        
        // Run on page load
        initAllForms();
        
        // Also re-check when modals open (for dynamically shown forms)
        const originalOpenModal = window._systemUI?.openModal;
        if (originalOpenModal) {
            window._systemUI.openModal = function(modal) {
                originalOpenModal(modal);
                // Initialize forms inside the modal after it opens
                setTimeout(() => {
                    qsa(formSelector, modal).forEach(form => {
                        // Re-initialize in case form wasn't visible before
                        form.dataset.validationInit = '0';
                        initFormValidation(form);
                    });
                }, 50);
            };
        }
        
        // Observe for dynamically added forms
        const observer = new MutationObserver(mutations => {
            mutations.forEach(mutation => {
                mutation.addedNodes.forEach(node => {
                    if (node.nodeType === 1) { // Element node
                        if (node.matches && node.matches(formSelector)) {
                            initFormValidation(node);
                        }
                        // Check children
                        if (node.querySelectorAll) {
                            qsa(formSelector, node).forEach(form => initFormValidation(form));
                        }
                    }
                });
            });
        });
        observer.observe(document.body, { childList: true, subtree: true });
        
        // Expose for manual re-initialization
        window._initFormValidation = initFormValidation;
        window._updateSubmitState = updateSubmitState;
    }

    window._systemUI = { openModalById, openModal, closeModal };

    /* =============== INIT =============== */
    document.addEventListener('DOMContentLoaded', () => {
        initSidebar();
        initProfileDropdown();
        initModals();
        initCreateEmployeePreview();
        initEmployeeFormValidation();
        highlightActiveNav();
        defineStockInInitializer();
        initEmployeeModals();
        initEmptyFieldValidation();

        // If modal auto-open (validation errors)
        if (qs('#createStockInModal[data-auto-open="true"]')) {
            if (typeof window._stockInEnsureInit === 'function') {
                window._stockInEnsureInit(true);
            }
        }
    });
})();