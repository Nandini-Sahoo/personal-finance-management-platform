<?php

require_once "../../../backend/config/dbcon.php";
require_once '../../../backend/session.php';

Session::requireLogin();
$userId = Session::getUserId();
$conn=getConnection();


$category = $_POST['category'];
$amount = $_POST['amount'];
$date = $_POST['date'];
$description = $_POST['description'];
$payment = $_POST['payment'];

$stmt= $conn->prepare("SELECT * FROM categories WHERE category_name=?");
$stmt->bind_param("s", $category);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$catId=$row['category_id'];

$stmt = $conn->prepare("INSERT INTO income (user_id, category_id, amount, income_date, source, payment_method) VALUES(?, ?, ?, ?, ?, ?)");
$stmt->bind_param("iidsss", $userId, $catId, $amount, $date, $description, $payment);

if($stmt->execute()){
echo "✅ Income Added Successfully! You can add another.";
}else{
echo "❌ Error adding income.";
}

$conn->close();

?>