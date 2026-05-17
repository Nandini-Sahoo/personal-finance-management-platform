<?php
// Include required files
require_once "../../../backend/config/dbcon.php";
require_once '../../../backend/session.php';

// Check if user is logged in
Session::requireLogin();
$userId = Session::getUserId();
$userName = Session::getUserName();

// Get database connection
$conn = getConnection();
if ($conn->connect_error) {
    die("Database connection failed");
}

// Get username for sidebar
$username_qry = "SELECT name FROM users WHERE user_id = ?";
$stmt_user = $conn->prepare($username_qry);
$stmt_user->bind_param("i", $userId);
$stmt_user->execute();
$user_result = $stmt_user->get_result();
$userData = $user_result->fetch_assoc();
$username = $userData['name'] ?? 'User';
$stmt_user->close();

// Pagination settings
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Get filter values
$month = isset($_GET['month']) ? $_GET['month'] : '';
$type = isset($_GET['type']) ? $_GET['type'] : '';
$category = isset($_GET['category']) ? $_GET['category'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Base queries without filters
$data_query = "SELECT * FROM (
    SELECT 
        'expense' as type,
        e.expense_id as id,
        DATE_FORMAT(e.expense_date, '%d/%m') as display_date,
        DATE_FORMAT(e.expense_date, '%Y-%m-%d') as full_date,
        c.category_name,
        e.notes as description,
        e.amount,
        e.payment_method
    FROM expenses e
    JOIN categories c ON e.category_id = c.category_id
    WHERE e.user_id = ?
    
    UNION ALL
    
    SELECT 
        'income' as type,
        i.income_id as id,
        DATE_FORMAT(i.income_date, '%d/%m') as display_date,
        DATE_FORMAT(i.income_date, '%Y-%m-%d') as full_date,
        c.category_name,
        i.source as description,
        i.amount,
        i.payment_method
    FROM income i
    JOIN categories c ON i.category_id = c.category_id
    WHERE i.user_id = ?
) AS combined WHERE 1=1";

$count_query = "SELECT COUNT(*) as total FROM (
    SELECT 
        e.expense_id as id,
        'expense' as type,
        c.category_name,
        e.notes as description
    FROM expenses e
    JOIN categories c ON e.category_id = c.category_id
    WHERE e.user_id = ?
    
    UNION ALL
    
    SELECT 
        i.income_id as id,
        'income' as type,
        c.category_name,
        i.source as description
    FROM income i
    JOIN categories c ON i.category_id = c.category_id
    WHERE i.user_id = ?
) AS combined WHERE 1=1";

// Parameters array
$params = [$userId, $userId];
$countParams = [$userId, $userId];
$types = "ii";
$countTypes = "ii";

// Apply month filter
if (!empty($month)) {
    $data_query .= " AND DATE_FORMAT(full_date, '%Y-%m') = ?";
    $count_query .= " AND DATE_FORMAT(
        CASE 
            WHEN type = 'expense' THEN (
                SELECT expense_date FROM expenses e2 WHERE e2.expense_id = combined.id
            )
            ELSE (
                SELECT income_date FROM income i2 WHERE i2.income_id = combined.id
            )
        END, '%Y-%m') = ?";
    $params[] = $month;
    $countParams[] = $month;
    $types .= "s";
    $countTypes .= "s";
}

// Apply type filter
if (!empty($type)) {
    $data_query .= " AND type = ?";
    $count_query .= " AND type = ?";
    $params[] = $type;
    $countParams[] = $type;
    $types .= "s";
    $countTypes .= "s";
}

// Apply category filter
if (!empty($category)) {
    $data_query .= " AND category_name = ?";
    $count_query .= " AND category_name = ?";
    $params[] = $category;
    $countParams[] = $category;
    $types .= "s";
    $countTypes .= "s";
}

// Apply search filter
if (!empty($search)) {
    $search_term = "%$search%";
    $data_query .= " AND (description LIKE ? OR category_name LIKE ?)";
    $count_query .= " AND (description LIKE ? OR category_name LIKE ?)";
    $params[] = $search_term;
    $params[] = $search_term;
    $countParams[] = $search_term;
    $countParams[] = $search_term;
    $types .= "ss";
    $countTypes .= "ss";
}

// Get total count for pagination
$stmt_count = $conn->prepare($count_query);
if (!empty($countParams)) {
    $stmt_count->bind_param($countTypes, ...$countParams);
}
$stmt_count->execute();
$count_result = $stmt_count->get_result();
$total_records = $count_result->fetch_assoc()['total'] ?? 0;
$total_pages = $total_records > 0 ? ceil($total_records / $limit) : 1;
$stmt_count->close();

// Add sorting and pagination to data query
$data_query .= " ORDER BY full_date DESC, id DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= "ii";

// Get filtered and paginated data
$stmt_data = $conn->prepare($data_query);
if (!empty($params)) {
    $stmt_data->bind_param($types, ...$params);
}
$stmt_data->execute();
$transactions = $stmt_data->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_data->close();

// Get unique months for filter dropdown
$months_query = "SELECT DISTINCT DATE_FORMAT(expense_date, '%Y-%m') as month_value, 
                        DATE_FORMAT(expense_date, '%M %Y') as month_name 
                 FROM expenses WHERE user_id = ?
                 UNION 
                 SELECT DISTINCT DATE_FORMAT(income_date, '%Y-%m') as month_value, 
                        DATE_FORMAT(income_date, '%M %Y') as month_name 
                 FROM income WHERE user_id = ?
                 ORDER BY month_value DESC";
$stmt_months = $conn->prepare($months_query);
$stmt_months->bind_param("ii", $userId, $userId);
$stmt_months->execute();
$months = $stmt_months->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_months->close();

// Get categories for filter dropdown
$categories_query = "SELECT DISTINCT category_name FROM categories WHERE category_type IN ('income', 'expense') ORDER BY category_name";
$categories_result = $conn->query($categories_query);
$categories = $categories_result->fetch_all(MYSQLI_ASSOC);

$conn->close();

// Include assets
include_once '../add-asset.html';
?>

<!-- Custom CSS for Transactions -->
<link rel="stylesheet" href="../../assets/css/transaction.css">

<style>
    /* Additional fixes to match dashboard layout */
    .transactions-content {
        padding: 2rem;
        background: var(--bg-dark);
        min-height: 100vh;
    }
    
    /* Ensure the container works with Bootstrap grid */
    .container-fluid.p-0 {
        overflow: hidden;
    }
    
    .row.g-0 {
        margin: 0;
    }
    
    /* Fix for sidebar */
    .sidebar {
        background: linear-gradient(180deg, var(--dark-color) 0%, #1a1e2c 100%);
        min-height: 100vh;
        color: white;
        position: sticky;
        top: 0;
    }
    
    /* Override any conflicting styles */
    .main-content {
        background: var(--bg-dark);
    }
    
    /* Ensure cards have proper background */
    .filters-card,
    .table-card {
        background: var(--bg-card);
        border: 1px solid var(--border-color);
    }
    
    /* Fix for table text color */
    .table {
        color: #ffffff;
    }
    
    .table thead th {
        background: #1e3a5f;
        color: var(--accent-color);
    }
    
    /* Fix for pagination */
    .page-link {
        background: var(--bg-card);
        color: #fff;
    }
    
    /* Fix for export buttons */
    .btn-export {
        background: transparent;
        color: #fff;
    }
</style>

<body>
    <div class="container-fluid p-0">
        <div class="row g-0">
            <?php 
            // Set username for sidebar
            $userName = $username;
            include_once '../sidebar.php';
            ?>
            
            <!-- Main Content -->
            <div class="col-lg-10 col-md-9 main-content transactions-content">
                
                <!-- Page Title -->
                <div class="page-title">
                    <h1><i class="fas fa-exchange-alt"></i> ALL TRANSACTIONS</h1>
                    <p>View and manage your financial transactions</p>
                </div>
                
                <!-- Filters Card -->
                <div class="filters-card">
                    <form method="GET" action="transactions.php" id="filterForm">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <div class="filter-label">
                                    <i class="fas fa-calendar me-2"></i>Month
                                </div>
                                <select name="month" class="filter-select">
                                    <option value="">All Months</option>
                                    <?php foreach ($months as $m): ?>
                                        <option value="<?php echo $m['month_value']; ?>" 
                                            <?php echo $month == $m['month_value'] ? 'selected' : ''; ?>>
                                            <?php echo $m['month_name']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-2">
                                <div class="filter-label">
                                    <i class="fas fa-tag me-2"></i>Type
                                </div>
                                <select name="type" class="filter-select">
                                    <option value="">All</option>
                                    <option value="income" <?php echo $type == 'income' ? 'selected' : ''; ?>>Income</option>
                                    <option value="expense" <?php echo $type == 'expense' ? 'selected' : ''; ?>>Expense</option>
                                </select>
                            </div>
                            
                            <div class="col-md-3">
                                <div class="filter-label">
                                    <i class="fas fa-list me-2"></i>Category
                                </div>
                                <select name="category" class="filter-select">
                                    <option value="">All Categories</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo $cat['category_name']; ?>" 
                                            <?php echo $category == $cat['category_name'] ? 'selected' : ''; ?>>
                                            <?php echo $cat['category_name']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-2">
                                <div class="filter-label">
                                    <i class="fas fa-search me-2"></i>Search
                                </div>
                                <input type="text" name="search" class="filter-input" 
                                       placeholder="Description..." value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            
                            <div class="col-md-2 d-flex align-items-end gap-2">
                                <button type="submit" class="btn-filter">
                                    <i class="fas fa-filter me-2"></i>Apply
                                </button>
                                <?php if (!empty($month) || !empty($type) || !empty($category) || !empty($search)): ?>
                                    <a href="transactions.php" class="btn-reset">
                                        <i class="fas fa-times me-2"></i>Clear
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </form>
                </div>
                
                <!-- Transactions Table Card -->
                <div class="table-card">
                    <div class="table-header">
                        <h5>
                            <i class="fas fa-list-ul"></i>
                            TRANSACTIONS TABLE
                        </h5>
                        <span class="table-count">
                            <i class="fas fa-database me-2"></i>
                            Showing <?php echo count($transactions); ?> of <?php echo $total_records; ?> transactions
                        </span>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th>Category</th>
                                    <th>Description</th>
                                    <th>Payment Method</th>
                                    <th>Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($transactions)): ?>
                                    <tr>
                                        <td colspan="6" class="empty-state">
                                            <i class="fas fa-receipt"></i>
                                            <p>No transactions found</p>
                                            <div class="mt-3">
                                                <a href="add_income.php" class="btn-filter" style="width: auto; padding: 0.6rem 1.5rem; margin-right: 1rem;">
                                                    <i class="fas fa-plus me-2"></i>Add Income
                                                </a>
                                                <a href="add_expense.php" class="btn-filter" style="width: auto; padding: 0.6rem 1.5rem; background: var(--danger-color);">
                                                    <i class="fas fa-plus me-2"></i>Add Expense
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($transactions as $trans): ?>
                                        <tr>
                                            <td>
                                                <span style="color: var(--accent-color); font-weight: 600;">
                                                    <?php echo $trans['display_date']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="<?php echo $trans['type'] == 'income' ? 'badge-income' : 'badge-expense'; ?>">
                                                    <i class="fas <?php echo $trans['type'] == 'income' ? 'fa-arrow-down' : 'fa-arrow-up'; ?> me-1"></i>
                                                    <?php echo ucfirst($trans['type']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="category-badge">
                                                    <i class="fas fa-circle me-2" style="color: <?php echo $trans['type'] == 'income' ? '#06d6a0' : '#ef476f'; ?>; font-size: 8px;"></i>
                                                    <?php echo htmlspecialchars($trans['category_name']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php echo htmlspecialchars($trans['description'] ?: '-'); ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary">
                                                    <?php echo htmlspecialchars($trans['payment_method'] ?? 'N/A'); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="<?php echo $trans['type'] == 'income' ? 'amount-income' : 'amount-expense'; ?>">
                                                    <?php echo $trans['type'] == 'income' ? '+' : '-'; ?> 
                                                    ₹<?php echo number_format($trans['amount'], 2); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div class="pagination">
                            <?php if ($page > 1): ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" class="page-link">
                                    <i class="fas fa-chevron-left"></i> Prev
                                </a>
                            <?php endif; ?>
                            
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <?php if ($i >= $page - 2 && $i <= $page + 2): ?>
                                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" 
                                       class="page-link <?php echo $i == $page ? 'active' : ''; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endif; ?>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" class="page-link">
                                    Next <i class="fas fa-chevron-right"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                        
                        <div class="info-text">
                            <i class="fas fa-info-circle"></i>
                            Showing page <?php echo $page; ?> of <?php echo $total_pages; ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Export Buttons -->
                    <div class="export-buttons">
                        <button class="btn-export csv" onclick="exportData('csv')">
                            <i class="fas fa-file-csv"></i>
                            Export to CSV
                        </button>
                        <button class="btn-export pdf" onclick="exportData('pdf')">
                            <i class="fas fa-file-pdf"></i>
                            Export to PDF
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Export data function
        function exportData(format) {
            // Get current filter parameters
            const urlParams = new URLSearchParams(window.location.search);
            const month = urlParams.get('month') || '';
            const type = urlParams.get('type') || '';
            const category = urlParams.get('category') || '';
            const search = urlParams.get('search') || '';
            
            // Redirect to export page with filters
            window.location = '../report/export-data.php?type=' + format + 
                '&month=' + encodeURIComponent(month) + 
                '&transaction_type=' + encodeURIComponent(type) + 
                '&category=' + encodeURIComponent(category) + 
                '&search=' + encodeURIComponent(search);
        }
    </script>
    
    <?php include_once "../user/footer.php"; ?>
</body>
</html>