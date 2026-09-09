/*
==========================================================================
CryptoVault - Crypto Dashboard Template
Template Name: CryptoVault
Template URL: https://templatemo.com
Description: JavaScript functionality for CryptoVault dashboard
Author: TemplateMo
Version: 1.0
==========================================================================

TemplateMo 609 Crypto Vault

https://templatemo.com/tm-609-crypto-vault

*/

(function() {
    'use strict';

    /* ========================================
       Mobile Menu
    ======================================== */
    function initMobileMenu() {
        const mobileMenuToggle = document.getElementById('mobileMenuToggle');
        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.getElementById('sidebarOverlay');
        function syncSidebarAccess() {
            if (sidebar) sidebar.inert = window.innerWidth <= 1024 && !sidebar.classList.contains('active');
        }
        syncSidebarAccess();

        function toggleMobileMenu() {
            if (mobileMenuToggle && sidebar && sidebarOverlay) {
                mobileMenuToggle.classList.toggle('active');
                sidebar.classList.toggle('active');
                sidebarOverlay.classList.toggle('active');
                document.body.style.overflow = sidebar.classList.contains('active') ? 'hidden' : '';
                mobileMenuToggle.setAttribute('aria-expanded', String(sidebar.classList.contains('active')));
                mobileMenuToggle.setAttribute('aria-label', sidebar.classList.contains('active') ? 'Fermer le menu' : 'Ouvrir le menu');
                syncSidebarAccess();
            }
        }

        if (mobileMenuToggle) {
            mobileMenuToggle.addEventListener('click', toggleMobileMenu);
        }

        if (sidebarOverlay) {
            sidebarOverlay.addEventListener('click', toggleMobileMenu);
        }
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && sidebar && sidebar.classList.contains('active')) {
                toggleMobileMenu();
                mobileMenuToggle.focus();
            }
        });

        // Close menu when clicking nav items
        document.querySelectorAll('.nav-item').forEach(function(item) {
            item.addEventListener('click', function() {
                if (window.innerWidth <= 1024 && sidebar && sidebar.classList.contains('active')) {
                    toggleMobileMenu();
                }
            });
        });

        // Close menu on window resize
        window.addEventListener('resize', function() {
            if (window.innerWidth > 1024 && sidebar && sidebar.classList.contains('active')) {
                toggleMobileMenu();
            }
            syncSidebarAccess();
        });
    }

    /* ========================================
       Toggle Switches
    ======================================== */
    function initToggleSwitches() {
        document.querySelectorAll('.toggle-switch').forEach(function(toggle) {
            // Skip dark mode toggle as it's handled separately
            if (toggle.id !== 'darkModeToggle') {
                toggle.addEventListener('click', function() {
                    toggle.classList.toggle('active');
                });
            }
        });
    }

    /* ========================================
       Copy to Clipboard
    ======================================== */
    function initCopyButtons() {
        document.querySelectorAll('.copy-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                const addressElement = btn.parentElement.querySelector('.wallet-address');
                if (addressElement) {
                    const address = addressElement.textContent;
                    navigator.clipboard.writeText(address).then(function() {
                        // Show success state
                        btn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6b8e6b" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>';
                        
                        // Reset after 2 seconds
                        setTimeout(function() {
                            btn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>';
                        }, 2000);
                    });
                }
            });
        });
    }

    /* ========================================
       Settings Tabs
    ======================================== */
    function initSettingsTabs() {
        document.querySelectorAll('.settings-tab').forEach(function(tab) {
            tab.addEventListener('click', function() {
                // Remove active from all tabs
                document.querySelectorAll('.settings-tab').forEach(function(t) {
                    t.classList.remove('active');
                });
                
                // Remove active from all content
                document.querySelectorAll('.settings-content').forEach(function(c) {
                    c.classList.remove('active');
                });
                
                // Add active to clicked tab
                tab.classList.add('active');
                
                // Show corresponding content
                const targetId = tab.dataset.tab;
                const targetContent = document.getElementById(targetId);
                if (targetContent) {
                    targetContent.classList.add('active');
                }
            });
        });
    }

    /* ========================================
       Filter Tabs (Markets Page)
    ======================================== */
    function initFilterTabs() {
        document.querySelectorAll('.filter-tab').forEach(function(tab) {
            tab.addEventListener('click', function() {
                document.querySelectorAll('.filter-tab').forEach(function(t) {
                    t.classList.remove('active');
                });
                tab.classList.add('active');
            });
        });
    }

    /* ========================================
       Star/Favorite Toggle
    ======================================== */
    function initStarButtons() {
        document.querySelectorAll('.star-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                btn.classList.toggle('active');
                btn.textContent = btn.classList.contains('active') ? '★' : '☆';
            });
        });
    }

    /* ========================================
       Search Functionality
    ======================================== */
    function initSearch() {
        const searchInput = document.getElementById('searchInput');
        
        if (searchInput) {
            searchInput.addEventListener('input', function(e) {
                const search = e.target.value.toLowerCase();
                
                document.querySelectorAll('.market-table tbody tr').forEach(function(row) {
                    const nameElement = row.querySelector('.coin-name');
                    const symbolElement = row.querySelector('.coin-symbol');
                    
                    if (nameElement && symbolElement) {
                        const name = nameElement.textContent.toLowerCase();
                        const symbol = symbolElement.textContent.toLowerCase();
                        row.style.display = (name.includes(search) || symbol.includes(search)) ? '' : 'none';
                    }
                });
            });
        }
    }

    /* ========================================
       Password Toggle
    ======================================== */
    function initPasswordToggle() {
        document.querySelectorAll('.password-toggle').forEach(function(btn) {
            btn.setAttribute('aria-label', 'Afficher le mot de passe');
            btn.setAttribute('aria-pressed', 'false');
            btn.setAttribute('aria-controls', btn.dataset.target);
            btn.addEventListener('click', function() {
                const targetId = btn.dataset.target;
                const input = document.getElementById(targetId);
                
                if (input) {
                    const type = input.type === 'password' ? 'text' : 'password';
                    input.type = type;
                    btn.setAttribute('aria-label', type === 'text' ? 'Masquer le mot de passe' : 'Afficher le mot de passe');
                    btn.setAttribute('aria-pressed', String(type === 'text'));
                }
            });
        });
    }

    /* ========================================
       Password Strength Meter
    ======================================== */
    function initPasswordStrength() {
        const passwordInput = document.getElementById('registerPassword');
        const strengthBars = document.querySelectorAll('.strength-bar');
        
        if (passwordInput && strengthBars.length > 0) {
            passwordInput.addEventListener('input', function() {
                const password = passwordInput.value;
                let strength = 0;

                if (password.length >= 8) strength++;
                if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
                if (/\d/.test(password)) strength++;
                if (/[^a-zA-Z0-9]/.test(password)) strength++;

                strengthBars.forEach(function(bar, index) {
                    bar.classList.remove('weak', 'medium', 'strong');
                    if (index < strength) {
                        if (strength <= 1) bar.classList.add('weak');
                        else if (strength <= 2) bar.classList.add('medium');
                        else bar.classList.add('strong');
                    }
                });
            });
        }
    }

    /* ========================================
       Confirmation Modal (replaces window.confirm)
    ======================================== */
    function initConfirmModal() {
        const modal = document.getElementById('confirmModal');
        if (!modal) return;

        const messageEl = document.getElementById('confirmModalMessage');
        const confirmBtn = document.getElementById('confirmModalConfirm');
        const cancelBtn = document.getElementById('confirmModalCancel');
        let pendingForm = null;
        let pendingSubmitter = null;
        let opener = null;

        function closeModal() {
            modal.classList.remove('active');
            pendingForm = null;
            if (opener) opener.focus();
        }

        document.querySelectorAll('form[data-confirm]').forEach(function(form) {
            form.addEventListener('submit', function(e) {
                if (form.dataset.confirmed === 'true') {
                    return;
                }
                e.preventDefault();
                pendingForm = form;
                pendingSubmitter = e.submitter;
                opener = document.activeElement;
                messageEl.textContent = form.dataset.confirm;
                modal.classList.add('active');
                cancelBtn.focus();
            });
        });

        confirmBtn.addEventListener('click', function() {
            if (pendingForm) {
                pendingForm.dataset.confirmed = 'true';
                pendingForm.requestSubmit(pendingSubmitter || undefined);
            }
            closeModal();
        });

        cancelBtn.addEventListener('click', closeModal);
        trapDialogKeyboard(modal, closeModal);

        modal.addEventListener('click', function(e) {
            if (e.target === modal) closeModal();
        });
    }

    /* ========================================
       Reject Reason Modal
    ======================================== */
    function initRejectReasonModal() {
        const modal = document.getElementById('rejectReasonModal');
        if (!modal) return;

        const form = document.getElementById('rejectReasonForm');
        const reasonInput = document.getElementById('rejectReasonInput');
        const cancelBtn = document.getElementById('rejectReasonCancel');
        let opener = null;

        function closeModal() {
            modal.classList.remove('active');
            if (opener) opener.focus();
        }

        document.querySelectorAll('.js-reject-reason').forEach(function(btn) {
            btn.addEventListener('click', function() {
                form.action = btn.dataset.action;
                opener = btn;
                reasonInput.value = '';
                modal.classList.add('active');
                setTimeout(function() { reasonInput.focus(); }, 50);
            });
        });

        cancelBtn.addEventListener('click', closeModal);
        trapDialogKeyboard(modal, closeModal);

        modal.addEventListener('click', function(e) {
            if (e.target === modal) closeModal();
        });
    }

    function trapDialogKeyboard(modal, close) {
        modal.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') { e.preventDefault(); close(); return; }
            if (e.key !== 'Tab') return;
            const items = Array.from(modal.querySelectorAll('button:not(:disabled), input:not(:disabled), textarea:not(:disabled), select:not(:disabled), a[href], [tabindex="0"]'))
                .filter(function(el) { return el.getClientRects().length > 0; });
            if (!items.length) return;
            const first = items[0], last = items[items.length - 1];
            if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
            else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
        });
    }

    /* ========================================
       Reveal Animations
    ======================================== */
    function initRevealAnimations() {
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

        const targets = document.querySelectorAll('.card, .market-stat');
        targets.forEach(function(el, index) {
            el.classList.add('reveal');
            el.style.animationDelay = Math.min(index * 60, 480) + 'ms';
        });
    }

    /* ========================================
       Animated Counters
    ======================================== */
    function initAnimatedCounters() {
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

        document.querySelectorAll('.market-stat-value').forEach(function(el) {
            const raw = el.textContent.trim();
            if (!/^[0-9][0-9\s]*$/.test(raw)) return;

            const target = parseInt(raw.replace(/\s/g, ''), 10);
            if (isNaN(target)) return;

            const duration = 700;
            const start = performance.now();

            function step(now) {
                const progress = Math.min((now - start) / duration, 1);
                const eased = 1 - Math.pow(1 - progress, 3);
                el.textContent = Math.floor(eased * target).toLocaleString('fr-FR');
                if (progress < 1) {
                    requestAnimationFrame(step);
                } else {
                    el.textContent = target.toLocaleString('fr-FR');
                }
            }

            requestAnimationFrame(step);
        });
    }

    /* ========================================
       Ripple Effect
    ======================================== */
    function initRippleEffect() {
        document.addEventListener('click', function(e) {
            const btn = e.target.closest('.btn, .security-btn, .icon-btn');
            if (!btn) return;

            const rect = btn.getBoundingClientRect();
            const ripple = document.createElement('span');
            const size = Math.max(rect.width, rect.height);
            ripple.className = 'ripple';
            ripple.style.width = ripple.style.height = size + 'px';
            ripple.style.left = (e.clientX - rect.left - size / 2) + 'px';
            ripple.style.top = (e.clientY - rect.top - size / 2) + 'px';
            btn.appendChild(ripple);
            setTimeout(function() { ripple.remove(); }, 600);
        });
    }

    /* ========================================
       Toast Notifications
    ======================================== */
    function showToast(message, type) {
        let container = document.querySelector('.toast-container');
        if (!container) {
            container = document.createElement('div');
            container.className = 'toast-container';
            document.body.appendChild(container);
        }

        const toast = document.createElement('div');
        toast.className = 'toast ' + (type || 'success');

        const iconPath = type === 'error'
            ? '<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>'
            : '<path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>';

        toast.innerHTML =
            '<svg class="toast-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">' + iconPath + '</svg>' +
            '<span></span>' +
            '<button type="button" class="toast-close" aria-label="Fermer la notification">&times;</button>';
        toast.querySelector('span').textContent = message;
        toast.setAttribute('role', type === 'error' ? 'alert' : 'status');

        container.appendChild(toast);

        function remove() {
            toast.classList.add('toast-out');
            setTimeout(function() { toast.remove(); }, 300);
        }

        toast.querySelector('.toast-close').addEventListener('click', remove);
        if (type !== 'error') setTimeout(remove, 5000);
    }

    function initToasts() {
        const flash = document.getElementById('flashStatus');
        if (flash && flash.dataset.message) {
            showToast(flash.dataset.message, flash.dataset.type || 'success');
        }

        document.querySelectorAll('#flashErrors:not(.notice) li').forEach(function(li) {
            showToast(li.textContent, 'error');
        });
    }

    /* ========================================
       Generic Table Search
    ======================================== */
    function initTableSearch() {
        const targets = new Set();
        document.querySelectorAll('.js-table-search, .js-table-status-filter, .js-table-filter').forEach(function(el) {
            targets.add(el.dataset.target);
        });

        targets.forEach(function(targetId) {
            const container = document.getElementById(targetId);
            if (!container) return;

            const searchInput = document.querySelector('.js-table-search[data-target="' + targetId + '"]');
            const filterEls = Array.prototype.slice.call(
                document.querySelectorAll('.js-table-status-filter[data-target="' + targetId + '"], .js-table-filter[data-target="' + targetId + '"]')
            );
            const emptyMessage = document.querySelector('.js-table-empty[data-target="' + targetId + '"]');
            const rows = Array.prototype.slice.call(container.querySelectorAll('[data-search-row]'));
            const groupHeaders = Array.prototype.slice.call(container.querySelectorAll('[data-group-header]'));

            function rowSearchText(row) {
                let text = row.textContent;
                Array.prototype.forEach.call(row.querySelectorAll('input, select'), function(field) {
                    if (field.type === 'password' || field.type === 'hidden') return;
                    text += ' ' + (field.tagName === 'SELECT' ? field.options[field.selectedIndex].text : field.value);
                });
                return text.toLowerCase();
            }

            function applyFilters() {
                const query = searchInput ? searchInput.value.trim().toLowerCase() : '';
                let visibleCount = 0;
                const visibleByGroup = {};

                rows.forEach(function(row) {
                    const matchesQuery = !query || rowSearchText(row).includes(query);
                    const matchesFilters = filterEls.every(function(el) {
                        const field = el.dataset.field || 'status';
                        return !el.value || row.dataset[field] === el.value;
                    });
                    const matches = matchesQuery && matchesFilters;
                    row.style.display = matches ? '' : 'none';
                    if (matches) {
                        visibleCount++;
                        if (row.dataset.group) {
                            visibleByGroup[row.dataset.group] = (visibleByGroup[row.dataset.group] || 0) + 1;
                        }
                    }
                });

                groupHeaders.forEach(function(header) {
                    header.style.display = visibleByGroup[header.dataset.groupHeader] ? '' : 'none';
                });

                if (emptyMessage) {
                    emptyMessage.style.display = visibleCount === 0 ? '' : 'none';
                }
            }

            if (searchInput) searchInput.addEventListener('input', applyFilters);
            filterEls.forEach(function(el) { el.addEventListener('change', applyFilters); });
        });
    }

    /* ========================================
       Visual Tabs synced to a hidden filter <select>
    ======================================== */
    function initVisualTabs() {
        document.querySelectorAll('.js-visual-tabs').forEach(function (group) {
            const select = document.getElementById(group.dataset.syncs);
            if (!select) return;
            const buttons = Array.prototype.slice.call(group.querySelectorAll('[data-value]'));

            buttons.forEach(function (btn) {
                btn.addEventListener('click', function () {
                    buttons.forEach(function (b) { b.classList.remove('active', 'primary'); });
                    btn.classList.add('active', 'primary');
                    select.value = btn.dataset.value;
                    select.dispatchEvent(new Event('change', { bubbles: true }));
                });
            });
        });
    }

    /* ========================================
       Collapsible Table Groups
    ======================================== */
    function initCollapsibleGroups() {
        document.querySelectorAll('[data-group-header]').forEach(function (header) {
            const table = header.closest('table');
            if (!table) return;
            const groupId = header.dataset.groupHeader;
            const rows = Array.prototype.slice.call(
                table.querySelectorAll('[data-search-row][data-group="' + CSS.escape(groupId) + '"]')
            );

            header.classList.add('is-collapsible');
            header.addEventListener('click', function () {
                const collapsed = header.classList.toggle('is-collapsed');
                rows.forEach(function (row) {
                    row.classList.toggle('is-group-collapsed', collapsed);
                });
            });
        });
    }

    /* ========================================
       Button Loading State
    ======================================== */
    function initButtonLoadingState() {
        document.addEventListener('submit', function(e) {
            if (e.defaultPrevented) return;
            const form = e.target;
            if (!(form instanceof HTMLFormElement)) return;
            if (form.hasAttribute('data-confirm') && form.dataset.confirmed !== 'true') return;

            const btn = e.submitter || form.querySelector('button[type="submit"]');
            if (!btn || btn.disabled) return;
            if (form.dataset.submitting === 'true') { e.preventDefault(); return; }

            form.dataset.submitting = 'true';
            btn.classList.add('is-loading');
            // Keep the submitter enabled so its name/value reaches the server.
            btn.setAttribute('aria-disabled', 'true');
            form.setAttribute('aria-busy', 'true');
            const spinner = document.createElement('span');
            spinner.className = 'btn-spinner';
            btn.prepend(spinner);
        });
        window.addEventListener('pageshow', function() {
            document.querySelectorAll('form[data-submitting]').forEach(function(form) {
                delete form.dataset.submitting;
                form.removeAttribute('aria-busy');
                form.querySelectorAll('.is-loading').forEach(function(btn) {
                    btn.classList.remove('is-loading');
                    btn.removeAttribute('aria-disabled');
                    btn.querySelectorAll('.btn-spinner').forEach(function(spinner) { spinner.remove(); });
                });
            });
        });
    }

    function initFieldAccessibility() {
        document.querySelectorAll('.form-group').forEach(function(group, index) {
            const label = group.querySelector('label.form-label');
            const field = group.querySelector('input:not([type="hidden"]), select, textarea');
            if (label && field && !label.htmlFor) {
                if (!field.id) field.id = 'form-field-' + index;
                label.htmlFor = field.id;
            }
        });
        document.querySelectorAll('.nav-item.active').forEach(function(link) { link.setAttribute('aria-current', 'page'); });
        const summary = document.querySelector('#flashErrors.notice');
        if (!summary) return;
        const fields = Array.from(document.querySelectorAll('input[name], select[name], textarea[name]'));
        summary.querySelectorAll('[data-error-field]').forEach(function(item, index) {
            const key = item.dataset.errorField;
            const field = fields.find(function(input) {
                return input.dataset.errorKey === key || input.name.replace(/\[([^\]]+)\]/g, '.$1') === key;
            });
            if (!field) return;
            if (!field.id) field.id = 'invalid-field-' + index;
            field.setAttribute('aria-invalid', 'true');
            field.classList.add('is-invalid');
            const error = document.createElement('p');
            error.id = 'server-error-' + index;
            error.className = 'field-error server-field-error';
            error.textContent = item.textContent;
            field.insertAdjacentElement('afterend', error);
            field.setAttribute('aria-describedby', ((field.getAttribute('aria-describedby') || '') + ' ' + error.id).trim());
            const link = document.createElement('a');
            link.href = '#' + field.id;
            link.textContent = item.textContent;
            item.textContent = '';
            item.appendChild(link);
            link.addEventListener('click', function(e) {
                e.preventDefault();
                let parent = field.parentElement;
                while (parent) { if (parent.tagName === 'DETAILS') parent.open = true; parent = parent.parentElement; }
                field.focus();
                field.scrollIntoView({ block: 'center' });
            });
        });
        summary.focus();
    }

    /* ========================================
       Status Badge Pulse (flash confirmation)
    ======================================== */
    function initStatusBadgePulse() {
        const flash = document.getElementById('flashStatus');
        if (!flash) return;

        const badge = document.querySelector('.page-header .status-badge');
        if (!badge) return;

        badge.classList.add('badge-pulse');
        badge.addEventListener('animationend', function () {
            badge.classList.remove('badge-pulse');
        }, { once: true });
    }

    /* ========================================
       Table Scroll Shadow (mobile affordance)
    ======================================== */
    function initTableScrollShadow() {
        document.querySelectorAll('.table-responsive').forEach(function (wrap) {
            function update() {
                const scrollable = wrap.scrollWidth > wrap.clientWidth + 1;
                wrap.classList.toggle('has-scroll', scrollable);
                wrap.classList.toggle('scrolled-end', wrap.scrollLeft + wrap.clientWidth >= wrap.scrollWidth - 1);
            }

            wrap.addEventListener('scroll', update);
            window.addEventListener('resize', update);
            update();
        });
    }

    /* ========================================
       Sortable Table Columns
    ======================================== */
    function cellSortText(cell) {
        if (cell.dataset.sortValue !== undefined) return cell.dataset.sortValue;
        const field = cell.querySelector('input, select');
        if (field) return field.tagName === 'SELECT' ? field.options[field.selectedIndex].text : field.value;
        return cell.textContent;
    }

    function initSortableTables() {
        document.querySelectorAll('table.sortable').forEach(function (table) {
            const tbody = table.querySelector('tbody');
            if (!tbody) return;

            const headers = Array.prototype.slice.call(table.querySelectorAll('thead th[data-sort]'));

            headers.forEach(function (th) {
                th.classList.add('is-sortable');

                th.addEventListener('click', function () {
                    const type = th.dataset.sort;
                    const dir = th.classList.contains('sort-asc') ? 'desc' : 'asc';

                    headers.forEach(function (h) {
                        h.classList.remove('sort-asc', 'sort-desc');
                    });
                    th.classList.add(dir === 'asc' ? 'sort-asc' : 'sort-desc');

                    const colIndex = Array.prototype.indexOf.call(th.parentElement.children, th);
                    const rows = Array.prototype.slice.call(tbody.querySelectorAll(':scope > tr'));

                    rows.sort(function (a, b) {
                        const cellA = a.children[colIndex];
                        const cellB = b.children[colIndex];
                        if (!cellA || !cellB) return 0;

                        const rawA = cellSortText(cellA).trim();
                        const rawB = cellSortText(cellB).trim();

                        let valA, valB;
                        if (type === 'number') {
                            valA = parseFloat(rawA.replace(/[^0-9.\-]/g, '')) || 0;
                            valB = parseFloat(rawB.replace(/[^0-9.\-]/g, '')) || 0;
                        } else {
                            valA = rawA.toLowerCase();
                            valB = rawB.toLowerCase();
                        }

                        if (valA < valB) return dir === 'asc' ? -1 : 1;
                        if (valA > valB) return dir === 'asc' ? 1 : -1;
                        return 0;
                    });

                    rows.forEach(function (row) { tbody.appendChild(row); });
                });
            });
        });
    }

    /* ========================================
       Drag & Drop Upload Zone
    ======================================== */
    function initDropzones() {
        document.querySelectorAll('.dropzone').forEach(function (zone) {
            const input = zone.querySelector('input[type="file"]');
            if (!input) return;

            ['dragenter', 'dragover'].forEach(function (evt) {
                zone.addEventListener(evt, function (e) {
                    e.preventDefault();
                    zone.classList.add('is-dragover');
                });
            });

            ['dragleave', 'drop'].forEach(function (evt) {
                zone.addEventListener(evt, function (e) {
                    e.preventDefault();
                    zone.classList.remove('is-dragover');
                });
            });

            zone.addEventListener('drop', function (e) {
                const dt = e.dataTransfer;
                if (!dt || !dt.files || dt.files.length === 0) return;
                input.files = dt.files;
                input.dispatchEvent(new Event('change', { bubbles: true }));
            });
        });
    }

    /* ========================================
       File Upload Preview (name, size, remove)
    ======================================== */
    function initFilePreview() {
        document.querySelectorAll('input[type="file"].js-file-input').forEach(function (input) {
            const previewId = input.dataset.previewTarget || (input.id + 'Preview');
            const preview = document.getElementById(previewId);
            if (!preview) return;

            let files = [];

            function humanSize(bytes) {
                if (bytes < 1024) return bytes + ' o';
                if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' Ko';
                return (bytes / (1024 * 1024)).toFixed(1) + ' Mo';
            }

            function syncInput() {
                const dt = new DataTransfer();
                files.forEach(function (file) { dt.items.add(file); });
                input.files = dt.files;
            }

            function render() {
                preview.innerHTML = '';
                files.forEach(function (file, index) {
                    const item = document.createElement('div');
                    item.className = 'file-preview-item';

                    const name = document.createElement('span');
                    name.className = 'file-preview-name';
                    name.textContent = file.name;

                    const size = document.createElement('span');
                    size.className = 'file-preview-size';
                    size.textContent = humanSize(file.size);

                    const removeBtn = document.createElement('button');
                    removeBtn.type = 'button';
                    removeBtn.className = 'file-preview-remove';
                    removeBtn.setAttribute('aria-label', 'Retirer ce fichier');
                    removeBtn.innerHTML = '&times;';
                    removeBtn.addEventListener('click', function () {
                        files.splice(index, 1);
                        syncInput();
                        render();
                    });

                    item.appendChild(name);
                    item.appendChild(size);
                    item.appendChild(removeBtn);
                    preview.appendChild(item);
                });
            }

            input.addEventListener('change', function () {
                files = Array.prototype.slice.call(input.files);
                render();
            });
        });
    }

    /* ========================================
       Inline Form Validation
    ======================================== */
    function initInlineValidation() {
        document.querySelectorAll('form.js-validate').forEach(function (form) {
            const fields = Array.prototype.slice.call(
                form.querySelectorAll('[required], input[type="number"], input[type="email"]')
            );

            function validateField(field) {
                let message = '';

                if (field.type === 'file') {
                    if (field.hasAttribute('required') && field.files.length === 0) {
                        message = 'Veuillez sélectionner un fichier.';
                    }
                } else if (field.hasAttribute('required') && !field.value.trim()) {
                    message = 'Ce champ est requis.';
                } else if (field.value) {
                    if (field.type === 'number') {
                        const val = parseFloat(field.value);
                        if (field.min !== '' && !isNaN(parseFloat(field.min)) && val < parseFloat(field.min)) {
                            message = 'Valeur minimale : ' + field.min + '.';
                        } else if (field.max !== '' && !isNaN(parseFloat(field.max)) && val > parseFloat(field.max)) {
                            message = 'Valeur maximale : ' + field.max + '.';
                        }
                    } else if (field.type === 'email' && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(field.value)) {
                        message = 'Adresse email invalide.';
                    }
                }

                let errorEl = field.parentElement.querySelector(':scope > .field-error');
                if (message) {
                    field.classList.add('is-invalid');
                    if (!errorEl) {
                        errorEl = document.createElement('p');
                        errorEl.className = 'field-error';
                        field.insertAdjacentElement('afterend', errorEl);
                    }
                    if (!errorEl.id) errorEl.id = 'validation-' + (field.id || fields.indexOf(field));
                    field.setAttribute('aria-invalid', 'true');
                    const descriptions = new Set((field.getAttribute('aria-describedby') || '').split(' ').filter(Boolean));
                    descriptions.add(errorEl.id);
                    field.setAttribute('aria-describedby', Array.from(descriptions).join(' '));
                    errorEl.textContent = message;
                } else {
                    field.classList.remove('is-invalid');
                    field.removeAttribute('aria-invalid');
                    if (errorEl) {
                        field.setAttribute('aria-describedby', (field.getAttribute('aria-describedby') || '').split(' ').filter(function(id) { return id !== errorEl.id; }).join(' '));
                        errorEl.remove();
                    }
                }

                return !message;
            }

            fields.forEach(function (field) {
                field.addEventListener('blur', function () { validateField(field); });
                field.addEventListener('change', function () {
                    if (field.type === 'file' || field.classList.contains('is-invalid')) validateField(field);
                });
                field.addEventListener('input', function () {
                    if (field.classList.contains('is-invalid')) validateField(field);
                });
            });

            form.addEventListener('submit', function (e) {
                let valid = true;
                let firstInvalid = null;

                fields.forEach(function (field) {
                    if (!validateField(field)) {
                        valid = false;
                        if (!firstInvalid) firstInvalid = field;
                    }
                });

                if (!valid) {
                    e.preventDefault();
                    e.stopPropagation();
                    if (firstInvalid) firstInvalid.focus();
                }
            });
        });
    }

    /* ========================================
       Auth Tabs (Login Page)
    ======================================== */
    function initAuthTabs() {
        const authTabs = document.querySelectorAll('.auth-tab');
        const loginForm = document.getElementById('loginForm');
        const registerForm = document.getElementById('registerForm');
        const formHeader = document.querySelector('.form-header');

        authTabs.forEach(function(tab) {
            tab.addEventListener('click', function() {
                authTabs.forEach(function(t) {
                    t.classList.remove('active');
                    t.setAttribute('aria-pressed', 'false');
                });
                tab.classList.add('active');
                tab.setAttribute('aria-pressed', 'true');

                if (tab.dataset.form === 'login') {
                    if (loginForm) loginForm.classList.add('active');
                    if (registerForm) registerForm.classList.remove('active');
                    if (formHeader) {
                        formHeader.querySelector('h1').textContent = 'Bienvenue';
                        formHeader.querySelector('p').textContent = 'Connectez-vous ou créez le compte de votre fédération';
                    }
                } else {
                    if (registerForm) registerForm.classList.add('active');
                    if (loginForm) loginForm.classList.remove('active');
                    if (formHeader) {
                        formHeader.querySelector('h1').textContent = 'Créer un compte';
                        formHeader.querySelector('p').textContent = 'Enregistrez votre fédération';
                    }
                }
            });
        });

        // Quick switch links
        const switchToRegister = document.getElementById('switchToRegister');
        const switchToLogin = document.getElementById('switchToLogin');

        if (switchToRegister && authTabs[1]) {
            switchToRegister.addEventListener('click', function(e) {
                e.preventDefault();
                authTabs[1].click();
            });
        }

        if (switchToLogin && authTabs[0]) {
            switchToLogin.addEventListener('click', function(e) {
                e.preventDefault();
                authTabs[0].click();
            });
        }
    }

    /* ========================================
       Initialize All
    ======================================== */
    function init() {
        initFieldAccessibility();
        initMobileMenu();
        initToggleSwitches();
        initCopyButtons();
        initSettingsTabs();
        initFilterTabs();
        initStarButtons();
        initSearch();
        initPasswordToggle();
        initPasswordStrength();
        initAuthTabs();
        initTableSearch();
        initVisualTabs();
        initCollapsibleGroups();
        initButtonLoadingState();
        initConfirmModal();
        initRejectReasonModal();
        initRevealAnimations();
        initAnimatedCounters();
        initRippleEffect();
        initToasts();
        initStatusBadgePulse();
        initTableScrollShadow();
        initSortableTables();
        initDropzones();
        initFilePreview();
        initInlineValidation();
    }

    // Run on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
