<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');
include './partials/connection.php';

$business_name = mysqli_real_escape_string($conn,$_POST['business_name']);
$email         = mysqli_real_escape_string($conn,$_POST['email']);
$phone         = mysqli_real_escape_string($conn,$_POST['phone']);
$address       = mysqli_real_escape_string($conn,$_POST['address']);
$city          = mysqli_real_escape_string($conn,$_POST['city']);
$state         = mysqli_real_escape_string($conn,$_POST['state']);
$pincode       = mysqli_real_escape_string($conn,$_POST['pincode']);
$gst_in        = mysqli_real_escape_string($conn,$_POST['gst_in']);
$username       = mysqli_real_escape_string($conn,$_POST['username']);

$user_id = 'isc100'.rand(1000,9999);

$sql = "
INSERT INTO users
(
    user_id,
    business_name,
    username,
    email,
    phone,
    address,
    city,
    state,
    pincode,
    gst_in,
    created_at,
    method,
    role,
    is_verified,
    is_deleted
)
VALUES
(
    '$user_id',
    '$business_name',
    '$username',
    '$email',
    '$phone',
    '$address',
    '$city',
    '$state',
    '$pincode',
    '$gst_in',
    NOW(),
    '1',
    '8',
    1,
    0
)
";
if (!$conn) {
    die(json_encode([
        'status' => 'error',
        'message' => mysqli_connect_error()
    ]));
}
if(mysqli_query($conn,$sql)){

    $id = mysqli_insert_id($conn);

    echo json_encode([
        'status'=>'success',
        'user'=>[
            'id'=>$id,
            'business_name'=>$business_name
        ]
    ]);
}
else {
    http_response_code(500);

    echo json_encode([
        'status' => 'error',
        'message' => mysqli_error($conn),
        'query' => $sql
    ]);
}