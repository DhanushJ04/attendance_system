<?php
$host = "localhost";
$dbname = "attendance_db";  // your database name
$username = "root";         // default XAMPP username
$password = "";             // default XAMPP has no password

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    // Set error mode
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // echo "Connected successfully"; // (Optional) for testing
} catch(PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
?>