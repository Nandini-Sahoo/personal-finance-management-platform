<?php
// require_once '../includes/session.php';
require_once '../../../backend/report-func.php';

// Check if user is logged in
// Session::requireLogin();
// $userId = Session::getUserId();
// $userName = Session::getUserName();
$userId = 1;
$userName = "";

// Initialize report functions
$reportFunctions = new ReportFunctions();

// Get available months and years for dropdowns
$availableMonths = $reportFunctions->getAvailableMonths($userId);
$years = getAvailableYears($userId);

// Get categories for filtering
$categories = getExpenseCategories();

/**
 * Get available years for user
 */
function getAvailableYears($userId) {
    global $db;
    
    $sql = "SELECT DISTINCT YEAR(transaction_date) as year 
            FROM (
                SELECT expense_date as transaction_date FROM expenses WHERE user_id = ?
                UNION
                SELECT income_date as transaction_date FROM income WHERE user_id = ?
            ) as dates
            ORDER BY year DESC";
    
    $stmt = $db->prepare($sql);
    $stmt->bind_param("ii", $userId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $years = [];
    while ($row = $result->fetch_assoc()) {
        $years[] = $row['year'];
    }
    
    $stmt->close();
    return $years;
}

/**
 * Get expense categories
 */
function getExpenseCategories() {
    global $db;
    
    $sql = "SELECT category_id, category_name FROM categories WHERE category_type = 'expense' ORDER BY category_name";
    $result = $db->query($sql);
    
    $categories = [];
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
    
    return $categories;
}

// Handle export request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $exportType = $_POST['export_type'] ?? 'transactions';
    $format = $_POST['format'] ?? 'csv';
    $dateRange = $_POST['date_range'] ?? 'custom';
    $startDate = $_POST['start_date'] ?? '';
    $endDate = $_POST['end_date'] ?? '';
    $month = $_POST['month'] ?? '';
    $year = $_POST['year'] ?? '';
    $categoryId = $_POST['category'] ?? '';
    
    // Determine date range based on selection
    if ($dateRange === 'month' && !empty($month)) {
        $startDate = $month . '-01';
        $endDate = date('Y-m-t', strtotime($startDate));
    } elseif ($dateRange === 'year' && !empty($year)) {
        $startDate = $year . '-01-01';
        $endDate = $year . '-12-31';
    }
    
    // Generate filename
    $filename = $exportType . '_' . date('Ymd_His') . '.' . $format;
    
    // Set headers based on format
    if ($format === 'csv') {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // Add headers based on export type
        if ($exportType === 'transactions') {
            fputcsv($output, ['Date', 'Type', 'Category', 'Amount', 'Description', 'Payment Method']);
            
            // Fetch transactions
            $sql = "SELECT 
                        t.transaction_date,
                        t.type,
                        c.category_name,
                        t.amount,
                        t.description,
                        t.payment_method
                    FROM (
                        SELECT expense_id as id, expense_date as transaction_date, 'expense' as type, 
                               category_id, amount, notes as description, payment_method
                        FROM expenses WHERE user_id = ? AND expense_date BETWEEN ? AND ?
                        UNION ALL
                        SELECT income_id as id, income_date as transaction_date, 'income' as type,
                               category_id, amount, source as description, 'N/A' as payment_method
                        FROM income WHERE user_id = ? AND income_date BETWEEN ? AND ?
                    ) t
                    JOIN categories c ON t.category_id = c.category_id
                    ORDER BY t.transaction_date DESC";
            
            $stmt = $db->prepare($sql);
            $stmt->bind_param("isssis", $userId, $startDate, $endDate, $userId, $startDate, $endDate);
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                fputcsv($output, [
                    $row['transaction_date'],
                    ucfirst($row['type']),
                    $row['category_name'],
                    $row['amount'],
                    $row['description'],
                    $row['payment_method']
                ]);
            }
            
            $stmt->close();
        } elseif ($exportType === 'category_summary') {
            fputcsv($output, ['Category', 'Total Amount', 'Transaction Count', 'Average']);
            
            $sql = "SELECT 
                        c.category_name,
                        COALESCE(SUM(e.amount), 0) as total_amount,
                        COUNT(e.expense_id) as transaction_count,
                        COALESCE(AVG(e.amount), 0) as average
                    FROM categories c
                    LEFT JOIN expenses e ON c.category_id = e.category_id 
                        AND e.user_id = ? 
                        AND e.expense_date BETWEEN ? AND ?
                    WHERE c.category_type = 'expense'
                    GROUP BY c.category_id, c.category_name
                    HAVING total_amount > 0
                    ORDER BY total_amount DESC";
            
            $stmt = $db->prepare($sql);
            $stmt->bind_param("iss", $userId, $startDate, $endDate);
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                fputcsv($output, [
                    $row['category_name'],
                    $row['total_amount'],
                    $row['transaction_count'],
                    $row['average']
                ]);
            }
            
            $stmt->close();
        }
        
        fclose($output);
        exit();
    } else {
        // For PDF, you would use a library like TCPDF or FPDF
        // For now, just show a message
        $message = "PDF export will be implemented using a PDF library like TCPDF";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Export Data - <?php echo SITE_NAME; ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../../assets/css/reports.css">
    
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
        
        /* Sidebar Styles (same as before) */
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
        
        /* Export Card */
        .export-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            max-width: 600px;
            margin: 0 auto;
        }
        
        .export-card h5 {
            color: var(--dark-color);
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #f1f3f5;
        }
        
        .form-label {
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 0.5rem;
        }
        
        .btn-export {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border: none;
            padding: 0.8rem 2rem;
            border-radius: 10px;
            font-weight: 600;
            width: 100%;
            transition: all 0.3s;
        }
        
        .btn-export:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(67,97,238,0.3);
            color: white;
        }
        
        .info-box {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1rem;
            margin-top: 1rem;
            font-size: 0.9rem;
            color: #6c757d;
        }
        
        .info-box i {
            color: var(--primary-color);
            margin-right: 0.5rem;
        }
        
        @media (max-width: 992px) {
            .sidebar {
                min-height: auto;
                position: relative;
            }
            
            .main-content {
                padding: 1.5rem;
            }
            
            .export-card {
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
                    <h1><i class="fas fa-download"></i> Export Data</h1>
                    <p>Export your financial data in various formats</p>
                </div>
                
                <!-- Export Form -->
                <div class="export-card">
                    <h5><i class="fas fa-file-export me-2 text-primary"></i>Export Options</h5>
                    
                    <?php if (isset($message)): ?>
                        <div class="alert alert-info alert-dismissible fade show" role="alert">
                            <i class="fas fa-info-circle me-2"></i><?php echo $message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="export-data.php">
                        <!-- Export Type -->
                        <div class="mb-3">
                            <label class="form-label">Export Type</label>
                            <select name="export_type" class="form-select" required>
                                <option value="transactions">All Transactions</option>
                                <option value="category_summary">Category Summary</option>
                                <option value="budget_report">Budget Report</option>
                                <option value="monthly_summary">Monthly Summary</option>
                            </select>
                        </div>
                        
                        <!-- Format -->
                        <div class="mb-3">
                            <label class="form-label">File Format</label>
                            <div class="d-flex gap-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="format" id="formatCsv" value="csv" checked>
                                    <label class="form-check-label" for="formatCsv">
                                        <i class="fas fa-file-csv me-1 text-success"></i> CSV
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="format" id="formatPdf" value="pdf">
                                    <label class="form-check-label" for="formatPdf">
                                        <i class="fas fa-file-pdf me-1 text-danger"></i> PDF
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Date Range -->
                        <div class="mb-3">
                            <label class="form-label">Date Range</label>
                            <select name="date_range" class="form-select" id="dateRange">
                                <option value="custom">Custom Range</option>
                                <option value="month">Specific Month</option>
                                <option value="year">Specific Year</option>
                                <option value="all">All Time</option>
                            </select>
                        </div>
                        
                        <!-- Custom Date Range -->
                        <div id="customRange" class="row g-2 mb-3">
                            <div class="col-md-6">
                                <input type="date" name="start_date" class="form-control" placeholder="Start Date">
                            </div>
                            <div class="col-md-6">
                                <input type="date" name="end_date" class="form-control" placeholder="End Date">
                            </div>
                        </div>
                        
                        <!-- Month Selection -->
                        <div id="monthSelection" class="mb-3" style="display: none;">
                            <select name="month" class="form-select">
                                <option value="">Select Month</option>
                                <?php foreach ($availableMonths as $month): ?>
                                    <option value="<?php echo $month['month_year']; ?>">
                                        <?php echo $month['month_name']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Year Selection -->
                        <div id="yearSelection" class="mb-3" style="display: none;">
                            <select name="year" class="form-select">
                                <option value="">Select Year</option>
                                <?php foreach ($years as $year): ?>
                                    <option value="<?php echo $year; ?>"><?php echo $year; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Category Filter -->
                        <div class="mb-3">
                            <label class="form-label">Category (Optional)</label>
                            <select name="category" class="form-select">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['category_id']; ?>">
                                        <?php echo $category['category_name']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn-export">
                            <i class="fas fa-download me-2"></i>Export Data
                        </button>
                        
                        <div class="info-box">
                            <i class="fas fa-info-circle"></i>
                            CSV files can be opened in Excel, Google Sheets, or any spreadsheet application.
                            PDF export includes formatted tables and charts.
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        document.getElementById('dateRange').addEventListener('change', function() {
            const customRange = document.getElementById('customRange');
            const monthSelection = document.getElementById('monthSelection');
            const yearSelection = document.getElementById('yearSelection');
            
            switch(this.value) {
                case 'custom':
                    customRange.style.display = 'flex';
                    monthSelection.style.display = 'none';
                    yearSelection.style.display = 'none';
                    break;
                case 'month':
                    customRange.style.display = 'none';
                    monthSelection.style.display = 'block';
                    yearSelection.style.display = 'none';
                    break;
                case 'year':
                    customRange.style.display = 'none';
                    monthSelection.style.display = 'none';
                    yearSelection.style.display = 'block';
                    break;
                case 'all':
                    customRange.style.display = 'none';
                    monthSelection.style.display = 'none';
                    yearSelection.style.display = 'none';
                    break;
            }
        });
    </script>
</body>
</html>
<?php $db->close(); ?>