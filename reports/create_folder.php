<?php
require '../config/db.php';
require '../config/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $year = $_POST['year'];
    $month = str_pad($_POST['month'], 2, '0', STR_PAD_LEFT);
    $title = trim($_POST['title']);
    $path = trim($_POST['path'] ?? '');
    
    if (empty($title)) {
        die("Folder title is required.");
    }
    
    // Sanitize folder title
    $safeTitle = preg_replace('/[^A-Za-z0-9\-_ ]/', '_', $title);
    $safeTitle = preg_replace('/\s+/', ' ', $safeTitle);
    $safeTitle = trim($safeTitle);
    
    // Build the directory path
    $dir = "uploads/$year/$month/";
    
    // Handle optional subfolder path
    if (!empty($path)) {
        $safePath = preg_replace('/[^A-Za-z0-9\-_ \/]/', '_', $path);
        $safePath = preg_replace('/\s+/', ' ', $safePath);
        $safePath = trim($safePath, '/');
        $dir .= $safePath . '/';
    }
    
    $dir .= $safeTitle . '/';
    
    if (!is_dir($dir)) {
        if (mkdir($dir, 0777, true)) {
            // Log activity
            $log = $pdo->prepare("
                INSERT INTO activity_logs (user_id, action, description)
                VALUES (?,?,?)
            ");
            $log->execute([
                $_SESSION['user_id'],
                'CREATE_FOLDER',
                'Created folder: ' . $safeTitle . ' in ' . $year . '/' . $month . (!empty($path) ? '/' . $path : '')
            ]);
            
            $redirectPath = urlencode($safeTitle);
            if (!empty($path)) {
                $redirectPath = urlencode($path) . '/' . urlencode($safeTitle);
            }
            header("Location: report_folders.php?year=$year&month=$month&folder=$redirectPath&created=1");
            exit;
        } else {
            die("Failed to create folder.");
        }
    } else {
        die("Folder already exists.");
    }
}
?>
