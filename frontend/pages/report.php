<?php
// require_once '../includes/session.php';
require_once '../../backend/report-func.php';

// // Check if user is logged in
// Session::requireLogin();
// $userId = Session::getUserId();
// $userName = Session::getUserName();

$userId = 1;
$userName = "";

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
$insights = $reportFunctions->generateInsights($comparison, $currentMonth, $previousMonth);

// Get month names for display
$currentMonthName = $reportFunctions->getMonthName($currentMonth);
$previousMonthName = $reportFunctions->getMonthName($previousMonth);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monthly Pattern Analysis</title>
    
    <!-- CSS Files -->
    <link rel="stylesheet" href="../assets/css/reports.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h2>💰 Finance</h2>
                <p>Welcome, <?php echo htmlspecialchars($userName); ?></p>
            </div>
            <ul class="sidebar-menu">
                <li><a href="../dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="../transactions/add-expense.php"><i class="fas fa-plus-circle"></i> Add Expense</a></li>
                <li><a href="../transactions/add-income.php"><i class="fas fa-plus-circle"></i> Add Income</a></li>
                <li><a href="../transactions/view-transactions.php"><i class="fas fa-list"></i> Transactions</a></li>
                <li class="active"><a href="monthly-analysis.php"><i class="fas fa-chart-line"></i> Reports</a></li>
                <li><a href="../budget/set-budget.php"><i class="fas fa-tasks"></i> Budget</a></li>
                <li><a href="../profile.php"><i class="fas fa-user"></i> Profile</a></li>
                <li class="logout"><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <div class="page-header">
                <h1><i class="fas fa-chart-pie"></i> Monthly Pattern Analysis</h1>
                <p>Compare your spending patterns across different months</p>
            </div>
            
            <!-- Month Selector -->
            <div class="month-selector-container">
                <form id="monthComparisonForm" method="GET" action="monthly-analysis.php">
                    <div class="month-selector">
                        <div class="selector-group">
                            <label for="month1">Month 1:</label>
                            <select id="month1" name="month1" class="month-select">
                                <?php foreach ($availableMonths as $month): ?>
                                    <option value="<?php echo $month['month_year']; ?>" 
                                        <?php echo $month['month_year'] == $currentMonth ? 'selected' : ''; ?>>
                                        <?php echo $month['month_name']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="selector-group">
                            <label for="month2">Month 2:</label>
                            <select id="month2" name="month2" class="month-select">
                                <?php foreach ($availableMonths as $month): ?>
                                    <option value="<?php echo $month['month_year']; ?>" 
                                        <?php echo $month['month_year'] == $previousMonth ? 'selected' : ''; ?>>
                                        <?php echo $month['month_name']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn-compare">
                            <i class="fas fa-sync-alt"></i> Compare
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Monthly Summary Cards -->
            <div class="summary-cards">
                <div class="summary-card current-month">
                    <div class="card-header">
                        <h3><?php echo $currentMonthName; ?></h3>
                        <span class="badge">Current</span>
                    </div>
                    <div class="card-content">
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
                            <span class="percentage">(<?php echo $currentMonthSummary['savings_percentage']; ?>%)</span>
                        </div>
                    </div>
                </div>
                
                <div class="summary-card previous-month">
                    <div class="card-header">
                        <h3><?php echo $previousMonthName; ?></h3>
                        <span class="badge">Previous</span>
                    </div>
                    <div class="card-content">
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
                            <span class="percentage">(<?php echo $previousMonthSummary['savings_percentage']; ?>%)</span>
                        </div>
                    </div>
                </div>
                
                <div class="summary-card comparison">
                    <div class="card-header">
                        <h3>Change</h3>
                        <span class="badge"><?php echo $currentMonthName; ?> vs <?php echo $previousMonthName; ?></span>
                    </div>
                    <div class="card-content">
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
            
            <!-- Comparison Chart -->
            <div class="chart-container">
                <div class="chart-header">
                    <h3><i class="fas fa-chart-bar"></i> Category Comparison</h3>
                    <div class="chart-legend">
                        <span class="legend-item"><span class="color-box" style="background: #36A2EB;"></span> <?php echo $currentMonthName; ?></span>
                        <span class="legend-item"><span class="color-box" style="background: #FF6384;"></span> <?php echo $previousMonthName; ?></span>
                    </div>
                </div>
                <div class="chart-wrapper">
                    <canvas id="comparisonChart"></canvas>
                </div>
            </div>
            
            <!-- Comparison Table & Insights -->
            <div class="comparison-insights-container">
                <!-- Comparison Table -->
                <div class="comparison-table-wrapper">
                    <h3><i class="fas fa-table"></i> Detailed Comparison</h3>
                    <div class="table-responsive">
                        <table class="comparison-table">
                            <thead>
                                <tr>
                                    <th>Category</th>
                                    <th><?php echo $currentMonthName; ?></th>
                                    <th><?php echo $previousMonthName; ?></th>
                                    <th>Difference</th>
                                    <th>Change %</th>
                                    <th>Trend</th>
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
                                        <span class="category-badge" style="background: <?php echo $data['color']; ?>20; color: <?php echo $data['color']; ?>;">
                                            <?php echo htmlspecialchars($category); ?>
                                        </span>
                                    </td>
                                    <td class="amount"><?php echo $reportFunctions->formatCurrency($data['month1_amount']); ?></td>
                                    <td class="amount"><?php echo $reportFunctions->formatCurrency($data['month2_amount']); ?></td>
                                    <td class="amount <?php echo $data['difference'] >= 0 ? 'text-danger' : 'text-success'; ?>">
                                        <?php echo $data['difference'] >= 0 ? '+' : ''; ?><?php echo $reportFunctions->formatCurrency($data['difference']); ?>
                                    </td>
                                    <td>
                                        <span class="change-badge <?php 
                                            echo $data['percentage_change'] > 0 ? 'badge-danger' : 
                                                ($data['percentage_change'] < 0 ? 'badge-success' : 'badge-neutral'); 
                                        ?>">
                                            <?php echo $data['percentage_change'] > 0 ? '+' : ''; ?><?php echo $data['percentage_change']; ?>%
                                        </span>
                                    </td>
                                    <td class="trend <?php 
                                        echo $data['percentage_change'] > 0 ? 'trend-up' : 
                                            ($data['percentage_change'] < 0 ? 'trend-down' : 'trend-neutral'); 
                                    ?>">
                                        <?php echo $data['trend']; ?>
                                    </td>
                                </tr>
                                <?php endif; endforeach; ?>
                                
                                <?php if (!$hasData): ?>
                                <tr>
                                    <td colspan="6" class="no-data">
                                        <i class="fas fa-chart-line"></i>
                                        <p>No expense data available for the selected months</p>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Insights & Suggestions -->
                <div class="insights-wrapper">
                    <h3><i class="fas fa-lightbulb"></i> Insights & Suggestions</h3>
                    <div class="insights-list">
                        <?php if (empty($insights)): ?>
                            <div class="insight-item empty">
                                <i class="fas fa-smile"></i>
                                <p>No insights available for this comparison</p>
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
                        
                        <div class="action-buttons">
                            <a href="category-report.php?month=<?php echo $currentMonth; ?>" class="btn btn-outline">
                                <i class="fas fa-chart-pie"></i> Category Report
                            </a>
                            <a href="comparison-report.php?month1=<?php echo $currentMonth; ?>&month2=<?php echo $previousMonth; ?>" class="btn btn-outline">
                                <i class="fas fa-balance-scale"></i> Comparison
                            </a>
                            <a href="spending-trends.php" class="btn btn-outline">
                                <i class="fas fa-chart-line"></i> Trends
                            </a>
                            <a href="export-data.php?type=comparison&month1=<?php echo $currentMonth; ?>&month2=<?php echo $previousMonth; ?>" class="btn btn-primary">
                                <i class="fas fa-download"></i> Export Report
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/reports.js"></script>
    <script>
        // Pass PHP data to JavaScript
        const reportData = {
            currentMonth: '<?php echo $currentMonthName; ?>',
            previousMonth: '<?php echo $previousMonthName; ?>',
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
            colors: <?php echo json_encode(array_values(array_map(function($item) {
                return $item['color'];
            }, array_filter($comparison, function($item) {
                return $item['month1_amount'] > 0 || $item['month2_amount'] > 0;
            })))); ?>
        };
    </script>
</body>
</html>
