<?php
include_once "navbar.php";
include_once "check.php";
require_once "dbcon.php";

if(isset($_POST['submit'])){
    $title = $_POST['title'];
    $amount = $_POST['amount'];
    $date = $_POST['date'];
    $id = $_SESSION['user_id'];

    $qry = "INSERT INTO transactions (user_id,title,amount,type,date) VALUES (?,?,?,?,?)";
    $stmt = $conn->prepare($qry);
    $type = "expense";

    $stmt->bind_param("isdss",$id,$title,$amount,$type,$date);
    $stmt->execute();

    echo "<div class='alert alert-danger'>Expense Added Successfully</div>";
}
?>

<div class="container mt-4">
<h2>Add Expense</h2>

<form method="POST">

<input type="text" name="title" class="form-control mb-3" placeholder="Expense Title" required>

<input type="number" name="amount" class="form-control mb-3" placeholder="Amount" required>

<input type="date" name="date" class="form-control mb-3" required>

<button name="submit" class="btn btn-danger">Add Expense</button>

</form>
</div>

<?php include_once "footer.php"; ?>