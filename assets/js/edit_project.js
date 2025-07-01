// edit_project.js - JavaScript functionality for edit project page
document.addEventListener('DOMContentLoaded', function() {
    // Highlight the current active stage for better visibility
    const highlightActiveStage = function() {
        const firstUnsubmittedStageName = window.firstUnsubmittedStageName;
        if (firstUnsubmittedStageName) {
            document.querySelectorAll(`tr[data-stage="${firstUnsubmittedStageName}"]`).forEach(row => {
                row.style.backgroundColor = '#f8f9fa';
                row.style.boxShadow = '0 0 5px rgba(0,0,0,0.1)';
            });

            document.querySelectorAll(`.stage-card h4`).forEach(heading => {
                if (heading.textContent === firstUnsubmittedStageName) {
                    heading.closest('.stage-card').style.backgroundColor = '#f8f9fa';
                    heading.closest('.stage-card').style.boxShadow = '0 0 8px rgba(0,0,0,0.15)';
                }
            });
        }
    };

    highlightActiveStage();

    // Add tooltips for better usability
    const addTooltip = function(element, text) {
        element.title = text;
        element.style.cursor = 'help';
    };

    document.querySelectorAll('th').forEach(th => {
        if (th.textContent === 'Created') {
            addTooltip(th, 'When the document was created');
        } else if (th.textContent === 'Approved') {
            addTooltip(th, 'When the document was approved');
        } else if (th.textContent === 'Office') {
            addTooltip(th, 'Office responsible for this stage');
        }
    });

    // Toast notification handler
    const showToast = function() {
        var toast = document.getElementById('toast-success');
        if (toast) {
            toast.style.display = 'block';
            setTimeout(function() {
                toast.style.opacity = '0';
                setTimeout(function() { 
                    toast.style.display = 'none'; 
                }, 600);
            }, 2500);
        }
    };

    // Check if we need to show toast
    if (window.showSuccessToast) {
        showToast();
    }
});
