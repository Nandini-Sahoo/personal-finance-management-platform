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
            