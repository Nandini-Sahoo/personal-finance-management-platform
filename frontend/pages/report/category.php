<?php
// require_once '../includes/session.php';
require_once '../../../backend/report-func.php';
require_once '../../../backend/config/dbcon.php';

// Check if user is logged in
// Session::requireLogin();
// $userId = Session::getUserId();
// $userName = Session::getUserName();

$userId = 1;
$userName = "";

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
    $db=getConnection();
    
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
            ORDER BY total_amount DESC;";
    
    $stmt = $db->prepare($sql);
    $stmt->bind_param("is", $userId, $monthYear);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    
    $stmt->close();
    $db->close();
    return $data;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Category Report</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href=".../../assets/css/reports.css">
    
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #7209b7;
            --success-color: #06d6a0;
            --warning-color: #ffb703;
            --danger-color: #ef476f;
            --dark-color: #2b2d42;
        }
        
        body {
            background-color: #f4f7fc;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }
        
        /* Sidebar Styles (same as monthly-analysis.php) */
        .sidebar {
            background: linear-gradient(180deg, var(--dark-color) 0%, #1a1e2c 100%);
            min-height: 100vh;
            color: white;
            position: sticky;
            top: 0;
        }
        
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 0.8rem 1rem;
            margin: 0.2rem 0;
            border-radius: 8px;
            transition: all 0.3s;
        }
        
        .sidebar .nav-link:hover {
            background: rgba(255,255,255,0.1);
            color: white;
            transform: translateX(5px);
        }
        
        .sidebar .nav-link.active {
            background: linear-gradient(90deg, rgba(67,97,238,0.3), transparent);
            color: white;
            border-left: 4px solid var(--primary-color);
        }
        
        .sidebar .nav-link i {
            width: 24px;
            margin-right: 10px;
        }
        
        .sidebar .nav-link.logout {
            color: var(--danger-color);
        }
        
        .sidebar .nav-link.logout:hover {
            background: rgba(239,71,111,0.1);
        }
        
        .user-info {
            padding: 1.5rem 1rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 1rem;
        }
        
        .user-info h5 {
            margin: 0;
            font-size: 1.1rem;
        }
        
        .user-info small {
            color: rgba(255,255,255,0.6);
        }
        
        /* Main Content Styles */
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
        
        /* Category Cards */
        .category-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            transition: transform 0.3s, box-shadow 0.3s;
            overflow: hidden;
        }
        
        .category-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .category-header {
            padding: 1rem;
            color: white;
            font-weight: 600;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .category-body {
            padding: 1.5rem;
            background: white;
        }
        
        .stat-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 0;
            border-bottom: 1px solid #f1f3f5;
        }
        
        .stat-item:last-child {
            border-bottom: none;
        }
        
        .stat-label {
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .stat-value {
            font-weight: 700;
            color: var(--dark-color);
        }
        
        /* Summary Cards */
        .summary-card {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border: none;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .summary-card .stat {
            font-size: 2rem;
            font-weight: 700;
            margin: 0.5rem 0;
        }
        
        .summary-card .label {
            opacity: 0.9;
            font-size: 0.9rem;
        }
        
        /* Progress Bar */
        .progress {
            height: 8px;
            border-radius: 4px;
            background-color: #e9ecef;
        }
        
        .progress-bar {
            border-radius: 4px;
        }
        
        /* Month Selector */
        .month-selector {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            margin-bottom: 2rem;
        }
        
        .btn-view {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border: none;
            padding: 0.6rem 2rem;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-view:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(67,97,238,0.3);
            color: white;
        }
        
        .btn-export {
            background: white;
            border: 2px solid #e9ecef;
            color: var(--dark-color);
            padding: 0.6rem 1.5rem;
            border-radius: 10px;
            font-weight: 500;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-export:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
        }
        
        @media (max-width: 992px) {
            .sidebar {
                min-height: auto;
                position: relative;
            }
            
            .main-content {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid p-0">
        <div class="row g-0">
            <!-- Sidebar -->
            <div class="col-lg-2 col-md-3 sidebar">
                <div class="user-info">
                    <h5 class="text-white">Welcome,</h5>
                    <h5 class="text-white fw-bold"><?php echo htmlspecialchars($userName); ?></h5>
                    <small>Personal Finance Platform</small>
                </div>
                
                <nav class="nav flex-column px-3">
                    <a class="nav-link" href="../dashboard.php">
                        <i class="fas fa-home"></i> Dashboard
                    </a>
                    <a class="nav-link" href="../transactions/add-expense.php">
                        <i class="fas fa-plus-circle"></i> Add Expense
                    </a>
                    <a class="nav-link" href="../transactions/add-income.php">
                        <i class="fas fa-plus-circle"></i> Add Income
                    </a>
                    <a class="nav-link" href="../transactions/view-transactions.php">
                        <i class="fas fa-list"></i> Transactions
                    </a>
                    <a class="nav-link active" href="monthly-analysis.php">
                        <i class="fas fa-chart-line"></i> Reports
                    </a>
                    <a class="nav-link" href="../budget/set-budget.php">
                        <i class="fas fa-tasks"></i> Budget
                    </a>
                    <a class="nav-link" href="../profile.php">
                        <i class="fas fa-user"></i> Profile
                    </a>
                    <hr class="text-white-50 my-3">
                    <a class="nav-link logout" href="../logout.php">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </nav>
            </div>
            
            <!-- Main Content -->
            <div class="col-lg-10 col-md-9 main-content">
                <!-- Page Title -->
                <div class="page-title">
                    <h1><i class="fas fa-chart-pie"></i> Category-Wise Report</h1>
                    <p>Analyze your spending by category</p>
                </div>
                
                <!-- Month Selector -->
                <div class="month-selector">
                    <form method="GET" action="category-report.php">
                        <div class="row align-items-end">
                            <div class="col-md-6">
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
                                    <div class="category-header" style="background: <?php echo $category['color']; ?>;">
                                        <span><i class="fas fa-tag me-2"></i><?php echo htmlspecialchars($category['category_name']); ?></span>
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
    
    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
