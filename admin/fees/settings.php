<?php
session_start();
require_once '../../config.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../../logout.php");
    exit;
}

// Handle settings updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_fee_types'])) {
        $feeTypes = $_POST['fee_types'] ?? [];
        $feeTypes = array_filter($feeTypes); // Remove empty values
        
        // Store in session for now (in a real app, you'd store in database)
        $_SESSION['fee_types'] = $feeTypes;
        $_SESSION['success'] = "Fee types updated successfully";
        header("Location: settings.php");
        exit;
    }
    
    if (isset($_POST['update_payment_methods'])) {
        $paymentMethods = $_POST['payment_methods'] ?? [];
        $paymentMethods = array_filter($paymentMethods); // Remove empty values
        
        // Store in session for now (in a real app, you'd store in database)
        $_SESSION['payment_methods'] = $paymentMethods;
        $_SESSION['success'] = "Payment methods updated successfully";
        header("Location: settings.php");
        exit;
    }
    
    if (isset($_POST['update_general_settings'])) {
        $settings = [
            'currency_symbol' => $_POST['currency_symbol'] ?? '₹',
            'default_due_days' => $_POST['default_due_days'] ?? 30,
            'late_fee_percentage' => $_POST['late_fee_percentage'] ?? 5,
            'send_reminders' => isset($_POST['send_reminders']),
            'auto_reminder_days' => $_POST['auto_reminder_days'] ?? 7
        ];
        
        // Store in session for now (in a real app, you'd store in database)
        $_SESSION['fee_settings'] = $settings;
        $_SESSION['success'] = "General settings updated successfully";
        header("Location: settings.php");
        exit;
    }
}

// Get current settings
$feeTypes = $_SESSION['fee_types'] ?? [
    'Tuition Fee',
    'Admission Fee',
    'Exam Fee',
    'Transport Fee',
    'Library Fee',
    'Laboratory Fee',
    'Sports Fee',
    'Computer Fee',
    'Other'
];

$paymentMethods = $_SESSION['payment_methods'] ?? [
    'Cash',
    'Cheque',
    'Online Transfer',
    'Card',
    'UPI'
];

$settings = $_SESSION['fee_settings'] ?? [
    'currency_symbol' => '₹',
    'default_due_days' => 30,
    'late_fee_percentage' => 5,
    'send_reminders' => true,
    'auto_reminder_days' => 7
];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fee Settings - School ERP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>

<body class="bg-gray-50">
    <!-- Header -->
    <header class="bg-blue-700 text-white shadow-md">
        <div class="container mx-auto px-6 py-4">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-4">
                    <img src="../../assets/img/logo/logo.png" alt="Logo" class="w-10 h-10">
                    <div>
                        <h1 class="text-2xl font-bold">School ERP System</h1>
                        <p class="text-blue-200">Fee Settings</p>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="relative group">
                        <div class="flex items-center space-x-2 cursor-pointer">
                            <img src="../../assets/img/admin-avatar.jpg" alt="Admin" class="w-8 h-8 rounded-full border-2 border-white">
                            <span><?= htmlspecialchars($_SESSION['user']['full_name']) ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <div class="container mx-auto px-6 py-8 flex">
        <!-- Sidebar Navigation -->
        <aside class="w-64 flex-shrink-0">
            <nav class="bg-white rounded-lg shadow-md p-4 sticky top-4">
                <ul class="space-y-2">
                    <li>
                        <a href="../dashboard.php" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-blue-50">
                            <i class="fas fa-tachometer-alt w-5"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="index.php" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-blue-50">
                            <i class="fas fa-money-bill-wave w-5"></i>
                            <span>Fee Overview</span>
                        </a>
                    </li>
                    <li>
                        <a href="collect.php" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-blue-50">
                            <i class="fas fa-cash-register w-5"></i>
                            <span>Collect Fee</span>
                        </a>
                    </li>
                    <li>
                        <a href="structure.php" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-blue-50">
                            <i class="fas fa-list-alt w-5"></i>
                            <span>Fee Structure</span>
                        </a>
                    </li>
                    <li>
                        <a href="reports.php" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-blue-50">
                            <i class="fas fa-chart-bar w-5"></i>
                            <span>Reports</span>
                        </a>
                    </li>
                    <li>
                        <a href="settings.php" class="flex items-center space-x-3 p-3 rounded-lg bg-blue-50 text-blue-700">
                            <i class="fas fa-cog w-5"></i>
                            <span>Settings</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <!-- Main Content Area -->
        <main class="flex-1 ml-8">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-gray-800">Fee Settings</h2>
            </div>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    <?= $_SESSION['success'];
                    unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?= $_SESSION['error'];
                    unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <!-- Settings Tabs -->
            <div class="bg-white rounded-lg shadow-md">
                <div class="border-b border-gray-200">
                    <nav class="-mb-px flex space-x-8 px-6" aria-label="Tabs">
                        <button onclick="showTab('general')" class="tab-button border-b-2 border-blue-500 text-blue-600 py-4 px-1 text-sm font-medium">
                            General Settings
                        </button>
                        <button onclick="showTab('fee-types')" class="tab-button border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 py-4 px-1 text-sm font-medium">
                            Fee Types
                        </button>
                        <button onclick="showTab('payment-methods')" class="tab-button border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 py-4 px-1 text-sm font-medium">
                            Payment Methods
                        </button>
                    </nav>
                </div>

                <!-- General Settings Tab -->
                <div id="general-tab" class="tab-content p-6">
                    <form method="POST">
                        <input type="hidden" name="update_general_settings" value="1">
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="currency_symbol" class="block text-sm font-medium text-gray-700 mb-1">Currency Symbol</label>
                                <input type="text" id="currency_symbol" name="currency_symbol" value="<?= htmlspecialchars($settings['currency_symbol']) ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            
                            <div>
                                <label for="default_due_days" class="block text-sm font-medium text-gray-700 mb-1">Default Due Days</label>
                                <input type="number" id="default_due_days" name="default_due_days" value="<?= $settings['default_due_days'] ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <p class="text-xs text-gray-500 mt-1">Number of days from creation to due date</p>
                            </div>
                            
                            <div>
                                <label for="late_fee_percentage" class="block text-sm font-medium text-gray-700 mb-1">Late Fee Percentage</label>
                                <input type="number" id="late_fee_percentage" name="late_fee_percentage" step="0.1" value="<?= $settings['late_fee_percentage'] ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <p class="text-xs text-gray-500 mt-1">Percentage added to overdue fees</p>
                            </div>
                            
                            <div>
                                <label for="auto_reminder_days" class="block text-sm font-medium text-gray-700 mb-1">Auto Reminder Days</label>
                                <input type="number" id="auto_reminder_days" name="auto_reminder_days" value="<?= $settings['auto_reminder_days'] ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <p class="text-xs text-gray-500 mt-1">Days before due date to send reminders</p>
                            </div>
                        </div>
                        
                        <div class="mt-6">
                            <label class="flex items-center">
                                <input type="checkbox" name="send_reminders" <?= $settings['send_reminders'] ? 'checked' : '' ?>
                                       class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <span class="ml-2 text-sm text-gray-700">Send automatic reminders for overdue fees</span>
                            </label>
                        </div>
                        
                        <div class="mt-6">
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
                                <i class="fas fa-save mr-2"></i> Save General Settings
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Fee Types Tab -->
                <div id="fee-types-tab" class="tab-content p-6 hidden">
                    <form method="POST">
                        <input type="hidden" name="update_fee_types" value="1">
                        
                        <div class="mb-4">
                            <h3 class="text-lg font-medium text-gray-900 mb-2">Fee Types</h3>
                            <p class="text-sm text-gray-600">Manage the types of fees that can be assigned to students</p>
                        </div>
                        
                        <div id="fee-types-container">
                            <?php foreach ($feeTypes as $index => $feeType): ?>
                                <div class="fee-type-row flex items-center space-x-2 mb-2">
                                    <input type="text" name="fee_types[]" value="<?= htmlspecialchars($feeType) ?>"
                                           class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <button type="button" onclick="removeFeeType(this)" class="text-red-600 hover:text-red-900">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="mt-4">
                            <button type="button" onclick="addFeeType()" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md">
                                <i class="fas fa-plus mr-2"></i> Add Fee Type
                            </button>
                        </div>
                        
                        <div class="mt-6">
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
                                <i class="fas fa-save mr-2"></i> Save Fee Types
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Payment Methods Tab -->
                <div id="payment-methods-tab" class="tab-content p-6 hidden">
                    <form method="POST">
                        <input type="hidden" name="update_payment_methods" value="1">
                        
                        <div class="mb-4">
                            <h3 class="text-lg font-medium text-gray-900 mb-2">Payment Methods</h3>
                            <p class="text-sm text-gray-600">Manage the payment methods available for fee collection</p>
                        </div>
                        
                        <div id="payment-methods-container">
                            <?php foreach ($paymentMethods as $index => $paymentMethod): ?>
                                <div class="payment-method-row flex items-center space-x-2 mb-2">
                                    <input type="text" name="payment_methods[]" value="<?= htmlspecialchars($paymentMethod) ?>"
                                           class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <button type="button" onclick="removePaymentMethod(this)" class="text-red-600 hover:text-red-900">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="mt-4">
                            <button type="button" onclick="addPaymentMethod()" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md">
                                <i class="fas fa-plus mr-2"></i> Add Payment Method
                            </button>
                        </div>
                        
                        <div class="mt-6">
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
                                <i class="fas fa-save mr-2"></i> Save Payment Methods
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script>
        function showTab(tabName) {
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.add('hidden');
            });
            
            // Remove active class from all tab buttons
            document.querySelectorAll('.tab-button').forEach(button => {
                button.classList.remove('border-blue-500', 'text-blue-600');
                button.classList.add('border-transparent', 'text-gray-500');
            });
            
            // Show selected tab content
            document.getElementById(tabName + '-tab').classList.remove('hidden');
            
            // Add active class to selected tab button
            event.target.classList.remove('border-transparent', 'text-gray-500');
            event.target.classList.add('border-blue-500', 'text-blue-600');
        }

        function addFeeType() {
            const container = document.getElementById('fee-types-container');
            const newRow = document.createElement('div');
            newRow.className = 'fee-type-row flex items-center space-x-2 mb-2';
            newRow.innerHTML = `
                <input type="text" name="fee_types[]" value=""
                       class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                <button type="button" onclick="removeFeeType(this)" class="text-red-600 hover:text-red-900">
                    <i class="fas fa-trash"></i>
                </button>
            `;
            container.appendChild(newRow);
        }

        function removeFeeType(button) {
            button.parentElement.remove();
        }

        function addPaymentMethod() {
            const container = document.getElementById('payment-methods-container');
            const newRow = document.createElement('div');
            newRow.className = 'payment-method-row flex items-center space-x-2 mb-2';
            newRow.innerHTML = `
                <input type="text" name="payment_methods[]" value=""
                       class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                <button type="button" onclick="removePaymentMethod(this)" class="text-red-600 hover:text-red-900">
                    <i class="fas fa-trash"></i>
                </button>
            `;
            container.appendChild(newRow);
        }

        function removePaymentMethod(button) {
            button.parentElement.remove();
        }
    </script>
</body>

</html> 