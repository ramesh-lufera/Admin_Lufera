<?php
include './partials/connection.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = intval($_POST['user_id']);
    $roleId = intval($_POST['role_id']);

    if ($userId > 0 && $roleId > 0) {
        $stmt = mysqli_prepare($conn, "UPDATE users SET role = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, 'ii', $roleId, $userId);

        if (mysqli_stmt_execute($stmt)) {
            echo json_encode(['success' => true]);
            exit;
        }
    }
}

echo json_encode(['success' => false]);
exit;
