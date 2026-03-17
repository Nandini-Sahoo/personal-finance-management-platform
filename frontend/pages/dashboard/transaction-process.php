<?php

$conn = new mysqli("localhost", "root", "", "personal_finance_db");

if ($conn->connect_error) {
    die("Database connection failed");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $category = $_POST['category'] ?? '';
    $amount = $_POST['amount'] ?? '';
    $date = $_POST['date'] ?? '';
    $notes = $_POST['description'] ?? '';

    if ($category == "" || $amount == "" || $date == "") {
        echo "❌ Fill all fields";
        exit();
    }

    $stmt = $conn->prepare("
        INSERT INTO expenses (category_id, amount, expense_date, notes) 
        VALUES (?, ?, ?, ?)
    ");

    if (!$stmt) {
        echo "❌ SQL Error: " . $conn->error;
        exit();
    }

    $stmt->bind_param("siss", $category, $amount, $date, $notes);

    if ($stmt->execute()) {
        echo "✅ Expense added successfully!";
    } else {
        echo "❌ Execute Error: " . $stmt->error;
    }

    $stmt->close();
}

$conn->close();

?>