<?php
require_once __DIR__ . '/config/dbcon.php';
define('CURRENCY', '₹');
class ReportFunctions {
    
    // private $db;
    
    // public function __construct() {
    //     $this->db = new Database();
    // }

    
    /**
     * Get available months for a user
    */
    public function getAvailableMonths($userId) {
        $db=getConnection();
        $sql = "SELECT DISTINCT 
                    DATE_FORMAT(expense_date, '%Y-%m') as month_year,
                    DATE_FORMAT(expense_date, '%M %Y') as month_name
                FROM expenses 
                WHERE user_id = ?
                UNION
                SELECT DISTINCT 
                    DATE_FORMAT(income_date, '%Y-%m') as month_year,
                    DATE_FORMAT(income_date, '%M %Y') as month_name
                FROM income 
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
        return $months;
    }
    
    /**
     * Get monthly comparison data
     */
    public function getMonthlyComparison($userId, $month1, $month2) {
        // Get expenses for month 1
        $expenses1 = $this->getMonthlyExpensesByCategory($userId, $month1);
        
        // Get expenses for month 2
        $expenses2 = $this->getMonthlyExpensesByCategory($userId, $month2);
        
        // Merge and calculate comparisons
        $comparison = [];
        $allCategories = array_unique(array_merge(array_keys($expenses1), array_keys($expenses2)));
        
        foreach ($allCategories as $category) {
            $amount1 = $expenses1[$category] ?? 0;
            $amount2 = $expenses2[$category] ?? 0;
            
            $difference = $amount1 - $amount2;
            $percentageChange = 0;
            
            if ($amount2 > 0) {
                $percentageChange = round(($difference / $amount2) * 100);
            } elseif ($amount1 > 0) {
                $percentageChange = 100;
            }
            
            $comparison[$category] = [
                'month1_amount' => $amount1,
                'month2_amount' => $amount2,
                'difference' => $difference,
                'percentage_change' => $percentageChange,
                'trend' => $this->getTrendIcon($percentageChange),
                'color' => $this->getCategoryColor($category)
            ];
        }
        
        // Sort by month1 amount descending
        uasort($comparison, function($a, $b) {
            return $b['month1_amount'] <=> $a['month1_amount'];
        });
        
        return $comparison;
    }
    
    /**
     * Get monthly expenses grouped by category
     */
    private function getMonthlyExpensesByCategory($userId, $monthYear) {
        $db=getConnection();
        $sql = "SELECT 
                    c.category_name,
                    SUM(e.amount) as total_amount
                FROM expenses e
                JOIN categories c ON e.category_id = c.category_id
                WHERE e.user_id = ? 
                    AND DATE_FORMAT(e.expense_date, '%Y-%m') = ?
                GROUP BY c.category_name
                ORDER BY total_amount DESC";
        
        $stmt = $db->prepare($sql);
        $stmt->bind_param("is", $userId, $monthYear);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $expenses = [];
        while ($row = $result->fetch_assoc()) {
            $expenses[$row['category_name']] = floatval($row['total_amount']);
        }
        
        $stmt->close();
        $db->close();
        return $expenses;
    }
    
    /**
     * Get monthly summary (income, expense, savings)
     */
    public function getMonthlySummary($userId, $monthYear) {
        $db=getConnection();
        // Get total income
        $sqlIncome = "SELECT COALESCE(SUM(amount), 0) as total 
                     FROM income 
                     WHERE user_id = ? AND DATE_FORMAT(income_date, '%Y-%m') = ?";
        $stmt = $db->prepare($sqlIncome);
        $stmt->bind_param("is", $userId, $monthYear);
        $stmt->execute();
        $result = $stmt->get_result();
        $income = $result->fetch_assoc()['total'];
        $stmt->close();
        
        // Get total expense
        $sqlExpense = "SELECT COALESCE(SUM(amount), 0) as total 
                      FROM expenses 
                      WHERE user_id = ? AND DATE_FORMAT(expense_date, '%Y-%m') = ?";
        $stmt = $db->prepare($sqlExpense);
        $stmt->bind_param("is", $userId, $monthYear);
        $stmt->execute();
        $result = $stmt->get_result();
        $expense = $result->fetch_assoc()['total'];
        $stmt->close();
        
        $savings = $income - $expense;
        $savingsPercentage = $income > 0 ? round(($savings / $income) * 100) : 0;
        
        return [
            'income' => $income,
            'expense' => $expense,
            'savings' => $savings,
            'savings_percentage' => $savingsPercentage
        ];
    }
    
    /**
     * Generate insights from comparison data
     */
    public function generateInsights($comparison, $month1, $month2) {
        $db=getConnection();
        $insights = [];
        
        // Find categories with significant increase (>20%)
        $increasedCategories = array_filter($comparison, function($item) {
            return $item['percentage_change'] > 20;
        });
        
        // Find categories with significant decrease (>20%)
        $decreasedCategories = array_filter($comparison, function($item) {
            return $item['percentage_change'] < -20;
        });
        
        // Find top spender
        $topSpender = !empty($comparison) ? array_key_first($comparison) : null;
        $topAmount = $topSpender ? $comparison[$topSpender]['month1_amount'] : 0;
        
        // Generate insights
        if (!empty($increasedCategories)) {
            $topIncrease = array_key_first($increasedCategories);
            $increasePercent = $increasedCategories[$topIncrease]['percentage_change'];
            $increaseAmount = $increasedCategories[$topIncrease]['month1_amount'] - 
                            $increasedCategories[$topIncrease]['month2_amount'];
            
            $insights[] = [
                'type' => 'warning',
                'icon' => '📈',
                'message' => "You spent " . abs($increasePercent) . "% more on {$topIncrease} this month (₹" . 
                            number_format($increaseAmount, 2) . " increase)"
            ];
        }
        
        if (!empty($decreasedCategories)) {
            $topDecrease = array_key_first($decreasedCategories);
            $decreasePercent = abs($decreasedCategories[$topDecrease]['percentage_change']);
            $decreaseAmount = $decreasedCategories[$topDecrease]['month2_amount'] - 
                            $decreasedCategories[$topDecrease]['month1_amount'];
            
            $insights[] = [
                'type' => 'success',
                'icon' => '📉',
                'message' => "Great job! You saved " . $decreasePercent . "% on {$topDecrease} (₹" . 
                            number_format($decreaseAmount, 2) . " less than last month)"
            ];
        }
        
        if ($topSpender) {
            $insights[] = [
                'type' => 'info',
                'icon' => '💰',
                'message' => "Your highest spending category is {$topSpender} (₹" . 
                            number_format($topAmount, 2) . ")"
            ];
        }
        
        // Check if any category has no budget set
        $sql = "SELECT DISTINCT c.category_name 
                FROM categories c
                JOIN expenses e ON c.category_id = e.category_id
                WHERE e.user_id = ? 
                    AND DATE_FORMAT(e.expense_date, '%Y-%m') = ?
                    AND c.category_type = 'expense'
                    AND c.category_name NOT IN (
                        SELECT c2.category_name
                        FROM budget b
                        JOIN categories c2 ON b.category_id = c2.category_id
                        WHERE b.user_id = ? 
                            AND DATE_FORMAT(b.budget_month, '%Y-%m') = ?
                    )
                LIMIT 1";
        
        $stmt = $db->prepare($sql);
        $stmt->bind_param("isis", $userId, $month1, $userId, $month1);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            $insights[] = [
                'type' => 'suggestion',
                'icon' => '🎯',
                'message' => "Consider setting a budget for {$row['category_name']} category"
            ];
        }
        $stmt->close();
        $db->close();
        
        return $insights;
    }
    
    /**
     * Get category color for charts
     */
    private function getCategoryColor($categoryName) {
        $colors = [
            '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', 
            '#FF9F40', '#8AC926', '#1982C4', '#6A4C93', '#F94144',
            '#F3722C', '#F8961E', '#F9C74F', '#90BE6D', '#43AA8B'
        ];
        
        $index = abs(crc32($categoryName)) % count($colors);
        return $colors[$index];
    }
    
    /**
     * Get trend icon based on percentage change
     */
    private function getTrendIcon($percentage) {
        if ($percentage > 0) return '▲';
        if ($percentage < 0) return '▼';
        return '●';
    }
    
    /**
     * Format currency
    */
    public function formatCurrency($amount) {
        return CURRENCY . ' ' . number_format($amount, 2);
        }
        
    /**
     * Get month name from year-month
     */
    public function getMonthName($monthYear) {
        return date('F Y', strtotime($monthYear . '-01'));
    }
}
?>