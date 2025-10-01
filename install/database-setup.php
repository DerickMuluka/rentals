<?php
// Database setup script - Run this once to set up the database

// Database configuration
$host = 'localhost';
$dbname = 'house_rental_kenya';
$username = 'root';
$password = '';

// Create connection
try {
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS $dbname");
    $pdo->exec("USE $dbname");
    
    echo "Database created successfully.<br>";
    
    // Read SQL file
    $sqlFile = __DIR__ . '/../house_rental_kenya.sql';
    
    if (file_exists($sqlFile)) {
        $sql = file_get_contents($sqlFile);
        
        // Split SQL statements
        $statements = array_filter(array_map('trim', explode(';', $sql)));
        
        foreach ($statements as $statement) {
            if (!empty($statement)) {
                try {
                    $pdo->exec($statement);
                    echo "Executed: " . substr($statement, 0, 50) . "...<br>";
                } catch (PDOException $e) {
                    echo "Error executing statement: " . $e->getMessage() . "<br>";
                }
            }
        }
        
        echo "Database setup completed successfully!<br>";
        echo "<h2>Next Steps:</h2>";
        echo "<ol>";
        echo "<li>Update the database configuration in <code>includes/config.php</code> if needed</li>";
        echo "<li>Test the application by visiting the home page</li>";
        echo "<li>Login with the default admin account: <strong>admin@coastalstudenthousing.co.ke</strong> / <strong>admin123</strong></li>";
        echo "</ol>";
        
    } else {
        echo "Error: SQL file not found at $sqlFile<br>";
    }
    
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Create necessary directories
function createDirectory($path) {
    if (!is_dir($path)) {
        if (mkdir($path, 0777, true)) {
            echo "Created directory: $path<br>";
            // Create index.html to prevent directory listing
            file_put_contents($path . '/index.html', '<!DOCTYPE html><html><head><title>403 Forbidden</title></head><body><p>Directory access is forbidden.</p></body></html>');
        } else {
            echo "Error creating directory: $path<br>";
        }
    } else {
        echo "Directory already exists: $path<br>";
    }
}

// Create upload directories
$basePath = dirname(__DIR__);
createDirectory($basePath . '/assets/images');
createDirectory($basePath . '/assets/images/properties');
createDirectory($basePath . '/assets/images/universities');
createDirectory($basePath . '/uploads');
createDirectory($basePath . '/logs');

echo "<h2>Directory setup completed!</h2>";

// Test database connection
try {
    $testPdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $testPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Test queries
    $users = $testPdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $properties = $testPdo->query("SELECT COUNT(*) FROM properties")->fetchColumn();
    $universities = $testPdo->query("SELECT COUNT(*) FROM universities")->fetchColumn();
    
    echo "<h2>Database Test Results:</h2>";
    echo "<ul>";
    echo "<li>Users: $users</li>";
    echo "<li>Properties: $properties</li>";
    echo "<li>Universities: $universities</li>";
    echo "</ul>";
    
    echo "<h2 style='color: green;'>Setup completed successfully!</h2>";
    echo "<p>You can now <a href='../index.php'>access the application</a>.</p>";
    
} catch (PDOException $e) {
    echo "<h2 style='color: red;'>Database test failed:</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Setup - Kenya Coastal Student Housing</title>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 2rem;
            background-color: #f8f9fa;
            color: #333;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        h1, h2 {
            color: #2c3e50;
        }
        
        .success {
            color: #27ae60;
        }
        
        .error {
            color: #e74c3c;
        }
        
        code {
            background: #f8f9fa;
            padding: 0.2rem 0.4rem;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
        }
        
        .btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            background: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 1rem;
        }
        
        .btn:hover {
            background: #2980b9;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Database Setup</h1>
        <p>This script sets up the database and necessary directories for the Kenya Coastal Student Housing system.</p>
        
        <h2>Setup Results:</h2>
        <div id="results">
            <?php
            // Results are output above
            ?>
        </div>
        
        <a href="../index.php" class="btn">Go to Application</a>
        <a href="../admin/index.php" class="btn">Go to Admin Panel</a>
    </div>
</body>
</html>