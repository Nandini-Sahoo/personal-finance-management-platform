<?php
// require_once '../includes/session.php';
require_once '../../../backend/budget-func.php';

// Check if user is logged in
// Session::requireLogin();
// $userId = Session::getUserId();

$userId =1;
$userName = " ";

// Only allow AJAX requests
if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied']);
    exit();
}

// Initialize response
$response = [
    'success' => false,
    'message' => '',
    'data' => null
];

// Get request parameters
$action = $_POST['action'] ?? '';

$budgetFunctions = new BudgetFunctions();

switch ($action) {
    case 'get_budget_status':
        $monthYear = $_POST['month'] ?? date('Y-m');
        $budgetData = $budgetFunctions->getMonthlyBudgetData($userId, $monthYear);
        
        $response['success'] = true;
        $response['data'] = $budgetData;
        break;
        
    case 'update_budget':
        $monthYear = $_POST['month'] ?? date('Y-m');
        $budgets = $_POST['budgets'] ?? [];
        
        $result = $budgetFunctions->saveBudgets($userId, $monthYear, $budgets);
        
        if ($result['success']) {
            $response['success'] = true;
            $response['message'] = 'Budgets updated successfully';
        } else {
            $response['message'] = 'Error updating budgets: ' . implode(', ', $result['errors']);
        }
        break;
        
    case 'get_summary':
        $summary = $budgetFunctions->getCurrentMonthSummary($userId);
        $response['success'] = true;
        $response['data'] = $summary;
        break;
        
    default:
        $response['message'] = 'Invalid action';
        break;
}

header('Content-Type: application/json');
echo json_encode($response);
?>