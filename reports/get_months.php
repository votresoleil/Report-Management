<?php
require '../config/db.php';
require '../config/auth.php';

$year = $_GET['year'] ?? null;

if (!$year) {
    echo json_encode(['months' => []]);
    exit;
}

$basePath = "uploads/$year/";
$months = [];

if (is_dir($basePath)) {
    $items = scandir($basePath);
    $monthNames = [
        '01' => 'January', '02' => 'February', '03' => 'March', '04' => 'April',
        '05' => 'May', '06' => 'June', '07' => 'July', '08' => 'August',
        '09' => 'September', '10' => 'October', '11' => 'November', '12' => 'December'
    ];
    
    foreach ($items as $item) {
        if ($item != '.' && $item != '..' && is_dir($basePath . $item)) {
            // Check if it's a valid month folder (01-12)
            if (isset($monthNames[$item])) {
                // Count items in this month folder
                $count = countFiles($basePath . $item);
                $months[] = [
                    'month' => $item,
                    'name' => $monthNames[$item],
                    'count' => $count
                ];
            }
        }
    }
    
    // Sort by month number
    usort($months, function($a, $b) {
        return intval($a['month']) - intval($b['month']);
    });
}

function countFiles($dir) {
    $count = 0;
    if (is_dir($dir)) {
        $files = scandir($dir);
        foreach ($files as $file) {
            if ($file != '.' && $file != '..') {
                $count++;
            }
        }
    }
    return $count;
}

echo json_encode(['months' => $months]);
exit;
?>
