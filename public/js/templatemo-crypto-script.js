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

        function toggleMobileMenu() {
            if (mobileMenuToggle && sidebar && sidebarOverlay) {
                mobileMenuToggle.classList.toggle('active');
                sidebar.classList.toggle('active');
                sidebarOverlay.classList.toggle('active');
                document.body.style.overflow = sidebar.classList.contains('active') ? 'hidden' : '';
            }
        }

        if (mobileMenuToggle) {
            mobileMenuToggle.addEventListener('click', toggleMobileMenu);
        }

        if (sidebarOverlay) {
            sidebarOverlay.addEventListener('click', toggleMobileMenu);
        }

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
            btn.addEventListener('click', function() {
                const targetId = btn.dataset.target;
                const input = document.getElementById(targetId);
                
                if (input) {
                    const type = input.type === 'password' ? 'text' : 'password';
                    input.type = type;
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

        function closeModal() {
            modal.classList.remove('active');
            pendingForm = null;
        }

        document.querySelectorAll('form[data-confirm]').forEach(function(form) {
            form.addEventListener('submit', function(e) {
                if (form.dataset.confirmed === 'true') {
                    return;
                }
                e.preventDefault();
                pendingForm = form;
                messageEl.textContent = form.dataset.confirm;
                modal.classList.add('active');
            });
        });

        confirmBtn.addEventListener('click', function() {
            if (pendingForm) {
                pendingForm.dataset.confirmed = 'true';
                pendingForm.requestSubmit();
            }
            closeModal();
        });

        cancelBtn.addEventListener('click', closeModal);

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

        function closeModal() {
            modal.classList.remove('active');
        }

        document.querySelectorAll('.js-reject-reason').forEach(function(btn) {
            btn.addEventListener('click', function() {
                form.action = btn.dataset.action;
                reasonInput.value = '';
                modal.classList.add('active');
                setTimeout(function() { reasonInput.focus(); }, 50);
            });
        });

        cancelBtn.addEventListener('click', closeModal);

        modal.addEventListener('click', function(e) {
            if (e.target === modal) closeModal();
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
            '<span>' + message + '</span>' +
            '<button type="button" class="toast-close">&times;</button>';

        container.appendChild(toast);

        function remove() {
            toast.classList.add('toast-out');
            setTimeout(function() { toast.remove(); }, 300);
        }

        toast.querySelector('.toast-close').addEventListener('click', remove);
        setTimeout(remove, 5000);
    }

    function initToasts() {
        const flash = document.getElementById('flashStatus');
        if (flash && flash.dataset.message) {
            showToast(flash.dataset.message, flash.dataset.type || 'success');
        }

        document.querySelectorAll('#flashErrors li').forEach(function(li) {
            showToast(li.textContent, 'error');
        });
    }

    /* ========================================
       Generic Table Search
    ======================================== */
    function initTableSearch() {
        const targets = new Set();
        document.querySelectorAll('.js-table-search, .js-table-status-filter').forEach(function(el) {
            targets.add(el.dataset.target);
        });

        targets.forEach(function(targetId) {
            const container = document.getElementById(targetId);
            if (!container) return;

            const searchInput = document.querySelector('.js-table-search[data-target="' + targetId + '"]');
            const statusFilter = document.querySelector('.js-table-status-filter[data-target="' + targetId + '"]');
            const emptyMessage = document.querySelector('.js-table-empty[data-target="' + targetId + '"]');
            const rows = Array.prototype.slice.call(container.querySelectorAll('[data-search-row]'));

            function applyFilters() {
                const query = searchInput ? searchInput.value.trim().toLowerCase() : '';
                const status = statusFilter ? statusFilter.value : '';
                let visibleCount = 0;

                rows.forEach(function(row) {
                    const matchesQuery = !query || row.textContent.toLowerCase().includes(query);
                    const matchesStatus = !status || row.dataset.status === status;
                    const matches = matchesQuery && matchesStatus;
                    row.style.display = matches ? '' : 'none';
                    if (matches) visibleCount++;
                });

                if (emptyMessage) {
                    emptyMessage.style.display = visibleCount === 0 ? '' : 'none';
                }
            }

            if (searchInput) searchInput.addEventListener('input', applyFilters);
            if (statusFilter) statusFilter.addEventListener('change', applyFilters);
        });
    }

    /* ========================================
       Button Loading State
    ======================================== */
    function initButtonLoadingState() {
        document.addEventListener('submit', function(e) {
            const form = e.target;
            if (!(form instanceof HTMLFormElement)) return;
            if (form.hasAttribute('data-confirm') && form.dataset.confirmed !== 'true') return;

            const btn = e.submitter || form.querySelector('button[type="submit"]');
            if (!btn || btn.disabled || btn.classList.contains('is-loading')) return;

            btn.classList.add('is-loading');
            btn.disabled = true;
            const spinner = document.createElement('span');
            spinner.className = 'btn-spinner';
            btn.prepend(spinner);
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
                });
                tab.classList.add('active');

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
        initButtonLoadingState();
        initConfirmModal();
        initRejectReasonModal();
        initRevealAnimations();
        initAnimatedCounters();
        initRippleEffect();
        initToasts();
    }

    // Run on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
