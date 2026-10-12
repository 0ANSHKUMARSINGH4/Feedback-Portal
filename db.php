<?php
/**
 * Core Database & Application Bootstrapper
 * Supports PDO MySQL and fallback PDO SQLite for flexible deployment (e.g. Render).
 */

// 1. Send Defensive Security Headers (Vulnerability #23)
if (!headers_sent()) {
    header("X-Content-Type-Options: nosniff");
    header("X-Frame-Options: SAMEORIGIN");
    header("X-XSS-Protection: 1; mode=block");
    header("Referrer-Policy: strict-origin-when-cross-origin");
}

// 2. Harden Session Security Settings (Vulnerabilities #12, #30)
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_samesite', 'Lax');
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        ini_set('session.cookie_secure', '1');
    }
    session_start();
}

// 3. Session Inactivity Timeout (30 minutes = 1800s) (Vulnerability #30)
$sessionTimeout = 1800;
if (!empty($_SESSION['user_id'])) {
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $sessionTimeout)) {
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
        }
        session_destroy();
        header("Location: login.php?msg=timeout");
        exit();
    }
    $_SESSION['last_activity'] = time();
}

// Load optional local configuration if present
if (file_exists(__DIR__ . '/config.local.php')) {
    require_once __DIR__ . '/config.local.php';
}

class Database {
    private static ?PDO $instance = null;

    public static function getConnection(): PDO {
        if (self::$instance !== null) {
            return self::$instance;
        }

        $dbDriver = getenv('DB_DRIVER') ?: 'mysql'; // 'mysql' or 'sqlite'
        $databaseUrl = getenv('MYSQL_URL') ?: getenv('DATABASE_URL'); // Common on Render / TiDB / Aiven / Railway

        $isCloudHost = false;
        if (!empty($databaseUrl)) {
            $parsed = parse_url($databaseUrl);
            $dbDriver = ($parsed['scheme'] ?? '') === 'postgres' ? 'pgsql' : 'mysql';
            $dbHost = $parsed['host'] ?? 'localhost';
            $dbPort = $parsed['port'] ?? ($dbDriver === 'pgsql' ? 5432 : 3306);
            $dbUser = isset($parsed['user']) ? urldecode($parsed['user']) : 'root';
            $dbPass = isset($parsed['pass']) ? urldecode($parsed['pass']) : '';
            $dbName = ltrim($parsed['path'] ?? 'feedback_system', '/');
            if (isset($parsed['query'])) {
                // If query contains database name or flags
                parse_str($parsed['query'], $queryParams);
                if (!empty($queryParams['database'])) {
                    $dbName = $queryParams['database'];
                }
            }
        } else {
            $dbHost = getenv('DB_HOST') ?: 'localhost';
            $dbPort = getenv('DB_PORT') ?: 3306;
            $dbUser = getenv('DB_USER') ?: 'root';
            $dbPass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';
            $dbName = getenv('DB_NAME') ?: 'feedback_system';
        }

        $isCloudHost = (
            strpos($dbHost, 'tidbcloud.com') !== false ||
            strpos($dbHost, 'aivencloud.com') !== false ||
            strpos($dbHost, 'clever-cloud.com') !== false ||
            strpos($dbHost, 'planetscale.net') !== false ||
            strpos($dbHost, 'render.com') !== false ||
            strpos($dbHost, 'aws') !== false
        );

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        // Cloud MySQL SSL/TLS compatibility (TiDB Cloud, Aiven, PlanetScale require SSL)
        if ($isCloudHost || getenv('DB_SSL') === 'true' || getenv('DB_SSL') === '1' || strpos($databaseUrl ?? '', 'ssl') !== false) {
            $options[PDO::MYSQL_ATTR_SSL_CA] = true;
            $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
        }

        try {
            if ($dbDriver === 'sqlite') {
                $sqlitePath = getenv('SQLITE_PATH') ?: __DIR__ . '/feedback_portal.sqlite';
                self::$instance = new PDO("sqlite:" . $sqlitePath, null, null, $options);
                self::initSqliteTables(self::$instance);
            } else {
                // Connect to MySQL with fallback to SQLite for frictionless local/container boots
                $dsn = "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4";
                try {
                    self::$instance = new PDO($dsn, $dbUser, $dbPass, $options);
                    self::initMysqlTables(self::$instance);
                } catch (PDOException $mysqlEx) {
                    if (getenv('FORCE_MYSQL') === '1') {
                        error_log("Forced MySQL connection failed: " . $mysqlEx->getMessage());
                        die("Database connection failed: " . htmlspecialchars($mysqlEx->getMessage()));
                    }
                    $sqlitePath = __DIR__ . '/feedback_portal.sqlite';
                    self::$instance = new PDO("sqlite:" . $sqlitePath, null, null, $options);
                    self::initSqliteTables(self::$instance);
                }
            }
        } catch (PDOException $e) {
            // Suppress internal system/database paths in user response (Vulnerability #14)
            error_log("Database initialization error: " . $e->getMessage());
            die("A database connection error occurred. Please contact the administrator.");
        }

        return self::$instance;
    }

    private static function initMysqlTables(PDO $pdo): void {
        try {
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS users (
                    user_id VARCHAR(32) NOT NULL PRIMARY KEY,
                    name VARCHAR(100) NOT NULL,
                    email VARCHAR(120) NOT NULL UNIQUE,
                    password VARCHAR(255) NOT NULL,
                    role VARCHAR(20) NOT NULL DEFAULT 'user',
                    profile_image VARCHAR(255) DEFAULT NULL,
                    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

                CREATE TABLE IF NOT EXISTS complete_info (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id VARCHAR(32) NOT NULL UNIQUE,
                    name VARCHAR(100) NOT NULL,
                    branch VARCHAR(50) DEFAULT NULL,
                    gender VARCHAR(20) DEFAULT NULL,
                    dob DATE DEFAULT NULL,
                    phone VARCHAR(20) DEFAULT NULL,
                    college_name VARCHAR(150) DEFAULT NULL,
                    address TEXT DEFAULT NULL,
                    pincode VARCHAR(20) DEFAULT NULL,
                    photo VARCHAR(255) DEFAULT NULL,
                    is_registered TINYINT(1) DEFAULT 1,
                    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    CONSTRAINT fk_complete_info_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

                CREATE TABLE IF NOT EXISTS feedback (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id VARCHAR(32) NOT NULL UNIQUE,
                    department VARCHAR(100) NOT NULL,
                    feedback_message TEXT NOT NULL,
                    q1 INT NOT NULL,
                    q2 INT NOT NULL,
                    q3 INT NOT NULL,
                    q4 INT NOT NULL,
                    q5 INT NOT NULL,
                    submitted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    CONSTRAINT fk_feedback_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

                CREATE TABLE IF NOT EXISTS logs (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id VARCHAR(32) DEFAULT NULL,
                    action VARCHAR(255) NOT NULL,
                    timestamp DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    CONSTRAINT fk_logs_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");

            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'admin'");
            $stmt->execute();
            if ($stmt->fetchColumn() == 0) {
                $adminStmt = $pdo->prepare("
                    INSERT INTO users (user_id, name, email, password, role)
                    VALUES ('USR_ADMIN01', 'System Administrator', 'admin@feedbackportal.local', '$2y$10$55RTOJgQ9eJnfieLEq8GueZwI0dCNLDK1.DZbHmxVjlwV1HF3tS2q', 'admin')
                    ON DUPLICATE KEY UPDATE name=name
                ");
                $adminStmt->execute();
            }
        } catch (Throwable $t) {
            error_log('MySQL auto-init notice: ' . $t->getMessage());
        }
    }

    private static function initSqliteTables(PDO $pdo): void {
        $pdo->exec("PRAGMA foreign_keys = ON;");
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS users (
                user_id TEXT PRIMARY KEY,
                name TEXT NOT NULL,
                email TEXT NOT NULL UNIQUE,
                password TEXT NOT NULL,
                role TEXT NOT NULL DEFAULT 'user',
                profile_image TEXT DEFAULT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            );
            CREATE TABLE IF NOT EXISTS complete_info (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id TEXT NOT NULL UNIQUE,
                name TEXT NOT NULL,
                branch TEXT DEFAULT NULL,
                gender TEXT DEFAULT NULL,
                dob DATE DEFAULT NULL,
                phone TEXT DEFAULT NULL,
                college_name TEXT DEFAULT NULL,
                address TEXT DEFAULT NULL,
                pincode TEXT DEFAULT NULL,
                photo TEXT DEFAULT NULL,
                is_registered INTEGER DEFAULT 1,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
            );
            CREATE TABLE IF NOT EXISTS feedback (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id TEXT NOT NULL UNIQUE,
                department TEXT NOT NULL,
                feedback_message TEXT NOT NULL,
                q1 INTEGER NOT NULL,
                q2 INTEGER NOT NULL,
                q3 INTEGER NOT NULL,
                q4 INTEGER NOT NULL,
                q5 INTEGER NOT NULL,
                submitted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
            );
            CREATE TABLE IF NOT EXISTS logs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id TEXT DEFAULT NULL,
                action TEXT NOT NULL,
                timestamp DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            );
        ");

        // Seed default admin if missing
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'admin'");
        $stmt->execute();
        if ($stmt->fetchColumn() == 0) {
            $adminStmt = $pdo->prepare("
                INSERT INTO users (user_id, name, email, password, role)
                VALUES ('USR_ADMIN01', 'System Administrator', 'admin@feedbackportal.local', '$2y$10$55RTOJgQ9eJnfieLEq8GueZwI0dCNLDK1.DZbHmxVjlwV1HF3tS2q', 'admin')
            ");
            $adminStmt->execute();
        }
    }
}

// Global helper: CSRF tokens (Vulnerability #7)
function generateCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken(?string $token): bool {
    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

// Global helper: Auth verification
function requireAuth(?string $requiredRole = null): void {
    if (empty($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }
    if ($requiredRole !== null && ($_SESSION['role'] ?? '') !== $requiredRole) {
        header("Location: " . (($_SESSION['role'] ?? '') === 'admin' ? 'admin_dashboard.php' : 'user_dashboard.php'));
        exit();
    }
}

// Password strength validator (Vulnerability #8)
function validatePasswordPolicy(string $password): ?string {
    if (strlen($password) < 8) {
        return "Password must be at least 8 characters long.";
    }
    if (!preg_match('/[A-Z]/', $password)) {
        return "Password must contain at least one uppercase letter (A-Z).";
    }
    if (!preg_match('/[a-z]/', $password)) {
        return "Password must contain at least one lowercase letter (a-z).";
    }
    if (!preg_match('/[0-9]/', $password)) {
        return "Password must contain at least one number (0-9).";
    }
    return null;
}

// Rate Limiting on authentication actions (Vulnerability #19)
function isRateLimited(string $action, int $maxAttempts = 5, int $decaySeconds = 300): bool {
    $now = time();
    if (!isset($_SESSION['rate_limits'][$action])) {
        return false;
    }
    $entry = $_SESSION['rate_limits'][$action];
    if ($now - $entry['first_attempt'] > $decaySeconds) {
        unset($_SESSION['rate_limits'][$action]);
        return false;
    }
    return $entry['count'] >= $maxAttempts;
}

function recordFailedAttempt(string $action): void {
    $now = time();
    if (!isset($_SESSION['rate_limits'][$action])) {
        $_SESSION['rate_limits'][$action] = [
            'count' => 1,
            'first_attempt' => $now
        ];
    } else {
        $_SESSION['rate_limits'][$action]['count']++;
    }
}

function clearRateLimit(string $action): void {
    unset($_SESSION['rate_limits'][$action]);
}

// Global database connection instance
$pdo = Database::getConnection();