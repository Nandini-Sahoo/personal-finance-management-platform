<?php 
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include required files with correct paths
require_once "../../../backend/config/dbcon.php";
include_once "check.php";

// Get user ID from session or set default for testing
// $id = $_SESSION['user_id'] ?? 1; // Uncomment when session is working
$id = 1; // For testing

// Get database connection
$conn = getConnection();

// Get username from database
$username_qry = "SELECT name FROM users WHERE user_id = ?";
$stmt_user = $conn->prepare($username_qry);
$stmt_user->bind_param("i", $id);
$stmt_user->execute();
$user_result = $stmt_user->get_result();
$userData = $user_result->fetch_assoc();
$username = $userData['name'] ?? 'User';

// Get current month and year
$currentMonth = date('Y-m');
$currentMonthDisplay = date('F Y');
$currentMonthStart = date('Y-m-01');
$currentMonthEnd = date('Y-m-t');

/* Get Monthly Summary */
// Total Income for current month
$qry_monthly_income = "SELECT COALESCE(SUM(amount), 0) as total_income 
                       FROM income 
                       WHERE user_id = ? 
                       AND income_date BETWEEN ? AND ?";
$stmt1 = $conn->prepare($qry_monthly_income);
$stmt1->bind_param("iss", $id, $currentMonthStart, $currentMonthEnd);
$stmt1->execute();
$result1 = $stmt1->get_result();
$monthly_income = $result1->fetch_assoc()['total_income'];

// Total Expense for current month
$qry_monthly_expense = "SELECT COALESCE(SUM(amount), 0) as total_expense 
                        FROM expenses 
                        WHERE user_id = ? 
                        AND expense_date BETWEEN ? AND ?";
$stmt2 = $conn->prepare($qry_monthly_expense);
$stmt2->bind_param("iss", $id, $currentMonthStart, $currentMonthEnd);
$stmt2->execute();
$result2 = $stmt2->get_result();
$monthly_expense = $result2->fetch_assoc()['total_expense'];

// Calculate monthly savings and savings rate
$monthly_savings = $monthly_income - $monthly_expense;
$savings_rate = ($monthly_income > 0) ? round(($monthly_savings / $monthly_income) * 100, 1) : 0;

/* Get Expense Distribution for Pie Chart */
$qry_expense_distribution = "SELECT c.category_name, c.category_id, 
                                    COALESCE(SUM(e.amount), 0) as total,
                                    CASE c.category_id 
                                        WHEN 1 THEN '#4361ee'
                                        WHEN 2 THEN '#7209b7'
                                        WHEN 3 THEN '#ef476f'
                                        WHEN 4 THEN '#ffb703'
                                        WHEN 5 THEN '#06d6a0'
                                        ELSE '#6c757d'
                                    END as color
                             FROM categories c
                             LEFT JOIN expenses e ON c.category_id = e.category_id 
                                 AND e.user_id = ? 
                                 AND e.expense_date BETWEEN ? AND ?
                             WHERE c.category_type = 'expense'
                             GROUP BY c.category_id, c.category_name
                             HAVING total > 0";
$stmt3 = $conn->prepare($qry_expense_distribution);
$stmt3->bind_param("iss", $id, $currentMonthStart, $currentMonthEnd);
$stmt3->execute();
$expense_distribution = $stmt3->get_result()->fetch_all(MYSQLI_ASSOC);

/* Get Income vs Expense for Bar Chart (Last 6 months) */
$qry_monthly_trend = "SELECT 
                        DATE_FORMAT(month_date, '%b') as month_name,
                        COALESCE(income_total, 0) as income,
                        COALESCE(expense_total, 0) as expense
                      FROM (
                          SELECT LAST_DAY(DATE_SUB(CURDATE(), INTERVAL n MONTH)) as month_date
                          FROM (SELECT 0 n UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5) numbers
                      ) months
                      LEFT JOIN (
                          SELECT LAST_DAY(income_date) as month_end, SUM(amount) as income_total
                          FROM income
                          WHERE user_id = ? AND income_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                          GROUP BY LAST_DAY(income_date)
                      ) i ON months.month_date = i.month_end
                      LEFT JOIN (
                          SELECT LAST_DAY(expense_date) as month_end, SUM(amount) as expense_total
                          FROM expenses
                          WHERE user_id = ? AND expense_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                          GROUP BY LAST_DAY(expense_date)
                      ) e ON months.month_date = e.month_end
                      ORDER BY months.month_date";
$stmt4 = $conn->prepare($qry_monthly_trend);
$stmt4->bind_param("ii", $id, $id);
$stmt4->execute();
$monthly_trend = $stmt4->get_result()->fetch_all(MYSQLI_ASSOC);

/* Get Budget Alerts */
$qry_budget_alerts = "SELECT 
                        c.category_name,
                        COALESCE(b.target_amount, 0) as budget,
                        COALESCE(SUM(e.amount), 0) as spent,
                        ROUND((COALESCE(SUM(e.amount), 0) / b.target_amount) * 100, 1) as percentage
                      FROM categories c
                      LEFT JOIN budget b ON c.category_id = b.category_id AND b.user_id = ? AND b.budget_month = ?
                      LEFT JOIN expenses e ON c.category_id = e.category_id AND e.user_id = ? AND e.expense_date BETWEEN ? AND ?
                      WHERE c.category_type = 'expense'
                      GROUP BY c.category_id, c.category_name, b.target_amount
                      HAVING budget > 0 AND percentage >= 80";
$stmt5 = $conn->prepare($qry_budget_alerts);
$stmt5->bind_param("isiss", $id, $currentMonthStart, $id, $currentMonthStart, $currentMonthEnd);
$stmt5->execute();
$budget_alerts = $stmt5->get_result()->fetch_all(MYSQLI_ASSOC);

/* Get Recent Transactions */
$qry_recent = "(SELECT 'expense' as type, expense_date as trans_date, c.category_name, amount, e.notes as description
                FROM expenses e
                JOIN categories c ON e.category_id = c.category_id
                WHERE e.user_id = ?
                ORDER BY expense_date DESC
                LIMIT 5)
                UNION ALL
                (SELECT 'income' as type, income_date as trans_date, c.category_name, amount, i.source as description
                FROM income i
                JOIN categories c ON i.category_id = c.category_id
                WHERE i.user_id = ?
                ORDER BY income_date DESC
                LIMIT 5)
                ORDER BY trans_date DESC
                LIMIT 5";
$stmt6 = $conn->prepare($qry_recent);
$stmt6->bind_param("ii", $id, $id);
$stmt6->execute();
$recent_transactions = $stmt6->get_result()->fetch_all(MYSQLI_ASSOC);

// Include assets
include_once '../add-asset.html';
?>

<!-- Custom CSS for Dashboard -->
<link rel="stylesheet" href="../../assets/css/dashboard.css">

<body>
    <div class="container-fluid p-0">
        <div class="row g-0">
            <?php 
            // Set the username for sidebar
            $userName = " ";
            include_once '../sidebar.php';
            ?>
            
            <!-- Main Content -->
            <div class="col-lg-10 col-md-9 main-content dashboard-content">
                
                <!-- Welcome Card -->
                <div class="welcome-card">
                    <h2><i class="fas fa-hand-wave me-2"></i> Welcome back, <?php echo htmlspecialchars($username); ?>!</h2>
                    <p class="mb-0">Here's your financial summary for <?php echo $currentMonthDisplay; ?></p>
                </div>
                
                <!-- Monthly Summary Cards -->
                <div class="row g-4 mb-4">
                    <div class="col-md-4">
                        <div class="summary-card income">
                            <div class="label">
                                <i class="fas fa-arrow-down text-success me-2"></i>Total Income
                            </div>
                            <div class="value">₹ <?php echo number_format($monthly_income, 2); ?></div>
                            <div class="sub">For <?php echo $currentMonthDisplay; ?></div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="summary-card expense">
                            <div class="label">
                                <i class="fas fa-arrow-up text-danger me-2"></i>Total Expense
                            </div>
                            <div class="value">₹ <?php echo number_format($monthly_expense, 2); ?></div>
                            <div class="sub">For <?php echo $currentMonthDisplay; ?></div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="summary-card savings">
                            <div class="label">
                                <i class="fas fa-piggy-bank me-2"></i>Monthly Savings
                            </div>
                            <div class="value <?php echo $monthly_savings >= 0 ? 'text-success' : 'text-danger'; ?>">
                                ₹ <?php echo number_format($monthly_savings, 2); ?>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="sub">Savings Rate:</span>
                                <span class="savings-rate"><?php echo $savings_rate; ?>%</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Charts Section -->
                <div class="row g-4 mb-4">
                    <!-- Expense Distribution Pie Chart -->
                    <div class="col-lg-6">
                        <div class="chart-container">
                            <h5 class="section-title">
                                <i class="fas fa-chart-pie"></i>Expense Distribution
                            </h5>
                            <div class="chart-wrapper">
                                <canvas id="expensePieChart"></canvas>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Income vs Expense Bar Chart -->
                    <div class="col-lg-6">
                        <div class="chart-container">
                            <h5 class="section-title">
                                <i class="fas fa-chart-bar"></i>Income vs Expense (Last 6 Months)
                            </h5>
                            <div class="chart-wrapper">
                                <canvas id="incomeExpenseChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Alerts and Transactions Row -->
                <div class="row g-4">
                    <!-- Alerts Section -->
                    <div class="col-lg-5">
                        <div class="chart-container">
                            <h5 class="section-title">
                                <i class="fas fa-exclamation-triangle text-warning"></i>Alerts & Notifications
                            </h5>
                            
                            <?php if (empty($budget_alerts)): ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                                    <p class="text-muted">No alerts! All budgets are on track.</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($budget_alerts as $alert): ?>
                                    <div class="alert-card alert-warning mb-3">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <strong><?php echo htmlspecialchars($alert['category_name']); ?></strong>
                                            <span class="badge bg-warning text-dark"><?php echo $alert['percentage']; ?>% used</span>
                                        </div>
                                        <div class="progress mb-2" style="height: 8px;">
                                            <div class="progress-bar bg-warning" style="width: <?php echo min($alert['percentage'], 100); ?>%"></div>
                                        </div>
                                        <small class="text-muted">
                                            Spent: ₹<?php echo number_format($alert['spent'], 2); ?> / ₹<?php echo number_format($alert['budget'], 2); ?>
                                        </small>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            
                            <!-- Quick Actions -->
                            <h5 class="section-title mt-4">
                                <i class="fas fa-bolt text-primary"></i>Quick Actions
                            </h5>
                            <div class="row g-3">
                                <div class="col-6">
                                    <a href="add_income.php" class="quick-action-btn d-block">
                                        <i class="fas fa-plus-circle text-success"></i>
                                        <span>Add Income</span>
                                    </a>
                                </div>
                                <div class="col-6">
                                    <a href="add_expense.php" class="quick-action-btn d-block">
                                        <i class="fas fa-minus-circle text-danger"></i>
                                        <span>Add Expense</span>
                                    </a>
                                </div>
                                <div class="col-6">
                                    <a href="../budget/set-budget.php" class="quick-action-btn d-block">
                                        <i class="fas fa-tasks text-primary"></i>
                                        <span>Set Budget</span>
                                    </a>
                                </div>
                                <div class="col-6">
                                    <a href="../report/report.php" class="quick-action-btn d-block">
                                        <i class="fas fa-chart-line text-info"></i>
                                        <span>View Reports</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Recent Transactions -->
                    <div class="col-lg-7">
                        <div class="transactions-container">
                            <h5 class="section-title">
                                <i class="fas fa-history"></i>Recent Transactions
                            </h5>
                            
                            <?php if (empty($recent_transactions)): ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-receipt fa-4x text-muted mb-3"></i>
                                    <p class="text-muted">No transactions yet. Start by adding an income or expense!</p>
                                    <div class="mt-3">
                                        <a href="add_income.php" class="btn btn-success btn-sm me-2">Add Income</a>
                                        <a href="add_expense.php" class="btn btn-danger btn-sm">Add Expense</a>
                                    </div>
                                </div>
                            <?php else: ?>
                                <?php foreach ($recent_transactions as $trans): ?>
                                    <div class="transaction-item">
                                        <div class="d-flex align-items-center">
                                            <div class="transaction-icon <?php echo $trans['type'] == 'income' ? 'transaction-income' : 'transaction-expense'; ?>">
                                                <i class="fas <?php echo $trans['type'] == 'income' ? 'fa-arrow-down' : 'fa-arrow-up'; ?>"></i>
                                            </div>
                                            <div class="transaction-details">
                                                <div class="transaction-category">
                                                    <?php echo htmlspecialchars($trans['category_name']); ?>
                                                </div>
                                                <div class="transaction-date">
                                                    <i class="far fa-calendar-alt me-1"></i>
                                                    <?php echo date('M d, Y', strtotime($trans['trans_date'])); ?>
                                                    <?php if (!empty($trans['description'])): ?>
                                                        • <?php echo htmlspecialchars(substr($trans['description'], 0, 30)); ?>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="transaction-amount <?php echo $trans['type'] == 'income' ? 'amount-income' : 'amount-expense'; ?>">
                                            <?php echo $trans['type'] == 'income' ? '+' : '-'; ?> ₹<?php echo number_format($trans['amount'], 2); ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                                
                                <div class="text-center mt-3">
                                    <a href="transactions.php" class="btn btn-outline-primary btn-sm">
                                        View All Transactions <i class="fas fa-arrow-right ms-2"></i>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Chart.js Scripts -->
    <script>
        $(document).ready(function() {
            // Expense Distribution Pie Chart
            <?php if (!empty($expense_distribution)): ?>
            const pieCtx = document.getElementById('expensePieChart').getContext('2d');
            new Chart(pieCtx, {
                type: 'pie',
                data: {
                    labels: <?php echo json_encode(array_column($expense_distribution, 'category_name')); ?>,
                    datasets: [{
                        data: <?php echo json_encode(array_column($expense_distribution, 'total')); ?>,
                        backgroundColor: <?php echo json_encode(array_column($expense_distribution, 'color')); ?>,
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                boxWidth: 12,
                                padding: 15
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.label || '';
                                    let value = context.raw || 0;
                                    let total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    let percentage = ((value / total) * 100).toFixed(1);
                                    return `${label}: ₹${value.toFixed(2)} (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });
            <?php endif; ?>
            
            // Income vs Expense Bar Chart
            <?php if (!empty($monthly_trend)): ?>
            const barCtx = document.getElementById('incomeExpenseChart').getContext('2d');
            new Chart(barCtx, {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode(array_column($monthly_trend, 'month_name')); ?>,
                    datasets: [
                        {
                            label: 'Income',
                            data: <?php echo json_encode(array_column($monthly_trend, 'income')); ?>,
                            backgroundColor: '#06d6a0',
                            borderRadius: 6,
                            barPercentage: 0.7
                        },
                        {
                            label: 'Expense',
                            data: <?php echo json_encode(array_column($monthly_trend, 'expense')); ?>,
                            backgroundColor: '#ef476f',
                            borderRadius: 6,
                            barPercentage: 0.7
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return `${context.dataset.label}: ₹${context.raw.toFixed(2)}`;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '₹' + value;
                                }
                            }
                        }
                    }
                }
            });
            <?php endif; ?>
        });
    </script>
    
    <?php include_once "footer.php"; ?>
</body>
</html>