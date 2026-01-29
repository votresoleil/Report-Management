<?php
$path = $_GET['path'] ?? '';

if (!$path || !file_exists($path)) {
    echo json_encode(['error' => 'File not found']);
    exit;
}

$ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
if ($ext !== 'docx') {
    echo json_encode(['error' => 'Not a DOCX file']);
    exit;
}

$dir = dirname($path);
$basename = pathinfo($path, PATHINFO_FILENAME);
$pdf_path = $dir . '/' . $basename . '.pdf';

if (!file_exists($pdf_path)) {
    $command = "\"C:\\Program Files\\LibreOffice\\program\\soffice.exe\" --headless --convert-to pdf \"$path\" --outdir \"$dir\"";
    exec($command, $output, $return_var);
    if ($return_var !== 0) {
        echo json_encode(['error' => 'Conversion failed']);
        exit;
    }
}

echo json_encode(['pdf_path' => $pdf_path]);
?>