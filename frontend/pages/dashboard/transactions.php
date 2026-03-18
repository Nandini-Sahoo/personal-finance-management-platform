<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include required files
include_once '../sidebar.php';
include_once '../add-asset.html';
include_once "check.php";
require_once "../../../backend/config/dbcon.php";

// Get database connection
$conn = getConnection();
if ($conn->connect_error) {
    die("Database connection failed");
}

// Get user ID from session or set default for testing
// $user_id = $_SESSION['user_id'] ?? 1; // Uncomment when session is working
$user_id = 1; // For testing

// Get username for sidebar
$username_qry = "SELECT name FROM users WHERE user_id = ?";
$stmt_user = $conn->prepare($username_qry);
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$user_result = $stmt_user->get_result();
$userData = $user_result->fetch_assoc();
$username = $userData['name'] ?? 'User';

// Pagination settings
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Get filter values
$month = isset($_GET['month']) ? $_GET['month'] : '';
$type = isset($_GET['type']) ? $_GET['type'] : '';
$category = isset($_GET['category']) ? $_GET['category'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Build the base query
$count_query = "SELECT COUNT(*) as total FROM (
    SELECT 'expense' as transaction_type, e.expense_id, e.expense_date, c.category_name, e.notes as description, e.amount
    FROM expenses e
    JOIN categories c ON e.category_id = c.category_id
    WHERE e.user_id = ?
    UNION ALL
    SELECT 'income' as transaction_type, i.income_id, i.income_date, c.category_name, i.source as description, i.amount
    FROM income i
    JOIN categories c ON i.category_id = c.category_id
    WHERE i.user_id = ?
) AS all_transactions WHERE 1=1";

$data_query = "SELECT * FROM (
    SELECT 
        'expense' as type,
        e.expense_id as id,
        DATE_FORMAT(e.expense_date, '%d/%m') as display_date,
        DATE_FORMAT(e.expense_date, '%Y-%m-%d') as full_date,
        c.category_name,
        e.notes as description,
        e.amount,
        c.category_id
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
        c.category_id
    FROM income i
    JOIN categories c ON i.category_id = c.category_id
    WHERE i.user_id = ?
) AS combined WHERE 1=1";

$params = [$user_id, $user_id];
$types = "ii";

// Apply filters
if (!empty($month)) {
    $count_query .= " AND DATE_FORMAT(expense_date, '%Y-%m') = ? AND DATE_FORMAT(income_date, '%Y-%m') = ?";
    $data_query .= " AND DATE_FORMAT(full_date, '%Y-%m') = ?";
    $params[] = $month;
    $types .= "s";
}

if (!empty($type)) {
    $count_query .= " AND transaction_type = ?";
    $data_query .= " AND type = ?";
    $params[] = $type;
    $types .= "s";
}

if (!empty($category)) {
    $count_query .= " AND category_name = ?";
    $data_query .= " AND category_name = ?";
    $params[] = $category;
    $types .= "s";
}

if (!empty($search)) {
    $search_term = "%$search%";
    $count_query .= " AND (description LIKE ? OR category_name LIKE ?)";
    $data_query .= " AND (description LIKE ? OR category_name LIKE ?)";
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= "ss";
}

// Get total count for pagination
$stmt_count = $conn->prepare($count_query);
if (!empty($params)) {
    $stmt_count->bind_param($types, ...$params);
}
$stmt_count->execute();
$count_result = $stmt_count->get_result();
$total_records = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $limit);

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
$stmt_months->bind_param("ii", $user_id, $user_id);
$stmt_months->execute();
$months = $stmt_months->get_result()->fetch_all(MYSQLI_ASSOC);

// Get categories for filter dropdown
$categories_query = "SELECT DISTINCT category_name FROM categories WHERE category_type IN ('income', 'expense') ORDER BY category_name";
$categories_result = $conn->query($categories_query);
$categories = $categories_result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Transactions</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #7209b7;
            --success-color: #06d6a0;
            --warning-color: #ffb703;
            --danger-color: #ef476f;
            --dark-color: #2b2d42;
            --bg-dark: #0a192f;
            --bg-card: #112240;
            --border-color: #233554;
            --accent-color: #64ffda;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: var(--bg-dark);
            color: #fff;
            margin: 0;
            overflow-x: hidden;
        }

        /* Main Layout */
        .main-wrapper {
            display: flex;
            min-height: 100vh;
        }

        .sidebar-col {
            width: 250px;
            flex-shrink: 0;
            background: linear-gradient(180deg, var(--dark-color) 0%, #1a1e2c 100%);
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }

        .content-col {
            flex: 1;
            margin-left: 250px;
            padding: 2rem;
            background: var(--bg-dark);
        }

        /* Container */
        .transactions-container {
            max-width: 1400px;
            margin: 0 auto;
        }

        /* Title */
        .page-title {
            text-align: center;
            margin-bottom: 2rem;
        }

        .page-title h1 {
            color: var(--accent-color);
            font-size: 2.5rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 0.5rem;
        }

        .page-title h1 i {
            margin-right: 10px;
            color: var(--primary-color);
        }

        .page-title p {
            color: #8892b0;
            font-size: 1rem;
        }

        /* Filters Card */
        .filters-card {
            background: var(--bg-card);
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            border: 1px solid var(--border-color);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .filter-label {
            color: var(--accent-color);
            font-weight: 600;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .filter-select, .filter-input {
            background: #1e3a5f;
            border: 2px solid var(--border-color);
            color: #fff;
            border-radius: 8px;
            padding: 0.6rem 1rem;
            width: 100%;
            font-size: 0.95rem;
            transition: all 0.3s;
        }

        .filter-select option {
            background: var(--bg-card);
            color: #fff;
        }

        .filter-select:focus, .filter-input:focus {
            border-color: var(--accent-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(100, 255, 218, 0.1);
        }

        .filter-input::placeholder {
            color: #64748b;
        }

        .btn-filter {
            background: var(--accent-color);
            color: var(--bg-dark);
            border: none;
            padding: 0.6rem 2rem;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
            width: 100%;
            cursor: pointer;
        }

        .btn-filter:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(100, 255, 218, 0.3);
        }

        .btn-reset {
            background: transparent;
            border: 2px solid var(--border-color);
            color: #8892b0;
            padding: 0.6rem 2rem;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn-reset:hover {
            border-color: var(--danger-color);
            color: var(--danger-color);
        }

        /* Table Card */
        .table-card {
            background: var(--bg-card);
            border-radius: 15px;
            padding: 1.5rem;
            border: 1px solid var(--border-color);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            margin-bottom: 2rem;
        }

        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--border-color);
        }

        .table-header h5 {
            color: var(--accent-color);
            font-weight: 600;
            margin: 0;
        }

        .table-header h5 i {
            margin-right: 10px;
            color: var(--primary-color);
        }

        .table-count {
            background: rgba(100, 255, 218, 0.1);
            color: var(--accent-color);
            padding: 0.4rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            color: #fff;
        }

        .table thead th {
            background: #1e3a5f;
            color: var(--accent-color);
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 1rem;
            border-bottom: 2px solid var(--border-color);
            text-align: center;
        }

        .table tbody td {
            padding: 1rem;
            border-bottom: 1px solid var(--border-color);
            text-align: center;
            vertical-align: middle;
        }

        .table tbody tr:hover {
            background: #1e3a5f;
            transition: background 0.3s;
        }

        /* Badges */
        .badge-income {
            background: rgba(6, 214, 160, 0.1);
            color: var(--success-color);
            padding: 0.4rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-block;
        }

        .badge-expense {
            background: rgba(239, 71, 111, 0.1);
            color: var(--danger-color);
            padding: 0.4rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-block;
        }

        .category-badge {
            background: rgba(100, 255, 218, 0.1);
            color: var(--accent-color);
            padding: 0.4rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            display: inline-block;
        }

        /* Amount colors */
        .amount-income {
            color: var(--success-color);
            font-weight: 700;
            font-size: 1.1rem;
        }

        .amount-expense {
            color: var(--danger-color);
            font-weight: 700;
            font-size: 1.1rem;
        }

        /* Action Buttons */
        .btn-edit {
            background: transparent;
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
            padding: 0.4rem 1rem;
            border-radius: 6px;
            font-size: 0.85rem;
            font-weight: 600;
            transition: all 0.3s;
            cursor: pointer;
            margin-right: 0.5rem;
        }

        .btn-edit:hover {
            background: var(--primary-color);
            color: white;
        }

        .btn-delete {
            background: transparent;
            border: 2px solid var(--danger-color);
            color: var(--danger-color);
            padding: 0.4rem 1rem;
            border-radius: 6px;
            font-size: 0.85rem;
            font-weight: 600;
            transition: all 0.3s;
            cursor: pointer;
        }

        .btn-delete:hover {
            background: var(--danger-color);
            color: white;
        }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
            margin-top: 2rem;
        }

        .page-link {
            display: inline-block;
            padding: 0.5rem 1rem;
            background: var(--bg-card);
            border: 2px solid var(--border-color);
            border-radius: 8px;
            color: #fff;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
        }

        .page-link:hover {
            border-color: var(--accent-color);
            color: var(--accent-color);
        }

        .page-link.active {
            background: var(--accent-color);
            color: var(--bg-dark);
            border-color: var(--accent-color);
        }

        .page-link.disabled {
            opacity: 0.5;
            pointer-events: none;
        }

        /* Export Buttons */
        .export-buttons {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-top: 2rem;
        }

        .btn-export {
            background: transparent;
            border: 2px solid var(--border-color);
            color: #fff;
            padding: 0.8rem 2rem;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-export:hover {
            border-color: var(--accent-color);
            color: var(--accent-color);
            transform: translateY(-2px);
        }

        .btn-export.csv:hover {
            border-color: var(--success-color);
            color: var(--success-color);
        }

        .btn-export.pdf:hover {
            border-color: var(--danger-color);
            color: var(--danger-color);
        }

        /* Info text */
        .info-text {
            color: #8892b0;
            text-align: center;
            margin-top: 1rem;
            font-size: 0.9rem;
        }

        .info-text i {
            color: var(--accent-color);
            margin-right: 0.5rem;
        }

        /* Empty state */
        .empty-state {
            padding: 3rem;
            text-align: center;
        }

        .empty-state i {
            font-size: 4rem;
            color: var(--border-color);
            margin-bottom: 1rem;
        }

        .empty-state p {
            color: #8892b0;
            font-size: 1.1rem;
        }

        /* Responsive */
        @media (max-width: 992px) {
            .sidebar-col {
                width: 100%;
                position: relative;
                height: auto;
            }
            
            .content-col {
                margin-left: 0;
            }
            
            .export-buttons {
                flex-direction: column;
            }
            
            .btn-export {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="main-wrapper">
        <!-- Sidebar -->
        <div class="sidebar-col">
            <?php 
            // Set username for sidebar
            $userName = $username;
            include_once '../sidebar.php';
            ?>
        </div>
        
        <!-- Main Content -->
        <div class="content-col">
            <div class="transactions-container">
                
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
                            
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn-filter">
                                    <i class="fas fa-filter me-2"></i>Apply
                                </button>
                            </div>
                            
                            <?php if (!empty($month) || !empty($type) || !empty($category) || !empty($search)): ?>
                            <div class="col-12 text-end">
                                <a href="transactions.php" class="btn-reset">
                                    <i class="fas fa-times me-2"></i>Clear Filters
                                </a>
                            </div>
                            <?php endif; ?>
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
                                    <th>Amount</th>
                                    <th>Actions</th>
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
                                                <span class="<?php echo $trans['type'] == 'income' ? 'amount-income' : 'amount-expense'; ?>">
                                                    <?php echo $trans['type'] == 'income' ? '+' : '-'; ?> 
                                                    ₹<?php echo number_format($trans['amount'], 2); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button class="btn-edit" onclick="location.href='edit-transaction.php?id=<?php echo $trans['id']; ?>&type=<?php echo $trans['type']; ?>'">
                                                    <i class="fas fa-edit me-1"></i>Edit
                                                </button>
                                                <button class="btn-delete" onclick="confirmDelete(<?php echo $trans['id']; ?>, '<?php echo $trans['type']; ?>')">
                                                    <i class="fas fa-trash me-1"></i>Delete
                                                </button>
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
        // Confirm delete function
        function confirmDelete(id, type) {
            if (confirm("Are you sure you want to delete this " + type + " transaction?")) {
                window.location = "delete-transaction.php?id=" + id + "&type=" + type;
            }
        }
        
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
        
        // Auto-submit form when filter changes (optional)
        // Uncomment if you want auto-submit on change
        /*
        document.querySelectorAll('.filter-select').forEach(select => {
            select.addEventListener('change', function() {
                document.getElementById('filterForm').submit();
            });
        });
        */
    </script>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>