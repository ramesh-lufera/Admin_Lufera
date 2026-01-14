<?php
include './partials/connection.php';

$data = json_decode(file_get_contents("php://input"), true);
if (!$data) {
    $data = $_POST;
}

$sheet_id   = intval($data['sheet_id'] ?? 0);
$sheet_row = intval($data['sheet_row'] ?? 0);
$comment    = trim($data['comment'] ?? '');
$parent_id  = isset($data['parent_id']) ? intval($data['parent_id']) : null;

$stmt = $conn->prepare("
    INSERT INTO sheet_comments (`sheet_id`, `sheet_row`, `parent_id`, `comment`)
    VALUES (?, ?, ?, ?)
");

$stmt->bind_param(
    "iiis",
    $sheet_id,
    $sheet_row,
    $parent_id,
    $comment
);

$stmt->execute();

echo json_encode(["success" => true]);
