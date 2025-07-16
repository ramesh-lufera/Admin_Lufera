<?php

include './partials/layouts/layoutTop.php';

    $admin_id = $_SESSION['user_id'];

// Get the admin's role
$stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$role = (int) $row['role'];

if (!in_array($role, [1, 7])) {
    http_response_code(403);
    echo 'Permission denied';
    exit;
}

// Get POST data
$website_id = $_POST['website_id'] ?? '';
$field = $_POST['field'] ?? '';
$status = $_POST['status'] ?? '';

if (!$website_id || !$field || !in_array($status, ['approved', 'rejected'])) {
    http_response_code(400);
    echo 'Invalid request';
    exit;
}

// Fetch latest JSON for website
$stmt = $conn->prepare("SELECT id, name FROM json WHERE website_id = ? ORDER BY id DESC LIMIT 1");
$stmt->bind_param("i", $website_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    echo 'Form data not found';
    exit;
}

$row = $result->fetch_assoc();
$data = json_decode($row['name'], true);

// Update the specific field's status
if (!isset($data[$field])) {
    http_response_code(400);
    echo 'Field not found';
    exit;
}

$data[$field]['status'] = $status;

// Update JSON back to DB
$updated_json = json_encode($data);
$update_stmt = $conn->prepare("UPDATE json SET name = ? WHERE id = ?");
$update_stmt->bind_param("si", $updated_json, $row['id']);
$update_stmt->execute();

echo 'Status updated';

include './partials/layouts/layoutBottom.php';

?>
