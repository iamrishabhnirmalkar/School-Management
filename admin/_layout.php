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
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) : 'Admin Panel - School ERP' ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .dashboard-card { transition: all 0.3s ease; }
        .dashboard-card:hover { transform: translateY(-3px); box-shadow: 0 10px 20px rgba(0,0,0,0.1); }
        .sidebar-item { transition: all 0.2s ease; }
        .sidebar-item:hover { background-color: #f3f4f6; border-left: 4px solid #3b82f6; }
        .sidebar-active { background-color: #e0e7ff; color: #1d4ed8; font-weight: bold; }
        .sidebar-overlay { transition: opacity 0.3s ease; }
        .mobile-sidebar { transform: translateX(-100%); transition: transform 0.3s ease; }
        .mobile-sidebar.open { transform: translateX(0); }
        .custom-scrollbar::-webkit-scrollbar { height: 8px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #e5e7eb; border-radius: 4px; }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Mobile Menu Button -->
    <button id="mobileMenuBtn" class="lg:hidden fixed top-4 left-4 z-50 bg-blue-700 text-white p-2 rounded-md shadow-lg">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Mobile Sidebar Overlay -->
    <div id="sidebarOverlay" class="lg:hidden fixed inset-0 bg-black bg-opacity-50 z-40 hidden sidebar-overlay"></div>

    <!-- Sidebar Navigation -->
    <aside id="sidebar" class="w-64 bg-white shadow-2xl border-r border-gray-200 fixed left-0 top-0 h-full z-30 mobile-sidebar lg:translate-x-0 flex flex-col">
        <div class="p-6 flex-1 flex flex-col">
            <!-- Mobile Close Button -->
            <button id="closeSidebarBtn" class="lg:hidden absolute top-4 right-4 text-gray-500 hover:text-gray-700">
                <i class="fas fa-times text-xl"></i>
            </button>
            <div class="mb-8 mt-8 lg:mt-0">
                <h2 class="text-xl font-bold text-gray-800">Admin Panel</h2>
                <p class="text-sm text-gray-600">School ERP System</p>
            </div>
            <nav class="flex-1">
                <ul class="space-y-1">
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
                    <li><a href="/admin/transport/" class="sidebar-item flex items-center space-x-3 p-3 rounded-lg <?= ($activePage ?? '') === 'transport' ? 'sidebar-active' : '' ?>"><i class="fas fa-bus w-5"></i><span>Transport</span></a></li>
                    <li><a href="/admin/notices/" class="sidebar-item flex items-center space-x-3 p-3 rounded-lg <?= ($activePage ?? '') === 'notices' ? 'sidebar-active' : '' ?>"><i class="fas fa-bullhorn w-5"></i><span>Notices</span></a></li>
                    <li><a href="/admin/reports/" class="sidebar-item flex items-center space-x-3 p-3 rounded-lg <?= ($activePage ?? '') === 'reports' ? 'sidebar-active' : '' ?>"><i class="fas fa-chart-bar w-5"></i><span>Reports</span></a></li>
                </ul>
            </nav>
        </div>
    </aside>

    <!-- Header -->
    <header class="bg-blue-700 text-white shadow-md fixed top-0 right-0 left-0 lg:left-64 z-20">
        <div class="px-4 lg:px-6 py-3 lg:py-4">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-2 lg:space-x-4">
                    <img src="../assets/images/logo.png" alt="Logo" class="w-8 h-8 lg:w-10 lg:h-10">
                    <div class="hidden sm:block">
                        <h1 class="text-lg lg:text-2xl font-bold">School ERP System</h1>
                        <p class="text-blue-200 text-sm"><?= isset($pageTitle) ? htmlspecialchars($pageTitle) : 'Admin Panel' ?></p>
                    </div>
                </div>
                <div class="flex items-center space-x-2 lg:space-x-4">
                    <div class="relative">
                        <button id="userMenuButton" class="flex items-center space-x-2 cursor-pointer focus:outline-none">
                            <img src="../assets/img/admin-avatar.jpg" alt="Admin" class="w-6 h-6 lg:w-8 lg:h-8 rounded-full border-2 border-white">
                            <span class="text-sm hidden sm:inline"><?= htmlspecialchars($_SESSION['user']['full_name'] ?? 'Admin') ?></span>
                        </button>
                        <div id="userMenu" class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-10 hidden">
                            <a href="profile.php" class="block px-4 py-2 text-gray-800 hover:bg-blue-50"><i class="fas fa-user mr-2"></i>Profile</a>
                            <a href="/logout.php" class="block px-4 py-2 text-gray-800 hover:bg-blue-50"><i class="fas fa-sign-out-alt mr-2"></i>Logout</a>
                        </div>
                    </div>
                    <a href="/logout.php" class="bg-red-600 hover:bg-red-700 text-white px-3 lg:px-4 py-2 rounded flex items-center text-sm">
                        <i class="fas fa-sign-out-alt mr-1 lg:mr-2"></i>
                        <span class="hidden sm:inline">Logout</span>
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Layout Container -->
    <div class="min-h-screen bg-gray-50">
        <!-- Main Content Area -->
        <div class="lg:ml-64">
            <div class="p-4 lg:p-8 pt-16 lg:pt-24 custom-scrollbar overflow-x-auto">
                <!-- Main Content Area starts here in each page -->