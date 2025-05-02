<?php
session_start();

// Check if the user is logged in
if (isset($_SESSION['email']) && isset($_SESSION['google_login']) && $_SESSION['google_login'] === true) {
    include 'partials/connection.php';  // Include your database connection

    $email = $_SESSION['email'];  // Get the logged-in user's email

    // Delete the user record from the database (for Google login users only)
    $stmt = $conn->prepare("DELETE FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);  // Bind the email parameter
    $stmt->execute();  // Execute the deletion query

    // Check if the row was deleted successfully
    // if ($stmt->affected_rows > 0) {
    //     // User data deleted successfully from the database
    //     echo "User data deleted successfully!";
    // } else {
    //     // User data not found in the database
    //     echo "No data found for this user.";
    // }

    // Close the statement
    $stmt->close();
    
    // Clear the session data
    session_unset();
    session_destroy();

    // Redirect to the login page after logout
    header("Location: sign-in.php");
    exit();
} else {
    // If the user is logged in manually or no Google login, just log out without deleting data
    session_unset();
    session_destroy();

    // Redirect to the login page
    header("Location: sign-in.php");
    exit();
}
?>
