<?php
require_once '../db.php';
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}
$admin_id = $_SESSION['user_id'];
// Fetch current admin info
$admin = $conn->query("SELECT * FROM admins WHERE id = $admin_id")->fetch_assoc();
// Handle form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = htmlspecialchars(trim($_POST['name']));
    $profile_img = $admin['profile_img'] ?? '';
    if (isset($_FILES['profile_img']) && $_FILES['profile_img']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['profile_img']['name'], PATHINFO_EXTENSION);
        $profile_img = uniqid('admin_') . '.' . $ext;
        move_uploaded_file($_FILES['profile_img']['tmp_name'], '../uploads/' . $profile_img);
    }
    $conn->query("UPDATE admins SET name = '$name', profile_img = '$profile_img' WHERE id = $admin_id");
    $_SESSION['name'] = $name;
    $_SESSION['success'] = 'Profile updated!';
    header('Location: dashboard.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Profile</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="w-full max-w-md bg-white rounded-xl shadow-lg p-8 my-8">
        <h1 class="text-2xl font-bold text-blue-800 mb-6">Edit Profile</h1>
        <form method="post" enctype="multipart/form-data" class="space-y-6">
            <div class="flex flex-col items-center">
                <?php if (!empty($admin['profile_img'])): ?>
                    <img src="../uploads/<?php echo htmlspecialchars($admin['profile_img']); ?>" alt="Profile" class="w-24 h-24 rounded-full object-cover mb-2">
                <?php else: ?>
                    <div class="w-24 h-24 rounded-full bg-blue-100 flex items-center justify-center mb-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 15c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                    </div>
                <?php endif; ?>
                <input type="file" name="profile_img" class="mt-2">
            </div>
            <div>
                <label class="block font-medium mb-1">Name</label>
                <input type="text" name="name" value="<?php echo htmlspecialchars($admin['name']); ?>" required class="w-full border rounded px-3 py-2">
            </div>
            <div>
                <label class="block font-medium mb-1">Role</label>
                <input type="text" value="Admin" readonly class="w-full border rounded px-3 py-2 bg-gray-100">
            </div>
            <div class="flex justify-end gap-4">
                <a href="dashboard.php" class="bg-gray-200 text-gray-700 px-6 py-2 rounded hover:bg-gray-300">Cancel</a>
                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded shadow hover:bg-blue-800 font-semibold">Save</button>
            </div>
        </form>
    </div>
</body>
</html> 