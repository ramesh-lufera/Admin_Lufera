<?php
    session_start();
    include './partials/connection.php';
    require_once 'vendor/autoload.php';

    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();

    // Google Client Configuration
    require './partials/google-config.php';
    // $client->setRedirectUri('https://admin.luferatech.com/sign-up-redirect.php');
    $client->setRedirectUri('http://localhost/Admin_Lufera/sign-up-redirect.php');

    if (isset($_GET['code'])) {
        $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
        $client->setAccessToken($token);

        $google_service = new Google_Service_Oauth2($client);
        $google_user = $google_service->userinfo->get();

        $fname = $google_user->givenName;
        $lname = $google_user->familyName;
        $email = $google_user->email;
        $username = explode('@', $email)[0];
        $password = '';
        $phone = '';
        $created_at = date("Y-m-d H:i:s");
        $method = "1";
        $role = "7";
        $photo = $google_user->picture;
        $business_name = $address = $city = $state = $country = $pincode = $dob = null;

        // Check if user exists
        $stmt = $conn->prepare("SELECT id, username FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            // User exists - log in
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];

            // User exists: update photo
            $update = $conn->prepare("UPDATE users SET photo = ? WHERE email = ?");
            $update->bind_param("ss", $photo, $email);
            $update->execute();
        } else {
            // User doesn't exist - insert new
            function generateUserId() {
                $letters = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 3);
                $numbers = substr(str_shuffle('0123456789'), 0, 3);
                return $letters . $numbers;
            }
            
            $newUserId = generateUserId();

            $insert = $conn->prepare("INSERT INTO users (user_id, username, email, phone, password, first_name, last_name, business_name, address, city, state, country, pincode, dob, created_at, method, role, photo) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $insert->bind_param("ssssssssssssssssss", $newUserId, $username, $email, $phone, $password, $fname, $lname, $business_name, $address, $city, $state, $country, $pincode, $dob, $created_at, $method, $role, $photo);
            $insert->execute();

            $_SESSION['user_id'] = $insert->insert_id;
            $_SESSION['username'] = $username;
        }

        header('Location: admin-dashboard.php');
        exit;
    } else {
        echo "Google login failed.";
    }
?>
