<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/vendor/autoload.php';

// Facebook App configuration
$fb = new \Facebook\Facebook([
    'app_id'                => '873435845242782',
    'app_secret'            => 'ab40f37beaf91b507c505821763f7cac',
    'default_graph_version' => 'v19.0',
]);

$helper = $fb->getRedirectLoginHelper();

// FIX for Facebook "state" mismatch
if (isset($_GET['state'])) {
    $_SESSION['FBRLH_state'] = $_GET['state'];
}

// IMPORTANT — Add birthday permission
$permissions = ['email', 'user_birthday'];

// Your callback URL
$callbackUrl = "http://localhost/Admin_Lufera/fb-callback.php";

$loginUrl1 = $helper->getLoginUrl($callbackUrl, $permissions);
