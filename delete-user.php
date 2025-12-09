<?php
session_start();
include 'partials/connection.php';
include './log.php';         // Log function

// Get user ID BEFORE destroying session
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
if (isset($_POST['id'])) {
    $id = intval($_POST['id']);

    // Change DELETE to UPDATE for soft delete
    $stmt = $conn->prepare("UPDATE users SET is_deleted = 1 WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        logActivity(
            $conn,
            $user_id,
            "Users",                   // module
            "User Deleted",                   // action
        );
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false, "error" => $stmt->error]);
    }

    $stmt->close();
} else {
    echo json_encode(["success" => false, "error" => "Invalid request"]);
}
?>
