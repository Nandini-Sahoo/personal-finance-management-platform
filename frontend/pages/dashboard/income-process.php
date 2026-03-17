<?php

$conn = new mysqli("localhost","root","","personal_finance_db");

if($conn->connect_error){
die("Database connection failed");
}

$category = $_POST['category'];
$amount = $_POST['amount'];
$date = $_POST['date'];
$description = $_POST['description'];

$sql = "INSERT INTO income(category,amount,date,description)
VALUES('$category','$amount','$date','$description')";

if($conn->query($sql)){
echo "✅ Income Added Successfully! You can add another.";
}else{
echo "❌ Error adding income.";
}

$conn->close();

?>