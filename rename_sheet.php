<?php
// update.php
include './partials/connection.php';

$id   = intval($_POST['id']);
$name = trim($_POST['name']);

$stmt = $conn->prepare(
    "UPDATE sheets
     SET name=?,
         updated_at=NOW()
     WHERE id=?"
);

$stmt->bind_param("si",$name,$id);

echo json_encode([
    "success"=>$stmt->execute()
]);
?>