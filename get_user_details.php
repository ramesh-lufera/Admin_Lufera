<?php
include './partials/connection.php';

if(isset($_POST['user_id']))
{
    $user_id = intval($_POST['user_id']);

    $query = mysqli_query($conn,"
        SELECT
            business_name,
            address,
            city,
            state,
            pincode,
            gst_in,
            country
        FROM users
        WHERE id = '$user_id'
    ");

    $row = mysqli_fetch_assoc($query);

    echo json_encode($row);
}