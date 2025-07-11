<?php
session_start();
require_once '../config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../logout.php");
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: index.php?error=Invalid+book+ID');
    exit;
}
$book_id = intval($_GET['id']);

// Fetch book info
$book = $conn->query("SELECT * FROM library_books WHERE id=$book_id")->fetch_assoc();
if (!$book) {
    header('Location: index.php?error=Book+not+found');
    exit;
}
if ($book['available'] <= 0) {
    header('Location: index.php?error=No+copies+available');
    exit;
}
// Fetch students
$students = $conn->query("SELECT id, full_name FROM users WHERE role='student' ORDER BY full_name")->fetch_all(MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = intval($_POST['student_id'] ?? 0);
    $issue_date = $_POST['issue_date'] ?? date('Y-m-d');
    $due_date = $_POST['due_date'] ?? date('Y-m-d', strtotime('+14 days'));
    if ($student_id && $issue_date && $due_date) {
        $stmt = $conn->prepare("INSERT INTO book_issues (book_id, student_id, issue_date, due_date, status) VALUES (?, ?, ?, ?, 'issued')");
        $stmt->bind_param('iiss', $book_id, $student_id, $issue_date, $due_date);
        if ($stmt->execute()) {
            $conn->query("UPDATE library_books SET available = available - 1 WHERE id=$book_id");
            header('Location: index.php?success=Book+issued+successfully');
            exit;
        } else {
            $error = 'Failed to issue book.';
        }
    } else {
        $error = 'All fields are required.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Issue Book - Library Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="container mx-auto px-6 py-8">
        <div class="bg-white rounded-lg shadow-md p-6 max-w-lg mx-auto">
            <h2 class="text-xl font-bold mb-4">Issue Book: <?= htmlspecialchars($book['title']) ?></h2>
            <?php if (!empty($error)): ?>
                <div class="bg-red-100 text-red-700 px-4 py-2 rounded mb-4"> <?= htmlspecialchars($error) ?> </div>
            <?php endif; ?>
            <form method="post">
                <div class="mb-4">
                    <label class="block mb-1 font-semibold">Select Student</label>
                    <select name="student_id" class="w-full border rounded px-3 py-2" required>
                        <option value="">-- Select Student --</option>
                        <?php foreach ($students as $student): ?>
                            <option value="<?= $student['id'] ?>"> <?= htmlspecialchars($student['full_name']) ?> </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-4">
                    <label class="block mb-1 font-semibold">Issue Date</label>
                    <input type="date" name="issue_date" class="w-full border rounded px-3 py-2" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="mb-4">
                    <label class="block mb-1 font-semibold">Due Date</label>
                    <input type="date" name="due_date" class="w-full border rounded px-3 py-2" value="<?= date('Y-m-d', strtotime('+14 days')) ?>" required>
                </div>
                <div class="flex justify-between">
                    <a href="index.php" class="bg-gray-200 px-4 py-2 rounded hover:bg-gray-300">Cancel</a>
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Issue Book</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html> 