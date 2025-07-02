<?php
// Enable error display for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// SECURITY: This code is FULLY SECURED against SQL injection and XSS attacks
// Uses prepared statements, input validation, output sanitization, and CSRF protection

// Database configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "secure_test_db";

$conn = null;
$setup_message = "";

// Security function to generate CSRF token
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Security function to validate CSRF token
function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Security function to sanitize output (XSS protection)
function sanitizeOutput($data) {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

// Security function to validate and sanitize input
function validateInput($input, $type = 'string', $maxLength = 255) {
    $input = trim($input);
    
    switch ($type) {
        case 'username':
            // Only allow alphanumeric characters and underscores
            $input = preg_replace('/[^a-zA-Z0-9_]/', '', $input);
            $input = substr($input, 0, 50);
            break;
        case 'email':
            $input = filter_var($input, FILTER_SANITIZE_EMAIL);
            $input = filter_var($input, FILTER_VALIDATE_EMAIL) ? $input : '';
            break;
        case 'string':
        default:
            $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
            $input = substr($input, 0, $maxLength);
            break;
    }
    
    return $input;
}

// Security function to generate CAPTCHA
function generateCaptcha() {
    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $captcha = '';
    for ($i = 0; $i < 6; $i++) {
        $captcha .= $characters[rand(0, strlen($characters) - 1)];
    }
    $_SESSION['captcha'] = $captcha;
    return $captcha;
}

// Security function to create CAPTCHA as SVG (no GD extension needed)
function createCaptchaSVG($text) {
    $width = 200;
    $height = 80;
    
    // Start SVG
    $svg = '<svg width="' . $width . '" height="' . $height . '" xmlns="http://www.w3.org/2000/svg">';
    
    // Background
    $svg .= '<rect width="100%" height="100%" fill="#f8f9fa" stroke="#dee2e6" stroke-width="2"/>';
    
    // Add noise lines
    for ($i = 0; $i < 8; $i++) {
        $x1 = rand(0, $width);
        $y1 = rand(0, $height);
        $x2 = rand(0, $width);
        $y2 = rand(0, $height);
        $color = sprintf("#%02x%02x%02x", rand(150, 200), rand(150, 200), rand(150, 200));
        $svg .= '<line x1="' . $x1 . '" y1="' . $y1 . '" x2="' . $x2 . '" y2="' . $y2 . '" stroke="' . $color . '" stroke-width="1"/>';
    }
    
    // Add noise circles
    for ($i = 0; $i < 15; $i++) {
        $cx = rand(0, $width);
        $cy = rand(0, $height);
        $r = rand(1, 3);
        $color = sprintf("#%02x%02x%02x", rand(100, 180), rand(100, 180), rand(100, 180));
        $svg .= '<circle cx="' . $cx . '" cy="' . $cy . '" r="' . $r . '" fill="' . $color . '"/>';
    }
    
    // Add text characters
    for ($i = 0; $i < strlen($text); $i++) {
        $x = 20 + ($i * 25) + rand(-5, 5);
        $y = 45 + rand(-8, 8);
        $rotation = rand(-15, 15);
        $color = sprintf("#%02x%02x%02x", rand(0, 100), rand(0, 100), rand(0, 100));
        $fontSize = rand(24, 28);
        
        $svg .= '<text x="' . $x . '" y="' . $y . '" font-family="Arial, sans-serif" font-size="' . $fontSize . '" font-weight="bold" fill="' . $color . '" transform="rotate(' . $rotation . ' ' . $x . ' ' . $y . ')">' . $text[$i] . '</text>';
    }
    
    $svg .= '</svg>';
    
    return $svg;
}

// Security function to get CAPTCHA display
function getCaptchaDisplay() {
    if (!isset($_SESSION['captcha'])) {
        generateCaptcha();
    }
    return createCaptchaSVG($_SESSION['captcha']);
}

// Security function to validate CAPTCHA
function validateCaptcha($input) {
    return isset($_SESSION['captcha']) && strtoupper($input) === $_SESSION['captcha'];
}

// Function for database setup
if (isset($_GET['setup'])) {
    session_start();
    
    // CSRF protection for setup
    if (!isset($_GET['csrf']) || !validateCSRFToken($_GET['csrf'])) {
        die("CSRF token validation failed. Access denied.");
    }
    
    try {
        // Connect to MySQL without specifying database
        $conn_setup = new mysqli($servername, $username, $password);
        
        if ($conn_setup->connect_error) {
            throw new Exception("Connection failed: " . $conn_setup->connect_error);
        }
        
        // Create database
        $sql_create_db = "CREATE DATABASE IF NOT EXISTS secure_test_db";
        if ($conn_setup->query($sql_create_db) === TRUE) {
            $setup_message .= "Database 'secure_test_db' created successfully.<br>";
        }
        
        // Select database
        $conn_setup->select_db($dbname);
        
        // Create table with additional security fields
        $sql_create_table = "CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            email VARCHAR(100),
            role VARCHAR(20) DEFAULT 'user',
            profile_note TEXT,
            failed_login_attempts INT DEFAULT 0,
            account_locked_until DATETIME NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            last_login TIMESTAMP NULL,
            INDEX idx_username (username),
            INDEX idx_email (email)
        )";
        
        if ($conn_setup->query($sql_create_table) === TRUE) {
            $setup_message .= "Table 'users' created successfully.<br>";
        }
        
        // Delete existing users and insert new ones
        $conn_setup->query("DELETE FROM users");
        
        // Insert test users with hashed passwords
        $users = [
            ['admin', 'admin123', 'admin@test.com', 'admin', 'System Administrator Profile'],
            ['user1', 'password1', 'user1@test.com', 'user', 'Regular User Profile'],
            ['test', 'test123', 'test@test.com', 'user', 'Test Account Profile'],
            ['guest', 'guest', 'guest@test.com', 'guest', 'Guest Account Profile'],
            ['john', 'john2024', 'john@company.com', 'manager', 'Manager Profile']
        ];
        
        // SECURE: Use prepared statements for inserting users
        $stmt = $conn_setup->prepare("INSERT INTO users (username, password_hash, email, role, profile_note) VALUES (?, ?, ?, ?, ?)");
        
        foreach ($users as $user) {
            $password_hash = password_hash($user[1], PASSWORD_DEFAULT);
            $stmt->bind_param("sssss", $user[0], $password_hash, $user[2], $user[3], $user[4]);
            if ($stmt->execute()) {
                $setup_message .= "User '{$user[0]}' added successfully.<br>";
            }
        }
        
        $stmt->close();
        $conn_setup->close();
        
    } catch (Exception $e) {
        $setup_message = "Error during setup: " . sanitizeOutput($e->getMessage());
    }
}

// Connect to database for normal operations
$conn = null;
$db_error = null;

try {
    $conn = new mysqli($servername, $username, $password, $dbname);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    // Set charset to UTF-8 for security
    $conn->set_charset("utf8");
    
} catch (Exception $e) {
    $db_error = $e->getMessage();
}

// Handle logout
if (isset($_GET['logout'])) {
    session_start();
    session_destroy();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Start session to maintain login state
session_start();

// Handle CAPTCHA SVG generation
if (isset($_GET['captcha'])) {
    if (!isset($_SESSION['captcha'])) {
        generateCaptcha();
    }
    header('Content-Type: image/svg+xml');
    echo createCaptchaSVG($_SESSION['captcha']);
    exit();
}

// Regenerate session ID for security
if (!isset($_SESSION['session_started'])) {
    session_regenerate_id(true);
    $_SESSION['session_started'] = true;
}

// Generate initial CAPTCHA
if (!isset($_SESSION['captcha'])) {
    generateCaptcha();
}

// SECURE: Process login form with prepared statements and password verification
$login_result = "";
$sql_error = "";
$rate_limit_error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['username']) && isset($_POST['password'])) {
    
    // CSRF protection
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $login_result = "error";
        $sql_error = "CSRF token validation failed. Please try again.";
    } 
    // CAPTCHA validation
    elseif (!isset($_POST['captcha']) || !validateCaptcha($_POST['captcha'])) {
        $login_result = "error";
        $sql_error = "Invalid CAPTCHA. Please try again.";
        // Generate new CAPTCHA for next attempt
        generateCaptcha();
    }
    elseif ($conn === null) {
        $login_result = "error";
        $sql_error = "Database connection is not available. " . ($db_error ? sanitizeOutput($db_error) : "Unknown error");
    } else {
        // Validate and sanitize input
        $user = validateInput($_POST['username'], 'username');
        $pass = $_POST['password']; // Don't sanitize password, just validate length
        
        if (strlen($user) < 3 || strlen($user) > 50) {
            $login_result = "error";
            $sql_error = "Username must be between 3 and 50 characters.";
        } elseif (strlen($pass) < 3 || strlen($pass) > 255) {
            $login_result = "error";
            $sql_error = "Password length is invalid.";
        } else {
            // SECURE: Use prepared statements to prevent SQL injection
            $stmt = $conn->prepare("SELECT id, username, password_hash, email, role, profile_note, failed_login_attempts, account_locked_until FROM users WHERE username = ?");
            
            if (!$stmt) {
                $login_result = "error";
                $sql_error = "Database error occurred.";
            } else {
                $stmt->bind_param("s", $user);
                
                try {
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    if ($result && $result->num_rows > 0) {
                        $row = $result->fetch_assoc();
                        
                        // Check if account is locked
                        if ($row['account_locked_until'] && new DateTime() < new DateTime($row['account_locked_until'])) {
                            $login_result = "locked";
                            $sql_error = "Account is temporarily locked due to multiple failed login attempts.";
                        } elseif (password_verify($pass, $row['password_hash'])) {
                            // Successful login
                            $login_result = "success";
                            $user_data = $row;
                            
                            // Reset failed login attempts
                            $reset_stmt = $conn->prepare("UPDATE users SET failed_login_attempts = 0, account_locked_until = NULL, last_login = NOW() WHERE id = ?");
                            $reset_stmt->bind_param("i", $row['id']);
                            $reset_stmt->execute();
                            $reset_stmt->close();
                            
                            // Save user data in session
                            $_SESSION['logged_in'] = true;
                            $_SESSION['user_id'] = $row['id'];
                            $_SESSION['username'] = $row['username'];
                            $_SESSION['email'] = $row['email'];
                            $_SESSION['role'] = $row['role'];
                            $_SESSION['profile_note'] = $row['profile_note'];
                            $_SESSION['login_time'] = date('Y-m-d H:i:s');
                            
                            // Clear CAPTCHA after successful login
                            unset($_SESSION['captcha']);
                            
                            // Regenerate session ID after successful login
                            session_regenerate_id(true);
                            
                        } else {
                            // Failed login - increment failed attempts
                            $failed_attempts = $row['failed_login_attempts'] + 1;
                            $lock_until = null;
                            
                            // Lock account after 5 failed attempts for 15 minutes
                            if ($failed_attempts >= 5) {
                                $lock_until = date('Y-m-d H:i:s', strtotime('+15 minutes'));
                            }
                            
                            $update_stmt = $conn->prepare("UPDATE users SET failed_login_attempts = ?, account_locked_until = ? WHERE id = ?");
                            $update_stmt->bind_param("isi", $failed_attempts, $lock_until, $row['id']);
                            $update_stmt->execute();
                            $update_stmt->close();
                            
                            $login_result = "failed";
                            // Generate new CAPTCHA for next attempt
                            generateCaptcha();
                        }
                    } else {
                        $login_result = "failed";
                        // Generate new CAPTCHA for next attempt
                        generateCaptcha();
                    }
                    
                    $stmt->close();
                    
                } catch (Exception $e) {
                    $login_result = "error";
                    $sql_error = "An error occurred during login.";
                    error_log("Login error: " . $e->getMessage());
                    // Generate new CAPTCHA for next attempt
                    generateCaptcha();
                }
            }
        }
    }
}

// SECURE: Handle profile update with XSS protection
$profile_update_message = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile']) && isset($_SESSION['logged_in'])) {
    
    // CSRF protection
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $profile_update_message = "CSRF token validation failed.";
    } elseif ($conn === null) {
        $profile_update_message = "Database connection error.";
    } else {
        // SECURE: Validate and sanitize input to prevent XSS
        $new_note = validateInput($_POST['profile_note'], 'string', 1000);
        
        if (strlen($new_note) > 1000) {
            $profile_update_message = "Profile note is too long. Maximum 1000 characters allowed.";
        } else {
            // SECURE: Use prepared statement
            $stmt = $conn->prepare("UPDATE users SET profile_note = ? WHERE id = ?");
            if (!$stmt) {
                $profile_update_message = "Database error occurred.";
            } else {
                $stmt->bind_param("si", $new_note, $_SESSION['user_id']);
                
                if ($stmt->execute()) {
                    $_SESSION['profile_note'] = $new_note; // Store sanitized version
                    $profile_update_message = "Profile updated successfully!";
                } else {
                    $profile_update_message = "Error updating profile.";
                }
                $stmt->close();
            }
        }
    }
}

if ($conn && !$conn->connect_error) {
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Secure login system with protection against SQL injection and XSS attacks">
    <title>Secure Login System - Protected Against SQL Injection and XSS</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background-color: #f5f5f5;
            line-height: 1.6;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="text"], input[type="password"], textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            box-sizing: border-box;
        }
        textarea {
            height: 100px;
            resize: vertical;
        }
        input[type="submit"], .btn {
            background: #28a745;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            text-decoration: none;
            display: inline-block;
            margin-right: 10px;
        }
        input[type="submit"]:hover, .btn:hover {
            background: #218838;
        }
        .warning {
            background: #d1ecf1;
            color: #0c5460;
            padding: 15px;
            border: 1px solid #bee5eb;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .success {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border: 1px solid #c3e6cb;
            border-radius: 4px;
            margin: 10px 0;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border: 1px solid #f5c6cb;
            border-radius: 4px;
            margin: 10px 0;
        }
        .info {
            background: #d1ecf1;
            color: #0c5460;
            padding: 15px;
            border: 1px solid #bee5eb;
            border-radius: 4px;
            margin: 10px 0;
        }
        .users-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        .users-table th, .users-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        .btn-logout {
            background: #dc3545;
            margin-left: 10px;
        }
        .btn-logout:hover {
            background: #c82333;
        }
        .user-session {
            background: #d1ecf1;
            color: #0c5460;
            padding: 15px;
            border: 1px solid #bee5eb;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .session-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }
        .session-details {
            flex: 1;
        }
        .users-table th {
            background-color: #f2f2f2;
        }
        .secure-badge {
            background: #28a745;
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8em;
            margin-left: 10px;
        }
        .profile-display {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 15px;
            margin: 10px 0;
            word-wrap: break-word;
        }
        .security-features {
            background: #e7f5e7;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .security-features h4 {
            color: #155724;
            margin-top: 0;
        }
        .feature-list {
            list-style-type: none;
            padding-left: 0;
        }
        .feature-list li {
            padding: 5px 0;
            border-bottom: 1px solid #c3e6cb;
        }
        .feature-list li:before {
            content: "✅ ";
            color: #28a745;
            font-weight: bold;
        }
        .captcha-container {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
        }
        .captcha-display {
            border: 2px solid #dee2e6;
            border-radius: 4px;
            cursor: pointer;
            transition: border-color 0.3s;
            background: #f8f9fa;
            padding: 5px;
            min-width: 200px;
            min-height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .captcha-display:hover {
            border-color: #28a745;
        }
        .captcha-refresh {
            background: #6c757d;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }
        .captcha-refresh:hover {
            background: #5a6268;
        }
        .captcha-input {
            width: 120px !important;
            text-align: center;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 16px;
            letter-spacing: 2px;
        }
        @media (max-width: 600px) {
            .session-info {
                flex-direction: column;
                align-items: flex-start;
            }
            .session-actions {
                margin-top: 10px;
            }
        }
    </style>
</head>
<body>
    <!-- User Session Status -->
    <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']): ?>
    <div class="container">
        <div class="user-session">
            <div class="session-info">
                <div class="session-details">
                    <strong>🔐 Active Session:</strong><br>
                    <strong>User:</strong> <?php echo sanitizeOutput($_SESSION['username']); ?> 
                    (<?php echo sanitizeOutput($_SESSION['role']); ?>)<br>
                    <strong>Email:</strong> <?php echo sanitizeOutput($_SESSION['email']); ?><br>
                    <strong>Login time:</strong> <?php echo sanitizeOutput($_SESSION['login_time']); ?><br>
                    <strong>Session ID:</strong> <?php echo sanitizeOutput(substr(session_id(), 0, 10)) . '...'; ?>
                </div>
                <div class="session-actions">
                    <a href="?logout=1" class="btn btn-logout">🚪 Logout</a>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="warning">
        <strong>🛡️ SECURITY:</strong> This form is <span class="secure-badge">FULLY SECURED</span> against SQL injection and XSS attacks using prepared statements, input validation, output sanitization, and CSRF protection!
    </div>

    <!-- Database Status -->
    <div class="container">
        <h3>📊 Database Status</h3>
        <?php if ($conn !== null || !$db_error): ?>
            <div class="success">
                <strong>✅ Database Connection:</strong> SUCCESS<br>
                <strong>Server:</strong> <?php echo sanitizeOutput($servername); ?><br>
                <strong>Database:</strong> <?php echo sanitizeOutput($dbname); ?>
            </div>
        <?php else: ?>
            <div class="error">
                <strong>❌ Database Connection:</strong> FAILED<br>
                <?php if ($db_error): ?>
                    <strong>Error:</strong> <?php echo sanitizeOutput($db_error); ?><br>
                <?php endif; ?>
                <small>Make sure MySQL is running and run the setup first.</small>
            </div>
        <?php endif; ?>
    </div>

    <div class="container">
        <h3>🔧 Database Setup</h3>
        <?php if ($setup_message): ?>
            <div class="success">
                <strong>Setup Status:</strong><br>
                <?php echo $setup_message; ?>
            </div>
        <?php endif; ?>
        
        <a href="?setup=1&csrf=<?php echo urlencode(generateCSRFToken()); ?>" class="btn">Create/Reset Database</a>
        
        <h4>Test Users:</h4>
        <table class="users-table">
            <tr>
                <th>Username</th>
                <th>Password</th>
                <th>Role</th>
            </tr>
            <tr><td>admin</td><td>admin123</td><td>admin</td></tr>
            <tr><td>user1</td><td>password1</td><td>user</td></tr>
            <tr><td>test</td><td>test123</td><td>user</td></tr>
            <tr><td>guest</td><td>guest</td><td>guest</td></tr>
            <tr><td>john</td><td>john2024</td><td>manager</td></tr>
        </table>
    </div>

    <!-- Login Form -->
    <?php if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']): ?>
    <div class="container">
        <h2>🔐 Secure Login Form <span class="secure-badge">PROTECTED</span></h2>
        
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo sanitizeOutput(generateCSRFToken()); ?>">
            
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" required maxlength="50" pattern="[a-zA-Z0-9_]+" title="Only letters, numbers, and underscores allowed">
            </div>
            
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required maxlength="255">
            </div>
            
            <div class="form-group">
                <label for="captcha">Security Code:</label>
                <div class="captcha-container">
                    <div class="captcha-display" id="captcha-display" onclick="refreshCaptcha()">
                        <img src="?captcha=1" alt="CAPTCHA" style="width: 200px; height: 80px;" id="captcha-img">
                    </div>
                    <input type="text" id="captcha" name="captcha" class="captcha-input" required maxlength="6" placeholder="Enter code" autocomplete="off">
                    <button type="button" class="captcha-refresh" onclick="refreshCaptcha()" title="Refresh CAPTCHA">🔄</button>
                </div>
                <small>Click the image or refresh button to get a new code</small>
            </div>
            
            <input type="submit" value="Secure Login">
        </form>

        <!-- Results -->
        <?php if ($login_result == "success"): ?>
            <div class="success">
                <h3>✅ Login Successful!</h3>
                <strong>Welcome, <?php echo sanitizeOutput($user_data['username']); ?>!</strong><br>
                <strong>Email:</strong> <?php echo sanitizeOutput($user_data['email']); ?><br>
                <strong>Role:</strong> <?php echo sanitizeOutput($user_data['role']); ?><br>
                <strong>ID:</strong> <?php echo sanitizeOutput($user_data['id']); ?><br>
                <small>Page will reload to show the session...</small>
                <script>setTimeout(function(){ window.location.reload(); }, 2000);</script>
            </div>
        <?php elseif ($login_result == "failed"): ?>
            <div class="error">
                <strong>❌ Login Failed!</strong><br>
                Invalid username or password.
            </div>
        <?php elseif ($login_result == "locked"): ?>
            <div class="error">
                <strong>🔒 Account Locked!</strong><br>
                <?php echo sanitizeOutput($sql_error); ?>
            </div>
        <?php elseif ($login_result == "error"): ?>
            <div class="error">
                <strong>💥 Error:</strong><br>
                <?php echo sanitizeOutput($sql_error); ?>
            </div>
        <?php endif; ?>
    </div>
    <?php else: ?>
    
    <!-- User Dashboard -->
    <div class="container">
        <h2>🎯 Dashboard - Authenticated User <span class="secure-badge">SECURE</span></h2>
        <div class="success">
            <h3>Welcome to the secure zone!</h3>
            <p>You are logged in as <strong><?php echo sanitizeOutput($_SESSION['username']); ?></strong> with the role of <strong><?php echo sanitizeOutput($_SESSION['role']); ?></strong>.</p>
            
            <h4>Session Information:</h4>
            <ul>
                <li><strong>User ID:</strong> <?php echo sanitizeOutput($_SESSION['user_id']); ?></li>
                <li><strong>Email:</strong> <?php echo sanitizeOutput($_SESSION['email']); ?></li>
                <li><strong>Login time:</strong> <?php echo sanitizeOutput($_SESSION['login_time']); ?></li>
                <li><strong>Session ID:</strong> <?php echo sanitizeOutput(session_id()); ?></li>
            </ul>
        </div>
    </div>

    <!-- Profile Update Form - XSS PROTECTED -->
    <div class="container">
        <h3>👤 Update Profile <span class="secure-badge">XSS PROTECTED</span></h3>
        
        <?php if ($profile_update_message): ?>
            <div class="<?php echo strpos($profile_update_message, 'successfully') !== false ? 'success' : 'error'; ?>">
                <?php echo sanitizeOutput($profile_update_message); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo sanitizeOutput(generateCSRFToken()); ?>">
            
            <div class="form-group">
                <label for="profile_note">Profile Note/Bio:</label>
                <textarea id="profile_note" name="profile_note" placeholder="Enter your profile information, links, or notes..." maxlength="1000"><?php echo sanitizeOutput($_SESSION['profile_note'] ?? ''); ?></textarea>
                <small>✅ This field is protected against XSS attacks! Maximum 1000 characters.</small>
            </div>
            <input type="hidden" name="update_profile" value="1">
            <input type="submit" value="Update Profile">
        </form>

        <!-- Display Profile Note - XSS PROTECTED -->
        <?php if (!empty($_SESSION['profile_note'])): ?>
        <div class="profile-display">
            <h4>Current Profile Note:</h4>
            <!-- ✅ SECURE: Output is sanitized against XSS -->
            <div><?php echo sanitizeOutput($_SESSION['profile_note']); ?></div>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Security Features -->
    <div class="container">
        <div class="security-features">
            <h3>🛡️ Security Features Implemented</h3>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                <div>
                    <h4>🔒 SQL Injection Protection</h4>
                    <ul class="feature-list">
                        <li>Prepared statements for all database queries</li>
                        <li>Parameter binding with type checking</li>
                        <li>Input validation and sanitization</li>
                        <li>No direct SQL string concatenation</li>
                    </ul>
                </div>
                
                <div>
                    <h4>🚫 XSS Attack Prevention</h4>
                    <ul class="feature-list">
                        <li>HTML entity encoding for all output</li>
                        <li>Input sanitization and validation</li>
                        <li>Content Security Policy ready</li>
                        <li>Safe handling of user-generated content</li>
                    </ul>
                </div>
                
                <div>
                    <h4>🔐 Authentication Security</h4>
                    <ul class="feature-list">
                        <li>Password hashing with bcrypt</li>
                        <li>Account lockout after failed attempts</li>
                        <li>Session management and regeneration</li>
                        <li>Secure logout functionality</li>
                    </ul>
                </div>
                
                <div>
                    <h4>🛡️ Additional Protections</h4>
                    <ul class="feature-list">
                        <li>CSRF token validation</li>
                        <li>CAPTCHA verification system</li>
                        <li>Rate limiting for login attempts</li>
                        <li>Input length restrictions</li>
                        <li>Error message sanitization</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="info">
            <h3>🎓 Educational Note</h3>
            <p>This application demonstrates proper security practices for web development:</p>
            <ul>
                <li><strong>Always use prepared statements</strong> for database queries</li>
                <li><strong>Sanitize all output</strong> using htmlspecialchars() or similar functions</li>
                <li><strong>Validate and sanitize input</strong> on both client and server side</li>
                <li><strong>Implement CSRF protection</strong> for state-changing operations</li>
                <li><strong>Use CAPTCHA verification</strong> to prevent automated attacks</li>
                <li><strong>Use proper password hashing</strong> algorithms like bcrypt</li>
                <li><strong>Implement rate limiting</strong> to prevent brute force attacks</li>
            </ul>
        </div>
    </div>

    <?php if (isset($db_error) && $db_error): ?>
    <div class="container">
        <div class="error">
            <strong>Database Connection Error:</strong><br>
            <?php echo sanitizeOutput($db_error); ?><br>
            <small>Make sure MySQL is running and that you have run the setup first.</small>
        </div>
    </div>
    <?php endif; ?>
</body>
<script>
function refreshCaptcha() {
    var captchaImg = document.getElementById('captcha-img');
    var captchaInput = document.getElementById('captcha');
    
    // Add timestamp to prevent caching
    captchaImg.src = '?captcha=1&t=' + new Date().getTime();
    
    // Clear the input field
    captchaInput.value = '';
    captchaInput.focus();
}

// Auto-refresh CAPTCHA on page load if there was an error
document.addEventListener('DOMContentLoaded', function() {
    var hasError = <?php echo ($login_result == 'error' || $login_result == 'failed') ? 'true' : 'false'; ?>;
    if (hasError) {
        setTimeout(refreshCaptcha, 500);
    }
    
    // Ensure CAPTCHA loads properly
    var captchaImg = document.getElementById('captcha-img');
    captchaImg.onload = function() {
        console.log('CAPTCHA loaded successfully');
    };
    captchaImg.onerror = function() {
        console.log('CAPTCHA failed to load, refreshing...');
        setTimeout(refreshCaptcha, 1000);
    };
});
</script>
</html>