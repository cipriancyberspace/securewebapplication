<?php
// Database connection details
$host = 'localhost';
$dbname = 'test_db';
$username = 'root';
$password = '';

try {
    // Create a PDO instance (database connection)
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);

    // Set PDO to throw exceptions on errors
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Example: Insert data into the database using a prepared statement
    $name = "John Doe";
    $email = "john.doe@example.com";

    // Prepare the SQL statement
    $stmt = $pdo->prepare("INSERT INTO users (name, email) VALUES (:name, :email)");

    // Bind parameters to the prepared statement
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':email', $email);

    // Execute the statement
    $stmt->execute();
    echo "Data inserted successfully!";

    // Example: Fetch data from the database
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
    $stmt->execute(['email' => $email]);

    // Fetch the result as an associative array
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user) {
        echo "User found: " . $user['name'];
    } else {
        echo "User not found!";
    }

} catch (PDOException $e) {
    // Handle database errors
    echo "Database error: " . $e->getMessage();
}
?>