<?php
session_start();
require_once "../../../backend/config/dbcon.php";

$msg="";

if($_SERVER["REQUEST_METHOD"]=="POST"){

$name=$_POST["name"];
$email=$_POST["email"];
$password=$_POST["password"];
$confirm_password=$_POST["confirm_password"];

if($password!=$confirm_password){
$msg="Passwords do not match";
}else{

$conn=getConnection();
$check="SELECT * FROM users WHERE email=?";
$stmt=$conn->prepare($check);
$stmt->bind_param("s",$email);
$stmt->execute();
$result=$stmt->get_result();

if($result->num_rows>0){

$msg="Email already registered";

}else{

$hashed=password_hash($password,PASSWORD_DEFAULT);

$qry="INSERT INTO users(name,email,password_hash) VALUES(?,?,?)";
$stmt=$conn->prepare($qry);
$stmt->bind_param("sss",$name,$email,$hashed);

if($stmt->execute()){

echo "<script>alert('Registration Successful');window.location='login.php';</script>";

}else{
$msg="Registration Failed";
}

}

}

}
?>

<!DOCTYPE html>
<html>
<head>

<title>Register | Personal Finance Manager</title>

<style>

body{
margin:0;
font-family:"Poppins",Arial;
background:url("https://images.unsplash.com/photo-1554224155-8d04cb21cd6c?auto=format&fit=crop&w=1600&q=80") no-repeat center center/cover;
color:white;
}

/* overlay */

body::before{
content:"";
position:fixed;
top:0;
left:0;
width:100%;
height:100%;
background:rgba(15,23,42,0.85);
z-index:-1;
}

/* center page */

.page{
display:flex;
justify-content:center;
align-items:center;
min-height:85vh;
padding:40px;
}

/* register card */

.register-box{
background:rgba(30,41,59,0.95);
backdrop-filter:blur(8px);
padding:45px;
border-radius:14px;
width:380px;
box-shadow:0 15px 40px rgba(0,0,0,0.6);
color:white;
}

/* titles */

h2{
text-align:center;
margin-bottom:8px;
font-size:30px;
}

.subtitle{
text-align:center;
color:#94a3b8;
margin-bottom:35px;
}

/* form groups */

.form-group{
margin-bottom:20px;
}

.form-group.password{
margin-bottom:35px;   /* bigger gap before button */
}

/* labels */

label{
font-size:14px;
color:#e2e8f0;
}

/* inputs */

input{
width:100%;
padding:13px;
margin-top:6px;
border:none;
border-radius:6px;
outline:none;
background:#e5e7eb;
}

input:focus{
box-shadow:0 0 0 2px #22c55e;
}

/* password icon */

.password-box{
position:relative;
}

.eye{
position:absolute;
right:12px;
top:13px;
cursor:pointer;
}

/* button */

button{
width:100%;
padding:13px;
background:#22c55e;
border:none;
border-radius:6px;
color:white;
font-weight:bold;
cursor:pointer;
transition:0.3s;
}

button:hover{
background:#16a34a;
transform:translateY(-1px);
}

/* error */

.error{
background:#ef4444;
padding:10px;
border-radius:6px;
text-align:center;
margin-bottom:15px;
}

/* login link */

.login{
text-align:center;
margin-top:25px;
}

.login a{
color:#3b82f6;
text-decoration:none;
}

/* back */

.back{
display:block;
text-align:center;
margin-top:15px;
color:#94a3b8;
text-decoration:none;
}

</style>

</head>

<body>

<?php include_once "navbar.php"; ?>

<div class="page">

<div class="register-box">

<h2>Create Account 💰</h2>
<p class="subtitle">Start managing your finances today</p>

<?php if($msg!=""){ ?>

<div class="error">
<?php echo $msg; ?>
</div>

<?php } ?>

<form method="POST">

<div class="form-group">
<label>Full Name</label>
<input type="text" name="name" placeholder="Enter your name" required>
</div>

<div class="form-group">
<label>Email</label>
<input type="email" name="email" placeholder="Enter your email" required>
</div>

<div class="form-group">
<label>Password</label>

<div class="password-box">
<input type="password" name="password" id="pwd" placeholder="Enter password" required>
<span class="eye" onclick="togglePwd()">👁</span>
</div>

</div>

<div class="form-group password">
<label>Confirm Password</label>

<div class="password-box">
<input type="password" name="confirm_password" id="pwd2" placeholder="Confirm password" required>
<span class="eye" onclick="togglePwd2()">👁</span>
</div>

</div>

<button type="submit">Register</button>

</form>

<p class="login">
Already have an account?
<a href="login.php">Login</a>
</p>

<a href="landing.php" class="back">Back to Home</a>

</div>

</div>

<script>

function togglePwd(){
let p=document.getElementById("pwd");
p.type=(p.type==="password")?"text":"password";
}

function togglePwd2(){
let p=document.getElementById("pwd2");
p.type=(p.type==="password")?"text":"password";
}

</script>

</body>
</html>