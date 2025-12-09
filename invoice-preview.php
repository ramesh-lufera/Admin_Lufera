<?php $script = '<script>
                    function printInvoice() {
                        var printContents = document.getElementById("invoice").innerHTML;
                        var originalContents = document.body.innerHTML;

                        document.body.innerHTML = printContents;

                        window.print();

                        document.body.innerHTML = originalContents;
                    }
                </script>';?>

<?php include './partials/layouts/layoutTop.php' ?>

<style>
    .invoice_table {
        font-size:16px !important;
        /* width:300px; */
    }
</style>

<?php
    $company_sql = "select * from company";
    $company_fetch = $conn->query($company_sql);
    $company_row = $company_fetch->fetch_assoc();

    $invoice_id = $_GET['id'];
    $type = $_GET['type'] ?? 'normal';

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
    
    $user = "select * from users where id = '$user_id'"; 
    $results = $conn->query($user);
    $rows = $results->fetch_assoc();
    $user_ids = $rows['email'];

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
                logActivity(
                    $conn,
                    $loggedInUserId,
                    "Invoice",                   // module
                    "Record payment created successfully - $payment_id"  // description
                  );

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
                            window.history.back();
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
        <div class="dashboard-main-body">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
                <a class="cursor-pointer fw-bold" onclick="history.back()"><span class="fa fa-arrow-left"></span>&nbsp; Back</a>    
                <h6 class="fw-semibold mb-0">Invoice Preview</h6>
                <a class="cursor-pointer fw-bold visibility-hidden" onclick="history.back()"><span class="fa fa-arrow-left"></span>&nbsp; Back</a> 
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="d-flex flex-wrap align-items-center justify-content-end gap-2">

                    <?php 
                    if($row2['role'] == "1" || $row2['role'] == "2") {?>  
                        <button type="button" class="btn btn-sm btn-primary radius-8 d-inline-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#exampleModal">
                            <iconify-icon icon="lucide:edit" class="text-xl"></iconify-icon>
                            Record Payment
                        </button>
                    <?php } ?>

                    <?php 
                    if($row2['role'] == "1" || $row2['role'] == "2") {?>  
                    <a href="invoice-preview-edit.php?id=<?php echo $invoice_id; ?>">  
                        <button type="button" class="btn btn-sm btn-success radius-8 d-inline-flex align-items-center gap-1" >
                            <iconify-icon icon="basil:edit-outline" class="text-xl"></iconify-icon>
                            Edit
                        </button>
                    </a> 
                        <?php } ?>   
                        <button type="button" class="btn btn-sm btn-danger radius-8 d-inline-flex align-items-center gap-1" onclick="printInvoice()">
                            <iconify-icon icon="basil:printer-outline" class="text-xl"></iconify-icon>
                            Print
                        </button>
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
                
                <div class="card-body py-40">
                    <div class="row justify-content-center" id="invoice">
                        <div class="col-lg-8">
                            <div class="shadow-4 border radius-8">
                                <div class="p-20 d-flex flex-wrap justify-content-between gap-3 ">
                                    <div>
                                        <!-- <h3 class="text-xl">Invoice No: <?php echo $invoice_id; ?></h3>
                                        <p class="mb-1 text-sm">Date : <?php echo date('d/m/Y', strtotime($row['created_on'])); ?></p> -->
                                        <!-- <img src="assets/images/logo_lufera.png" alt="image" class="mb-8" width="200px"> -->
                                        <img src="uploads/company_logo/<?php echo $logo; ?>" alt="site logo" class="light-logo" width="200px">
                                        <div class="">
                                            <p class="mb-0 text-xl"><b><?php echo $company_row['full_name']; ?></b></p>
                                            <p class="mb-0 text-sm"><?php echo $company_row['address']; ?>, <?php echo $company_row['city']; ?>,<?php echo $company_row['state']; ?>,<?php echo $company_row['zip_code']; ?>, <?php echo $company_row['country']; ?></p>
                                            <p class="mb-0 text-sm"><?php echo $company_row['phone_no']; ?></p>
                                            <p class="mb-0 text-sm"><?php echo $company_row['website']; ?></p>
                                            <p class="mb-0 text-sm">GSTIN: <?php echo $company_row['gst_in']; ?></p>
                                        </div>
                                    </div>
                                    <div class="">
                                       <!-- <p class="mb-0"><b>Lufera Infotech Pvt. Ltd. (OPC)</b></p>
                                        <p class="mb-0 text-sm">96/1, Bharathidasan Salai,Cantonment, Trichy,TN, India, 620 001</p>
                                        <p class="mb-0 text-sm">+91 86 80808 204</p>
                                        <p class="mb-0 text-sm">www.luferatech.com</p> -->
                                       <?php if($row['balance_due'] != "0.00" ){ ?> 
                                            <h4>Proforma Invoice</h4>
                                        <?php } ?>
                                            <p class="text-md mb-0">Invoice Date: <?php echo date('d/m/Y', strtotime($row['created_on'])); ?> </p>
                                        <p class="text-md mb-0">Bill To:</p>
                                        <p class="text-md mb-0"><?php echo $rows['business_name']; ?> </p>
                                        <p class="text-md mb-0"><?php echo $rows['address']; ?></p>
                                        <p class="text-md mb-0"><?php echo $rows['city']; ?> <?php echo $rows['pincode']; ?></p>
                                        <p class="text-md mb-0"><?php echo $rows['state']; ?>, <?php echo $rows['country']; ?></p>
                                        <p class="text-md mb-0">GSTIN: <?php echo $rows['gst_in']; ?></p>
                                    </div>
                                </div>

                                <div class="py-28 px-20">
                                    <div class="">
                                        <div class="table-responsive scroll-sm">
                                            <!-- <table class="table table-bordered text-sm">
                                                <thead>
                                                    <tr>
                                                       
                                                        <th scope="col" class="text-sm">Plan</th>
                                                        <th scope="col" class="text-sm">Qty</th>
                                                        <th scope="col" class="text-sm">Payment Method</th>
                                                        <th scope="col" class="text-sm">Status</th>
                                                        <th scope="col" class="text-end text-sm">Amount</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td><?php echo $row['plan_name']; ?> </td>
                                                        <td><?php echo $row['duration']; ?> </td>
                                                        <td><?php echo $row['payment_method']; ?> </td>
                                                        <td><?php echo $row['status']; ?> </td>
                                                        <td class="text-end text-sm" id="currency-symbol-display"><?= htmlspecialchars($symbol) ?> <?php echo $row['price']; ?> </td>
                                                    </tr>
                                                </tbody>
                                            </table> -->
                                            <table class="table table-bordered mb-0">
                                                <thead>
                                                    <th class="w-25">Description</th>
                                                    <th class="w-25">Price</th>
                                                    <th class="w-25">Tax</th>
                                                    <th class="text-end w-25">Total</th>
                                                </thead>
                                                <tbody>
                                                    <td class="w-25"><?php echo $row['plan_name']; ?></td>
                                                    <td class="w-25"><?= htmlspecialchars($symbol) ?> <?php echo number_format($row['price'], 2); ?> </td>
                                                    <td class="w-25"><?= htmlspecialchars($symbol) ?> <?php echo number_format($row['gst'], 2); ?></td>
                                                    <td class="text-end w-25"><?= htmlspecialchars($symbol) ?> <?php echo number_format(floatval($row['price']) + floatval($row['gst']), 2); ?></td>
                                                </tbody>
                                            </table>
                                        </div>

                                        <?php
                                        // Add-on services table
                                        if (!empty($row['addon_service'])) {
                                            $addon_ids = explode(',', $row['addon_service']);
                                            $addon_ids = array_map('intval', $addon_ids); // sanitize IDs

                                            if (!empty($addon_ids)) {
                                                $addon_id_list = implode(',', $addon_ids);
                                                $addon_query = "SELECT name, cost FROM `add-on-service` WHERE id IN ($addon_id_list)";
                                                $addon_result = $conn->query($addon_query);

                                                if ($addon_result->num_rows > 0) {
                                                    echo '
                                                    <table class="table table-bordered w-100">
                                                        <tbody>';

                                                    while ($addon_row = $addon_result->fetch_assoc()) {
                                                        echo "<tr>
                                                                <td class='w-25'>" . htmlspecialchars($addon_row['name']) . "</td>
                                                                <td class='w-25'>" . htmlspecialchars($symbol) . " " . number_format($row['addon_price'], 2) . "</td>
                                                                <td class='w-25'>" . htmlspecialchars($symbol) . " " . htmlspecialchars($row['addon_gst']) . "</td>
                                                                <td class='w-25 text-end'>
                                                                    <span class='text-primary-light'>"
                                                                    . htmlspecialchars($symbol) . " " . number_format(floatval($row['addon_price']) + floatval($row['addon_gst']), 2) .
                                                                    "</span>
                                                                </td>
                                                            </tr>";
                                                    }
                                                    echo '</tbody>
                                                                </table>';
                                                }
                                            }
                                        }
                                        ?>
                                        
                                        <div class="d-flex flex-wrap justify-content-end gap-3">
                                            <div>
                                                <table class="invoice_table text-end">
                                                    <tbody>
                                                        <?php if($row['discount'] != null){ ?>
                                                            <!-- <tr>
                                                                <td class="pe-64  p-8">Discount</td>
                                                                <td class=" p-8">
                                                                    <span class="text-primary-light fw-semibold"> <?php echo $row['discount']; ?> <?php echo $row['discount_type'] ?></span>
                                                                </td>
                                                            </tr> -->
                                                        <?php } ?>
                                                        <!-- <tr>
                                                            <td class="pe-64 p-8 fw-semibold">Sub Total</td>
                                                            <td class=" p-8">
                                                            <?php
                                                                $total = floatval($row['price']) + floatval($row['addon_price']);
                                                                ?>
                                                                <span class="text-primary-light" id="currency-symbol-display">
                                                                    <?= htmlspecialchars($symbol) ?> <?= number_format($total, 2); ?>
                                                                </span>
                                                            </td>
                                                        </tr> -->

                                                        <!-- <tr>
                                                            <td class="pe-64 p-8 fw-semibold">GST 18%</td>
                                                            <td class=" p-8">
                                                                <span class="text-primary-light" id="currency-symbol-display"><?= htmlspecialchars($symbol) ?> <?php echo $row['gst']; ?></span>
                                                            </td>
                                                        </tr>
                                                        <?php if ($row['coupon_code']) { ?>
                                                            <tr>
                                                                <td class="pe-64 p-8 fw-semibold">Coupon Applied (<?php echo $row['coupon_code']; ?>)</td>
                                                                <td class=" p-8">
                                                                <span class="text-primary-light" id="currency-symbol-display"><?= htmlspecialchars($symbol) ?> <?php echo $row['discount_amount']; ?></span>
                                                                </td>
                                                            </tr>
                                                        <?php } ?>
                                                        <tr>
                                                            <td class="pe-64 p-8 fw-semibold">
                                                                <span class="text-primary-light">Total</span>
                                                            </td>
                                                            <td class="p-8">
                                                                <span class="text-primary-light" id="currency-symbol-display"><?= htmlspecialchars($symbol) ?> <?php echo $row['amount']; ?></span>
                                                            </td>
                                                        </tr>
                                                        <?php if($row['payment_made'] != null){ ?>
                                                            <tr>
                                                                <td class="pe-64 p-8 fw-semibold">
                                                                    <span class="text-primary-light">Payment Made</span>
                                                                </td>
                                                                <td class="p-8">
                                                                    <span class="text-primary-light" id="currency-symbol-display"><?= htmlspecialchars($symbol) ?> <?php echo $row['payment_made']; ?></span>
                                                                </td>
                                                            </tr>
                                                        <?php } ?>
                                                        <tr>
                                                            <td class="pe-64 p-8 fw-semibold">
                                                                <span class="text-primary-light">Balance Due</span>
                                                            </td>
                                                            <td class="p-8">
                                                                <span class="text-primary-light" id="currency-symbol-display"><?= htmlspecialchars($symbol) ?> <?php echo $row['balance_due']; ?></span>
                                                            </td>
                                                        </tr> -->
                                                        
                                                        <!-- <?php
                                                            // ✅ GST logic reused (same as above)
                                                            $tax_rate = 0;
                                                            $tax_name = "No Tax";

                                                            if (!empty($row['plan'])) {
                                                                $plan_id = intval($row['plan']);

                                                                if ($type === 'renewal') {
                                                                    $tax_query = $conn->query("
                                                                        SELECT t.tax_name, t.rate
                                                                        FROM package p
                                                                        LEFT JOIN taxes t ON p.gst_id = t.id
                                                                        INNER JOIN renewal_invoices r ON r.plan = p.id
                                                                        WHERE r.plan = $plan_id
                                                                        LIMIT 1
                                                                    ");
                                                                } else {
                                                                    $tax_query = $conn->query("
                                                                        SELECT t.tax_name, t.rate
                                                                        FROM package p
                                                                        LEFT JOIN taxes t ON p.gst_id = t.id
                                                                        INNER JOIN orders o ON o.plan = p.id
                                                                        WHERE o.plan = $plan_id
                                                                        LIMIT 1
                                                                    ");
                                                                }

                                                                if ($tax_query && $tax_query->num_rows > 0) {
                                                                    $tax_row = $tax_query->fetch_assoc();
                                                                    if (!empty($tax_row['rate'])) $tax_rate = floatval($tax_row['rate']);
                                                                    if (!empty($tax_row['tax_name'])) $tax_name = $tax_row['tax_name'];
                                                                }
                                                            }

                                                            // ✅ Compute GST amount and new totals
                                                            $gst_amount = $row['subtotal'] * ($tax_rate / 100);
                                                            $total_after_gst = floatval($row['subtotal']) + floatval($gst_amount) - floatval($row['discount_amount']);
                                                            $balance_due = $total_after_gst - $row['payment_made'];
                                                        ?> -->
                                                        <?php
                                                            // ✅ GST logic reused (same as above)
                                                            $tax_rate = 0;
                                                            $tax_name = "No Tax";

                                                            if (!empty($row['plan'])) {
                                                                $plan_id = intval($row['plan']);

                                                                // ================================
                                                                //   RENEWAL TIME (PACKAGE ONLY)
                                                                // ================================
                                                                if ($type === 'renewal') {

                                                                    $tax_query = $conn->query("
                                                                        SELECT t.tax_name, t.rate
                                                                        FROM package p
                                                                        LEFT JOIN taxes t ON p.gst_id = t.id
                                                                        INNER JOIN renewal_invoices r ON r.plan = p.id
                                                                        WHERE r.plan = $plan_id
                                                                        LIMIT 1
                                                                    ");

                                                                } else {

                                                                    // ================================
                                                                    //   NORMAL TIME (PACKAGE + PRODUCT)
                                                                    // ================================

                                                                    if ($row['type'] === 'package') {

                                                                        // ⭐ Normal Time → Package Logic (Already working)
                                                                        $tax_query = $conn->query("
                                                                            SELECT t.tax_name, t.rate
                                                                            FROM package p
                                                                            LEFT JOIN taxes t ON p.gst_id = t.id
                                                                            INNER JOIN orders o ON o.plan = p.id
                                                                            WHERE o.plan = $plan_id
                                                                            LIMIT 1
                                                                        ");

                                                                    } else if ($row['type'] === 'product') {

                                                                        // ⭐ Normal Time → PRODUCT Logic (NEW)
                                                                        // products.gst column contains tax ID
                                                                        $tax_query = $conn->query("
                                                                            SELECT t.tax_name, t.rate
                                                                            FROM products pr
                                                                            LEFT JOIN taxes t ON pr.gst = t.id
                                                                            INNER JOIN orders o ON o.plan = pr.id
                                                                            WHERE o.plan = $plan_id
                                                                            LIMIT 1
                                                                        ");

                                                                    }
                                                                }

                                                                // ================================
                                                                //   Fetch the tax details
                                                                // ================================
                                                                if (!empty($tax_query) && $tax_query->num_rows > 0) {
                                                                    $tax_row = $tax_query->fetch_assoc();
                                                                    if (!empty($tax_row['rate'])) $tax_rate = floatval($tax_row['rate']);
                                                                    if (!empty($tax_row['tax_name'])) $tax_name = $tax_row['tax_name'];
                                                                }
                                                            }

                                                            // ================================
                                                            // Compute GST + Totals
                                                            // ================================
                                                            $gst_amount = $row['subtotal'] * ($tax_rate / 100);
                                                            $total_after_gst = floatval($row['subtotal']) + floatval($gst_amount) - floatval($row['discount_amount']);
                                                            $balance_due = $total_after_gst - $row['payment_made'];
                                                        ?>
                                                        <!-- <tr>
                                                            <td class="pe-64 p-8 fw-semibold"><?php echo htmlspecialchars($tax_name); ?> (<?php echo $tax_rate; ?>%)</td>
                                                            <td class="p-8">
                                                                <span class="text-primary-light" id="currency-symbol-display"><?= htmlspecialchars($symbol) ?> <?= number_format($gst_amount, 2); ?></span>
                                                            </td>
                                                        </tr> -->
                                                        <?php if ($row['existing_balance']) { ?>
                                                            <tr>
                                                                <td class="pe-64 p-8 fw-semibold">Existing Plan</td>
                                                                <td class="p-8">
                                                                    <span class="text-primary-light" id="currency-symbol-display"><?= htmlspecialchars($symbol) ?> <?php echo number_format($row['existing_balance'], 2); ?></span>
                                                                </td>
                                                            </tr>
                                                        <?php } ?>
                                                        <tr>
                                                            <td class="pe-64 p-8 fw-semibold">
                                                                <span class="text-primary-light">Total</span>
                                                            </td>
                                                            <td class="p-8">
                                                            <span class="text-primary-light" id="currency-symbol-display">
                                                                <?= htmlspecialchars($symbol) ?>
                                                                <?= number_format(floatval($row['amount']), 2); ?>
                                                            </span>

                                                            </td>
                                                        </tr>
                                                        <?php if ($row['payment_made'] != null) { ?>
                                                            <tr>
                                                                <td class="pe-64 p-8 fw-semibold">
                                                                    <span class="text-primary-light">Payment Made</span>
                                                                </td>
                                                                <td class="p-8">
                                                                    <span class="text-primary-light" id="currency-symbol-display"><?= htmlspecialchars($symbol) ?> <?= number_format($row['payment_made'], 2); ?></span>
                                                                </td>
                                                            </tr>
                                                        <?php } ?>
                                                        <tr>
                                                            <td class="pe-64 p-8 fw-semibold">
                                                                <span class="text-primary-light">Balance Due</span>
                                                            </td>
                                                            <td class="p-8">
                                                                <span class="text-primary-light" id="currency-symbol-display"><?= htmlspecialchars($symbol) ?> <?= number_format($row['balance_due'], 2); ?></span>
                                                            </td>
                                                        </tr>

                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                    <?php 
                                        $tc_sql = "SELECT * FROM terms_conditions where apply_for = 'invoice'";
                                        $tc_result = $conn->query($tc_sql);
                                        if ($tc_result->num_rows > 0) {
                                            $tc_row = $tc_result->fetch_assoc();
                                            $id = $tc_row['id'];
                                            $title = $tc_row['title'];
                                            $content = $tc_row['content'];
                                        }
                                        if ($tc_result->num_rows > 0) { ?>
                                        <p>Terms & Conditions</p>
                                        <?php echo $content; ?>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
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
        </script>

<?php include './partials/layouts/layoutBottom.php' ?>