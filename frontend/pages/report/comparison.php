<?php
require_once '../../../backend/session.php';
require_once '../../../backend/report-func.php';

// Check if user is logged in
Session::requireLogin();
$userId = Session::getUserId();
$userName = Session::getUserName();
// $userId = 1;
// $userName = "";

// Initialize report functions
$reportFunctions = new ReportFunctions();

// Get selected months from URL
$month1 = $_GET['month1'] ?? date('Y-m');
$month2 = $_GET['month2'] ?? date('Y-m', strtotime('-1 month'));

// Get available months
$availableMonths = $reportFunctions->getAvailableMonths($userId);

// Get comparison data
$comparison = $reportFunctions->getMonthlyComparison($userId, $month1, $month2);

// Get monthly summaries
$summary1 = $reportFunctions->getMonthlySummary($userId, $month1);
$summary2 = $reportFunctions->getMonthlySummary($userId, $month2);

// Get month names
$month1Name = $reportFunctions->getMonthName($month1);
$month2Name = $reportFunctions->getMonthName($month2);

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
        
        /* Month Selector */
        .month-selector {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            margin-bottom: 2rem;
        }
        
        .btn-compare {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border: none;
            padding: 0.6rem 2rem;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-compare:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(67,97,238,0.3);
            color: white;
        }
        
        /* Comparison Cards */
        .comparison-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            transition: transform 0.3s;
            height: 100%;
        }
        
        .comparison-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .card-header-custom {
            padding: 1.2rem;
            border-bottom: 1px solid #e9ecef;
            background: white;
            border-radius: 15px 15px 0 0 !important;
            font-weight: 600;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .month-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .month-badge.month1 {
            background: rgba(67,97,238,0.1);
            color: var(--primary-color);
        }
        
        .month-badge.month2 {
            background: rgba(239,71,111,0.1);
            color: var(--danger-color);
        }
        
        .stat-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 1.2rem;
            border-bottom: 1px solid #f1f3f5;
        }
        
        .stat-row:last-child {
            border-bottom: none;
        }
        
        .stat-label {
            color: #6c757d;
        }
        
        .stat-value {
            font-weight: 700;
        }
        
        .difference-badge {
            display: inline-block;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .positive {
            background: rgba(239,71,111,0.1);
            color: var(--danger-color);
        }
        
        .negative {
            background: rgba(6,214,160,0.1);
            color: var(--success-color);
        }
        
        .neutral {
            background: rgba(108,117,125,0.1);
            color: #6c757d;
        }
        
        /* Table Styles */
        .table-container {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            margin-top: 2rem;
        }
        
        .table thead th {
            background: #f8f9fa;
            border-bottom: 2px solid #e9ecef;
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .table tbody td {
            vertical-align: middle;
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
                    <h1><i class="fas fa-balance-scale"></i> Detailed Comparison</h1>
                    <p>Compare financial performance between two months</p>
                </div>
                
                <!-- Month Selector -->
                <div class="month-selector">
                    <form method="GET" action="comparison-report.php">
                        <div class="row align-items-end">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Month 1:</label>
                                <select name="month1" class="form-select">
                                    <?php foreach ($availableMonths as $month): ?>
                                        <option value="<?php echo $month['month_year']; ?>" 
                                            <?php echo $month['month_year'] == $month1 ? 'selected' : ''; ?>>
                                            <?php echo $month['month_name']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Month 2:</label>
                                <select name="month2" class="form-select">
                                    <?php foreach ($availableMonths as $month): ?>
                                        <option value="<?php echo $month['month_year']; ?>" 
                                            <?php echo $month['month_year'] == $month2 ? 'selected' : ''; ?>>
                                            <?php echo $month['month_name']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <button type="submit" class="btn-compare w-100">
                                    <i class="fas fa-sync-alt me-2"></i> Compare
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
                
                <!-- Comparison Cards -->
                <div class="row g-4">
                    <!-- Month 1 Card -->
                    <div class="col-md-4">
                        <div class="comparison-card">
                            <div class="card-header-custom">
                                <span><?php echo $month1Name; ?></span>
                                <span class="month-badge month1">Month 1</span>
                            </div>
                            <div class="card-body">
                                <div class="stat-row">
                                    <span class="stat-label"><i class="fas fa-arrow-down text-success me-2"></i>Income</span>
                                    <span class="stat-value"><?php echo $reportFunctions->formatCurrency($summary1['income']); ?></span>
                                </div>
                                <div class="stat-row">
                                    <span class="stat-label"><i class="fas fa-arrow-up text-danger me-2"></i>Expense</span>
                                    <span class="stat-value"><?php echo $reportFunctions->formatCurrency($summary1['expense']); ?></span>
                                </div>
                                <div class="stat-row">
                                    <span class="stat-label"><i class="fas fa-piggy-bank me-2"></i>Savings</span>
                                    <span class="stat-value <?php echo $summary1['savings'] >= 0 ? 'text-success' : 'text-danger'; ?>">
                                        <?php echo $reportFunctions->formatCurrency($summary1['savings']); ?>
                                    </span>
                                </div>
                                <div class="stat-row">
                                    <span class="stat-label"><i class="fas fa-chart-line me-2"></i>Savings %</span>
                                    <span class="stat-value"><?php echo $summary1['savings_percentage']; ?>%</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Month 2 Card -->
                    <div class="col-md-4">
                        <div class="comparison-card">
                            <div class="card-header-custom">
                                <span><?php echo $month2Name; ?></span>
                                <span class="month-badge month2">Month 2</span>
                            </div>
                            <div class="card-body">
                                <div class="stat-row">
                                    <span class="stat-label"><i class="fas fa-arrow-down text-success me-2"></i>Income</span>
                                    <span class="stat-value"><?php echo $reportFunctions->formatCurrency($summary2['income']); ?></span>
                                </div>
                                <div class="stat-row">
                                    <span class="stat-label"><i class="fas fa-arrow-up text-danger me-2"></i>Expense</span>
                                    <span class="stat-value"><?php echo $reportFunctions->formatCurrency($summary2['expense']); ?></span>
                                </div>
                                <div class="stat-row">
                                    <span class="stat-label"><i class="fas fa-piggy-bank me-2"></i>Savings</span>
                                    <span class="stat-value <?php echo $summary2['savings'] >= 0 ? 'text-success' : 'text-danger'; ?>">
                                        <?php echo $reportFunctions->formatCurrency($summary2['savings']); ?>
                                    </span>
                                </div>
                                <div class="stat-row">
                                    <span class="stat-label"><i class="fas fa-chart-line me-2"></i>Savings %</span>
                                    <span class="stat-value"><?php echo $summary2['savings_percentage']; ?>%</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Difference Card -->
                    <div class="col-md-4">
                        <div class="comparison-card">
                            <div class="card-header-custom">
                                <span>Difference</span>
                                <span class="month-badge neutral">Change</span>
                            </div>
                            <div class="card-body">
                                <?php 
                                $incomeDiff = $summary1['income'] - $summary2['income'];
                                $expenseDiff = $summary1['expense'] - $summary2['expense'];
                                $savingsDiff = $summary1['savings'] - $summary2['savings'];
                                ?>
                                <div class="stat-row">
                                    <span class="stat-label">Income Change</span>
                                    <span>
                                        <span class="stat-value <?php echo $incomeDiff >= 0 ? 'text-success' : 'text-danger'; ?>">
                                            <?php echo $incomeDiff >= 0 ? '+' : ''; ?><?php echo $reportFunctions->formatCurrency($incomeDiff); ?>
                                        </span>
                                        <span class="difference-badge <?php echo $incomeDiff >= 0 ? 'positive' : 'negative'; ?> ms-2">
                                            <?php echo $summary2['income'] > 0 ? round(($incomeDiff / $summary2['income']) * 100, 1) : 0; ?>%
                                        </span>
                                    </span>
                                </div>
                                <div class="stat-row">
                                    <span class="stat-label">Expense Change</span>
                                    <span>
                                        <span class="stat-value <?php echo $expenseDiff <= 0 ? 'text-success' : 'text-danger'; ?>">
                                            <?php echo $expenseDiff >= 0 ? '+' : ''; ?><?php echo $reportFunctions->formatCurrency($expenseDiff); ?>
                                        </span>
                                        <span class="difference-badge <?php echo $expenseDiff <= 0 ? 'negative' : 'positive'; ?> ms-2">
                                            <?php echo $summary2['expense'] > 0 ? round(($expenseDiff / $summary2['expense']) * 100, 1) : 0; ?>%
                                        </span>
                                    </span>
                                </div>
                                <div class="stat-row">
                                    <span class="stat-label">Savings Change</span>
                                    <span>
                                        <span class="stat-value <?php echo $savingsDiff >= 0 ? 'text-success' : 'text-danger'; ?>">
                                            <?php echo $savingsDiff >= 0 ? '+' : ''; ?><?php echo $reportFunctions->formatCurrency($savingsDiff); ?>
                                        </span>
                                        <span class="difference-badge <?php echo $savingsDiff >= 0 ? 'positive' : 'negative'; ?> ms-2">
                                            <?php echo abs($summary2['savings']) > 0 ? round(($savingsDiff / abs($summary2['savings'])) * 100, 1) : 0; ?>%
                                        </span>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Detailed Comparison Table -->
                <div class="table-container">
                    <h5 class="mb-3"><i class="fas fa-table me-2 text-primary"></i>Category-wise Comparison</h5>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Category</th>
                                    <th class="text-end"><?php echo $month1Name; ?></th>
                                    <th class="text-end"><?php echo $month2Name; ?></th>
                                    <th class="text-end">Difference</th>
                                    <th class="text-center">Change %</th>
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
                                    <td><?php echo htmlspecialchars($category); ?></td>
                                    <td class="text-end"><?php echo $reportFunctions->formatCurrency($data['month1_amount']); ?></td>
                                    <td class="text-end"><?php echo $reportFunctions->formatCurrency($data['month2_amount']); ?></td>
                                    <td class="text-end <?php echo $data['difference'] >= 0 ? 'text-danger' : 'text-success'; ?>">
                                        <?php echo $data['difference'] >= 0 ? '+' : ''; ?><?php echo $reportFunctions->formatCurrency($data['difference']); ?>
                                    </td>
                                    <td class="text-center">
                                        <span class="difference-badge <?php 
                                            echo $data['percentage_change'] > 0 ? 'positive' : 
                                                ($data['percentage_change'] < 0 ? 'negative' : 'neutral'); 
                                        ?>">
                                            <?php echo $data['percentage_change'] > 0 ? '+' : ''; ?><?php echo $data['percentage_change']; ?>%
                                        </span>
                                    </td>
                                </tr>
                                <?php endif; endforeach; ?>
                                
                                <?php if (!$hasData): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4">
                                        <i class="fas fa-chart-line fa-2x text-muted mb-2"></i>
                                        <p class="text-muted">No expense data available for the selected months</p>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="text-end mt-3">
                        <a href="export-data.php?type=comparison&month1=<?php echo $month1; ?>&month2=<?php echo $month2; ?>" class="btn btn-outline-primary">
                            <i class="fas fa-download me-2"></i>Export Comparison
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
