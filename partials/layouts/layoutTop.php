<!-- meta tags and other links -->
<!DOCTYPE html>
<html lang="en" data-theme="light">

<?php include './partials/head.php';
      include './partials/connection.php';
      include './partials/check_login.php';

    $photo = isset($_SESSION['photo']) ? $_SESSION['photo'] : 'assets/images/user1.png';
?>

<body>

    <?php include './partials/sidebar.php' ?>

    <main class="dashboard-main">
        <?php include './partials/navbar.php' ?>
