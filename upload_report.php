<?php
require 'config/db.php';
require 'config/auth.php'; 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $allowed = ['pdf', 'doc', 'docx', 'pptx', 'pub', 'jpg', 'jpeg', 'png', 'gif', 'bmp', 'tiff', 'svg'];
    $file = $_FILES['report'];

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($ext, $allowed)) {
        die("Invalid file type. Only PDF and Word files are allowed.");
    }

    
    $year  = $_POST['year'];
    $month = str_pad($_POST['month'], 2, '0', STR_PAD_LEFT);
    $day = str_pad($_POST['day'], 2, '0', STR_PAD_LEFT);

   
    $dir = "uploads/$year/$month/$day/";

    
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true); 
    }

    
    $newName = time() . "_" . basename($file['name']);
    $path = $dir . $newName;

    if (!move_uploaded_file($file['tmp_name'], $path)) {
        die("Failed to move uploaded file.");
    }

  
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
