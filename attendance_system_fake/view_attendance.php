<?php
session_start();
require 'db.php';

if (!isset($_SESSION['teacher_id'])) {
    header("Location: login.html");
    exit();
}

if (!isset($_GET['class_id'])) {
    echo "Class ID missing.";
    exit();
}

$class_id = $_GET['class_id'];

// Get students in the selected class
$stmt = $conn->prepare("SELECT * FROM students WHERE class_id = :class_id");
$stmt->execute(['class_id' => $class_id]);
$students = $stmt->fetchAll();

// For each student, calculate attendance stats
function getAttendanceStats($conn, $student_id) {
    $stmt = $conn->prepare("SELECT 
        COUNT(*) AS total_classes,
        SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) AS total_present 
        FROM attendance 
        WHERE student_id = :student_id");
    $stmt->execute(['student_id' => $student_id]);
    return $stmt->fetch();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>View Attendance</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f4f4f4; }
        table { width: 80%; border-collapse: collapse; background: white; }
        th, td { padding: 12px; border: 1px solid #ccc; text-align: center; }
        th { background: #eee; }
        .low { color: red; font-weight: bold; }
        .btn { padding: 6px 12px; background: green; color: white; border: none; border-radius: 5px; cursor: pointer; }
    </style>
</head>
<body>

<h2>Attendance Report</h2>

<table>
    <tr>
        <th>Sl. No.</th>
        <th>Student Name</th>
        <th>Total Classes</th>
        <th>Total Present</th>
        <th>Attendance %</th>
        <th>Action</th>
    </tr>

    <?php
    $i = 1;
    foreach ($students as $student):
        $stats = getAttendanceStats($conn, $student['id']);
        $total = $stats['total_classes'];
        $present = $stats['total_present'];
        $percentage = ($total > 0) ? round(($present / $total) * 100, 2) : 0;
        $lowAttendance = $percentage < 75;
    ?>
    <tr>
        <td><?php echo $i++; ?></td>
        <td><?php echo htmlspecialchars($student['student_name']); ?></td>
        <td><?php echo $total; ?></td>
        <td><?php echo $present; ?></td>
        <td class="<?php echo $lowAttendance ? 'low' : ''; ?>">
            <?php echo $percentage; ?>%
        </td>
        <td>
            <?php if ($lowAttendance): ?>
                <form method="post" action="send_email.php">
                    <input type="hidden" name="student_id" value="<?php echo $student['id']; ?>">
                    <button class="btn" type="submit">Send Email</button>
                </form>
            <?php else: ?>
                ✔ OK
            <?php endif; ?>
        </td>
    </tr>
    <?php endforeach; ?>
</table>

</body>
</html>