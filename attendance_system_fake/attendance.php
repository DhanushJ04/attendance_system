<?php
session_start();
require 'db.php';

if (!isset($_SESSION['teacher_id'])) {
    header("Location: login.html");
    exit();
}

if (!isset($_GET['class_id'])) {
    echo "Class ID not provided.";
    exit();
}

$classId = $_GET['class_id'];

// Get class name
$classStmt = $conn->prepare("SELECT class_name FROM classes WHERE id = :id");
$classStmt->execute(['id' => $classId]);
$class = $classStmt->fetch();

if (!$class) {
    echo "Class not found.";
    exit();
}

// Get students in this class
$stmt = $conn->prepare("SELECT * FROM students WHERE class_id = :class_id");
$stmt->execute(['class_id' => $classId]);
$students = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Mark Attendance - <?php echo htmlspecialchars($class['class_name']); ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
            background: #f3f3f3;
        }
        h2 {
            margin-bottom: 20px;
        }
        table {
            width: 60%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 12px;
            text-align: center;
        }
        button {
            padding: 10px 20px;
            background: green;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }
        button:hover {
            background: darkgreen;
        }
    </style>
</head>
<body>

<h2>Mark Attendance for Class: <?php echo htmlspecialchars($class['class_name']); ?></h2>

<form method="POST" action="submit_attendance.php">
    <input type="hidden" name="class_id" value="<?php echo $classId; ?>">

    <table>
        <tr>
            <th>Sl. No.</th>
            <th>Student Name</th>
            <th>Present</th>
            <th>Absent</th>
        </tr>

        <?php foreach ($students as $index => $student): ?>
            <tr>
                <td><?php echo $index + 1; ?></td>
                <td><?php echo htmlspecialchars($student['student_name']); ?></td>
                <td><input type="radio" name="attendance[<?php echo $student['id']; ?>]" value="Present" required></td>
                <td><input type="radio" name="attendance[<?php echo $student['id']; ?>]" value="Absent"></td>
            </tr>
        <?php endforeach; ?>
    </table>

    <button type="submit">Submit Attendance</button>
</form>

</body>
</html>