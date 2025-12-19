<?php
header('Content-Type: application/json');
include './partials/connection.php';

$input = json_decode(file_get_contents("php://input"), true);

if (!$input || !isset($input["name"])) {
    echo json_encode(["success" => false, "error" => "Invalid data: name required"]);
    exit;
}

// Optional: sheet ID for update
$sheetId = isset($input["id"]) ? intval($input["id"]) : 0;

$name = $input["name"];
$now = date("Y-m-d H:i:s");

// Build the data array to save (ensure columnTypes is included)
$dataToSave = [
    "rows" => $input["rows"] ?? 10,
    "cols" => $input["cols"] ?? 4,
    "headers" => $input["headers"] ?? [],
    "columnTypes" => $input["columnTypes"] ?? [],  // <-- Important: save column types
    "cells" => $input["cells"] ?? []
];

$jsonData = json_encode($dataToSave);

if ($sheetId > 0) {
    // UPDATE existing sheet
    $stmt = $conn->prepare("UPDATE sheets SET name = ?, data = ?, updated_at = ? WHERE id = ?");
    if (!$stmt) {
        echo json_encode(["success" => false, "error" => "Prepare failed: " . $conn->error]);
        exit;
    }
    $stmt->bind_param("sssi", $name, $jsonData, $now, $sheetId);
    
    if ($stmt->execute()) {
        echo json_encode(["success" => true, "id" => $sheetId]);
    } else {
        echo json_encode(["success" => false, "error" => $stmt->error]);
    }
    $stmt->close();
} else {
    // INSERT new sheet
    $stmt = $conn->prepare("INSERT INTO sheets (name, data, created_at, updated_at) VALUES (?, ?, ?, ?)");
    if (!$stmt) {
        echo json_encode(["success" => false, "error" => "Prepare failed: " . $conn->error]);
        exit;
    }
    $stmt->bind_param("ssss", $name, $jsonData, $now, $now);
    
    if ($stmt->execute()) {
        echo json_encode(["success" => true, "id" => $conn->insert_id]);
    } else {
        echo json_encode(["success" => false, "error" => $stmt->error]);
    }
    $stmt->close();
}

$conn->close();
?>