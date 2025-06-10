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
        if ($stmt->execute()) {
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
            if ($stmt->execute()) {
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
