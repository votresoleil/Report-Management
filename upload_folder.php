<?php
require 'config/db.php';
require 'config/auth.php';


ini_set('max_execution_time', 300); 
ini_set('memory_limit', '256M');
ini_set('upload_max_filesize', '100M');
ini_set('post_max_size', '200M');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $allowed = ['pdf', 'doc', 'docx', 'pptx', 'pub', 'jpg', 'jpeg', 'png', 'gif', 'bmp', 'tiff', 'svg'];
    $files = $_FILES['files'];


    $year  = $_POST['year'];
    $month = str_pad($_POST['month'], 2, '0', STR_PAD_LEFT);
    $folderTitle = trim($_POST['folder_title']);

    if (empty($folderTitle)) {
        die("Folder title is required.");
    }

   
    $safeFolderTitle = preg_replace('/[^A-Za-z0-9\-_]/', '_', $folderTitle);

    
    $dir = "uploads/$year/$month/$safeFolderTitle/";

    
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true); 
    }

    $uploadedCount = 0;
    $errors = [];

  
    if (count($files['name']) == 0) {
        die("No files received. Check if webkitdirectory is supported and folder was selected.");
    }

    
    $stmt = $pdo->prepare("
        INSERT INTO reports
        (report_title, file_name, file_type, file_size, local_path, report_month, report_year, uploaded_by)
        VALUES (?,?,?,?,?,?,?,?)
    ");

    
    for ($i = 0; $i < count($files['name']); $i++) {
        $fileName = $files['name'][$i];
        $fileTmp = $files['tmp_name'][$i];
        $fileSize = $files['size'][$i];
        $fileError = $files['error'][$i];

        if (empty($fileName)) continue;
        if ($fileError !== UPLOAD_ERR_OK) {
            $errors[] = "File '$fileName': Upload error code $fileError";
            continue;
        }

        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed)) {
            $errors[] = "File '$fileName': Invalid file type '$ext'";
            continue;
        }

        
        $newName = time() . "_" . $i . "_" . basename($fileName);
        $path = $dir . $newName;

        
        if (move_uploaded_file($fileTmp, $path)) {
            
            $stmt->execute([
                basename($fileName), 
                $newName,
                $ext,
                $fileSize,
                $path,
                $month,
                $year,
                $_SESSION['user_id']
            ]);

            $uploadedCount++;
        } else {
            $errors[] = "File '$fileName': Failed to move to $path";
        }
    }

    if ($uploadedCount > 0) {
      
        $log = $pdo->prepare("
            INSERT INTO activity_logs (user_id, action, description)
            VALUES (?,?,?)
        ");
        $log->execute([
            $_SESSION['user_id'],
            'UPLOAD_FOLDER',
            'Uploaded folder: ' . $folderTitle . ' (' . $uploadedCount . ' files) to ' . $year . '/' . $month
        ]);

        header("Location: report_folders.php?uploaded=1");
        exit;
    } else {
        $errorMsg = "No files were uploaded successfully.";
        if (!empty($errors)) {
            $errorMsg .= " Errors: " . implode('; ', $errors);
        }
        die($errorMsg);
    }
}
?>