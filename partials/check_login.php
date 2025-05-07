<?php
session_start();

// Check if the user is logged in either via Google or using the regular username
if (!isset($_SESSION['google_email']) && !isset($_SESSION['username'])) {
    // If neither session variable is set, redirect to the sign-in page
    header("Location: sign-in.php");
    exit();
}
?>