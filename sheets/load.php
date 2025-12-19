<?php
header('Content-Type: application/json');
include './partials/connection.php';

$id = intval($_GET["id"] ?? 0);

if ($id <= 0) {
    echo json_encode(["success" => false, "error" => "Invalid ID"]);
    exit;
}

$stmt = $conn->prepare("SELECT id, name, data, created_at, updated_at FROM sheets WHERE id = ? LIMIT 1");
if (!$stmt) {
    echo json_encode(["success" => false, "error" => "Prepare failed"]);
    exit;
}

$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(["success" => false, "error" => "Sheet not found"]);
    $stmt->close();
    exit;
}

$row = $result->fetch_assoc();
$decodedData = json_decode($row["data"], true);

// Ensure columnTypes is always returned (even if missing in old sheets)
if (!isset($decodedData["columnTypes"])) {
    $decodedData["columnTypes"] = [];
}

echo json_encode([
    "success" => true,
    "id" => $row["id"],
    "name" => $row["name"],
    "data" => $decodedData,
    "created_at" => $row["created_at"],
    "updated_at" => $row["updated_at"]
]);

$stmt->close();
$conn->close();
?>