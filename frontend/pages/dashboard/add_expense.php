<?php
session_start();
require_once "../../../backend/config/dbcon.php";
require_once '../../../backend/session.php';

// Check if user is logged in
Session::requireLogin();
$userId = Session::getUserId();

// Fetch expense categories from database
$conn = getConnection();
$categoryQuery = "SELECT category_id, category_name FROM categories WHERE category_type = 'expense' ORDER BY category_name";
$categoryResult = $conn->query($categoryQuery);
$categories = [];
while ($row = $categoryResult->fetch_assoc()) {
    $categories[] = $row;
}
$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
<title>Add Expense</title>

<style>
body{
    font-family: Arial;
    background:#eef4ff;
    margin:0;
}

/* Container */
.container{
    width:420px;
    margin:80px auto;
    background:white;
    padding:30px;
    border-radius:10px;
    box-shadow:0 5px 15px rgba(0,0,0,0.1);
}

/* Title */
h2{
    text-align:center;
    color:#1565c0;
}

/* Form */
label{
    font-weight:bold;
    color:#0d47a1;
}

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

/* Success Message */
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

.error{
    background:#fde2e2;
    color:#c62828;
}
</style>
</head>

<body>

<div class="container">

<h2>ADD EXPENSE</h2>

<form id="expenseForm">
    <label>Category</label>
    <select name="category" required>
        <option value="">-- Select Category --</option>
        <?php foreach ($categories as $category): ?>
            <option value="<?php echo htmlspecialchars($category['category_name']); ?>">
                <?php echo htmlspecialchars($category['category_name']); ?>
            </option>
        <?php endforeach; ?>
    </select>

    <label>Amount</label>
    <input type="number" name="amount" step="0.01" placeholder="₹ Enter amount" required>

    <label>Date</label>
    <input type="date" name="date" required>

    <label>Description</label>
    <textarea name="description" placeholder="Lunch at restaurant"></textarea>

    <label>Payment Method</label>
    <select name="payment">
        <option value="Cash">Cash</option>
        <option value="UPI">UPI</option>
        <option value="Credit Card">Credit Card</option>
        <option value="Debit Card">Debit Card</option>
        <option value="Others">Others</option>
    </select>

    <div class="buttons">
        <button type="submit" class="add-btn">ADD EXPENSE</button>
        <button type="button" class="cancel-btn" onclick="window.location='./dashboard.php'">
            CANCEL
        </button>
    </div>
</form>

<div id="message"></div>

</div>

<script>
/* AJAX FORM SUBMIT */
document.getElementById("expenseForm").addEventListener("submit", function(e) {
    e.preventDefault();
    
    let formData = new FormData(this);
    
    fetch("transaction-process.php", {
        method: "POST",
        body: formData
    })
    .then(res => res.text())
    .then(data => {
        let msg = document.getElementById("message");
        msg.style.display = "block";
        
        if (data.includes("✅") || data.includes("successfully")) {
            msg.className = "success";
            document.getElementById("expenseForm").reset();
        } else {
            msg.className = "error";
        }
        
        msg.innerHTML = data;
        
        // Auto-hide message after 5 seconds
        setTimeout(() => {
            msg.style.display = "none";
        }, 5000);
    })
    .catch(error => {
        let msg = document.getElementById("message");
        msg.style.display = "block";
        msg.className = "error";
        msg.innerHTML = "❌ Error submitting form. Please try again.";
    });
});
</script>

</body>
</html>