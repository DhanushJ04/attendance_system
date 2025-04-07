<?php
session_start();
require 'db.php';

if (!isset($_SESSION['teacher_id'])) {
    header("Location: login.html");
    exit();
}

$teacher_id = $_SESSION['teacher_id'];
$selected_year = $_POST['year'] ?? '';

// Hardcoded subject list
$teacher_subjects = [
    '1' => [
        'First Year' => ['C Language'],
        'Second Year' => ['Python', 'AI'],
        'Third Year' => ['CMA']
    ]
];

$subjects = $teacher_subjects[$teacher_id][$selected_year] ?? [];

if (!$subjects) {
    echo "<h2>No subjects assigned for $selected_year</h2>";
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Select Subject</title>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(to right, #e3f2fd, #bbdefb);
            text-align: center;
            margin: 0;
            padding: 0;
        }

        header {
            background: #1565c0;
            color: white;
            padding: 30px 20px;
            position: relative;
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
        }

        h1 {
            margin: 0;
            font-size: 36px;
            letter-spacing: 1px;
            text-shadow: 1px 1px 4px rgba(0,0,0,0.2);
        }

        .datetime {
            position: absolute;
            top: 20px;
            right: 30px;
            font-size: 18px;
            font-weight: normal;
            color: #f1f1f1;
        }

        h2 {
            font-size: 28px;
            margin: 40px 0 20px;
            color: #0d47a1;
        }

        .subject-btn {
            display: inline-block;
            margin: 20px 30px;
            padding: 15px 40px;
            font-size: 18px;
            background-color: #1976d2;
            color: white;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            transition: transform 0.2s, background-color 0.3s, box-shadow 0.3s;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .subject-btn:hover {
            background-color: #0d47a1;
            transform: translateY(-3px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.2);
        }
    </style>
</head>
<body>

<header>
    <h1>Select Subject for <?php echo htmlspecialchars($selected_year); ?></h1>
    <div class="datetime" id="datetime"></div>
</header>

<h2>Available Subjects</h2>

<?php foreach ($subjects as $subject): ?>
    <form action="mark_attendance.php" method="POST" style="display:inline;">
        <input type="hidden" name="year" value="<?php echo $selected_year; ?>">
        <input type="hidden" name="subject" value="<?php echo $subject; ?>">
        <button class="subject-btn" type="submit"><?php echo $subject; ?></button>
    </form>
<?php endforeach; ?>

<script>
    function updateDateTime() {
        const now = new Date();
        const date = now.toLocaleDateString();
        const time = now.toLocaleTimeString();
        document.getElementById('datetime').textContent = `${date} | ${time}`;
    }

    setInterval(updateDateTime, 1000);
    updateDateTime();
</script>

</body>
</html>