<?php
include 'partials/connection.php';

$res = $conn->query("SELECT id, name, updated_at FROM sheets ORDER BY updated_at DESC");

$list = [];
while ($row = $res->fetch_assoc()) {
    $list[] = $row;
}

echo json_encode($list);
?>
