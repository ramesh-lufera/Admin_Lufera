<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');
include 'partials/connection.php';

$sheetId = intval($_GET['sheet_id'] ?? 0);
if ($sheetId <= 0) {
    echo json_encode(["comments" => [], "attachments" => []]);
    exit;
}

// COMMENTS
$commentStmt = $conn->prepare("
    SELECT sheet_row, COUNT(*) as cnt
    FROM sheet_comments
    WHERE sheet_id = ?
    GROUP BY sheet_row
");
$commentStmt->bind_param("i", $sheetId);
$commentStmt->execute();
$commentStmt->bind_result($rowNumber, $cnt);

$comments = [];
while ($commentStmt->fetch()) {
    $comments[$rowNumber] = (int)$cnt;
}
$commentStmt->close();

// ATTACHMENTS
$attachStmt = $conn->prepare("
    SELECT sheet_row, COUNT(*) as cnt
    FROM sheet_attachments
    WHERE sheet_id = ?
    GROUP BY sheet_row
");
$attachStmt->bind_param("i", $sheetId);
$attachStmt->execute();
$attachStmt->bind_result($rowNumber, $cnt);

$attachments = [];
while ($attachStmt->fetch()) {
    $attachments[$rowNumber] = (int)$cnt;
}
$attachStmt->close();

echo json_encode([
    "comments" => $comments,
    "attachments" => $attachments
]);
?>