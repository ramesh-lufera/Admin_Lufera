<!-- meta tags and other links -->
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
</head>

<?php include './partials/head.php';
      include './partials/connection.php';
      include './partials/check_login.php';

        $photo = isset($_SESSION['photo']) ? $_SESSION['photo'] : 'assets/images/user1.png';

        $loggedInUserId = $_SESSION['user_id'];

        $sql = "select user_id from users where id = $loggedInUserId";
        $result = $conn ->query($sql);
        $row = $result ->fetch_assoc();
        $UserId = $row['user_id'];

        $stmt = $conn->prepare("SELECT * FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC LIMIT 5");
        $stmt->bind_param("s", $UserId);
        $stmt->execute();
        $notifications = $stmt->get_result();
        $notiCount = $notifications->num_rows;
?>

<body>

    <?php include './partials/sidebar.php' ?>

    <main class="dashboard-main">
        <?php include './partials/navbar.php' ?>
