<?php
include './partials/layouts/layoutTop.php';
ini_set('display_errors', 1);
      ini_set('display_startup_errors', 1);
      error_reporting(E_ALL);
      $result1 = $conn->query("SELECT symbol FROM currencies WHERE is_active = 1 LIMIT 1");
    $symbol = "$"; // default
    if ($row = $result1->fetch_assoc()) {
        $symbol = $row['symbol'];
    }
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $web_id = $_POST['web_id'];
    $user_id = $_POST['user_id'];
    $upgrade_package_id = $_POST['upgrade_package_id'];
    $total_amount = $_POST['total_amount'];
    $hostinger_balance = $_POST['hostinger_balance'];
    // GST is 18% on (total_amount - hostinger_balance)
    $gst_base = max($total_amount - $hostinger_balance, 0);
    $gst = round($gst_base * 0.18, 2);

    // Final payable = base + GST
    $amount_to_pay = round($gst_base + $gst, 2);
    //$amount_to_pay = $_POST['amount_to_pay'];
    $duration = $_POST['duration'];
    $cat_id = $_POST['cat_id'];
    $invoice_id = $_POST['invoice_id'];

    // Fetch Package Title
    $website_sql = "SELECT * FROM package WHERE id = ? LIMIT 1";
    $stmt_web = $conn->prepare($website_sql);
    $stmt_web->bind_param("i", $upgrade_package_id);
    $stmt_web->execute();
    $result_web = $stmt_web->get_result();
    $website = $result_web->fetch_assoc();
    $title = $website['title'];
}

if (isset($_POST['continuePay'])) {
    $web_id = $_POST['web_id'];
    $user_id = $_POST['user_id'];
    $upgrade_package_id = $_POST['upgrade_package_id'];
    $total_amount = $_POST['total_amount'];
    $hostinger_balance = $_POST['hostinger_balance'];
    $amount_to_pay = $_POST['amount_to_pay'];
    $duration = $_POST['duration'];
    $cat_id = $_POST['cat_id'];
    $pkg_invoice_id = rand(10000000, 99999999);
    $type = "package";
    $gst = 0;
    $payment_method = "Direct pay";
    $status = "Pending";

    // --- INSERT new record into websites table ---
    $insert_sql = "
        INSERT INTO websites (
            user_id, domain, plan, duration, status, created_at, cat_id, invoice_id,
            access1, access_www, ip_address, nameserver1, nameserver2, facebook_id, password_1,
            insta_id, password_2, access_your_marketing_at, access_your_marketing_with_www,
            marketing_ip_address, nameserver_1, nameserver_2, product_id, type, is_Active
        ) VALUES (
            ?, 'N/A', ?, ?, 'Pending', NOW(), ?, ?, 
            NULL, NULL, NULL, NULL, NULL, NULL, NULL,
            NULL, NULL, NULL, NULL, NULL, NULL, NULL, ?, ?, 1
        )
    ";

    $stmt_insert = $conn->prepare($insert_sql);
    $stmt_insert->bind_param(
        "iisisis",
        $user_id,
        $upgrade_package_id,
        $duration,
        $cat_id,
        $pkg_invoice_id,
        $upgrade_package_id,
        $type
    );

    if (!$stmt_insert->execute()) {
        echo "<script>alert('Error inserting new website: " . $stmt_insert->error . "');</script>";
    } else {
        // --- INSERT into orders table ---
        $order_sql = "
            INSERT INTO orders (
                user_id, invoice_id, plan, duration, price, addon_price, subtotal, gst, discount,
                amount, balance_due, payment_made, payment_method, addon_service, type,
                discount_type, status, created_on
            ) VALUES (
                ?, ?, ?, ?, ?, NULL, ?, ?, 0, ?, ?, 0, ?, NULL, ?, NULL, ?, NOW()
            )
        ";

        $stmt_order = $conn->prepare($order_sql);
        $stmt_order->bind_param(
            "sisssddddsss",
            $user_id,
            $pkg_invoice_id,
            $upgrade_package_id,
            $duration,
            $total_amount,
            $gst_base,      // subtotal (total - credits)
            $gst,           // GST (18%)
            $amount_to_pay, // amount (subtotal + GST)
            $amount_to_pay, // balance_due
            $payment_method,
            $type,
            $status
        );        

        if (!$stmt_order->execute()) {
            echo "<script>alert('Error inserting order: " . $stmt_order->error . "');</script>";
        } else {
            // --- UPDATE old website record ---
            $update_sql = "UPDATE websites SET is_Active = 0 WHERE id = ?";
            $stmt_update = $conn->prepare($update_sql);
            $stmt_update->bind_param("i", $web_id);
        }
            if (!$stmt_update->execute()) {
                echo "<script>alert('Error inserting order: " . $stmt_update->error . "');</script>";
            } else {
                // --- UPDATE old website record ---
                $update_order_sql = "UPDATE orders SET is_Active = 0 WHERE invoice_id = ?";
                $stmt_order_update = $conn->prepare($update_order_sql);
                $stmt_order_update->bind_param("i", $invoice_id);



            if ($stmt_order_update->execute()) {
                echo "<script>
                    Swal.fire({
                        title: 'Success!',
                        text: 'Upgrade successfully.',
                        confirmButtonText: 'OK'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = 'subscription.php';
                        }
                    });
                </script>";
                // Optionally redirect:
                // header('Location: success-page.php');
                // exit;
            } else {
                echo "<script>alert('Failed to deactivate old website record.');</script>";
            }

            $stmt_update->close();
        }

        $stmt_order->close();
    }

    $stmt_insert->close();
}
?>

<style>
    .plan-details-table tbody tr td {
        padding: 15px .5rem;
        border-bottom: 1px solid #dadada;
        width: 50%;
    }
    .ad-box {
        background: lightgoldenrodyellow;
        padding: 2px;
        border: 1px solid;
        margin: 10px 0 0;
    }

    /* Payment Option Boxes */
    .payment-option-box {
        border: 1px solid black;
        border-radius: 6px;
        padding: 10px 16px;
        display: flex;
        align-items: center;
        background-color: #fff;
        width: auto;
        max-width: 200px;
        cursor: pointer;
        flex-grow: 0;
        margin-right: 10px;
        margin-bottom: 10px;
        user-select: none;
        transition: background-color 0.2s ease;
    }
    .payment-option-box:hover {
        background-color: #fff8dc;
    }
    .payment-option-box input[type="radio"] {
        margin: 0;
        cursor: pointer;
    }
    .payment-option-box label {
        cursor: pointer;
        margin-left: 8px;
        display: flex;
        align-items: center;
        gap: 6px;
        font-weight: 600;
    }
    .icon-circle {
        background-color: #fec700;
        color: white;
        border-radius: 50%;
        padding: 6px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 24px;
        height: 24px;
        font-size: 12px;
    }

    /* Card shadow and button styles */
    .custom-pay-btn {
        background-color: #fec700;
        color: black;
        border: 1px solid black;
        box-shadow: none;
        border-radius: 0;
        padding: 8px 20px;
        font-weight: 600;
    }
    .custom-pay-btn:hover {
        background-color: #ffd700;
    }
    .card-shadow {
        box-shadow: 0px 3px 3px 0px lightgray;
    }

    /* Responsive adjustments */
    @media (max-width: 992px) {
        .payment-option-box {
            max-width: 180px;
            padding: 8px 14px;
            font-size: 0.95rem;
        }
    }
    @media (max-width: 576px) {
        .payment-option-box {
            max-width: 100%;
            margin-right: 0;
            padding: 12px 20px;
            font-size: 1rem;
        }
        .payment-option-box label {
            gap: 8px;
            font-weight: 700;
        }
    }
    .payment-detail { display:none; }
</style>
<!-- Payment Method Card -->
<div class="dashboard-main-body">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
            <h6 class="fw-semibold mb-0">Your Cart</h6>
            <form method="POST" action="">
                <input type="hidden" name="web_id" value="<?php echo $web_id; ?>">
                <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
                <input type="hidden" name="upgrade_package_id" value="<?php echo $upgrade_package_id; ?>">
                <input type="hidden" name="total_amount" value="<?php echo $total_amount; ?>">
                <input type="hidden" name="hostinger_balance" value="<?php echo $hostinger_balance; ?>">
                <input type="hidden" name="amount_to_pay" value="<?php echo $amount_to_pay; ?>">
                <input type="hidden" name="duration" value="<?php echo $duration; ?>">
                <input type="hidden" name="cat_id" value="<?php echo $cat_id; ?>">
                <input type="hidden" name="invoice_id" value="<?php echo $invoice_id; ?>">
                <input type="hidden" name="gst" value="<?php echo $gst; ?>">
                <button type="submit" name="continuePay" id="continuePayBtn" class="lufera-bg text-center btn-sm px-12 py-10 float-end" style="width:150px; border: 1px solid #000" value="Submit">Continue to Pay</button>
            </form>
        </div>
            <div class="row">
                <div class="col-6">
                    <div class="card h-100 radius-12">
                        <div class="card-header py-10 border-none card-shadow">
                            <h6 class="mb-0">Select Payment Mode</h6>
                            <!-- <p class="mb-0 text-muted">Order Summary includes discounts & taxes</p> -->
                        </div>
                        <div class="card-body p-16">
                            <p class="text-muted fw-medium mb-3">How would you like to make the payment? <span class="text-danger-600">*</span></p>

                            <div class="d-flex flex-wrap gap-3 justify-content-start">
                                <?php
                                    $payments = [
                                        'Bank Transfer' => 'bank-transfer',
                                        'Direct Pay'    => 'direct-pay',
                                        'PayPal'        => 'paypal-button-container'
                                    ];
                                    foreach ($payments as $label => $target): ?>
                                        <div class="payment-option-box">
                                            <input
                                                type="radio"
                                                class="form-check-input m-0"
                                                name="pay_method"
                                                id="pay<?= $target ?>"
                                                value="<?= $label ?>"
                                                data-target="<?= $target ?>"                   
                                            >
                                            <label for="pay<?= $target ?>">
                                                <?= $label ?>
                                                <span class="icon-circle">
                                                    <i class="fas fa-chevron-down"></i>
                                                </span>
                                            </label>
                                        </div>
                                <?php endforeach; ?>                            
                            </div>
                            <?php
                                $sql = "SELECT * FROM bank_details LIMIT 1";
                                $result3 = $conn->query($sql);
                                if ($result3->num_rows > 0) {
                                    $row = $result3->fetch_assoc();
                                    $id = $row['id'];
                                    $bank_name = $row['bank_name'];
                                    $ac_name = $row['ac_name'];
                                    $ac_no = $row['ac_no'];
                                    $branch = $row['branch'];
                                    $ifsc_code = $row['ifsc_code'];
                                    $micr = $row['micr'];
                                    $swift_code = $row['swift_code'];
                                }
                            ?>
                            <div id="bank-transfer" class="payment-detail">
                                <div class="row mt-3">
                                    <div class="col-12">
                                        <div class="card h-100 radius-12">
                                        <div class="card-header py-10 border-none card-shadow">
                                            <h6 class="mb-0">Bank Transfer</h6>
                                            <p class="mb-0 text-muted">Order Summary includes discounts & taxes</p>
                                        </div>
                                        <div class="card-body p-16">
                                            <div class="fw-semibold my-3">Bank A/C Details:</div>
                                            <div class="row gy-4 align-items-start">
                                            <div class="col-lg-5 col-md-6">
                                                <div class="table-responsive">
                                                <table class="table table-bordered small mb-0">
                                                    <tbody>
                                                    <tr><td>Bank Name</td><td><?php echo htmlspecialchars($bank_name); ?></td></tr>
                                                    <tr><td>Account Name</td><td><?php echo htmlspecialchars($ac_name); ?></td></tr>
                                                    <tr><td>Account No</td><td><?php echo htmlspecialchars($ac_no); ?></td></tr>
                                                    <tr><td>Account Branch</td><td><?php echo htmlspecialchars($branch); ?></td></tr>
                                                    <tr><td>IFSC</td><td><?php echo htmlspecialchars($ifsc_code); ?></td></tr>
                                                    <tr><td>MICR</td><td><?php echo htmlspecialchars($micr); ?></td></tr>
                                                    <tr><td>Swift Code</td><td><?php echo htmlspecialchars($swift_code); ?></td></tr>
                                                    </tbody>
                                                </table>
                                                </div>
                                            </div>
                                            <div class="col-lg-7 col-md-6 d-flex align-items-start">
                                                <div class="ms-lg-5 pt-0 w-100">
                                                <p class="mb-1 fw-medium">Please let us know!</p>
                                                <p class="mb-1 text-muted small">Once you are done with your payment please let us know.</p>
                                                <p class="mb-3 text-muted small">Thank You.</p>
                                                </div>
                                            </div>
                                            </div>
                                        </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div id="direct-pay" class="payment-detail">
                                <div class="row mt-3">
                                        <div class="col-12">
                                            <div class="card h-100 radius-12">
                                            <div class="card-header py-10 border-none card-shadow">
                                                <h6 class="mb-0">Direct Pay</h6>
                                                <p class="mb-0 text-muted">Order Summary includes discounts & taxes</p>
                                            </div>
                                            <div class="card-body p-16">
                                                <div class="fw-semibold my-3">Thank You!</div>

                                                <div class="row gy-4 align-items-start">
                                                    <div class="col-lg-7 col-md-6 d-flex align-items-start">
                                                        <div class="ms-lg-5 pt-0 w-100">
                                                            <p class="mb-1 fw-medium">Please confirm your payment with one of our representative.</p>
                                                            <p class="mb-1 text-muted small">Contact your Relationship manager or call us at +91 -86-80808-204 or write to us at info@luferatech.com.</p>
                                                            <p class="mb-3 text-muted small">For futher support.</p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div id="paypal-button-container" class="payment-detail mt-3"></div>
                        </div>
                    </div>
                
                <div class="col-6">
                    <div class="card h-100 radius-12">
                        <div class="card-header py-10 border-none d-flex justify-content-between card-shadow">
                            <div>
                                <h6 class="mb-0">Order Summary</h6>
                                <!-- <p class="mb-0">Sub total does not include applicable taxes</p> -->
                            </div>
                            
                        </div>
                        <div class="card-body p-16">
                            <table class="table plan-details-table mb-0 w-100">
                                <tbody>
                                
                                     <tr>
                                        <td><?php echo $title; ?></td>
                                        <td class="text-end"><?php echo htmlspecialchars($symbol) . number_format($total_amount, 2); ?></td>
                                    </tr> 
                                    <!-- Tax (GST 18%) -->
                                    <tr>
                                        <td>Credits</td>
                                        <td class="text-end"><?php echo htmlspecialchars($symbol) . number_format($hostinger_balance, 2); ?></td>
                                    </tr>
                                    <tr>
                                        <td>Tax (GST 18%)</td>
                                        <td class="text-end"><?php echo htmlspecialchars($symbol) . number_format($gst, 2); ?></td>
                                    </tr>

                                    <!-- Estimated Total -->
                                    <tr>
                                        <td class="border-0 fw-semibold">Total</td>
                                        <td class="border-0 text-end fw-semibold text-xl" id="currency-symbol-display">
                                            <?php echo htmlspecialchars($symbol) . number_format($amount_to_pay, 2); ?>
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
    document.addEventListener('DOMContentLoaded', function () {
        const payBtn = document.getElementById('continuePayBtn');
        const paypalContainer = document.getElementById('paypal-button-container');

        // Always hide PayPal section initially
        paypalContainer.style.display = 'none';

        // Listen for changes in payment method radio buttons
        document.querySelectorAll('input[name="pay_method"]').forEach(radio => {
            radio.addEventListener('change', function () {
                const selectedTarget = this.getAttribute('data-target');

                if (selectedTarget === 'paypal-button-container') {
                    // Hide continue button (inline style) and show PayPal section
                    payBtn.style.display = 'none';
                    paypalContainer.style.display = 'block';
                } else {
                    // Show continue button (inline style) and hide PayPal section
                    payBtn.style.display = 'inline-block';
                    paypalContainer.style.display = 'none';
                }
            });
        });
    });
</script>

<script>
    document.addEventListener('DOMContentLoaded', () => {
    const radios   = document.querySelectorAll('input[name="pay_method"]');
    const details  = document.querySelectorAll('.payment-detail');

    // convenience function
    function showDetail(id) {
        details.forEach(el => { el.style.display = 'none'; });
        const chosen = document.getElementById(id);
        if (chosen) { chosen.style.display = 'block'; }
    }

    // run once so the default-checked radio shows its panel on load
    const checked = document.querySelector('input[name="pay_method"]:checked');
    if (checked) { showDetail(checked.dataset.target); }

    // change handler
    radios.forEach(radio =>
        radio.addEventListener('change', e => showDetail(e.target.dataset.target))
    );
    });
</script>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        const btn = document.getElementById("continuePayBtn");

        btn.addEventListener("click", function (e) {
            const selected = document.querySelector('input[name="pay_method"]:checked');

            if (!selected) {
                e.preventDefault(); // Stop form submission

                Swal.fire({
                    icon: 'warning',
                    title: 'Select Payment Method',
                    // text: 'Please select a payment method before continuing.',
                    confirmButtonColor: '#fec700'
                });
            }
        });
    });
</script>
<?php include './partials/layouts/layoutBottom.php' ?>