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
    
    $sql = "select role, user_id from users where id = $Id";
    $result = $conn ->query($sql);
    $row = $result ->fetch_assoc();
    $role = $row['role'];
    $UserId = $row['user_id'];

    // Handle order approval if POST submitted
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['approve_id'])) {
        $orderId = intval($_POST['approve_id']);
        $sql = "UPDATE orders SET status = 'approved' WHERE id = $orderId";
        $conn->query($sql);
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
        users.photo
    FROM orders
    INNER JOIN users ON orders.user_id = users.user_id
    ";

    if ($role !== 'admin') {
        if (!empty($UserId)) {
            $query .= " WHERE orders.user_id = '$UserId'";
        } else {
            $query .= " WHERE 1 = 0";
        }
    }

    $result = mysqli_query($conn, $query);
?>

<body>
    <div class="dashboard-main-body">

        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
            <h6 class="fw-semibold mb-0">Orders</h6>
            <ul class="d-flex align-items-center gap-2">
                <li class="fw-medium">
                    <a href="index.php" class="d-flex align-items-center gap-1 hover-text-primary">
                        <iconify-icon icon="solar:home-smile-angle-outline" class="icon text-lg"></iconify-icon>
                        Dashboard
                    </a>
                </li>
                <li>-</li>
                <li class="fw-medium">Orders</li>
            </ul>
        </div>

        <div class="card">
            <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div class="d-flex flex-wrap align-items-center gap-3">
                    <!-- <div class="d-flex align-items-center gap-2">
                        <span>Show</span>
                        <select class="form-select form-select-sm w-auto">
                            <option>10</option>
                            <option>15</option>
                            <option>20</option>
                        </select>
                    </div> -->
                    <div class="icon-field">
                        <input type="text" name="#0" class="form-control form-control-sm w-auto" placeholder="Search">
                        <span class="icon">
                            <iconify-icon icon="ion:search-outline"></iconify-icon>
                        </span>
                    </div>
                </div>
                <!-- <div class="d-flex flex-wrap align-items-center gap-3">
                    <select class="form-select form-select-sm w-auto">
                        <option>Satatus</option>
                        <option>Paid</option>
                        <option>Pending</option>
                    </select>
                    <a href="invoice-add.php" class="btn btn-sm btn-primary-600"><i class="ri-add-line"></i> Create Invoice</a>
                </div> -->
            </div>
            <div class="card-body">
                <table class="table bordered-table mb-0">
                    <thead>
                        <tr>
                            <th scope="col">
                                <div class="form-check style-check d-flex align-items-center">
                                    <input class="form-check-input" type="checkbox" value="" id="checkAll">
                                    <label class="form-check-label" for="checkAll">
                                        No
                                    </label>
                                </div>
                            </th>
                            <th scope="col">Invoice</th>
                            <th scope="col">Name</th>
                            <th scope="col">Issued Date</th>
                            <th scope="col">Amount</th>
                            <th scope="col">Status</th>
                            <th scope="col">Action</th>
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
                                <div class="form-check style-check d-flex align-items-center">
                                    <input class="form-check-input" type="checkbox" value="" id="check1">
                                    <label class="form-check-label" for="check1">
                                    <?php echo $row['id']; ?>
                                    </label>
                                </div>
                            </td>
                            <td><a href="javascript:void(0)" class="text-primary-600"><?php echo $row['invoice_id']; ?></a></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <img src="<?= $photo ?>" alt="" class="flex-shrink-0 me-12 radius-8" style="width: 40px; height: 40px;">
                                    <h6 class="text-md mb-0 fw-medium flex-grow-1"><?php echo $row['username']; ?></h6>
                                </div>
                            </td>
                            <td><?= date('d M Y', strtotime($row['created_on'])) ?></td>
                            <td>$ <?= number_format($row['amount'], 2) ?></td>
                            <!-- <td> <span class="bg-success-focus text-success-main px-24 py-4 rounded-pill fw-medium text-sm">Paid</span> </td> -->
                            <!-- <td>
                                <a href="javascript:void(0)" class="btn btn-success btn-sm fw-medium text-white me-2" id="approveButton" onclick="approveAction()">
                                    Approve
                                </a>
                            </td> -->
                            <td>
                                <?php if ($role === 'admin' && $row['status'] === 'pending'): ?>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="approve_id" value="<?= $row['id'] ?>">
                                        <button type="submit" class="btn btn-success btn-sm fw-medium text-white me-2">
                                            Approve
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <button class="btn btn-secondary btn-sm fw-medium text-white me-2" disabled>
                                        <?= ucfirst($row['status']) ?>
                                    </button>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="javascript:void(0)" class="w-32-px h-32-px bg-primary-light text-primary-600 rounded-circle d-inline-flex align-items-center justify-content-center">
                                    <iconify-icon icon="iconamoon:eye-light"></iconify-icon>
                                </a>
                                <a href="javascript:void(0)" class="w-32-px h-32-px bg-success-focus text-success-main rounded-circle d-inline-flex align-items-center justify-content-center">
                                    <iconify-icon icon="lucide:edit"></iconify-icon>
                                </a>
                                <a href="javascript:void(0)" class="w-32-px h-32-px bg-danger-focus text-danger-main rounded-circle d-inline-flex align-items-center justify-content-center">
                                    <iconify-icon icon="mingcute:delete-2-line"></iconify-icon>
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted">No orders found.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-24">
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
                </div>
            </div>
        </div>
    </div>

    <script>
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