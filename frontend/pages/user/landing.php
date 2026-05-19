<?php
// Start session at the very beginning, before ANY output
require_once '../../../backend/session.php';
Session::startSession();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Personal Finance Management Platform</title>
<link rel="stylesheet" href="../../assets/css/user/landing.css">
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

<!-- SLIDER -->

<div class="hero-slider">

<img src="../../assets/image/home-img1.avif" class="slide active" alt="home page img 1">

<img src="../../assets/image/home-img2.avif" class="slide" alt="home page img 1">

<img src="../../assets/image/home-img3.avif" class="slide" alt="home page img 1">

</div>

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

<img src="../../assets//image/home-img2.avif"  alt="home page img">

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

<img src="../../assets/image/home-img3.avif"  alt="home page img">

</section>

<section class="section">

<img src="../../assets/image/home-img4.avif"  alt="home page img">

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

<?php include 'footer.php'; ?>

<!-- SLIDER SCRIPT -->

<script>

const slides = document.querySelectorAll(".slide");

let currentSlide = 0;

setInterval(() => {

slides[currentSlide].classList.remove("active");

currentSlide = (currentSlide + 1) % slides.length;

slides[currentSlide].classList.add("active");

}, 3000);

</script>

</body>
</html>