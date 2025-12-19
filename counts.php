<?php
header('Content-Type: application/json');
include 'partials/connection.php';

$sheetId = intval($_GET['sheet_id'] ?? 0);
if ($sheetId <= 0) {
    echo json_encode(["comments" => [], "attachments" => []]);
    exit;
}

// Count comments (including replies)
$commentStmt = $conn->prepare("
    SELECT row_number, COUNT(*) as cnt
    FROM sheet_comments
    WHERE sheet_id = ?
    GROUP BY row_number
");
$commentStmt->bind_param("i", $sheetId);
$commentStmt->execute();
$result = $commentStmt->get_result();

$comments = [];
while ($row = $result->fetch_assoc()) {
    $comments[$row['row_number']] = (int)$row['cnt'];
}
$commentStmt->close();

// Count attachments
$attachStmt = $conn->prepare("
    SELECT row_number, COUNT(*) as cnt
    FROM sheet_attachments
    WHERE sheet_id = ?
    GROUP BY row_number
");
$attachStmt->bind_param("i", $sheetId);
$attachStmt->execute();
$result = $attachStmt->get_result();

$attachments = [];
while ($row = $result->fetch_assoc()) {
    $attachments[$row['row_number']] = (int)$row['cnt'];
}
$attachStmt->close();

echo json_encode([
    "comments" => $comments,
    "attachments" => $attachments
]);
?>