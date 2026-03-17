<?php
$conn = new mysqli("localhost","root","","personal_finance_db");

if($conn->connect_error){
die("Database connection failed");
}

$sql = "SELECT * FROM transactions ORDER BY date DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
<title>All Transactions</title>

<style>

body{
font-family:Arial;
background:#0a192f;
color:white;
margin:0;
}

.container{
width:95%;
margin:auto;
padding:20px;
}

/* Title */

h1{
text-align:center;
color:#64ffda;
}

/* Filters */

.filters{
background:#112240;
padding:15px;
border-radius:8px;
margin-bottom:20px;
}

select,input{
padding:8px;
border-radius:5px;
border:none;
margin-right:10px;
}

/* Table */

table{
width:100%;
border-collapse:collapse;
background:#112240;
}

th,td{
padding:10px;
text-align:center;
border-bottom:1px solid #233554;
}

th{
color:#64ffda;
}

tr:hover{
background:#233554;
}

/* Buttons */

button{
padding:6px 10px;
border:none;
border-radius:4px;
cursor:pointer;
}

.edit{
background:#1e90ff;
color:white;
}

.delete{
background:#ff4c4c;
color:white;
}

.export{
margin-top:15px;
background:#1e90ff;
color:white;
padding:10px 20px;
}

/* Pagination */

.pagination{
margin-top:15px;
text-align:center;
}

.pagination button{
margin:3px;
background:#1e90ff;
color:white;
}

</style>

</head>

<body>

<div class="container">

<h1>ALL TRANSACTIONS</h1>

<!-- Filters -->

<div class="filters">

Month:
<select>
<option>March 2024</option>
<option>February 2024</option>
</select>

Type:
<select>
<option>All</option>
<option>Income</option>
<option>Expense</option>
</select>

Category:
<select>
<option>All</option>
<option>Food</option>
<option>Shopping</option>
<option>Transport</option>
<option>Salary</option>
</select>

Search:
<input type="text" placeholder="Search description">

</div>

<!-- Table -->

<table>

<tr>
<th>Date</th>
<th>Category</th>
<th>Description</th>
<th>Amount</th>
<th>Actions</th>
</tr>

<?php

if($result->num_rows>0){

while($row=$result->fetch_assoc()){

echo "<tr>";

echo "<td>".date('d/m',strtotime($row['date']))."</td>";

echo "<td>".$row['category']."</td>";

echo "<td>".$row['description']."</td>";

echo "<td>₹".$row['amount']."</td>";

echo "<td>
<button class='edit' onclick=\"location.href='edit-transaction.php?id=".$row['id']."'\">Edit</button>
<button class='delete' onclick=\"confirmDelete(".$row['id'].")\">Delete</button>
</td>";

echo "</tr>";

}

}

?>

</table>

<!-- Pagination -->

<div class="pagination">

<button>&lt;&lt; Prev</button>
<button>1</button>
<button>2</button>
<button>3</button>
<button>Next &gt;&gt;</button>

</div>

<!-- Export Buttons -->

<div style="text-align:center">

<button class="export" onclick="location.href='../reports/export-data.php?type=csv'">
Export to CSV
</button>

<button class="export" onclick="location.href='../reports/export-data.php?type=pdf'">
Export to PDF
</button>

</div>

</div>

<script>

function confirmDelete(id){

if(confirm("Are you sure you want to delete this transaction?")){

window.location="delete-transaction.php?id="+id;

}

}

</script>

</body>
</html>