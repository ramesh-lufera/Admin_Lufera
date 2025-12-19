<?php
include './partials/connection.php';
$data = json_decode(file_get_contents("php://input"), true);

$stmt = $conn->prepare("
    INSERT INTO sheet_comments (sheet_id, row_number, parent_id, comment)
    VALUES (?, ?, ?, ?)
");

$stmt->bind_param(
    "iiis",
    $data['sheet_id'],
    $data['row_number'],
    $data['parent_id'],
    $data['comment']
);

$stmt->execute();

echo json_encode(["success" => true]);
