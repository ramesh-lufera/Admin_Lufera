<?php
session_start();

include './partials/connection.php';  // DB connection
include './log.php';         // Log function

// Get user ID BEFORE destroying session
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;

// Log the logout action
logActivity(
    $conn,
    $user_id,
    "Logout",                   // module
    "Logout",                   // action
    "User Logout successfully"  // description
);

// Destroy session AFTER logging
session_unset();
session_destroy();

// Redirect to sign-in page
header("Location: sign-in.php");
exit;
?>
