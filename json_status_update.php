<?php
    include './partials/layouts/layoutTop.php';

    $user_id = $_SESSION['user_id'];
    
    $website_id = intval($_GET['id'] ?? 0);
    $fields = $_POST['fields'] ?? [];
    $status = $_POST['status'] ?? '';

    if (!in_array($status, ['approved', 'rejected']) || empty($fields)) {
        http_response_code(400);
        exit('Invalid input');
    }

    $roleCheck = $conn->prepare("SELECT role FROM users WHERE id = ?");
    $roleCheck->bind_param("i", $user_id);
    $roleCheck->execute();
    $roleCheck->bind_result($role);
    $roleCheck->fetch();
    $roleCheck->close();

    if (!in_array($role, [1, 2, 7])) {
        http_response_code(403);
        exit('Unauthorized');
    }

    $stmt = $conn->prepare("SELECT id, name, prefill_name FROM json WHERE website_id = ?");
    $stmt->bind_param("i", $website_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $data = $res->fetch_assoc();
    $stmt->close();

    $json = json_decode($data['name'], true);

    foreach ($fields as $f) {
        if (isset($json[$f])) {
            $json[$f]['status'] = $status;
        }
    }


    $newJson = json_encode($json);
    $update = $conn->prepare("UPDATE json SET name = ? WHERE id = ?");
    $update->bind_param("si", $newJson, $data['id']);
    $update->execute();

    $prefillLabel = isset($data['prefill_name']) && $data['prefill_name'] !== '' 
    ? $data['prefill_name'] 
    : null;

    // Determine action text based on number of fields and status
    if (count($fields) > 1) {
        // Bulk action
        if ($prefillLabel) {
            $safePrefill = htmlspecialchars($prefillLabel, ENT_QUOTES, 'UTF-8');
            $actionText = $status === 'approved'
                ? "Wizard bulk approved for {$safePrefill}"
                : "Wizard bulk rejected for {$safePrefill}";
        } else {
            $actionText = $status === 'approved'
                ? "Wizard bulk approved"
                : "Wizard bulk rejected";
        }
    } else {
        // Single field action
        $fieldName = $fields[0];
        $prefix = $status === 'approved'
            ? "Field Approved for {$fieldName}"
            : "Field Rejected for {$fieldName}";
        if ($prefillLabel) {
            $safePrefill = htmlspecialchars($prefillLabel, ENT_QUOTES, 'UTF-8');
            $actionText = $prefix . " in {$safePrefill}";
        } else {
            $actionText = $prefix;
        }
    }

// Log activity
logActivity(
    $conn,
    $user_id,
    "wizard",
    $actionText
);

    echo 'Success';

    include './partials/layouts/layoutBottom.php';
?>