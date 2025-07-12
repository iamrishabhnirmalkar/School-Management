<?php
session_start();
require_once '../../config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../../logout.php");
    exit;
}

// Handle deletion
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM time_slots WHERE id = $id");
    header("Location: time_slots.php");
    exit;
}

// Handle creation/update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $label = $_POST['label'];

    if ($id) {
        // Update existing time slot
        $stmt = $conn->prepare("UPDATE time_slots SET start_time = ?, end_time = ?, label = ? WHERE id = ?");
        $stmt->bind_param("sssi", $start_time, $end_time, $label, $id);
    } else {
        // Create new time slot
        $stmt = $conn->prepare("INSERT INTO time_slots (start_time, end_time, label) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $start_time, $end_time, $label);
    }
    $stmt->execute();
    header("Location: time_slots.php");
    exit;
}

// Fetch time slots
$time_slots = $conn->query("SELECT * FROM time_slots ORDER BY start_time ASC")->fetch_all(MYSQLI_ASSOC);

// Get time slot for editing
$edit_slot = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $edit_slot = $conn->query("SELECT * FROM time_slots WHERE id = $edit_id")->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Time Slots Management - School ERP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <header class="bg-blue-700 text-white shadow-md">
        <div class="container mx-auto px-6 py-4 flex justify-between items-center">
            <div class="flex items-center space-x-4">
                <img src="../../assets/img/logo/logo.png" alt="Logo" class="w-10 h-10">
                <div>
                    <h1 class="text-2xl font-bold">School ERP System</h1>
                    <p class="text-blue-200">Time Slots Management</p>
                </div>
            </div>
            <div class="flex items-center space-x-4">
                <a href="index.php" class="bg-white text-blue-700 px-4 py-2 rounded-lg font-semibold hover:bg-blue-50 transition">
                    <i class="fas fa-arrow-left mr-1"></i>Back to Timetable
                </a>
                <span><?= htmlspecialchars($_SESSION['user']['full_name']) ?></span>
            </div>
        </div>
    </header>
    
    <div class="container mx-auto px-6 py-8">
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h2 class="text-xl font-bold mb-4 text-gray-800">
                <?= $edit_slot ? 'Edit Time Slot' : 'Add New Time Slot' ?>
            </h2>
            <form method="POST" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <?php if ($edit_slot): ?>
                    <input type="hidden" name="id" value="<?= $edit_slot['id'] ?>">
                <?php endif; ?>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Label *</label>
                    <input type="text" name="label" value="<?= $edit_slot ? htmlspecialchars($edit_slot['label']) : '' ?>" 
                           placeholder="e.g., Period 1" required 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md">
                    <p class="text-xs text-gray-500 mt-1">This will be displayed as: "Period 1 (08:00-08:45)"</p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Start Time *</label>
                    <input type="time" name="start_time" value="<?= $edit_slot ? $edit_slot['start_time'] : '' ?>" 
                           required class="w-full px-3 py-2 border border-gray-300 rounded-md">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">End Time *</label>
                    <input type="time" name="end_time" value="<?= $edit_slot ? $edit_slot['end_time'] : '' ?>" 
                           required class="w-full px-3 py-2 border border-gray-300 rounded-md">
                </div>
                
                <div class="flex items-end">
                    <button type="submit" class="w-full px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                        <?= $edit_slot ? 'Update' : 'Add' ?> Time Slot
                    </button>
                </div>
            </form>
            
            <?php if ($edit_slot): ?>
                <div class="mt-4">
                    <a href="time_slots.php" class="text-blue-600 hover:text-blue-800">
                        <i class="fas fa-times mr-1"></i>Cancel Edit
                    </a>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-bold mb-4 text-gray-800">Time Slots</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left">Label</th>
                            <th class="px-4 py-2 text-left">Start Time</th>
                            <th class="px-4 py-2 text-left">End Time</th>
                            <th class="px-4 py-2 text-left">Duration</th>
                            <th class="px-4 py-2 text-left">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($time_slots)): ?>
                            <tr><td colspan="5" class="px-4 py-4 text-center text-gray-500">No time slots found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($time_slots as $slot): ?>
                                <tr>
                                    <td class="px-4 py-2 font-medium">
                                        <?= htmlspecialchars($slot['label']) ?> 
                                        <span class="text-gray-500 text-sm">
                                            (<?= date('H:i', strtotime($slot['start_time'])) ?>-<?= date('H:i', strtotime($slot['end_time'])) ?>)
                                        </span>
                                    </td>
                                    <td class="px-4 py-2"><?= date('H:i', strtotime($slot['start_time'])) ?></td>
                                    <td class="px-4 py-2"><?= date('H:i', strtotime($slot['end_time'])) ?></td>
                                    <td class="px-4 py-2">
                                        <?php
                                        $start = new DateTime($slot['start_time']);
                                        $end = new DateTime($slot['end_time']);
                                        $duration = $start->diff($end);
                                        echo $duration->format('%H:%I');
                                        ?>
                                    </td>
                                    <td class="px-4 py-2">
                                        <a href="?edit=<?= $slot['id'] ?>" class="text-blue-600 hover:text-blue-900 mr-3">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <a href="?delete=<?= $slot['id'] ?>" class="text-red-600 hover:text-red-900" 
                                           onclick="return confirm('Delete this time slot? This will affect all timetables.')">
                                            <i class="fas fa-trash"></i> Delete
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html> 