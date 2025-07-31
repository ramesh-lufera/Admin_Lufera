<?php
include 'partials/connection.php';

if (isset($_POST['email']) && isset($_POST['id'])) {
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $id = intval($_POST['id']);

    // Check if the email already exists for a different user
    $sql = "SELECT id FROM users WHERE email = '$email' AND id != $id LIMIT 1";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) > 0) {
        echo "This email is already in use.";
    } else {
        echo "";
    }
}
?>
