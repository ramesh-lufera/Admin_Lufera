<?php
include './partials/connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $id = intval($_POST['id']);

        $stmt = $conn->prepare("DELETE FROM roles WHERE id = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            echo 'success';
        } else {
            echo 'error';
        }

        $stmt->close();
        $conn->close();
        exit; // Important: stop further processing
    }
    $id = $_POST['role_id'];
    $name = $_POST['role_name'];
    $description = $_POST['description'];
    $isActive = $_POST['isActive'];
    $created_at = date("Y-m-d H:i:s");

    if (!empty($id)) {
        // UPDATE logic
        $stmt = $conn->prepare("UPDATE roles SET name=?, description=?, isActive=? WHERE id=?");
        $stmt->bind_param("ssii", $name, $description, $isActive, $id);
        // if ($stmt->execute()) {
        //     echo "update";
        // } else {
        //     echo "error";
        // }
        // $stmt->close();

        if ($stmt->execute()) {
            // Delete old permissions
            $conn->query("DELETE FROM permission WHERE role_id = $id");
        
            // Insert new permissions
            $category_ids = isset($_POST['category_ids']) ? $_POST['category_ids'] : [];
            if (!empty($category_ids)) {
                $permStmt = $conn->prepare("INSERT INTO permission (role_id, category_id) VALUES (?, ?)");
                foreach ($category_ids as $cat_id) {
                    $permStmt->bind_param("ii", $id, $cat_id);
                    $permStmt->execute();
                }
                $permStmt->close();
            }
        
            echo "update";
        } else {
            echo "error";
        }
        $stmt->close();
        
    } else {
        // Check for duplicate
        $check = $conn->prepare("SELECT id FROM roles WHERE name = ?");
        $check->bind_param("s", $name);
        $check->execute();
        $check->store_result();
        if ($check->num_rows > 0) {
            echo "exists";
        } else {
            // INSERT logic
            $stmt = $conn->prepare("INSERT INTO roles (name, description, isActive, created_on) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssis", $name, $description, $isActive, $created_at);
            // if ($stmt->execute()) {
            //     echo "success";
            // } else {
            //     echo "error";
            // }
            // $stmt->close();
            if ($stmt->execute()) {
                $role_id = $stmt->insert_id; // Get new role's ID
                $category_ids = isset($_POST['category_ids']) ? $_POST['category_ids'] : [];
            
                if (!empty($category_ids)) {
                    $permStmt = $conn->prepare("INSERT INTO permission (role_id, category_id) VALUES (?, ?)");
                    foreach ($category_ids as $cat_id) {
                        $permStmt->bind_param("ii", $role_id, $cat_id);
                        $permStmt->execute();
                    }
                    $permStmt->close();
                }
            
                echo "success";
            } else {
                echo "error";
            }
            $stmt->close();
            
        }
        $check->close();
    }

    $conn->close();
}
?>
