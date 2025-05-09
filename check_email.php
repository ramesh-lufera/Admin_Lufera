<?php
include './partials/connection.php';
header('Content-Type: application/json');

// Get raw POST data
$data = json_decode(file_get_contents('php://input'), true);
$email = $data['email'] ?? '';

$response = ['exists' => false];

if (!empty($email)) {
    // Prevent SQL injection
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    // Check if any rows returned
    if ($stmt->num_rows > 0) {
        $response['exists'] = true;
    }

    $stmt->close();
    $conn->close();
}

// Return JSON response
echo json_encode($response);
?>
