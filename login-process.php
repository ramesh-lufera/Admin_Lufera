<?php
session_start();
include './partials/connection.php';
include './log.php';

$response = [
    'success' => false,
    'errors' => []
];

if (!empty($_POST['email']) && !empty($_POST['password'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, password, username, photo FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($user_id, $db_password, $username, $photo);
        $stmt->fetch();

        if ($password === $db_password) {
            // Set session
            $_SESSION['user_id'] = $user_id;
            $_SESSION['username'] = $username;
            $_SESSION['user'] = $username;
            $_SESSION['email'] = $email;
            $_SESSION["photo"] = $photo;

            // 🔥 SET COOKIE FOR WORDPRESS
            $redirectUrl = $_POST['redirect'] ?? '';
            $host = parse_url($redirectUrl, PHP_URL_HOST);

            $cookieDomain = $host 
                ? '.' . preg_replace('/^www\./', '', $host) 
                : '.' . preg_replace('/^www\./', '', $_SERVER['HTTP_HOST']);

            setcookie(
                "lufera_user_logged_in",
                "1",
                time() + (86400 * 7),
                "/",
                $cookieDomain,
                true,
                true
            );

            // 🔥 LOG ACTIVITY HERE
            logActivity(
                $conn,
                $user_id,
                "sign-in",        // module
                "User logged in successfully"         // description
            );

            $response['success'] = true;

            $response['redirect'] = $_POST['redirect'] ?? '';
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
