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
    // Get role of logged-in user
    $roleQuery = "SELECT role FROM users WHERE id = '$Id' LIMIT 1";
    $roleResult = mysqli_query($conn, $roleQuery);
    $roleRow = mysqli_fetch_assoc($roleResult);
    $role = $roleRow['role'];
    // Get active symbol
    $result2 = $conn->query("SELECT symbol FROM currencies WHERE is_active = 1 LIMIT 1");
    $symbol = "$"; // default
    if ($row1 = $result2->fetch_assoc()) {
        $symbol = $row1['symbol'];
    }
    
    // JOIN orders with users
    $query = "
    SELECT
        orders.*,
        users.username,
        users.first_name,
        users.last_name,
        users.photo,
        users.business_name,

        CASE 
            WHEN orders.type = 'package' THEN package.package_name
            WHEN orders.type = 'product' THEN products.name
            ELSE orders.plan
        END AS plan_name

        FROM orders
        INNER JOIN users ON orders.user_id = users.id
        LEFT JOIN package ON (orders.type = 'package' AND orders.plan = package.id)
        LEFT JOIN products ON (orders.type = 'product' AND orders.plan = products.id)
    ";
// Add condition only if role is NOT 1 or 2
if ($role != 1 && $role != 2) {
    $query .= " WHERE orders.user_id = '$Id'";
}
    $result = mysqli_query($conn, $query);

    function generatePaymentID($conn) {
        do {
            $randomNumber = str_pad(mt_rand(0, 99999999), 8, '0', STR_PAD_LEFT);
            $paymentID = "L_" . $randomNumber;
    
            // Check uniqueness in DB
            $check = mysqli_query($conn, "SELECT payment_id FROM record_payment WHERE payment_id = '$paymentID'");
        } while (mysqli_num_rows($check) > 0);
    
        return $paymentID;
    }

    if (isset($_POST['save'])) {
        $order_id = $_POST['order_id'];
        $invoice_no = $_POST['invoice_no'];
        $payment_method = $_POST['payment_method'];
        $amount = $_POST['amount'];

        $payment_made = $_POST['payment_made'];

        $total_amount = $amount + $payment_made;
        $created_at = date("Y-m-d H:i:s");
        $remarks = $_POST['remarks'];
        $balance_due = $_POST['balance_due'];
        $payment_id = generatePaymentID($conn);
        $sql = "INSERT INTO record_payment (payment_id, orders_id, invoice_no, payment_method, amount, balance, remarks, paid_date) 
                        VALUES ('$payment_id', '$order_id', '$invoice_no', '$payment_method', '$amount', '$balance_due', '$remarks', '$created_at')";
            if (mysqli_query($conn, $sql)) {

                $siteInsert = "UPDATE orders
                                SET payment_made = $total_amount, balance_due = $balance_due
                                WHERE invoice_id = '$invoice_id'";
                    mysqli_query($conn, $siteInsert);
                echo "
                <script>
                    Swal.fire({
                        title: 'Success!',
                        text: 'Payment Record Created Successfully.',
                        confirmButtonText: 'OK',
                        allowOutsideClick: false
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = 'subscription.php';
                        }
                    });
                </script>";
                } else {
                echo "<script>
                    alert('Error: " . $stmt->error . "');
                    window.history.back();
                </script>";
            }
    }
?>

<body>
    <div class="dashboard-main-body">
        <div class="d-flex flex-wrap align-items-center gap-3 mb-24">
            <a class="cursor-pointer fw-bold" onclick="history.back()"><span class="fa fa-arrow-left"></span>&nbsp; Back</a>    
            <h6 class="fw-semibold mb-0 m-auto">Subscriptions</h6>
        </div>
        <div class="card">
            <div class="card-body">
            <div class="table-responsive scroll-sm">
                <table class="table bordered-table mb-0" id="userTable">
                    <thead>
                        <tr>
                            <th scope="col">Subscription</th>
                            <th scope="col">Bussiness name</th>
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
                                <td><?php echo htmlspecialchars($row['business_name']); ?></td>
                                <td class="text-center"><?php echo $expiryFormatted; ?></td>
                                <td class="text-center">Off</td>
                                <td class="text-center">
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
                                    <p class="text-sm"><?php echo htmlspecialchars($row['business_name']); ?></p>
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
                                    <h6 class="text-md mt-20">Payment Received</h6>

                                    <div class="d-flex justify-content-between mt-3 p-4" style="background:lightgray">
                                        <span class="fw-semibold">Date</span>
                                        <span class="fw-semibold">Amount</span>
                                    </div>
                                    <hr />
                                    
                                    <?php
                                        $invoice_id = $row['invoice_id'];
                                        $id = $row['id'];
                                        $payment_made = $row['payment_made'];
                                        $balance_due = $row['balance_due'];
                                        // Get role of logged-in user
                                        $invoiceQuery = "SELECT * FROM record_payment WHERE invoice_no = '$invoice_id'";
                                        $invoiceResult = mysqli_query($conn, $invoiceQuery);
                                        if (mysqli_num_rows($invoiceResult) > 0) {
                                        while ($invoiceRow = mysqli_fetch_assoc($invoiceResult)) {
                                            $date = $invoiceRow['paid_date'];
                                            $amount = $invoiceRow['amount'];
                                            ?>
                                            <div class="d-flex justify-content-between my-2 p-4">
                                                <span><?php echo $date; ?></span>
                                                <span><?php echo number_format($amount, 2); ?></span>
                                            </div>
                                            <hr />
                                            <?php
                                        }
                                    } else {
                                        echo "<div>No payments found.</div>";
                                    }
                                    ?>

                                    <div class="mt-20">
                                    <a href="order-summary.php?id=<?php echo $invoice_id; ?>"><button class="btn text-white btn-danger text-sm mb-10">View More</button></a>
                                    <a href="invoice-preview.php?id=<?php echo $invoice_id; ?>"><button class="btn text-white btn-success text-sm mb-10">Invoice</button> </a>   
                                    <button class="btn text-white lufera-bg text-sm mb-10">Renew</button>
                                    <button class="btn text-white btn-primary text-sm mb-10" data-bs-toggle="modal" data-bs-target="#exampleModal">Record Payment</button>
                                    </div>
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
<div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <form method="post">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">Record Payment</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-sm-6">
                                <div class="mb-20">
                                    <label for="" class="form-label fw-semibold text-primary-light text-sm mb-8">Invoice No: <span class="text-danger-600">*</span></label>
                                    <input type="hidden" value="<?php echo $id; ?>" name="order_id">
                                    <input type="text" class="form-control radius-8" name="invoice_no" value="<?php echo $invoice_id; ?>" <?php echo !empty($invoice_id) ? 'readonly' : ''; ?> required>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="mb-20">
                                    <label for="" class="form-label fw-semibold text-primary-light text-sm mb-8">Payment Method <span class="text-danger-600">*</span></label>
                                    <select class="form-control" name="payment_method" required <?php echo $row['balance_due'] == "0" ? 'disabled' : ''; ?> >
                                        <option value="">Select payment method</option>
                                        <option value="Cash">Cash</option>
                                        <option value="Card">Card</option>
                                        <option value="UPI">UPI</option>
                                        <option value="Bank">Bank</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="mb-20">
                                    <label for="" class="form-label fw-semibold text-primary-light text-sm mb-8">Enter Amount <span class="text-danger-600">*</span></label>
                                    
                                    <input type="hidden" class="form-control radius-8" name="payment_made" id="payment_made" value="<?php echo $payment_made; ?>">

                                    <input type="text" class="form-control radius-8" name="amount" id="numericInput" required <?php echo $row['balance_due'] == "0" ? 'readonly' : ''; ?> >
                                    <small id="amountError" class="text-danger d-none">Amount cannot be greater than Balance Due.</small>

                                    <input type="hidden" class="form-control radius-8" name="balance_due" id="balance_due" value="<?php echo $balance_due; ?>">
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="mb-20">
                                    <label for="" class="form-label fw-semibold text-primary-light text-sm mb-8">Remarks <span class="text-danger-600">*</span></label>
                                    <input type="text" class="form-control radius-8" name="remarks" value="" required <?php echo $row['balance_due'] == "0" ? 'readonly' : ''; ?> >
                                </div>
                            </div>

                            <?php if ($row['balance_due'] == '0') { ?>
                                <p class="text-danger">Payment fully paid</p>
                                <?php } ?>
                        </div>
                    </div>
                    <div class="modal-footer d-flex align-items-center justify-content-center gap-3">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" id="submit" class="btn lufera-bg text-white" name="save" <?php echo $row['balance_due'] == "0" ? 'disabled' : ''; ?>>Save</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
<script>
$(document).ready(function() {
    $('#userTable').DataTable();
} );
</script>
<script>
    document.getElementById("numericInput").addEventListener("input", function () {
    this.value = this.value.replace(/\D/g, ''); // Remove non-digits
  });
</script>
<script>
    document.getElementById('numericInput').addEventListener('input', function () {
        const amount = parseFloat(this.value);
        const originalBalance = parseFloat(<?php echo json_encode($row['balance_due']); ?>);

        if (!isNaN(amount)) {
            const updatedBalance = originalBalance - amount;
            document.getElementById('balance_due').value = updatedBalance.toFixed(2);;
        } else {
            // Reset if input is not a number
            document.getElementById('balance_due').value = originalBalance.toFixed(2);;
        }
    });
</script>
<script>
document.addEventListener("DOMContentLoaded", function () {
    const amountInput = document.getElementById("numericInput").toFixed(2);;
    const balanceDue = parseFloat(document.getElementById("balance_due").value).toFixed(2);;
    const errorText = document.getElementById("amountError").toFixed(2);;
    const submit = document.getElementById("submit").toFixed(2);;
    amountInput.addEventListener("input", function () {
        const enteredAmount = parseFloat(this.value);

        if (enteredAmount > balanceDue) {
            errorText.classList.remove("d-none");
            submit.disabled = true;
            //this.value = ""; 
        }
        else {
            errorText.classList.add("d-none");
            submit.disabled = false;
        }
    });
});
</script>
<script>
document.addEventListener("DOMContentLoaded", function () {
    const amountInput = document.getElementById("numericInput");
    const balanceDue = parseFloat(document.getElementById("balance_due").value);
    const errorText = document.getElementById("amountError");
    const submit = document.getElementById("submit");

    amountInput.addEventListener("input", function () {
        const enteredAmount = parseFloat(this.value);

        if (!isNaN(enteredAmount) && enteredAmount > balanceDue) {
            errorText.classList.remove("d-none");
            submit.disabled = true;
            //alert("Entered amount cannot be greater than Total Payable (" + balanceDue.toFixed(2) + ")");
            //this.value = ""; // clear field
        } else {
            errorText.classList.add("d-none");
            submit.disabled = false;
        }
    });
});
</script>
</body>
</html>

<?php include './partials/layouts/layoutBottom.php' ?>