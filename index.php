<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>School Dashboards</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .portal-card {
            transition: all 0.3s ease;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }

        .portal-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        }

        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        }
    </style>
</head>

<body class="min-h-screen">
    <!-- Logo -->
    <div class="text-center py-10">
        <img src="assets/img/logo/logo.png" alt="School Logo" class="mx-auto w-32 md:w-48">
        <h1 class="text-3xl md:text-4xl font-bold mt-6 text-gray-800">Welcome to Our School Portal</h1>
        <p class="text-gray-600 mt-2">Select your portal to continue</p>
    </div>

    <!-- Dashboard Cards -->
    <div class="max-w-6xl mx-auto px-4 py-10 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-8">
        <!-- Student Card -->
        <a href="login.php" class="portal-card bg-white rounded-xl overflow-hidden relative group">
            <div class="absolute inset-0 bg-blue-500 opacity-0 group-hover:opacity-10 transition"></div>
            <img src="assets/img/student-portal.jpg" alt="Student Portal" class="w-full h-56 object-cover">
            <div class="p-6 text-center">
                <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-user-graduate text-blue-600 text-2xl"></i>
                </div>
                <h2 class="text-xl font-bold text-blue-600">Student Portal</h2>
                <p class="text-gray-600 mt-2">Access your courses, grades, and school resources</p>
                <button class="mt-4 px-6 py-2 bg-blue-600 text-white rounded-full text-sm font-medium hover:bg-blue-700 transition">
                    Enter Portal
                </button>
            </div>
        </a>

        <!-- Teacher Card -->
        <a href="login.php" class="portal-card bg-white rounded-xl overflow-hidden relative group">
            <div class="absolute inset-0 bg-green-500 opacity-0 group-hover:opacity-10 transition"></div>
            <img src="assets/img/teacher-portal.jpg" alt="Teacher Portal" class="w-full h-56 object-cover">
            <div class="p-6 text-center">
                <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-chalkboard-teacher text-green-600 text-2xl"></i>
                </div>
                <h2 class="text-xl font-bold text-green-600">Teacher Portal</h2>
                <p class="text-gray-600 mt-2">Manage classes, attendance, and student progress</p>
                <button class="mt-4 px-6 py-2 bg-green-600 text-white rounded-full text-sm font-medium hover:bg-green-700 transition">
                    Enter Portal
                </button>
            </div>
        </a>

        <!-- Admin Card -->
        <a href="login.php" class="portal-card bg-white rounded-xl overflow-hidden relative group">
            <div class="absolute inset-0 bg-red-500 opacity-0 group-hover:opacity-10 transition"></div>
            <img src="assets/img/admin-portal.jpg" alt="Admin Portal" class="w-full h-56 object-cover">
            <div class="p-6 text-center">
                <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-user-shield text-red-600 text-2xl"></i>
                </div>
                <h2 class="text-xl font-bold text-red-600">Admin Portal</h2>
                <p class="text-gray-600 mt-2">Manage school operations and system administration</p>
                <button class="mt-4 px-6 py-2 bg-red-600 text-white rounded-full text-sm font-medium hover:bg-red-700 transition">
                    Enter Portal
                </button>
            </div>
        </a>
    </div>

    <!-- Footer -->
    <footer class="text-center text-sm py-8 text-gray-500 border-t border-gray-200 mt-12">
        <div class="max-w-6xl mx-auto px-4">
            <p>© Copyright 2025 Our School. All rights reserved.</p>
            <p class="mt-2">Empowering education through technology</p>
        </div>
    </footer>

    <!-- Font Awesome -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
</body>

</html>