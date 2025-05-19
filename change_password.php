<?php
session_start();
include './partials/connection.php';

$userId = $_SESSION['user_id']; // Make sure user is logged in

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $oldPassword = $_POST['old_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';

    $oldPassword = trim($oldPassword);
    $newPassword = trim($newPassword);

    if (empty($oldPassword) || empty($newPassword)) {
        echo json_encode(['status' => 'error', 'message' => 'All fields are required.']);
        exit;
    }

    // Fetch current password from DB
    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $stmt->bind_result($currentPassword);
    $stmt->fetch();
    $stmt->close();
    
    // Compare directly (not recommended for real apps)
    if ($oldPassword !== $currentPassword) {
        echo json_encode(['status' => 'error', 'message' => 'Old password is incorrect.']);
        exit;
    }

    // Update new password (as plain text)
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt->bind_param('si', $newPassword, $userId);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Password updated successfully.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Update failed.']);
    }

    $stmt->close();
    $conn->close();
}
?>
