<?php
session_start();
include './partials/connection.php';
include './log.php';
header('Content-Type: application/json');
$loggedInUserId = $_SESSION['user_id'] ?? 0;
$response = ['success' => false];

if (isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'toggle_status' && isset($_POST['id'], $_POST['status'])) {
        $id = intval($_POST['id']);
        $status = intval($_POST['status']) === 1 ? 0 : 1; // Toggle

        $query = "UPDATE products SET is_active = ? WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('ii', $status, $id);
        if ($stmt->execute()) {
            logActivity(
                $conn,
                $loggedInUserId,
                "Product",                   // module
                "Product status changed successfully"  // description
              );
            $response['success'] = true;
        }
    }

    if ($action === 'delete_product' && isset($_POST['id'])) {
        $id = intval($_POST['id']);
    
        // Get product details first
        $getQuery = "SELECT name FROM products WHERE id = ?";
        $getStmt = $conn->prepare($getQuery);
        $getStmt->bind_param('i', $id);
        $getStmt->execute();
    
        $result = $getStmt->get_result();
        $row = $result->fetch_assoc();
    
        if ($row) {
            // Product name to filename
            $name = $row['name'] ?? null;    
            $nameRawEdit = strtolower(preg_replace('/\s+/', '-', $name));
            $product_name = pathinfo($nameRawEdit, PATHINFO_FILENAME);
    
            // Base directory
            $baseDir = realpath(__DIR__);
    
            // Files to delete
            $filesToDelete = [
                "$baseDir/{$product_name}.php",
                "$baseDir/pages/{$product_name}.php",
            ];
    
            // Delete files if exists
            foreach ($filesToDelete as $file) {
                if (file_exists($file)) {
                    unlink($file);
                }
            }
    
            // Soft delete product
            $query = "UPDATE products SET is_deleted = 1 WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('i', $id);
    
            if ($stmt->execute()) {
                logActivity(
                    $conn,
                    $loggedInUserId,
                    "Product",
                    "Product Deleted"
                );
                $response['success'] = true;
            }
        }
    }
}

echo json_encode($response);
?>