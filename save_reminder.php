<?php
header('Content-Type: application/json');
include './partials/connection.php';

$data = json_decode(file_get_contents('php://input'), true);

// if (!$data || empty($data['sheet_id']) || empty($data['sheet_row']) || empty($data['remind_at'])) {
//     echo json_encode(['success' => false, 'error' => 'Missing required fields']);
//     exit;
// }

$stmt = $conn->prepare("
    INSERT INTO sheet_reminders 
    (sheet_id, sheet_row, remind_at, message, recipient_email, created_at)
    VALUES (?, ?, ?, ?, ?, NOW())
");

$stmt->bind_param("iiss",
    $data['sheet_id'],
    $data['sheet_row'],
    $data['remind_at'],
    $data['message'],
    $data['recipient_email'] ?? null
);

$ok = $stmt->execute();

echo json_encode([
    'success' => $ok,
    'error'   => $ok ? null : $conn->error
]);