<?php
// admin/classes/add_timetable.php
session_start();
require_once '../../config.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../../logout.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $classId = $_POST['class_id'];
    $dayOfWeek = $_POST['day_of_week'];
    $subjectId = $_POST['subject_id'];
    $teacherId = $_POST['teacher_id'];
    $startTime = $_POST['start_time'];
    $endTime = $_POST['end_time'];

    // Validate
    if (empty($dayOfWeek) || empty($subjectId) || empty($teacherId) || empty($startTime) || empty($endTime)) {
        $_SESSION['error'] = "All fields are required!";
    } else {
        // Check for time conflict
        $stmt = $conn->prepare("SELECT id FROM timetable 
                              WHERE class_id = ? AND day_of_week = ? 
                              AND ((start_time <= ? AND end_time > ?) OR (start_time < ? AND end_time >= ?))");
        $stmt->bind_param("isssss", $classId, $dayOfWeek, $startTime, $startTime, $endTime, $endTime);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $_SESSION['error'] = "There's a time conflict with another period!";
        } else {
            // Insert into database
            $stmt = $conn->prepare("INSERT INTO timetable (class_id, subject_id, teacher_id, day_of_week, start_time, end_time) 
                                  VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iiisss", $classId, $subjectId, $teacherId, $dayOfWeek, $startTime, $endTime);

            if ($stmt->execute()) {
                $_SESSION['success'] = "Timetable period added successfully!";
            } else {
                $_SESSION['error'] = "Error adding timetable period: " . $conn->error;
            }
        }
    }

    header("Location: view.php?id=$classId");
    exit;
}
