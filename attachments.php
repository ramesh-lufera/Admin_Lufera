<?php
include 'partials/connection.php';

$sheet_id = intval($_GET['sheet_id'] ?? 0);
$sheet_row = intval($_GET['row'] ?? 0);

$files = [];

if ($sheet_id > 0 && $sheet_row > 0) {
    $stmt = $conn->prepare("
        SELECT 
            a.id,
            a.original_name,
            a.file_path,
            a.file_size,
            a.mime_type,
            a.created_at,
            a.created_by,
            TRIM(CONCAT_WS(' ', u.first_name, u.last_name)) AS uploaded_by
        FROM sheet_attachments a
        LEFT JOIN users u ON u.id = a.created_by
        WHERE a.sheet_id = ? 
          AND a.sheet_row = ?
        ORDER BY a.created_at DESC
    ");

    $stmt->bind_param("ii", $sheet_id, $sheet_row);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        // If no name found → fallback to username or email or User #ID
        if (empty(trim($row['uploaded_by']))) {
            $row['uploaded_by'] = $row['username'] ?? $row['email'] ?? "Form User";
            // In the SELECT query or after fetch
            $row['created_at_formatted'] = date('d/m/Y', strtotime($row['created_at']));
        }
        $files[] = $row;
    }

    $stmt->close();
}

header('Content-Type: application/json');
echo json_encode($files);