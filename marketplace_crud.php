<?php

session_start();
include './partials/connection.php';
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (isset($_GET['action']) && $_GET['action'] == "get") {
    $id = intval($_GET['id']);
    $stmt = $conn->prepare("SELECT * FROM marketplace WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        echo json_encode([
            "status" => "success",
            "data" => $result->fetch_assoc()
        ]);
    } else {
        echo json_encode([
            "status" => "error",
            "message" => "Record not found."
        ]);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == "POST" && $_POST['action'] == "create") {
    $cat_id = intval($_POST['cat_id']);
    $model = intval($_POST['model']);
    $created_by = $_SESSION['user_id'];
    $stmt = $conn->prepare("
        INSERT INTO marketplace
        (
            cat_id,
            model,
            created_by,
            created_at
        )
        VALUES
        (
            ?,
            ?,
            ?,
            NOW()
        )
    ");

    $stmt->bind_param(
        "iii",
        $cat_id,
        $model,
        $created_by
    );

    if ($stmt->execute()) {
        echo json_encode([
            "status" => "success",
            "message" => "Marketplace created successfully."
        ]);
    } else {
        echo json_encode([
            "status" => "error",
            "message" => $stmt->error
        ]);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == "POST" && $_POST['action'] == "update") {
    $id = intval($_POST['id']);
    $cat_id = intval($_POST['cat_id']);
    $model = intval($_POST['model']);
    $stmt = $conn->prepare("
        UPDATE marketplace
        SET
            cat_id=?,
            model=?
        WHERE id=?
    ");
    $stmt->bind_param(
        "iii",
        $cat_id,
        $model,
        $id
    );
    if ($stmt->execute()) {
        echo json_encode([
            "status" => "success",
            "message" => "Marketplace updated successfully."
        ]);
    } else {
        echo json_encode([
            "status" => "error",
            "message" => $stmt->error
        ]);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == "POST" && $_POST['action'] == "delete") {
    $id = intval($_POST['id']);
    $stmt = $conn->prepare("DELETE FROM marketplace WHERE id=?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        echo json_encode([
            "status" => "success",
            "message" => "Marketplace deleted successfully."
        ]);

    } else {
        echo json_encode([
            "status" => "error",
            "message" => $stmt->error
        ]);
    }
    exit;
}

echo json_encode([
    "status" => "error",
    "message" => "Invalid Request."
]);