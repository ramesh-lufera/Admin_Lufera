<!-- meta tags and other links -->
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<title>Subscription</title>
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
    // Get active symbol
    $result2 = $conn->query("SELECT symbol FROM currencies WHERE is_active = 1 LIMIT 1");
    $symbol = "$"; // default
    if ($row1 = $result2->fetch_assoc()) {
        $symbol = $row1['symbol'];
    }
    
    // JOIN orders with users
    $query = "
    SELECT
        orders.id, 
        orders.invoice_id,
        orders.plan,
        orders.amount,
        orders.status,
        orders.duration,
        orders.created_on,
        orders.type,
        orders.addon_service,
        users.username,
        users.first_name,
        users.last_name,
        users.photo,

        CASE 
            WHEN orders.type = 'package' THEN package.package_name
            WHEN orders.type = 'product' THEN products.name
            ELSE orders.plan
        END AS plan_name

        FROM orders
        INNER JOIN users ON orders.user_id = users.user_id
        LEFT JOIN package ON (orders.type = 'package' AND orders.plan = package.id)
        LEFT JOIN products ON (orders.type = 'product' AND orders.plan = products.id)
    ";

    $result = mysqli_query($conn, $query);
?>

<body>
    <div class="dashboard-main-body">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
            <h6 class="fw-semibold mb-0">Subscriptions</h6>
        </div>
        <div class="card">
            <div class="card-body">
            <div class="table-responsive scroll-sm">
                <table class="table bordered-table mb-0" id="userTable">
                    <thead>
                        <tr>
                            <th scope="col">Subscription</th>
                            <th scope="col" class="text-center">Expiration date</th>
                            <th scope="col" class="text-center">Auto-renewal</th>
                            <th scope="col" class="text-center">-</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <?php
                                $createdOn = new DateTime($row['created_on']);
                                $duration  = $row['duration'];
                                $expiryDate = (clone $createdOn)->modify("+$duration");
                                $expiryFormatted = $expiryDate->format("Y-m-d");
                                $orderId = $row['id']; // unique identifier
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['plan_name']); ?></td>
                                <td class="text-center"><?php echo $expiryFormatted; ?></td>
                                <td class="text-center">Off</td>
                                <td class="text-center">
                                    <button class="btn text-white lufera-bg text-sm">Renew</button>
                                    <!-- link points to unique offcanvas -->
                                    <a class="fa fa-chevron-right ms-10 text-sm lufera-color" 
                                    data-bs-toggle="offcanvas" 
                                    data-bs-target="#offcanvas-<?php echo $orderId; ?>"></a>
                                </td>
                            </tr>

                            <!-- unique offcanvas for this row -->
                            <div class="offcanvas offcanvas-end" id="offcanvas-<?php echo $orderId; ?>">
                                <div class="offcanvas-header pb-0">
                                    <h6>Subscription details</h6>
                                    <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
                                </div>
                                <div class="offcanvas-body">
                                    <h6 class="text-lg"><?php echo htmlspecialchars($row['plan_name']); ?></h6>
                                    <div class="d-flex justify-content-between my-3">
                                        <span>Status</span>
                                        <span><i class="fa-regular fa-circle-check text-success me-2"></i>Active</span>
                                    </div>
                                    <hr />
                                    <div class="d-flex justify-content-between my-3">
                                        <span>Expiration date</span>
                                        <span><?php echo $expiryFormatted; ?></span>
                                    </div>
                                    <hr />
                                    <div class="d-flex justify-content-between my-3">
                                        <span>Renewal price</span>
                                        <span></span>
                                    </div>
                                    <hr />
                                    <div class="d-flex justify-content-between my-3">
                                        <span>Auto renewal</span>
                                        <span>Off</span>
                                    </div>
                                    <hr />
                                    <div class="d-flex justify-content-between my-3">
                                        <span>Next billing period</span>
                                        <span><?php echo $duration; ?></span>
                                    </div>
                                    <hr />

                                    <h6 class="text-md mt-20">ADD-ONS</h6>
                                    <?php
                                    if (!empty($row['addon_service'])) {
                                        $addon_ids = explode(",", $row['addon_service']);
                                        $ids_str = implode(",", array_map('intval', $addon_ids));

                                        $sql_addons = "SELECT name FROM `add-on-service` WHERE id IN ($ids_str)";
                                        $res_addons = $conn->query($sql_addons);

                                        if ($res_addons && $res_addons->num_rows > 0) {
                                            while ($addon = $res_addons->fetch_assoc()) {
                                                ?>
                                                <h6 class="text-lg my-20"><?= htmlspecialchars($addon['name']) ?></h6>
                                                <div class="d-flex justify-content-between my-3">
                                                    <span>Renewal price</span>
                                                    <span></span>
                                                </div>
                                                <hr />
                                                <?php
                                            }
                                        } else {
                                            echo "<p class='text-muted'>No add-ons found</p>";
                                        }
                                    } else {
                                        echo "<p class='text-muted'>No add-ons selected</p>";
                                    }
                                    ?>

                                </div>
                            </div>
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
</script>

</body>
</html>

<?php include './partials/layouts/layoutBottom.php' ?>