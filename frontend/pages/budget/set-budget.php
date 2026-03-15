<?php
// require_once '../includes/session.php';
require_once '../../../backend/budget-func.php';


// Check if user is logged in
// Session::requireLogin();
// $userId = Session::getUserId();
// $userName = Session::getUserName();

$userId =1;
$userName = " ";

// Initialize budget functions
$budgetFunctions = new BudgetFunctions();

// Get selected month from URL or default to current month
$selectedMonth = $_GET['month'] ?? date('Y-m');
$selectedMonthName = $budgetFunctions->getMonthName($selectedMonth);

// Get available months for dropdown
$availableMonths = $budgetFunctions->getAvailableBudgetMonths($userId);

// Get budget data for selected month
$budgetData = $budgetFunctions->getMonthlyBudgetData($userId, $selectedMonth);

// Get current month summary
$summary = $budgetFunctions->getCurrentMonthSummary($userId);

// Handle form submission
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_budgets'])) {
        $budgets = [];
        foreach ($_POST['budget'] as $categoryId => $amount) {
            $budgets[$categoryId] = $amount;
        }
        
        $result = $budgetFunctions->saveBudgets($userId, $selectedMonth, $budgets);
        
        if ($result['success']) {
            $message = 'Budgets saved successfully!';
            $messageType = 'success';
            // Refresh budget data
            $budgetData = $budgetFunctions->getMonthlyBudgetData($userId, $selectedMonth);
        } else {
            $message = 'Error saving budgets: ' . implode(', ', $result['errors']);
            $messageType = 'danger';
        }
    }
}
include_once '../add-asset.html';
?>
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../../assets/css/budget.css">

</head>
<body>
    <div class="container-fluid p-0">
        <div class="row g-0">
            <?php include_once '../sidebar.php'?>
            <!-- Main Content -->
            <div class="col-lg-10 col-md-9 main-content">
                <!-- Page Title -->
                <div class="page-title">
                    <h1><i class="fas fa-tasks"></i> Budget Planner</h1>
                    <p>Set and manage your monthly budgets</p>
                </div>
                
                <!-- Alert Message -->
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                        <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?> me-2"></i>
                        <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <!-- Summary Cards Row -->
                <div class="row g-4 mb-4">
                    <div class="col-md-3">
                        <div class="summary-card">
                            <div class="label">Total Budget</div>
                            <div class="value"><?php echo $budgetFunctions->formatCurrency($summary['total_budget']); ?></div>
                            <div class="sub">for <?php echo date('F Y'); ?></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="summary-card">
                            <div class="label">Total Spent</div>
                            <div class="value"><?php echo $budgetFunctions->formatCurrency($summary['total_spent']); ?></div>
                            <div class="sub"><?php echo $summary['percentage']; ?>% of budget</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="summary-card">
                            <div class="label">Remaining</div>
                            <div class="value <?php echo $summary['remaining'] >= 0 ? 'text-success' : 'text-danger'; ?>">
                                <?php echo $budgetFunctions->formatCurrency($summary['remaining']); ?>
                            </div>
                            <div class="sub">to spend this month</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="summary-card primary">
                            <div class="label">Budget Status</div>
                            <div class="value"><?php echo $summary['categories_with_budget']; ?>/<?php echo count($budgetData); ?></div>
                            <div class="sub">
                                <?php echo $summary['warning_count']; ?> warning • 
                                <?php echo $summary['over_budget_count']; ?> over budget
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Month Selector -->
                <div class="month-selector">
                    <form method="GET" action="set-budget.php">
                        <div class="row align-items-end">
                            <div class="col-md-9">
                                <label class="form-label fw-semibold">Select Month:</label>
                                <select name="month" class="form-select">
                                    <?php foreach ($availableMonths as $month): ?>
                                        <option value="<?php echo $month['month_year']; ?>" 
                                            <?php echo $month['month_year'] == $selectedMonth ? 'selected' : ''; ?>>
                                            <?php echo $month['month_name']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn-view w-100">
                                    <i class="fas fa-eye me-2"></i> View Month
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
                
                <!-- Budget Form -->
                <form method="POST" action="set-budget.php?month=<?php echo $selectedMonth; ?>" id="budgetForm">
                    <input type="hidden" name="month" value="<?php echo $selectedMonth; ?>">
                    
                    <div class="row">
                        <?php foreach ($budgetData as $item): ?>
                            <?php 
                            $statusClass = '';
                            if ($item['status'] === 'over-budget') $statusClass = 'status-over';
                            elseif ($item['status'] === 'warning') $statusClass = 'status-warning';
                            elseif ($item['status'] === 'good') $statusClass = 'status-good';
                            else $statusClass = 'status-no-budget';
                            ?>
                            
                            <div class="col-xl-6">
                                <div class="budget-card <?php echo $statusClass; ?>">
                                    <div class="budget-header">
                                        <span class="budget-title">
                                            <i class="fas fa-circle me-2" style="color: <?php echo $item['color']; ?>;"></i>
                                            <?php echo htmlspecialchars($item['category_name']); ?>
                                        </span>
                                        <span class="budget-icon"><?php echo $item['icon']; ?></span>
                                    </div>
                                    
                                    <div class="budget-amount">
                                        <span class="fw-semibold">Budget:</span>
                                        <input type="number" 
                                               name="budget[<?php echo $item['category_id']; ?>]" 
                                               class="budget-amount-input form-control" 
                                               value="<?php echo $item['budget_amount'] > 0 ? $item['budget_amount'] : ''; ?>"
                                               placeholder="Enter amount"
                                               step="0.01"
                                               min="0">
                                    </div>
                                    
                                    <div class="spent-info">
                                        <span>Spent: <span class="spent-amount"><?php echo $budgetFunctions->formatCurrency($item['spent_amount']); ?></span></span>
                                        <span class="remaining">
                                            Remaining: <?php echo $budgetFunctions->formatCurrency($item['remaining']); ?>
                                        </span>
                                    </div>
                                    
                                    <div class="progress">
                                        <div class="progress-bar <?php echo $item['progress_class']; ?>" 
                                             style="width: <?php echo min($item['percentage'], 100); ?>%">
                                        </div>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="fw-semibold"><?php echo $item['percentage']; ?>% used</span>
                                        <span class="status-message <?php echo str_replace('-', ' ', $item['status']); ?>">
                                            <?php echo $item['message']; ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="action-buttons">
                        <button type="submit" name="save_budgets" class="btn-save">
                            <i class="fas fa-save me-2"></i> Save Budgets
                        </button>
                        <a href="view-budget.php" class="btn-view-all">
                            <i class="fas fa-list me-2"></i> View All Budgets
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Custom JS -->
    <script src="../../assets/js/budget.js"></script>
    
    <script>
        // Auto-dismiss alerts after 5 seconds
        setTimeout(function() {
            document.querySelectorAll('.alert').forEach(function(alert) {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(function() {
                    alert.remove();
                }, 500);
            });
        }, 5000);
    </script>