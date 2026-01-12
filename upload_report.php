<?php
require 'config/db.php';
require 'config/auth.php'; // ensures user is logged in

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $allowed = ['pdf', 'doc', 'docx'];
    $file = $_FILES['report'];

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($ext, $allowed)) {
        die("Invalid file type. Only PDF and Word files are allowed.");
    }

    // Get the month/year from form
    $year  = $_POST['year'];
    $month = str_pad($_POST['month'], 2, '0', STR_PAD_LEFT);

    // Folder structure: uploads/2025/01/
    $dir = "uploads/$year/$month/";

    // Automatically create folder if it doesn't exist
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true); // recursive creation
    }

    // Create unique filename to prevent overwriting
    $newName = time() . "_" . basename($file['name']);
    $path = $dir . $newName;

    // Move uploaded file to folder
    if (!move_uploaded_file($file['tmp_name'], $path)) {
        die("Failed to move uploaded file.");
    }

    // Insert into database
    $stmt = $pdo->prepare("
        INSERT INTO reports 
        (report_title, file_name, file_type, file_size, local_path, report_month, report_year, uploaded_by)
        VALUES (?,?,?,?,?,?,?,?)
    ");

    $stmt->execute([
        $_POST['title'],
        $newName,
        $ext,
        $file['size'],
        $path,
        $month,
        $year,
        $_SESSION['user_id']
    ]);

    // Log activity
    $log = $pdo->prepare("
        INSERT INTO activity_logs (user_id, action, description)
        VALUES (?,?,?)
    ");
    $log->execute([
        $_SESSION['user_id'],
        'UPLOAD',
        'Uploaded report: ' . $_POST['title']
    ]);

    header("Location: dashboard.php?uploaded=1");
    exit;
}
?>
