<?php
/**
 * Logout Page
 * Destroys user session and redirects to login page
 */

// Include session management
require_once '../../../backend/session.php';

// Perform logout using Session class
Session::destroy();

// Redirect to login page with success message
header("Location: login.php?logout=success");
exit();