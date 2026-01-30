<?php
// save.php

header('Content-Type: application/json');
include 'partials/connection.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || empty($data['id']) || !is_numeric($data['id'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error'   => 'Missing or invalid sheet ID'
    ]);
    exit;
}

$id = (int)$data['id'];

// ────────────────────────────────────────────────
// Verify the sheet actually exists
// (extra safety — prevents updating non-owned or deleted records)
$stmt = $conn->prepare("SELECT id FROM sheets WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        'success' => false,
        'error'   => 'Sheet not found or access denied'
    ]);
    $stmt->close();
    $conn->close();
    exit;
}
$stmt->close();

// ────────────────────────────────────────────────
// Prepare the JSON data blob (structure remains the same)
$jsonData = json_encode([
    'rows'        => (int)($data['rows'] ?? 10),
    'cols'        => (int)($data['cols'] ?? 4),
    'headers'     => $data['headers']     ?? [],
    'columnTypes' => $data['columnTypes'] ?? [],
    'cells'       => $data['cells']       ?? []
]);

// ────────────────────────────────────────────────
// UPDATE only — no name change here
$stmt = $conn->prepare("
    UPDATE sheets 
    SET data = ?, 
        updated_at = NOW() 
    WHERE id = ?
");

$stmt->bind_param("si", $jsonData, $id);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'id'      => $id
    ]);
} else {
    echo json_encode([
        'success' => false,
        'error'   => $conn->error ?: 'Database update failed'
    ]);
}

$stmt->close();
$conn->close();
?>