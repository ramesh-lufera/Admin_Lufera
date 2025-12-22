<?php
header('Content-Type: application/json');
include 'partials/connection.php';

$input = json_decode(file_get_contents("php://input"), true);

if (!$input || !isset($input["name"])) {
    echo json_encode(["success" => false, "error" => "Invalid data: name required"]);
    exit;
}

$name = $input["name"];
$rows = $input["rows"] ?? 10;
$cols = $input["cols"] ?? 4;
$headers = $input["headers"] ?? [];
$columnTypes = $input["columnTypes"] ?? [];
$cells = $input["cells"] ?? [];

$dataToSave = [
    "rows" => $rows,
    "cols" => $cols,
    "headers" => $headers,
    "columnTypes" => $columnTypes,
    "cells" => $cells
];

$jsonData = json_encode($dataToSave);
$now = date("Y-m-d H:i:s");

// If ID is provided → UPDATE
if (!empty($input["id"])) {
    $id = intval($input["id"]);
    $stmt = $conn->prepare("UPDATE sheets SET name = ?, data = ?, updated_at = ? WHERE id = ?");
    $stmt->bind_param("sssi", $name, $jsonData, $now, $id);
    
    if ($stmt->execute()) {
        echo json_encode(["success" => true, "id" => $id]);
    } else {
        echo json_encode(["success" => false, "error" => $stmt->error]);
    }
    $stmt->close();
} else {
    // INSERT new sheet
    $stmt = $conn->prepare("INSERT INTO sheets (name, data, created_at, updated_at) VALUES (?, ?, ?, ?)");
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