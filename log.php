<?php
function logActivity($conn, $user_id, $log_module, $log_action)
{
    $stmt = $conn->prepare("
        INSERT INTO log (user_id, module, action, date_time)
        VALUES (?, ?, ?, UTC_TIMESTAMP())
    ");

    if (!$stmt) {
        error_log("Log prepare failed: " . $conn->error);
        return false;
    }

    $stmt->bind_param("iss", $user_id, $log_module, $log_action);

    if ($stmt->execute()) {
        return true;
    } else {
        error_log("Log execute failed: " . $stmt->error);
        return false;
    }
}
?>
