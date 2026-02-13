<?php
// require_once '../includes/session.php';
// require_once '../includes/report_functions.php';

// // Check if user is logged in
// Session::requireLogin();
// $userId = Session::getUserId();

// Only allow AJAX requests
if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied']);
    exit();
}

// Initialize response
$response = [
    'success' => false,
    'data' => null,
    'message' => ''
];

// Get request parameters
$action = $_GET['action'] ?? $_POST['action'] ?? '';

$reportFunctions = new ReportFunctions();

switch ($action) {
    case 'get_monthly_comparison':
        $month1 = $_GET['month1'] ?? date('Y-m');
        $month2 = $_GET['month2'] ?? date('Y-m', strtotime('-1 month'));
        
        $comparison = $reportFunctions->getMonthlyComparison($userId, $month1, $month2);
        $insights = $reportFunctions->generateInsights($comparison, $month1, $month2);
        $summary1 = $reportFunctions->getMonthlySummary($userId, $month1);
        $summary2 = $reportFunctions->getMonthlySummary($userId, $month2);
        
        $response['success'] = true;
        $response['data'] = [
            'comparison' => $comparison,
            'insights' => $insights,
            'summary1' => $summary1,
            'summary2' => $summary2,
            'month1_name' => $reportFunctions->getMonthName($month1),
            'month2_name' => $reportFunctions->getMonthName($month2)
        ];
        break;
        
    case 'get_available_months':
        $months = $reportFunctions->getAvailableMonths($userId);
        $response['success'] = true;
        $response['data'] = $months;
        break;
        
    case 'export_comparison':
        $month1 = $_GET['month1'] ?? date('Y-m');
        $month2 = $_GET['month2'] ?? date('Y-m', strtotime('-1 month'));
        
        $comparison = $reportFunctions->getMonthlyComparison($userId, $month1, $month2);
        
        // Generate CSV
        $filename = "comparison_{$month1}_vs_{$month2}.csv";
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // Add headers
        fputcsv($output, ['Category', $month1 . ' Amount', $month2 . ' Amount', 'Difference', 'Change %']);
        
        // Add data
        foreach ($comparison as $category => $data) {
            if ($data['month1_amount'] > 0 || $data['month2_amount'] > 0) {
                fputcsv($output, [
                    $category,
                    $data['month1_amount'],
                    $data['month2_amount'],
                    $data['difference'],
                    $data['percentage_change'] . '%'
                ]);
            }
        }
        
        fclose($output);
        exit();
        break;
        
    default:
        $response['message'] = 'Invalid action';
        break;
}

header('Content-Type: application/json');
echo json_encode($response);
?>