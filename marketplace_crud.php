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
if ($_SERVER['REQUEST_METHOD'] == "POST" && $_POST['action'] == "getOrder") {

    $result = $conn->query("
        SELECT
            m.id,
            c.cat_name,
            c.cat_type
        FROM marketplace m
        LEFT JOIN categories c
        ON c.cat_id = m.cat_id
        ORDER BY m.display_order ASC
    ");

    $data = [];

    while($row = $result->fetch_assoc()){
        $data[] = $row;
    }

    echo json_encode([
        "status"=>"success",
        "data"=>$data
    ]);

    exit;
}
if ($_SERVER['REQUEST_METHOD'] == "POST" && $_POST['action'] == "swap") {

    if(!isset($_POST['order'])){

        echo json_encode([
            "status"=>"error",
            "message"=>"Invalid order."
        ]);

        exit;
    }

    $order = $_POST['order'];

    foreach($order as $index=>$id){

        $display_order = $index + 1;

        $stmt = $conn->prepare("
            UPDATE marketplace
            SET display_order=?
            WHERE id=?
        ");

        $stmt->bind_param(
            "ii",
            $display_order,
            $id
        );

        $stmt->execute();

    }

    echo json_encode([
        "status"=>"success",
        "message"=>"Marketplace order updated successfully."
    ]);

    exit;
}
if ($_SERVER['REQUEST_METHOD'] == "POST" && $_POST['action'] == "create") {
    $cat_id = intval($_POST['cat_id']);
    $model = isset($_POST['model']) && $_POST['model'] !== '' ? intval($_POST['model']) : null;
    $created_by = $_SESSION['user_id'];
    // Get next display order
    $result = $conn->query("SELECT COALESCE(MAX(display_order),0)+1 AS next_order FROM marketplace");
    $row = $result->fetch_assoc();
    $display_order = $row['next_order'];
    $stmt = $conn->prepare("
        INSERT INTO marketplace
        (
            cat_id,
            model,
            display_order,
            created_by,
            created_at
        )
        VALUES
        (
            ?,
            ?,
            ?,
            ?,
            NOW()
        )
    ");

    $stmt->bind_param(
        "iiii",
        $cat_id,
        $model,
        $display_order,
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
    $model = isset($_POST['model']) && $_POST['model'] !== '' ? intval($_POST['model']): null;
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
        // Reorder display_order
        $result = $conn->query("SELECT id FROM marketplace ORDER BY display_order ASC");
        $order = 1;
        while ($row = $result->fetch_assoc()) {
            $update = $conn->prepare("UPDATE marketplace SET display_order=? WHERE id=?");
            $update->bind_param("ii", $order, $row['id']);
            $update->execute();
            $order++;
        }
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