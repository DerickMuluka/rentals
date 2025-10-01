// Admin Panel JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Initialize admin functionality
    initAdminSidebar();
    initDataTables();
    initAdminForms();
    initAdminModals();
    initAdminFilters();
});

// Admin Sidebar
function initAdminSidebar() {
    const sidebarLinks = document.querySelectorAll('.sidebar-link');
    const currentPage = window.location.pathname.split('/').pop();
    
    // Mark current page as active
    sidebarLinks.forEach(link => {
        const href = link.getAttribute('href');
        if (href === currentPage || (currentPage === '' && href === 'index.php')) {
            link.classList.add('active');
        }
        
        link.addEventListener('click', function(e) {
            // Remove active class from all links
            sidebarLinks.forEach(l => l.classList.remove('active'));
            // Add active class to clicked link
            this.classList.add('active');
        });
    });
    
    // Mobile sidebar toggle
    const sidebarToggle = document.getElementById('sidebar-toggle');
    const adminSidebar = document.querySelector('.admin-sidebar');
    
    if (sidebarToggle && adminSidebar) {
        sidebarToggle.addEventListener('click', function() {
            adminSidebar.classList.toggle('active');
        });
    }
}

// Data Tables
function initDataTables() {
    const dataTables = document.querySelectorAll('.data-table');
    
    dataTables.forEach(table => {
        // Add sorting functionality
        const headers = table.querySelectorAll('th[data-sort]');
        
        headers.forEach(header => {
            header.style.cursor = 'pointer';
            header.addEventListener('click', function() {
                const column = this.cellIndex;
                const sortDirection = this.getAttribute('data-sort-direction') || 'asc';
                const newSortDirection = sortDirection === 'asc' ? 'desc' : 'asc';
                
                // Update all headers
                headers.forEach(h => {
                    h.removeAttribute('data-sort-direction');
                    h.querySelector('.sort-icon')?.remove();
                });
                
                // Set current header
                this.setAttribute('data-sort-direction', newSortDirection);
                
                // Add sort icon
                const sortIcon = document.createElement('i');
                sortIcon.className = `sort-icon fas fa-chevron-${newSortDirection === 'asc' ? 'up' : 'down'}`;
                sortIcon.style.marginLeft = '0.5rem';
                this.appendChild(sortIcon);
                
                // Sort table
                sortTable(table, column, newSortDirection);
            });
        });
        
        // Add search functionality
        const searchContainer = document.createElement('div');
        searchContainer.className = 'table-search';
        searchContainer.innerHTML = `
            <div class="search-input">
                <input type="text" placeholder="Search..." class="form-input">
                <i class="fas fa-search"></i>
            </div>
        `;
        
        table.parentNode.insertBefore(searchContainer, table);
        
        const searchInput = searchContainer.querySelector('input');
        searchInput.addEventListener('input', debounce(function() {
            filterTable(table, this.value);
        }, 300));
        
        // Add pagination
        addPagination(table);
    });
}

function sortTable(table, column, direction) {
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    
    rows.sort((a, b) => {
        const aValue = a.cells[column].textContent.trim();
        const bValue = b.cells[column].textContent.trim();
        
        // Try to convert to number if possible
        const aNum = parseFloat(aValue.replace(/[^\d.]/g, ''));
        const bNum = parseFloat(bValue.replace(/[^\d.]/g, ''));
        
        if (!isNaN(aNum) && !isNaN(bNum)) {
            return direction === 'asc' ? aNum - bNum : bNum - aNum;
        }
        
        // Otherwise, sort as string
        return direction === 'asc' 
            ? aValue.localeCompare(bValue)
            : bValue.localeCompare(aValue);
    });
    
    // Remove existing rows
    while (tbody.firstChild) {
        tbody.removeChild(tbody.firstChild);
    }
    
    // Add sorted rows
    rows.forEach(row => tbody.appendChild(row));
}

function filterTable(table, searchText) {
    const tbody = table.querySelector('tbody');
    const rows = tbody.querySelectorAll('tr');
    const searchTerm = searchText.toLowerCase();
    
    rows.forEach(row => {
        const rowText = row.textContent.toLowerCase();
        if (rowText.includes(searchTerm)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

function addPagination(table) {
    const tbody = table.querySelector('tbody');
    const rows = tbody.querySelectorAll('tr');
    const rowsPerPage = 10;
    const pageCount = Math.ceil(rows.length / rowsPerPage);
    
    if (pageCount <= 1) return;
    
    // Create pagination container
    const paginationContainer = document.createElement('div');
    paginationContainer.className = 'table-pagination';
    
    // Create pagination controls
    let currentPage = 1;
    
    function updatePagination() {
        paginationContainer.innerHTML = '';
        
        // Previous button
        const prevButton = document.createElement('button');
        prevButton.className = 'pagination-btn';
        prevButton.innerHTML = '<i class="fas fa-chevron-left"></i>';
        prevButton.disabled = currentPage === 1;
        prevButton.addEventListener('click', () => {
            if (currentPage > 1) {
                currentPage--;
                updatePagination();
                showPage(currentPage);
            }
        });
        paginationContainer.appendChild(prevButton);
        
        // Page numbers
        for (let i = 1; i <= pageCount; i++) {
            const pageButton = document.createElement('button');
            pageButton.className = `pagination-btn ${i === currentPage ? 'active' : ''}`;
            pageButton.textContent = i;
            pageButton.addEventListener('click', () => {
                currentPage = i;
                updatePagination();
                showPage(currentPage);
            });
            paginationContainer.appendChild(pageButton);
        }
        
        // Next button
        const nextButton = document.createElement('button');
        nextButton.className = 'pagination-btn';
        nextButton.innerHTML = '<i class="fas fa-chevron-right"></i>';
        nextButton.disabled = currentPage === pageCount;
        nextButton.addEventListener('click', () => {
            if (currentPage < pageCount) {
                currentPage++;
                updatePagination();
                showPage(currentPage);
            }
        });
        paginationContainer.appendChild(nextButton);
    }
    
    function showPage(page) {
        const start = (page - 1) * rowsPerPage;
        const end = start + rowsPerPage;
        
        rows.forEach((row, index) => {
            if (index >= start && index < end) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }
    
    // Add pagination to table
    table.parentNode.insertBefore(paginationContainer, table.nextSibling);
    
    // Initialize pagination
    updatePagination();
    showPage(currentPage);
}

// Admin Forms
function initAdminForms() {
    // Image preview for file inputs
    const fileInputs = document.querySelectorAll('input[type="file"][data-preview]');
    
    fileInputs.forEach(input => {
        const previewId = input.getAttribute('data-preview');
        const preview = document.getElementById(previewId);
        
        if (preview) {
            input.addEventListener('change', function() {
                const file = this.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        if (preview.tagName === 'IMG') {
                            preview.src = e.target.result;
                        } else {
                            preview.style.backgroundImage = `url(${e.target.result})`;
                        }
                        preview.style.display = 'block';
                    };
                    reader.readAsDataURL(file);
                }
            });
        }
    });
    
    // Rich text editors
    const textEditors = document.querySelectorAll('.rich-text-editor');
    
    textEditors.forEach(editor => {
        // Simple rich text functionality
        const toolbar = document.createElement('div');
        toolbar.className = 'editor-toolbar';
        toolbar.innerHTML = `
            <button type="button" data-command="bold"><i class="fas fa-bold"></i></button>
            <button type="button" data-command="italic"><i class="fas fa-italic"></i></button>
            <button type="button" data-command="underline"><i class="fas fa-underline"></i></button>
            <button type="button" data-command="insertUnorderedList"><i class="fas fa-list-ul"></i></button>
            <button type="button" data-command="insertOrderedList"><i class="fas fa-list-ol"></i></button>
        `;
        
        editor.parentNode.insertBefore(toolbar, editor);
        
        toolbar.querySelectorAll('button').forEach(button => {
            button.addEventListener('click', function() {
                const command = this.getAttribute('data-command');
                document.execCommand(command, false, null);
                editor.focus();
            });
        });
    });
    
    // Form validation
    const adminForms = document.querySelectorAll('.admin-form');
    
    adminForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!validateAdminForm(this)) {
                e.preventDefault();
                HRS.showToast('Please fill all required fields correctly', 'error');
            }
        });
    });
}

function validateAdminForm(form) {
    let isValid = true;
    const requiredFields = form.querySelectorAll('[required]');
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.classList.add('error');
            isValid = false;
            
            // Add error message
            let errorElement = field.nextElementSibling;
            if (!errorElement || !errorElement.classList.contains('error-message')) {
                errorElement = document.createElement('div');
                errorElement.className = 'error-message';
                field.parentNode.insertBefore(errorElement, field.nextSibling);
            }
            errorElement.textContent = 'This field is required';
        } else {
            field.classList.remove('error');
            const errorElement = field.nextElementSibling;
            if (errorElement && errorElement.classList.contains('error-message')) {
                errorElement.remove();
            }
        }
    });
    
    return isValid;
}

// Admin Modals
function initAdminModals() {
    // Modal triggers
    const modalTriggers = document.querySelectorAll('[data-modal]');
    
    modalTriggers.forEach(trigger => {
        trigger.addEventListener('click', function() {
            const modalId = this.getAttribute('data-modal');
            const modal = document.getElementById(modalId);
            
            if (modal) {
                openModal(modal);
            }
        });
    });
    
    // Modal close buttons
    const modalCloses = document.querySelectorAll('.modal-close');
    
    modalCloses.forEach(close => {
        close.addEventListener('click', function() {
            const modal = this.closest('.modal');
            closeModal(modal);
        });
    });
    
    // Close modal when clicking outside
    const modals = document.querySelectorAll('.modal');
    
    modals.forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal(this);
            }
        });
    });
    
    // Close modal with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const openModal = document.querySelector('.modal.active');
            if (openModal) {
                closeModal(openModal);
            }
        }
    });
}

function openModal(modal) {
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeModal(modal) {
    modal.classList.remove('active');
    document.body.style.overflow = '';
}

// Admin Filters
function initAdminFilters() {
    const filterToggles = document.querySelectorAll('.filter-toggle');
    
    filterToggles.forEach(toggle => {
        toggle.addEventListener('click', function() {
            const filterPanel = this.nextElementSibling;
            filterPanel.classList.toggle('active');
            
            const icon = this.querySelector('i');
            if (filterPanel.classList.contains('active')) {
                icon.classList.remove('fa-chevron-down');
                icon.classList.add('fa-chevron-up');
            } else {
                icon.classList.remove('fa-chevron-up');
                icon.classList.add('fa-chevron-down');
            }
        });
    });
    
    // Date range filters
    const dateRangeFilters = document.querySelectorAll('.date-range-filter');
    
    dateRangeFilters.forEach(filter => {
        const startDate = filter.querySelector('[name="start_date"]');
        const endDate = filter.querySelector('[name="end_date"]');
        
        if (startDate && endDate) {
            startDate.addEventListener('change', function() {
                endDate.min = this.value;
            });
            
            endDate.addEventListener('change', function() {
                if (startDate.value && new Date(this.value) < new Date(startDate.value)) {
                    this.setCustomValidity('End date must be after start date');
                } else {
                    this.setCustomValidity('');
                }
            });
        }
    });
    
    // Range sliders
    const rangeSliders = document.querySelectorAll('input[type="range"]');
    
    rangeSliders.forEach(slider => {
        const valueDisplay = slider.nextElementSibling;
        if (valueDisplay && valueDisplay.classList.contains('range-value')) {
            valueDisplay.textContent = slider.value;
            
            slider.addEventListener('input', function() {
                valueDisplay.textContent = this.value;
            });
        }
    });
}

// Export admin functions
window.Admin = {
    openModal,
    closeModal,
    validateAdminForm,
    sortTable,
    filterTable
};