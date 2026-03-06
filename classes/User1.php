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
     * Get current user's role name
     */
    public static function getRoleName(): ?string {
        return $_SESSION['role_name'] ?? null;
    }
    
    /**
     * Check if user has specific role
     */
    public static function hasRole(int $roleId): bool {
        return isset($_SESSION['role_id']) && (int)$_SESSION['role_id'] === $roleId;
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
     * Check if user is admin
     */
    public static function isAdmin(): bool {
        return self::hasRole(ROLE_ADMIN);
    }
    
    /**
     * Require admin role — redirects if not admin
     */
    public static function requireAdmin(): void {
        if (!self::isLoggedIn()) {
            $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
            header('Location: ' . APP_URL . '/pages/login.php');
            exit;
        }
        
        if (!self::isAdmin()) {
            $_SESSION['error'] = 'Access denied. Admin privileges required.';
            if (self::isDoctor()) {
                header('Location: ' . APP_URL . '/doctor/index.php');
            } elseif (self::isAssistant()) {
                header('Location: ' . APP_URL . '/assistant/index.php');
            } else {
                header('Location: ' . APP_URL . '/pages/login.php');
            }
            exit;
        }
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
    
    // ================================================================
    //  DOCTOR METHODS
    // ================================================================
    
    /**
     * Get all active doctors (used by session/visit forms)
     */
    public function getAllDoctors(): array {
        $sql = "SELECT user_id, full_name, email, phone 
                FROM users 
                WHERE role_id = ? AND is_active = 1 
                ORDER BY full_name";
        return $this->db->fetchAll($sql, [ROLE_DOCTOR]);
    }
    
    /**
     * Get ALL doctors including inactive (for admin panel)
     */
    public function getAllDoctorsAdmin(): array {
        $sql = "SELECT u.user_id, u.full_name, u.username, u.email, u.phone,
                       u.is_active, u.created_at, u.last_login
                FROM users u 
                WHERE u.role_id = ? 
                ORDER BY u.created_at DESC";
        return $this->db->fetchAll($sql, [ROLE_DOCTOR]);
    }
    
    /**
     * Get doctor count
     */
    public function getDoctorCount(): int {
        $sql = "SELECT COUNT(*) as total FROM users WHERE role_id = ?";
        $result = $this->db->fetchOne($sql, [ROLE_DOCTOR]);
        return $result ? (int)$result['total'] : 0;
    }
    
    /**
     * Get active doctor count
     */
    public function getActiveDoctorCount(): int {
        $sql = "SELECT COUNT(*) as total FROM users WHERE role_id = ? AND is_active = 1";
        $result = $this->db->fetchOne($sql, [ROLE_DOCTOR]);
        return $result ? (int)$result['total'] : 0;
    }
    
    // ================================================================
    //  ASSISTANT METHODS
    // ================================================================
    
    /**
     * Get ALL assistants including inactive (for admin panel)
     */
    public function getAllAssistantsAdmin(): array {
        $sql = "SELECT u.user_id, u.full_name, u.username, u.email, u.phone,
                       u.is_active, u.created_at, u.last_login
                FROM users u 
                WHERE u.role_id = ? 
                ORDER BY u.created_at DESC";
        return $this->db->fetchAll($sql, [ROLE_ASSISTANT]);
    }
    
    /**
     * Get assistant count
     */
    public function getAssistantCount(): int {
        $sql = "SELECT COUNT(*) as total FROM users WHERE role_id = ?";
        $result = $this->db->fetchOne($sql, [ROLE_ASSISTANT]);
        return $result ? (int)$result['total'] : 0;
    }
    
    /**
     * Get active assistant count
     */
    public function getActiveAssistantCount(): int {
        $sql = "SELECT COUNT(*) as total FROM users WHERE role_id = ? AND is_active = 1";
        $result = $this->db->fetchOne($sql, [ROLE_ASSISTANT]);
        return $result ? (int)$result['total'] : 0;
    }
    
    // ================================================================
    //  USER CRUD (admin panel)
    // ================================================================
    
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
     * Get user by ID with role restriction (for admin safety)
     */
    public function getUserByIdAndRole(int $userId, int $roleId): ?array {
        $sql = "SELECT u.user_id, u.full_name, u.username, u.email, u.phone,
                       u.is_active, u.role_id, u.created_at
                FROM users u 
                WHERE u.user_id = ? AND u.role_id = ?";
        return $this->db->fetchOne($sql, [$userId, $roleId]);
    }
    
    /**
     * Check if username already exists
     */
    public function usernameExists(string $username, ?int $excludeUserId = null): bool {
        if ($excludeUserId) {
            $sql = "SELECT user_id FROM users WHERE username = ? AND user_id != ?";
            $result = $this->db->fetchOne($sql, [$username, $excludeUserId]);
        } else {
            $sql = "SELECT user_id FROM users WHERE username = ?";
            $result = $this->db->fetchOne($sql, [$username]);
        }
        return $result !== null && $result !== false;
    }
    
    /**
     * Check if email already exists
     */
    public function emailExists(string $email, ?int $excludeUserId = null): bool {
        if (empty($email)) return false;
        if ($excludeUserId) {
            $sql = "SELECT user_id FROM users WHERE email = ? AND user_id != ?";
            $result = $this->db->fetchOne($sql, [$email, $excludeUserId]);
        } else {
            $sql = "SELECT user_id FROM users WHERE email = ?";
            $result = $this->db->fetchOne($sql, [$email]);
        }
        return $result !== null && $result !== false;
    }
    
    /**
     * Create a new user
     */
    public function createUser(array $data): int {
        $sql = "INSERT INTO users (full_name, username, email, phone, password_hash, role_id, is_active, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
        
        return $this->db->insert($sql, [
            $data['full_name'],
            $data['username'],
            $data['email'] ?? null,
            $data['phone'] ?? null,
            password_hash($data['password'], PASSWORD_DEFAULT),
            $data['role_id'],
            $data['is_active'] ?? 1
        ]);
    }
    
    /**
     * Update user without password change
     */
    public function updateUser(int $userId, array $data): bool {
        $sql = "UPDATE users 
                SET full_name = ?, username = ?, email = ?, phone = ?, is_active = ?
                WHERE user_id = ?";
        
        return $this->db->update($sql, [
            $data['full_name'],
            $data['username'],
            $data['email'] ?? null,
            $data['phone'] ?? null,
            $data['is_active'] ?? 1,
            $userId
        ]);
    }
    
    /**
     * Update user with password change
     */
    public function updateUserWithPassword(int $userId, array $data): bool {
        $sql = "UPDATE users 
                SET full_name = ?, username = ?, email = ?, phone = ?, 
                    password_hash = ?, is_active = ?
                WHERE user_id = ?";
        
        return $this->db->update($sql, [
            $data['full_name'],
            $data['username'],
            $data['email'] ?? null,
            $data['phone'] ?? null,
            password_hash($data['password'], PASSWORD_DEFAULT),
            $data['is_active'] ?? 1,
            $userId
        ]);
    }
    
    /**
     * Toggle user active status (prevents deactivating admins)
     */
    public function updateUserStatus(int $userId, int $status): bool {
        $sql = "UPDATE users SET is_active = ? WHERE user_id = ? AND role_id != ?";
        return $this->db->update($sql, [$status, $userId, ROLE_ADMIN]);
    }
    
    // ================================================================
    //  DASHBOARD STATISTICS (using correct table names)
    // ================================================================
    
    /**
     * Get recent users (for admin dashboard)
     */
    public function getRecentUsers(int $limit = 10): array {
        $sql = "SELECT u.user_id, u.full_name, u.username, u.email, 
                       u.is_active, u.created_at, u.last_login, r.role_name
                FROM users u
                JOIN roles r ON u.role_id = r.role_id
                ORDER BY u.created_at DESC
                LIMIT ?";
        return $this->db->fetchAll($sql, [$limit]);
    }
    
    /**
     * Get today's session count
     * ★ Uses clinic_sessions (NOT 'sessions')
     */
    public function getTodaySessionCount(): int {
        try {
            $sql = "SELECT COUNT(*) as total FROM clinic_sessions WHERE session_date = CURDATE()";
            $result = $this->db->fetchOne($sql);
            return $result ? (int)$result['total'] : 0;
        } catch (\Exception $e) {
            return 0;
        }
    }
    
    /**
     * Get today's patient count
     */
    public function getTodayPatientCount(): int {
        try {
            $sql = "SELECT COUNT(*) as total FROM patients WHERE DATE(created_at) = CURDATE()";
            $result = $this->db->fetchOne($sql);
            return $result ? (int)$result['total'] : 0;
        } catch (\Exception $e) {
            return 0;
        }
    }
    
    /**
     * Get total patient count
     */
    public function getTotalPatientCount(): int {
        try {
            $sql = "SELECT COUNT(*) as total FROM patients";
            $result = $this->db->fetchOne($sql);
            return $result ? (int)$result['total'] : 0;
        } catch (\Exception $e) {
            return 0;
        }
    }
    
    /**
     * Get today's visit count
     */
    public function getTodayVisitCount(): int {
        try {
            $sql = "SELECT COUNT(*) as total FROM visits WHERE visit_date = CURDATE()";
            $result = $this->db->fetchOne($sql);
            return $result ? (int)$result['total'] : 0;
        } catch (\Exception $e) {
            return 0;
        }
    }
    
    /**
     * Get today's sessions with doctor info (for admin dashboard)
     * ★ Uses clinic_sessions (NOT 'sessions')
     */
    public function getTodaySessionsAdmin(): array {
        try {
            $sql = "SELECT cs.*, u.full_name AS doctor_name,
                           (SELECT COUNT(*) FROM session_patients sp WHERE sp.session_id = cs.session_id) AS total_patients,
                           (SELECT COUNT(*) FROM session_patients sp WHERE sp.session_id = cs.session_id AND sp.status = 'Waiting') AS waiting_count
                    FROM clinic_sessions cs
                    JOIN users u ON cs.doctor_id = u.user_id
                    WHERE cs.session_date = CURDATE()
                    ORDER BY cs.start_time ASC";
            return $this->db->fetchAll($sql);
        } catch (\Exception $e) {
            return [];
        }
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