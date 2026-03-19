<!-- <?php
session_start();
?> -->

<!DOCTYPE html>
<html>
<head>

<title>Finance Manager</title>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">

</head>

<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">

<div class="container">

<a class="navbar-brand" href="dashboard.php">Finance Manager</a>

<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menu">
<span class="navbar-toggler-icon"></span>
</button>

<div class="collapse navbar-collapse" id="menu">

<ul class="navbar-nav ms-auto">

<li class="nav-item">
<a class="nav-link" href="dashboard.php">Dashboard</a>
</li>

<li class="nav-item">
<a class="nav-link" href="add_income.php">Add Income</a>
</li>

<li class="nav-item">
<a class="nav-link" href="add_expense.php">Add Expense</a>
</li>

<li class="nav-item">
<a class="nav-link" href="transactions.php">Transactions</a>
</li>

<li class="nav-item">
<a class="nav-link" href="profile.php">Profile</a>
</li>

<li class="nav-item">
<a class="nav-link text-danger" href="logout.php">Logout</a>
</li>

</ul>

</div>

</div>

</nav>