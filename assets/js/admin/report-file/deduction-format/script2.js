let currentPage = 1;
let recordsPerPage = 10;
let allRows = [];
let filteredRows = [];

document.addEventListener('DOMContentLoaded', function() {
    // Show loading animation briefly, then initialize
    setTimeout(() => {
        hideLoadingRows();
        initializePagination();
        setupEventListeners();
    }, 800); // Adjust timing as needed
});

function hideLoadingRows() {
    // Hide loading placeholders
    const loadingRows = document.querySelectorAll('.loading-row');
    loadingRows.forEach(row => row.style.display = 'none');
    
    // Show actual data rows
    const dataRows = document.querySelectorAll('.data-row');
    dataRows.forEach(row => row.style.display = '');
}

function showLoadingRows() {
    // Show loading placeholders
    const loadingRows = document.querySelectorAll('.loading-row');
    loadingRows.forEach(row => row.style.display = '');
    
    // Hide actual data rows
    const dataRows = document.querySelectorAll('.data-row');
    dataRows.forEach(row => row.style.display = 'none');
}

function initializePagination() {
    const table = document.getElementById('users-table');
    const tbody = table.querySelector('tbody');
    allRows = Array.from(tbody.querySelectorAll('.data-row')).filter(row => 
        !row.querySelector('td[colspan]')
    );
    filteredRows = [...allRows];
    
    displayPage(1);
    updatePaginationControls();
    updatePaginationInfo();
    calculateTotalAmount();
}

function setupEventListeners() {
    // Records per page dropdown
    document.getElementById('recordsPerPage').addEventListener('change', function() {
        showLoadingRows();
        setTimeout(() => {
            recordsPerPage = this.value === 'all' ? filteredRows.length : parseInt(this.value);
            currentPage = 1;
            hideLoadingRows();
            displayPage(1);
            updatePaginationControls();
            updatePaginationInfo();
        }, 300);
    });

    // Search functionality
    document.getElementById('search_input').addEventListener('input', function() {
        updateFilterVisualIndicator();
        showLoadingRows();
        setTimeout(() => {
            hideLoadingRows();
            filterTable();
        }, 300);
    });

    // Setup date validation
    setupDateValidation();

    // Clear filters
    document.getElementById('clearFilters').addEventListener('click', function() {
        document.getElementById('search_input').value = '';
        document.getElementById('start_date').value = '';
        document.getElementById('end_date').value = '';
        
        updateFilterVisualIndicator();
        showLoadingRows();
        setTimeout(() => {
            hideLoadingRows();
            filterTable();
        }, 300);
    });

    // Pagination controls
    document.getElementById('prevPage').addEventListener('click', function(e) {
        e.preventDefault();
        if (currentPage > 1) {
            showLoadingRows();
            setTimeout(() => {
                currentPage--;
                hideLoadingRows();
                displayPage(currentPage);
                updatePaginationControls();
                updatePaginationInfo();
            }, 200);
        }
    });

    document.getElementById('nextPage').addEventListener('click', function(e) {
        e.preventDefault();
        const totalPages = Math.ceil(filteredRows.length / recordsPerPage);
        if (currentPage < totalPages) {
            showLoadingRows();
            setTimeout(() => {
                currentPage++;
                hideLoadingRows();
                displayPage(currentPage);
                updatePaginationControls();
                updatePaginationInfo();
            }, 200);
        }
    });
}

function updateFilterVisualIndicator() {
    const searchInput = document.getElementById('search_input');
    const startDateInput = document.getElementById('start_date');
    const endDateInput = document.getElementById('end_date');
    
    // Add/remove visual indicators
    searchInput.classList.toggle('filter-active', searchInput.value !== '');
    startDateInput.classList.toggle('filter-active', startDateInput.value !== '');
    endDateInput.classList.toggle('filter-active', endDateInput.value !== '');
}

function displayPage(page) {
    const table = document.getElementById('users-table');
    const tbody = table.querySelector('tbody');
    
    // Hide all rows first
    allRows.forEach(row => row.style.display = 'none');
    
    if (filteredRows.length === 0) {
        // Show no records message
        let noRecordsRow = tbody.querySelector('tr td[colspan]');
        if (!noRecordsRow) {
            const row = document.createElement('tr');
            row.innerHTML = '<td colspan="6" class="text-center text-muted py-4">No records found</td>';
            tbody.appendChild(row);
        } else {
            noRecordsRow.parentElement.style.display = '';
        }
        return;
    } else {
        // Hide no records message if it exists
        const noRecordsRow = tbody.querySelector('tr td[colspan]');
        if (noRecordsRow) {
            noRecordsRow.parentElement.style.display = 'none';
        }
    }

    const startIndex = (page - 1) * recordsPerPage;
    const endIndex = recordsPerPage === filteredRows.length ? filteredRows.length : Math.min(startIndex + recordsPerPage, filteredRows.length);
    
    for (let i = startIndex; i < endIndex; i++) {
        if (filteredRows[i]) {
            filteredRows[i].style.display = '';
        }
    }
}

function updatePaginationControls() {
    const totalPages = Math.ceil(filteredRows.length / recordsPerPage);
    const paginationControls = document.getElementById('paginationControls');
    const prevButton = document.getElementById('prevPage');
    const nextButton = document.getElementById('nextPage');
    
    // Remove existing page number buttons
    const existingPageButtons = paginationControls.querySelectorAll('.page-number');
    existingPageButtons.forEach(button => button.remove());
    
    // Update prev/next button states
    prevButton.classList.toggle('disabled', currentPage === 1);
    nextButton.classList.toggle('disabled', currentPage === totalPages || totalPages === 0);
    
    // Add page number buttons
    if (totalPages > 1) {
        const maxVisiblePages = 5;
        let startPage = Math.max(1, currentPage - Math.floor(maxVisiblePages / 2));
        let endPage = Math.min(totalPages, startPage + maxVisiblePages - 1);
        
        if (endPage - startPage + 1 < maxVisiblePages) {
            startPage = Math.max(1, endPage - maxVisiblePages + 1);
        }
        
        for (let i = startPage; i <= endPage; i++) {
            const pageItem = document.createElement('li');
            pageItem.className = `page-item page-number ${i === currentPage ? 'active' : ''}`;
            
            const pageLink = document.createElement('a');
            pageLink.className = 'page-link';
            pageLink.href = '#';
            pageLink.textContent = i;
            
            pageLink.addEventListener('click', function(e) {
                e.preventDefault();
                currentPage = i;
                displayPage(currentPage);
                updatePaginationControls();
                updatePaginationInfo();
            });
            
            pageItem.appendChild(pageLink);
            
            // Insert before the next button
            paginationControls.insertBefore(pageItem, nextButton);
        }
    }
}

function updatePaginationInfo() {
    const startRecord = filteredRows.length === 0 ? 0 : (currentPage - 1) * recordsPerPage + 1;
    const endRecord = Math.min(currentPage * recordsPerPage, filteredRows.length);
    const totalRecords = filteredRows.length;
    
    document.getElementById('startRecord').textContent = startRecord;
    document.getElementById('endRecord').textContent = endRecord;
    document.getElementById('totalRecords').textContent = totalRecords;
}

function filterTable() {
    const searchValue = document.getElementById('search_input').value.toLowerCase();
    const startDateInput = document.getElementById('start_date');
    const endDateInput = document.getElementById('end_date');
    
    // Don't filter if there are validation errors
    if (startDateInput.classList.contains('is-invalid') || endDateInput.classList.contains('is-invalid')) {
        return;
    }
    
    const startDate = startDateInput.value;
    const endDate = endDateInput.value;
    
    filteredRows = allRows.filter(row => {
        const cells = row.querySelectorAll('td');
        if (cells.length === 0) return false;
        
        const dateText = cells[0].textContent.trim();
        const employeeId = cells[1].textContent.toLowerCase();
        const employeeName = cells[2].textContent.toLowerCase();
        
        // Search filter
        const matchesSearch = !searchValue || 
            employeeId.includes(searchValue) || 
            employeeName.includes(searchValue);
        
        // Date filter - improved date parsing
        let matchesDate = true;
        if (startDate || endDate) {
            // Convert table date to standard format for comparison
            let rowDate;
            
            // Check if date is in YYYY-MM-DD format
            if (dateText.match(/^\d{4}-\d{2}-\d{2}$/)) {
                rowDate = new Date(dateText);
            } 
            // Check if date is in MM/DD/YYYY format
            else if (dateText.match(/^\d{1,2}\/\d{1,2}\/\d{4}$/)) {
                const dateParts = dateText.split('/');
                rowDate = new Date(dateParts[2], dateParts[0] - 1, dateParts[1]);
            }
            // Check if date is in DD/MM/YYYY format
            else if (dateText.match(/^\d{1,2}-\d{1,2}-\d{4}$/)) {
                const dateParts = dateText.split('-');
                rowDate = new Date(dateParts[2], dateParts[1] - 1, dateParts[0]);
            }
            // Try to parse as is
            else {
                rowDate = new Date(dateText);
            }
            
            // Check if the parsed date is valid
            if (!isNaN(rowDate.getTime())) {
                const startDateObj = startDate ? new Date(startDate) : null;
                const endDateObj = endDate ? new Date(endDate) : null;
                
                // Set time to beginning/end of day for accurate comparison
                if (startDateObj) {
                    startDateObj.setHours(0, 0, 0, 0);
                    rowDate.setHours(0, 0, 0, 0);
                }
                if (endDateObj) {
                    endDateObj.setHours(23, 59, 59, 999);
                    if (!startDateObj) rowDate.setHours(0, 0, 0, 0);
                }
                
                if (startDateObj && rowDate < startDateObj) matchesDate = false;
                if (endDateObj && rowDate > endDateObj) matchesDate = false;
            } else {
                // If date parsing fails, exclude from filtered results when date filter is applied
                matchesDate = false;
            }
        }
        
        return matchesSearch && matchesDate;
    });
    
    currentPage = 1;
    displayPage(1);
    updatePaginationControls();
    updatePaginationInfo();
    calculateTotalAmount();
}

function calculateTotalAmount() {
    let total = 0;
    filteredRows.forEach(row => {
        const amountCell = row.querySelectorAll('td')[3];
        if (amountCell) {
            const amount = parseFloat(amountCell.textContent.replace(/,/g, '')) || 0;
            total += amount;
        }
    });
    
    const totalAmountElement = document.getElementById('total-amount');
    if (totalAmountElement) {
        // Remove placeholder classes and set the formatted amount
        totalAmountElement.classList.remove('placeholder-glow');
        totalAmountElement.innerHTML = total.toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }
}

function validatePayrollDate(dateInput) {
    const selectedDate = new Date(dateInput.value);
    const day = selectedDate.getDate();
    const month = selectedDate.getMonth();
    const year = selectedDate.getFullYear();
    
    // Get the last day of the selected month
    const lastDayOfMonth = new Date(year, month + 1, 0).getDate();
    
    // Check if the selected day is 15th or last day of month
    if (day !== 15 && day !== lastDayOfMonth) {
        // Show alert message
        alert('Please select only the 15th or the last day of the month.');
        // Reset the input
        dateInput.value = '';
        return false;
    } else {
        return true;
    }
}

function setupDateValidation() {
    const startDateInput = document.getElementById('start_date');
    const endDateInput = document.getElementById('end_date');
    
    // Add validation on change event
    startDateInput.addEventListener('change', function() {
        const isValid = validatePayrollDate(this);
        if (isValid && this.value && endDateInput.value) {
            // Re-trigger filtering if both dates are valid
            setTimeout(() => {
                updateFilterVisualIndicator();
                showLoadingRows();
                setTimeout(() => {
                    hideLoadingRows();
                    filterTable();
                }, 300);
            }, 100);
        }
    });
    
    endDateInput.addEventListener('change', function() {
        const isValid = validatePayrollDate(this);
        if (isValid && this.value && startDateInput.value) {
            // Re-trigger filtering if both dates are valid
            setTimeout(() => {
                updateFilterVisualIndicator();
                showLoadingRows();
                setTimeout(() => {
                    hideLoadingRows();
                    filterTable();
                }, 300);
            }, 100);
        }
    });
}

// Attach click event listeners to group buttons
document.querySelectorAll('.group-btn').forEach(button => {
    button.addEventListener('click', () => {
        const group = button.parentElement;

        // Toggle visibility of this group
        group.classList.toggle('show');

        // Close other groups in the dropdown
        document.querySelectorAll('.dropdown-group').forEach(otherGroup => {
            if (otherGroup !== group) {
                otherGroup.classList.remove('show');
            }
        });
    });
});

// Close all groups when clicking outside the dropdown
document.addEventListener('click', event => {
    if (!event.target.closest('.dropdown-content')) {
        document.querySelectorAll('.dropdown-group').forEach(group => {
            group.classList.remove('show');
        });
    }
});