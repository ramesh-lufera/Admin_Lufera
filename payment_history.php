<!-- meta tags and other links -->
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<title>Payment History</title>
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

    // Get active symbol
    $result2 = $conn->query("SELECT symbol FROM currencies WHERE is_active = 1 LIMIT 1");
    $symbol = "$"; // default
    if ($row1 = $result2->fetch_assoc()) {
        $symbol = $row1['symbol'];
    }
    
    // JOIN orders with users
    $Id = $_SESSION['user_id'];

// Get role of logged-in user
$roleQuery = "SELECT role FROM users WHERE id = '$Id' LIMIT 1";
$roleResult = mysqli_query($conn, $roleQuery);
$roleRow = mysqli_fetch_assoc($roleResult);
$role = $roleRow['role'];

// Build main query
$query = "
    SELECT 
        rp.id AS trans_id,
        rp.payment_id,
        rp.invoice_no,
        rp.paid_date,
        rp.amount,
        CASE 
            WHEN o.type = 'package' THEN p.package_name
            WHEN o.type = 'product' THEN pr.name
            ELSE o.plan
        END AS plan_name,
        u.business_name
    FROM record_payment rp
    INNER JOIN orders o 
        ON o.id = rp.orders_id
    LEFT JOIN package p 
        ON (o.type = 'package' AND o.plan = p.id)
    LEFT JOIN products pr 
        ON (o.type = 'product' AND o.plan = pr.id)
    INNER JOIN users u
        ON u.id = o.user_id
";

// Add condition only if role is NOT 1 or 2
if ($role != 1 && $role != 2) {
    $query .= " WHERE o.user_id = '$Id'";
}

$result = mysqli_query($conn, $query);


?>

<body>
    <div class="dashboard-main-body">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
            <a class="cursor-pointer fw-bold" onclick="history.back()"><span class="fa fa-arrow-left"></span>&nbsp; Back</a>
            <h6 class="fw-semibold mb-0">Payment History</h6>
            <a class="cursor-pointer fw-bold visibility-hidden" onclick="history.back()"><span class="fa fa-arrow-left"></span>&nbsp; Back</a> 
        </div>
        <div class="card">
            <div class="card-body">
            <div class="table-responsive scroll-sm">
                <table class="table bordered-table mb-0" id="userTable">
                    <thead>
                        <tr>
                            <th scope="col">Payment ID</th>
                            <th scope="col" class="text-center">Invoice ID</th>
                            <th scope="col" class="text-center">Service</th>
                            <th scope="col" class="text-center">Title</th>
                            <th scope="col" class="text-center">Paid at</th>
                            <th scope="col" class="text-center">Amount</th>
                            <th scope="col" class="text-center"><a class="fa fa-chevron-right ms-10 text-sm lufera-color"></a></th>

                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><span class="d-none"><?php echo htmlspecialchars($row['trans_id']); ?></span> <?php echo htmlspecialchars($row['payment_id']); ?></td>
                                <td class="text-center"><?php echo htmlspecialchars($row['invoice_no']); ?></td>
                                <td class="text-center"><?php echo htmlspecialchars($row['plan_name']); ?></td>
                                <td class="text-center"><?php echo htmlspecialchars($row['business_name']); ?></td>
                                <td class="text-center"><?php echo htmlspecialchars($row['paid_date']); ?></td>
                                <td class="text-center"><?php echo htmlspecialchars($row['amount']); ?></td>
                                <td class="text-center">
                                <!-- <form action="payment_details.php" method="POST">
                                    <input type="hidden" name="payment_id" value="<?php echo htmlspecialchars($row['payment_id']); ?>">    
                                    <input type="hidden" name="services" value="<?php echo htmlspecialchars($row['plan_name']); ?>">
                                    <button type="submit" class="fa fa-chevron-right ms-10 text-sm lufera-color border-0"></button>
                                </form> -->
                                <a href="payment_details.php?id=<?php echo $row['trans_id']; ?>" class="fa fa-chevron-right ms-10 text-sm lufera-color"></a>
                                </td>
                            </tr>

                            <!-- unique offcanvas for this row -->
                            
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