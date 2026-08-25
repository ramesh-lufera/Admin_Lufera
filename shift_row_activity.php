<?php

header('Content-Type: application/json');

include 'partials/connection.php';

try {

    $input = json_decode(file_get_contents("php://input"), true);

    $sheetId  = intval($input['sheet_id'] ?? 0);
    $insertAt = intval($input['insert_at'] ?? 0);

    if ($sheetId <= 0 || $insertAt <= 0) {
        throw new Exception("Invalid sheet_id or insert_at");
    }

    // Start transaction
    $conn->begin_transaction();


    // -----------------------------------------
    // 1. Shift comments
    // -----------------------------------------
    $stmt = $conn->prepare("
        UPDATE sheet_comments
        SET sheet_row = sheet_row + 1
        WHERE sheet_id = ?
        AND sheet_row >= ?
    ");

    if (!$stmt) {
        throw new Exception(
            "Comments query failed: " . $conn->error
        );
    }

    $stmt->bind_param("ii", $sheetId, $insertAt);
    $stmt->execute();
    $stmt->close();


    // -----------------------------------------
    // 2. Shift attachments
    // -----------------------------------------
    $stmt = $conn->prepare("
        UPDATE sheet_attachments
        SET sheet_row = sheet_row + 1
        WHERE sheet_id = ?
        AND sheet_row >= ?
    ");

    if (!$stmt) {
        throw new Exception(
            "Attachments query failed: " . $conn->error
        );
    }

    $stmt->bind_param("ii", $sheetId, $insertAt);
    $stmt->execute();
    $stmt->close();


    // -----------------------------------------
    // 3. Shift reminders
    // -----------------------------------------
    $stmt = $conn->prepare("
        UPDATE sheet_reminders
        SET sheet_row = sheet_row + 1
        WHERE sheet_id = ?
        AND sheet_row >= ?
    ");

    if (!$stmt) {
        throw new Exception(
            "Reminders query failed: " . $conn->error
        );
    }

    $stmt->bind_param("ii", $sheetId, $insertAt);
    $stmt->execute();
    $stmt->close();


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