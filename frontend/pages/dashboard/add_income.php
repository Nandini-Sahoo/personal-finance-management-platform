<?php
session_start();
?>

<!DOCTYPE html>
<html>
<head>
<title>Add Income</title>

<style>

body{
    font-family: Arial;
    background:#eef4ff;
    margin:0;
}

/* Form Container */

.container{
    width:420px;
    margin:80px auto;
    background:white;
    padding:30px;
    border-radius:10px;
    box-shadow:0 5px 15px rgba(0,0,0,0.1);
}

h2{
    text-align:center;
    color:#1565c0;
}

/* Labels */

label{
    font-weight:bold;
    color:#0d47a1;
}

/* Inputs */

input,select,textarea{
    width:100%;
    padding:10px;
    margin-top:5px;
    margin-bottom:15px;
    border:1px solid #ccc;
    border-radius:6px;
}

/* Buttons */

.buttons{
    display:flex;
    justify-content:space-between;
}

button{
    padding:10px 20px;
    border:none;
    border-radius:6px;
    cursor:pointer;
    font-weight:bold;
}

.add-btn{
    background:#1565c0;
    color:white;
}

.add-btn:hover{
    background:#0d47a1;
}

.cancel-btn{
    background:#90caf9;
}

.cancel-btn:hover{
    background:#64b5f6;
}

/* Message */

#message{
    margin-top:15px;
    padding:10px;
    border-radius:6px;
    display:none;
}

.success{
    background:#e3f2fd;
    color:#0d47a1;
}

</style>
</head>

<body>

<div class="container">

<h2>ADD INCOME</h2>

<form id="incomeForm">

<label>Category</label>
<select name="category" required>
<option value="Salary">Salary</option>
<option value="Freelance">Freelance</option>
<option value="Business">Business</option>
<option value="Investment">Investment</option>
</select>

<label>Amount</label>
<input type="number" name="amount" placeholder="₹ Enter amount" required>

<label>Date</label>
<input type="date" name="date" required>

<label>Description</label>
<textarea name="description" placeholder="Monthly salary"></textarea>

<div class="buttons">

<button type="submit" class="add-btn">ADD INCOME</button>

<button type="button" class="cancel-btn" onclick="window.location='../dashboard.php'">
CANCEL
</button>

</div>

</form>

<div id="message"></div>

</div>

<script>

/* AJAX Submit */

document.getElementById("incomeForm").addEventListener("submit",function(e){

e.preventDefault();

let formData = new FormData(this);

fetch("income-process.php",{
method:"POST",
body:formData
})
.then(res => res.text())
.then(data => {

let msg=document.getElementById("message");

msg.style.display="block";
msg.className="success";
msg.innerHTML=data;

document.getElementById("incomeForm").reset();

});

});

</script>

</body>
</html>