<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'Mona@123');
define('DB_NAME', 'personal_finance_db');
<<<<<<< HEAD
// uncomment the below if your port 3306 is available
// function getConnection() {
//     $conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    function getConnection() {
        $conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME, 3306);
=======

function getConnection() {

    $conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
>>>>>>> d0bf7e272a5b29e474dd605f1f23ccc0d744844c

    if (!$conn) {
        die("Connection failed: " . mysqli_connect_error());
    }

    return $conn;
}
?>