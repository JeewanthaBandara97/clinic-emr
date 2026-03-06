<?php
/**
 * User Class
 * Handles user authentication and management
 */

require_once __DIR__ . '/Database.php';

class User {
    private $db;
    private $userId;
    private $username;
    private $fullName;
    private $email;
    private $roleId;
    private $roleName;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Authenticate user with username and password
     */
    public function authenticate(string $username, string $password): bool {
        $sql = "SELECT u.*, r.role_name 
                FROM users u 
                JOIN roles r ON u.role_id = r.role_id 
                WHERE u.username = ? AND u.is_active = 1";
        
        $user = $this->db->fetchOne($sql, [$username]);
        
        if ($user && password_verify($password, $user['password_hash'])) {
            $this->userId = $user['user_id'];
            $this->username = $user['username'];
            $this->fullName = $user['full_name'];
            $this->email = $user['email'];
            $this->roleId = $user['role_id'];
            $this->roleName = $user['role_name'];
            
            // Update last login
            $this->updateLastLogin();
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Update last login timestamp
     */
    private function updateLastLogin(): void {
        $sql = "UPDATE users SET last_login = NOW() WHERE user_id = ?";
        $this->db->update($sql, [$this->userId]);
    }
    
    /**
     * Create user session
     */
    public function createSession(): void {
        $_SESSION['user_id'] = $this->userId;
        $_SESSION['username'] = $this->username;
        $_SESSION['full_name'] = $this->fullName;
        $_SESSION['email'] = $this->email;
        $_SESSION['role_id'] = $this->roleId;
        $_SESSION['role_name'] = $this->roleName;
        $_SESSION['logged_in'] = true;
        $_SESSION['login_time'] = time();
        $_SESSION['last_activity'] = time();
        
        // Regenerate session ID for security
        session_regenerate_id(true);
    }
    
    /**
     * Check if user is logged in
     */
    public static function isLoggedIn(): bool {
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            return false;
        }
        
        // Check session timeout
        if (isset($_SESSION['last_activity'])) {
            if (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT) {
                self::logout();
                return false;
            }
            $_SESSION['last_activity'] = time();
        }
        
        return true;
    }
    
    /**
     * Get current user's role ID
     */
    public static function getRoleId(): ?int {
        return $_SESSION['role_id'] ?? null;
    }
    
    /**
     * Get current user's ID
     */
    public static function getUserId(): ?int {
        return $_SESSION['user_id'] ?? null;
    }
    
    /**
     * Get current user's full name
     */
    public static function getFullName(): ?string {
        return $_SESSION['full_name'] ?? null;
    }
    
    /**
     * Check if user has specific role
     */
    public static function hasRole(int $roleId): bool {
        return isset($_SESSION['role_id']) && $_SESSION['role_id'] === $roleId;
    }
    
    /**
     * Check if user is assistant
     */
    public static function isAssistant(): bool {
        return self::hasRole(ROLE_ASSISTANT);
    }
    
    /**
     * Check if user is doctor
     */
    public static function isDoctor(): bool {
        return self::hasRole(ROLE_DOCTOR);
    }
    
    /**
     * Logout user
     */
    public static function logout(): void {
        $_SESSION = array();
        
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        session_destroy();
    }
    
    /**
     * Get all doctors
     */
    public function getAllDoctors(): array {
        $sql = "SELECT user_id, full_name, email, phone 
                FROM users 
                WHERE role_id = ? AND is_active = 1 
                ORDER BY full_name";
        return $this->db->fetchAll($sql, [ROLE_DOCTOR]);
    }
    
    /**
     * Get user by ID
     */
    public function getUserById(int $userId): ?array {
        $sql = "SELECT u.*, r.role_name 
                FROM users u 
                JOIN roles r ON u.role_id = r.role_id 
                WHERE u.user_id = ?";
        return $this->db->fetchOne($sql, [$userId]);
    }
    
    /**
     * Log activity
     */
    public function logActivity(string $action, ?string $tableName = null, ?int $recordId = null, ?string $details = null): void {
        $sql = "INSERT INTO activity_log (user_id, action, table_name, record_id, details, ip_address, user_agent) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $this->db->insert($sql, [
            self::getUserId(),
            $action,
            $tableName,
            $recordId,
            $details,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    }
}