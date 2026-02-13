<?php
session_start();

class Session {
    
    public static function startSession() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    public static function isLoggedIn() {
        self::startSession();
        return isset($_SESSION['user_id']);
    }
    
    public static function requireLogin() {
        if (!self::isLoggedIn()) {
            header("Location: ../login.php?redirect=" . urlencode($_SERVER['REQUEST_URI']));
            exit();
        }
    }
    
    public static function getUserId() {
        self::startSession();
        return $_SESSION['user_id'] ?? null;
    }
    
    public static function getUserName() {
        self::startSession();
        return $_SESSION['user_name'] ?? null;
    }
    
    public static function setUser($userId, $userName) {
        self::startSession();
        $_SESSION['user_id'] = $userId;
        $_SESSION['user_name'] = $userName;
        $_SESSION['login_time'] = time();
    }
    
    public static function destroy() {
        self::startSession();
        session_unset();
        session_destroy();
    }
}
?>