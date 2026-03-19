<?php 
session_start();
include_once "navbar.php";
//include_once "check.php";
require_once "../../../backend/config/dbcon.php";

// $id = $_SESSION['user_id'];
$id = 1;
$conn=getConnection();
$qry = "SELECT * FROM users WHERE user_id=?";
$stmt = $conn->prepare($qry);
$stmt->bind_param("i",$id);
$stmt->execute();

$result = $stmt->get_result();
$data = $result->fetch_assoc();
?>

<div class="container mt-4">

<h2>Your Profile</h2>

<div class="card p-3">

<p><b>Name:</b> <?php echo $data['name']; ?></p>

<p><b>Email:</b> <?php echo $data['email']; ?></p>

</div>

</div>

<?php include_once "footer.php"; ?>