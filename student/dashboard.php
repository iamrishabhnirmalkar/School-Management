<?php
require_once '../db.php';
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header('Location: ../login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-yellow-100 to-yellow-300 min-h-screen flex items-center justify-center">
    <div class="w-full max-w-lg bg-white rounded-xl shadow-lg overflow-hidden">
        <div class="bg-yellow-500 flex items-center p-4">
            <img src="../assets/school-logo.svg" alt="Logo" class="w-10 h-10 mr-3">
            <h1 class="text-xl font-bold text-white">Student Dashboard</h1>
        </div>
        <div class="p-8 flex flex-col items-center">
            <h2 class="text-2xl font-semibold text-yellow-700 mb-2">Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?></h2>
            <p class="text-gray-600 mb-6">You are logged in as <span class="font-semibold">Student</span>.</p>
            <a href="../logout.php" class="bg-yellow-500 text-white px-6 py-2 rounded shadow hover:bg-yellow-700 transition-colors font-semibold">Logout</a>
        </div>
    </div>
</body>
</html>
