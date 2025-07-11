<?php
session_start();
require_once 'config.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login_id = trim($_POST['login_id']);

    if (empty($login_id)) {
        $error = "Login ID is required!";
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE login_id = ?");
        $stmt->bind_param("s", $login_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user) {
            $_SESSION['user'] = $user;
            switch ($user['role']) {
                case 'admin':
                    header("Location: admin/dashboard.php");
                    break;
                case 'teacher':
                    header("Location: teacher/dashboard.php");
                    break;
                case 'student':
                    header("Location: student/dashboard.php");
                    break;
            }
            exit;
        } else {
            $error = "Invalid Login ID!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Login - School Portal</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            background: url('assets/img/bg-school.jpg') no-repeat center center fixed;
            background-size: cover;
        }

        .login-box {
            background-color: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(5px);
        }
    </style>
</head>

<body class="min-h-screen flex items-center justify-center">
    <div class="login-box w-full max-w-md shadow-lg rounded-xl p-8">
        <div class="text-center mb-6">
            <img src="assets/img/logo/logo.png" alt="School Logo" class="w-24 mx-auto mb-4">
            <h2 class="text-2xl font-semibold text-gray-700">School Portal Login</h2>
            <p class="text-gray-500 mt-2">Enter your ID to access the system</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="bg-red-100 text-red-600 text-sm px-4 py-2 rounded mb-4">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Login ID</label>
                <input type="text" name="login_id" placeholder="Enter your ID"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required>
            </div>

            <button type="submit"
                class="w-full bg-blue-600 hover:bg-blue-700 text-white py-3 rounded-lg font-semibold transition transform hover:scale-105">
                Login
            </button>
        </form>
    </div>
</body>

</html>