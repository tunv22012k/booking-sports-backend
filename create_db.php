<?php
$host = '127.0.0.1';
$port = '5432';
$dbname = 'postgres';
$user = 'postgres';
$password = ''; // Try empty password

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
    $pdo = new PDO($dsn, $user, $password);
    
    // Check if database exists
    $stmt = $pdo->query("SELECT 1 FROM pg_database WHERE datname = 'messaging_backend'");
    if ($stmt->fetch()) {
        echo "Database 'messaging_backend' already exists.\n";
    } else {
        $pdo->exec("CREATE DATABASE messaging_backend");
        echo "Database 'messaging_backend' created successfully.\n";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
