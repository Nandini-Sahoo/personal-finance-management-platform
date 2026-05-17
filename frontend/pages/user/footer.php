<!-- Footer -->
<?php
require_once '../../../backend/session.php';
Session::startSession();
$isLoggedIn = Session::isLoggedIn();

function secureLink($url, $text, $isLoggedIn) {
    if ($isLoggedIn) {
        return '<a href="' . $url . '" class="footer-link">' . $text . '</a>';
    } else {
        return '<a href="../user/login.php?redirect=' . urlencode($url) . '" class="footer-link">' . $text . '</a>';
    }
}
?>

<footer class="footer-section text-light pt-5 pb-4">
    <div class="container">
        <div class="row">

            <!-- Features -->
            <div class="col-md-3 col-sm-6 mb-4">
                <h5 class="fw-bold mb-4">Features</h5>
                <ul class="list-unstyled">
                    <li class="footer-link">Expense Management</li>
                    <li class="footer-link">Budget Tracking</li>
                    <li class="footer-link">Expense Reports</li>
                    <li class="footer-link">Income Tracking</li>
                    <li class="footer-link">Analytics Dashboard</li>
                    <li class="footer-link">Data Export</li>
                </ul>
            </div>

            <!-- Resources -->
            <div class="col-md-3 col-sm-6 mb-4">
                <h5 class="fw-bold mb-4">Resources</h5>
                <ul class="list-unstyled">
                    <li><?php echo secureLink("../dashboard/profile.php", "Support", $isLoggedIn); ?></li>
                    <li><?php echo secureLink("../report/comparison.php", "Documentation", $isLoggedIn); ?></li>
                    <li><a href="#" class="footer-link">Privacy Policy</a></li>
                    <li><a href="#" class="footer-link">Terms & Conditions</a></li>
                </ul>
            </div>

            <!-- Learn More -->
            <div class="col-md-3 col-sm-6 mb-4">
                <h5 class="fw-bold mb-4">Learn More</h5>
                <ul class="list-unstyled">
                    <li><a href="#" class="footer-link">About Project</a></li>
                    <li><?php echo secureLink("../report/report.php", "Reports", $isLoggedIn); ?></li>
                    <li><?php echo secureLink("../budget/view-budget.php", "Budget Planning", $isLoggedIn); ?></li>
                    <li><?php echo secureLink("../report/spending-trends.php", "Spending Trends", $isLoggedIn); ?></li>
                </ul>
            </div>

            <!-- Get Started -->
            <div class="col-md-3 col-sm-6 mb-4">
                <h5 class="fw-bold mb-4">Get Started</h5>
                <ul class="list-unstyled">
                    <?php if ($isLoggedIn): ?>
                        <li><a href="../dashboard/dashboard.php" class="footer-link">Go to Dashboard</a></li>
                        <li><a href="../user/logout.php" class="footer-link">Log Out</a></li>
                    <?php else: ?>
                        <li><a href="../user/register.php" class="footer-link">Create Account</a></li>
                        <li><a href="../user/login.php" class="footer-link">Log In</a></li>
                    <?php endif; ?>
                </ul>
            </div>

        </div>

        <hr class="border-secondary">

        <div class="text-center pt-2">
            <p class="mb-0 text-secondary">
                &copy; 2026 Personal Finance Management Platform. All rights reserved.
            </p>
        </div>
    </div>
</footer>

<style>
.footer-section {
    background: #020617;
    border-top: 3px solid rgba(255,255,255,0.05);
}

.footer-section h5 {
    font-size: 24px;
    font-weight: 700;
    color: #2ea8ff;
    margin-bottom: 20px;
    text-align: left;
}

.footer-section ul {
    padding-left: 0;
}

.footer-link {
    color: #ffffff;
    text-decoration: none;
    display: block;
    margin-bottom: 14px;
    transition: all 0.3s ease;
    font-size: 18px;
    text-align: left;
    padding-left: 0;
    cursor: pointer;
}

.footer-section ul li a.footer-link,
.footer-section ul li.footer-link {
    display: block;
    text-align: left;
}

.footer-link:hover {
    color: #00d26a;
    transform: translateX(5px);
    text-decoration: none;
}

.footer-section hr {
    border-color: rgba(255,255,255,0.12);
}

.footer-section p {
    color: rgba(255,255,255,0.55);
    font-size: 18px;
}
</style>