<?php
include './partials/connection.php';

ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {

    // === CREATE PROMOTION ===
    if ($action === 'create') {

        $promo_name  = $_POST['promo_name'] ?? '';
        $coupon_code  = $_POST['coupon_code'] ?? '';
        $description = $_POST['description'] ?? '';
        $discount    = $_POST['discount'] ?? 0;
        $type        = $_POST['type'] ?? '';
        $start_date  = $_POST['start_date'] ?? null;
        $end_date    = $_POST['end_date'] ?? null;
        $apply_to    = $_POST['apply_to'] ?? '';
        $is_Active   = isset($_POST['is_Active']) ? 1 : 0;

        // ✅ Validation
        if (empty($promo_name) || empty($discount) || empty($type)) {
            echo json_encode(['status' => 'error', 'message' => 'Required fields are missing.']);
            exit;
        }

        $stmt = $conn->prepare("
            INSERT INTO promotion (promo_name, coupon_code, description, discount, type, start_date, end_date, apply_to, is_Active)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        if (!$stmt) {
            echo json_encode(['status' => 'error', 'message' => 'Prepare failed: ' . $conn->error]);
            exit;
        }

        // discount may be DECIMAL or VARCHAR — adjust type if needed
        $stmt->bind_param('sssdssssi', 
            $promo_name, 
            $coupon_code,
            $description, 
            $discount, 
            $type, 
            $start_date, 
            $end_date, 
            $apply_to, 
            $is_Active
        );

        if (!$stmt->execute()) {
            echo json_encode(['status' => 'error', 'message' => 'Execute failed: ' . $stmt->error]);
            exit;
        }

        echo json_encode(['status' => 'success', 'message' => 'Promotion added successfully.']);
        exit;
    }

    // === UPDATE PROMOTION ===
    elseif ($action === 'update') {

        $id          = $_POST['id'] ?? 0;
        $promo_name  = $_POST['promo_name'] ?? '';
        $coupon_code  = $_POST['coupon_code'] ?? '';
        $description = $_POST['description'] ?? '';
        $discount    = $_POST['discount'] ?? 0;
        $type        = $_POST['type'] ?? '';
        $start_date  = $_POST['start_date'] ?? null;
        $end_date    = $_POST['end_date'] ?? null;
        $apply_to    = $_POST['apply_to'] ?? '';
        $is_Active   = isset($_POST['is_Active']) ? 1 : 0;

        $stmt = $conn->prepare("
            UPDATE promotion 
            SET promo_name=?, coupon_code=?, description=?, discount=?, type=?, start_date=?, end_date=?, apply_to=?, is_Active=? 
            WHERE id=?
        ");

        if (!$stmt) {
            echo json_encode(['status' => 'error', 'message' => 'Prepare failed: ' . $conn->error]);
            exit;
        }

        $stmt->bind_param('sssdssssii', 
            $promo_name, 
            $coupon_code,
            $description, 
            $discount, 
            $type, 
            $start_date, 
            $end_date, 
            $apply_to, 
            $is_Active, 
            $id
        );

        if (!$stmt->execute()) {
            echo json_encode(['status' => 'error', 'message' => 'Execute failed: ' . $stmt->error]);
            exit;
        }

        echo json_encode(['status' => 'success', 'message' => 'Promotion updated successfully.']);
        exit;
    }

    // === DELETE PROMOTION ===
    elseif ($action === 'delete') {

        $id = $_POST['id'] ?? 0;

        $stmt = $conn->prepare("DELETE FROM promotion WHERE id=?");
        $stmt->bind_param('i', $id);
        $stmt->execute();

        echo json_encode(['status' => 'success', 'message' => 'Promotion deleted successfully.']);
        exit;
    }

    // === GET SINGLE PROMOTION ===
    elseif ($action === 'get') {

        $id = $_GET['id'] ?? 0;
        $stmt = $conn->prepare("SELECT * FROM promotion WHERE id=?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        echo json_encode(['status' => 'success', 'data' => $result]);
        exit;
    }

    else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid action.']);
        exit;
    }

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
