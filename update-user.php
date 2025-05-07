<?php
include 'partials/connection.php';

if (isset($_POST['id'])) {
    $id = intval($_POST['id']);
    $fname  = $_POST['fname'];
    $lname  = $_POST['lname'];
    $uname  = $_POST['uname'];
    $bname  = $_POST['bname'];
    $email  = $_POST['email'];
    $phone  = $_POST['phone'];
    $dob  = $_POST['dob'];
    $address  = $_POST['address'];
    $city  = $_POST['city'];
    $state  = $_POST['state'];
    $country  = $_POST['country'];
    $pin = $_POST['pin'];
    $pass = $_POST['pass'];
    $role = $_POST['role'];

    $stmt = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, username = ?, business_name = ?, email = ?, phone = ?, password = ?, address = ?, city = ?, state = ?, country = ?, pincode = ?, dob = ?, role = ?  WHERE id = ?");
    $stmt->bind_param("ssssssssssssssi", $fname, $lname, $uname, $bname, $email, $phone, $pass, $address, $city, $state, $country, $pin, $dob, $role, $id);

    if ($stmt->execute()) {
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false, "error" => $stmt->error]);
    }
}
?>
