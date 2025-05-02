<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: sign-in.php");
    exit();
}
?>