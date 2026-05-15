<?php
require_once '../../../backend/session.php';
require_once '../../../backend/report-func.php';

Session::requireLogin();
$userId = Session::getUserId();
$reportFunctions = new ReportFunctions();

// Get data for PDF
$selectedMonth = $_GET['month'] ?? date('Y-m');
$categoryData = getCategoryWiseData($userId, $selectedMonth);
$monthlySummary = $reportFunctions->getMonthlySummary($userId, $selectedMonth);

// Function to get category data
function getCategoryWiseData($userId, $monthYear) {
    $db = getConnection();
    $sql = "SELECT c.category_name, COALESCE(SUM(e.amount), 0) AS total_amount,
                   COUNT(e.expense_id) AS transaction_count
            FROM categories c
            LEFT JOIN expenses e ON c.category_id = e.category_id
                AND e.user_id = ? AND DATE_FORMAT(e.expense_date, '%Y-%m') = ?
            WHERE c.category_type = 'expense'
            GROUP BY c.category_id, c.category_name
            HAVING total_amount > 0
            ORDER BY total_amount DESC";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("is", $userId, $monthYear);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    $stmt->close();
    $db->close();
    return $data;
}
include_once '../add-asset.html';
?>
    <style>
        @media print {
            body { margin: 0; padding: 0; }
            .no-print { display: none; }
        }
        body { font-family: 'Segoe UI', Arial, sans-serif; padding: 20px; }
        .report-header { text-align: center; margin-bottom: 30px; }
        .report-title { color: #4361ee; margin-bottom: 10px; }
        .summary-card { background: #f8f9fa; border-radius: 10px; padding: 15px; margin-bottom: 20px; }
        .category-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .category-table th, .category-table td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        .category-table th { background: #4361ee; color: white; }
        .chart-container { width: 100%; height: 300px; margin: 20px 0; }
        .footer { text-align: center; margin-top: 30px; font-size: 12px; color: #666; }
    </style>
    <div id="report-content">
        <div class="report-header">
            <h1 class="report-title">Financial Report</h1>
            <p>Month: <?php echo date('F Y', strtotime($selectedMonth . '-01')); ?></p>
            <p>Generated on: <?php echo date('Y-m-d H:i:s'); ?></p>
        </div>

        <!-- Summary Section -->
        <div class="row">
            <div class="col-md-4">
                <div class="summary-card">
                    <h5>Total Expense</h5>
                    <h3>₹<?php echo number_format($monthlySummary['expense'], 2); ?></h3>
                </div>
            </div>
            <div class="col-md-4">
                <div class="summary-card">
                    <h5>Categories Used</h5>
                    <h3><?php echo count($categoryData); ?></h3>
                </div>
            </div>
            <div class="col-md-4">
                <div class="summary-card">
                    <h5>Total Income</h5>
                    <h3>₹<?php echo number_format($monthlySummary['income'], 2); ?></h3>
                </div>
            </div>
        </div>

        <!-- Category Breakdown Table -->
        <table class="category-table">
            <thead>
                <tr><th>Category</th><th>Total Amount</th><th>Transactions</th><th>Percentage</th></tr>
            </thead>
            <tbody>
                <?php 
                $total = $monthlySummary['expense'];
                foreach ($categoryData as $cat): 
                    $percent = $total > 0 ? round(($cat['total_amount'] / $total) * 100, 1) : 0;
                ?>
                <tr>
                    <td><?php echo $cat['category_name']; ?></td>
                    <td>₹<?php echo number_format($cat['total_amount'], 2); ?></td>
                    <td><?php echo $cat['transaction_count']; ?></td>
                    <td><?php echo $percent; ?>%</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Chart Canvas for PDF -->
        <div class="chart-container">
            <canvas id="expenseChart" width="400" height="200"></canvas>
        </div>
        
        <div class="footer">
            <p>This is an auto-generated report from Personal Finance Management Platform</p>
        </div>
    </div>

    <div class="no-print" style="text-align: center; margin: 20px;">
        <button onclick="downloadPDF()" class="btn btn-primary">Download as PDF</button>
        <button onclick="window.print()" class="btn btn-secondary">Print</button>
    </div>

    <script>
        // Initialize Chart
        const ctx = document.getElementById('expenseChart').getContext('2d');
        const categories = <?php echo json_encode(array_column($categoryData, 'category_name')); ?>;
        const amounts = <?php echo json_encode(array_column($categoryData, 'total_amount')); ?>;
        
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: categories,
                datasets: [{
                    label: 'Expense Amount',
                    data: amounts,
                    backgroundColor: '#4361ee',
                    borderColor: '#2b2d42',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { position: 'top' },
                    tooltip: { callbacks: { label: function(context) {
                        return '₹' + context.raw.toFixed(2);
                    }}}
                }
            }
        });

        // PDF Download Function
        function downloadPDF() {
            const element = document.getElementById('report-content');
            const opt = {
                margin: [0.5, 0.5, 0.5, 0.5],
                filename: 'financial_report_<?php echo $selectedMonth; ?>.pdf',
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2, useCORS: true, logging: false },
                jsPDF: { unit: 'in', format: 'a4', orientation: 'portrait' }
            };
            html2pdf().set(opt).from(element).save();
        }
    </script>