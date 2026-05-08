<?php
require_once '../../../backend/report-func.php';
require_once '../../../backend/session.php';


// Check if user is logged in
Session::requireLogin();
$userId = Session::getUserId();
$userName = Session::getUserName();

// $userId =1;
// $userName = " ";

// Initialize report functions
$reportFunctions = new ReportFunctions();

// Get available months
$availableMonths = $reportFunctions->getAvailableMonths($userId);

// Get selected months from GET parameters
$currentMonth = $_GET['month1'] ?? date('Y-m');
$previousMonth = $_GET['month2'] ?? date('Y-m', strtotime('-1 month'));

// Validate months exist in user's data
$validMonths = array_column($availableMonths, 'month_year');
if (!in_array($currentMonth, $validMonths)) {
    $currentMonth = !empty($validMonths) ? $validMonths[0] : date('Y-m');
}
if (!in_array($previousMonth, $validMonths)) {
    $previousMonth = !empty($validMonths) && count($validMonths) > 1 ? $validMonths[1] : date('Y-m', strtotime('-1 month'));
}

// Get comparison data
$comparison = $reportFunctions->getMonthlyComparison($userId, $currentMonth, $previousMonth);

// Get monthly summaries
$currentMonthSummary = $reportFunctions->getMonthlySummary($userId, $currentMonth);
$previousMonthSummary = $reportFunctions->getMonthlySummary($userId, $previousMonth);

// Generate insights
$insights = $reportFunctions->generateInsights($comparison, $currentMonth, $previousMonth, $userId);

// Get month names for display
$currentMonthName = $reportFunctions->getMonthName($currentMonth);
$previousMonthName = $reportFunctions->getMonthName($previousMonth);
?>
<head>
    <?php include_once '../add-asset.html'; ?>
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../../assets/css/reports.css">
</head>
<body>
    <div class="container-fluid p-0">
        <div class="row g-0">
           <?php include_once '../sidebar.php'?>
           
            <!-- Main Content -->
            <div class="col-lg-10 col-md-9 main-content">
                <!-- Page Title -->
                <div class="page-title">
                    <h1><i class="fas fa-chart-pie"></i> Monthly Pattern Analysis</h1>
                    <p>Compare your spending patterns across different months</p>
                </div>
                
                <!-- Month Selector -->
                <div class="month-selector">
                    <form id="monthComparisonForm" method="GET" action="report.php">
                        <div class="row align-items-end">
                            <div class="col-md-5">
                                <label class="form-label fw-semibold">Month 1:</label>
                                <select id="month1" name="month1" class="form-select">
                                    <?php foreach ($availableMonths as $month): ?>
                                        <option value="<?php echo $month['month_year']; ?>" 
                                            <?php echo $month['month_year'] == $currentMonth ? 'selected' : ''; ?>>
                                            <?php echo $month['month_name']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-5">
                                <label class="form-label fw-semibold">Month 2:</label>
                                <select id="month2" name="month2" class="form-select">
                                    <?php foreach ($availableMonths as $month): ?>
                                        <option value="<?php echo $month['month_year']; ?>" 
                                            <?php echo $month['month_year'] == $previousMonth ? 'selected' : ''; ?>>
                                            <?php echo $month['month_name']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-2">
                                <button type="submit" class="btn-compare w-100">
                                    <i class="fas fa-sync-alt me-2"></i> Compare
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
                
                <!-- Summary Cards Row -->
                <div class="row summary-cards g-4 mb-4">
                    <!-- Current Month Card -->
                    <div class="col-md-4">
                        <div class="card summary-card current-month h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <span><?php echo $currentMonthName; ?></span>
                                <span class="badge-custom badge-current">Current</span>
                            </div>
                            <div class="card-body">
                                <div class="summary-item">
                                    <span class="label"><i class="fas fa-arrow-down text-success"></i> Income</span>
                                    <span class="amount income"><?php echo $reportFunctions->formatCurrency($currentMonthSummary['income']); ?></span>
                                </div>
                                <div class="summary-item">
                                    <span class="label"><i class="fas fa-arrow-up text-danger"></i> Expense</span>
                                    <span class="amount expense"><?php echo $reportFunctions->formatCurrency($currentMonthSummary['expense']); ?></span>
                                </div>
                                <div class="summary-item highlight">
                                    <span class="label"><i class="fas fa-piggy-bank"></i> Savings</span>
                                    <span class="amount <?php echo $currentMonthSummary['savings'] >= 0 ? 'text-success' : 'text-danger'; ?>">
                                        <?php echo $reportFunctions->formatCurrency($currentMonthSummary['savings']); ?>
                                    </span>
                                    <span class="text-muted small">(<?php echo $currentMonthSummary['savings_percentage']; ?>%)</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Previous Month Card -->
                    <div class="col-md-4">
                        <div class="card summary-card previous-month h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <span><?php echo $previousMonthName; ?></span>
                                <span class="badge-custom badge-previous">Previous</span>
                            </div>
                            <div class="card-body">
                                <div class="summary-item">
                                    <span class="label"><i class="fas fa-arrow-down text-success"></i> Income</span>
                                    <span class="amount income"><?php echo $reportFunctions->formatCurrency($previousMonthSummary['income']); ?></span>
                                </div>
                                <div class="summary-item">
                                    <span class="label"><i class="fas fa-arrow-up text-danger"></i> Expense</span>
                                    <span class="amount expense"><?php echo $reportFunctions->formatCurrency($previousMonthSummary['expense']); ?></span>
                                </div>
                                <div class="summary-item highlight">
                                    <span class="label"><i class="fas fa-piggy-bank"></i> Savings</span>
                                    <span class="amount <?php echo $previousMonthSummary['savings'] >= 0 ? 'text-success' : 'text-danger'; ?>">
                                        <?php echo $reportFunctions->formatCurrency($previousMonthSummary['savings']); ?>
                                    </span>
                                    <span class="text-muted small">(<?php echo $previousMonthSummary['savings_percentage']; ?>%)</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Comparison Card -->
                    <div class="col-md-4">
                        <div class="card summary-card comparison h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <span>Change</span>
                                <span class="badge-custom badge-comparison">vs Previous</span>
                            </div>
                            <div class="card-body">
                                <?php 
                                $incomeChange = $currentMonthSummary['income'] - $previousMonthSummary['income'];
                                $expenseChange = $currentMonthSummary['expense'] - $previousMonthSummary['expense'];
                                $savingsChange = $currentMonthSummary['savings'] - $previousMonthSummary['savings'];
                                ?>
                                <div class="summary-item">
                                    <span class="label">Income Change</span>
                                    <span class="amount <?php echo $incomeChange >= 0 ? 'text-success' : 'text-danger'; ?>">
                                        <?php echo $incomeChange >= 0 ? '+' : ''; ?><?php echo $reportFunctions->formatCurrency($incomeChange); ?>
                                    </span>
                                </div>
                                <div class="summary-item">
                                    <span class="label">Expense Change</span>
                                    <span class="amount <?php echo $expenseChange <= 0 ? 'text-success' : 'text-danger'; ?>">
                                        <?php echo $expenseChange >= 0 ? '+' : ''; ?><?php echo $reportFunctions->formatCurrency($expenseChange); ?>
                                    </span>
                                </div>
                                <div class="summary-item highlight">
                                    <span class="label">Savings Change</span>
                                    <span class="amount <?php echo $savingsChange >= 0 ? 'text-success' : 'text-danger'; ?>">
                                        <?php echo $savingsChange >= 0 ? '+' : ''; ?><?php echo $reportFunctions->formatCurrency($savingsChange); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Chart Section -->
                <div class="chart-container">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0"><i class="fas fa-chart-bar me-2 text-primary"></i>Category Comparison</h5>
                        <div>
                            <span class="me-3"><span class="badge bg-primary rounded-pill me-1" style="width: 12px; height: 12px;"></span> <?php echo $currentMonthName; ?></span>
                            <span><span class="badge bg-danger rounded-pill me-1"></span> <?php echo $previousMonthName; ?></span>
                        </div>
                    </div>
                    <div class="chart-wrapper">
                        <canvas id="comparisonChart"></canvas>
                    </div>
                </div>
                
                <!-- Comparison Table and Insights Row -->
                <div class="row g-4">
                    <!-- Comparison Table -->
                    <div class="col-lg-8">
                        <div class="table-container">
                            <h5 class="mb-3"><i class="fas fa-table me-2 text-primary"></i>Detailed Comparison</h5>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Category</th>
                                            <th class="text-end"><?php echo $currentMonthName; ?></th>
                                            <th class="text-end"><?php echo $previousMonthName; ?></th>
                                            <th class="text-end">Difference</th>
                                            <th class="text-center">Change %</th>
                                            <th class="text-center">Trend</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $hasData = false;
                                        foreach ($comparison as $category => $data): 
                                            if ($data['month1_amount'] > 0 || $data['month2_amount'] > 0):
                                            $hasData = true;
                                        ?>
                                        <tr>
                                            <td>
                                                <span class="category-badge">                                                    
                                                    <?php echo htmlspecialchars($category); ?>
                                                </span>
                                            </td>
                                            <td class="text-end fw-semibold"><?php echo $reportFunctions->formatCurrency($data['month1_amount']); ?></td>
                                            <td class="text-end fw-semibold"><?php echo $reportFunctions->formatCurrency($data['month2_amount']); ?></td>
                                            <td class="text-end fw-semibold <?php echo $data['difference'] >= 0 ? 'text-danger' : 'text-success'; ?>">
                                                <?php echo $data['difference'] >= 0 ? '+' : ''; ?><?php echo $reportFunctions->formatCurrency($data['difference']); ?>
                                            </td>
                                            <td class="text-center">
                                                <span class="change-badge <?php 
                                                    echo $data['percentage_change'] > 0 ? 'badge-danger' : 
                                                        ($data['percentage_change'] < 0 ? 'badge-success' : 'badge-neutral'); 
                                                ?>">
                                                    <?php echo $data['percentage_change'] > 0 ? '+' : ''; ?><?php echo $data['percentage_change']; ?>%
                                                </span>
                                            </td>
                                            <td class="text-center trend <?php 
                                                echo $data['percentage_change'] > 0 ? 'trend-up' : 
                                                    ($data['percentage_change'] < 0 ? 'trend-down' : 'trend-neutral'); 
                                            ?>">
                                                <?php echo $data['trend']; ?>
                                            </td>
                                        </tr>
                                        <?php endif; endforeach; ?>
                                        
                                        <?php if (!$hasData): ?>
                                        <tr>
                                            <td colspan="6" class="text-center py-5">
                                                <i class="fas fa-chart-line fa-3x text-muted mb-3"></i>
                                                <p class="text-muted">No expense data available for the selected months</p>
                                            </td>
                                        </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Insights -->
                    <div class="col-lg-4">
                        <div class="insights-container">
                            <h5 class="mb-3"><i class="fas fa-lightbulb me-2 text-warning"></i>Insights & Suggestions</h5>
                            <div class="insights-list">
                                <?php if (empty($insights)): ?>
                                    <div class="insight-item">
                                        <div class="insight-icon text-muted"><i class="fas fa-smile"></i></div>
                                        <div class="insight-content">
                                            <p class="text-muted">No insights available for this comparison</p>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($insights as $insight): ?>
                                        <div class="insight-item insight-<?php echo $insight['type']; ?>">
                                            <div class="insight-icon"><?php echo $insight['icon']; ?></div>
                                            <div class="insight-content">
                                                <p><?php echo $insight['message']; ?></p>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                
                                <hr class="my-3">
                                
                                <div class="d-flex flex-wrap gap-2">
                                    <a href="category.php?month=<?php echo $currentMonth; ?>" class="btn btn-outline-custom btn-sm">
                                        <i class="fas fa-chart-pie me-1"></i> Category
                                    </a>
                                    <a href="comparison.php?month1=<?php echo $currentMonth; ?>&month2=<?php echo $previousMonth; ?>" class="btn btn-outline-custom btn-sm">
                                        <i class="fas fa-balance-scale me-1"></i> Comparison
                                    </a>
                                    <a href="spending-trends.php" class="btn btn-outline-custom btn-sm">
                                        <i class="fas fa-chart-line me-1"></i> Trends
                                    </a>
                                    <a href="export-data.php?type=comparison&month1=<?php echo $currentMonth; ?>&month2=<?php echo $previousMonth; ?>" class="btn btn-primary-custom btn-sm">
                                        <i class="fas fa-download me-1"></i> Export
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
    const reportData = {
        categories: <?php echo json_encode(array_keys(array_filter($comparison, function($item) {
            return $item['month1_amount'] > 0 || $item['month2_amount'] > 0;
        }))); ?>,
        currentAmounts: <?php echo json_encode(array_values(array_map(function($item) {
            return $item['month1_amount'];
        }, array_filter($comparison, function($item) {
            return $item['month1_amount'] > 0 || $item['month2_amount'] > 0;
        })))); ?>,
        previousAmounts: <?php echo json_encode(array_values(array_map(function($item) {
            return $item['month2_amount'];
        }, array_filter($comparison, function($item) {
            return $item['month1_amount'] > 0 || $item['month2_amount'] > 0;
        })))); ?>,
        currentMonth: '<?php echo $currentMonthName; ?>',
        previousMonth: '<?php echo $previousMonthName; ?>'
    };
    </script>
    <script src="../../assets/js/reports.js"></script>
    
</body>
</html>