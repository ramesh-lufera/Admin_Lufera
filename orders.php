<!-- meta tags and other links -->
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<title>Orders</title>
<style>
    /* Styling for disabled button to appear blurred */
    .disabled {
        pointer-events: none;  /* Prevents clicking */
        opacity: 0.5;  /* Makes the button appear blurred */
    }
</style>
</head>

<?php 
    include './partials/layouts/layoutTop.php';

    $Id = $_SESSION['user_id'];

    // Fetch users data from the database
    $sql = "SELECT * FROM users ORDER BY created_at ASC";
    $result = mysqli_query($conn, $sql);
    
    $sql = "select * from users where id = $Id";
    $result = $conn ->query($sql);
    $row = $result ->fetch_assoc();
    $role = $row['role'];
    $UserId = $row['user_id'];
    $photo = !empty($row['photo']) ? $row['photo'] : 'assets/images/user1.png';

    // ADMIN approves → Notify USER
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['approve_id']) && ($role === '1' || $role === '2')) {
        $orderId = intval($_POST['approve_id']);

        // Approve order
        $conn->query("UPDATE orders SET status = 'Approved' WHERE id = $orderId");

        date_default_timezone_set('Asia/Kolkata');

        // Get user_id for notification
        $res = $conn->query("SELECT user_id FROM orders WHERE id = $orderId");
        $order = $res->fetch_assoc();
        $userId = $order['user_id'];

        // Add notification
        $msg = "Your payment has been approved.";
        $createdAt = date('Y-m-d H:i:s');
        $stmt = $conn->prepare("INSERT INTO notifications (user_id, message, n_photo, created_at) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $userId, $msg, $photo, $createdAt);
        $stmt->execute();
    }
    
    // JOIN orders with users
    $query = "
    SELECT
        orders.id, 
        orders.invoice_id,
        orders.plan,
        orders.amount,
        orders.status,
        orders.created_on,
        users.username,
        users.first_name,
        users.last_name,
        users.photo
    FROM orders
    INNER JOIN users ON orders.user_id = users.user_id
    ";

    if ($role !== '1' && $role !== '2') {
        if (!empty($UserId)) {
            $query .= " WHERE orders.user_id = '$UserId'";
        } else {
            $query .= " WHERE 1 = 0";
        }
    }

    // Get active symbol
    $result = $conn->query("SELECT symbol FROM currencies WHERE is_active = 1 LIMIT 1");
    $symbol = "$"; // default
    if ($row = $result->fetch_assoc()) {
        $symbol = $row['symbol'];
    }

    $result = mysqli_query($conn, $query);
?>

<body>
    <div class="dashboard-main-body">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
            <h6 class="fw-semibold mb-0">Orders</h6>
        </div>
        <div class="card">
            <div class="card-body">
            <div class="table-responsive scroll-sm">
                <table class="table bordered-table mb-0" id="userTable">
                    <thead>
                        <tr>
                            <th scope="col">Name</th>
                            <th scope="col" class="text-center">Invoice ID</th>
                            <th scope="col" class="text-center">Date</th>
                            <th scope="col" class="text-center">Amount</th>
                            <th scope="col" class="text-center">Status</th>
                            <th scope="col" class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                    <?php
                        $photo = !empty($row['photo']) ? $row['photo'] : 'assets/images/user1.png';
                        $status = $row['status'];
                    ?>
                        <tr>
                            <td>
                                <div class="fw-medium">
                                    <img src="<?= $photo ?>" alt="" class="flex-shrink-0 me-12 radius-8" style="width: 30px; height: 30px;">
                                    <?php echo $row['first_name']; ?> <?php echo $row['last_name']; ?>
                                </div>
                            </td>
                            <td class="text-center"><?php echo $row['invoice_id']; ?></td>
                            <td class="text-center"><?= date('d M Y', strtotime($row['created_on'])) ?></td>
                            <td class="text-center" id="currency-symbol-display"><?= htmlspecialchars($symbol) ?> <?= number_format($row['amount'], 2) ?></td>
                           

                            <td class="text-center">
                                <?php if (($role === '1' || $role === '2') && $row['status'] === 'Pending'){ ?>
                                    <input type="hidden" name="approve_id" value="<?= $row['id'] ?>">
                                    <button class="btn btn-danger btn-sm fw-medium text-white me-2">
                                            New Order
                                    </button>
                                <?php } else if(($role != '1' || $role != '2') && $row['status'] === 'Pending'){ ?>
                                    <button class="btn btn-danger btn-sm fw-medium text-white me-2">
                                        Pending Confirmation
                                    </button>
                                <?php } ?>
                                <?php if (($role === '1' || $role === '2') && $row['status'] === 'Approved'){ ?>
                                    <input type="hidden" name="approve_id" value="<?= $row['id'] ?>">
                                    <button class="btn btn-success btn-sm fw-medium text-white me-2">
                                    Approved
                                    </button>
                                <?php } else if(($role != '1' || $role != '2') && $row['status'] === 'Approved'){ ?>
                                    <button class="btn btn-success btn-sm fw-medium text-white me-2">
                                    Approved
                                    </button>
                                <?php } ?>
                            </td>
                            <td class="text-center">
                                <a href="order-summary.php?id=<?php echo $row['invoice_id']; ?>" class="fa fa-eye view-user-btn bg-info-focus text-info-600 bg-hover-info-200 fw-medium w-32-px h-32-px d-inline-flex justify-content-center align-items-center rounded-circle">
                                </a>
                                <a href="invoice-preview.php?id=<?php echo $row['invoice_id']; ?>" class="fa fa-file view-user-btn bg-warning-focus text-warning-600 bg-hover-warning-200 fw-medium w-32-px h-32-px d-inline-flex justify-content-center align-items-center rounded-circle">
                                </a>
                                <?php if (($role === '1' || $role === '2') && $row['status'] === 'Pending'){ ?>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="approve_id" value="<?= $row['id'] ?>">
                                        <button type="submit" class="fa fa-check-square view-user-btn bg-success-focus text-success-600 bg-hover-success-200 fw-medium w-32-px h-32-px d-inline-flex justify-content-center align-items-center rounded-circle">
                                        
                                        </button>
                                    </form>
                                <?php } ?>
                               
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

    <script>
        $(document).ready(function() {
            $('#userTable').DataTable();
        } );

        function approveAction() {
            // Blur the Approve button by adding a "disabled" class and prevent it from being clicked
            let approveButton = document.getElementById('approveButton');
            approveButton.classList.add('disabled');  // Add "disabled" class for visual effect
            approveButton.setAttribute('disabled', true);  // Disable the button to prevent further clicks
        }
    </script>
</body>
</html>

<?php include './partials/layouts/layoutBottom.php' ?>