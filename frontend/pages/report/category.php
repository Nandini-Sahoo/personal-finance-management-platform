<?php
require_once '../../../backend/session.php';
require_once '../../../backend/report-func.php';
require_once '../../../backend/config/dbcon.php';

// Check if user is logged in
Session::requireLogin();
$userId = Session::getUserId();
$userName = Session::getUserName();

// $userId = 1;
// $userName = "";

// Initialize report functions
$reportFunctions = new ReportFunctions();

// Get selected month from URL
$selectedMonth = $_GET['month'] ?? date('Y-m');
$selectedMonthName = $reportFunctions->getMonthName($selectedMonth);

// Get available months for dropdown
$availableMonths = $reportFunctions->getAvailableMonths($userId);

// Get category-wise data for the selected month
$categoryData = getCategoryWiseData($userId, $selectedMonth);

// Get monthly summary
$monthlySummary = $reportFunctions->getMonthlySummary($userId, $selectedMonth);

/**
 * Get category-wise expense data for a specific month
 */
function getCategoryWiseData($userId, $monthYear) {
    $db = getConnection();
    
    $sql = "SELECT 
                c.category_id,
                c.category_name,
                COALESCE(SUM(e.amount), 0) AS total_amount,
                COUNT(e.expense_id) AS transaction_count,
                COALESCE(AVG(e.amount), 0) AS average_amount
            FROM categories c
            LEFT JOIN expenses e 
                ON c.category_id = e.category_id
                AND e.user_id = ?
                AND DATE_FORMAT(e.expense_date, '%Y-%m') = ?
            WHERE c.category_type = 'expense'
            GROUP BY c.category_id, c.category_name
            HAVING total_amount > 0
            ORDER BY total_amount DESC";
    
    $stmt = $db->prepare($sql);
    $stmt->bind_param("is", $userId, $monthYear);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $data = [];
    while ($row = $result->fetch_assoc()) {
        // Add a dynamic color based on category name
        $colors = [
            'Food & Dining' => '#FF6384',
            'Transportation' => '#36A2EB',
            'Shopping' => '#FFCE56',
            'Entertainment' => '#4BC0C0',
            'Bills & Utilities' => '#9966FF',
            'Healthcare' => '#FF9F40',
            'Education' => '#8AC926',
            'Travel' => '#1982C4',
            'Rent' => '#6A4C93',
            'Groceries' => '#F94144',
            'Insurance' => '#F3722C',
            'Personal Care' => '#F8961E',
            'Gifts & Donations' => '#F9C74F',
            'Others' => '#90BE6D'
        ];
        $row['color'] = $colors[$row['category_name']] ?? '#4361ee';
        $data[] = $row;
    }
    
    $stmt->close();
    $db->close();
    return $data;
}

?>
<head>
    <?php include_once '../add-asset.html'; ?>
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../../assets/css/report/category.css">
</head>
<body>
    <div class="container-fluid p-0">
        <div class="row g-0">

            <?php include_once '../sidebar.php'?>

            <!-- Main Content -->
            <div class="col-lg-10 col-md-9 main-content">
                <!-- Page Title -->
                <div class="page-title">
                    <h1><i class="fas fa-chart-pie"></i> Category-Wise Report</h1>
                    <p>Analyze your spending by category</p>
                </div>
                
                <!-- Month Selector -->
                <div class="month-selector">
                    <form method="GET" action="category.php">
                        <div class="row align-items-end">
                            <div class="col-md-3">
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
                                    <i class="fas fa-eye me-2"></i> View Report
                                </button>
                            </div>
                            <div class="col-md-3 text-end">
                                <a href="export-data.php?type=category&month=<?php echo $selectedMonth; ?>" class="btn-export w-100">
                                    <i class="fas fa-download"></i> Export CSV
                                </a>
                            </div>
                            <div class="col-md-3 text-end">
                                <a href="export-data.php?month=<?php echo $selectedMonth; ?>" class="btn-export w-100">
                                    <i class="fas fa-file-pdf"></i> Export PDF
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
                
                <!-- Summary Cards -->
                <div class="row g-4 mb-4">
                    <div class="col-md-4">
                        <div class="summary-card">
                            <div class="label">Total Expense</div>
                            <div class="stat"><?php echo $reportFunctions->formatCurrency($monthlySummary['expense']); ?></div>
                            <div class="label">for <?php echo $selectedMonthName; ?></div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="summary-card" style="background: linear-gradient(135deg, #06d6a0, #0ca678);">
                            <div class="label">Categories Used</div>
                            <div class="stat"><?php echo count($categoryData); ?></div>
                            <div class="label">out of 14 expense categories</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="summary-card" style="background: linear-gradient(135deg, #ffb703, #f59f00);">
                            <div class="label">Average per Category</div>
                            <div class="stat">
                                <?php 
                                $avg = count($categoryData) > 0 ? $monthlySummary['expense'] / count($categoryData) : 0;
                                echo $reportFunctions->formatCurrency($avg);
                                ?>
                            </div>
                            <div class="label">per category</div>
                        </div>
                    </div>
                </div>
                
                <!-- Category Grid -->
                <?php if (empty($categoryData)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-chart-pie fa-4x text-muted mb-3"></i>
                        <h5 class="text-muted">No expense data for <?php echo $selectedMonthName; ?></h5>
                        <p class="text-muted">Add some expenses to see category-wise breakdown</p>
                        <a href="../transactions/add-expense.php" class="btn btn-primary mt-3">
                            <i class="fas fa-plus-circle me-2"></i>Add Expense
                        </a>
                    </div>
                <?php else: ?>
                    <div class="row g-4">
                        <?php foreach ($categoryData as $index => $category): ?>
                            <div class="col-xl-4 col-lg-6">
                                <div class="category-card">
                                    <div class="category-header" style="background: <?php echo $category['color']; ?>20; color: <?php echo $category['color']; ?>;">                                        <span><i class="fas fa-tag me-2"></i><?php echo htmlspecialchars($category['category_name']); ?></span>
                                        <span class="badge bg-white text-dark">#<?php echo $index + 1; ?></span>
                                    </div>
                                    <div class="category-body">
                                        <div class="stat-item">
                                            <span class="stat-label"><i class="fas fa-rupee-sign me-2"></i>Total Spent</span>
                                            <span class="stat-value"><?php echo $reportFunctions->formatCurrency($category['total_amount']); ?></span>
                                        </div>
                                        <div class="stat-item">
                                            <span class="stat-label"><i class="fas fa-calculator me-2"></i>Average</span>
                                            <span class="stat-value"><?php echo $reportFunctions->formatCurrency($category['average_amount']); ?></span>
                                        </div>
                                        <div class="stat-item">
                                            <span class="stat-label"><i class="fas fa-shopping-cart me-2"></i>Transactions</span>
                                            <span class="stat-value"><?php echo $category['transaction_count']; ?></span>
                                        </div>
                                        <div class="mt-3">
                                            <div class="d-flex justify-content-between mb-1">
                                                <small>% of total</small>
                                                <small class="fw-bold">
                                                    <?php echo round(($category['total_amount'] / $monthlySummary['expense']) * 100, 1); ?>%
                                                </small>
                                            </div>
                                            <div class="progress">
                                                <div class="progress-bar" style="width: <?php echo ($category['total_amount'] / $monthlySummary['expense']) * 100; ?>%; background: <?php echo $category['color']; ?>;"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
