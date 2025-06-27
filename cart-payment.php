<?php include './partials/layouts/layoutTop.php' ?>

<?php
    $Id = $_SESSION['user_id'];
    
    $sql = "select user_id, username, role, photo from users where id = $Id";
    $result = $conn ->query($sql);
    $row = $result ->fetch_assoc();
    $role = $row['role'];
    $UserId = $row['user_id'];
    $username = $row['username'];
    $photo = !empty($row['photo']) ? $row['photo'] : 'assets/images/user1.png';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $plan_name = $_POST['plan_name'];
        $price = $_POST['price'];
        $duration = $_POST['duration'];
        $total_price = $_POST['total_price'];
        $rec_id = $_POST['receipt_id'];

        // $gst = $price * 0.18; // 10% GST
        // $total_price = $price + $gst;

        $gst = $total_price - $price;
        $created_on = $_POST['created_on'];
    }

    if (isset($_POST['save'])) {
        $pay_method = $_POST['pay_method'];
        $rec_id = $_POST['rec_id'];
        $plan_name = $_POST['plan_name'];
        $duration = $_POST['duration'];
        $total_price = $_POST['total_price'];
        $created_at = date("Y-m-d H:i:s");
        $price = $_POST['price'];
        $user_id = $_SESSION['user_id'];
        $sql = "SELECT user_id FROM users WHERE id = $user_id";
        $result = mysqli_query($conn, $sql);
        $row = mysqli_fetch_assoc($result);
        $client_id = $row['user_id'];
        $gst = $_POST['gst'];
        $discount = $payment_made = "0";
 
        $sql = "INSERT INTO orders (user_id, invoice_id, plan, duration, amount, gst, price, status, payment_method, discount, payment_made, created_on, subtotal, balance_due) VALUES 
                ('$client_id', '$rec_id', '$plan_name', '$duration' ,'$total_price', '$gst', '$price', 'Pending', '$pay_method', '$discount', '$payment_made', '$created_at', '$total_price', '$total_price')";


        if (mysqli_query($conn, $sql)) {

            // Generate a domain from the username
            // $domain = strtolower(preg_replace('/\s+/', '', $username)) . ".lufera.com";

            $domain = "N/A";

            // Insert new website record
            $siteInsert = "INSERT INTO websites (user_id, domain, plan, duration, status) 
                        VALUES ('$client_id', '$domain', '$plan_name', '$duration', 'Pending')";
            mysqli_query($conn, $siteInsert);


            echo "
            <script>
                Swal.fire({
                    title: 'Success!',
                    text: 'Invoice Created Successfully.',
                    confirmButtonText: 'OK',
                    allowOutsideClick: false
                }).then((result) => {
                    if (result.isConfirmed) {
                        // window.location.href = 'invoice-preview.php?id=$rec_id';
                        window.location.href = 'orders.php';
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

    // USER sends payment request (→ notify all admins)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save']) && $role != '1') {
        $msg = "$username has sent a payment request.";

        $adminQuery = $conn->query("SELECT user_id FROM users WHERE role = '1'");
        while ($adminRow = $adminQuery->fetch_assoc()) {
            $adminUserId = $adminRow['user_id'];

            $stmt = $conn->prepare("INSERT INTO notifications (user_id, message, n_photo) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $adminUserId, $msg, $photo);
            $stmt->execute();
        }
    }
?>

<!-- SweetAlert2 CDN -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    .tagline {
        border-bottom: 1px solid #fec700;
    }
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

<div class="dashboard-main-body">
    <form method="post">
        <input type="hidden" value="<?php echo $duration; ?>" name="duration">
        <input type="hidden" value="<?php echo $rec_id; ?>" name="rec_id">
        <input type="hidden" value="<?php echo $plan_name; ?>" name="plan_name">
        <input type="hidden" value="<?php echo $price; ?>" name="price">
        <input type="hidden" value="<?php echo $gst; ?>" name="gst">
        <input type="hidden" value="<?php echo $total_price; ?>" name="total_price">

        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
            <h6 class="fw-semibold mb-0">Your Cart</h6>
            <button type="submit" name="save" id="continuePayBtn" class="lufera-bg text-center btn-sm px-12 py-10 float-end" style="width:150px; border: 1px solid #000" value="Submit">Continue to Pay</button>
        </div>

        <div class="mb-40">
            <div class="row gy-4">
                <!-- First Card -->
                <div class="col-lg-6 col-md-12">
                    <div class="card h-100 radius-12">
                        <div class="card-header py-10 border-none card-shadow">
                            <div class="d-flex justify-content-between align-items-center">
                                <h6 class="mb-0"><?php echo $plan_name; ?></h6>
                                <span class="text-muted small">Receipt ID: <?php echo $rec_id; ?></span>
                            </div>
                            <p class="mb-0">Perfect plan to get started for your own Website</p>
                        </div>
                        <div class="card-body p-16">
                            <table class="plan-details-table mb-0 w-100">
                                <tbody>
                                    <tr>
                                        <td>Period</td>
                                        <td><?php echo $duration; ?></td>
                                    </tr>
                                    <tr>
                                        <td>Validity</td>
                                        <td>
                                            <?php
                                                $start_date = new DateTime($created_on);

                                                $duration_str = $duration;
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
                                        <td class="border-0" colspan="2">Renews at $1500/year for 3 Years
                                            <p class="text-sm ad-box">Great news! Your FREE domain + 3 months FREE are included with this order</p>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Second Card -->
                <div class="col-lg-6 col-md-12">
                    <div class="card h-100 radius-12">
                        <div class="card-header py-10 border-none d-flex justify-content-between card-shadow">
                            <div>
                                <h6 class="mb-0">Sub Total</h6>
                                <p class="mb-0">Sub total does not include applicable taxes</p>
                            </div>
                            <div class="align-content-center">
                                <h4 class="mb-0">$<?php echo $price; ?></h4>
                            </div>
                        </div>
                        <div class="card-body p-16">
                            <table class="plan-details-table mb-0 w-100">
                                <tbody>
                                    <tr>
                                        <td>Discount</td>
                                        <td class="text-end">N/A</td>
                                    </tr>
                                    <tr>
                                        <td>Tax (GST 18%)</td>
                                        <td class="text-end">$<?php echo $gst; ?></td>
                                    </tr>
                                    <tr>
                                        <td class="border-0">Estimated Total</td>
                                        <td class="border-0 text-end">$<?php echo $total_price; ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payment Method Card -->
            <div class="row">
                <div class="col-12 mt-3">
                    <div class="card h-100 radius-12">
                        <div class="card-header py-10 border-none card-shadow">
                            <h6 class="mb-0">Select Payment Mode</h6>
                            <p class="mb-0 text-muted">Order Summary includes discounts & taxes</p>
                        </div>
                        <div class="card-body p-16">
                            <p class="text-muted fw-medium mb-3">How would you like to make the payment? <span class="text-danger-600">*</span></p>

                            <div class="d-flex flex-wrap gap-3 justify-content-start">
                                <?php
                                    $payments = [
                                        'Bank Transfer' => 'bank-transfer',
                                        'Direct Pay'    => 'direct-pay',
                                        'PayPal'        => 'paypal',
                                        'Card'          => 'card'
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
                                                    <tr><td>Bank Name</td><td>Kotak Mahindra Bank</td></tr>
                                                    <tr><td>Account Name</td><td>Avinash Balasubramaniyam</td></tr>
                                                    <tr><td>Account No</td><td>84548518445</td></tr>
                                                    <tr><td>Account Branch</td><td>Thillainagar, Trichy</td></tr>
                                                    <tr><td>IFSC</td><td>FGF545DFSE</td></tr>
                                                    <tr><td>MICR</td><td>4852124</td></tr>
                                                    <tr><td>Swift Code</td><td>FDSEWSWR</td></tr>
                                                    </tbody>
                                                </table>
                                                </div>
                                            </div>
                                            <div class="col-lg-7 col-md-6 d-flex align-items-start">
                                                <div class="ms-lg-5 pt-0 w-100">
                                                <p class="mb-1 fw-medium">Please let us know!</p>
                                                <p class="mb-1 text-muted small">Once you are done with your payment please let us know.</p>
                                                <p class="mb-3 text-muted small">Thank You.</p>
                                                <button type="button" class="lufera-bg text-center btn-sm px-12 py-10" style="width:100px; border: 1px solid #000">Explore</button>
                                                <!-- <button type="button" class="lufera-bg text-white px-4 py-2">Explore</button> -->
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
                                                            <button type="button" class="lufera-bg text-center btn-sm px-12 py-10" style="width:100px; border: 1px solid #000">Explore</button>
                                                            <!-- <button type="button" class="lufera-bg text-white px-4 py-2">Explore</button> -->
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div id="paypal" class="payment-detail">PayPal...</div>
                            <div id="card" class="payment-detail">Card...</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>    
    </form>
</div>

<script>
    $('#updateForm').submit(function(e) {
        e.preventDefault();

        $.ajax({
            url: 'update.php',
            type: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                $('#result').html(response);
                loadUserData(); // Reload user data after update
            },
            error: function(xhr) {
                $('#result').html("Error updating data.");
            }
        });
    });

    loadUserData();
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