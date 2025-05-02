<?php
// update.php
$host = 'localhost';
$db = 'lufera infotech';
$user = 'root';
$pass = '';
 
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
 
$id    = $_POST['id'];
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
 
// Basic validation (you can expand this)
// if (!$id || !$fname || !$lname || !$uname || !$bname || !$email || !$phone || !$address || !$city || !$state || !$country || !$pin) {
//     echo "All fields are required.";
//     exit;
// }
 
//
//$stmt = $conn->prepare("UPDATE users SET name=?, email=? WHERE id=?");
$stmt = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, username = ?, business_name = ?, email = ?, phone = ?, address = ?, city = ?, state = ?, country = ?, pincode = ?, dob = ?  WHERE id = ?");
$stmt->bind_param("ssssssssssssi", $fname, $lname, $uname, $bname, $email, $phone, $address, $city, $state, $country, $pin, $dob, $id);
if ($stmt->execute()) {
    echo "<script>location.reload();</script>";
} else {
    echo "Error updating user: " . $stmt->error;
}
 
$stmt->close();
$conn->close();
?>