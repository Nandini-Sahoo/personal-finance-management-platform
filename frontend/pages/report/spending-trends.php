<?php
require_once '../../../backend/session.php';
require_once '../../../backend/report-func.php';
require_once '../../../backend/config/dbcon.php';

// Check if user is logged in
Session::requireLogin();
$userId = Session::getUserId();
$userName = Session::getUserName();
// $userId =1;
// $userName ="";


// Initialize report functions
$reportFunctions = new ReportFunctions();

// Get selected year from URL
$selectedYear = $_GET['year'] ?? date('Y');

// Get available years
$years = getAvailableYears($userId);

// Get monthly trend data
$trendData = getMonthlyTrendData($userId, $selectedYear);

/**
 * Get available years for user
 */
function getAvailableYears($userId) {
    $db=getConnection();
    
    $sql = "SELECT DISTINCT YEAR(transaction_date) AS year
            FROM (
                SELECT expense_date AS transaction_date
                FROM expenses
                WHERE user_id = ?

                UNION

                SELECT income_date AS transaction_date
                FROM income
                WHERE user_id = ?
            ) AS dates
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
    $db->close();
    return $years;
}

/**
 * Get monthly trend data for a specific year
 */
function getMonthlyTrendData($userId, $year) {
    $db=getConnection();
    
    $sql = "SELECT 
                MONTH(transaction_date) AS month,
                SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) AS income,
                SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) AS expense,
                SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) -
                SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) AS savings
            FROM (
                SELECT 
                    amount,
                    'income' AS type,
                    income_date AS transaction_date
                FROM income
                WHERE user_id = ? 
                AND YEAR(income_date) = ?

                UNION ALL

                SELECT 
                    amount,
                    'expense' AS type,
                    expense_date AS transaction_date
                FROM expenses
                WHERE user_id = ? 
                AND YEAR(expense_date) = ?
            ) AS transactions
            GROUP BY MONTH(transaction_date)
            ORDER BY month";
    
    $stmt = $db->prepare($sql);
    $stmt->bind_param("iiii", $userId, $year, $userId, $year);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $data = [];
    for ($i = 1; $i <= 12; $i++) {
        $data[$i] = [
            'month' => $i,
            'month_name' => date('F', mktime(0, 0, 0, $i, 1)),
            'income' => 0,
            'expense' => 0,
            'savings' => 0
        ];
    }
    
    while ($row = $result->fetch_assoc()) {
        $data[$row['month']]['income'] = floatval($row['income']);
        $data[$row['month']]['expense'] = floatval($row['expense']);
        $data[$row['month']]['savings'] = floatval($row['savings']);
    }
    
    $stmt->close();
    $db->close();
    return array_values($data);
}

// Calculate yearly totals
$yearlyIncome = array_sum(array_column($trendData, 'income'));
$yearlyExpense = array_sum(array_column($trendData, 'expense'));
$yearlySavings = $yearlyIncome - $yearlyExpense;
$avgMonthlySavings = $yearlySavings / 12;

include_once '../add-asset.html';
?>
    
    <style>
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
        
        /* Year Selector */
        .year-selector {
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
        
        /* Stats Cards */
        .stat-card {
            border: none;
            border-radius: 15px;
            padding: 1.5rem;
            color: white;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-card.income {
            background: linear-gradient(135deg, #06d6a0, #0ca678);
        }
        
        .stat-card.expense {
            background: linear-gradient(135deg, #ef476f, #d64161);
        }
        
        .stat-card.savings {
            background: linear-gradient(135deg, #4361ee, #3a56d4);
        }
        
        .stat-card .label {
            font-size: 0.9rem;
            opacity: 0.9;
            margin-bottom: 0.5rem;
        }
        
        .stat-card .value {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.2rem;
        }
        
        .stat-card .sub {
            font-size: 0.8rem;
            opacity: 0.8;
        }
        
        /* Chart Container */
        .chart-container {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            margin-bottom: 2rem;
        }
        
        .chart-wrapper {
            height: 400px;
            margin-top: 1rem;
        }
        
        /* Month Cards */
        .month-card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.05);
            transition: all 0.3s;
            height: 100%;
        }
        
        .month-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .month-header {
            padding: 1rem;
            border-bottom: 1px solid #e9ecef;
            background: #f8f9fa;
            border-radius: 12px 12px 0 0;
            font-weight: 600;
        }
        
        .month-body {
            padding: 1rem;
        }
        
        .month-stat {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.3rem 0;
            font-size: 0.9rem;
        }
        
        .month-stat .amount {
            font-weight: 600;
        }
        
        /* @media (max-width: 992px) {
            .sidebar {
                min-height: auto;
                position: relative;
            }
            
            .main-content {
                padding: 1.5rem;
            }
            
            .chart-wrapper {
                height: 300px;
            } */
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
                    <h1><i class="fas fa-chart-line"></i> Spending Trends</h1>
                    <p>Track your financial patterns throughout the year</p>
                </div>
                
                <!-- Year Selector -->
                <div class="year-selector">
                    <form method="GET" action="spending-trends.php">
                        <div class="row align-items-end">
                            <div class="col-md-9">
                                <label class="form-label fw-semibold">Select Year:</label>
                                <select name="year" class="form-select">
                                    <?php foreach ($years as $year): ?>
                                        <option value="<?php echo $year; ?>" 
                                            <?php echo $year == $selectedYear ? 'selected' : ''; ?>>
                                            <?php echo $year; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn-view w-100">
                                    <i class="fas fa-eye me-2"></i> View Trends
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
                
                <!-- Yearly Stats -->
                <div class="row g-4 mb-4">
                    <div class="col-md-4">
                        <div class="stat-card income">
                            <div class="label">Total Income</div>
                            <div class="value"><?php echo $reportFunctions->formatCurrency($yearlyIncome); ?></div>
                            <div class="sub">Year <?php echo $selectedYear; ?></div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card expense">
                            <div class="label">Total Expense</div>
                            <div class="value"><?php echo $reportFunctions->formatCurrency($yearlyExpense); ?></div>
                            <div class="sub">Year <?php echo $selectedYear; ?></div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card savings">
                            <div class="label">Total Savings</div>
                            <div class="value"><?php echo $reportFunctions->formatCurrency($yearlySavings); ?></div>
                            <div class="sub">Avg Monthly: <?php echo $reportFunctions->formatCurrency($avgMonthlySavings); ?></div>
                        </div>
                    </div>
                </div>
                
                <!-- Trend Chart -->
                <div class="chart-container">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0"><i class="fas fa-chart-line me-2 text-primary"></i>Monthly Trends - <?php echo $selectedYear; ?></h5>
                        <div>
                            <span class="me-3"><span class="badge bg-success rounded-pill me-1"></span> Income</span>
                            <span class="me-3"><span class="badge bg-danger rounded-pill me-1"></span> Expense</span>
                            <span><span class="badge bg-primary rounded-pill me-1"></span> Savings</span>
                        </div>
                    </div>
                    <div class="chart-wrapper">
                        <canvas id="trendChart"></canvas>
                    </div>
                </div>
                
                <!-- Monthly Breakdown -->
                <h5 class="mb-3"><i class="fas fa-calendar-alt me-2 text-primary"></i>Monthly Breakdown</h5>
                <div class="row g-3">
                    <?php foreach ($trendData as $data): ?>
                        <div class="col-xl-3 col-lg-4 col-md-6">
                            <div class="month-card">
                                <div class="month-header">
                                    <?php echo $data['month_name']; ?>
                                </div>
                                <div class="month-body">
                                    <div class="month-stat">
                                        <span class="text-success"><i class="fas fa-arrow-down me-1"></i>Income</span>
                                        <span class="amount"><?php echo $reportFunctions->formatCurrency($data['income']); ?></span>
                                    </div>
                                    <div class="month-stat">
                                        <span class="text-danger"><i class="fas fa-arrow-up me-1"></i>Expense</span>
                                        <span class="amount"><?php echo $reportFunctions->formatCurrency($data['expense']); ?></span>
                                    </div>
                                    <div class="month-stat">
                                        <span><i class="fas fa-piggy-bank me-1"></i>Savings</span>
                                        <span class="amount <?php echo $data['savings'] >= 0 ? 'text-success' : 'text-danger'; ?>">
                                            <?php echo $reportFunctions->formatCurrency($data['savings']); ?>
                                        </span>
                                    </div>
                                    <div class="progress mt-2" style="height: 4px;">
                                        <?php 
                                        $total = $data['income'] + $data['expense'];
                                        $expensePercent = $total > 0 ? ($data['expense'] / $total) * 100 : 0;
                                        ?>
                                        <div class="progress-bar bg-success" style="width: <?php echo 100 - $expensePercent; ?>%"></div>
                                        <div class="progress-bar bg-danger" style="width: <?php echo $expensePercent; ?>%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Chart.js Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('trendChart').getContext('2d');
            
            const months = <?php echo json_encode(array_column($trendData, 'month_name')); ?>;
            const incomeData = <?php echo json_encode(array_column($trendData, 'income')); ?>;
            const expenseData = <?php echo json_encode(array_column($trendData, 'expense')); ?>;
            const savingsData = <?php echo json_encode(array_column($trendData, 'savings')); ?>;
            
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: months,
                    datasets: [
                        {
                            label: 'Income',
                            data: incomeData,
                            borderColor: '#06d6a0',
                            backgroundColor: 'rgba(6,214,160,0.1)',
                            borderWidth: 3,
                            tension: 0.3,
                            fill: false
                        },
                        {
                            label: 'Expense',
                            data: expenseData,
                            borderColor: '#ef476f',
                            backgroundColor: 'rgba(239,71,111,0.1)',
                            borderWidth: 3,
                            tension: 0.3,
                            fill: false
                        },
                        {
                            label: 'Savings',
                            data: savingsData,
                            borderColor: '#4361ee',
                            backgroundColor: 'rgba(67,97,238,0.1)',
                            borderWidth: 3,
                            tension: 0.3,
                            borderDash: [5, 5],
                            fill: false
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    let value = context.raw || 0;
                                    return `${label}: ₹${value.toFixed(2)}`;
                                }
                            }
                        },
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: '#f1f3f5'
                            },
                            ticks: {
                                callback: function(value) {
                                    return '₹' + value;
                                }
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
        });
    </script>
