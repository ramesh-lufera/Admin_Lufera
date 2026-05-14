<?php

// CURRENT FILE
$currentFile = basename($_SERVER['PHP_SELF']);

// ALLOW maintenance page
if ($currentFile == 'maintenance.php') {
    return;
}

// CHECK DATABASE
$maintenanceQuery = mysqli_query(
    $conn,
    "SELECT maintenance_mode FROM company LIMIT 1"
);

// FETCH DATA
$maintenanceData = mysqli_fetch_assoc($maintenanceQuery);

// IF ENABLED
if (
    isset($maintenanceData['maintenance_mode']) &&
    $maintenanceData['maintenance_mode'] == '1'
) {

    include 'maintenance.php';
    exit;
}
?>