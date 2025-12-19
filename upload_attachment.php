<?php
include 'partials/connection.php';

if (!isset($_FILES['file'])) {
    echo json_encode(["success" => false, "error" => "No file"]);
    exit;
}

$sheet = intval($_POST['sheet_id']);
$row   = intval($_POST['row_number']);

$uploadDir = "uploads/sheet_attachments/";
if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

$original = basename($_FILES['file']['name']);
$tmp = $_FILES['file']['tmp_name'];
$size = $_FILES['file']['size'];
$type = mime_content_type($tmp);

$filename = time() . "_" . preg_replace("/[^a-zA-Z0-9._-]/", "_", $original);
$path = $uploadDir . $filename;

if (!move_uploaded_file($tmp, $path)) {
    echo json_encode(["success" => false, "error" => "Upload failed"]);
    exit;
}

$stmt = $conn->prepare("
    INSERT INTO sheet_attachments
    (sheet_id, row_number, original_name, file_path, file_size, mime_type)
    VALUES (?, ?, ?, ?, ?, ?)
");

$stmt->bind_param(
    "iissis",
    $sheet,
    $row,
    $original,
    $path,
    $size,
    $type
);

$stmt->execute();

echo json_encode(["success" => true]);
