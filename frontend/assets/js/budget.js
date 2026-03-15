/**
 * Budget JavaScript - Interactive features
 */

$(document).ready(function() {
    
    // Format budget inputs
    $('.budget-amount-input').on('blur', function() {
        let value = $(this).val();
        if (value && !isNaN(value)) {
            $(this).val(parseFloat(value).toFixed(2));
        }
    });
    
    // Quick actions for budget amounts
    $('.quick-action').click(function() {
        const categoryId = $(this).data('category');
        const action = $(this).data('action');
        const input = $(`#budget_${categoryId}`);
        let currentValue = parseFloat(input.val()) || 0;
        
        switch(action) {
            case 'increase':
                input.val((currentValue + 500).toFixed(2));
                break;
            case 'decrease':
                if (currentValue >= 500) {
                    input.val((currentValue - 500).toFixed(2));
                }
                break;
            case 'clear':
                input.val('');
                break;
        }
        
        // Trigger change event
        input.trigger('change');
    });
    
    // Live preview of budget status
    $('.budget-amount-input').on('input', function() {
        const card = $(this).closest('.budget-card');
        const spent = parseFloat(card.find('.spent-amount').text().replace('₹', '').replace(',', '')) || 0;
        const newBudget = parseFloat($(this).val()) || 0;
        
        if (newBudget > 0) {
            const percentage = ((spent / newBudget) * 100).toFixed(1);
            const progressBar = card.find('.progress-bar');
            const percentageText = card.find('.fw-semibold:first');
            
            // Update progress bar
            progressBar.css('width', Math.min(percentage, 100) + '%');
            percentageText.text(percentage + '% used');
            
            // Update status
            if (percentage >= 100) {
                progressBar.removeClass('bg-success bg-warning').addClass('bg-danger');
                card.find('.status-message').removeClass('good warning').addClass('over').text('Over budget!');
            } else if (percentage >= 90) {
                progressBar.removeClass('bg-success bg-danger').addClass('bg-warning');
                card.find('.status-message').removeClass('good over').addClass('warning').text('Approaching limit');
            } else {
                progressBar.removeClass('bg-warning bg-danger').addClass('bg-success');
                card.find('.status-message').removeClass('warning over').addClass('good').text('Within budget');
            }
        }
    });
    
    // Confirm before navigating away with unsaved changes
    let formChanged = false;
    
    $('.budget-amount-input').on('change', function() {
        formChanged = true;
    });
    
    $('form').on('submit', function() {
        formChanged = false;
    });
    
    $(window).on('beforeunload', function() {
        if (formChanged) {
            return 'You have unsaved changes. Are you sure you want to leave?';
        }
    });
    
    // Auto-save functionality (optional)
    let autoSaveTimer;
    $('.budget-amount-input').on('input', function() {
        clearTimeout(autoSaveTimer);
        autoSaveTimer = setTimeout(function() {
            if (confirm('Auto-save budgets?')) {
                $('#budgetForm').submit();
            }
        }, 5000);
    });
    
    // Load budget summary via AJAX
    function loadBudgetSummary() {
        $.ajax({
            url: 'budget-process.php',
            method: 'POST',
            data: {
                action: 'get_summary'
            },
            dataType: 'json',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            success: function(response) {
                if (response.success) {
                    updateSummaryCards(response.data);
                }
            }
        });
    }
    
    function updateSummaryCards(data) {
        // Update summary cards with new data
        $('.summary-card .value').each(function(index) {
            // This would update the summary cards dynamically
            console.log('Summary updated:', data);
        });
    }
    
    // Optional: Refresh summary every 30 seconds
    // setInterval(loadBudgetSummary, 30000);
    
    // Tooltip initialization
    $('[data-tooltip]').tooltip({
        trigger: 'hover'
    });
    
    // Keyboard shortcuts
    $(document).on('keydown', function(e) {
        // Ctrl/Cmd + S to save
        if ((e.ctrlKey || e.metaKey) && e.key === 's') {
            e.preventDefault();
            $('#budgetForm').submit();
        }
        
        // Escape to clear form changes
        if (e.key === 'Escape') {
            if (confirm('Discard all changes?')) {
                location.reload();
            }
        }
    });
    
    // Initialize any tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    });
});