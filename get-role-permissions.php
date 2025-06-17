<?php
include './partials/connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['role_id'])) {
    $role_id = intval($_POST['role_id']);
    $stmt = $conn->prepare("SELECT category_id FROM permission WHERE role_id = ?");
    $stmt->bind_param("i", $role_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $category_ids = [];
    while ($row = $result->fetch_assoc()) {
        $category_ids[] = $row['category_id'];
    }

    echo json_encode($category_ids);
}
?>
