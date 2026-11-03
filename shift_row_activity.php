<?php

header('Content-Type: application/json');

include 'partials/connection.php';

try {

    $input = json_decode(file_get_contents("php://input"), true);

    $sheetId = intval($input['sheet_id'] ?? 0);
    $mode    = $input['mode'] ?? 'insert';

    if ($sheetId <= 0) {
        throw new Exception("Invalid sheet_id");
    }

    $activityTables = ['sheet_comments', 'sheet_attachments', 'sheet_reminders'];

    // Start transaction
    $conn->begin_transaction();

    if ($mode === 'delete') {

        // -----------------------------------------
        // Row(s) removed: drop their activity rows,
        // then pull every row below them up by one.
        // Rows must be processed highest-to-lowest so
        // earlier shifts don't affect rows still queued.
        // -----------------------------------------
        $rows = $input['rows'] ?? (isset($input['row']) ? [$input['row']] : []);
        $rows = array_values(array_unique(array_map('intval', (array) $rows)));
        rsort($rows, SORT_NUMERIC);

        if (empty($rows)) {
            throw new Exception("No rows provided to delete");
        }

        foreach ($rows as $deletedRow) {
            if ($deletedRow <= 0) {
                continue;
            }

            foreach ($activityTables as $table) {
                $stmt = $conn->prepare("
                    DELETE FROM {$table}
                    WHERE sheet_id = ?
                    AND sheet_row = ?
                ");
                if (!$stmt) {
                    throw new Exception("{$table} delete query failed: " . $conn->error);
                }
                $stmt->bind_param("ii", $sheetId, $deletedRow);
                $stmt->execute();
                $stmt->close();

                $stmt = $conn->prepare("
                    UPDATE {$table}
                    SET sheet_row = sheet_row - 1
                    WHERE sheet_id = ?
                    AND sheet_row > ?
                ");
                if (!$stmt) {
                    throw new Exception("{$table} shift query failed: " . $conn->error);
                }
                $stmt->bind_param("ii", $sheetId, $deletedRow);
                $stmt->execute();
                $stmt->close();
            }
        }

    } else {

        $insertAt = intval($input['insert_at'] ?? 0);

        if ($insertAt <= 0) {
            throw new Exception("Invalid insert_at");
        }

        foreach ($activityTables as $table) {
            $stmt = $conn->prepare("
                UPDATE {$table}
                SET sheet_row = sheet_row + 1
                WHERE sheet_id = ?
                AND sheet_row >= ?
            ");

            if (!$stmt) {
                throw new Exception("{$table} query failed: " . $conn->error);
            }

            $stmt->bind_param("ii", $sheetId, $insertAt);
            $stmt->execute();
            $stmt->close();
        }
    }

    // Everything successful
    $conn->commit();

    echo json_encode([
        "success" => true
    ]);

} catch (Throwable $e) {

    if (isset($conn)) {
        $conn->rollback();
    }

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "error" => $e->getMessage()
    ]);
}