<?php

define('DB_HOST', '127.0.0.1');
define('DB_USER', 'root');
define('DB_PASS', 'Mona@123');
define('DB_NAME', 'personal_finance_db');
define('DB_PORT', 3307);

function getConnection() {

    $con = mysqli_connect(
        DB_HOST,
        DB_USER,
        DB_PASS,
        DB_NAME,
        DB_PORT
    );

    if (!$con) {
        die("Connection failed: " . mysqli_connect_error());
    }

    return $con;
}

?>