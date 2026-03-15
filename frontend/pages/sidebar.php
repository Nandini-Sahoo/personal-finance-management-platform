<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Document</title>
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
        
        body {
            background-color: #f4f7fc;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
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
    </head>
    <body>
    <!-- Sidebar -->
     <?php $userName="" ?>
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
                       <a class="nav-link active" href="../report/report.php">
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
</body>
</html>