<?php 
//session_start();
include_once "navbar.php";
//include_once "check.php";
require_once "../../../backend/config/dbcon.php";
$conn = getConnection();
$id = $_SESSION['user_id'];

/* Total Income */
$qry_income = "SELECT SUM(amount) as total_income FROM income WHERE user_id=?";
$stmt1 = $conn->prepare($qry_income);
$stmt1->bind_param("i",$id);
$stmt1->execute();
$result1 = $stmt1->get_result();
$income = $result1->fetch_assoc()['total_income'] ?? 0;

/* Total Expense */
$qry_expense = "SELECT SUM(amount) as total_expense FROM expenses WHERE user_id=?";
$stmt2 = $conn->prepare($qry_expense);
$stmt2->bind_param("i",$id);
$stmt2->execute();
$result2 = $stmt2->get_result();
$expense = $result2->fetch_assoc()['total_expense'] ?? 0;

/* Balance */
$balance = $income - $expense;
?>

<div class="container my-4">

<h2 class="text-center mb-4">Dashboard</h2>

<div class="row">

<!-- Income Card -->
<div class="col-md-4">
<div class="card bg-success text-white shadow">
<div class="card-body text-center">
<h5>Total Income</h5>
<h3>₹ <?php echo $income; ?></h3>
</div>
</div>
</div>

<!-- Expense Card -->
<div class="col-md-4">
<div class="card bg-danger text-white shadow">
<div class="card-body text-center">
<h5>Total Expense</h5>
<h3>₹ <?php echo $expense; ?></h3>
</div>
</div>
</div>

<!-- Balance Card -->
<div class="col-md-4">
<div class="card bg-primary text-white shadow">
<div class="card-body text-center">
<h5>Balance</h5>
<h3>₹ <?php echo $balance; ?></h3>
</div>
</div>
</div>

</div>

<hr class="my-4">

<div class="text-center">

<a href="add_income.php" class="btn btn-success m-2">Add Income</a>

<a href="add_expense.php" class="btn btn-danger m-2">Add Expense</a>

<a href="transactions.php" class="btn btn-info m-2">View Transactions</a>
</div>
</div>
<br>
<?php include_once "footer.php"; ?>