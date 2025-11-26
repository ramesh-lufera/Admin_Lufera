<?php
include './partials/connection.php';

$apply_for = $_GET['apply_for'] ?? '';

$response = ["title" => "", "content" => "", "id" => 0];

if (!empty($apply_for)) {
    $stmt = $conn->prepare("SELECT * FROM terms_conditions WHERE apply_for = ? LIMIT 1");
    $stmt->bind_param("s", $apply_for);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $response = [
            "title" => $row["title"],
            "content" => $row["content"],
            "id" => $row["id"]
        ];
    }
}

echo json_encode($response);
?>