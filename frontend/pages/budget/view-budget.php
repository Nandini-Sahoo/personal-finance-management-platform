<?php
require_once '../../../backend/session.php';
require_once '../../../backend/budget-func.php';

// Check if user is logged in
Session::requireLogin();
$userId = Session::getUserId();
$userName = Session::getUserName();

// $userId =1;
// $userName = " ";

// Initialize budget functions
$budgetFunctions = new BudgetFunctions();

// Get all budgets
$allBudgets = $budgetFunctions->getAllBudgets($userId);

// Group budgets by month
$groupedBudgets = [];
foreach ($allBudgets as $budget) {
    $groupedBudgets[$budget['month_year']][] = $budget;
}

?>
<head>
    <?php include_once '../add-asset.html'; ?>
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../../assets/css/view-budget.css">
</head>
    <div class="container-fluid p-0">
        <div class="row g-0">
            <?php include_once '../sidebar.php'?>
            
            <!-- Main Content -->
            <div class="col-lg-10 col-md-9 main-content">
                <!-- Page Title -->
                <div class="page-title">
                    <h1><i class="fas fa-list"></i> All Budgets</h1>
                    <p>View and manage all your monthly budgets</p>
                </div>
                
                <!-- Action Button -->
                <div class="mb-4">
                    <a href="set-budget.php" class="btn btn-primary">
                        <i class="fas fa-plus-circle me-2"></i> Set New Budget
                    </a>
                </div>
                
                <!-- Budgets List -->
                <?php if (empty($groupedBudgets)): ?>
                    <div class="no-data">
                        <i class="fas fa-tasks"></i>
                        <h5>No budgets found</h5>
                        <p class="text-muted">Start by setting your first monthly budget</p>
                        <a href="set-budget.php" class="btn btn-primary mt-3">
                            <i class="fas fa-plus-circle me-2"></i> Set Budget
                        </a>
                    </div>
                <?php else: ?>
                    <?php foreach ($groupedBudgets as $monthYear => $budgets): ?>
                        <?php 
                        $monthTotal = array_sum(array_column($budgets, 'target_amount'));
                        $monthSpent = array_sum(array_column($budgets, 'spent_amount'));
                        $monthPercentage = $monthTotal > 0 ? round(($monthSpent / $monthTotal) * 100, 1) : 0;
                        $monthName = date('F Y', strtotime($monthYear . '-01'));
                        ?>
                        
                        <div class="month-group">
                            <div class="month-header">
                                <h3><?php echo $monthName; ?></h3>
                                <div class="month-total">
                                    Total Budget: <?php echo $budgetFunctions->formatCurrency($monthTotal); ?>
                                    <small>(<?php echo $monthSpent > 0 ? $budgetFunctions->formatCurrency($monthSpent) . ' spent' : 'No spending'; ?>)</small>
                                </div>
                            </div>
                            
                            <?php foreach ($budgets as $budget): ?>
                                <?php 
                                $statusClass = '';
                                $statusIcon = '';
                                
                                if ($budget['percentage'] >= 100) {
                                    $statusClass = 'text-danger';
                                    $statusIcon = '❌';
                                } elseif ($budget['percentage'] >= 90) {
                                    $statusClass = 'text-warning';
                                    $statusIcon = '⚠️';
                                } elseif ($budget['percentage'] > 0) {
                                    $statusClass = 'text-success';
                                    $statusIcon = '✅';
                                } else {
                                    $statusClass = 'text-muted';
                                    $statusIcon = '⚪';
                                }
                                ?>
                                
                                <div class="budget-row">
                                    <div class="category-info">
                                        <span class="category-color" style="background: <?php echo $budget['color'] ?? '#4361ee'; ?>;"></span>
                                        <span class="category-name"><?php echo htmlspecialchars($budget['category_name']); ?></span>
                                    </div>
                                    
                                    <div class="budget-amount-info">
                                        <strong>Budget:</strong> <?php echo $budgetFunctions->formatCurrency($budget['target_amount']); ?>
                                    </div>
                                    
                                    <div class="spent-info">
                                        <span class="<?php echo $budget['spent_amount'] > $budget['target_amount'] ? 'text-danger fw-bold' : ''; ?>">
                                            <strong>Spent:</strong> <?php echo $budgetFunctions->formatCurrency($budget['spent_amount']); ?>
                                        </span>
                                    </div>
                                    
                                    <div class="progress-info">
                                        <div class="progress">
                                            <div class="progress-bar <?php 
                                                if ($budget['percentage'] >= 100) echo 'bg-danger';
                                                elseif ($budget['percentage'] >= 90) echo 'bg-warning';
                                                else echo 'bg-success';
                                            ?>" style="width: <?php echo min($budget['percentage'], 100); ?>%"></div>
                                        </div>
                                        <div class="percentage-text">
                                            <?php echo $budget['percentage']; ?>% used
                                            <?php if ($budget['remaining'] < 0): ?>
                                                <span class="text-danger">(<?php echo $budgetFunctions->formatCurrency(abs($budget['remaining'])); ?> over)</span>
                                            <?php else: ?>
                                                <span class="text-muted">(<?php echo $budgetFunctions->formatCurrency($budget['remaining']); ?> left)</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="status-badge <?php echo $statusClass; ?>">
                                        <?php echo $statusIcon; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>