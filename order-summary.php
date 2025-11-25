<?php include './partials/layouts/layoutTop.php' ?>
<?php
    $invoice_id = $_GET['id'];
    $type = $_GET['type'] ?? 'normal';
    //$invoice = "select * from orders where invoice_id = $invoice_id";
    
    // $invoice = "
    // SELECT
    //     orders.*,
    //     CASE 
    //         WHEN orders.type = 'package' THEN package.package_name
    //         WHEN orders.type = 'product' THEN products.name
    //         ELSE orders.plan
    //     END AS plan_name
    //     FROM orders
    //     LEFT JOIN package ON (orders.type = 'package' AND orders.plan = package.id)
    //     LEFT JOIN products ON (orders.type = 'product' AND orders.plan = products.id)
    // where invoice_id = $invoice_id";

    if ($type === 'renewal') {
        // ✅ Fetch from renewal_invoices when renewal invoice
        $invoice = "
            SELECT
                r.*,
                CASE 
                    WHEN r.type = 'package' THEN p.package_name
                    WHEN r.type = 'product' THEN pr.name
                    ELSE r.plan
                END AS plan_name
            FROM renewal_invoices r
            LEFT JOIN package p ON (r.type = 'package' AND r.plan = p.id)
            LEFT JOIN products pr ON (r.type = 'product' AND r.plan = pr.id)
            WHERE r.invoice_id = '$invoice_id'
        ";
    } else {
        $invoice = "
            SELECT
                orders.*,
                CASE 
                    WHEN orders.type = 'package' THEN package.package_name
                    WHEN orders.type = 'product' THEN products.name
                    ELSE orders.plan
                END AS plan_name
                FROM orders
                LEFT JOIN package ON (orders.type = 'package' AND orders.plan = package.id)
                LEFT JOIN products ON (orders.type = 'product' AND orders.plan = products.id)
            where invoice_id = $invoice_id
        ";
    }

    $result = $conn->query($invoice);
    $row = $result->fetch_assoc();
    $user_id = $row['user_id'];
    $id = $row['id'];

    $userId = $_SESSION['user_id'];
    $session_user = "select * from users where id = $userId";
    $result2 = $conn->query($session_user);
    $row2 = $result2->fetch_assoc();

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
        $total_amount = floatval($amount) + floatval($payment_made);
        $created_at = date("Y-m-d H:i:s");
        $remarks = $_POST['remarks'];
        $balance_due = $_POST['balance_due'];
        $payment_id = generatePaymentID($conn);

        $sql = "INSERT INTO record_payment (payment_id, orders_id, invoice_no, payment_method, amount, balance, remarks, paid_date) 
                        VALUES ('$payment_id', '$order_id', '$invoice_no', '$payment_method', '$amount', '$balance_due', '$remarks', '$created_at')";
            
            if (mysqli_query($conn, $sql)) {

                 if ($type === 'renewal') {
                    // 👈 Renewal Payment Update
                    $updateQuery = "UPDATE renewal_invoices 
                                    SET payment_made = $total_amount, balance_due = $balance_due 
                                    WHERE invoice_id = '$invoice_id'";
                } else {
                    $updateQuery = "UPDATE orders
                        SET payment_made = $total_amount, balance_due = $balance_due
                        WHERE invoice_id = '$invoice_id'";
                }
                mysqli_query($conn, $updateQuery);
                
                echo "
                <script>
                    Swal.fire({
                        title: 'Success!',
                        text: 'Payment Record Created Successfully.',
                        confirmButtonText: 'OK',
                        allowOutsideClick: false
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = 'order-summary.php?id=$invoice_id';
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

    // Get active symbol
    $result1 = $conn->query("SELECT symbol FROM currencies WHERE is_active = 1 LIMIT 1");
    $symbol = "$"; // default
    if ($row1 = $result1->fetch_assoc()) {
        $symbol = $row1['symbol'];
    }
?>
<style>
    .plan-details-table tbody tr td{
        padding: 15px .5rem;
        border-bottom: 1px solid #dadada;
        width:50%;
    }
    .plan-details-table2 tbody tr td{
        padding: 15px .5rem;
        width:50%;
    }
    .border-top{
        border-top: 1px solid #dadada;
    }
    .add-ons{
        font-size: 0.9rem;
        color: #555;
        padding: 3px 0.5rem !important;
    }
</style>
<div class="dashboard-main-body">
    <div class="gap-3 mb-24">
        <div class="row">
            <div class="col-lg-4">
                <a class="cursor-pointer fw-bold" onclick="history.back()"><span class="fa fa-arrow-left"></span>&nbsp; Back</a> 
            </div>
            <div class="col-lg-4 text-center">
                <h6 class="fw-semibold mb-0">Order Summary</h6>
            </div>
            <div class="col-lg-4 text-end">
                <div>
                    <?php 
                    if($row2['role'] == "1" || $row2['role'] == "2") {?>  
                        <button type="button" class="btn btn-sm btn-primary radius-8 d-inline-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#exampleModal">
                            <iconify-icon icon="lucide:edit" class="text-xl"></iconify-icon>
                            Record Payment
                        </button>
                    <?php } ?>
                    <button type="button" class="btn btn-sm btn-success radius-8 d-inline-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#Payment">
                        <?= htmlspecialchars($symbol) ?> Payment History
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="payment" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Payment History</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive scroll-sm">
                        <table class="table bordered-table sm-table mb-20">
                            <thead>
                                <tr>
                                    <th scope="col">Invoice No</th>
                                    <th scope="col">Payment Method</th>
                                    <th scope="col">Paid Amount</th>
                                    <th scope="col">Remarks</th>
                                    <th scope="col">Paid Date</th>
                                </tr>
                            </thead>
                            <tbody>
                    <?php 
                    $sql = "SELECT * FROM record_payment where invoice_no = '$invoice_id'";
                    $result = mysqli_query($conn, $sql);

                    if (mysqli_num_rows($result) > 0) {
                        while ($row_history = mysqli_fetch_assoc($result)) {
                            echo '<tr>
                                    <td>' . htmlspecialchars($row_history['invoice_no']) . '</td>
                                    <td>' . htmlspecialchars($row_history['payment_method']) . '</td>
                                    <td>' . htmlspecialchars($symbol) . htmlspecialchars($row_history['amount']) . '</td>
                                    <td>' . htmlspecialchars($row_history['remarks']) . '</td>
                                    <td>' . date('d/m/Y', strtotime($row_history['paid_date'])) . '</td>
                                </tr>';
                                }
                            } else {
                                echo '<tr><td colspan="13" class="text-center">No users found.</td></tr>';
                            }
                        ?>
                            </tbody>
                        </table>
                    </div>
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
                                    
                                    <input type="hidden" class="form-control radius-8" name="payment_made" id="payment_made" value="<?php echo $row['payment_made'] ?>">

                                    <input type="text" class="form-control radius-8" name="amount" id="numericInput" required <?php echo $row['balance_due'] == "0" ? 'readonly' : ''; ?> >
                                    <small id="amountError" class="text-danger d-none">Amount cannot be greater than Balance Due.</small>

                                    <input type="hidden" class="form-control radius-8" name="balance_due" id="balance_due" value="<?php echo $row['balance_due'] ?>">
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

    <div class="mb-40">
        <div class="row gy-4">
            <div class="col-xxl-6 col-sm-6">
                <div class="card h-100 radius-12">
                    <div class="card-header border-none py-10" style="box-shadow: 0px 3px 3px 0px lightgray">
                        <h6 class="mb-0">Plan Details</h6>
                    </div>
                    <div class="card-body p-16">
                        <!-- <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                            <label>Plan Name</label>
                            <label><?php echo $row['plan']; ?></label>
                        </div> -->
                        <table class="plan-details-table mb-0 w-100">
                            <tbody>
                                <tr>
                                    <td>Plan Name</td>
                                    <td><?php echo htmlspecialchars($row['plan_name']); ?></td>
                                </tr>
                                <tr>
                                    <td>Plan Duration</td>
                                    <td><?php echo $row['duration']; ?></td>
                                </tr>
                                <tr>
                                    <td>Validity</td>
                                    <td>
                                        <?php
                                            $start_date = new DateTime($row['created_on']);

                                            // Parse duration (assumes format like '1 year', '6 months', '2 weeks', etc.)
                                            $duration_str = $row['duration'];
                                            try {
                                                $interval = DateInterval::createFromDateString($duration_str);
                                                $end_date = clone $start_date;
                                                $end_date->add($interval);

                                                echo $start_date->format('d-m-Y') . " to " . $end_date->format('d-m-Y');
                                            } catch (Exception $e) {
                                                echo $start_date->format('d-m-Y') . " to (Invalid duration)";
                                            }
                                        ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Status</td>
                                    <td><?php echo $row['status']; ?></td>
                                </tr>
                                <tr>
                                    <td class="border-0">Add-on service</td>
                                    <td class="border-0">
                                        <?php
                                            if (!empty($row['addon_service'])) {
                                                $addon_ids = explode(',', $row['addon_service']);
                                                $addon_ids = array_map('intval', $addon_ids); // sanitize

                                                if (!empty($addon_ids)) {
                                                    $addon_id_list = implode(',', $addon_ids);
                                                    $addon_query = "SELECT name FROM `add-on-service` WHERE id IN ($addon_id_list)";
                                                    $addon_result = $conn->query($addon_query);

                                                    $addon_names = [];
                                                    while ($addon_row = $addon_result->fetch_assoc()) {
                                                        $addon_names[] = htmlspecialchars($addon_row['name']);
                                                    }

                                                    echo !empty($addon_names) ? implode(', ', $addon_names) : '—';
                                                } else {
                                                    echo '—';
                                                }
                                            } else {
                                                echo '—';
                                            }
                                        ?>
                                    </td>
                                </tr>

                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-xxl-6 col-sm-6">
                <div class="card h-100 radius-12">
                    <div class="card-header py-10 border-none d-flex justify-content-between" style="box-shadow: 0px 3px 3px 0px lightgray">
                        <div class="">
                            <h6 class="mb-0">Sub Total</h6>
                        </div>
                        <div class="align-content-center">
                            <h6 class="mb-0"><?= htmlspecialchars($symbol) ?><?= number_format(floatval($row['price']) + floatval($row['addon_price']), 2) ?></h6>
                        </div>
                        
                    </div>
                    <div class="card-body p-16">
                        <table class="w-100 plan-details-table2 mb-0">
                            <tbody>
                            <tr>
                                <td onclick="toggleBreakdowns()" style="cursor:pointer; user-select:none;">
                                    <?php echo htmlspecialchars($row['plan_name']); ?>
                                    &nbsp;
                                    <span id="breakdown-arrow"><i class="fas fa-chevron-down"></i></span>
                                </td>
                                <td class="text-end">
                                    <?= htmlspecialchars($symbol) ?><?= number_format(floatval($row['price']) + floatval($row['gst']), 2) ?>
                                </td>
                            </tr>

                            <!-- Hidden Breakdown (only Base Price + Tax) -->
                            <tbody id="breakdown-rows" style="display:none;">
                                <tr>
                                    <td class="add-ons">Base Price</td>
                                    <td class="text-end add-ons" >
                                        <?= htmlspecialchars($symbol) ?><?= number_format($row['price'], 2) ?>
                                    </td>
                                </tr>

                                <tr>
                                    <td class="add-ons">Tax</td>
                                    <td class="text-end add-ons" >
                                        <?= htmlspecialchars($symbol) ?><?= number_format($row['gst'], 2) ?>
                                    </td>
                                </tr>
                            </tbody>

                            <?php
                            $addon_ids = !empty($row['addon_service']) ? array_map('intval', explode(',', $row['addon_service'])) : [];

                            if (!empty($addon_ids)) {
                                $addon_id_list = implode(',', $addon_ids);
                                $addon_query = "SELECT id, name, cost, gst FROM `add-on-service` WHERE id IN ($addon_id_list)";
                                $addon_result = $conn->query($addon_query);

                                while ($addon_row = $addon_result->fetch_assoc()) {
                                    $addonId = $addon_row['id'];
                            ?>
                                <!-- Add-on Row -->
                                <tr class="border-top">
                                    <td onclick="toggleBreakdown('addon-<?= $addonId ?>')" style="cursor:pointer; user-select:none;">
                                        <?= htmlspecialchars($addon_row['name']) ?> (Add-on)
                                        &nbsp;
                                        <span id="arrow-addon-<?= $addonId ?>"><i class="fas fa-chevron-down"></i></span>
                                    </td>
                                    <td class="text-end">
                                        <?= htmlspecialchars($symbol) ?><?= number_format(floatval($row['addon_price']) + floatval($row['addon_gst']), 2) ?>
                                    </td>
                                </tr>

                                <!-- Add-on Breakdown -->
                                <tbody id="addon-<?= $addonId ?>" style="display:none;">
                                    <tr>
                                        <td class="add-ons">Addon Price</td>
                                        <td class="text-end add-ons">
                                            <?= htmlspecialchars($symbol) ?><?= number_format($row['addon_price'], 2) ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="add-ons">Addon Tax</td>
                                        <td class="text-end add-ons">
                                            <?= htmlspecialchars($symbol) ?><?= number_format($row['addon_gst'], 2) ?>
                                        </td>
                                    </tr>
                                </tbody>

                            <?php
                                }
                            }
                            ?>
                                <?php if ($row['existing_balance']) { ?>    
                                    <tr class="border-top">
                                        <td class="fw-semibold">Existing Plan</td>
                                        <td class="text-end">
                                            <?= htmlspecialchars($symbol) ?> <?php echo number_format($row['existing_balance'], 2); ?>
                                        </td>
                                    </tr>
                                <?php } ?>
                                <tr class="border-top">
                                    <td class="fw-bold">Payments</td>
                                    <td class="text-end fw-bold" ><?= htmlspecialchars($symbol) ?><?= number_format($row['payment_made'], 2) ?></td>
                                </tr>
                                <tr class="border-top">
                                    <td class="fw-bold border-0">Total Payable</td>
                                    <td class="text-end fw-bold border-0" >
                                        <?= htmlspecialchars($symbol) ?><?= number_format($row['balance_due'], 2) ?>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
    $invoice_id = $_GET['id'];
    $invoiceQuery = "SELECT * FROM record_payment WHERE invoice_no = '$invoice_id'";
    $invoiceResult = mysqli_query($conn, $invoiceQuery);
    $paymentCount = mysqli_num_rows($invoiceResult);
    ?>
    <p class="fw-semibold" style="cursor: pointer;" onclick="togglePayments()">
        Payment Received (<?= $paymentCount ?>) 
        <span id="arrow"><i class="fas fa-chevron-down"></i></span>
    </p>
    <div id="paymentTable">
    <table class="table mb-20">
        <thead>
            <th>Date</th>
            <th>Reference</th>
            <th>Payment Mode</th>
            <th>Amount</th>
        </thead>
        <tbody>
            <?php
                if ($paymentCount > 0) {
                    while ($invoiceRow = mysqli_fetch_assoc($invoiceResult)) {
                        ?>
                        <tr>
                            <td><?= date('d/m/Y', strtotime($invoiceRow['paid_date'])) ?></td>
                            <td><?= $invoiceRow['payment_id']; ?></td>
                            <td><?= $invoiceRow['payment_method']; ?></td>
                            <td><?= number_format($invoiceRow['amount'], 2); ?></td>
                        </tr>
                        <?php
                    }
                } else {
                    echo "<tr><td colspan='4'>No payments found.</td></tr>";
                }
            ?>
        </tbody>
    </table>
    </div>
</div>

<script>
    function sanitizeNumberInput(el) {
            el.value = el.value.replace(/[^0-9.]/g, '');
            el.value = el.value.replace(/(\..*)\./g, '$1');
        }
document.getElementById('numericInput').addEventListener('input', function () {
    sanitizeNumberInput(this);
    const amount = parseFloat(this.value);
    const originalBalance = parseFloat(<?php echo json_encode($row['balance_due']); ?>);

    if (!isNaN(amount)) {
        const updatedBalance = originalBalance - amount;
        document.getElementById('balance_due').value = updatedBalance.toFixed(2);
    } else {
        // Reset if input is not a number
        document.getElementById('balance_due').value = originalBalance.toFixed(2);
    }
});

document.addEventListener("DOMContentLoaded", function () {
    const amountInput = document.getElementById("numericInput");
    const balanceDue = parseFloat(document.getElementById("balance_due").value);
    const errorText = document.getElementById("amountError");
    const submit = document.getElementById("submit");
    amountInput.addEventListener("input", function () {
        const enteredAmount = parseFloat(this.value);

        if (enteredAmount > balanceDue) {
            errorText.classList.remove("d-none");
            submit.disabled = true;
        }
        else {
            errorText.classList.add("d-none");
            submit.disabled = false;
        }
    });
});

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
        } else {
            errorText.classList.add("d-none");
            submit.disabled = false;
        }
    });
});
function toggleBreakdowns() {
    let box = document.getElementById("breakdown-rows");
    let arrow = document.getElementById("breakdown-arrow");

    if (box.style.display === "none" || box.style.display === "") {
        box.style.display = "table-row-group";
        arrow.innerHTML = '<i class="fas fa-chevron-up"></i>';
    } else {
        box.style.display = "none";
        arrow.innerHTML = '<i class="fas fa-chevron-down"></i>';
    }
}
</script>
<script>
function toggleBreakdown(id) {
    let box = document.getElementById(id);
    let arrow = document.getElementById('arrow-' + id);

    if (box.style.display === "none" || box.style.display === "") {
        box.style.display = "table-row-group";
        arrow.innerHTML = '<i class="fas fa-chevron-up"></i>';
    } else {
        box.style.display = "none";
        arrow.innerHTML = '<i class="fas fa-chevron-down"></i>';
    }
}

</script>

<?php include './partials/layouts/layoutBottom.php' ?>