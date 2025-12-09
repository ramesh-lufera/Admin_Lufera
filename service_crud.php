<?php
include './partials/connection.php';
include './log.php';

// Make sure you get the logged-in user ID
session_start();
$user_id = $_SESSION['user_id'] ?? 0;

// Create or Update Service
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action == 'create' || $action == 'update') {
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $name = $conn->real_escape_string($_POST['name']);
        $description = $conn->real_escape_string($_POST['description']);
        $cost = floatval($_POST['cost']);
        $duration = $conn->real_escape_string($_POST['duration']); // e.g. "3 months"
        $isActive = isset($_POST['isActive']) ? 1 : 0;

        if ($action == 'create') {
            $sql = "INSERT INTO `add-on-service` (name, description, cost, duration, is_Active, created_at) 
                    VALUES ('$name', '$description', $cost, '$duration', $isActive, NOW())";
        } else {
            $sql = "UPDATE `add-on-service` 
                    SET name='$name', description='$description', cost=$cost, duration='$duration', is_Active=$isActive 
                    WHERE id=$id";
        }

        if ($conn->query($sql) === TRUE) {

            // -----------------------------
            // LOG ACTIVITY HERE
            // -----------------------------
            if ($action == 'create') {
                logActivity(
                    $conn,
                    $user_id,
                    "Add-on Service",
                    "Add-on Service Created - $name"
                );
            } else {
                logActivity(
                    $conn,
                    $user_id,
                    "Add-on Service",
                    "Add-on Service Updated - $name"
                );
            }

            echo json_encode([
                'status' => 'success', 
                'message' => $action == 'create' ? 'Service created successfully' : 'Service updated successfully'
            ]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error: ' . $conn->error]);
        }
        exit;
    }

    // Delete Service
    if ($action == 'delete') {
        $id = intval($_POST['id']);

        // Get name first for logging
        $nameQuery = $conn->query("SELECT name FROM `add-on-service` WHERE id=$id");
        $serviceName = ($nameQuery && $nameQuery->num_rows > 0)
            ? $nameQuery->fetch_assoc()['name']
            : "Unknown";

        $sql = "DELETE FROM `add-on-service` WHERE id=$id";
        
        if ($conn->query($sql) === TRUE) {

            // -----------------------------
            // LOG DELETE ACTIVITY
            // -----------------------------
            logActivity(
                $conn,
                $user_id,
                "Add-on Service",
                "Add-on Service Deleted - $serviceName"
            );

            echo json_encode(['status' => 'success', 'message' => 'Service deleted successfully']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error: ' . $conn->error]);
        }
        exit;
    }
}

// Read Service
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['action']) && $_GET['action'] == 'get') {
    $id = intval($_GET['id']);
    $sql = "SELECT * FROM `add-on-service` WHERE id=$id";
    $result = $conn->query($sql);
    
    if ($result === FALSE) {
        echo json_encode(['status' => 'error', 'message' => 'Query failed: ' . $conn->error]);
    } elseif ($result->num_rows > 0) {
        $row = $result->fetch_assoc();

        // Split duration into value + unit
        $durationParts = explode(' ', trim($row['duration']));
        $row['duration_value'] = $durationParts[0] ?? '';
        $row['duration_unit'] = $durationParts[1] ?? '';

        echo json_encode(['status' => 'success', 'data' => $row]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Service not found']);
    }
    exit;
}

$conn->close();
?>
