<?php
include 'partials/connection.php';

$id = intval($_GET["id"] ?? 0);

$res = $conn->query("SELECT * FROM sheets WHERE id=$id LIMIT 1");

if (!$res || $res->num_rows == 0) {
    echo json_encode(["success" => false, "error" => "Sheet not found"]);
    exit;
}

$row = $res->fetch_assoc();

echo json_encode([
    "success" => true,
    "data" => json_decode($row["data"], true),
    "name" => $row["name"],
    "updated_at" => $row["updated_at"]
]);
?>
