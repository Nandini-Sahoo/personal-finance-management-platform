<?php 
require_once "../../../backend/config/dbcon.php";
require_once '../../../backend/session.php';

Session::requireLogin();
$userId = Session::getUserId();
$userName = Session::getUserName();

$conn = getConnection();
$message = '';
$error = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        
        // Validate inputs
        if (empty($name) || empty($email)) {
            $error = "Name and email are required fields.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Please enter a valid email address.";
        } else {
            // Check if email already exists for another user
            $checkEmail = "SELECT user_id FROM users WHERE email = ? AND user_id != ?";
            $stmt = $conn->prepare($checkEmail);
            $stmt->bind_param("si", $email, $userId);
            $stmt->execute();
            $emailResult = $stmt->get_result();
            
            if ($emailResult->num_rows > 0) {
                $error = "Email address is already in use by another account.";
            } else {
                // Update profile
                $updateQuery = "UPDATE users SET name = ?, email = ?, phone_no = ? WHERE user_id = ?";
                $stmt = $conn->prepare($updateQuery);
                $stmt->bind_param("sssi", $name, $email, $phone, $userId);
                
                if ($stmt->execute()) {
                    $message = "Profile updated successfully!";
                    // Refresh user data
                    $qry = "SELECT * FROM users WHERE user_id = ?";
                    $stmt = $conn->prepare($qry);
                    $stmt->bind_param("i", $userId);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $data = $result->fetch_assoc();
                } else {
                    $error = "Failed to update profile. Please try again.";
                }
                $stmt->close();
            }
        }
    } elseif (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Get current password hash
        $passQuery = "SELECT password_hash FROM users WHERE user_id = ?";
        $stmt = $conn->prepare($passQuery);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $passResult = $stmt->get_result();
        $userPass = $passResult->fetch_assoc();
        
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $error = "All password fields are required.";
        } elseif (!password_verify($current_password, $userPass['password_hash'])) {
            $error = "Current password is incorrect.";
        } elseif (strlen($new_password) < 6) {
            $error = "New password must be at least 6 characters long.";
        } elseif ($new_password !== $confirm_password) {
            $error = "New password and confirm password do not match.";
        } else {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $updatePass = "UPDATE users SET password_hash = ? WHERE user_id = ?";
            $stmt = $conn->prepare($updatePass);
            $stmt->bind_param("si", $hashed_password, $userId);
            
            if ($stmt->execute()) {
                $message = "Password changed successfully!";
            } else {
                $error = "Failed to change password. Please try again.";
            }
            $stmt->close();
        }
    }
}

// Fetch user data
$qry = "SELECT * FROM users WHERE user_id = ?";
$stmt = $conn->prepare($qry);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();
$stmt->close();
$conn->close();

include_once '../add-asset.html';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>My Profile | Personal Finance Manager</title>
    <style>
        .profile-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .profile-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
            margin-bottom: 2rem;
        }
        
        .profile-header {
            background: linear-gradient(135deg, var(--success-color), var(--primary-color));
            padding: 2rem;
            text-align: center;
            color: white;
        }
        
        .profile-avatar {
            width: 100px;
            height: 100px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 48px;
            color: var(--primary-color);
        }
        
        .profile-header h3 {
            margin: 0;
            font-size: 24px;
        }
        
        .profile-header p {
            margin: 5px 0 0;
            opacity: 0.9;
        }
        
        .profile-body {
            padding: 2rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 0.5rem;
        }
        
        .form-group input {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s;
        }
        
        .form-group input:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
        }
        
        .form-group input:disabled {
            background: #f8f9fa;
            cursor: not-allowed;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--warning-color), var(--danger-color));
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.3);
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        .btn-outline {
            background: transparent;
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
        }
        
        .btn-outline:hover {
            background: var(--primary-color);
            color: white;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .section-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #e9ecef;
        }
        
        .info-row {
            display: flex;
            padding: 0.75rem 0;
            border-bottom: 1px solid #f1f3f5;
        }
        
        .info-label {
            width: 120px;
            font-weight: 600;
            color: var(--dark-color);
        }
        
        .info-value {
            flex: 1;
            color: #6c757d;
        }
        
        .edit-mode .info-row {
            display: none;
        }
        
        .view-mode .edit-fields {
            display: none;
        }
        
        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .password-section {
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid #e9ecef;
        }
        
        @media (max-width: 768px) {
            .profile-container {
                margin: 1rem auto;
            }
            
            .profile-body {
                padding: 1.5rem;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid p-0">
        <div class="row g-0">
            <?php include_once '../sidebar.php'; ?>
            
            <div class="col-lg-10 col-md-9 main-content">
                <div class="profile-container">
                    <!-- Profile Card -->
                    <div class="profile-card">
                        <div class="profile-header">
                            <div class="profile-avatar">
                                <i class="fas fa-user-circle"></i>
                            </div>
                            <h3><?php echo htmlspecialchars($data['name']); ?></h3>
                            <p>Member since <?php echo date('F Y', strtotime($data['created_at'])); ?></p>
                        </div>
                        
                        <div class="profile-body">
                            <!-- Alert Messages -->
                            <?php if ($message): ?>
                                <div class="alert alert-success">
                                    <i class="fas fa-check-circle me-2"></i> <?php echo $message; ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($error): ?>
                                <div class="alert alert-danger">
                                    <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error; ?>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Profile Information - View Mode -->
                            <div id="viewMode">
                                <h5 class="section-title">
                                    <i class="fas fa-info-circle me-2"></i>Profile Information
                                </h5>
                                
                                <div class="info-row">
                                    <div class="info-label">Full Name:</div>
                                    <div class="info-value"><?php echo htmlspecialchars($data['name']); ?></div>
                                </div>
                                <div class="info-row">
                                    <div class="info-label">Email Address:</div>
                                    <div class="info-value"><?php echo htmlspecialchars($data['email']); ?></div>
                                </div>
                                <div class="info-row">
                                    <div class="info-label">Phone Number:</div>
                                    <div class="info-value"><?php echo htmlspecialchars($data['phone_no'] ?? 'Not provided'); ?></div>
                                </div>
                                <div class="info-row">
                                    <div class="info-label">Account ID:</div>
                                    <div class="info-value">#<?php echo $data['user_id']; ?></div>
                                </div>
                                
                                <div class="action-buttons">
                                    <button class="btn btn-primary" onclick="enableEditMode()">
                                        <i class="fas fa-edit me-2"></i> Edit Profile
                                    </button>
                                    <button class="btn btn-outline" onclick="showPasswordModal()">
                                        <i class="fas fa-key me-2"></i> Change Password
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Profile Information - Edit Mode -->
                            <div id="editMode" style="display: none;">
                                <form method="POST" action="">
                                    <h5 class="section-title">
                                        <i class="fas fa-edit me-2"></i>Edit Profile Information
                                    </h5>
                                    
                                    <div class="form-group">
                                        <label>Full Name *</label>
                                        <input type="text" name="name" value="<?php echo htmlspecialchars($data['name']); ?>" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Email Address *</label>
                                        <input type="email" name="email" value="<?php echo htmlspecialchars($data['email']); ?>" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Phone Number</label>
                                        <input type="tel" name="phone" value="<?php echo htmlspecialchars($data['phone_no'] ?? ''); ?>" placeholder="Enter your phone number">
                                    </div>
                                    
                                    <div class="action-buttons">
                                        <button type="submit" name="update_profile" class="btn btn-primary">
                                            <i class="fas fa-save me-2"></i> Save Changes
                                        </button>
                                        <button type="button" class="btn btn-secondary" onclick="disableEditMode()">
                                            <i class="fas fa-times me-2"></i> Cancel
                                        </button>
                                    </div>
                                </form>
                            </div>
                            
                            <!-- Change Password Section (Hidden by default) -->
                            <div id="passwordModal" style="display: none;">
                                <h5 class="section-title mt-4">
                                    <i class="fas fa-key me-2"></i>Change Password
                                </h5>
                                
                                <form method="POST" action="">
                                    <div class="form-group">
                                        <label>Current Password</label>
                                        <input type="password" name="current_password" placeholder="Enter your current password" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>New Password</label>
                                        <input type="password" name="new_password" placeholder="Enter new password (min 6 characters)" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Confirm New Password</label>
                                        <input type="password" name="confirm_password" placeholder="Confirm new password" required>
                                    </div>
                                    
                                    <div class="action-buttons">
                                        <button type="submit" name="change_password" class="btn btn-primary">
                                            <i class="fas fa-key me-2"></i> Change Password
                                        </button>
                                        <button type="button" class="btn btn-secondary" onclick="hidePasswordModal()">
                                            <i class="fas fa-times me-2"></i> Cancel
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function enableEditMode() {
            document.getElementById('viewMode').style.display = 'none';
            document.getElementById('editMode').style.display = 'block';
        }
        
        function disableEditMode() {
            document.getElementById('viewMode').style.display = 'block';
            document.getElementById('editMode').style.display = 'none';
        }
        
        function showPasswordModal() {
            document.getElementById('viewMode').style.display = 'none';
            document.getElementById('passwordModal').style.display = 'block';
        }
        
        function hidePasswordModal() {
            document.getElementById('viewMode').style.display = 'block';
            document.getElementById('passwordModal').style.display = 'none';
        }
        
        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(function() {
                    alert.remove();
                }, 500);
            });
        }, 5000);
    </script>
    
    <?php include_once "../user/footer.php"; ?>
</body>
</html>