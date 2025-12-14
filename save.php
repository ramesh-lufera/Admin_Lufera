<?php
include 'partials/connection.php';

$input = json_decode(file_get_contents("php://input"), true);

if (!$input || !isset($input["name"])) {
    echo json_encode(["success" => false, "error" => "Invalid data"]);
    exit;
}

$name = $conn->real_escape_string($input["name"]);
$json = $conn->real_escape_string(json_encode($input));
$now = date("Y-m-d H:i:s");

$sql = "INSERT INTO sheets (name, data, created_at, updated_at)
        VALUES ('$name', '$json', '$now', '$now')";

if ($conn->query($sql)) {
    echo json_encode(["success" => true, "id" => $conn->insert_id]);
} else {
    echo json_encode(["success" => false, "error" => $conn->error]);
}
?>
