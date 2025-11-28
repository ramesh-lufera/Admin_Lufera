<?php
function logActivity($conn, $user_id, $log_module, $log_action, $log_description)
{
    $stmt = $conn->prepare("
        INSERT INTO log (user_id, module, action, description, date_time)
        VALUES (?, ?, ?, ?, NOW())
    ");

    if (!$stmt) {
        error_log("Log prepare failed: " . $conn->error);
        return false;
    }

    $stmt->bind_param("isss", $user_id, $log_module, $log_action, $log_description);

    if ($stmt->execute()) {
        return true;
    } else {
        error_log("Log execute failed: " . $stmt->error);
        return false;
    }
}
?>
