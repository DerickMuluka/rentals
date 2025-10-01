// Custom JavaScript for Kenya Coastal Student Housing

// Initialize everything when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Initialize image galleries
    initImageGalleries();
    
    // Initialize date pickers
    initDatePickers();
    
    // Initialize form validation
    initFormValidation();
    
    // Initialize mobile navigation
    initMobileNavigation();
    
    // Initialize search functionality
    initSearchFunctionality();
    
    // Initialize property filters
    initPropertyFilters();
    
    // Initialize booking calculators
    initBookingCalculators();
    
    // Initialize favorite functionality
    initFavoriteFunctionality();
    
    // Initialize review functionality
    initReviewFunctionality();
});

// Image Galleries
function initImageGalleries() {
    // Property image galleries
    const propertyGalleries = document.querySelectorAll('.property-gallery');
    
    propertyGalleries.forEach(gallery => {
        const mainImage = gallery.querySelector('.main-image');
        const thumbnails = gallery.querySelectorAll('.thumbnail');
        
        if (mainImage && thumbnails.length > 0) {
            thumbnails.forEach(thumbnail => {
                thumbnail.addEventListener('click', function() {
                    // Update main image
                    mainImage.src = this.src;
                    
                    // Add active class to clicked thumbnail
                    thumbnails.forEach(t => t.classList.remove('active'));
                    this.classList.add('active');
                });
            });
        }
    });
    
    // University image galleries
    const universityGalleries = document.querySelectorAll('.university-gallery');
    
    universityGalleries.forEach(gallery => {
        const mainImage = gallery.querySelector('.main-image');
        const thumbnails = gallery.querySelectorAll('.thumbnail');
        
        if (mainImage && thumbnails.length > 0) {
            thumbnails.forEach(thumbnail => {
                thumbnail.addEventListener('click', function() {
                    // Update main image
                    mainImage.src = this.src;
                    
                    // Add active class to clicked thumbnail
                    thumbnails.forEach(t => t.classList.remove('active'));
                    this.classList.add('active');
                });
            });
        }
    });
}

// Date Pickers
function initDatePickers() {
    const dateInputs = document.querySelectorAll('input[type="date"]');
    
    dateInputs.forEach(input => {
        // Set min date to today
        const today = new Date().toISOString().split('T')[0];
        input.min = today;
        
        // Add custom styling
        input.addEventListener('focus', function() {
            this.classList.add('focused');
        });
        
        input.addEventListener('blur', function() {
            this.classList.remove('focused');
        });
    });
    
    // Booking date validation
    const bookingForms = document.querySelectorAll('form.booking-form');
    
    bookingForms.forEach(form => {
        const startDate = form.querySelector('input[name="start_date"]');
        const endDate = form.querySelector('input[name="end_date"]');
        
        if (startDate && endDate) {
            startDate.addEventListener('change', function() {
                // Set end date min to start date + 1 day
                const startDateValue = new Date(this.value);
                startDateValue.setDate(startDateValue.getDate() + 1);
                endDate.min = startDateValue.toISOString().split('T')[0];
                
                // If end date is before new min, clear it
                if (endDate.value && new Date(endDate.value) <= new Date(this.value)) {
                    endDate.value = '';
                }
            });
            
            endDate.addEventListener('change', function() {
                // Validate end date is after start date
                if (startDate.value && new Date(this.value) <= new Date(startDate.value)) {
                    this.setCustomValidity('End date must be after start date');
                } else {
                    this.setCustomValidity('');
                }
            });
        }
    });
}

// Form Validation
function initFormValidation() {
    const forms = document.querySelectorAll('form:not(.no-validation)');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!validateForm(this)) {
                e.preventDefault();
                showToast('Please fill all required fields correctly', 'error');
            }
        });
        
        // Add real-time validation
        const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
        
        inputs.forEach(input => {
            input.addEventListener('blur', function() {
                validateField(this);
            });
            
            input.addEventListener('input', function() {
                if (this.classList.contains('error')) {
                    validateField(this);
                }
            });
        });
    });
}

function validateForm(form) {
    let isValid = true;
    const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
    
    inputs.forEach(input => {
        if (!validateField(input)) {
            isValid = false;
        }
    });
    
    // Special validation for password confirmation
    const password = form.querySelector('input[name="password"]');
    const confirmPassword = form.querySelector('input[name="confirm_password"]');
    
    if (password && confirmPassword && password.value !== confirmPassword.value) {
        confirmPassword.classList.add('error');
        showToast('Passwords do not match', 'error');
        isValid = false;
    }
    
    return isValid;
}

function validateField(field) {
    let isValid = true;
    let errorMessage = '';
    
    // Clear previous error
    field.classList.remove('error');
    
    // Check if field is empty
    if (!field.value.trim()) {
        isValid = false;
        errorMessage = 'This field is required';
    }
    
    // Email validation
    if (field.type === 'email' && field.value) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(field.value)) {
            isValid = false;
            errorMessage = 'Please enter a valid email address';
        }
    }
    
    // Phone validation
    if (field.name === 'phone' && field.value) {
        const phoneRegex = /^[+]*[(]{0,1}[0-9]{1,4}[)]{0,1}[-\s\./0-9]*$/;
        if (!phoneRegex.test(field.value)) {
            isValid = false;
            errorMessage = 'Please enter a valid phone number';
        }
    }
    
    // Password validation
    if (field.type === 'password' && field.value) {
        if (field.value.length < 6) {
            isValid = false;
            errorMessage = 'Password must be at least 6 characters long';
        }
    }
    
    // Show error if invalid
    if (!isValid) {
        field.classList.add('error');
        
        // Show error message
        let errorElement = field.nextElementSibling;
        if (!errorElement || !errorElement.classList.contains('error-message')) {
            errorElement = document.createElement('div');
            errorElement.className = 'error-message';
            field.parentNode.insertBefore(errorElement, field.nextSibling);
        }
        errorElement.textContent = errorMessage;
    } else {
        // Remove error message
        const errorElement = field.nextElementSibling;
        if (errorElement && errorElement.classList.contains('error-message')) {
            errorElement.remove();
        }
    }
    
    return isValid;
}

// Mobile Navigation
function initMobileNavigation() {
    const mobileMenuBtn = document.getElementById('mobile-menu-btn');
    const mobileNav = document.getElementById('mobile-nav');
    const mobileNavClose = document.getElementById('mobile-nav-close');
    const overlay = document.getElementById('overlay');
    
    function toggleMobileMenu() {
        mobileNav.classList.toggle('active');
        overlay.classList.toggle('active');
        document.body.classList.toggle('no-scroll');
    }
    
    if (mobileMenuBtn) {
        mobileMenuBtn.addEventListener('click', toggleMobileMenu);
    }
    
    if (mobileNavClose) {
        mobileNavClose.addEventListener('click', toggleMobileMenu);
    }
    
    if (overlay) {
        overlay.addEventListener('click', toggleMobileMenu);
    }
    
    // Close mobile menu when clicking on links
    const mobileNavLinks = document.querySelectorAll('.mobile-nav-link');
    mobileNavLinks.forEach(link => {
        link.addEventListener('click', toggleMobileMenu);
    });
    
    // Handle window resize
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768 && mobileNav.classList.contains('active')) {
            toggleMobileMenu();
        }
    });
}

// Search Functionality
function initSearchFunctionality() {
    // Live search for properties
    const searchInputs = document.querySelectorAll('.live-search');
    
    searchInputs.forEach(input => {
        input.addEventListener('input', debounce(function() {
            const searchValue = this.value.toLowerCase();
            const items = this.closest('.search-container').querySelectorAll('.search-item');
            
            items.forEach(item => {
                const text = item.textContent.toLowerCase();
                if (text.includes(searchValue)) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            });
        }, 300));
    });
    
    // Advanced search filters
    const filterToggles = document.querySelectorAll('.filter-toggle');
    
    filterToggles.forEach(toggle => {
        toggle.addEventListener('click', function() {
            const filters = this.closest('.filters-section').querySelector('.filters-content');
            filters.classList.toggle('active');
            
            const icon = this.querySelector('i');
            if (filters.classList.contains('active')) {
                icon.classList.remove('fa-chevron-down');
                icon.classList.add('fa-chevron-up');
            } else {
                icon.classList.remove('fa-chevron-up');
                icon.classList.add('fa-chevron-down');
            }
        });
    });
}

// Property Filters
function initPropertyFilters() {
    const filterForms = document.querySelectorAll('.filter-form');
    
    filterForms.forEach(form => {
        // Handle range inputs
        const rangeInputs = form.querySelectorAll('input[type="range"]');
        
        rangeInputs.forEach(input => {
            const valueDisplay = input.nextElementSibling;
            if (valueDisplay && valueDisplay.classList.contains('range-value')) {
                valueDisplay.textContent = input.value;
                
                input.addEventListener('input', function() {
                    valueDisplay.textContent = this.value;
                });
            }
        });
        
        // Handle filter submission
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            applyFilters(this);
        });
        
        // Handle filter reset
        const resetButtons = form.querySelectorAll('.reset-filters');
        resetButtons.forEach(button => {
            button.addEventListener('click', function() {
                form.reset();
                
                // Reset range value displays
                const rangeInputs = form.querySelectorAll('input[type="range"]');
                rangeInputs.forEach(input => {
                    const valueDisplay = input.nextElementSibling;
                    if (valueDisplay && valueDisplay.classList.contains('range-value')) {
                        valueDisplay.textContent = input.value;
                    }
                });
                
                applyFilters(form);
            });
        });
    });
}

function applyFilters(form) {
    const formData = new FormData(form);
    const params = new URLSearchParams();
    
    // Add all form data to URL params
    for (const [key, value] of formData.entries()) {
        if (value) {
            params.append(key, value);
        }
    }
    
    // Redirect to filtered page
    window.location.href = `${form.action}?${params.toString()}`;
}

// Booking Calculators
function initBookingCalculators() {
    const bookingForms = document.querySelectorAll('.booking-form');
    
    bookingForms.forEach(form => {
        const startDate = form.querySelector('input[name="start_date"]');
        const endDate = form.querySelector('input[name="end_date"]');
        const pricePerMonth = parseFloat(form.dataset.price) || 0;
        const totalAmountDisplay = form.querySelector('.total-amount');
        
        if (startDate && endDate && totalAmountDisplay && pricePerMonth > 0) {
            function calculateTotal() {
                if (startDate.value && endDate.value) {
                    const start = new Date(startDate.value);
                    const end = new Date(endDate.value);
                    
                    if (end > start) {
                        const nights = Math.ceil((end - start) / (1000 * 60 * 60 * 24));
                        const totalAmount = (pricePerMonth / 30) * nights; // Approximate daily rate
                        
                        totalAmountDisplay.textContent = `KES ${totalAmount.toLocaleString('en-KE', {maximumFractionDigits: 2})}`;
                    }
                }
            }
            
            startDate.addEventListener('change', calculateTotal);
            endDate.addEventListener('change', calculateTotal);
            
            // Initial calculation
            calculateTotal();
        }
    });
}

// Favorite Functionality
function initFavoriteFunctionality() {
    const favoriteButtons = document.querySelectorAll('.favorite-btn');
    
    favoriteButtons.forEach(button => {
        button.addEventListener('click', function() {
            const propertyId = this.dataset.propertyId;
            const isFavorite = this.classList.contains('active');
            
            // Toggle visual state
            this.classList.toggle('active');
            
            // Update icon
            const icon = this.querySelector('i');
            if (this.classList.contains('active')) {
                icon.classList.remove('far');
                icon.classList.add('fas');
            } else {
                icon.classList.remove('fas');
                icon.classList.add('far');
            }
            
            // Send AJAX request to update favorites
            toggleFavorite(propertyId, !isFavorite);
        });
    });
}

function toggleFavorite(propertyId, addToFavorites) {
    // This would typically make an AJAX request to the server
    // For now, we'll just show a notification
    
    const message = addToFavorites ? 'Added to favorites' : 'Removed from favorites';
    showToast(message, 'success');
    
    // Example AJAX request (commented out):
    /*
    fetch('ajax/favorite.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            property_id: propertyId,
            action: addToFavorites ? 'add' : 'remove'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(message, 'success');
        } else {
            showToast('Error updating favorites', 'error');
            // Revert visual state
            const button = document.querySelector(`.favorite-btn[data-property-id="${propertyId}"]`);
            button.classList.toggle('active');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Error updating favorites', 'error');
    });
    */
}

// Review Functionality
function initReviewFunctionality() {
    // Star rating system
    const ratingInputs = document.querySelectorAll('.rating-input');
    
    ratingInputs.forEach(container => {
        const stars = container.querySelectorAll('input[type="radio"]');
        const starLabels = container.querySelectorAll('label');
        
        stars.forEach((star, index) => {
            star.addEventListener('change', function() {
                // Update visual rating
                starLabels.forEach((label, i) => {
                    if (i <= index) {
                        label.classList.add('active');
                    } else {
                        label.classList.remove('active');
                    }
                });
            });
            
            // Hover effect
            star.addEventListener('mouseenter', function() {
                starLabels.forEach((label, i) => {
                    if (i <= index) {
                        label.classList.add('hover');
                    } else {
                        label.classList.remove('hover');
                    }
                });
            });
            
            star.addEventListener('mouseleave', function() {
                starLabels.forEach(label => {
                    label.classList.remove('hover');
                });
                
                // Restore actual rating
                const checkedStar = container.querySelector('input[type="radio"]:checked');
                if (checkedStar) {
                    const checkedIndex = Array.from(stars).indexOf(checkedStar);
                    starLabels.forEach((label, i) => {
                        if (i <= checkedIndex) {
                            label.classList.add('active');
                        } else {
                            label.classList.remove('active');
                        }
                    });
                }
            });
        });
    });
    
    // Review form submission
    const reviewForms = document.querySelectorAll('.review-form');
    
    reviewForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const rating = form.querySelector('input[name="rating"]:checked');
            const comment = form.querySelector('textarea[name="comment"]');
            
            if (!rating || !comment.value.trim()) {
                showToast('Please provide both a rating and comment', 'error');
                return;
            }
            
            // Simulate form submission
            const formData = new FormData(this);
            
            // Example AJAX request (commented out):
            /*
            fetch(this.action, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Review submitted successfully', 'success');
                    this.reset();
                    // Reload reviews
                    loadReviews();
                } else {
                    showToast('Error submitting review', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Error submitting review', 'error');
            });
            */
            
            // For demo purposes, just show success message
            showToast('Review submitted successfully', 'success');
            this.reset();
        });
    });
}

// Utility Functions
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

function showToast(message, type = 'success') {
    // Create toast element
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.innerHTML = `
        <div class="toast-content">
            <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>
            <span>${message}</span>
        </div>
        <button class="toast-close">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    // Add to page
    document.body.appendChild(toast);
    
    // Show toast
    setTimeout(() => {
        toast.classList.add('show');
    }, 100);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        hideToast(toast);
    }, 5000);
    
    // Close button
    const closeButton = toast.querySelector('.toast-close');
    closeButton.addEventListener('click', () => {
        hideToast(toast);
    });
}

function hideToast(toast) {
    toast.classList.remove('show');
    setTimeout(() => {
        if (toast.parentNode) {
            toast.parentNode.removeChild(toast);
        }
    }, 300);
}

// Export functions for global use
window.HRS = {
    showToast,
    debounce,
    validateForm,
    validateField
};