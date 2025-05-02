<?php
session_start();
require_once 'vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();

// Google Client Configuration
$client = new Google_Client();
$client->setClientId($_ENV['GOOGLE_CLIENT_ID']);
$client->setClientSecret($_ENV['GOOGLE_CLIENT_SECRET']);
$client->setRedirectUri('http://localhost/Lufera-Infotech/sign-up-redirect.php');
// $client->setRedirectUri('https://admin.luferatech.com/sign-up-redirect.php');
$client->addScope('email');
$client->addScope('profile');

if (isset($_GET['code'])) {
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    $client->setAccessToken($token);

    $google_service = new Google_Service_Oauth2($client);
    $google_user = $google_service->userinfo->get();

    // Save user info to session
    $_SESSION['google_name'] = $google_user->name;
    $_SESSION['google_email'] = $google_user->email;
    $_SESSION['google_password'] = bin2hex(random_bytes(4)); // Random 8-char password

    // Redirect to signup.php with autofill
    header('Location: sign-up.php');
    exit;
} else {
    echo "Google login failed.";
}
?>
