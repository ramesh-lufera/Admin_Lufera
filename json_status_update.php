<?php

include './partials/layouts/layoutTop.php';

$session_user_id = $_SESSION['user_id'] ?? 0;
$field = $_POST['field'] ?? '';
$status = $_POST['status'] ?? '';
$website_id = $_GET['id'] ?? 0;

if (!$session_user_id || !$field || !in_array($status, ['approved', 'rejected']) || !$website_id) {
    http_response_code(400);
    exit('Invalid request');
}

// Fetch current user's role
$roleQuery = $conn->prepare("SELECT role FROM users WHERE id = ?");
$roleQuery->bind_param("i", $session_user_id);
$roleQuery->execute();
$roleQuery->bind_result($user_role);
$roleQuery->fetch();
$roleQuery->close();

// Only admin or developer allowed
if (!in_array($user_role, [1, 7])) {
    http_response_code(403);
    exit('Unauthorized');
}

// Fetch JSON data using website_id
$website_id = intval($website_id);
$query = $conn->prepare("SELECT id, name FROM json WHERE website_id = ?");
$query->bind_param("i", $website_id);
$query->execute();
$query->bind_result($json_id, $jsonData);

if (!$query->fetch()) {
    http_response_code(404);
    exit('Data not found');
}
$query->close();

// Decode, update field status, and re-save
$data = json_decode($jsonData, true);

if (!isset($data[$field])) {
    http_response_code(404);
    exit('Field not found');
}

$data[$field]['status'] = $status;
$updatedJson = json_encode($data);

// Update JSON in DB
$update = $conn->prepare("UPDATE json SET name = ? WHERE id = ?");
$update->bind_param("si", $updatedJson, $json_id);
$update->execute();
$update->close();

echo 'OK';

include './partials/layouts/layoutBottom.php';

?>
