<?php
session_start();
require 'db.php';

if (!isset($_SESSION['teacher_id'])) {
    header("Location: login.html");
    exit();
}

$teacher_id = $_SESSION['teacher_id'];
$year = $_POST['year'] ?? '';
$subject = $_POST['subject'] ?? '';

// For demo, using 10 hardcoded students for each year
$students = [
    'First Year' => [
        ['id' => 'FY01', 'name' => 'Alice'],
        ['id' => 'FY02', 'name' => 'Ben'],
        ['id' => 'FY03', 'name' => 'Cathy'],
        ['id' => 'FY04', 'name' => 'David'],
        ['id' => 'FY05', 'name' => 'Emma'],
        ['id' => 'FY06', 'name' => 'Frank'],
        ['id' => 'FY07', 'name' => 'Grace'],
        ['id' => 'FY08', 'name' => 'Harry'],
        ['id' => 'FY09', 'name' => 'Isha'],
        ['id' => 'FY10', 'name' => 'Jack']
    ],
    'Second Year' => [
        ['id' => 'SY01', 'name' => 'Anu'],
        ['id' => 'SY02', 'name' => 'Bharath'],
        // Add 8 more students
    ],
    'Third Year' => [
        ['id' => 'TY01', 'name' => 'Rahul'],
        ['id' => 'TY02', 'name' => 'Riya'],
        // Add 8 more students
    ]
];

$class_students = $students[$year] ?? [];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Mark Attendance</title>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: #f1f8ff;
            margin: 0;
        }

        header {
            background-color: #1565c0;
            color: white;
            padding: 25px;
            text-align: center;
            font-size: 28px;
            position: relative;
        }

        .datetime {
            position: absolute;
            right: 30px;
            top: 20px;
            font-size: 16px;
        }

        h2 {
            margin-top: 30px;
            text-align: center;
            color: #0d47a1;
        }

        .date-container {
            text-align: center;
            margin: 20px;
        }

        .date-container input {
            padding: 10px;
            font-size: 16px;
        }

        table {
            margin: 30px auto;
            border-collapse: collapse;
            width: 90%;
            background-color: #ffffff;
            box-shadow: 0px 4px 10px rgba(0,0,0,0.1);
        }

        th, td {
            border: 1px solid #ccc;
            padding: 12px 15px;
            text-align: center;
            font-size: 16px;
        }

        th {
            background-color: #1976d2;
            color: white;
        }

        input[type="radio"] {
            transform: scale(1.3);
            margin: 5px;
        }

        .submit-btn {
            display: block;
            margin: 30px auto 60px;
            padding: 12px 30px;
            font-size: 18px;
            background-color: #0d47a1;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: 0.3s;
        }

        .submit-btn:hover {
            background-color: #093170;
        }
    </style>
</head>
<body>

<header>
    <?php echo htmlspecialchars($year); ?> - <?php echo htmlspecialchars($subject); ?>
    <div class="datetime" id="datetime"></div>
</header>

<h2>Mark Attendance</h2>

<form action="save_attendance.php" method="POST">
    <input type="hidden" name="year" value="<?php echo htmlspecialchars($year); ?>">
    <input type="hidden" name="subject" value="<?php echo htmlspecialchars($subject); ?>">

    <div class="date-container">
        <label for="attendance_date"><strong>Select Date:</strong></label>
        <input type="date" id="attendance_date" name="attendance_date" required>
    </div>

    <table>
        <tr>
            <th>Sl. No.</th>
            <th>Student ID</th>
            <th>Name</th>
            <th>Present ✅</th>
            <th>Absent ❌</th>
            <th>Exempted 🚫</th>
        </tr>

        <?php foreach ($class_students as $index => $student): ?>
            <tr>
                <td><?php echo $index + 1; ?></td>
                <td><?php echo htmlspecialchars($student['id']); ?></td>
                <td><?php echo htmlspecialchars($student['name']); ?></td>
                <td><input type="radio" name="attendance[<?php echo $student['id']; ?>]" value="Present" required></td>
                <td><input type="radio" name="attendance[<?php echo $student['id']; ?>]" value="Absent"></td>
                <td><input type="radio" name="attendance[<?php echo $student['id']; ?>]" value="Exempted"></td>
            </tr>
        <?php endforeach; ?>
    </table>

    <button class="submit-btn" type="submit">Submit Attendance</button>
</form>

<script>
    function updateDateTime() {
        const now = new Date();
        const date = now.toLocaleDateString();
        const time = now.toLocaleTimeString();
        document.getElementById('datetime').textContent = `${date} | ${time}`;
    }

    // Restrict calendar: no Sundays, no future dates
    const dateInput = document.getElementById('attendance_date');
    const today = new Date().toISOString().split("T")[0];
    dateInput.setAttribute('max', today);

    dateInput.addEventListener('input', function () {
        const selectedDate = new Date(this.value);
        if (selectedDate.getDay() === 0) {
            alert("Sundays are not allowed for marking attendance.");
            this.value = '';
        }
    });

    setInterval(updateDateTime, 1000);
    updateDateTime();
</script>

</body>
</html>