<?php include './partials/layouts/layoutTop.php' ?>

<?php
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
?>

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
    .payment-option-box {
        border: 1px solid black;
        border-radius: 6px;
        padding: 10px 16px;
        display: flex;
        align-items: center;
        background-color: #fff;
        min-width: 180px;
        cursor: pointer;
        flex-grow: 1;
    }
    .payment-option-box input[type="checkbox"] {
        margin: 0;
    }
    .icon-circle {
        /* background-color: #007bff; */
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
    @media (max-width: 768px) {
        .payment-option-box {
            width: 100%;
        }
    }
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
        <button name="save" id="continuePayBtn" type="submit" class="btn lufera-bg text-white" value="Submit">Continue to Pay</button>
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

                                            // Parse duration (assumes format like '1 year', '6 months', '2 weeks', etc.)
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
                        <p class="text-muted fw-medium mb-3">How would you like to make the payment ?</p>

                        <div class="d-flex flex-wrap gap-3 justify-content-start">
                            <!-- Payment Options -->
                            <?php
                            $payments = ['Bank Transfer', 'Direct Pay', 'PayPal', 'Card'];
                            foreach ($payments as $id => $method): ?>
                            <div class="payment-option-box">
                                <input type="radio" class="form-check-input m-0" name="pay_method" id="pay<?= $id ?>" value="<?= $method ?>">
                                <label for="pay<?= $id ?>" class="mb-0 d-flex align-items-center gap-2 ms-2">
                                    <?= $method ?>
                                    <span class="icon-circle">
                                        <i class="fas fa-chevron-down"></i>
                                    </span>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        </div>
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

<!-- <script>
    document.getElementById('continuePayBtn').addEventListener('click', function () {
        const checkboxes = document.querySelectorAll('.payment-option-box input[type="checkbox"]');
        let selected = null;

        checkboxes.forEach((checkbox, index) => {
            if (checkbox.checked) {
                const method = checkbox.nextElementSibling.textContent.trim().toLowerCase().replace(/\s+/g, '-');
                selected = method;
            }
        });

        if (selected) {
            window.location.href = selected + '.php';
        } else {
            alert("Please select a payment method.");
        }
    });
</script> -->

<!-- Font Awesome for down icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<?php include './partials/layouts/layoutBottom.php' ?>