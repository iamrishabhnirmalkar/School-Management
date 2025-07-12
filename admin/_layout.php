<?php
// Usage: include '_layout.php';
// Set $pageTitle and $activePage before including for dynamic title and active link
if (!isset($_SESSION)) session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset(
        $pageTitle) ? htmlspecialchars($pageTitle) : 'Admin Panel - School ERP' ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .dashboard-card { transition: all 0.3s ease; }
        .dashboard-card:hover { transform: translateY(-3px); box-shadow: 0 10px 20px rgba(0,0,0,0.1); }
        .sidebar-item { transition: all 0.2s ease; }
        .sidebar-item:hover { background-color: #f3f4f6; border-left: 4px solid #3b82f6; }
        .sidebar-active { background-color: #e0e7ff; color: #1d4ed8; font-weight: bold; }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <header class="bg-blue-700 text-white shadow-md">
        <div class="container mx-auto px-6 py-4">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-4">
                    <img src="../assets/images/logo.png" alt="Logo" class="w-10 h-10">
                    <div>
                        <h1 class="text-2xl font-bold">School ERP System</h1>
                        <p class="text-blue-200"><?= isset($pageTitle) ? htmlspecialchars($pageTitle) : 'Admin Panel' ?></p>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="relative">
                        <button id="userMenuButton" class="flex items-center space-x-2 cursor-pointer focus:outline-none">
                            <img src="../assets/img/admin-avatar.jpg" alt="Admin" class="w-8 h-8 rounded-full border-2 border-white">
                            <span class="text-sm"><?= htmlspecialchars($_SESSION['user']['full_name'] ?? 'Admin') ?></span>
                            <i class="fas fa-chevron-down text-sm"></i>
                        </button>
                        <div id="userMenu" class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-10 hidden">
                            <a href="profile.php" class="block px-4 py-2 text-gray-800 hover:bg-blue-50"><i class="fas fa-user mr-2"></i>Profile</a>
                            <a href="../logout.php" class="block px-4 py-2 text-gray-800 hover:bg-blue-50"><i class="fas fa-sign-out-alt mr-2"></i>Logout</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>
    <div class="container mx-auto px-6 py-8 flex flex-col lg:flex-row gap-8">
        <!-- Sidebar Navigation -->
        <aside class="w-full lg:w-64 flex-shrink-0">
            <nav class="bg-white rounded-lg shadow-md p-4 sticky top-4">
                <ul class="space-y-2">
                    <li><a href="/admin/dashboard.php" class="sidebar-item flex items-center space-x-3 p-3 rounded-lg <?= ($activePage ?? '') === 'dashboard' ? 'sidebar-active' : '' ?>"><i class="fas fa-tachometer-alt w-5"></i><span>Dashboard</span></a></li>
                    <li><a href="/admin/students/" class="sidebar-item flex items-center space-x-3 p-3 rounded-lg <?= ($activePage ?? '') === 'students' ? 'sidebar-active' : '' ?>"><i class="fas fa-users w-5"></i><span>Student Management</span></a></li>
                    <li><a href="/admin/teachers/" class="sidebar-item flex items-center space-x-3 p-3 rounded-lg <?= ($activePage ?? '') === 'teachers' ? 'sidebar-active' : '' ?>"><i class="fas fa-chalkboard-teacher w-5"></i><span>Teacher Management</span></a></li>
                    <li><a href="/admin/classes/" class="sidebar-item flex items-center space-x-3 p-3 rounded-lg <?= ($activePage ?? '') === 'classes' ? 'sidebar-active' : '' ?>"><i class="fas fa-school w-5"></i><span>Class Management</span></a></li>
                    <li><a href="/admin/subject/" class="sidebar-item flex items-center space-x-3 p-3 rounded-lg <?= ($activePage ?? '') === 'subject' ? 'sidebar-active' : '' ?>"><i class="fas fa-book w-5"></i><span>Subject Management</span></a></li>
                    <li><a href="/admin/attendance/" class="sidebar-item flex items-center space-x-3 p-3 rounded-lg <?= ($activePage ?? '') === 'attendance' ? 'sidebar-active' : '' ?>"><i class="fas fa-calendar-check w-5"></i><span>Attendance</span></a></li>
                    <li><a href="/admin/timetable/" class="sidebar-item flex items-center space-x-3 p-3 rounded-lg <?= ($activePage ?? '') === 'timetable' ? 'sidebar-active' : '' ?>"><i class="fas fa-clock w-5"></i><span>Timetable</span></a></li>
                    <li><a href="/admin/examinations/" class="sidebar-item flex items-center space-x-3 p-3 rounded-lg <?= ($activePage ?? '') === 'examinations' ? 'sidebar-active' : '' ?>"><i class="fas fa-clipboard-list w-5"></i><span>Examinations</span></a></li>
                    <li><a href="/admin/fees/" class="sidebar-item flex items-center space-x-3 p-3 rounded-lg <?= ($activePage ?? '') === 'fees' ? 'sidebar-active' : '' ?>"><i class="fas fa-money-bill-wave w-5"></i><span>Fee Management</span></a></li>
                    <li><a href="/admin/library/" class="sidebar-item flex items-center space-x-3 p-3 rounded-lg <?= ($activePage ?? '') === 'library' ? 'sidebar-active' : '' ?>"><i class="fas fa-book w-5"></i><span>Library</span></a></li>
                    <li><a href="/admin/identity-cards/" class="sidebar-item flex items-center space-x-3 p-3 rounded-lg <?= ($activePage ?? '') === 'identity-cards' ? 'sidebar-active' : '' ?>"><i class="fas fa-id-card w-5"></i><span>Identity Cards</span></a></li>
                    <li><a href="/admin/student-id-cards/" class="sidebar-item flex items-center space-x-3 p-3 rounded-lg <?= ($activePage ?? '') === 'student-id-cards' ? 'sidebar-active' : '' ?>"><i class="fas fa-user-graduate w-5"></i><span>Student ID Cards</span></a></li>
                    <li><a href="/admin/transport/" class="sidebar-item flex items-center space-x-3 p-3 rounded-lg <?= ($activePage ?? '') === 'transport' ? 'sidebar-active' : '' ?>"><i class="fas fa-bus w-5"></i><span>Transport</span></a></li>
                </ul>
            </nav>
        </aside>
        <!-- Main Content Area starts here in each page --> 