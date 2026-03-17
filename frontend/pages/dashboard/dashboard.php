<?php
session_start();
if(!isset($_SESSION['username'])){
    $_SESSION['username'] = "User";
}
$username = $_SESSION['username'];
?>

<!DOCTYPE html>
<html>
<head>
<title>Finance Dashboard</title>

<style>

body{
    margin:0;
    font-family: Arial;
    background:#0a192f;
    color:white;
}

/* Layout */

.container{
    display:flex;
}

/* Sidebar */

.sidebar{
    width:230px;
    min-height:100vh;
    background:#020c1b;
}

.sidebar h2{
    text-align:center;
    padding:20px;
    margin:0;
    background:#0a192f;
    color:#64ffda;
}

.sidebar a{
    display:block;
    padding:15px;
    color:#ccd6f6;
    text-decoration:none;
    border-bottom:1px solid #112240;
}

.sidebar a:hover{
    background:#112240;
    color:#64ffda;
}

/* Main */

.main{
    flex:1;
    padding:25px;
}

.main h2{
    color:#64ffda;
}

/* Summary */

.summary{
    background:#112240;
    padding:20px;
    border-radius:10px;
    margin-bottom:20px;
}

.summary h3{
    color:#64ffda;
}

/* Charts */

.charts{
    display:flex;
    gap:20px;
}

.chart-box{
    flex:1;
    background:#112240;
    padding:20px;
    border-radius:10px;
}

.chart-box h4{
    color:#64ffda;
}

/* Alerts */

.alerts{
    background:#112240;
    margin-top:20px;
    padding:15px;
    border-left:4px solid #64ffda;
    border-radius:5px;
}

/* Transactions */

.transactions{
    background:#112240;
    margin-top:20px;
    padding:20px;
    border-radius:10px;
}

.transactions ul{
    list-style:none;
    padding:0;
}

.transactions li{
    padding:10px;
    border-bottom:1px solid #233554;
}

.transactions li:hover{
    background:#233554;
}

</style>
</head>

<body>

<div class="container">

<!-- Sidebar -->

<div class="sidebar">

<h2>💰 Finance</h2>

<a href="dashboard.php">🏠 Home</a>
<a href="add_expense.php">💸 Add Expense</a>
<a href="add_income.php">📥 Add Income</a>
<a href="reports.php">📊 View Reports</a>
<a href="budget.php">🎯 Budget Planner</a>
<a href="settings.php">⚙️ Settings</a>
<a href="logout.php">🚪 Logout</a>

</div>

<!-- Main Content -->

<div class="main">

<h2>🏠 Dashboard</h2>

<div class="summary">

<h3>WELCOME BACK, <?php echo $username; ?>!</h3>

<h4>📊 Monthly Summary (March 2024)</h4>

<p>💰 Total Income: ₹50,000</p>
<p>💸 Total Expense: ₹35,000</p>
<p>💵 Monthly Savings: ₹15,000</p>
<p>📈 Savings Rate: 30%</p>

</div>


<!-- Charts -->

<div class="charts">

<div class="chart-box">
<h4>Expense Distribution</h4>
<canvas id="pieChart"></canvas>
</div>

<div class="chart-box">
<h4>Income vs Expense</h4>
<canvas id="barChart"></canvas>
</div>

<div class="chart-box">
<h4>Monthly Trends</h4>
<canvas id="lineChart"></canvas>
</div>

</div>


<!-- Alerts -->

<div class="alerts">

⚠️ <b>Alerts</b><br><br>

• Budget alert: Food category at 85% <br>
• Large expense: ₹5,000 on Shopping

</div>


<!-- Transactions -->

<div class="transactions">

<h3>📋 Recent Transactions</h3>

<ul>
<li>Mar 15: Food - ₹500</li>
<li>Mar 14: Shopping - ₹2,000</li>
<li>Mar 13: Salary - ₹50,000</li>
</ul>

</div>

</div>

</div>


<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>

// Pie Chart
new Chart(document.getElementById("pieChart"),{
type:'pie',
data:{
labels:['Food','Shopping','Transport'],
datasets:[{
data:[5000,2000,1500],
backgroundColor:['#64ffda','#00bcd4','#1e90ff']
}]
}
});

// Bar Chart
new Chart(document.getElementById("barChart"),{
type:'bar',
data:{
labels:['Income','Expense'],
datasets:[{
data:[50000,35000],
backgroundColor:['#1e90ff','#64ffda']
}]
}
});

// Line Chart
new Chart(document.getElementById("lineChart"),{
type:'line',
data:{
labels:['Jan','Feb','Mar'],
datasets:[{
label:'Expenses',
data:[20000,25000,35000],
borderColor:'#64ffda',
fill:false
}]
}
});

</script>

</body>
</html>