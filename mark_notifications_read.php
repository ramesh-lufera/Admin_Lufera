<?php
require './partials/connection.php';
include './partials/check_login.php';

if (isset($_SESSION['user_id'])) {
    $loggedInUserId = $_SESSION['user_id'];

    $sql = "SELECT user_id FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $loggedInUserId);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $userId = $row['user_id'];

    $update = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
    $update->bind_param("s", $userId);
    $update->execute();

    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false]);
}
?>
