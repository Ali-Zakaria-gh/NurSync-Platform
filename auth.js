/**
 * auth.js — NurSync Centralized Auth State
 * Include this file in every page to auto-apply login/logout UI state.
 */
(function () {
    const isLoggedIn = localStorage.getItem('nursync_loggedin') === 'true';

    // Mobile Menu Logic
    window.toggleMobileMenu = function() {
        const drawer = document.getElementById('mobile-drawer');
        const overlay = document.getElementById('mobile-overlay');
        if (drawer && overlay) {
            drawer.classList.toggle('active');
            overlay.classList.toggle('active');
            document.body.style.overflow = drawer.classList.contains('active') ? 'hidden' : '';
        }
    };

    window.closeMobileMenu = function() {
        const drawer = document.getElementById('mobile-drawer');
        const overlay = document.getElementById('mobile-overlay');
        if (drawer && overlay) {
            drawer.classList.remove('active');
            overlay.classList.remove('active');
            document.body.style.overflow = '';
        }
    };

    // Close drawer on link click
    document.addEventListener('click', (e) => {
        if (e.target.closest('.mobile-drawer-links a')) {
            window.closeMobileMenu();
        }
    });

    // Global Scroll Listener for Navbar Effect
    window.addEventListener('scroll', () => {
        const nav = document.querySelector('.navbar');
        if (nav) {
            if (window.scrollY > 50) {
                nav.classList.add('scrolled');
            } else {
                nav.classList.remove('scrolled');
            }
        }
    });

    function applyAuthState() {
        const loggedOut = document.getElementById('nav-loggedout');
        const loggedIn  = document.getElementById('nav-loggedin');
        
        // Get user data for role check
        const userJson = localStorage.getItem('nursync_user');
        const user = userJson ? JSON.parse(userJson) : null;
        
        // Check by new role schema (admin, manager, headnurse)
        const isMgmt = isLoggedIn && user && (user.role === 'admin' || user.role === 'manager' || user.role === 'headnurse');
        const mgmtLinks = document.querySelectorAll('.nav-mgmt-link');
        mgmtLinks.forEach(el => el.style.display = isMgmt ? 'inline-block' : 'none');

        // Check for AHC Super Coordinator or Admin
        const isApprover = isLoggedIn && user && (user.role === 'admin' || user.hospital_id === 'ahc');
        const approvalLinks = document.querySelectorAll('.nav-approvals-link');
        approvalLinks.forEach(el => el.style.display = isApprover ? 'inline-block' : 'none');

        const addActivityBtn = document.getElementById('add-activity-btn');
        if (addActivityBtn) {
            addActivityBtn.style.setProperty('display', isMgmt ? 'inline-flex' : 'none', 'important');
        }
        const deleteActivityBtn = document.getElementById('delete-activity-btn');
        if (deleteActivityBtn) {
            deleteActivityBtn.style.setProperty('display', isMgmt ? 'inline-flex' : 'none', 'important');
        }

        if (loggedOut) loggedOut.style.display = isLoggedIn ? 'none' : 'flex';
        if (loggedIn)  loggedIn.style.display  = isLoggedIn ? 'flex' : 'none';

        // Add class to body to control CSS-based visibility
        if (isLoggedIn) {
            document.body.classList.add('is-logged-in');
        } else {
            document.body.classList.remove('is-logged-in');
        }
        
        // Also handle card-level admin actions
        const adminActions = document.querySelectorAll('.card-admin-actions');
        adminActions.forEach(el => el.style.display = isMgmt ? 'flex' : 'none');

        // Dynamic Mobile Drawer Footer (Login vs Logout)
        const drawerFooter = document.querySelector('.mobile-drawer-footer');
        if (drawerFooter) {
            if (isLoggedIn) {
                drawerFooter.innerHTML = `
                    <button onclick="logout()" class="btn-logout" style="width:100%; display:flex !important; justify-content:center; align-items:center; background:rgba(231,76,60,0.2) !important; color:#e74c3c !important; border:none; padding:12px; border-radius:8px; font-weight:600; cursor:pointer; font-family:inherit;">
                        <i class="fa-solid fa-right-from-bracket" style="margin-right:10px;"></i> Logout
                    </button>
                `;
            } else {
                drawerFooter.innerHTML = `
                    <a href="login.html" class="btn-login" style="width:100%; display:flex !important; justify-content:center; align-items:center; background:var(--primary-blue, #185289) !important; color:white !important; border:none; padding:12px; border-radius:8px; font-weight:600; cursor:pointer; text-decoration:none; box-sizing:border-box; font-family:inherit;">
                        <i class="fa-solid fa-right-to-bracket" style="margin-right:10px;"></i> Login
                    </a>
                `;
            }
        }
    }

    // Apply as soon as DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            applyAuthState();
            initCustomSelects();
        });
    } else {
        applyAuthState();
        initCustomSelects();
    }

    /**
     * initCustomSelects - Transforms standard selects into premium dropdowns
     * Now with MutationObserver for dynamic content support.
     */
    function initCustomSelects() {
        const selects = document.querySelectorAll('.filter-select');
        selects.forEach(select => {
            // Avoid double init via wrapper check
            if (select.getAttribute('data-custom-init')) return;
            
            const wrapper = document.createElement('div');
            wrapper.className = 'custom-select-wrapper animate-fade-up';
            
            const trigger = document.createElement('div');
            trigger.className = 'custom-select-trigger';
            
            const optionsContainer = document.createElement('div');
            optionsContainer.className = 'custom-options';

            // Function to build/rebuild options
            const updateOptions = () => {
                optionsContainer.innerHTML = '';
                const selectedText = select.options[select.selectedIndex]?.text || '';
                trigger.innerHTML = `<span>${selectedText}</span><i class="fa-solid fa-chevron-down chevron"></i>`;
                
                // Handle disabled state
                if (select.disabled) {
                    trigger.classList.add('disabled');
                } else {
                    trigger.classList.remove('disabled');
                }

                Array.from(select.options).forEach((opt, idx) => {
                    const customOpt = document.createElement('div');
                    customOpt.className = 'custom-option' + (idx === select.selectedIndex ? ' selected' : '');
                    
                    // Contextual icon logic
                    let iconClass = 'fa-hospital';
                    if (opt.value === 'all') {
                        iconClass = select.id.includes('month') ? 'fa-calendar-days' : 'fa-layer-group';
                    } else if (select.id.includes('month')) {
                        iconClass = 'fa-calendar-day';
                    } else {
                        if (opt.value === 'mch') iconClass = 'fa-baby';
                        if (opt.value === 'pscc') iconClass = 'fa-heart-pulse';
                        if (opt.value === 'mhh') iconClass = 'fa-brain';
                        if (opt.value === 'ent') iconClass = 'fa-eye';
                        if (opt.value === 'bhdc') iconClass = 'fa-droplet';
                    }
                    
                    customOpt.innerHTML = `<i class="fa-solid ${iconClass} hosp-icon"></i> <span>${opt.text}</span>`;
                    
                    customOpt.onclick = (e) => {
                        e.stopPropagation();
                        // Prevent selection if disabled
                        if (select.disabled) return;

                        select.value = opt.value;
                        trigger.querySelector('span').innerText = opt.text;
                        optionsContainer.querySelectorAll('.custom-option').forEach(el => el.classList.remove('selected'));
                        customOpt.classList.add('selected');
                        wrapper.classList.remove('active');
                        select.dispatchEvent(new CustomEvent('change', { 
                            detail: { viaCustom: true },
                            bubbles: true 
                        }));
                    };
                    optionsContainer.appendChild(customOpt);
                });
            };

            // Initial build
            updateOptions();
            
            // Sync custom UI if original select is changed programmatically
            select.addEventListener('change', (e) => {
                // If the event didn't originate from a click on a custom-option, 
                // we should update the custom UI state.
                if (!e.detail || !e.detail.viaCustom) {
                    updateOptions();
                }
            });

            // Listen for language changes to update custom select labels and options dynamically
            window.addEventListener('languageChanged', () => {
                updateOptions();
            });
            
            // MutationObserver to watch for dynamic option changes (e.g. Month Population)
            const observer = new MutationObserver(() => {
                updateOptions();
            });
            observer.observe(select, { childList: true, attributes: true, attributeFilter: ['disabled'] });

            select.style.display = 'none';
            select.setAttribute('data-custom-init', 'true');
            
            wrapper.appendChild(trigger);
            wrapper.appendChild(optionsContainer);
            select.parentNode.insertBefore(wrapper, select);
            
            trigger.onclick = (e) => {
                e.stopPropagation();
                if (select.disabled) return; // Prevent opening if disabled

                document.querySelectorAll('.custom-select-wrapper').forEach(w => {
                    if (w !== wrapper) w.classList.remove('active');
                });
                wrapper.classList.toggle('active');
            };
        });
        
        document.addEventListener('click', () => {
            document.querySelectorAll('.custom-select-wrapper').forEach(w => w.classList.remove('active'));
        });
    }

    // Global logout function available to all pages
    window.logout = function () {
        localStorage.removeItem('nursync_loggedin');
        localStorage.removeItem('nursync_user');
        window.location.href = 'index.html';
    };

    // Profile Modal Logic
    window.openProfileModal = function() {
        const modal = document.getElementById('profile-modal');
        if (!modal) {
            console.error('Profile modal not found in DOM');
            return;
        }
        
        const userJson = localStorage.getItem('nursync_user');
        if (!userJson) {
            console.warn('User data not found in localStorage');
            return;
        }
        
        try {
            const user = JSON.parse(userJson);
            
            // Safe population
            const setVal = (id, val) => {
                const el = document.getElementById(id);
                if (el) el.innerText = val || 'Not set';
            };

            setVal('prof-name',  user.full_name);
            let roleLabel = user.role;
            if (user.role === 'headnurse') roleLabel = 'Hospital Coordinator (منسق المستشفى)';
            if (user.role === 'admin') roleLabel = 'System Admin (مدير النظام)';
            if (user.role === 'manager') roleLabel = 'General Manager (المدير العام)';
            setVal('prof-role',  roleLabel);
            setVal('prof-hosp',  user.hospital_name || (user.hospital_id ? user.hospital_id.toUpperCase() : 'Not set'));
            setVal('prof-email', user.email);
            setVal('prof-phone', user.phone);
            
            modal.classList.add('active');
            
            // Ensure overlay click closes the modal
            if (!modal.onclick) {
                modal.onclick = (e) => {
                    if (e.target === modal) window.closeProfileModal();
                };
            }
        } catch (e) {
            console.error('Error opening profile modal:', e);
        }
    };

    window.closeProfileModal = function() {
        const modal = document.getElementById('profile-modal');
        if (modal) modal.classList.remove('active');
    };
})();
