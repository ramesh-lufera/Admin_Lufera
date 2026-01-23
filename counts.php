<?php
/**
 * counts.php
 * Returns activity counts per row for a given sheet:
 * - comment counts
 * - attachment counts
 * - reminder counts
 * - (optional) rows with reminders due TODAY
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // adjust if needed for CORS

include 'partials/connection.php';

// ────────────────────────────────────────────────
// Input validation
// ────────────────────────────────────────────────
$sheetId = isset($_GET['sheet_id']) ? (int)$_GET['sheet_id'] : 0;

if ($sheetId <= 0) {
    echo json_encode([
        "success"           => false,
        "error"             => "Invalid or missing sheet_id",
        "comments"          => [],
        "attachments"       => [],
        "reminders"         => [],
        "today_reminder_rows" => []
    ]);
    exit;
}

$counts = [
    "comments"          => [],
    "attachments"       => [],
    "reminders"         => [],
    "today_reminder_rows" => []
];

// ────────────────────────────────────────────────
// Helper function to fetch grouped counts
// ────────────────────────────────────────────────
function fetchRowCounts($conn, $sheetId, $table, $column = 'sheet_row') {
    $counts = [];
    
    $stmt = $conn->prepare("
        SELECT $column, COUNT(*) as cnt
        FROM $table
        WHERE sheet_id = ?
        GROUP BY $column
    ");
    
    if (!$stmt) {
        return $counts; // silent fail → empty array
    }
    
    $stmt->bind_param("i", $sheetId);
    $stmt->execute();
    $stmt->bind_result($rowNumber, $cnt);
    
    while ($stmt->fetch()) {
        $counts[(int)$rowNumber] = (int)$cnt;
    }
    
    $stmt->close();
    return $counts;
}

// ────────────────────────────────────────────────
// 1. Comments
// ────────────────────────────────────────────────
$counts['comments'] = fetchRowCounts($conn, $sheetId, 'sheet_comments');

// ────────────────────────────────────────────────
// 2. Attachments
// ────────────────────────────────────────────────
$counts['attachments'] = fetchRowCounts($conn, $sheetId, 'sheet_attachments');

// ────────────────────────────────────────────────
// 3. Reminders (total count per row)
// ────────────────────────────────────────────────
$counts['reminders'] = fetchRowCounts($conn, $sheetId, 'sheet_reminders');

// ────────────────────────────────────────────────
// 4. Rows with reminder DUE TODAY (optional but useful)
// ────────────────────────────────────────────────
$today = date('Y-m-d');

$todayStmt = $conn->prepare("
    SELECT DISTINCT sheet_row
    FROM sheet_reminders
    WHERE sheet_id = ?
      AND remind_at = ?
    ORDER BY sheet_row
");
if ($todayStmt) {
    $todayStmt->bind_param("is", $sheetId, $today);
    $todayStmt->execute();
    $result = $todayStmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $counts['today_reminder_rows'][] = (int)$row['sheet_row'];
    }
    
    $todayStmt->close();
}

// ────────────────────────────────────────────────
// Final response
// ────────────────────────────────────────────────
echo json_encode([
    "success"             => true,
    "sheet_id"            => $sheetId,
    "comments"            => $counts['comments'],
    "attachments"         => $counts['attachments'],
    "reminders"           => $counts['reminders'],
    "today_reminder_rows" => $counts['today_reminder_rows'] ?? []
]);

$conn->close();
?>