<?php
    session_start();
    include './partials/connection.php';
    require_once 'vendor/autoload.php';

    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();

    // Google Client Configuration
    $client = new Google_Client();
    $client->setClientId($_ENV['GOOGLE_CLIENT_ID']);
    $client->setClientSecret($_ENV['GOOGLE_CLIENT_SECRET']);
    $client->setRedirectUri('http://localhost/Admin_Lufera/sign-up-redirect.php');
    // $client->setRedirectUri('https://admin.luferatech.com/sign-up-redirect.php');
    $client->addScope('email');
    $client->addScope('profile');

    if (isset($_GET['code'])) {
        $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
        $client->setAccessToken($token);

        $google_service = new Google_Service_Oauth2($client);
        $google_user = $google_service->userinfo->get();

        $email = $google_user->email;
        $username = explode('@', $email)[0];
        $password = '';
        $first_name = $google_user->name;
        $nameParts = explode(' ', trim($first_name));
        $fname = $nameParts[0];
        $phone = '';
        $created_at = date("Y-m-d H:i:s");
        $method = "1";
        $role = "user";
        $photo = $google_user->picture;
        $lname = $business_name = $address = $city = $state = $country = $pincode = $dob = null;

        // Check if user already exists
        $stmt = $conn->prepare("SELECT id, username, photo FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($user = $result->fetch_assoc()) {
            // User exists - log in
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];

            // Update the photo column with the Google photo if it's not already the same
            if ($user['photo'] !== $photo) {
                $updateStmt = $conn->prepare("UPDATE users SET photo = ? WHERE email = ?");
                $updateStmt->bind_param("ss", $photo, $email);
                $updateStmt->execute();
                $updateStmt->close();
            }
        } else {
            // User doesn't exist - insert new
            function generateUserId() {
                $letters = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 3);
                $numbers = substr(str_shuffle('0123456789'), 0, 3);
                return $letters . $numbers;
            }
            
            $newUserId = generateUserId();

            $stmt = $conn->prepare("INSERT INTO users (user_id, username, email, phone, password, first_name,last_name,business_name,address,city,state,country,pincode,dob,created_at,method,role,photo ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssssssssssssss", $newUserId, $username, $email, $phone, $password, $fname, $lname, $business_name, $address, $city, $state, $country, $pincode, $dob, $created_at, $method, $role, $photo);
            $stmt->execute();
    
            $_SESSION['user_id'] = $stmt->insert_id;
            $_SESSION['username'] = $username;
            $_SESSION['photo'] = $photo;
        }

        $stmt->close();
        header('Location: admin-dashboard.php');
        exit;
    } else {
        echo "Google login failed.";
    }
?>
