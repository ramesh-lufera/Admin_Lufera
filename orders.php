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
                            <!-- <td> <span class="bg-success-focus text-success-main px-24 py-4 rounded-pill fw-medium text-sm">Paid</span> </td> -->
                            <!-- <td>
                                <a href="javascript:void(0)" class="btn btn-success btn-sm fw-medium text-white me-2" id="approveButton" onclick="approveAction()">
                                    Approve
                                </a>
                            </td> -->

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
                                <a href="order-summary.php?id=<?php echo $row['invoice_id']; ?>" class="w-32-px h-32-px bg-primary-light text-primary-600 rounded-circle d-inline-flex align-items-center justify-content-center">
                                    <iconify-icon icon="iconamoon:eye-light"></iconify-icon>
                                </a>
                                <a href="invoice-preview.php?id=<?php echo $row['invoice_id']; ?>" class="w-32-px h-32-px bg-warning-focus text-warning-main rounded-circle d-inline-flex align-items-center justify-content-center">
                                    <iconify-icon icon="iconamoon:invoice"></iconify-icon>
                                </a>
                                <?php if (($role === '1' || $role === '2') && $row['status'] === 'Pending'){ ?>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="approve_id" value="<?= $row['id'] ?>">
                                        <button type="submit" class="w-32-px h-32-px bg-success-focus text-success-main rounded-circle d-inline-flex align-items-center justify-content-center mb-8">
                                        <iconify-icon icon="iconamoon:check"></iconify-icon>
                                        </button>
                                    </form>
                                <?php } ?>
                                <!-- <a href="javascript:void(0)" class="w-32-px h-32-px bg-success-focus text-success-main rounded-circle d-inline-flex align-items-center justify-content-center">
                                    <iconify-icon icon="lucide:edit"></iconify-icon>
                                </a>
                                <a href="javascript:void(0)" class="w-32-px h-32-px bg-danger-focus text-danger-main rounded-circle d-inline-flex align-items-center justify-content-center">
                                    <iconify-icon icon="mingcute:delete-2-line"></iconify-icon>
                                </a> -->
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
                <!-- <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-24">
                    <span>Showing 1 to 10 of 12 entries</span>
                    <ul class="pagination d-flex flex-wrap align-items-center gap-2 justify-content-center">
                        <li class="page-item">
                            <a class="page-link text-secondary-light fw-medium radius-4 border-0 px-10 py-10 d-flex align-items-center justify-content-center h-32-px w-32-px bg-base" href="javascript:void(0)">
                                <iconify-icon icon="ep:d-arrow-left" class="text-xl"></iconify-icon>
                            </a>
                        </li>
                        <li class="page-item">
                            <a class="page-link bg-primary-600 text-white fw-medium radius-4 border-0 px-10 py-10 d-flex align-items-center justify-content-center h-32-px w-32-px" href="javascript:void(0)">1</a>
                        </li>
                        <li class="page-item">
                            <a class="page-link bg-primary-50 text-secondary-light fw-medium radius-4 border-0 px-10 py-10 d-flex align-items-center justify-content-center h-32-px w-32-px" href="javascript:void(0)">2</a>
                        </li>
                        <li class="page-item">
                            <a class="page-link bg-primary-50 text-secondary-light fw-medium radius-4 border-0 px-10 py-10 d-flex align-items-center justify-content-center h-32-px w-32-px" href="javascript:void(0)">3</a>
                        </li>
                        <li class="page-item">
                            <a class="page-link text-secondary-light fw-medium radius-4 border-0 px-10 py-10 d-flex align-items-center justify-content-center h-32-px w-32-px bg-base" href="javascript:void(0)">
                                <iconify-icon icon="ep:d-arrow-right" class="text-xl"></iconify-icon>
                            </a>
                        </li>
                    </ul>
                </div> -->

                <!-- Pagination -->
                <!-- <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mt-24">
                    <span>Showing <?php echo ($start_from + 1); ?> to <?php echo min($start_from + $records_per_page, $total_records); ?> of <?php echo $total_records; ?> entries</span>
                    
                    <ul class="d-flex flex-wrap align-items-center gap-2 justify-content-center">
                        <?php if ($current_page > 1): ?>
                        <li class="page-item">
                            <a class="page-link bg-neutral-200 text-secondary-light fw-semibold radius-8 border-0 d-flex align-items-center justify-content-center h-32-px w-32-px text-md" href="?page=<?php echo ($current_page - 1); ?>">
                                <iconify-icon icon="ep:d-arrow-left" class=""></iconify-icon>
                            </a>
                        </li>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item">
                            <a class="page-link <?php echo ($i == $current_page) ? 'bg-primary-600 text-white' : 'bg-neutral-200 text-secondary-light'; ?> fw-semibold radius-8 border-0 d-flex align-items-center justify-content-center h-32-px w-32-px text-md" style="<?php echo ($i == $current_page) ? 'background-color: #fec700 !important' : 'bg-neutral-200 text-secondary-light'; ?>" href="?page=<?php echo $i; ?>">
                            <?php echo $i; ?>
                            </a>
                        </li>    
                        <?php endfor; ?>     
                        
                        <?php if ($current_page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link bg-neutral-200 text-secondary-light fw-semibold radius-8 border-0 d-flex align-items-center justify-content-center h-32-px w-32-px text-md" href="?page=<?php echo ($current_page + 1); ?>">
                                <iconify-icon icon="ep:d-arrow-right" class=""></iconify-icon>
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </div> -->
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