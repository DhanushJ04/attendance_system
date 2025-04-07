<?php
session_start();
require 'db.php';

if (!isset($_SESSION['teacher_id'])) {
    header("Location: login.html");
    exit();
}

$teacher_id = $_SESSION['teacher_id'];

// Fetch teacher info
$stmt = $conn->prepare("SELECT * FROM teachers WHERE id = :id");
$stmt->execute(['id' => $teacher_id]);
$teacher = $stmt->fetch();

// Optional: You can store class-year mappings in DB. For now, hardcoding.
$class_years = ["First Year", "Second Year", "Third Year"];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Teacher Dashboard</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #f5f7fa, #c3cfe2);
            margin: 0;
            padding: 0;
            text-align: center;
        }

        header {
            background: #4A90E2;
            color: white;
            padding: 20px;
            position: relative;
        }

        .datetime {
            position: absolute;
            top: 20px;
            right: 30px;
            font-size: 18px;
            font-weight: normal;
        }

        h1 {
            margin: 0;
        }

        .container {
            margin-top: 50px;
        }

        .class-btn {
            display: inline-block;
            margin: 15px;
            padding: 15px 30px;
            font-size: 18px;
            background-color: #4A90E2;
            color: white;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            transition: background-color 0.3s;
            box-shadow: 0px 4px 10px rgba(0,0,0,0.1);
        }

        .class-btn:hover {
            background-color: #357ABD;
        }
    </style>
</head>
<body>

<header>
    <h1>Welcome, <?php echo htmlspecialchars($teacher['username']); ?> 👩‍🏫</h1>
    <div class="datetime" id="datetime"></div>
</header>

<div class="container">
    <h2>Select a Year</h2>
    <?php foreach ($class_years as $year): ?>
        <form action="select_subject.php" method="POST" style="display:inline;">
            <input type="hidden" name="year" value="<?php echo $year; ?>">
            <button class="class-btn" type="submit"><?php echo $year; ?></button>
        </form>
    <?php endforeach; ?>
</div>

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