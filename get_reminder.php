<?php
header('Content-Type: application/json');

include './partials/connection.php';

$sheetId = isset($_GET['sheet_id']) ? (int)$_GET['sheet_id'] : 0;
$row     = isset($_GET['row']) ? (int)$_GET['row'] : 0;

if ($sheetId <= 0 || $row <= 0) {
    echo json_encode([
        'success' => false,
        'error'   => 'Invalid sheet_id or row',
        'reminders' => [],
    ]);
    exit;
}

// Fetch all reminders for this sheet + row (latest first)
$stmt = $conn->prepare("
    SELECT id, remind_at, message, created_at
    FROM sheet_reminders 
    WHERE sheet_id = ? AND sheet_row = ?
    ORDER BY remind_at DESC, id DESC
");

if (!$stmt) {
    echo json_encode([
        'success'   => false,
        'error'     => $conn->error,
        'reminders' => [],
    ]);
    exit;
}

$stmt->bind_param('ii', $sheetId, $row);
$stmt->execute();
$result = $stmt->get_result();

$reminders = [];

while ($rowData = $result->fetch_assoc()) {
    $rawRemindAt = $rowData['remind_at'];
    $timestamp   = strtotime($rawRemindAt);

    // Format for display and for <input type="datetime-local">
    $forInput   = $timestamp !== false ? date('Y-m-d\TH:i', $timestamp) : $rawRemindAt;
    $forDisplay = $timestamp !== false ? date('d-m-Y H:i', $timestamp) : $rawRemindAt;

    $reminders[] = [
        'id'          => (int)$rowData['id'],
        'remind_at'   => $forInput,
        'display_at'  => $forDisplay,
        'message'     => $rowData['message'],
        'created_at'  => $rowData['created_at'],
    ];
}

$stmt->close();
$conn->close();

echo json_encode([
    'success'   => true,
    'reminders' => $reminders,
]);

