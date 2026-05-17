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
    $description = $_POST['description'] ?? '';
    $paymentMethod = $_POST['payment'] ?? 'Cash';

    // Get category ID from category name
    $stmt = $conn->prepare("SELECT category_id FROM categories WHERE category_name = ? AND category_type = 'income'");
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

    // Insert income with payment method
    $stmt = $conn->prepare("
        INSERT INTO income (user_id, category_id, amount, income_date, source, payment_method) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->bind_param("iidsss", $userId, $catId, $amount, $date, $description, $paymentMethod);

    if ($stmt->execute()) {
        echo "✅ Income Added Successfully! You can add another.";
    } else {
        echo "❌ Error adding income: " . $stmt->error;
    }

    $stmt->close();
}

$conn->close();
?>