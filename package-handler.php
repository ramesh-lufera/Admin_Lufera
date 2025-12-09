<?php
session_start();
include './partials/connection.php';
header('Content-Type: application/json');
include './log.php';
$loggedInUserId = $_SESSION['user_id'] ?? 0;
$response = ['success' => false];

if (isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'toggle_status' && isset($_POST['id'], $_POST['status'])) {
        $id = intval($_POST['id']);
        $status = intval($_POST['status']) === 1 ? 0 : 1; // Toggle

        $query = "UPDATE package SET is_active = ? WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('ii', $status, $id);
        if ($stmt->execute()) {
            logActivity(
                $conn, 
                $loggedInUserId, 
                "Packages", 
                "Package Status changed Successfully - $status"
            );
            $response['success'] = true;
        }
    }

    if ($action === 'delete_package' && isset($_POST['id'])) {
        $id = intval($_POST['id']);

        $query = "UPDATE package SET is_deleted = 1 WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) {
            logActivity(
                $conn, 
                $loggedInUserId, 
                "Packages", 
                "Package Deleted", 
                "Package Deleted Successfully"
            );
            $response['success'] = true;
        }
    }
}

echo json_encode($response);
?>