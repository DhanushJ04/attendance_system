<?php
session_start();
require 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Query the teachers table
    $stmt = $conn->prepare("SELECT * FROM teachers WHERE username = :username AND password = :password");
    $stmt->execute(['username' => $username, 'password' => $password]);

    $teacher = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($teacher) {
        $_SESSION['teacher_id'] = $teacher['id'];
        $_SESSION['username'] = $teacher['username'];
        $_SESSION['class'] = $teacher['class'];

        header("Location: dashboard.php");
        exit();
    } else {
        echo "<script>alert('Invalid username or password'); window.location.href='login.html';</script>";
    }
}
?>