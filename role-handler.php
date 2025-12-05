<?php
session_start();
include './partials/connection.php';
include './log.php';
$loggedInUserId = $_SESSION['user_id'] ?? 0;
$response = "error"; // Default fallback response
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    /* ============================================================
       DELETE ROLE
    ============================================================ */
    if (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $id = intval($_POST['id']);
        $stmt = $conn->prepare("DELETE FROM roles WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            logActivity(
                $conn,
                $loggedInUserId,
                "Roles",
                "Role Deleted",
                "Role deleted successfully"
            );
            $response = "success";
        } else {
            error_log("Delete Error: " . $stmt->error);
            $response = "error";
        }
        $stmt->close();  
        echo $response;
        $conn->close();
        exit;
    }
    /* ============================================================
       ADD / UPDATE ROLE
    ============================================================ */
    $id = $_POST['role_id'] ?? "";
    $name = trim($_POST['role_name']);
    $description = trim($_POST['description']);
    $isActive = intval($_POST['isActive']);
    $created_at = date("Y-m-d H:i:s");
    /* ---------------------------
       UPDATE ROLE
    ---------------------------- */
    if (!empty($id)) {
        $stmt = $conn->prepare("
            UPDATE roles SET name=?, description=?, isActive=? 
            WHERE id=?
        ");
        $stmt->bind_param("ssii", $name, $description, $isActive, $id);
        if ($stmt->execute()) {
            /* Delete old permissions */
            $conn->query("DELETE FROM permission WHERE role_id = $id");
            /* Insert new permissions */
            if (!empty($_POST['category_ids'])) {
                $permStmt = $conn->prepare("
                    INSERT INTO permission (role_id, category_id) 
                    VALUES (?, ?)
                ");
                foreach ($_POST['category_ids'] as $cat_id) {
                    $permStmt->bind_param("ii", $id, $cat_id);
                    $permStmt->execute();
                }
                $permStmt->close();
            }
            /* Log Activity */
            logActivity(
                $conn,
                $loggedInUserId,
                "Roles",
                "Role Updated",
                "Role updated successfully"
            );
            $response = "update";
        } else {
            error_log("Update Error: " . $stmt->error);
            $response = "error";
        }
        $stmt->close();
    }
    /* ---------------------------
       ADD NEW ROLE
    ---------------------------- */
    else {
        // Check for duplicate role
        $check = $conn->prepare("SELECT id FROM roles WHERE name = ?");
        $check->bind_param("s", $name);
        $check->execute();
        $check->store_result();
        if ($check->num_rows > 0) {
            $response = "exists";
        } else {
            $stmt = $conn->prepare("
                INSERT INTO roles (name, description, isActive, created_on)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->bind_param("ssis", $name, $description, $isActive, $created_at);
            if ($stmt->execute()) {
                $role_id = $stmt->insert_id;
                /* Insert permissions */
                if (!empty($_POST['category_ids'])) {
                    $permStmt = $conn->prepare("
                        INSERT INTO permission (role_id, category_id)
                        VALUES (?, ?)
                    ");
                    foreach ($_POST['category_ids'] as $cat_id) {
                        $permStmt->bind_param("ii", $role_id, $cat_id);
                        $permStmt->execute();
                    }
                    $permStmt->close();
                }
                /* Log Activity */
                logActivity(
                    $conn,
                    $loggedInUserId,
                    "Roles",
                    "Role Created",
                    "New Role created successfully - $name"
                );
                $response = "success";
            } else {
                error_log("Insert Error: " . $stmt->error);
                $response = "error";
            }
            $stmt->close();
        }
        $check->close();
    }
}
// Final output
echo $response;
// Close DB connection (ONLY HERE)
$conn->close();
?>
