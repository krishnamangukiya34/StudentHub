/**
 * StudentHub Portal - Core Interactivity Module
 * Practical 4: DOM Manipulation, Event Handling, LocalStorage, Sliders, Modals, Hamburger Menu & Theme Switcher
 */

document.addEventListener('DOMContentLoaded', () => {
    initThemeSwitcher();
    initAuthUI();
    initMobileMenu();
    initNotificationBanner();
    initSlider();
    initModals();
    initAccordion();
    highlightActiveNav();
});

/* --------------------------------------------------------------------------
   1. Theme Switcher (Practical 4: Unique Celestial Day/Night Horizon Switch)
   -------------------------------------------------------------------------- */
function initThemeSwitcher() {
    const savedTheme = localStorage.getItem('studenthub_theme') || 'light';
    const toggleBtns = document.querySelectorAll('.theme-toggle-btn');

    toggleBtns.forEach(btn => {
        btn.setAttribute('role', 'switch');
        btn.setAttribute('tabindex', '0');
        // Inject rich celestial elements: morning clouds, twinkling stars, and sliding sun/moon orb
        btn.innerHTML = `
            <span class="celestial-sky">
                <span class="sky-cloud cloud-1"></span>
                <span class="sky-cloud cloud-2"></span>
                <span class="sky-star star-1"></span>
                <span class="sky-star star-2"></span>
                <span class="sky-star star-3"></span>
            </span>
            <span class="celestial-orb">
                <span class="crater crater-1"></span>
                <span class="crater crater-2"></span>
                <span class="crater crater-3"></span>
            </span>
        `;

        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
            const nextTheme = currentTheme === 'dark' ? 'light' : 'dark';
            applyTheme(nextTheme);
            localStorage.setItem('studenthub_theme', nextTheme);
        });

        // Accessible Keyboard Toggle (Enter or Spacebar)
        btn.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                btn.click();
            }
        });
    });

    applyTheme(savedTheme);
}

function applyTheme(theme) {
    const isDark = theme === 'dark';
    if (isDark) {
        document.documentElement.setAttribute('data-theme', 'dark');
    } else {
        document.documentElement.removeAttribute('data-theme');
    }

    document.querySelectorAll('.theme-toggle-btn').forEach(btn => {
        btn.setAttribute('aria-checked', isDark ? 'true' : 'false');
        btn.setAttribute('aria-label', isDark ? 'Switch to warm light cream theme' : 'Switch to dark theme');
        btn.setAttribute('title', isDark ? 'Switch to Warm Light Cream ☀️' : 'Switch to Cosmic Dark Mode 🌙');
    });
}

/* --------------------------------------------------------------------------
   2. Mobile Hamburger Menu (Practical 4)
   -------------------------------------------------------------------------- */
function initMobileMenu() {
    const hamburgerBtn = document.querySelector('.mobile-menu-toggle');
    const navMenu = document.querySelector('.center-nav');

    if (hamburgerBtn && navMenu) {
        hamburgerBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            const isOpen = navMenu.classList.toggle('open');
            hamburgerBtn.setAttribute('aria-expanded', isOpen);
            hamburgerBtn.innerHTML = isOpen ? '✕' : '☰';
        });

        // Close when clicking outside
        document.addEventListener('click', (e) => {
            if (!navMenu.contains(e.target) && !hamburgerBtn.contains(e.target)) {
                navMenu.classList.remove('open');
                hamburgerBtn.setAttribute('aria-expanded', 'false');
                hamburgerBtn.innerHTML = '☰';
            }
        });
    }
}

/* --------------------------------------------------------------------------
   3. Notification Banner (Practical 4)
   -------------------------------------------------------------------------- */
function initNotificationBanner() {
    const banner = document.querySelector('.notification-banner');
    if (!banner) return;

    const bannerId = banner.dataset.bannerId || 'default_banner';
    const isDismissed = localStorage.getItem('dismissed_banner_' + bannerId);

    if (isDismissed === 'true') {
        banner.classList.add('hidden');
    }

    const closeBtn = banner.querySelector('.banner-close-btn');
    if (closeBtn) {
        closeBtn.addEventListener('click', () => {
            banner.classList.add('hidden');
            localStorage.setItem('dismissed_banner_' + bannerId, 'true');
        });
    }
}

/* --------------------------------------------------------------------------
   4. Image & Content Slider / Carousel (Practical 4)
   -------------------------------------------------------------------------- */
function initSlider() {
    const sliderContainer = document.querySelector('.slider-container');
    if (!sliderContainer) return;

    const slides = sliderContainer.querySelectorAll('.slider-slide');
    if (slides.length <= 1) return;

    let currentIndex = 0;
    const dotsContainer = document.querySelector('.slider-dots');
    const prevBtn = document.querySelector('.slider-prev');
    const nextBtn = document.querySelector('.slider-next');

    // Create dot indicators
    if (dotsContainer) {
        dotsContainer.innerHTML = '';
        slides.forEach((_, idx) => {
            const dot = document.createElement('div');
            dot.className = `slider-dot ${idx === 0 ? 'active' : ''}`;
            dot.setAttribute('aria-label', `Go to slide ${idx + 1}`);
            dot.addEventListener('click', () => goToSlide(idx));
            dotsContainer.appendChild(dot);
        });
    }

    function goToSlide(index) {
        currentIndex = (index + slides.length) % slides.length;
        sliderContainer.style.transform = `translateX(-${currentIndex * 100}%)`;
        
        // Update dots
        if (dotsContainer) {
            const dots = dotsContainer.querySelectorAll('.slider-dot');
            dots.forEach((dot, idx) => {
                dot.classList.toggle('active', idx === currentIndex);
            });
        }
    }

    if (prevBtn) {
        prevBtn.addEventListener('click', () => goToSlide(currentIndex - 1));
    }
    if (nextBtn) {
        nextBtn.addEventListener('click', () => goToSlide(currentIndex + 1));
    }

    // Auto-advance every 6 seconds
    let autoplay = setInterval(() => goToSlide(currentIndex + 1), 6000);

    const sliderWrap = sliderContainer.closest('.hero-slider-wrap');
    if (sliderWrap) {
        sliderWrap.addEventListener('mouseenter', () => clearInterval(autoplay));
        sliderWrap.addEventListener('mouseleave', () => {
            clearInterval(autoplay);
            autoplay = setInterval(() => goToSlide(currentIndex + 1), 6000);
        });
    }
}

/* --------------------------------------------------------------------------
   5. Modal Popup System (Practical 4)
   -------------------------------------------------------------------------- */
function initModals() {
    // Open trigger buttons
    document.querySelectorAll('[data-modal-target]').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const targetId = btn.getAttribute('data-modal-target');
            openModal(targetId);
        });
    });

    // Close buttons
    document.querySelectorAll('.modal-overlay .modal-close-btn, [data-modal-close]').forEach(btn => {
        btn.addEventListener('click', () => {
            const overlay = btn.closest('.modal-overlay');
            if (overlay) closeModal(overlay);
        });
    });

    // Close when clicking overlay backdrop
    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) {
                closeModal(overlay);
            }
        });
    });

    // ESC key closes active modal
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            const activeModal = document.querySelector('.modal-overlay.active');
            if (activeModal) closeModal(activeModal);
        }
    });
}

function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
        const focusable = modal.querySelector('button, [href], input, select, textarea');
        if (focusable) focusable.focus();
    }
}

function closeModal(modal) {
    modal.classList.remove('active');
    document.body.style.overflow = '';
}

// Global modal helper
window.StudentHub = {
    openModal,
    closeModal
};

/* --------------------------------------------------------------------------
   6. Collapsible FAQ Accordion (Practical 4)
   -------------------------------------------------------------------------- */
function initAccordion() {
    const faqCards = document.querySelectorAll('.faq-card');
    faqCards.forEach(card => {
        const summary = card.querySelector('summary');
        if (summary) {
            summary.addEventListener('click', () => {
                // Optional single-item accordion behavior
                const siblings = card.parentElement.querySelectorAll('.faq-card');
                siblings.forEach(sibling => {
                    if (sibling !== card && sibling.hasAttribute('open')) {
                        sibling.removeAttribute('open');
                    }
                });
            });
        }
    });
}

/* --------------------------------------------------------------------------
   7. Dynamic Active Navigation Highlighting
   -------------------------------------------------------------------------- */
function highlightActiveNav() {
    const currentPath = window.location.pathname.split('/').pop() || 'index.html';
    const navLinks = document.querySelectorAll('header nav a, .text-menu a');
    
    navLinks.forEach(link => {
        const href = link.getAttribute('href');
        if (!href) return;
        const page = href.split('/').pop();
        if (page === currentPath || (currentPath === '' && page === 'index.html')) {
            link.classList.add('active');
            link.style.color = 'var(--text-primary)';
            link.style.fontWeight = '700';
        }
    });
}

/* --------------------------------------------------------------------------
   8. Authentication State & Persistent Session Management
   Handles Login, Register, Session Badges, Logout, and Cross-Page Sync
   -------------------------------------------------------------------------- */

function getPathPrefix() {
    const rawPath = (window.location.pathname || '').replace(/\\/g, '/');
    const isPagesSubdir = rawPath.includes('/pages/') || (!rawPath.endsWith('/') && rawPath.split('/').slice(0, -1).pop() === 'pages');
    return isPagesSubdir ? '' : 'pages/';
}

function escapeHtml(text) {
    if (!text) return '';
    return String(text)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function getCurrentUser() {
    try {
        const stored = localStorage.getItem('studenthub_user');
        if (!stored) return null;
        const parsed = JSON.parse(stored);
        if (parsed && (parsed.isLoggedIn || parsed.name || parsed.id)) {
            return parsed;
        }
        return null;
    } catch (e) {
        return null;
    }
}

function loginUser(userData, redirectUrl) {
    const user = {
        name: userData.name || 'Krishna Mangukiya',
        id: userData.id || '25IT053',
        email: userData.email || 'krishna.mangukiya@studentshub.edu',
        role: userData.role || 'Student',
        avatar: userData.avatar || '👨‍💻',
        isLoggedIn: true,
        loginTime: new Date().toISOString()
    };
    localStorage.setItem('studenthub_user', JSON.stringify(user));
    initAuthUI();
    showToast(`Welcome back, ${user.name}! 🎓`, 'success');
    if (redirectUrl) {
        setTimeout(() => {
            window.location.href = redirectUrl;
        }, 400);
    }
}

function logoutUser(redirectUrl) {
    localStorage.removeItem('studenthub_user');
    initAuthUI();
    showToast('You have been logged out successfully. 👋', 'info');

    // If currently on an authenticated-only app page (e.g. dashboard, profile, admin)
    const currentPath = window.location.pathname.split('/').pop() || '';
    const protectedPages = ['dashboard.html', 'profile.html', 'admin.html'];
    const prefix = getPathPrefix();

    if (protectedPages.includes(currentPath)) {
        setTimeout(() => {
            window.location.href = redirectUrl || `${prefix}login.html`;
        }, 400);
    } else if (redirectUrl) {
        setTimeout(() => {
            window.location.href = redirectUrl;
        }, 400);
    }
}

function showToast(message, type = 'success') {
    let container = document.querySelector('.studenthub-toast-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'studenthub-toast-container';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = `studenthub-toast ${type === 'error' ? 'toast-error' : type === 'info' ? 'toast-info' : ''}`;
    
    const icon = type === 'error' ? '⚠️' : type === 'info' ? 'ℹ️' : '✅';
    toast.innerHTML = `
        <span style="font-size: 1.15rem;">${icon}</span>
        <span style="flex: 1; line-height: 1.4;">${escapeHtml(message)}</span>
        <button type="button" class="toast-close-btn" aria-label="Dismiss">&times;</button>
    `;

    container.appendChild(toast);

    const dismiss = () => {
        toast.classList.add('toast-hiding');
        setTimeout(() => toast.remove(), 350);
    };

    toast.querySelector('.toast-close-btn').addEventListener('click', dismiss);
    setTimeout(dismiss, 3500);
}

function initAuthUI() {
    const user = getCurrentUser();
    const prefix = getPathPrefix();
    const authContainers = document.querySelectorAll('.auth-buttons');
    const currentFile = window.location.pathname.split('/').pop() || 'index.html';

    // 1. Update public topnav .auth-buttons across all pages
    authContainers.forEach(container => {
        const loginBtn = container.querySelector('.btn-login');
        const registerBtn = container.querySelector('.btn-register');
        let sessionBadge = container.querySelector('#nav-user-session');

        if (user) {
            // User IS LOGGED IN: hide Login & Register buttons
            if (loginBtn) loginBtn.style.display = 'none';
            if (registerBtn) registerBtn.style.display = 'none';

            // Create or update user session badge with the Academic Identity Capsule
            if (!sessionBadge) {
                sessionBadge = document.createElement('div');
                sessionBadge.className = 'academic-capsule-wrapper';
                sessionBadge.id = 'nav-user-session';
                container.appendChild(sessionBadge);
            }

            sessionBadge.style.display = 'inline-flex';
            sessionBadge.innerHTML = `
                <button type="button" class="academic-capsule-btn" id="capsule-toggle-btn" aria-haspopup="true" aria-expanded="false" title="Click to view student quick menu">
                    <div class="capsule-avatar-wrap">
                        <span class="capsule-avatar">${escapeHtml(user.avatar || '👨‍💻')}</span>
                        <span class="status-beacon" title="Active on Campus"></span>
                    </div>
                    <div class="capsule-info">
                        <span class="capsule-name">${escapeHtml(user.name || 'Student')}</span>
                        <span class="capsule-role">${escapeHtml(user.id || '25IT053')} • ${escapeHtml(user.role || 'Student')}</span>
                    </div>
                    <span class="capsule-chevron">▾</span>
                </button>

                <!-- Floating Glassmorphic Quick-Deck Menu -->
                <div class="academic-dropdown-deck" id="capsule-dropdown-deck" role="menu" aria-label="Student Command Menu">
                    <div class="deck-header-card">
                        <div class="deck-avatar-box">
                            <span class="deck-big-avatar">${escapeHtml(user.avatar || '👨‍💻')}</span>
                        </div>
                        <div class="deck-student-details">
                            <div class="deck-name">${escapeHtml(user.name || 'Student')}</div>
                            <div class="deck-meta">ID: <strong>${escapeHtml(user.id || '25IT053')}</strong> • ${escapeHtml(user.email || 'krishna.mangukiya@studentshub.edu')}</div>
                            <span class="deck-status-pill">🟢 Campus Active • Fall 2026</span>
                        </div>
                    </div>

                    <div class="deck-divider"></div>

                    <div class="deck-menu-grid">
                        <a href="${prefix}dashboard.html" class="deck-menu-item" role="menuitem">
                            <span class="deck-item-icon">📊</span>
                            <div class="deck-item-text">
                                <strong>Academic Dashboard</strong>
                                <small>Live grades & summary</small>
                            </div>
                            <span class="deck-item-badge">Active</span>
                        </a>
                        <a href="${prefix}profile.html" class="deck-menu-item" role="menuitem">
                            <span class="deck-item-icon">👤</span>
                            <div class="deck-item-text">
                                <strong>Student Profile</strong>
                                <small>Academic bio & credentials</small>
                            </div>
                        </a>
                        <a href="${prefix}courses.html" class="deck-menu-item" role="menuitem">
                            <span class="deck-item-icon">📚</span>
                            <div class="deck-item-text">
                                <strong>Course Modules</strong>
                                <small>Syllabus & downloads</small>
                            </div>
                        </a>
                        <a href="${prefix}events.html" class="deck-menu-item" role="menuitem">
                            <span class="deck-item-icon">📅</span>
                            <div class="deck-item-text">
                                <strong>Campus Events & Schedule</strong>
                                <small>Workshops & deadlines</small>
                            </div>
                        </a>
                        <a href="${prefix}forum.html" class="deck-menu-item" role="menuitem">
                            <span class="deck-item-icon">💬</span>
                            <div class="deck-item-text">
                                <strong>Discussion Forum</strong>
                                <small>Campus community threads</small>
                            </div>
                        </a>
                    </div>

                    <div class="deck-divider"></div>

                    <div class="deck-footer">
                        <a href="${prefix}index.html" class="deck-footer-link">🏠 Home Portal</a>
                        <button type="button" class="deck-logout-btn" id="deck-logout-btn" title="Sign out of StudentHub">
                            <span>🚪</span> Sign Out
                        </button>
                    </div>
                </div>
            `;

            // Setup Capsule Dropdown Toggle
            const toggleBtn = sessionBadge.querySelector('#capsule-toggle-btn');
            const dropdownDeck = sessionBadge.querySelector('#capsule-dropdown-deck');
            const logoutBtn = sessionBadge.querySelector('#deck-logout-btn');

            if (toggleBtn && dropdownDeck) {
                toggleBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const isOpen = dropdownDeck.classList.toggle('active');
                    toggleBtn.classList.toggle('active', isOpen);
                    toggleBtn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
                });

                // Close on click outside
                const closeHandler = (e) => {
                    if (!sessionBadge.contains(e.target)) {
                        dropdownDeck.classList.remove('active');
                        toggleBtn.classList.remove('active');
                        toggleBtn.setAttribute('aria-expanded', 'false');
                    }
                };
                document.addEventListener('click', closeHandler);

                // Close on Escape key
                document.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape' && dropdownDeck.classList.contains('active')) {
                        dropdownDeck.classList.remove('active');
                        toggleBtn.classList.remove('active');
                        toggleBtn.setAttribute('aria-expanded', 'false');
                        toggleBtn.focus();
                    }
                });
            }

            if (logoutBtn) {
                logoutBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    logoutUser();
                });
            }
        } else {
            // User is NOT LOGGED IN: show Login & Register buttons
            if (loginBtn) loginBtn.style.display = '';
            if (registerBtn) registerBtn.style.display = '';
            if (sessionBadge) sessionBadge.style.display = 'none';
        }
    });

    // 2. Wire up all sidebar logout buttons across application pages
    document.querySelectorAll('.logout-text-btn').forEach(btn => {
        if (!btn.dataset.authBound) {
            btn.dataset.authBound = 'true';
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                logoutUser(`${prefix}login.html`);
            });
        }
    });

    // 3. Update dashboard/profile user names if user is logged in
    if (user && user.name) {
        document.querySelectorAll('.avatar-pill strong, .user-name-display').forEach(el => {
            el.textContent = user.name;
        });
    }

    // 4. Page-specific auth enhancements
    if (currentFile === 'login.html') {
        setupLoginPage(user);
    } else if (currentFile === 'register.html') {
        setupRegisterPage(user);
    }
}

function setupLoginPage(user) {
    const loginForm = document.querySelector('.auth-form');
    const authCard = document.querySelector('.auth-card');

    // Toggle password visibility
    const pwdToggle = document.getElementById('toggle-pwd-btn');
    const pwdInput = document.getElementById('login-password');
    if (pwdToggle && pwdInput && !pwdToggle.dataset.bound) {
        pwdToggle.dataset.bound = 'true';
        pwdToggle.addEventListener('click', () => {
            const isPassword = pwdInput.type === 'password';
            pwdInput.type = isPassword ? 'text' : 'password';
            pwdToggle.textContent = isPassword ? '🙈' : '👁️';
            pwdToggle.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
        });
    }

    // If user is already logged in, show active session notice inside the card
    if (authCard) {
        let notice = authCard.querySelector('.active-session-notice');
        if (user) {
            if (!notice) {
                notice = document.createElement('div');
                notice.className = 'active-session-notice';
                const formEl = authCard.querySelector('form');
                if (formEl) authCard.insertBefore(notice, formEl);
            }
            notice.innerHTML = `
                <div style="font-size: 0.95rem; font-weight: 600; margin-bottom: 4px;">
                    Currently signed in as ${escapeHtml(user.name)} (${escapeHtml(user.id || 'Student')})
                </div>
                <p style="font-size: 0.84rem; color: var(--text-secondary); margin: 0 0 10px 0;">
                    You already have an active session. You can proceed to your dashboard or switch accounts.
                </p>
                <div style="display: flex; gap: 10px; justify-content: center; flex-wrap: wrap;">
                    <a href="dashboard.html" class="action-btn-solid" style="padding: 6px 14px; font-size: 0.85rem; text-decoration: none;">Go to Dashboard</a>
                    <button type="button" id="btn-switch-account" class="action-btn-outline" style="padding: 6px 14px; font-size: 0.85rem;">Switch Account</button>
                </div>
            `;
            const switchBtn = notice.querySelector('#btn-switch-account');
            if (switchBtn) {
                switchBtn.addEventListener('click', () => {
                    logoutUser();
                    if (notice) notice.remove();
                });
            }
        } else if (notice) {
            notice.remove();
        }
    }

    // Attach form submit handler
    if (loginForm && !loginForm.dataset.authBound) {
        loginForm.dataset.authBound = 'true';
        loginForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const usernameInput = document.getElementById('login-username');
            const passwordInput = document.getElementById('login-password');
            const usernameVal = usernameInput ? usernameInput.value.trim() : '';
            const passwordVal = passwordInput ? passwordInput.value.trim() : '';

            if (!usernameVal || !passwordVal) {
                showToast('Please enter both Student ID/Email and password.', 'error');
                return;
            }

            // Resolve friendly student name
            let resolvedName = 'Krishna Mangukiya';
            if (usernameVal.toLowerCase().includes('alex')) {
                resolvedName = 'Alex Mercer';
            } else if (usernameVal.includes('@')) {
                const prefixPart = usernameVal.split('@')[0];
                resolvedName = prefixPart.charAt(0).toUpperCase() + prefixPart.slice(1);
            } else if (usernameVal.toUpperCase() !== '25IT053') {
                resolvedName = usernameVal;
            }

            const submitBtn = loginForm.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = 'Signing In...';
            }

            loginUser({
                name: resolvedName,
                id: usernameVal,
                email: usernameVal.includes('@') ? usernameVal : `${usernameVal.toLowerCase()}@studentshub.edu`,
                role: 'Student',
                avatar: '👨‍💻'
            }, 'dashboard.html');
        });
    }
}

function setupRegisterPage(user) {
    const authCard = document.querySelector('.auth-card');
    if (authCard && user) {
        let notice = authCard.querySelector('.active-session-notice');
        if (!notice) {
            notice = document.createElement('div');
            notice.className = 'active-session-notice';
            const formEl = authCard.querySelector('form');
            if (formEl) authCard.insertBefore(notice, formEl);
        }
        notice.innerHTML = `
            <div style="font-size: 0.95rem; font-weight: 600; margin-bottom: 4px;">
                You are currently signed in as ${escapeHtml(user.name)}
            </div>
            <p style="font-size: 0.84rem; color: var(--text-secondary); margin: 0 0 10px 0;">
                You already have an active account session.
            </p>
            <div style="display: flex; gap: 10px; justify-content: center; flex-wrap: wrap;">
                <a href="dashboard.html" class="action-btn-solid" style="padding: 6px 14px; font-size: 0.85rem; text-decoration: none;">Go to Dashboard</a>
                <button type="button" id="btn-reg-switch-account" class="action-btn-outline" style="padding: 6px 14px; font-size: 0.85rem;">Register New Account</button>
            </div>
        `;
        const regSwitchBtn = notice.querySelector('#btn-reg-switch-account');
        if (regSwitchBtn) {
            regSwitchBtn.addEventListener('click', () => {
                logoutUser();
                if (notice) notice.remove();
            });
        }
    }
}

// Global Authentication APIs
window.StudentHubAuth = {
    getCurrentUser,
    loginUser,
    logoutUser,
    initAuthUI,
    showToast
};

