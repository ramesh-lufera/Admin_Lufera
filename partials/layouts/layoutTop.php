<?php include './partials/head.php';
      include './partials/connection.php';
      include './partials/check_login.php';
      include './log.php';

      $loggedInUserId = $_SESSION['user_id'];

      $photo = 'assets/images/user1.png';

      if (isset($_SESSION['email'])) {
        $email = $_SESSION['email'];
        $stmt = $conn->prepare("SELECT photo FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($user = $result->fetch_assoc()) {
            if (!empty($user['photo'])) {
                $photo = $user['photo'];
            }
        }
    }

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

<!-- meta tags and other links -->
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <style>
    @media (max-width: 576px) {
        #notificationBadge {
            font-size: 8px !important;
            padding: 2px 4px !important;
            bottom: -2px !important;
            right: -2px !important;
        }
    }

    /* Notification */
    .user-photo {
        width: 40px;
        height: 40px;
        object-fit: cover;
        border: 1px solid #dee2e6;
    }

    .notification-item {
        transition: background-color 0.3s ease;
        cursor: pointer;
    }

    .notification-item:hover {
        background-color: #f8f9fa;
    }

    .notification-message {
        font-size: 0.95rem;
        word-break: break-word;
    }

    /* Responsive improvements */
    @media (max-width: 576px) {
        .notification-item {
            flex-direction: column;
            align-items: flex-start;
        }

        .user-photo {
            width: 36px;
            height: 36px;
            margin-bottom: 0.5rem;
        }
    }
  </style>
</head>

<body>

    <?php include './partials/sidebar.php' ?>
    <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.0/css/all.min.css" integrity="sha512-DxV+EoADOkOygM4IR9yXP8Sb2qwgidEmeqAEmDKIOfPRQZOWbXCzLC6vjbZyy0vPisbH2SyW27+ddLVCN+OMzQ==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <main class="dashboard-main">
        <?php include './partials/navbar.php' ?>

<script>
  document.addEventListener('DOMContentLoaded', function () {
      const bell = document.getElementById('notificationBell');
      const badge = document.getElementById('notificationBadge');

      bell.addEventListener('click', function () {
          fetch('mark_notifications_read.php', {
              method: 'POST'
          })
          .then(res => res.json())
          .then(data => {
              if (data.success && badge) {
                  badge.remove(); // Hide red badge without reload
              }
          })
          .catch(err => console.error('Failed to mark notifications as read:', err));
      });
  });
</script>

