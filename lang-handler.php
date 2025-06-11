<?php
include './partials/connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $id = intval($_POST['id']);

        $stmt = $conn->prepare("DELETE FROM language WHERE id = ?");
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
    $language = $_POST['language'];
    $isActive = $_POST['isActive'];
    $created_at = date("Y-m-d H:i:s");

    if (!empty($id)) {
        // UPDATE logic
        $stmt = $conn->prepare("UPDATE language SET language=?, status=? WHERE id=?");
        $stmt->bind_param("ssi", $language, $isActive, $id);
        if ($stmt->execute()) {
            echo "update";
        } else {
            echo "error";
        }
        $stmt->close();
    } else {
        // Check for duplicate
        $check = $conn->prepare("SELECT id FROM language WHERE language = ?");
        $check->bind_param("s", $language);
        $check->execute();
        $check->store_result();
        if ($check->num_rows > 0) {
            echo "exists";
        } else {
            // INSERT logic
            $stmt = $conn->prepare("INSERT INTO language (language, status, created_at) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $language, $isActive, $created_at);
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
