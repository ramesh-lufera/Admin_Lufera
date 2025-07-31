<?php
    // Server
    // $servername = "localhost";
    // $username = "u363064277_lufera";
    // $password = "Lufera@789";
    // $dbname = "u363064277_LI_Dashboard";

    // Local
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "new_db";

    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    } else {
        echo "";
    }
?>