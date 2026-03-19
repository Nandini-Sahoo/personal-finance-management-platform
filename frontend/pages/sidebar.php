<?php
// sidebar.php - Keep only the sidebar content, no HTML structure
// Get the current page filename
$current_page = basename($_SERVER['PHP_SELF']);

// Define function to check if link is active
function isActive($page_name) {
    global $current_page;
    return $current_page == $page_name ? 'active' : '';
}

$userName = Session::getUserName();
?>

<style>
    /* Sidebar Styles */
    :root {
        --primary-color: #4361ee;
        --secondary-color: #7209b7;
        --success-color: #06d6a0;
        --warning-color: #ffb703;
        --danger-color: #ef476f;
        --dark-color: #2b2d42;
    }
    
    .sidebar {
        background: linear-gradient(180deg, var(--dark-color) 0%, #1a1e2c 100%);
        min-height: 100vh;
        color: white;
        position: sticky;
        top: 0;
    }

    .sidebar .nav-link {
        color: rgba(255, 255, 255, 0.8);
        padding: 0.8rem 1rem;
        margin: 0.2rem 0;
        border-radius: 8px;
        transition: all 0.3s;
    }

    .sidebar .nav-link:hover {
        background: rgba(255, 255, 255, 0.1);
        color: white;
        transform: translateX(5px);
    }

    .sidebar .nav-link.active {
        background: linear-gradient(90deg, rgba(67, 97, 238, 0.3), transparent);
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
        background: rgba(239, 71, 111, 0.1);
    }

    .sidebar .nav-link.logout.active {
        background: rgba(239, 71, 111, 0.2);
        border-left-color: var(--danger-color);
    }

    .user-info {
        padding: 1.5rem 1rem;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        margin-bottom: 1rem;
    }

    .user-info h5 {
        margin: 0;
        font-size: 1.1rem;
    }

    .user-info small {
        color: rgba(255, 255, 255, 0.6);
    }
    
    @media (max-width: 992px) {
        .sidebar {
            min-height: auto;
            position: relative;
        }

        .main-content {
            padding: 1.5rem;
        }

        .chart-wrapper {
            height: 300px;
        }
    }
</style>

<!-- Sidebar Content -->
<div class="col-lg-2 col-md-3 sidebar">
    <div class="user-info">
        <h5 class="text-white">Welcome,</h5>
        <h5 class="text-white fw-bold"><?php echo htmlspecialchars($userName); ?></h5>
        <small>Personal Finance Platform</small>
    </div>
    
    <nav class="nav flex-column px-3">
        <a class="nav-link <?php echo isActive('dashboard.php'); ?>" href="../dashboard/dashboard.php">
            <i class="fas fa-home"></i> Dashboard
        </a>
        <a class="nav-link <?php echo isActive('add_expense.php'); ?>" href="../dashboard/add_expense.php">
            <i class="fas fa-plus-circle"></i> Add Expense
        </a>
        <a class="nav-link <?php echo isActive('add_income.php'); ?>" href="../dashboard/add_income.php">
            <i class="fas fa-plus-circle"></i> Add Income
        </a>
        <a class="nav-link <?php echo isActive('transactions.php'); ?>" href="../dashboard/transactions.php">
            <i class="fas fa-list"></i> Transactions
        </a>
        <a class="nav-link <?php echo isActive('report.php') || strpos($_SERVER['PHP_SELF'], 'report') !== false ? 'active' : ''; ?>" href="../report/report.php">
            <i class="fas fa-chart-line"></i> Reports
        </a>
        <a class="nav-link <?php echo isActive('set-budget.php') || isActive('view-budget.php') || isActive('budget-process.php') ? 'active' : ''; ?>" href="../budget/set-budget.php">
            <i class="fas fa-tasks"></i> Budget
        </a>
        <a class="nav-link <?php echo isActive('profile.php'); ?>" href="../dashboard/profile.php">
            <i class="fas fa-user"></i> Profile
        </a>
        <hr class="text-white-50 my-3">
        <a class="nav-link logout <?php echo isActive('logout.php'); ?>" href="../user/logout.php">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </nav>
</div>