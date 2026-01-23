<?php
// save.php

header('Content-Type: application/json');
include 'partials/connection.php';
$data = json_decode(file_get_contents('php://input'), true);

if (!$data || empty($data['name'])) {
    echo json_encode(['success' => false, 'error' => 'Name is required']);
    exit;
}

$name        = trim($data['name']);
$rows        = (int)($data['rows'] ?? 10);
$cols        = (int)($data['cols'] ?? 4);
$headers     = $data['headers']     ?? [];
$columnTypes = $data['columnTypes'] ?? [];
$cells       = $data['cells']       ?? [];
$id          = isset($data['id']) ? (int)$data['id'] : 0;

// ────────────────────────────────────────────────
// 1. Check for duplicate name (exclude current record if updating)
$query = "SELECT id FROM sheets WHERE name = ? AND id != ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("si", $name, $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo json_encode([
        'success' => false,
        'error'   => "A sheet with the name \"$name\" already exists. Please choose a different name."
    ]);
    exit;
}
$stmt->close();

// ────────────────────────────────────────────────
// 2. Proceed with INSERT or UPDATE
$jsonData = json_encode([
    'rows'        => $rows,
    'cols'        => $cols,
    'headers'     => $headers,
    'columnTypes' => $columnTypes,
    'cells'       => $cells
]);

if ($id > 0) {
    // ──────────────── UPDATE ────────────────
    $stmt = $conn->prepare("UPDATE sheets SET name = ?, data = ?, updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("ssi", $name, $jsonData, $id);
    
    $success = $stmt->execute();

    //  ←←← PASTE HERE for UPDATE
    if (!$success) {
        if ($conn->errno === 1062) { // duplicate key violation
            echo json_encode([
                'success' => false,
                'error'   => "A sheet named \"$name\" already exists. Please choose a different name."
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error'   => $conn->error
            ]);
        }
        $stmt->close();
        $conn->close();
        exit;
    }
    // ────────────────────────────────────────

} else {
    // ──────────────── INSERT ────────────────
    $stmt = $conn->prepare("INSERT INTO sheets (name, data, created_at, updated_at) VALUES (?, ?, NOW(), NOW())");
    $stmt->bind_param("ss", $name, $jsonData);
    
    $success = $stmt->execute();

    //  ←←← PASTE HERE for INSERT
    if (!$success) {
        if ($conn->errno === 1062) {
            echo json_encode([
                'success' => false,
                'error'   => "A sheet named \"$name\" already exists. Please choose a different name."
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error'   => $conn->error
            ]);
        }
        $stmt->close();
        $conn->close();
        exit;
    }
    // ────────────────────────────────────────

    $newId = $conn->insert_id;
}

// success response
echo json_encode([
    'success' => true,
    'id'      => $id > 0 ? $id : $newId
]);

$stmt->close();
$conn->close();
?>