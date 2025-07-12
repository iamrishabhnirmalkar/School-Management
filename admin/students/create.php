<?php
session_start();
require_once '../../config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../../logout.php");
    exit;
}

// Generate admission number
$last_admission = $conn->query("SELECT admission_number FROM users WHERE role='student' ORDER BY id DESC LIMIT 1")->fetch_assoc();
$last_number = $last_admission ? intval(substr($last_admission['admission_number'] ?? '', 3)) : 0;
$new_admission_number = 'ADM' . str_pad($last_number + 1, 5, '0', STR_PAD_LEFT);

// Get classes for dropdown
$classes = $conn->query("SELECT id, class_name, section FROM classes")->fetch_all(MYSQLI_ASSOC);

// Get available buses for dropdown
$buses = $conn->query("SELECT id, bus_number, route_name FROM buses")->fetch_all(MYSQLI_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $admission_number = $_POST['admission_number'] ?? $new_admission_number; // Use form value if provided
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $class_id = $_POST['class_id'];
    $roll_number = $_POST['roll_number'];
    $admission_date = $_POST['admission_date'];
    $gender = $_POST['gender'];
    $blood_group = $_POST['blood_group'];
    $parent_name = $_POST['parent_name'];
    $parent_phone = $_POST['parent_phone'];
    $address = $_POST['address'];
    $dob = $_POST['dob'];
    $bus_id = $_POST['bus_id'] ?? null;
    $stop_name = $_POST['stop_name'] ?? '';
    $pickup_time = $_POST['pickup_time'] ?? '';
    $drop_time = $_POST['drop_time'] ?? '';
    // Handle photo upload
    $photo_path = null;
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
        $photo_name = 'student_' . time() . '_' . rand(1000,9999) . '.' . $ext;
        $upload_dir = '../../uploads/student_photos/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        $target = $upload_dir . $photo_name;
        if (move_uploaded_file($_FILES['photo']['tmp_name'], $target)) {
            $photo_path = 'uploads/student_photos/' . $photo_name;
        }
    }

    // Start transaction
    $conn->begin_transaction();

    try {
        // Insert into users table
        $stmt = $conn->prepare("INSERT INTO users (admission_number, login_id, role, full_name, email, phone) VALUES (?, ?, 'student', ?, ?, ?)");
        $stmt->bind_param("sssss", $admission_number, $admission_number, $full_name, $email, $phone);
        $stmt->execute();
        $user_id = $stmt->insert_id;

        // Insert into students table (without bus_allocation_id initially)
        $stmt = $conn->prepare("INSERT INTO students (user_id, class_id, roll_number, admission_date, gender, blood_group, parent_name, parent_phone, address, dob, photo) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iisssssssss", $user_id, $class_id, $roll_number, $admission_date, $gender, $blood_group, $parent_name, $parent_phone, $address, $dob, $photo_path);
        $stmt->execute();

        // If bus is selected, create bus allocation
        if (!empty($bus_id) && !empty($stop_name)) {
            $stmt = $conn->prepare("INSERT INTO bus_allocations (bus_id, student_id, stop_name, pickup_time, drop_time, monthly_fee, payment_status, academic_year) VALUES (?, ?, ?, ?, ?, ?, 'unpaid', '2024-2025')");
            $monthly_fee = 1500.00; // Default fee
            $stmt->bind_param("iisssd", $bus_id, $user_id, $stop_name, $pickup_time, $drop_time, $monthly_fee);
            $stmt->execute();
            $bus_allocation_id = $stmt->insert_id;
            
            // Update student with bus_allocation_id
            $stmt = $conn->prepare("UPDATE students SET bus_allocation_id = ? WHERE user_id = ?");
            $stmt->bind_param("ii", $bus_allocation_id, $user_id);
            $stmt->execute();
        }

        $conn->commit();
        $_SESSION['success'] = "Student added successfully!";
        header("Location: index.php");
        exit;
    } catch (Exception $e) {
        $conn->rollback();
        $error = "Error adding student: " . $e->getMessage();
    }
}
?>

<?php
$pageTitle = 'Add New Student';
$activePage = 'students';
include '../_layout.php';
?>
        <!-- Main Content Area -->
        <main class="flex-1 ml-8">
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-bold mb-6 text-gray-800">Add New Student</h2>

                <?php if (isset($error)): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Student Information -->
                    <div class="space-y-4">
                        <h3 class="text-lg font-medium text-gray-900 border-b pb-2">Student Information</h3>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Admission Number</label>
                            <input type="text" name="admission_number" value="<?= isset($_POST['admission_number']) ? htmlspecialchars($_POST['admission_number']) : $new_admission_number ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Full Name *</label>
                            <input type="text" name="full_name" required class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Date of Birth</label>
                            <input type="date" name="dob" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Gender</label>
                            <select name="gender" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Blood Group</label>
                            <select name="blood_group" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                                <option value="">Select</option>
                                <option value="A+">A+</option>
                                <option value="A-">A-</option>
                                <option value="B+">B+</option>
                                <option value="B-">B-</option>
                                <option value="AB+">AB+</option>
                                <option value="AB-">AB-</option>
                                <option value="O+">O+</option>
                                <option value="O-">O-</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Photo</label>
                            <input type="file" name="photo" accept="image/*" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>
                    </div>

                    <!-- Academic Information -->
                    <div class="space-y-4">
                        <h3 class="text-lg font-medium text-gray-900 border-b pb-2">Academic Information</h3>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Class *</label>
                            <select name="class_id" required class="w-full px-3 py-2 border border-gray-300 rounded-md">
                                <option value="">Select Class</option>
                                <?php foreach ($classes as $class): ?>
                                    <option value="<?= $class['id'] ?>">
                                        <?= htmlspecialchars($class['class_name']) ?> - <?= htmlspecialchars($class['section']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Roll Number</label>
                            <input type="text" name="roll_number" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Admission Date</label>
                            <input type="date" name="admission_date" value="<?= date('Y-m-d') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Bus Assignment (if student uses school bus)</label>
                            <div class="space-y-3">
                                <div>
                                    <select name="bus_id" class="w-full px-3 py-2 border border-gray-300 rounded-md" onchange="toggleBusDetails()">
                                        <option value="">No Bus</option>
                                        <?php foreach ($buses as $bus): ?>
                                            <option value="<?= $bus['id'] ?>">
                                                <?= htmlspecialchars($bus['bus_number']) ?> (<?= htmlspecialchars($bus['route_name']) ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div id="bus_details" class="hidden space-y-3">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Stop Name</label>
                                        <input type="text" name="stop_name" class="w-full px-3 py-2 border border-gray-300 rounded-md" placeholder="e.g., Central Station">
                                    </div>
                                    <div class="grid grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Pickup Time</label>
                                            <input type="time" name="pickup_time" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Drop Time</label>
                                            <input type="time" name="drop_time" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Contact Information -->
                    <div class="space-y-4 md:col-span-2">
                        <h3 class="text-lg font-medium text-gray-900 border-b pb-2">Contact Information</h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                                <input type="email" name="email" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                                <input type="tel" name="phone" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                            <textarea name="address" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-md"></textarea>
                        </div>
                    </div>

                    <!-- Parent Information -->
                    <div class="space-y-4 md:col-span-2">
                        <h3 class="text-lg font-medium text-gray-900 border-b pb-2">Parent/Guardian Information</h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Parent Name</label>
                                <input type="text" name="parent_name" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Parent Phone</label>
                                <input type="tel" name="parent_phone" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                            </div>
                        </div>
                    </div>

                    <div class="md:col-span-2 flex justify-end space-x-4">
                        <a href="index.php" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">Cancel</a>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Save Student</button>
                    </div>
                </form>
            </div>
        </main>
    </div>
    <script>
        function toggleBusDetails() {
            const busSelect = document.querySelector('select[name="bus_id"]');
            const busDetails = document.getElementById('bus_details');
            
            if (busSelect.value) {
                busDetails.classList.remove('hidden');
            } else {
                busDetails.classList.add('hidden');
            }
        }
    </script>
</body>

</html>