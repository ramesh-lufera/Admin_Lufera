<?php
session_start();
include './partials/connection.php';
include "log.php";
$data = json_decode(file_get_contents("php://input"), true);
if (!$data) {
    $data = $_POST;
}
$loggedInUserId = $_SESSION['user_id'] ?? 0;
$sheet_id  = intval($data['sheet_id'] ?? 0);
$sheet_row = intval($data['sheet_row'] ?? 0);
$comment   = trim($data['comment'] ?? '');
$parent_id = isset($data['parent_id']) && $data['parent_id'] ? intval($data['parent_id']) : null;
if (!$sheet_id || !$sheet_row || $comment === '') {
    echo json_encode(["success"=>false,"message"=>"Invalid input"]);
    exit;
}
$stmt = $conn->prepare("
    INSERT INTO sheet_comments
    (sheet_id, sheet_row, parent_id, comment)
    VALUES (?, ?, ?, ?)
");
$stmt->bind_param("iiis",
    $sheet_id,
    $sheet_row,
    $parent_id,
    $comment
);
if ($stmt->execute()) {
    logActivity(
        $conn,
        $loggedInUserId,
        "Sheets",
        "Added comment to sheet ID {$sheet_id}, row {$sheet_row}" .
        ($parent_id ? " (reply {$parent_id})" : "")
    );
    echo json_encode(["success"=>true]);
} else {
    echo json_encode([
        "success"=>false,
        "error"=>$stmt->error
    ]);
}