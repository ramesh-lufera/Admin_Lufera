<?php
include 'partials/connection.php';
include 'partials/check_login.php';

// Make sure user is logged in
if (!isset($_SESSION['user_id']) || !is_numeric($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["success" => false, "error" => "Not authenticated"]);
    exit;
}

$created_by = (int) $_SESSION['user_id'];

if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(["success" => false, "error" => "No valid file uploaded"]);
    exit;
}

$sheet = intval($_POST['sheet_id'] ?? 0);
$row   = intval($_POST['sheet_row'] ?? 0);

if ($sheet <= 0 || $row <= 0) {
    echo json_encode(["success" => false, "error" => "Invalid sheet or row"]);
    exit;
}

$uploadDir = "uploads/sheet_attachments/";
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$original = basename($_FILES['file']['name']);
$tmp      = $_FILES['file']['tmp_name'];
$size     = (int) $_FILES['file']['size'];
$type     = mime_content_type($tmp) ?: 'application/octet-stream';

$safeName = preg_replace("/[^a-zA-Z0-9._-]/", "_", $original);
$filename = time() . "_" . $safeName;
$path     = $uploadDir . $filename;

if (!move_uploaded_file($tmp, $path)) {
    echo json_encode(["success" => false, "error" => "Failed to save file"]);
    exit;
}

$stmt = $conn->prepare("
    INSERT INTO sheet_attachments
    (sheet_id, sheet_row, original_name, file_path, file_size, mime_type, created_by)
    VALUES (?, ?, ?, ?, ?, ?, ?)
");

$stmt->bind_param(
    "iissisi",          // types: i i s s i s i
    $sheet,
    $row,
    $original,
    $path,
    $size,
    $type,
    $created_by
);

$success = $stmt->execute();

if ($success) {
    echo json_encode(["success" => true]);
} else {
    // Optional: delete the file if DB insert failed
    @unlink($path);
    echo json_encode([
        "success" => false,
        "error"   => "Database error: " . $stmt->error
    ]);
}

$stmt->close();