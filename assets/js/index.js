// Dashboard JavaScript functionality

// Define modal elements globally at the very top of your script
const addProjectModal = document.getElementById('addProjectModal');
const statsModal = document.getElementById('statsModal');
const statsModalContentPlaceholder = document.getElementById('statsModalContentPlaceholder');
const statsClose = document.getElementById('statsClose');
const addProjectClose = document.getElementById('addProjectClose');
const showAddProjectFormButton = document.getElementById('showAddProjectForm');

// Pagination elements
const prevPageBtn = document.getElementById('prevPage');
const nextPageBtn = document.getElementById('nextPage');
const linesPerPageSelect = document.getElementById('linesPerPage');

// Pagination variables
let currentPage = 1;
let rowsPerPage = 10;
let totalPages = 1;

// --- Common modal functions ---
function closeModal(modal, contentPlaceholder = null) {
    if (modal) {
        modal.style.display = 'none';
        if (contentPlaceholder) {
            contentPlaceholder.innerHTML = '';
        }
    }
}

// Function to handle pagination
function setupPagination() {
    const tableRows = document.querySelectorAll("table.dashboard-table tbody tr");
    
    // Count only rows that aren't filtered out by search
    const visibleRowsCount = Array.from(tableRows).filter(row => !row.classList.contains('filtered-out')).length;
    
    // Calculate total pages based on visible rows
    totalPages = Math.ceil(visibleRowsCount / rowsPerPage);
    
    // If current page is beyond total pages, reset to page 1
    if (currentPage > totalPages && totalPages > 0) {
        currentPage = 1;
    }
    
    // Update pagination buttons state
    updatePaginationControls();
    
    // Show only rows for current page
    displayRowsForCurrentPage();
}

// Function to display rows for current page
function displayRowsForCurrentPage() {
    const tableRows = document.querySelectorAll("table.dashboard-table tbody tr");
    const displayStyle = window.matchMedia("(max-width: 500px)").matches ? "block" : "table-row";
    
    // Filter out rows that don't match search criteria
    const visibleRows = Array.from(tableRows).filter(row => !row.classList.contains('filtered-out'));
    
    // Calculate pagination based on visible rows
    const startIndex = (currentPage - 1) * rowsPerPage;
    const endIndex = startIndex + rowsPerPage;
    
    // Hide all rows first
    tableRows.forEach(row => {
        row.style.display = "none";
    });
    
    // Show only the rows for current page that aren't filtered out
    visibleRows.forEach((row, index) => {
        if (index >= startIndex && index < endIndex) {
            row.style.display = displayStyle;
        }
    });
}

// Function to update pagination controls
function updatePaginationControls() {
    // Disable prev button if on first page
    prevPageBtn.disabled = currentPage === 1;
    
    // Disable next button if on last page
    nextPageBtn.disabled = currentPage === totalPages || totalPages === 0;
}

// Event listener for previous page button
function setupPaginationListeners() {
    if (prevPageBtn) {
        prevPageBtn.addEventListener('click', function() {
            if (currentPage > 1) {
                currentPage--;
                displayRowsForCurrentPage();
                updatePaginationControls();
            }
        });
    }

    // Event listener for next page button
    if (nextPageBtn) {
        nextPageBtn.addEventListener('click', function() {
            if (currentPage < totalPages) {
                currentPage++;
                displayRowsForCurrentPage();
                updatePaginationControls();
            }
        });
    }

    // Event listener for lines per page dropdown
    if (linesPerPageSelect) {
        linesPerPageSelect.addEventListener('change', function() {
            rowsPerPage = parseInt(this.value);
            currentPage = 1; // Reset to first page when changing rows per page
            setupPagination();
        });
    }
}

// --- Search functionality for filtering projects ---
function performSearch() {
    let searchInput = document.getElementById("searchInput");
    let query = searchInput.value.toLowerCase().trim();
    let rows = document.querySelectorAll("table.dashboard-table tbody tr");
    let visibleCount = 0;
    
    // Mark rows as filtered or not based on search query
    rows.forEach(row => {
        // Use querySelector to reliably get the cells by their data-label attributes
        let prNumberCell = row.querySelector('[data-label="PR Number"]');
        let projectDetailsCell = row.querySelector('[data-label="Project Details"]');
        
        if (!prNumberCell || !projectDetailsCell) {
            // Fallback to direct children if data-label selectors don't work
            prNumberCell = row.children[0];
            projectDetailsCell = row.children[1];
        }
        
        let prNumber = prNumberCell ? prNumberCell.textContent.toLowerCase() : '';
        let projectDetails = projectDetailsCell ? projectDetailsCell.textContent.toLowerCase() : '';
        
        if (prNumber.includes(query) || projectDetails.includes(query)) {
            row.classList.remove('filtered-out');
            visibleCount++;
        } else {
            row.classList.add('filtered-out');
        }
    });
    
    const noResultsDiv = document.getElementById("noResults");
    // Only show "No results" if the search query is not empty and no rows are visible
    if (noResultsDiv) {
        noResultsDiv.style.display = (visibleCount === 0 && query !== '') ? "block" : "none";
    }
    
    // Reset to first page and update pagination
    currentPage = 1;
    setupPagination();
}

// Setup search event listeners
function setupSearchListeners() {
    const searchInput = document.getElementById("searchInput");
    if (searchInput) {
        // Search on keyup
        searchInput.addEventListener("keyup", performSearch);
        
        // Also search when input is cleared or changed
        searchInput.addEventListener("input", performSearch);
        
        // Search on form submission
        searchInput.form?.addEventListener("submit", function(e) {
            e.preventDefault();
            performSearch();
        });
    }
}

// --- Add Project Modal logic ---
function setupAddProjectModal() {
    if (showAddProjectFormButton) {
        showAddProjectFormButton.addEventListener('click', function() {
            if (addProjectModal) {
                addProjectModal.style.display = 'block';
                // Clear any previous error messages when opening the modal for a new attempt
                const errorParagraph = addProjectModal.querySelector('p[style*="color: red"]');
                if (errorParagraph) {
                    errorParagraph.remove();
                }
                // Reset form fields when opening the modal for a new project
                const addProjectForm = document.getElementById('addProjectForm');
                if (addProjectForm) {
                    addProjectForm.reset();
                }
            }
        });
    }
    
    if (addProjectClose) {
        addProjectClose.addEventListener('click', function() {
            closeModal(addProjectModal);
        });
    }
}

// --- Statistics Modal loading function ---
function loadAndShowStatistics() {
    // Display a loading message immediately
    if (statsModalContentPlaceholder) {
        statsModalContentPlaceholder.innerHTML = '<p style="text-align: center; margin-top: 20px;">Loading statistics...</p>';
    }
    if (statsModal) {
        statsModal.style.display = 'block';
    }

    // Get the URL from PHP
    const statisticsUrl = window.statisticsUrl || 'statistics.php';
    
    fetch(statisticsUrl)
        .then(response => {
            if (!response.ok) {
                console.error('Network response was not ok:', response.status, response.statusText);
                return response.text().then(text => {
                    throw new Error('HTTP error! Status: ' + response.status + ' - ' + text);
                });
            }
            return response.text();
        })
        .then(html => {
            if (statsModalContentPlaceholder) {
                statsModalContentPlaceholder.innerHTML = html;
            }
        })
        .catch(error => {
            console.error('There has been a problem with your fetch operation:', error);
            if (statsModalContentPlaceholder) {
                statsModalContentPlaceholder.innerHTML = '<p style="color: red; text-align: center; margin-top: 20px;">Failed to load statistics. Please try again.<br>Error: ' + error.message + '</p>';
            }
        });
}

// Setup statistics modal
function setupStatisticsModal() {
    // Close Statistics Modal (X button)
    if (statsClose) {
        statsClose.addEventListener('click', function() {
            closeModal(statsModal, statsModalContentPlaceholder);
        });
    }
}

// --- Handle clicks outside modals to close them ---
function setupModalClickOutside() {
    document.addEventListener('click', function(event) {
        if (addProjectModal && event.target === addProjectModal) {
            closeModal(addProjectModal);
        }
        if (statsModal && event.target === statsModal) {
            closeModal(statsModal, statsModalContentPlaceholder);
        }
    });
}

// --- Modal Closing Logic (Escape Key) ---
function setupEscapeKeyHandler() {
    document.addEventListener('keydown', function(event) {
        if (event.key === "Escape") {
            closeModal(addProjectModal);
            closeModal(statsModal, statsModalContentPlaceholder);
        }
    });
}

// --- Project details expand/collapse functions ---
function showFullDetails(id) {
    document.getElementById(id + '_short').style.display = 'none';
    document.getElementById(id + '_full').style.display = 'inline';
}

function hideFullDetails(id) {
    document.getElementById(id + '_full').style.display = 'none';
    document.getElementById(id + '_short').style.display = 'inline';
}

// Combined filtering function for both MoP and search
function applyFilters() {
    const filterMoP = document.getElementById('filterMoP');
    const searchInput = document.getElementById('searchInput');
    const tableRows = document.querySelectorAll('table.dashboard-table tbody tr');
    
    const selectedMoP = filterMoP ? filterMoP.value : '';
    const query = searchInput ? searchInput.value.toLowerCase().trim() : '';
    let visibleCount = 0;

    tableRows.forEach(row => {
        // Mode of Procurement filter
        const mop = row.getAttribute('data-mop');
        // Search filter
        let prNumberCell = row.querySelector('[data-label="PR Number"]');
        let projectDetailsCell = row.querySelector('[data-label="Project Details"]');
        if (!prNumberCell || !projectDetailsCell) {
            prNumberCell = row.children[0];
            projectDetailsCell = row.children[1];
        }
        const prNumber = prNumberCell ? prNumberCell.textContent.toLowerCase() : '';
        const projectDetails = projectDetailsCell ? projectDetailsCell.textContent.toLowerCase() : '';

        // Check both filters
        const matchesMoP = !selectedMoP || mop === selectedMoP;
        const matchesSearch = !query || prNumber.includes(query) || projectDetails.includes(query);

        if (matchesMoP && matchesSearch) {
            row.style.display = '';
            visibleCount++;
            row.classList.remove('filtered-out');
        } else {
            row.style.display = 'none';
            row.classList.add('filtered-out');
        }
    });

    // Show/hide "No results" message
    const noResultsDiv = document.getElementById("noResults");
    if (noResultsDiv) {
        noResultsDiv.style.display = (visibleCount === 0) ? "block" : "none";
    }

    // Reset to first page and update pagination if you use it
    if (typeof setupPagination === 'function') {
        currentPage = 1;
        setupPagination();
    }
}

// Setup filtering listeners
function setupFilteringListeners() {
    const filterMoP = document.getElementById('filterMoP');
    const searchInput = document.getElementById('searchInput');
    
    // Attach to both search and dropdown
    if (filterMoP) filterMoP.addEventListener('change', applyFilters);
    if (searchInput) {
        searchInput.addEventListener('keyup', applyFilters);
        searchInput.addEventListener('input', applyFilters);
        searchInput.form?.addEventListener('submit', function(e) {
            e.preventDefault();
            applyFilters();
        });
    }
}

// Initialize everything when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Check if there was a project error and show modal if needed
    if (window.showAddProjectModal) {
        if (addProjectModal) {
            addProjectModal.style.display = 'block';
        }
    }
    
    // Initialize all functionality
    setupPaginationListeners();
    setupSearchListeners();
    setupAddProjectModal();
    setupStatisticsModal();
    setupModalClickOutside();
    setupEscapeKeyHandler();
    setupFilteringListeners();
    
    // Initialize pagination and apply initial filters
    setupPagination();
    applyFilters();
});

// Summary Report Function
function generateSummaryReport() {
    console.log('Generating summary report...');
    
    try {
        // Get the current search term to include in the report
        const searchInput = document.getElementById('searchInput');
        const searchTerm = searchInput ? searchInput.value.trim() : '';
        
        // Build the URL for the summary report
        let reportUrl = 'summary_report.php';
        if (searchTerm) {
            reportUrl += '?search=' + encodeURIComponent(searchTerm);
        }
        
        console.log('Opening report URL:', reportUrl);
        
        // Open the report in a new window/tab
        const newWindow = window.open(reportUrl, '_blank', 'width=1200,height=800,scrollbars=yes,resizable=yes');
        
        // Check if window was opened successfully
        if (newWindow) {
            newWindow.focus();
            console.log('Summary report opened successfully');
        } else {
            // Pop-up might be blocked, offer alternative
            const useCurrentTab = confirm('Pop-up blocked! Open summary report in current tab instead?');
            if (useCurrentTab) {
                window.location.href = reportUrl;
            }
        }
        
    } catch (error) {
        console.error('Error generating summary report:', error);
        alert('Error opening summary report. Please try again.');
    }
}

// Make functions available globally for inline onclick handlers
window.loadAndShowStatistics = loadAndShowStatistics;
window.generateSummaryReport = generateSummaryReport;
window.showFullDetails = showFullDetails;
window.hideFullDetails = hideFullDetails;
