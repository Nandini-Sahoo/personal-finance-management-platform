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

// Get all budgets
$allBudgets = $budgetFunctions->getAllBudgets($userId);

// Group budgets by month
$groupedBudgets = [];
foreach ($allBudgets as $budget) {
    $groupedBudgets[$budget['month_year']][] = $budget;
}

include_once '../add-asset.html';
?>
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/budget.css">
    
    <style>
        
        /* Main Content */
        .main-content {
            padding: 2rem;
        }
        
        .page-title {
            margin-bottom: 2rem;
        }
        
        .page-title h1 {
            font-size: 2rem;
            font-weight: 700;
            color: var(--dark-color);
        }
        
        .page-title h1 i {
            color: var(--primary-color);
            margin-right: 10px;
        }
        
        .page-title p {
            color: #6c757d;
            margin: 0;
        }
        
        /* Month Group Card */
        .month-group {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            margin-bottom: 2rem;
        }
        
        .month-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #f1f3f5;
        }
        
        .month-header h3 {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--dark-color);
            margin: 0;
        }
        
        .month-total {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--primary-color);
        }
        
        .month-total small {
            font-size: 0.9rem;
            color: #6c757d;
            font-weight: normal;
        }
        
        .budget-row {
            display: flex;
            align-items: center;
            padding: 1rem;
            border-bottom: 1px solid #f1f3f5;
            transition: background 0.3s;
        }
        
        .budget-row:hover {
            background: #f8f9fa;
        }
        
        .budget-row:last-child {
            border-bottom: none;
        }
        
        .category-info {
            flex: 2;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .category-color {
            width: 12px;
            height: 12px;
            border-radius: 50%;
        }
        
        .category-name {
            font-weight: 500;
            color: var(--dark-color);
        }
        
        .budget-amount-info {
            flex: 1;
            text-align: right;
        }
        
        .spent-info {
            flex: 1;
            text-align: right;
        }
        
        .progress-info {
            flex: 2;
            padding: 0 1rem;
        }
        
        .progress {
            height: 6px;
            margin-bottom: 0.3rem;
        }
        
        .percentage-text {
            font-size: 0.85rem;
            color: #6c757d;
        }
        
        .status-badge {
            width: 30px;
            text-align: center;
        }
        
        .no-data {
            text-align: center;
            padding: 3rem;
            color: #6c757d;
        }
        
        .no-data i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.3;
        }
        
        @media (max-width: 992px) {
            .sidebar {
                min-height: auto;
                position: relative;
            }
            
            .main-content {
                padding: 1.5rem;
            }
            
            .budget-row {
                flex-wrap: wrap;
            }
            
            .category-info, .budget-amount-info, .spent-info, .progress-info {
                flex: 100%;
                text-align: left;
                margin: 0.3rem 0;
            }
            
            .progress-info {
                padding: 0;
            }
        }
    </style>
</head>
<body>
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