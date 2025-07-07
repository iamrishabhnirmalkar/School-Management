<?php
require_once '../db.php';
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Helper: sanitize input
function clean($v) {
    return htmlspecialchars(trim($v));
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields = [
        'login_id', 'admission_no', 'name', 'middle_name', 'surname', 'email', 'phone',
        'father_name', 'father_middle_name', 'father_title',
        'mother_name', 'mother_middle_name', 'mother_title',
        'bus_no', 'travel_mode', 'travel_details', 'subject_list', 'fees',
        'gender', 'dob', 'class', 'section', 'address', 'admission_date',
        'blood_group', 'emergency_contact', 'library_id', 'certification', 'is_active'
    ];
    $data = [];
    foreach ($fields as $f) {
        $data[$f] = isset($_POST[$f]) ? clean($_POST[$f]) : null;
    }
    $data['is_active'] = isset($_POST['is_active']) ? intval($_POST['is_active']) : 1;
    $photo_filename = '';
    // Handle photo upload
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
        $photo_filename = uniqid('stu_') . '.' . $ext;
        move_uploaded_file($_FILES['photo']['tmp_name'], '../uploads/' . $photo_filename);
    }
    // If editing, update
    if (isset($_GET['id'])) {
        $id = intval($_GET['id']);
        // If new photo uploaded, update photo field
        $photo_sql = '';
        if ($photo_filename) {
            $photo_sql = ", photo = '" . $conn->real_escape_string($photo_filename) . "' ";
        }
        $sql = "UPDATE students SET
            admission_no = '{$data['admission_no']}',
            name = '{$data['name']}',
            middle_name = '{$data['middle_name']}',
            surname = '{$data['surname']}',
            email = '{$data['email']}',
            phone = '{$data['phone']}',
            father_name = '{$data['father_name']}',
            father_middle_name = '{$data['father_middle_name']}',
            father_title = '{$data['father_title']}',
            mother_name = '{$data['mother_name']}',
            mother_middle_name = '{$data['mother_middle_name']}',
            mother_title = '{$data['mother_title']}',
            bus_no = '{$data['bus_no']}',
            travel_mode = '{$data['travel_mode']}',
            travel_details = '{$data['travel_details']}',
            subject_list = '{$data['subject_list']}',
            fees = '{$data['fees']}',
            gender = '{$data['gender']}',
            dob = '{$data['dob']}',
            class = '{$data['class']}',
            section = '{$data['section']}',
            address = '{$data['address']}',
            admission_date = '{$data['admission_date']}',
            blood_group = '{$data['blood_group']}',
            emergency_contact = '{$data['emergency_contact']}',
            library_id = '{$data['library_id']}',
            certification = '{$data['certification']}',
            is_active = {$data['is_active']}
            $photo_sql
            WHERE id = $id";
        $conn->query($sql);
        $_SESSION['success'] = 'Student updated successfully!';
        header('Location: students.php');
        exit();
    } else {
        // Add new student
        $photo_col = $photo_filename ? ', photo' : '';
        $photo_val = $photo_filename ? ", '" . $conn->real_escape_string($photo_filename) . "'" : '';
        $sql = "INSERT INTO students (
            login_id, admission_no, name, middle_name, surname, email, phone,
            father_name, father_middle_name, father_title,
            mother_name, mother_middle_name, mother_title,
            bus_no, travel_mode, travel_details, subject_list, fees,
            gender, dob, class, section, address, admission_date,
            blood_group, emergency_contact, library_id, certification, is_active $photo_col
        ) VALUES (
            '{$data['login_id']}', '{$data['admission_no']}', '{$data['name']}', '{$data['middle_name']}', '{$data['surname']}', '{$data['email']}', '{$data['phone']}',
            '{$data['father_name']}', '{$data['father_middle_name']}', '{$data['father_title']}',
            '{$data['mother_name']}', '{$data['mother_middle_name']}', '{$data['mother_title']}',
            '{$data['bus_no']}', '{$data['travel_mode']}', '{$data['travel_details']}', '{$data['subject_list']}', '{$data['fees']}',
            '{$data['gender']}', '{$data['dob']}', '{$data['class']}', '{$data['section']}', '{$data['address']}', '{$data['admission_date']}',
            '{$data['blood_group']}', '{$data['emergency_contact']}', '{$data['library_id']}', '{$data['certification']}', {$data['is_active']} $photo_val
        )";
        $conn->query($sql);
        $_SESSION['success'] = 'Student added successfully!';
        header('Location: students.php');
        exit();
    }
}

// Initialize variables
$editing = false;
$student = [
    'login_id' => '', 'admission_no' => '', 'name' => '', 'middle_name' => '', 'surname' => '', 'email' => '', 'phone' => '',
    'father_name' => '', 'father_middle_name' => '', 'father_title' => 'Mr',
    'mother_name' => '', 'mother_middle_name' => '', 'mother_title' => 'Mrs',
    'bus_no' => '', 'travel_mode' => '', 'travel_details' => '', 'subject_list' => '', 'fees' => '',
    'gender' => '', 'dob' => '', 'class' => '', 'section' => '', 'address' => '', 'admission_date' => '',
    'blood_group' => '', 'emergency_contact' => '', 'photo' => '', 'library_id' => '', 'certification' => '', 'is_active' => 1
];

if (isset($_GET['id'])) {
    $editing = true;
    $id = intval($_GET['id']);
    $result = $conn->query("SELECT * FROM students WHERE id = $id");
    if ($result && $result->num_rows > 0) {
        $student = $result->fetch_assoc();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo $editing ? 'Edit' : 'Add'; ?> Student</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-blue-100 to-blue-300 min-h-screen flex items-center justify-center">
    <div class="w-full max-w-3xl bg-white rounded-xl shadow-lg p-8 my-8">
        <h1 class="text-2xl font-bold text-blue-800 mb-6"><?php echo $editing ? 'Edit' : 'Add'; ?> Student</h1>
        <form action="student_form.php<?php if($editing) echo '?id=' . $student['id']; ?>" method="POST" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block font-medium mb-1">Login ID</label>
                <input type="text" name="login_id" value="<?php echo htmlspecialchars($student['login_id']); ?>" required class="w-full border rounded px-3 py-2" <?php if($editing) echo 'readonly'; ?>>
            </div>
            <div>
                <label class="block font-medium mb-1">Admission No</label>
                <input type="text" name="admission_no" value="<?php echo htmlspecialchars($student['admission_no']); ?>" required class="w-full border rounded px-3 py-2">
            </div>
            <div>
                <label class="block font-medium mb-1">First Name</label>
                <input type="text" name="name" value="<?php echo htmlspecialchars($student['name']); ?>" required class="w-full border rounded px-3 py-2">
            </div>
            <div>
                <label class="block font-medium mb-1">Middle Name</label>
                <input type="text" name="middle_name" value="<?php echo htmlspecialchars($student['middle_name']); ?>" class="w-full border rounded px-3 py-2">
            </div>
            <div>
                <label class="block font-medium mb-1">Surname</label>
                <input type="text" name="surname" value="<?php echo htmlspecialchars($student['surname']); ?>" class="w-full border rounded px-3 py-2">
            </div>
            <div>
                <label class="block font-medium mb-1">Email</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($student['email']); ?>" class="w-full border rounded px-3 py-2">
            </div>
            <div>
                <label class="block font-medium mb-1">Phone Number</label>
                <input type="text" name="phone" value="<?php echo htmlspecialchars($student['phone']); ?>" class="w-full border rounded px-3 py-2">
            </div>
            <div class="md:col-span-2 grid grid-cols-2 gap-6">
                <div>
                    <label class="block font-medium mb-1">Father's Title</label>
                    <select name="father_title" class="w-full border rounded px-3 py-2">
                        <option value="Mr" <?php if($student['father_title']==='Mr') echo 'selected'; ?>>Mr</option>
                        <option value="Late" <?php if($student['father_title']==='Late') echo 'selected'; ?>>Late</option>
                    </select>
                </div>
                <div>
                    <label class="block font-medium mb-1">Father's Name</label>
                    <input type="text" name="father_name" value="<?php echo htmlspecialchars($student['father_name']); ?>" class="w-full border rounded px-3 py-2">
                </div>
                <div>
                    <label class="block font-medium mb-1">Father's Middle Name</label>
                    <input type="text" name="father_middle_name" value="<?php echo htmlspecialchars($student['father_middle_name']); ?>" class="w-full border rounded px-3 py-2">
                </div>
            </div>
            <div class="md:col-span-2 grid grid-cols-2 gap-6">
                <div>
                    <label class="block font-medium mb-1">Mother's Title</label>
                    <select name="mother_title" class="w-full border rounded px-3 py-2">
                        <option value="Mrs" <?php if($student['mother_title']==='Mrs') echo 'selected'; ?>>Mrs</option>
                        <option value="Miss" <?php if($student['mother_title']==='Miss') echo 'selected'; ?>>Miss</option>
                        <option value="Late" <?php if($student['mother_title']==='Late') echo 'selected'; ?>>Late</option>
                    </select>
                </div>
                <div>
                    <label class="block font-medium mb-1">Mother's Name</label>
                    <input type="text" name="mother_name" value="<?php echo htmlspecialchars($student['mother_name']); ?>" class="w-full border rounded px-3 py-2">
                </div>
                <div>
                    <label class="block font-medium mb-1">Mother's Middle Name</label>
                    <input type="text" name="mother_middle_name" value="<?php echo htmlspecialchars($student['mother_middle_name']); ?>" class="w-full border rounded px-3 py-2">
                </div>
            </div>
            <div>
                <label class="block font-medium mb-1">Bus Number</label>
                <input type="text" name="bus_no" value="<?php echo htmlspecialchars($student['bus_no']); ?>" class="w-full border rounded px-3 py-2">
            </div>
            <div>
                <label class="block font-medium mb-1">Travel Mode</label>
                <input type="text" name="travel_mode" value="<?php echo htmlspecialchars($student['travel_mode']); ?>" class="w-full border rounded px-3 py-2" placeholder="Bus, Auto, Other">
            </div>
            <div class="md:col-span-2">
                <label class="block font-medium mb-1">Travel Details</label>
                <textarea name="travel_details" class="w-full border rounded px-3 py-2"><?php echo htmlspecialchars($student['travel_details']); ?></textarea>
            </div>
            <div class="md:col-span-2">
                <label class="block font-medium mb-1">Subject List</label>
                <input type="text" name="subject_list" value="<?php echo htmlspecialchars($student['subject_list']); ?>" class="w-full border rounded px-3 py-2" placeholder="Comma separated subjects">
            </div>
            <div>
                <label class="block font-medium mb-1">Fees</label>
                <input type="number" step="0.01" name="fees" value="<?php echo htmlspecialchars($student['fees']); ?>" class="w-full border rounded px-3 py-2">
            </div>
            <div>
                <label class="block font-medium mb-1">Gender</label>
                <select name="gender" class="w-full border rounded px-3 py-2">
                    <option value="">Select</option>
                    <option value="Male" <?php if($student['gender']==='Male') echo 'selected'; ?>>Male</option>
                    <option value="Female" <?php if($student['gender']==='Female') echo 'selected'; ?>>Female</option>
                    <option value="Other" <?php if($student['gender']==='Other') echo 'selected'; ?>>Other</option>
                </select>
            </div>
            <div>
                <label class="block font-medium mb-1">Date of Birth</label>
                <input type="date" name="dob" value="<?php echo htmlspecialchars($student['dob']); ?>" class="w-full border rounded px-3 py-2">
            </div>
            <div>
                <label class="block font-medium mb-1">Class</label>
                <input type="text" name="class" value="<?php echo htmlspecialchars($student['class']); ?>" class="w-full border rounded px-3 py-2">
            </div>
            <div>
                <label class="block font-medium mb-1">Section</label>
                <input type="text" name="section" value="<?php echo htmlspecialchars($student['section']); ?>" class="w-full border rounded px-3 py-2">
            </div>
            <div class="md:col-span-2">
                <label class="block font-medium mb-1">Address</label>
                <textarea name="address" class="w-full border rounded px-3 py-2"><?php echo htmlspecialchars($student['address']); ?></textarea>
            </div>
            <div>
                <label class="block font-medium mb-1">Admission Date</label>
                <input type="date" name="admission_date" value="<?php echo htmlspecialchars($student['admission_date']); ?>" class="w-full border rounded px-3 py-2">
            </div>
            <div>
                <label class="block font-medium mb-1">Blood Group</label>
                <input type="text" name="blood_group" value="<?php echo htmlspecialchars($student['blood_group']); ?>" class="w-full border rounded px-3 py-2">
            </div>
            <div>
                <label class="block font-medium mb-1">Emergency Contact</label>
                <input type="text" name="emergency_contact" value="<?php echo htmlspecialchars($student['emergency_contact']); ?>" class="w-full border rounded px-3 py-2">
            </div>
            <div>
                <label class="block font-medium mb-1">Photo</label>
                <input type="file" name="photo" class="w-full border rounded px-3 py-2">
                <?php if($editing && $student['photo']): ?>
                    <img src="../uploads/<?php echo htmlspecialchars($student['photo']); ?>" alt="Student Photo" class="mt-2 w-20 h-20 object-cover rounded">
                <?php endif; ?>
            </div>
            <div>
                <label class="block font-medium mb-1">Library ID</label>
                <input type="text" name="library_id" value="<?php echo htmlspecialchars($student['library_id']); ?>" class="w-full border rounded px-3 py-2">
            </div>
            <div class="md:col-span-2">
                <label class="block font-medium mb-1">Certification</label>
                <textarea name="certification" class="w-full border rounded px-3 py-2"><?php echo htmlspecialchars($student['certification']); ?></textarea>
            </div>
            <div>
                <label class="block font-medium mb-1">Active</label>
                <select name="is_active" class="w-full border rounded px-3 py-2">
                    <option value="1" <?php if($student['is_active']) echo 'selected'; ?>>Active</option>
                    <option value="0" <?php if(!$student['is_active']) echo 'selected'; ?>>Inactive</option>
                </select>
            </div>
            <div class="md:col-span-2 flex justify-end gap-4">
                <a href="students.php" class="bg-gray-200 text-gray-700 px-6 py-2 rounded hover:bg-gray-300">Cancel</a>
                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded shadow hover:bg-blue-800 font-semibold"><?php echo $editing ? 'Update' : 'Add'; ?> Student</button>
            </div>
        </form>
    </div>
</body>
</html> 