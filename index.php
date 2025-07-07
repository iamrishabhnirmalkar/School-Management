<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>School ERP - Welcome</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-blue-100 to-blue-300 min-h-screen flex flex-col items-center justify-center">
    <div class="mb-8 flex flex-col items-center">
        <!-- School Logo Placeholder -->
        <div class="w-20 h-20 mb-2">
            <img src="assets/school-logo.svg" alt="School Logo" class="w-full h-full object-contain"/>
        </div>
        <h1 class="text-3xl font-bold text-blue-800">Welcome to School ERP</h1>
        <p class="text-gray-600 mt-2">Please select your portal</p>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-8 w-full max-w-3xl">
        <!-- Admin Card -->
        <a href="login.php?role=admin" class="bg-white rounded-lg shadow-lg p-8 flex flex-col items-center hover:scale-105 transition-transform">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-blue-600 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c0-1.104.896-2 2-2s2 .896 2 2-.896 2-2 2-2-.896-2-2zm-6 8v-1a4 4 0 014-4h4a4 4 0 014 4v1" /></svg>
            <span class="text-xl font-semibold text-blue-700">Admin</span>
        </a>
        <!-- Teacher Card -->
        <a href="login.php?role=teacher" class="bg-white rounded-lg shadow-lg p-8 flex flex-col items-center hover:scale-105 transition-transform">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-green-600 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 7v-6m0 0l-9-5m9 5l9-5" /></svg>
            <span class="text-xl font-semibold text-green-700">Teacher</span>
        </a>
        <!-- Student Card -->
        <a href="login.php?role=student" class="bg-white rounded-lg shadow-lg p-8 flex flex-col items-center hover:scale-105 transition-transform">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-yellow-500 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 7v-6m0 0l-9-5m9 5l9-5" /></svg>
            <span class="text-xl font-semibold text-yellow-600">Student</span>
        </a>
    </div>
</body>
</html>
