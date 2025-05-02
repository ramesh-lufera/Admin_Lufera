<?php
session_start();
require_once 'vendor/autoload.php';
include './partials/connection.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();

// Google Client setup
$client = new Google_Client();
$client->setClientId($_ENV['GOOGLE_CLIENT_ID']);
$client->setClientSecret($_ENV['GOOGLE_CLIENT_SECRET']);
$client->setRedirectUri('http://localhost/Lufera-Infotech/sign-in-redirect.php');
// $client->setRedirectUri('https://admin.luferatech.com/sign-in-redirect.php');
$client->addScope('email');
$client->addScope('profile');

function generateUserId() {
    $letters = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 3);
    $numbers = substr(str_shuffle('0123456789'), 0, 3);
    return $letters . $numbers;
}

$newUserId = generateUserId();
$method = "1";

if (isset($_GET['code'])) {
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);

    if (!isset($token['error'])) {
        $client->setAccessToken($token['access_token']);
        $google_service = new Google_Service_Oauth2($client);
        $userInfo = $google_service->userinfo->get();

        $email = $userInfo->email;
        $username = $userInfo->name;
        $google_photo = $userInfo->picture;
        $password = ''; // optional - leave empty for Google login

        // Check if user exists
        $stmt = $conn->prepare("SELECT id, username FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows == 0) {
            // New user, insert into database
            // $stmt_insert = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            // $stmt_insert->bind_param("sss", $username, $email, $password);
            $stmt_insert = $conn->prepare("INSERT INTO users (user_id, username, email, phone, password, first_name,last_name,business_name,address,city,state,country,pincode,dob,created_at,method,role, photo, google_photo ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt_insert->bind_param("sssssssssssssssssss", $newUserId, $username, $email, $phone, $password, $fname, $lname, $business_name, $address, $city, $state, $country, $pincode, $dob, $created_at, $method, $role, $photo, $google_photo);
            $stmt_insert->execute();
            $stmt_insert->close();
        }

        // Get user ID and username
        $stmt2 = $conn->prepare("SELECT id, username FROM users WHERE email = ?");
        $stmt2->bind_param("s", $email);
        $stmt2->execute();
        $stmt2->bind_result($id, $usernameFetched);
        $stmt2->fetch();
        $stmt2->close();

        // Set session variables
        $_SESSION['google_login'] = true;
        $_SESSION['user_id'] = $id;
        $_SESSION['username'] = $usernameFetched;
        $_SESSION['email'] = $email;

        // Redirect to dashboard
        header("Location: admin-dashboard.php");
        exit;
    }
}

// If failed, redirect back to login
header("Location: sign-in.php");
exit;
