/**
 * Reports JavaScript - Monthly Analysis Page
 */

$(document).ready(function() {
    
    // Initialize Chart
    initComparisonChart();
    
    // Handle month selector change via AJAX (optional)
    $('#month1, #month2').change(function() {
        // Uncomment to enable AJAX loading without page refresh
        // loadComparisonData();
    });
    
    // Export button handler
    $('.btn-export').click(function(e) {
        e.preventDefault();
        const month1 = $('#month1').val();
        const month2 = $('#month2').val();
        window.location.href = `export-data.php?type=comparison&month1=${month1}&month2=${month2}`;
    });
    
    // Responsive sidebar toggle for mobile
    createMobileMenuToggle();
});

/**
 * Initialize Comparison Bar Chart
 */
function initComparisonChart() {
    const ctx = document.getElementById('comparisonChart').getContext('2d');
    
    // Check if we have data
    if (!reportData.categories || reportData.categories.length === 0) {
        document.querySelector('.chart-wrapper').innerHTML = `
            <div style="height: 400px; display: flex; justify-content: center; align-items: center; flex-direction: column;">
                <i class="fas fa-chart-bar" style="font-size: 64px; color: #dee2e6; margin-bottom: 20px;"></i>
                <p style="color: #8d99ae; font-size: 16px;">No data available for the selected months</p>
            </div>
        `;
        return;
    }
    
    // Create gradient backgrounds
    const gradient1 = ctx.createLinearGradient(0, 0, 0, 400);
    gradient1.addColorStop(0, '#36A2EB');
    gradient1.addColorStop(1, '#2980b9');
    
    const gradient2 = ctx.createLinearGradient(0, 0, 0, 400);
    gradient2.addColorStop(0, '#FF6384');
    gradient2.addColorStop(1, '#e74c3c');
    
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: reportData.categories,
            datasets: [
                {
                    label: reportData.currentMonth,
                    data: reportData.currentAmounts,
                    backgroundColor: gradient1,
                    borderColor: '#2980b9',
                    borderWidth: 1,
                    borderRadius: 6,
                    barPercentage: 0.7,
                    categoryPercentage: 0.8
                },
                {
                    label: reportData.previousMonth,
                    data: reportData.previousAmounts,
                    backgroundColor: gradient2,
                    borderColor: '#e74c3c',
                    borderWidth: 1,
                    borderRadius: 6,
                    barPercentage: 0.7,
                    categoryPercentage: 0.8
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                tooltip: {
                    backgroundColor: 'rgba(0,0,0,0.8)',
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            let value = context.raw || 0;
                            return `${label}: ₹${value.toFixed(2)}`;
                        }
                    }
                },
                legend: {
                    display: false // Using custom legend
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: '#f1f3f5',
                        drawBorder: false
                    },
                    ticks: {
                        callback: function(value) {
                            return '₹' + value;
                        },
                        font: {
                            size: 12
                        }
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        font: {
                            size: 12,
                            weight: '500'
                        },
                        maxRotation: 45,
                        minRotation: 45
                    }
                }
            },
            layout: {
                padding: {
                    top: 20,
                    bottom: 20
                }
            }
        }
    });
}

/**
 * Load comparison data via AJAX (without page refresh)
 */
function loadComparisonData() {
    const month1 = $('#month1').val();
    const month2 = $('#month2').val();
    
    // Show loading state
    $('.summary-cards, .chart-container, .comparison-table-wrapper, .insights-wrapper').css('opacity', '0.5');
    
    $.ajax({
        url: '../api/get-report-data.php',
        type: 'GET',
        data: {
            action: 'get_monthly_comparison',
            month1: month1,
            month2: month2
        },
        dataType: 'json',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        success: function(response) {
            if (response.success) {
                updatePageContent(response.data);
            } else {
                showNotification('Failed to load comparison data', 'error');
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', error);
            showNotification('An error occurred while loading data', 'error');
        },
        complete: function() {
            $('.summary-cards, .chart-container, .comparison-table-wrapper, .insights-wrapper').css('opacity', '1');
        }
    });
}

/**
 * Update page content with AJAX response
 */
function updatePageContent(data) {
    // Update summary cards
    updateSummaryCards(data.summary1, data.summary2);
    
    // Update chart
    updateComparisonChart(data.comparison, data.month1_name, data.month2_name);
    
    // Update comparison table
    updateComparisonTable(data.comparison, data.month1_name, data.month2_name);
    
    // Update insights
    updateInsights(data.insights);
}

/**
 * Update summary cards
 */
function updateSummaryCards(summary1, summary2) {
    // Update current month summary
    $('.current-month .income').text('₹' + summary1.income.toFixed(2));
    $('.current-month .expense').text('₹' + summary1.expense.toFixed(2));
    $('.current-month .highlight .amount').text('₹' + summary1.savings.toFixed(2));
    $('.current-month .percentage').text('(' + summary1.savings_percentage + '%)');
    
    // Update previous month summary
    $('.previous-month .income').text('₹' + summary2.income.toFixed(2));
    $('.previous-month .expense').text('₹' + summary2.expense.toFixed(2));
    $('.previous-month .highlight .amount').text('₹' + summary2.savings.toFixed(2));
    $('.previous-month .percentage').text('(' + summary2.savings_percentage + '%)');
    
    // Update comparison
    const incomeChange = summary1.income - summary2.income;
    const expenseChange = summary1.expense - summary2.expense;
    const savingsChange = summary1.savings - summary2.savings;
    
    $('.comparison .summary-item:eq(0) .amount').text((incomeChange >= 0 ? '+' : '') + '₹' + incomeChange.toFixed(2));
    $('.comparison .summary-item:eq(1) .amount').text((expenseChange >= 0 ? '+' : '') + '₹' + expenseChange.toFixed(2));
    $('.comparison .summary-item:eq(2) .amount').text((savingsChange >= 0 ? '+' : '') + '₹' + savingsChange.toFixed(2));
}

/**
 * Update comparison chart
 */
function updateComparisonChart(comparison, month1Name, month2Name) {
    // Destroy existing chart
    if (window.comparisonChart) {
        window.comparisonChart.destroy();
    }
    
    // Prepare data
    const categories = [];
    const amounts1 = [];
    const amounts2 = [];
    const colors = [];
    
    Object.keys(comparison).forEach(category => {
        if (comparison[category].month1_amount > 0 || comparison[category].month2_amount > 0) {
            categories.push(category);
            amounts1.push(comparison[category].month1_amount);
            amounts2.push(comparison[category].month2_amount);
            colors.push(comparison[category].color);
        }
    });
    
    const ctx = document.getElementById('comparisonChart').getContext('2d');
    
    window.comparisonChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: categories,
            datasets: [
                {
                    label: month1Name,
                    data: amounts1,
                    backgroundColor: '#36A2EB',
                    borderColor: '#2980b9',
                    borderWidth: 1
                },
                {
                    label: month2Name,
                    data: amounts2,
                    backgroundColor: '#FF6384',
                    borderColor: '#e74c3c',
                    borderWidth: 1
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            let value = context.raw || 0;
                            return `${label}: ₹${value.toFixed(2)}`;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '₹' + value;
                        }
                    }
                }
            }
        }
    });
}

/**
 * Update comparison table
 */
function updateComparisonTable(comparison, month1Name, month2Name) {
    let html = '';
    
    Object.keys(comparison).forEach(category => {
        const data = comparison[category];
        if (data.month1_amount > 0 || data.month2_amount > 0) {
            const trendClass = data.percentage_change > 0 ? 'trend-up' : 
                             (data.percentage_change < 0 ? 'trend-down' : 'trend-neutral');
            const badgeClass = data.percentage_change > 0 ? 'badge-danger' : 
                             (data.percentage_change < 0 ? 'badge-success' : 'badge-neutral');
            
            html += `<tr>
                <td>
                    <span class="category-badge" style="background: ${data.color}20; color: ${data.color};">
                        ${category}
                    </span>
                </td>
                <td class="amount">₹${data.month1_amount.toFixed(2)}</td>
                <td class="amount">₹${data.month2_amount.toFixed(2)}</td>
                <td class="amount ${data.difference >= 0 ? 'text-danger' : 'text-success'}">
                    ${data.difference >= 0 ? '+' : ''}₹${data.difference.toFixed(2)}
                </td>
                <td>
                    <span class="change-badge ${badgeClass}">
                        ${data.percentage_change > 0 ? '+' : ''}${data.percentage_change}%
                    </span>
                </td>
                <td class="trend ${trendClass}">
                    ${data.trend}
                </td>
            </tr>`;
        }
    });
    
    if (html === '') {
        html = `<tr>
            <td colspan="6" class="no-data">
                <i class="fas fa-chart-line"></i>
                <p>No expense data available for the selected months</p>
            </td>
        </tr>`;
    }
    
    $('.comparison-table tbody').html(html);
}

/**
 * Update insights section
 */
function updateInsights(insights) {
    let html = '';
    
    if (!insights || insights.length === 0) {
        html = `<div class="insight-item empty">
            <i class="fas fa-smile" style="font-size: 32px; margin-bottom: 10px;"></i>
            <p>No insights available for this comparison</p>
        </div>`;
    } else {
        insights.forEach(insight => {
            html += `<div class="insight-item insight-${insight.type}">
                <div class="insight-icon">${insight.icon}</div>
                <div class="insight-content">
                    <p>${insight.message}</p>
                </div>
            </div>`;
        });
    }
    
    $('.insights-list').html(html);
}

/**
 * Show notification message
 */
function showNotification(message, type = 'success') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
            <span>${message}</span>
        </div>
        <button class="notification-close"><i class="fas fa-times"></i></button>
    `;
    
    // Style notification
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${type === 'success' ? '#06d6a0' : '#ef476f'};
        color: white;
        padding: 16px 24px;
        border-radius: 8px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        display: flex;
        align-items: center;
        gap: 12px;
        z-index: 9999;
        animation: slideIn 0.3s ease;
    `;
    
    document.body.appendChild(notification);
    
    // Auto remove after 3 seconds
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 3000);
    
    // Close button handler
    notification.querySelector('.notification-close').addEventListener('click', function() {
        notification.remove();
    });
}

/**
 * Create mobile menu toggle
 */
function createMobileMenuToggle() {
    if (window.innerWidth <= 768) {
        const header = document.querySelector('.page-header');
        const menuButton = document.createElement('button');
        menuButton.className = 'mobile-menu-btn';
        menuButton.innerHTML = '<i class="fas fa-bars"></i>';
        menuButton.style.cssText = `
            background: var(--primary);
            color: white;
            border: none;
            padding: 12px;
            border-radius: 8px;
            margin-right: 15px;
            cursor: pointer;
        `;
        
        menuButton.addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
        });
        
        header.prepend(menuButton);
    }
}

// Add CSS animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);