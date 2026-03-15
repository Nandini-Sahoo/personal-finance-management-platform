<?php
$HOST = "localhost";
$USER = "root";
$PASSWORD = "Mona@123";
$DB = "personal_finance_db";

$conn = new mysqli($HOST, $USER, $PASSWORD, $DB);
if($conn->connect_error)
    die ("ERROR: ".$conn->connect_error);
?>