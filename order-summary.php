<?php include './partials/layouts/layoutTop.php' ?>
<?php
    $invoice_id = $_GET['id'];
    //$invoice = "select * from orders where invoice_id = $invoice_id";
    
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
    where invoice_id = $invoice_id";
    $result = $conn->query($invoice);
    $row = $result->fetch_assoc();
    $user_id = $row['user_id'];
    $id = $row['id'];

    $userId = $_SESSION['user_id'];
    $session_user = "select * from users where id = $userId";
    $result2 = $conn->query($session_user);
    $row2 = $result2->fetch_assoc();

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
        $sql = "INSERT INTO record_payment (orders_id, invoice_no, payment_method, amount, remarks, paid_date) 
                        VALUES ('$order_id', '$invoice_no', '$payment_method', '$amount', '$remarks', '$created_at')";
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
</style>
<div class="dashboard-main-body">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-20">
        <div>
            <h6 class="fw-semibold mb-0">Order #<?php echo $invoice_id; ?></h6>
        </div>
        <div>
        <?php 
            if($row2['role'] == "1" || $row2['role'] == "2") {?>  
            <button type="button" class="btn btn-sm btn-primary radius-8 d-inline-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#exampleModal">
                <iconify-icon icon="lucide:edit" class="text-xl"></iconify-icon>
                Record Payment
            </button>
            <?php } ?>
            <button type="button" class="btn btn-sm btn-success radius-8 d-inline-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#Payment" id="currency-symbol-display">
                <?= htmlspecialchars($symbol) ?> Payment History
            </button>
            <!-- <button type="button" class="btn btn-sm btn-success radius-8 d-inline-flex align-items-center gap-1" >
                <iconify-icon icon="basil:edit-outline" class="text-xl"></iconify-icon>
                Edit
            </button>
            <button type="button" class="btn btn-sm btn-danger radius-8 d-inline-flex align-items-center gap-1" onclick="printInvoice()">
                <iconify-icon icon="basil:printer-outline" class="text-xl"></iconify-icon>
                Print
            </button> -->
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
                                    <td id="currency-symbol-display">' . htmlspecialchars($symbol) . htmlspecialchars($row_history['amount']) . '</td>
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
                    <div class="card-header py-20 border-none" style="box-shadow: 0px 3px 3px 0px lightgray">
                        <h6>Plan Details</h6>
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
                            <h6 class="mb-0">Order Summary</h6>
                            <p class="mb-0">Order Summary includes discounts & taxes</p>
                        </div>
                        <div class="align-content-center">
                            <h4 class="mb-0" id="currency-symbol-display"><?= htmlspecialchars($symbol) ?><?php echo $row['amount']; ?></h4>
                        </div>
                        
                    </div>
                    <div class="card-body p-16">
                        <!-- <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                            <label>Plan Name</label>
                            <label><?php echo $row['plan']; ?></label>
                        </div> -->
                        <table class="w-100 plan-details-table mb-0">
                            <tbody>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['plan_name']); ?></td>
                                    <td class="text-end" id="currency-symbol-display"><?= htmlspecialchars($symbol) ?><?= number_format($row['price'], 2) ?></td>
                                </tr>
                                <?php
                                    // Base price (plan price)
                                    $base_price = (float)$row['price'];

                                    // Addon cost calculation
                                    $addon_total = 0;
                                    if (!empty($row['addon_service'])) {
                                        $addon_ids = explode(',', $row['addon_service']);
                                        $addon_ids = array_map('intval', $addon_ids); // sanitize IDs

                                        if (!empty($addon_ids)) {
                                            $addon_id_list = implode(',', $addon_ids);
                                            $addon_query = "SELECT name, cost FROM `add-on-service` WHERE id IN ($addon_id_list)";
                                            $addon_result = $conn->query($addon_query);

                                            while ($addon_row = $addon_result->fetch_assoc()) {
                                                $addon_cost = (float)$addon_row['cost'];
                                                $addon_total += $addon_cost;

                                                echo "<tr>
                                                        <td>" . htmlspecialchars($addon_row['name']) . " (Add-on)</td>
                                                        <td class='text-end' id='currency-symbol-display'>" . htmlspecialchars($symbol) . number_format($addon_cost, 2) . "</td>
                                                    </tr>";
                                            }
                                        }
                                    }
                                    // Subtotal before GST
                                    $subtotal = $base_price + $addon_total;

                                    // GST 18%
                                    $gst = $subtotal * 0.18;

                                    // Total amount = subtotal + GST
                                    $grand_total = $subtotal + $gst;
                                ?>
                                <tr>
                                    <td>Tax (GST 18%)</td>
                                    <td class="text-end" id="currency-symbol-display"><?= htmlspecialchars($symbol) ?><?= number_format($gst, 2) ?></td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Payments</td>
                                    <td class="text-end fw-bold" id="currency-symbol-display"><?= htmlspecialchars($symbol) ?><?= number_format($row['payment_made'], 2) ?></td>
                                </tr>
                                <tr>
                                    <td class="fw-bold border-0">Total Payable</td>
                                    <td class="text-end fw-bold border-0" id="currency-symbol-display">
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
</div>
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

<?php include './partials/layouts/layoutBottom.php' ?>