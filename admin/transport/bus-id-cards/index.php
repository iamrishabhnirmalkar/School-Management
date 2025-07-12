<?php
session_start();
require_once '../../../config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../../../logout.php");
    exit;
}

// Get all buses for dropdown
$buses = $conn->query("
    SELECT id, bus_number, route_name, driver_name, driver_phone, registration_number, model, year
    FROM buses 
    ORDER BY bus_number
")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <title>Bus Driver ID Card Generator</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>

<body class="bg-gray-50">
    <!-- Header -->
    <header class="bg-blue-700 text-white shadow-md">
        <div class="container mx-auto px-6 py-4">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-4">
                    <img src="../../../assets/img/logo/logo.png" alt="Logo" class="w-10 h-10">
                    <div>
                        <h1 class="text-2xl font-bold">School ERP System</h1>
                        <p class="text-blue-200">Bus Driver ID Card Generator</p>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="relative group">
                        <div class="flex items-center space-x-2 cursor-pointer">
                            <img src="../../../assets/img/admin-avatar.jpg" alt="Admin" class="w-8 h-8 rounded-full border-2 border-white">
                            <span><?= htmlspecialchars($_SESSION['user']['full_name']) ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <div class="container mx-auto px-6 py-8">
        <div class="max-w-4xl mx-auto">
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-2xl font-bold text-center mb-6 text-gray-800">Bus Driver ID Card Generator</h2>
                
                <!-- Tab Navigation -->
                <div class="flex border-b border-gray-200 mb-6">
                    <button class="tab-button py-2 px-4 border-b-2 font-medium text-sm border-blue-500 text-blue-600" data-tab="manual">
                        <i class="fas fa-edit mr-2"></i>Manual Entry
                    </button>
                    <button class="tab-button py-2 px-4 border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300" data-tab="bus">
                        <i class="fas fa-bus mr-2"></i>Select Bus
                    </button>
                </div>

                <!-- Manual Entry Form -->
                <div id="manual-tab" class="tab-content">
                    <form action="view.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="mode" value="manual">
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Driver Name *</label>
                                <input type="text" name="driver_name" class="w-full px-3 py-2 border border-gray-300 rounded-md" placeholder="Enter driver name" required>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Bus Number *</label>
                                <input type="text" name="bus_number" class="w-full px-3 py-2 border border-gray-300 rounded-md" placeholder="Enter bus number" required>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Route Name *</label>
                                <input type="text" name="route_name" class="w-full px-3 py-2 border border-gray-300 rounded-md" placeholder="Enter route name" required>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Driver ID *</label>
                                <input type="text" name="driver_id" class="w-full px-3 py-2 border border-gray-300 rounded-md" placeholder="Enter driver ID" required>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number *</label>
                                <input type="tel" name="driver_phone" class="w-full px-3 py-2 border border-gray-300 rounded-md" placeholder="Enter phone number" required>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">License Number *</label>
                                <input type="text" name="license_number" class="w-full px-3 py-2 border border-gray-300 rounded-md" placeholder="Enter license number" required>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Vehicle Registration *</label>
                                <input type="text" name="registration_number" class="w-full px-3 py-2 border border-gray-300 rounded-md" placeholder="Enter vehicle registration" required>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Signature *</label>
                                <input type="text" name="signature" class="w-full px-3 py-2 border border-gray-300 rounded-md" placeholder="Enter signature name" required>
                            </div>
                        </div>

                        <div class="mt-6">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Address *</label>
                            <textarea name="address" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md" placeholder="Enter complete address" required></textarea>
                        </div>

                        <div class="mt-6">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Upload Photo *</label>
                            <input type="file" name="file" accept="image/*" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
                        </div>

                        <div class="mt-6 flex justify-center">
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-md">
                                <i class="fas fa-id-card mr-2"></i>Generate Bus ID Card
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Bus Selection Form -->
                <div id="bus-tab" class="tab-content hidden">
                    <form action="view.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="mode" value="bus">
                        
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Select Bus *</label>
                            <select name="bus_id" class="w-full px-3 py-2 border border-gray-300 rounded-md" required onchange="loadBusData()">
                                <option value="">Choose a bus...</option>
                                <?php foreach ($buses as $bus): ?>
                                    <option value="<?= $bus['id'] ?>" 
                                            data-driver="<?= htmlspecialchars($bus['driver_name'] ?? '') ?>"
                                            data-bus="<?= htmlspecialchars($bus['bus_number']) ?>"
                                            data-route="<?= htmlspecialchars($bus['route_name']) ?>"
                                            data-phone="<?= htmlspecialchars($bus['driver_phone'] ?? '') ?>"
                                            data-registration="<?= htmlspecialchars($bus['registration_number'] ?? '') ?>"
                                            data-model="<?= htmlspecialchars($bus['model'] ?? '') ?>"
                                            data-year="<?= htmlspecialchars($bus['year'] ?? '') ?>">
                                        <?= htmlspecialchars($bus['bus_number']) ?> - <?= htmlspecialchars($bus['route_name']) ?> (<?= htmlspecialchars($bus['driver_name'] ?? 'No Driver') ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div id="bus-preview" class="hidden bg-gray-50 rounded-lg p-4 mb-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Bus Information</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Driver Name</label>
                                    <input type="text" name="driver_name" id="bus_driver" class="w-full px-3 py-2 border border-gray-300 rounded-md bg-white" readonly>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Bus Number</label>
                                    <input type="text" name="bus_number" id="bus_number" class="w-full px-3 py-2 border border-gray-300 rounded-md bg-white" readonly>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Route Name</label>
                                    <input type="text" name="route_name" id="bus_route" class="w-full px-3 py-2 border border-gray-300 rounded-md bg-white" readonly>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                                    <input type="text" name="driver_phone" id="bus_phone" class="w-full px-3 py-2 border border-gray-300 rounded-md bg-white" readonly>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Registration Number</label>
                                    <input type="text" name="registration_number" id="bus_registration" class="w-full px-3 py-2 border border-gray-300 rounded-md bg-white" readonly>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Vehicle Model</label>
                                    <input type="text" name="model" id="bus_model" class="w-full px-3 py-2 border border-gray-300 rounded-md bg-white" readonly>
                                </div>
                            </div>
                            <div class="mt-4">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Driver ID</label>
                                <input type="text" name="driver_id" class="w-full px-3 py-2 border border-gray-300 rounded-md" placeholder="Enter driver ID" required>
                            </div>
                            <div class="mt-4">
                                <label class="block text-sm font-medium text-gray-700 mb-1">License Number</label>
                                <input type="text" name="license_number" class="w-full px-3 py-2 border border-gray-300 rounded-md" placeholder="Enter license number" required>
                            </div>
                            <div class="mt-4">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                                <textarea name="address" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-md" placeholder="Enter driver address" required></textarea>
                            </div>
                            <div class="mt-4">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Signature</label>
                                <input type="text" name="signature" class="w-full px-3 py-2 border border-gray-300 rounded-md" placeholder="Enter signature name" required>
                            </div>
                            <div class="mt-4">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Photo</label>
                                <input type="file" name="file" accept="image/*" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
                                <p class="text-xs text-gray-500 mt-1">Upload driver photo</p>
                            </div>
                        </div>

                        <div class="mt-6 flex justify-center">
                            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-md">
                                <i class="fas fa-id-card mr-2"></i>Generate Bus ID Card
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Tab switching functionality
        document.querySelectorAll('.tab-button').forEach(button => {
            button.addEventListener('click', () => {
                // Remove active state from all tabs
                document.querySelectorAll('.tab-button').forEach(btn => {
                    btn.classList.remove('border-blue-500', 'text-blue-600');
                    btn.classList.add('border-transparent', 'text-gray-500');
                });

                // Hide all tab contents
                document.querySelectorAll('.tab-content').forEach(content => {
                    content.classList.add('hidden');
                });

                // Activate current tab
                button.classList.remove('border-transparent', 'text-gray-500');
                button.classList.add('border-blue-500', 'text-blue-600');

                // Show current tab content
                const tabId = button.getAttribute('data-tab') + '-tab';
                document.getElementById(tabId).classList.remove('hidden');
            });
        });

        // Load bus data when selected
        function loadBusData() {
            const select = document.querySelector('select[name="bus_id"]');
            const preview = document.getElementById('bus-preview');
            const selectedOption = select.options[select.selectedIndex];
            
            if (select.value) {
                // Populate form fields
                document.getElementById('bus_driver').value = selectedOption.getAttribute('data-driver');
                document.getElementById('bus_number').value = selectedOption.getAttribute('data-bus');
                document.getElementById('bus_route').value = selectedOption.getAttribute('data-route');
                document.getElementById('bus_phone').value = selectedOption.getAttribute('data-phone');
                document.getElementById('bus_registration').value = selectedOption.getAttribute('data-registration');
                document.getElementById('bus_model').value = selectedOption.getAttribute('data-model');
                
                preview.classList.remove('hidden');
            } else {
                preview.classList.add('hidden');
            }
        }
    </script>
</body>

</html> 