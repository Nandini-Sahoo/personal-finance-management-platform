<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Personal Finance Management Platform</title>

<style>

*{
margin:0;
padding:0;
box-sizing:border-box;
font-family:"Poppins",Segoe UI,Arial;
}

body{
background:#0f172a;
color:#e2e8f0;
line-height:1.7;
overflow-x:hidden;
}

/* HERO */

.hero{
display:flex;
align-items:center;
justify-content:space-between;
padding:120px 100px;
background:linear-gradient(135deg,#0f172a,#1e293b,#1e3a5f);
}

.hero-text{
width:50%;
}

.hero-text h1{
font-size:54px;
font-weight:700;
margin-bottom:20px;
color:white;
}

.hero-text p{
font-size:18px;
color:#94a3b8;
margin-bottom:35px;
max-width:520px;
}

.hero img{
width:420px;
border-radius:14px;
box-shadow:0 20px 40px rgba(0,0,0,0.5);
transition:transform 0.4s ease;
}

.hero img:hover{
transform:translateY(-8px);
}

/* BUTTONS */

.hero-buttons a{
padding:14px 28px;
margin-right:12px;
border-radius:8px;
text-decoration:none;
font-weight:600;
transition:0.3s;
}

/* KEEP COLORS */

.start{
background:#22c55e;
color:white;
}

.login{
background:#3b82f6;
color:white;
}

/* FEATURE GRID */

.features{
display:grid;
grid-template-columns:repeat(3,1fr);
gap:30px;
padding:90px 100px;
background:#1e293b;
}

.feature-card{
background:#243b53;
padding:35px;
border-radius:14px;
transition:0.3s;
box-shadow:0 10px 25px rgba(0,0,0,0.35);
}

.feature-card:hover{
transform:translateY(-6px);
background:#2f4b6b;
}

.feature-card h3{
margin-bottom:12px;
color:white;
}

.feature-card p{
color:#94a3b8;
font-size:15px;
}

/* SECTIONS */

.section{
display:flex;
align-items:center;
justify-content:space-between;
padding:110px 100px;
background:#0f172a;
}

.section:nth-child(even){
background:#1e293b;
}

.section-text{
width:50%;
}

.section-text h2{
font-size:38px;
margin-bottom:20px;
color:white;
}

.section-text p{
color:#94a3b8;
font-size:17px;
max-width:520px;
}

.section img{
width:420px;
border-radius:14px;
box-shadow:0 15px 35px rgba(0,0,0,0.4);
}

/* CTA */

.cta{
text-align:center;
padding:120px 20px;
background:linear-gradient(135deg,#1e293b,#334e68);
}

.cta h2{
font-size:40px;
margin-bottom:18px;
color:white;
}

.cta p{
color:#94a3b8;
margin-bottom:35px;
}

.cta a{
background:#22c55e;
padding:16px 36px;
border-radius:10px;
text-decoration:none;
color:white;
font-weight:600;
}

/* FOOTER */

footer{
text-align:center;
padding:35px;
background:#020617;
color:#64748b;
}

</style>

</head>

<body>

<?php include_once "navbar.php"; ?>


<!-- HERO -->

<section class="hero">

<div class="hero-text">

<h1>Take Control of Your Financial Life</h1>

<p>
Track income, monitor expenses and manage budgets with ease.
Build smarter financial habits and stay in control of your money.
</p>

<div class="hero-buttons">
<a href="register.php" class="start">Get Started</a>
<a href="login.php" class="login">Login</a>
</div>

</div>

<img src="https://images.unsplash.com/photo-1554224155-6726b3ff858f">

</section>


<!-- FEATURE GRID -->

<section class="features">

<div class="feature-card">
<h3>Expense Tracking</h3>
<p>Record daily spending and categorize expenses to understand where your money goes.</p>
</div>

<div class="feature-card">
<h3>Income Monitoring</h3>
<p>Track all income sources in one place and analyze financial growth over time.</p>
</div>

<div class="feature-card">
<h3>Smart Budgeting</h3>
<p>Create monthly budgets and stay on track with your financial goals.</p>
</div>

</section>


<!-- SECTION -->

<section class="section">

<img src="https://images.unsplash.com/photo-1454165804606-c3d57bc86b40">

<div class="section-text">

<h2>Track Your Expenses Easily</h2>

<p>
Our platform allows you to log expenses quickly and categorize spending patterns,
helping you identify areas where you can save more money.
</p>

</div>

</section>


<section class="section">

<div class="section-text">

<h2>Monitor Your Income Sources</h2>

<p>
Track salary, freelance income and investments in one dashboard.
Understand how your earnings grow over time.
</p>

</div>

<img src="https://images.unsplash.com/photo-1551288049-bebda4e38f71?auto=format&fit=crop&w=900&q=80">

</section>


<section class="section">

<img src="https://images.unsplash.com/photo-1460925895917-afdab827c52f">

<div class="section-text">

<h2>Create Budgets and Stay in Control</h2>

<p>
Set monthly spending limits and compare real spending vs budget goals.
Stay financially disciplined.
</p>

</div>

</section>


<!-- CTA -->

<section class="cta">

<h2>Start Managing Your Money Today</h2>

<p>Create an account and start tracking your finances in minutes.</p>

<a href="register.php">Create Your Account</a>

</section>


<footer>

<p>© 2026 Personal Finance Management Platform</p>

</footer>

</body>
</html>