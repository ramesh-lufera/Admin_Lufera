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

// Check if a file has been uploaded
if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
    // Set the target directory for the uploaded file
    $targetDir = "assets/"; // Ensure this folder is writable
    $targetFile = $targetDir . basename($_FILES["photo"]["name"]);

    // Validate file type (optional)
    $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
    if (!in_array($_FILES['photo']['type'], $allowedTypes)) {
        die("Invalid file type.");
    }

    // Validate file size (optional, 5MB limit here)
    if ($_FILES['photo']['size'] > 5 * 1024 * 1024) {
        die("File size too large. Max 5MB allowed.");
    }

    // Sanitize the file name
    $sanitizedFileName = preg_replace("/[^a-zA-Z0-9\-_\.]/", "_", $_FILES["photo"]["name"]);

    // Move the uploaded file to the target directory
    $targetFile = $targetDir . $sanitizedFileName;
    if (move_uploaded_file($_FILES['photo']['tmp_name'], $targetFile)) {
        // Update the database with the file path
        $photoPath = $targetFile;
        // Update the photo path in the database
        $stmt = $conn->prepare("UPDATE users SET photo = ? WHERE id = ?");
        $stmt->bind_param("si", $photoPath, $id);
        if (!$stmt->execute()) {
            $_SESSION['photo'] = $photoPath;
            echo "Error updating profile photo: " . $stmt->error;
        }
        $stmt->close();
    } else {
        echo "Error uploading file.";
        exit;
    }
}
 
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