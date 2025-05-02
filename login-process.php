<?php
session_start();
include './partials/connection.php';

$response = [
    'success' => false,
    'errors' => []
];

if (isset($_POST['email']) && isset($_POST['password'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, password, username FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($user_id, $db_password, $username);
        $stmt->fetch();

        if ($password === $db_password) {
            $_SESSION['user_id'] = $user_id;
            $_SESSION['username'] = $username;
            $_SESSION['email'] = $email;
            $response['success'] = true;
        } else {
            $response['errors']['password'] = 'Incorrect password!';
        }
    } else {
        $response['errors']['email'] = 'Email not found!';
    }

    $stmt->close();
}
$conn->close();

header('Content-Type: application/json');
echo json_encode($response);
