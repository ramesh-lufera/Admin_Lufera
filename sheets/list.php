<?php
header('Content-Type: application/json');
include './partials/connection.php';

// Use prepared statement for better security and consistency
// (even though no user input here, it's good practice)
$stmt = $conn->prepare("SELECT id, name, updated_at FROM sheets ORDER BY updated_at DESC");
if (!$stmt) {
    echo json_encode(["success" => false, "error" => "Database prepare failed"]);
    exit;
}

$stmt->execute();
$result = $stmt->get_result();

$sheets = [];
while ($row = $result->fetch_assoc()) {
    // Optional: format date nicely for display (e.g., in load prompt)
    $row['updated_at_formatted'] = date("M d, Y H:i", strtotime($row['updated_at']));
    $sheets[] = $row;
}

$stmt->close();
$conn->close();

echo json_encode($sheets);
?>