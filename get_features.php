<?php
// update.php
include './partials/connection.php';
if (isset($_GET['package_id'])) {
    $package_id = intval($_GET['package_id']);

    $sql = "SELECT feature FROM features WHERE package_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $package_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $features = [];
    while ($row = $result->fetch_assoc()) {
        $features[] = $row['feature'];
    }

    header('Content-Type: application/json');
    echo json_encode($features);
}
?>