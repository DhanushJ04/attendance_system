<?php
session_start();
require 'db.php';

if (!isset($_SESSION['teacher_id'])) {
    header("Location: login.html");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $classId = $_POST['class_id'];
    $attendance = $_POST['attendance'];
    $date = date('Y-m-d'); // Current date

    foreach ($attendance as $studentId => $status) {
        // Check if attendance for this student on this date already exists
        $checkStmt = $conn->prepare("SELECT * FROM attendance WHERE student_id = :student_id AND date = :date");
        $checkStmt->execute([
            'student_id' => $studentId,
            'date' => $date
        ]);

        if ($checkStmt->rowCount() == 0) {
            // Insert attendance if not already marked
            $stmt = $conn->prepare("INSERT INTO attendance (student_id, class_id, date, status) VALUES (:student_id, :class_id, :date, :status)");
            $stmt->execute([
                'student_id' => $studentId,
                'class_id' => $classId,
                'date' => $date,
                'status' => $status
            ]);
        }
    }

    // Redirect back to dashboard or confirmation page
    echo "<script>alert('Attendance submitted successfully!'); window.location.href='dashboard.php';</script>";
} else {
    echo "Invalid request.";
}
?>