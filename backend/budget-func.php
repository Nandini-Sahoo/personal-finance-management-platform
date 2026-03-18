<?php
require_once __DIR__ . '/config/dbcon.php';

class BudgetFunctions {
    
    // private $db;
    
    // public function __construct() {
    //     $this->db = new Database();
    // }
    
    /**
     * Get all expense categories
     */
    public function getExpenseCategories() {
        $db=getConnection();
        $sql = "SELECT category_id, category_name 
                FROM categories 
                WHERE category_type = 'expense' 
                ORDER BY category_name";
        
        $result = $db->query($sql);
        
        $categories = [];
        while ($row = $result->fetch_assoc()) {
            $categories[] = $row;
        }
        
        $db->close();
        return $categories;
    }
    
    /**
     * Get available months for budget
     */
    public function getAvailableBudgetMonths($userId) {
        $db=getConnection();
        $sql = "SELECT DISTINCT DATE_FORMAT(budget_month, '%Y-%m') as month_year,
                       DATE_FORMAT(budget_month, '%M %Y') as month_name
                FROM budget 
                WHERE user_id = ?
                UNION
                SELECT DISTINCT DATE_FORMAT(expense_date, '%Y-%m') as month_year,
                       DATE_FORMAT(expense_date, '%M %Y') as month_name
                FROM expenses 
                WHERE user_id = ?
                ORDER BY month_year DESC";
        
        $stmt = $db->prepare($sql);
        $stmt->bind_param("ii", $userId, $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $months = [];
        while ($row = $result->fetch_assoc()) {
            $months[] = $row;
        }
        
        $stmt->close();
        $db->close();
        
        // If no months found, add current month
        if (empty($months)) {
            $months[] = [
                'month_year' => date('Y-m'),
                'month_name' => date('F Y')
            ];
        }
        
        return $months;
    }
    
    /**
     * Get budget data for a specific month
     */
    public function getMonthlyBudgetData($userId, $monthYear) {
        $db=getConnection();
        // Get all expense categories first
        $categories = $this->getExpenseCategories();
        
        // Get existing budgets for the month
        $sql = "SELECT category_id, target_amount 
                FROM budget 
                WHERE user_id = ? AND DATE_FORMAT(budget_month, '%Y-%m') = ?";
        
        $stmt = $db->prepare($sql);
        $stmt->bind_param("is", $userId, $monthYear);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $existingBudgets = [];
        while ($row = $result->fetch_assoc()) {
            $existingBudgets[$row['category_id']] = $row['target_amount'];
        }
        $stmt->close();
        
        // Get actual spending for the month
        $sql = "SELECT category_id, SUM(amount) as spent_amount 
                FROM expenses 
                WHERE user_id = ? AND DATE_FORMAT(expense_date, '%Y-%m') = ?
                GROUP BY category_id";
        
        $stmt = $db->prepare($sql);
        $stmt->bind_param("is", $userId, $monthYear);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $actualSpending = [];
        while ($row = $result->fetch_assoc()) {
            $actualSpending[$row['category_id']] = $row['spent_amount'];
            }
            $stmt->close();
            $db->close();
        
        // Combine all data
        $budgetData = [];
        foreach ($categories as $category) {
            $categoryId = $category['category_id'];
            $budgetAmount = $existingBudgets[$categoryId] ?? 0;
            $spentAmount = $actualSpending[$categoryId] ?? 0;
            
            $percentage = $budgetAmount > 0 ? round(($spentAmount / $budgetAmount) * 100, 1) : 0;
            $status = $this->getBudgetStatus($percentage, $spentAmount, $budgetAmount);
            
            $budgetData[] = [
                'category_id' => $categoryId,
                'category_name' => $category['category_name'],
                'color' => $category['color'] ?? '#4361ee',
                'budget_amount' => $budgetAmount,
                'spent_amount' => $spentAmount,
                'remaining' => $budgetAmount - $spentAmount,
                'percentage' => $percentage,
                'status' => $status['status'],
                'icon' => $status['icon'],
                'message' => $status['message'],
                'progress_class' => $status['progress_class']
            ];
        }
        
        return $budgetData;
    }
    
    /**
     * Get budget status based on percentage
     */
    private function getBudgetStatus($percentage, $spent, $budget) {
        if ($budget == 0) {
            return [
                'status' => 'no-budget',
                'icon' => '⚪',
                'message' => 'No budget set',
                'progress_class' => 'bg-secondary'
            ];
        } elseif ($percentage >= 100) {
            return [
                'status' => 'over-budget',
                'icon' => '❌',
                'message' => 'Over budget by ' . $this->formatCurrency($spent - $budget),
                'progress_class' => 'bg-danger'
            ];
        } elseif ($percentage >= 90) {
            return [
                'status' => 'warning',
                'icon' => '⚠️',
                'message' => 'Approaching budget limit',
                'progress_class' => 'bg-warning'
            ];
        } elseif ($percentage >= 75) {
            return [
                'status' => 'caution',
                'icon' => '⚡',
                'message' => 'Getting closer to limit',
                'progress_class' => 'bg-info'
            ];
        } else {
            return [
                'status' => 'good',
                'icon' => '✅',
                'message' => 'Within budget',
                'progress_class' => 'bg-success'
            ];
        }
    }
    
    /**
     * Save budgets for a month
     */
    public function saveBudgets($userId, $monthYear, $budgets) {
        $success = true;
        $errors = [];
        
        // Start transaction
        $conn = getConnection();
        $conn->begin_transaction();
        
        try {
            foreach ($budgets as $categoryId => $amount) {
                // Skip if amount is empty or not numeric
                if ($amount === '' || !is_numeric($amount)) {
                    continue;
                }
                
                $amount = floatval($amount);
                
                // Check if budget exists
                $sql = "SELECT budget_id FROM budget 
                        WHERE user_id = ? AND category_id = ? AND DATE_FORMAT(budget_month, '%Y-%m') = ?";
                
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("iis", $userId, $categoryId, $monthYear);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    // Update existing budget
                    $sql = "UPDATE budget SET target_amount = ? 
                            WHERE user_id = ? AND category_id = ? AND DATE_FORMAT(budget_month, '%Y-%m') = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("diis", $amount, $userId, $categoryId, $monthYear);
                } else {
                    // Insert new budget
                    $budgetMonth = $monthYear . '-01';
                    $sql = "INSERT INTO budget (user_id, category_id, target_amount, budget_month) 
                            VALUES (?, ?, ?, ?)";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("iids", $userId, $categoryId, $amount, $budgetMonth);
                }
                
                if (!$stmt->execute()) {
                    throw new Exception("Failed to save budget for category " . $categoryId);
                }
                
                $stmt->close();
            }
            
            $conn->commit();
            
        } catch (Exception $e) {
            $conn->rollback();
            $success = false;
            $errors[] = $e->getMessage();
        }
        
        return [
            'success' => $success,
            'errors' => $errors
        ];
    }
    
    /**
     * Get all budgets for user
     */
    public function getAllBudgets($userId) {
        $db=getConnection();
        $sql = "SELECT 
                    b.budget_id,
                    b.category_id,
                    c.category_name,
                    b.target_amount,
                    DATE_FORMAT(b.budget_month, '%Y-%m') as month_year,
                    DATE_FORMAT(b.budget_month, '%M %Y') as month_name,
                    COALESCE((
                        SELECT SUM(amount) 
                        FROM expenses e 
                        WHERE e.user_id = b.user_id 
                        AND e.category_id = b.category_id 
                        AND DATE_FORMAT(e.expense_date, '%Y-%m') = DATE_FORMAT(b.budget_month, '%Y-%m')
                    ), 0) as spent_amount
                FROM budget b
                JOIN categories c ON b.category_id = c.category_id
                WHERE b.user_id = ?
                ORDER BY b.budget_month DESC, c.category_name";
        
        $stmt = $db->prepare($sql);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $budgets = [];
        while ($row = $result->fetch_assoc()) {
            $row['percentage'] = $row['target_amount'] > 0 ? 
                round(($row['spent_amount'] / $row['target_amount']) * 100, 1) : 0;
            $row['remaining'] = $row['target_amount'] - $row['spent_amount'];
            $budgets[] = $row;
        }
        
        $stmt->close();
        $db->close();
        return $budgets;
    }
    
    /**
     * Get budget summary for current month
     */
    public function getCurrentMonthSummary($userId) {
        $currentMonth = date('Y-m');
        $budgetData = $this->getMonthlyBudgetData($userId, $currentMonth);
        
        $totalBudget = 0;
        $totalSpent = 0;
        $categoriesWithBudget = 0;
        $overBudgetCount = 0;
        $warningCount = 0;
        
        foreach ($budgetData as $item) {
            if ($item['budget_amount'] > 0) {
                $totalBudget += $item['budget_amount'];
                $totalSpent += $item['spent_amount'];
                $categoriesWithBudget++;
                
                if ($item['percentage'] >= 100) {
                    $overBudgetCount++;
                } elseif ($item['percentage'] >= 90) {
                    $warningCount++;
                }
            }
        }
        
        return [
            'total_budget' => $totalBudget,
            'total_spent' => $totalSpent,
            'remaining' => $totalBudget - $totalSpent,
            'percentage' => $totalBudget > 0 ? round(($totalSpent / $totalBudget) * 100, 1) : 0,
            'categories_with_budget' => $categoriesWithBudget,
            'over_budget_count' => $overBudgetCount,
            'warning_count' => $warningCount
        ];
    }
    
    /**
     * Format currency
     */
    public function formatCurrency($amount) {
        return '₹ ' . number_format($amount, 2);
    }
    
    /**
     * Get month name from year-month
     */
    public function getMonthName($monthYear) {
        return date('F Y', strtotime($monthYear . '-01'));
    }
}
?>