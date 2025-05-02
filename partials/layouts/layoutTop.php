<!-- meta tags and other links -->
<!DOCTYPE html>
<html lang="en" data-theme="light">

<?php include './partials/head.php';
      include './partials/connection.php';
      include './partials/check_login.php';

      $userId = $_SESSION['user_id'];
      $query = "SELECT photo, google_photo FROM users WHERE id = $userId";
      $result = mysqli_query($conn, $query);
      $user = mysqli_fetch_assoc($result);

      // Determine which photo to show
      if (!empty($user['google_photo'])) {
        $photo = $user['google_photo']; // Show Google photo
      } elseif (!empty($user['photo'])) {
        $photo = $user['photo']; // Show uploaded photo (e.g., 'uploads/user1.jpg')
      } else {
        $photo = 'assets/images/user1.png'; // Show default user icon
      }
?>

<body>

    <?php include './partials/sidebar.php' ?>

    <main class="dashboard-main">
        <?php include './partials/navbar.php' ?>
