<?php
session_start();
include 'partials/connection.php';
include './log.php';         // Log function
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
if (isset($_POST['id'])) {
    $id = intval($_POST['id']);
    $fname  = $_POST['fname'];
    $lname  = $_POST['lname'];
    $uname  = $_POST['uname'];
    $bname  = $_POST['bname'];
    $gst_in  = $_POST['gst_in'];
    $email  = $_POST['email'];
    $phone  = $_POST['phone'];
    $dob  = $_POST['dob'];
    $address  = $_POST['address'];
    $city  = $_POST['city'];
    $state  = $_POST['state'];
    $country  = $_POST['country'];
    $pin = $_POST['pin'];
    $pass = $_POST['pass'];

    $stmt = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, username = ?, business_name = ?, gst_in = ?, email = ?, phone = ?, password = ?, address = ?, city = ?, state = ?, country = ?, pincode = ?, dob = ?  WHERE id = ?");
    $stmt->bind_param("ssssssssssssssi", $fname, $lname, $uname, $bname, $gst_in, $email, $phone, $pass, $address, $city, $state, $country, $pin, $dob, $id);

    if ($stmt->execute()) {
        logActivity(
            $conn,
            $user_id,
            "Users",                   // module
            "User Updated",                   // action
            "User Updated Successfully for $fname $lname"  // description
        );
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false, "error" => $stmt->error]);
    }
}
?>
