<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/vendor/autoload.php';
require 'fb-config.php';

// FIX: prevent CSRF mismatch
if (isset($_GET['state'])) {
    $_SESSION['FBRLH_state'] = $_GET['state'];
}

// Generate User ID
function generateUserId() {
    $letters = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 3);
    $numbers = substr(str_shuffle('0123456789'), 0, 3);
    return $letters . $numbers;
}

// Try to get access token
try {
    $accessToken = $helper->getAccessToken();
} catch (Facebook\Exceptions\FacebookResponseException $e) {
    header("Location: sign-in.php");
    exit;
} catch (Facebook\Exceptions\FacebookSDKException $e) {
    header("Location: sign-in.php");
    exit;
}

// FAIL → back to login
if (!$accessToken) {
    header("Location: sign-in.php");
    exit;
}

// Save access token
$_SESSION['facebook_access_token'] = (string)$accessToken;


// Fetch Facebook profile fields including birthday
try {
    $response = $fb->get('/me?fields=id,name,email,birthday,picture.type(large)', $accessToken);
} catch (Exception $e) {
    header("Location: sign-in.php");
    exit;
}

$user = $response->getGraphUser();


// -------------------------------------------
// GET Facebook Birthday (DOB)
// -------------------------------------------
$dob = null;
if ($user->getBirthday()) {
    $dob = $user->getBirthday()->format('Y-m-d'); 
}


// -------------------------------------------
// PREPARE DATA
// -------------------------------------------

include './partials/connection.php';

$userInfo = $user;

$newUserId = generateUserId();
$method = "1";

$fname = $userInfo->getName();
$lname = $userInfo->getName();
$email = $userInfo->getEmail();
$username = explode('@', $email)[0];
$password = '';
$phone = '';
$created_at = date("Y-m-d H:i:s");
$role = "8";
$facebook_photo = $userInfo->getPicture()->getUrl();

$business_name = $address = $city = $state = $country = $pincode = null;


// -------------------------------------------
// CHECK IF USER ALREADY EXISTS
// -------------------------------------------

$check = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
$check->bind_param("s", $email);
$check->execute();
$result = $check->get_result();


if ($result->num_rows > 0) {

    // -----------------------------------
    // USER EXISTS → UPDATE photo & dob ONLY
    // -----------------------------------
    $existing = $result->fetch_assoc();
    $existingUserId = $existing['id'];

    $update = $conn->prepare("
        UPDATE users SET photo = ?, dob = ? WHERE id = ?
    ");
    $update->bind_param("ssi", $facebook_photo, $dob, $existingUserId);
    $update->execute();
    $update->close();

    // SESSION DATA
    $_SESSION['email'] = $email;
    $_SESSION['user_id'] = $existingUserId;
    $_SESSION['username'] = $username;
    $_SESSION['photo'] = $facebook_photo;

} else {

    // -----------------------------------
    // NEW USER → INSERT INTO DATABASE
    // -----------------------------------
    $stmt = $conn->prepare("
        INSERT INTO users 
        (user_id, first_name, last_name, email, username, password, phone, created_at, method, role, photo, business_name, address, city, state, country, pincode, dob)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param(
        "ssssssssssssssssss",
        $newUserId,
        $fname,
        $lname,
        $email,
        $username,
        $password,
        $phone,
        $created_at,
        $method,
        $role,
        $facebook_photo,
        $business_name,
        $address,
        $city,
        $state,
        $country,
        $pincode,
        $dob
    );

    $stmt->execute();

    $insertedId = $conn->insert_id;

    $stmt->close();

    // SESSION DATA
    $_SESSION['email'] = $email;
    $_SESSION['user_id'] = $insertedId;
    $_SESSION['username'] = $username;
    $_SESSION['photo'] = $facebook_photo;
}

$check->close();


// -------------------------------------------
// FINAL REDIRECT SUCCESS
// -------------------------------------------
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Redirecting…</title>
    <script>
        window.location.replace("admin-dashboard.php");
    </script>
</head>
<body>
Redirecting…
</body>
</html>
