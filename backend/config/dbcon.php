<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'personal_finance_db');
// uncomment the below if your port 3306 is available
// function getConnection() {
//     $conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    function getConnection() {
        $conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME, 3307);

    if (!$conn) {
        die("Connection failed: " . mysqli_connect_error());
    }
    
    return $conn;
}
?>