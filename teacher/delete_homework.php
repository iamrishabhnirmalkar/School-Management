<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'teacher') {
    header("Location: ../logout.php");
    exit;
}

$teacher_id = $_SESSION['user']['id'];
$homework_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch homework details
$stmt = $conn->prepare("SELECT * FROM homework WHERE id = ? AND teacher_id = ?");
$stmt->bind_param("ii", $homework_id, $teacher_id);
$stmt->execute();
$hw = $stmt->get_result()->fetch_assoc();
if (!$hw) {
    echo '<div class="text-center py-8 text-red-600 font-bold">Homework not found or access denied.</div>';
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $conn->prepare("DELETE FROM homework WHERE id = ? AND teacher_id = ?");
    $stmt->bind_param("ii", $homework_id, $teacher_id);
    if ($stmt->execute()) {
        header("Location: homework.php");
        exit;
    } else {
        $error = 'Error deleting homework.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Homework - School ERP</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <header class="bg-blue-700 text-white shadow-md">
        <div class="container mx-auto px-6 py-4 flex justify-between items-center">
            <div class="flex items-center space-x-4">
                <img src="../assets/img/logo/logo.png" alt="Logo" class="w-10 h-10">
                <div>
                    <h1 class="text-2xl font-bold">School ERP System</h1>
                    <p class="text-blue-200">Delete Homework</p>
                </div>
            </div>
            <div class="flex items-center space-x-4">
                <a href="homework.php" class="bg-white text-blue-700 px-4 py-2 rounded-lg font-semibold hover:bg-blue-50 transition">
                    <i class="fas fa-arrow-left mr-1"></i>Back to Homework
                </a>
                <span><?= htmlspecialchars($_SESSION['user']['full_name']) ?></span>
            </div>
        </div>
    </header>
    <main class="container mx-auto px-6 py-8">
        <div class="bg-white rounded-lg shadow-md p-6 max-w-xl mx-auto">
            <h2 class="text-xl font-bold mb-4 text-gray-800">Delete Homework</h2>
            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-2 rounded mb-4">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            <form method="POST" class="space-y-4">
                <div class="text-gray-700 text-lg mb-4">
                    Are you sure you want to delete the homework <span class="font-bold text-red-600">"<?= htmlspecialchars($hw['title']) ?>"</span>?
                </div>
                <div class="flex justify-end space-x-2">
                    <a href="homework.php" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">Cancel</a>
                    <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">Delete</button>
                </div>
            </form>
        </div>
    </main>
</body>
</html> 