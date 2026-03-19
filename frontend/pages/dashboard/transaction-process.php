<?php

require_once "../../../backend/config/dbcon.php";

$userId=1;
$conn=getConnection();

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $category = $_POST['category'] ?? '';
    $amount = $_POST['amount'] ?? '';
    $date = $_POST['date'] ?? '';
    $notes = $_POST['description'] ?? '';

    $stmt= $conn->prepare("SELECT * FROM categories WHERE category_name=?");
    $stmt->bind_param("s", $category);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $catId=$row['category_id'];

    if ($category == "" || $amount == "" || $date == "") {
        echo "❌ Fill all fields";
        exit();
    }

    $stmt = $conn->prepare("
        INSERT INTO expenses (category_id, user_id, amount, expense_date, notes) 
        VALUES (?, ?, ?, ?, ?)
    ");

    if (!$stmt) {
        echo "❌ SQL Error: " . $conn->error;
        exit();
    }

    $stmt->bind_param("iidss", $catId, $userId, $amount, $date, $notes);

    if ($stmt->execute()) {
        echo "✅ Expense added successfully!";
    } else {
        echo "❌ Execute Error: " . $stmt->error;
    }

    $stmt->close();
}

$conn->close();

?>