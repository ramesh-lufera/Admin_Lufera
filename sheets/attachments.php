<?php
include 'partials/connection.php';

$sheet = intval($_GET['sheet_id']);
$row   = intval($_GET['row']);

$res = $conn->query("
    SELECT original_name, file_path, created_at
    FROM sheet_attachments
    WHERE sheet_id = $sheet AND row_number = $row
    ORDER BY created_at DESC
");

echo json_encode($res->fetch_all(MYSQLI_ASSOC));
