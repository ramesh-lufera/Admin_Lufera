

<?php
include 'partials/connection.php';

if (isset($_POST['email']) && isset($_POST['id'])) {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $id = intval($_POST['id']);

    $sql = "SELECT id FROM users WHERE email = '$email' AND id != $id";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) > 0) {
        echo "Email already exists.";
    } else {
        echo ""; // No error message
    }
}
?>
