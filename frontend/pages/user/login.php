<?php
require_once "../../../backend/config/dbcon.php";
require_once '../../../backend/session.php';

// Start session using the Session class
Session::startSession();

$msg = "";
$conn = getConnection();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST["email"];
    $password = $_POST["password"];
    
    $qry = "SELECT * FROM users WHERE email=?";
    $stmt = $conn->prepare($qry);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $data = $result->fetch_assoc();
        
        if (password_verify($password, $data["password_hash"])) {
            // Use Session class to set user data
            Session::setUser($data["user_id"], $data["name"]);
            
            header("Location: ../dashboard/dashboard.php");
            exit();
        } else {
            $msg = "Invalid Email or Password";
        }
    } else {
        $msg = "Invalid Email or Password";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login | Personal Finance Manager</title>
    <style>
        body{
            margin:0;
            font-family:"Poppins", Arial;
            background:url("https://images.unsplash.com/photo-1554224155-8d04cb21cd6c?auto=format&fit=crop&w=1600&q=80") no-repeat center center/cover;
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
        
        /* page center */
        .page{
            display:flex;
            justify-content:center;
            align-items:center;
            min-height:85vh;
            padding:40px;
        }
        
        /* login card */
        .login-box{
            background:rgba(30,41,59,0.95);
            backdrop-filter:blur(8px);
            padding:45px;
            border-radius:14px;
            width:370px;
            box-shadow:0 15px 40px rgba(0,0,0,0.6);
            color:white;
        }
        
        /* title */
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
            margin-bottom:35px;   /* bigger gap before login button */
        }
        
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
            box-shadow:0 0 0 2px #3b82f6;
        }
        
        /* password eye */
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
            background:#3b82f6;
            border:none;
            border-radius:6px;
            color:white;
            font-weight:bold;
            cursor:pointer;
            transition:0.3s;
        }
        
        button:hover{
            background:#2563eb;
        }
        
        /* error */
        .error{
            background:#ef4444;
            padding:10px;
            text-align:center;
            border-radius:6px;
            margin-bottom:15px;
        }
        
        /* register */
        .register{
            text-align:center;
            margin-top:25px;
        }
        
        .register a{
            color:#22c55e;
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
    <div class="login-box">
        <h2>Welcome Back 💰</h2>
        <p class="subtitle">Login to manage your finances</p>
        
        <?php if ($msg != "") { ?>
            <div class="error">
                <?php echo $msg; ?>
            </div>
        <?php } ?>
        
        <form method="POST">
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" placeholder="Enter your email" required>
            </div>
            
            <div class="form-group password">
                <label>Password</label>
                <div class="password-box">
                    <input type="password" name="password" id="pwd" placeholder="Enter password" required>
                    <span class="eye" onclick="togglePwd()">👁</span>
                </div>
            </div>
            
            <button type="submit">Login</button>
        </form>
        
        <p class="register">
            Don't have an account?
            <a href="register.php">Register</a>
        </p>
        
        <a href="landing.php" class="back">Back to Home</a>
    </div>
</div>

<script>
    function togglePwd(){
        let p = document.getElementById("pwd");
        if(p.type === "password"){
            p.type = "text";
        } else {
            p.type = "password";
        }
    }
</script>

</body>
</html>