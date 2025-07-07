<?php
// Start session
session_start();
// If already logged in, redirect to dashboard
if (isset($_SESSION['role'])) {
    header('Location: ' . $_SESSION['role'] . '/dashboard.php');
    exit();
}

// Handle login POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userid = trim($_POST['userid']);
    require_once 'db.php';
    $found = false;
    // Check Admins
    $stmt = $conn->prepare('SELECT id, username, name FROM admins WHERE username = ?');
    $stmt->bind_param('s', $userid);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $_SESSION['role'] = 'admin';
        $_SESSION['user_id'] = $row['id'];
        $_SESSION['name'] = $row['name'];
        header('Location: admin/dashboard.php');
        exit();
    }
    $stmt->close();
    // Check Students
    $stmt = $conn->prepare('SELECT id, student_id, name FROM students WHERE student_id = ?');
    $stmt->bind_param('s', $userid);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $_SESSION['role'] = 'student';
        $_SESSION['user_id'] = $row['id'];
        $_SESSION['name'] = $row['name'];
        header('Location: student/dashboard.php');
        exit();
    }
    $stmt->close();
    // Check Teachers
    $stmt = $conn->prepare('SELECT id, teacher_id, name FROM teachers WHERE teacher_id = ?');
    $stmt->bind_param('s', $userid);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $_SESSION['role'] = 'teacher';
        $_SESSION['user_id'] = $row['id'];
        $_SESSION['name'] = $row['name'];
        header('Location: teacher/dashboard.php');
        exit();
    }
    $stmt->close();
    $conn->close();
    $_SESSION['login_error'] = 'User not found!';
    header('Location: login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>School ERP Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-blue-100 to-blue-300 min-h-screen flex items-center justify-center">
    <div class="w-full max-w-md mx-auto bg-white rounded-xl shadow-lg p-8 flex flex-col items-center">
        <div class="w-16 h-16 mb-4">
            <img src="assets/school-logo.svg" alt="School Logo" class="w-full h-full object-contain"/>
        </div>
        <h2 class="text-2xl font-bold mb-2 text-blue-800">School ERP Login</h2>
        <p class="mb-4 text-gray-500">Sign in to your account</p>
        <?php if (isset($_SESSION['login_error'])): ?>
            <div class="mb-4 text-red-600 text-center w-full">
                <?php echo $_SESSION['login_error']; unset($_SESSION['login_error']); ?>
            </div>
        <?php endif; ?>
        <form action="login.php" method="POST" class="space-y-4 w-full">
            <div>
                <label for="userid" class="block mb-1 font-medium text-gray-700">Username / ID</label>
                <input type="text" name="userid" id="userid" required class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400" placeholder="Enter your username or ID">
            </div>
            <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700 transition-colors font-semibold shadow">Login</button>
        </form>
        <a href="index.php" class="mt-6 text-blue-500 hover:underline text-sm">&larr; Back to Home</a>
    </div>
</body>
</html>
