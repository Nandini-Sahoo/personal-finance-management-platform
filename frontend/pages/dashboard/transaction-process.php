<?php
require_once "../../../backend/config/dbcon.php";
require_once '../../../backend/session.php';

Session::requireLogin();
$userId = Session::getUserId();
$conn = getConnection();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $category = $_POST['category'] ?? '';
    $amount = $_POST['amount'] ?? '';
    $date = $_POST['date'] ?? '';
    $notes = $_POST['description'] ?? '';
    $paymentMethod = $_POST['payment'] ?? 'Cash';

    // Get category ID from category name
    $stmt = $conn->prepare("SELECT category_id FROM categories WHERE category_name = ? AND category_type = 'expense'");
    $stmt->bind_param("s", $category);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    if (!$row) {
        echo "❌ Invalid category selected.";
        exit();
    }
    
    $catId = $row['category_id'];

    if ($category == "" || $amount == "" || $date == "") {
        echo "❌ Please fill all required fields";
        exit();
    }

    // Insert expense with payment method
    $stmt = $conn->prepare("
        INSERT INTO expenses (category_id, user_id, amount, expense_date, notes, payment_method) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    if (!$stmt) {
        echo "❌ SQL Error: " . $conn->error;
        exit();
    }

    $stmt->bind_param("iidsss", $catId, $userId, $amount, $date, $notes, $paymentMethod);

    if ($stmt->execute()) {
        echo "✅ Expense added successfully!";
    } else {
        echo "❌ Error: " . $stmt->error;
    }

    $stmt->close();
}

$conn->close();
?>