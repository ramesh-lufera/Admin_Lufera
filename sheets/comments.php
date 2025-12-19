<?php
include 'partials/connection.php';

$sheet = intval($_GET['sheet_id']);
$row = intval($_GET['row']);

$res = $conn->query("SELECT * FROM sheet_comments WHERE sheet_id=$sheet AND row_number=$row AND parent_id IS NULL");

$comments = [];

while ($c = $res->fetch_assoc()) {
    $cid = $c['id'];
    $r = $conn->query("SELECT * FROM sheet_comments WHERE parent_id=$cid");
    $c['replies'] = $r->fetch_all(MYSQLI_ASSOC);
    $comments[] = $c;
}

echo json_encode($comments);
