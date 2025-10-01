// Mobile Navigation
const mobileMenuBtn = document.getElementById('mobile-menu-btn');
const mobileNav = document.getElementById('mobile-nav');
const mobileNavClose = document.getElementById('mobile-nav-close');
const overlay = document.getElementById('overlay');

function openMobileNav() {
    mobileNav.classList.add('active');
    overlay.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeMobileNav() {
    mobileNav.classList.remove('active');
    overlay.classList.remove('active');
    document.body.style.overflow = 'auto';
}

if (mobileMenuBtn) {
    mobileMenuBtn.addEventListener('click', openMobileNav);
}

if (mobileNavClose) {
    mobileNavClose.addEventListener('click', closeMobileNav);
}

if (overlay) {
    overlay.addEventListener('click', closeMobileNav);
}

// Close mobile nav when clicking on links
const mobileNavLinks = document.querySelectorAll('.mobile-nav-link');
mobileNavLinks.forEach(link => {
    link.addEventListener('click', closeMobileNav);
});

// User dropdown functionality
const userDropdowns = document.querySelectorAll('.user-dropdown');
userDropdowns.forEach(dropdown => {
    dropdown.addEventListener('click', function(e) {
        e.stopPropagation();
        this.classList.toggle('active');
    });
});

// Close dropdowns when clicking outside
document.addEventListener('click', function(e) {
    userDropdowns.forEach(dropdown => {
        if (!dropdown.contains(e.target)) {
            dropdown.classList.remove('active');
        }
    });
});

// Image upload preview
function setupImagePreview(inputId, previewId) {
    const input = document.getElementById(inputId);
    const preview = document.getElementById(previewId);
    
    if (input && preview) {
        input.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                }
                reader.readAsDataURL(file);
            }
        });
    }
}

// Initialize image previews
setupImagePreview('property-images', 'image-preview');
setupImagePreview('profile-image', 'profile-preview');

// Form validation
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (form) {
        form.addEventListener('submit', function(e) {
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.classList.add('error');
                } else {
                    field.classList.remove('error');
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                alert('Please fill in all required fields.');
            }
        });
    }
}

// Initialize form validation
validateForm('login-form');
validateForm('register-form');
validateForm('property-form');
validateForm('booking-form');

// Password strength checker
function checkPasswordStrength(password) {
    const strength = {
        0: "Very Weak",
        1: "Weak",
        2: "Medium",
        3: "Strong",
        4: "Very Strong"
    };
    
    let score = 0;
    
    // Validate password length
    if (password.length >= 8) score++;
    
    // Validate lowercase letters
    if (password.match(/[a-z]+/)) score++;
    
    // Validate uppercase letters
    if (password.match(/[A-Z]+/)) score++;
    
    // Validate numbers
    if (password.match(/[0-9]+/)) score++;
    
    // Validate special characters
    if (password.match(/[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]+/)) score++;
    
    return {
        score: score,
        value: strength[score]
    };
}

// Initialize password strength indicator
const passwordInput = document.getElementById('password');
const strengthIndicator = document.getElementById('password-strength');

if (passwordInput && strengthIndicator) {
    passwordInput.addEventListener('input', function() {
        const strength = checkPasswordStrength(this.value);
        strengthIndicator.textContent = `Password Strength: ${strength.value}`;
        strengthIndicator.className = `password-strength strength-${strength.score}`;
    });
}

// Property search and filter
function initPropertySearch() {
    const searchInput = document.getElementById('property-search');
    const propertyCards = document.querySelectorAll('.property-card');
    const locationFilter = document.getElementById('location-filter');
    const typeFilter = document.getElementById('type-filter');
    const priceFilter = document.getElementById('price-filter');
    
    function filterProperties() {
        const searchTerm = searchInput ? searchInput.value.toLowerCase() : '';
        const locationValue = locationFilter ? locationFilter.value : '';
        const typeValue = typeFilter ? typeFilter.value : '';
        const priceValue = priceFilter ? priceFilter.value : '';
        
        propertyCards.forEach(card => {
            const title = card.querySelector('.property-title').textContent.toLowerCase();
            const location = card.querySelector('.property-location').textContent.toLowerCase();
            const type = card.dataset.type || '';
            const price = parseInt(card.dataset.price || 0);
            
            const matchesSearch = title.includes(searchTerm) || location.includes(searchTerm);
            const matchesLocation = !locationValue || location.includes(locationValue.toLowerCase());
            const matchesType = !typeValue || type === typeValue;
            const matchesPrice = !priceValue || (
                priceValue === 'low' ? price <= 10000 :
                priceValue === 'medium' ? price > 10000 && price <= 30000 :
                priceValue === 'high' ? price > 30000 : true
            );
            
            if (matchesSearch && matchesLocation && matchesType && matchesPrice) {
                card.style.display = 'block';
            } else {
                card.style.display = 'none';
            }
        });
    }
    
    if (searchInput) searchInput.addEventListener('input', filterProperties);
    if (locationFilter) locationFilter.addEventListener('change', filterProperties);
    if (typeFilter) typeFilter.addEventListener('change', filterProperties);
    if (priceFilter) priceFilter.addEventListener('change', filterProperties);
}

// Initialize property search
initPropertySearch();

// Booking date validation
function initDateValidation() {
    const checkinInput = document.getElementById('checkin-date');
    const checkoutInput = document.getElementById('checkout-date');
    
    if (checkinInput && checkoutInput) {
        const today = new Date().toISOString().split('T')[0];
        checkinInput.min = today;
        
        checkinInput.addEventListener('change', function() {
            checkoutInput.min = this.value;
            if (checkoutInput.value && checkoutInput.value < this.value) {
                checkoutInput.value = '';
            }
        });
    }
}

// Initialize date validation
initDateValidation();

// Smooth scrolling for anchor links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});

// Lazy loading for images
function initLazyLoading() {
    const lazyImages = document.querySelectorAll('img[data-src]');
    
    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.dataset.src;
                img.removeAttribute('data-src');
                observer.unobserve(img);
            }
        });
    });
    
    lazyImages.forEach(img => imageObserver.observe(img));
}

// Initialize lazy loading
initLazyLoading();

// Toast notifications
function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.innerHTML = `
        <div class="toast-content">
            <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>
            <span>${message}</span>
        </div>
        <button class="toast-close">&times;</button>
    `;
    
    document.body.appendChild(toast);
    
    // Animate in
    setTimeout(() => toast.classList.add('show'), 100);
    
    // Auto remove after 5 seconds
    setTimeout(() => hideToast(toast), 5000);
    
    // Close button
    toast.querySelector('.toast-close').addEventListener('click', () => hideToast(toast));
}

function hideToast(toast) {
    toast.classList.remove('show');
    setTimeout(() => toast.remove(), 300);
}

// Add toast styles
const toastStyles = document.createElement('style');
toastStyles.textContent = `
    .toast {
        position: fixed;
        top: 20px;
        right: 20px;
        background: white;
        border-left: 4px solid #27ae60;
        border-radius: 4px;
        padding: 1rem;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        transform: translateX(100%);
        transition: transform 0.3s ease;
        z-index: 10000;
        min-width: 300px;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    
    .toast.show {
        transform: translateX(0);
    }
    
    .toast-error {
        border-left-color: #e74c3c;
    }
    
    .toast-warning {
        border-left-color: #f39c12;
    }
    
    .toast-info {
        border-left-color: #3498db;
    }
    
    .toast-content {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .toast-close {
        background: none;
        border: none;
        font-size: 1.2rem;
        cursor: pointer;
        padding: 0;
        margin-left: 1rem;
    }
    
    @media (max-width: 768px) {
        .toast {
            left: 20px;
            right: 20px;
            min-width: auto;
        }
    }
`;

document.head.appendChild(toastStyles);

// Initialize all functionality when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Initialize all components
    initPropertySearch();
    initDateValidation();
    initLazyLoading();
    
    // Add loading state to forms
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function() {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
                
                // Re-enable after 5 seconds (in case of error)
                setTimeout(() => {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = submitBtn.dataset.originalText || 'Submit';
                }, 5000);
            }
        });
    });
    
    // Save original button text
    document.querySelectorAll('button[type="submit"]').forEach(btn => {
        btn.dataset.originalText = btn.innerHTML;
    });
});

// Error handling for images
document.querySelectorAll('img').forEach(img => {
    img.addEventListener('error', function() {
        if (this.src.includes('properties/')) {
            this.src = '../assets/images/default-property.jpg';
        } else if (this.src.includes('profile') || this.src.includes('avatar')) {
            this.src = '../assets/images/default-avatar.jpg';
        }
    });
});

// Enhanced dropdown functionality for all screen sizes
function initEnhancedDropdowns() {
    const dropdowns = document.querySelectorAll('.dropdown');
    
    dropdowns.forEach(dropdown => {
        const toggle = dropdown.querySelector('.dropdown-toggle');
        const menu = dropdown.querySelector('.dropdown-menu');
        
        if (toggle && menu) {
            // Click handler
            toggle.addEventListener('click', function(e) {
                e.stopPropagation();
                menu.classList.toggle('show');
            });
            
            // Close when clicking outside
            document.addEventListener('click', function() {
                menu.classList.remove('show');
            });
            
            // Prevent closing when clicking inside menu
            menu.addEventListener('click', function(e) {
                e.stopPropagation();
            });
        }
    });
}

// Initialize enhanced dropdowns
initEnhancedDropdowns();

// Keyboard navigation
document.addEventListener('keydown', function(e) {
    // Close dropdowns on Escape
    if (e.key === 'Escape') {
        document.querySelectorAll('.dropdown-menu.show').forEach(menu => {
            menu.classList.remove('show');
        });
        
        closeMobileNav();
    }
    
    // Submit form on Ctrl+Enter
    if (e.ctrlKey && e.key === 'Enter') {
        const focusedElement = document.activeElement;
        if (focusedElement && focusedElement.form) {
            focusedElement.form.submit();
        }
    }
});

// Touch device detection
const isTouchDevice = 'ontouchstart' in window || navigator.maxTouchPoints > 0;
if (isTouchDevice) {
    document.body.classList.add('touch-device');
} else {
    document.body.classList.add('no-touch-device');
}

// Performance optimization: Debounce function
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Debounce search input
const searchInput = document.getElementById('property-search');
if (searchInput) {
    searchInput.addEventListener('input', debounce(function() {
        initPropertySearch();
    }, 300));
}