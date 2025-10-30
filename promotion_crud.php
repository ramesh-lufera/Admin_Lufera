<?php
// promotion_crud.php
include './partials/connection.php';

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';

function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

try {
    if ($action === 'create') {
        $promo_name = sanitize($_POST['promo_name']);
        $coupon_code = sanitize($_POST['coupon_code']);
        $description = sanitize($_POST['description']);
        $discount = floatval($_POST['discount']);
        $type = sanitize($_POST['type']);
        $start_date = sanitize($_POST['start_date']);
        $end_date = sanitize($_POST['end_date']);
        $apply_to = sanitize($_POST['apply_to']);
        $applied_packages = sanitize($_POST['applied_packages']);
        $applied_products = sanitize($_POST['applied_products']);
        $applied_services = sanitize($_POST['applied_services']);
        $is_active = isset($_POST['is_Active']) ? 1 : 0;

        $stmt = $conn->prepare("INSERT INTO promotion (promo_name, coupon_code, description, discount, type, start_date, end_date, apply_to, applied_packages, applied_products, applied_services, is_Active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssisssssssi", $promo_name, $coupon_code, $description, $discount, $type, $start_date, $end_date, $apply_to, $applied_packages, $applied_products, $applied_services, $is_active);

        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Promotion created successfully']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to create promotion']);
        }
        $stmt->close();
    } elseif ($action === 'update') {
        $id = intval($_POST['id']);
        $promo_name = sanitize($_POST['promo_name']);
        $coupon_code = sanitize($_POST['coupon_code']);
        $description = sanitize($_POST['description']);
        $discount = floatval($_POST['discount']);
        $type = sanitize($_POST['type']);
        $start_date = sanitize($_POST['start_date']);
        $end_date = sanitize($_POST['end_date']);
        $apply_to = sanitize($_POST['apply_to']);
        $applied_packages = sanitize($_POST['applied_packages']);
        $applied_products = sanitize($_POST['applied_products']);
        $applied_services = sanitize($_POST['applied_services']);
        $is_active = isset($_POST['is_Active']) ? 1 : 0;

        $stmt = $conn->prepare("UPDATE promotion SET promo_name = ?, coupon_code = ?, description = ?, discount = ?, type = ?, start_date = ?, end_date = ?, apply_to = ?, applied_packages = ?, applied_products = ?, applied_services = ?, is_Active = ? WHERE id = ?");
        $stmt->bind_param("sssisssssssii", $promo_name, $coupon_code, $description, $discount, $type, $start_date, $end_date, $apply_to, $applied_packages, $applied_products, $applied_services, $is_active, $id);

        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Promotion updated successfully']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to update promotion']);
        }
        $stmt->close();
    } elseif ($action === 'get' && isset($_GET['id'])) {
        $id = intval($_GET['id']);
        $stmt = $conn->prepare("SELECT * FROM promotion WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            echo json_encode(['status' => 'success', 'data' => $row]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Promotion not found']);
        }
        $stmt->close();
    } elseif ($action === 'delete' && isset($_POST['id'])) {
        $id = intval($_POST['id']);
        $stmt = $conn->prepare("DELETE FROM promotion WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Promotion deleted successfully']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to delete promotion']);
        }
        $stmt->close();
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid action or parameters']);
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'An error occurred: ' . $e->getMessage()]);
}

$conn->close();
?>