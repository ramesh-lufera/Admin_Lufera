<?php
    session_start();
    include './partials/connection.php';
    require_once 'vendor/autoload.php';

    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();

    // Google Client Configuration
    require './partials/google-config.php';
    // $client->setRedirectUri('https://admin.luferatech.com/sign-in-redirect.php');
    $client->setRedirectUri('http://localhost/Admin_Lufera/sign-in-redirect.php');

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

            $fname = $userInfo->givenName;
            $lname = $userInfo->familyName;
            $email = $userInfo->email;
            $username = explode('@', $email)[0];
            $password = '';
            $phone = '';
            $created_at = date("Y-m-d H:i:s");
            $method = "1";
            $role = "user";
            $google_photo = $userInfo->picture;
            $business_name = $address = $city = $state = $country = $pincode = $dob = null;

            // Check if user exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                // User exists: update photo
                $update = $conn->prepare("UPDATE users SET photo = ? WHERE email = ?");
                $update->bind_param("ss", $google_photo, $email);
                $update->execute();
            } else {
                // User doesn't exist - insert new
                $insert = $conn->prepare("INSERT INTO users (user_id, username, email, phone, password, first_name, last_name, business_name, address, city, state, country, pincode, dob, created_at, method, role, photo) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $insert->bind_param("ssssssssssssssssss", $newUserId, $username, $email, $phone, $password, $fname, $lname, $business_name, $address, $city, $state, $country, $pincode, $dob, $created_at, $method, $role, $google_photo);
                $insert->execute();
            }

            // Get user ID and username
            $stmt1 = $conn->prepare("SELECT id, username FROM users WHERE email = ?");
            $stmt1->bind_param("s", $email);
            $stmt1->execute();
            $stmt1->bind_result($id, $username);
            $stmt1->fetch();
            $stmt1->close();

            // Store in session
            $_SESSION['user_id'] = $id;
            $_SESSION['username'] = $username;
            $_SESSION['email'] = $email;

            // Redirect to dashboard
            header("Location: admin-dashboard.php");
            exit;
        } else {
            echo "Google login failed.";
        }
    }

    // If failed, redirect back to login
    header("Location: sign-in.php");
    exit;
