<?php
session_start();

// Destroy all session data
session_unset();
session_destroy();

// Redirect to sign-in page
header("Location: sign-in.php");
exit;
?>
