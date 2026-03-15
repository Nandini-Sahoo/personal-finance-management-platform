<?php
if(session_status() === PHP_SESSION_NONE){
    session_start();
}
include_once "../add-asset.html";
?>

<style>

/* NAVBAR STYLE */

.navbar-custom{
background:#020617;
padding:16px 0;
box-shadow:0 4px 15px rgba(0,0,0,0.4);
}

/* LOGO */

.navbar-brand{
font-weight:700;
font-size:22px;
color:#38bdf8 !important;
letter-spacing:0.5px;
}

/* NAV LINKS */

.navbar-nav .nav-link{
color:#ffffff !important;
font-weight:500;
margin-left:18px;
position:relative;
transition:0.3s;
}

/* HOVER EFFECT */

.navbar-nav .nav-link::after{
content:"";
position:absolute;
width:0%;
height:2px;
left:0;
bottom:-4px;
background:#38bdf8;
transition:0.3s;
}

.navbar-nav .nav-link:hover{
color:#38bdf8 !important;
}

.navbar-nav .nav-link:hover::after{
width:100%;
}

/* MOBILE BUTTON */

.navbar-toggler{
border:none;
}

</style>


<nav class="navbar navbar-expand-lg navbar-custom">

<div class="container">

<a class="navbar-brand" href="landing.php">
💰 FinanceManager
</a>

<button class="navbar-toggler bg-light" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
<span class="navbar-toggler-icon"></span>
</button>

<div class="collapse navbar-collapse" id="navbarNav">

<ul class="navbar-nav ms-auto align-items-center">

<li class="nav-item">
<a class="nav-link" href="landing.php">
<i class="bi bi-house"></i> Home
</a>
</li>

<li class="nav-item">
<a class="nav-link" href="login.php">
<i class="bi bi-box-arrow-in-right"></i> Login
</a>
</li>

<li class="nav-item">
<a class="nav-link" href="register.php">
Register
</a>
</li>

</ul>

</div>

</div>

</nav>