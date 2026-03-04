<?php
require_once './partials/connection.php';
require_once "log.php"; // where logActivity exists

session_start();

$loggedInUserId = $_SESSION['user_id'] ?? 0;

$data = json_decode(file_get_contents("php://input"), true);

$sheet_name = $data['sheet_name'];

logActivity(
    $conn,
    $loggedInUserId,
    "Sheets",
    "Exported sheet: {$sheet_name}"
);

echo json_encode(["status" => "success"]);