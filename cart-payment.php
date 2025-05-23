<?php include './partials/layouts/layoutTop.php' ?>

<style>
    .tagline{
        border-bottom:1px solid #fec700;
    }
    .plan-details-table tbody tr td{
        padding: 15px .5rem;
        border-bottom: 1px solid #dadada;
        width: 50%;
    }
    .ad-box{
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
        min-width: 200px;
        cursor: pointer;
    }
    .payment-option-box input[type="checkbox"] {
        margin: 0;
    }
    .icon-circle {
        background-color: #fec700; /* Bootstrap blue */
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
        background-color: #fec700;   /* Yellow background */
        color: black;                /* Black text */
        border: 1px solid black;     /* Black border */
        box-shadow: none;            /* No rounded shadow */
        border-radius: 0;            /* Square corners */
        padding: 8px 20px;
        font-weight: 600;
    }
    .custom-pay-btn:hover {
        background-color: #ffd700;   /* Slightly lighter on hover */
    }

</style>

<div class="dashboard-main-body">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
        <h6 class="fw-semibold mb-0">Your Cart</h6>
        <button id="continuePayBtn" class="btn custom-pay-btn">Continue to Pay</button>
    </div>


<?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $plan_name = $_POST['plan_name'];
        $price = $_POST['price'];
        $duration = $_POST['duration'];
        $created_on = $_POST['created_on'];
        $gst = $price * 0.18; // 10% GST
        $total_price = $price + $gst;
        $auto_id = rand(10000000, 99999999);
    }
?>
    <div class="mb-40">
        <div class="row gy-4">
            <div class="col-xxl-6 col-sm-6">
                <div class="card h-100 radius-12">
                <div class="card-header py-10 border-none" style="box-shadow: 0px 3px 3px 0px lightgray">
                    <!-- <h6 class="mb-0"><?php echo $plan_name; ?></h6>
                    <p class="mb-0">Perfect plan to get started for your own Website</p> -->
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="mb-0"><?php echo $plan_name; ?></h6>
                        <span class="text-muted small">Receipt ID: <?php echo $auto_id; ?></span>
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
            <div class="col-xxl-6 col-sm-6">
                <div class="card h-100 radius-12">
                    <!-- <div class="card-header py-10 border-none" style="box-shadow: 0px 3px 3px 0px lightgray">
                        <h6 class="mb-0"><?php echo $plan_name; ?></h6>
                        <p class="mb-0">Perfect plan to get started for your own Website</p>
                    </div> -->
                    <div class="card-header py-10 border-none d-flex justify-content-between" style="box-shadow: 0px 3px 3px 0px lightgray">
                        <div class="">
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

        <div class="row mt-20">
            <div class="col-12">
                <div class="card h-100 radius-12">
                    <div class="card-header py-10 border-none" style="box-shadow: 0px 3px 3px 0px lightgray">
                        <h6 class="mb-0">Select payment mode</h6>
                        <p class="mb-0 text-muted">Order summary includes discounts & taxes</p>
                    </div>
                    <div class="card-body p-16">
                        <p class="text-muted fw-medium mb-3" style="font-size: 14px;">How would you do like a payment?</p>

                        <div class="d-flex flex-wrap gap-3 justify-content-start">
                            <!-- Bank Transfer -->
                            <div class="payment-option-box d-flex align-items-center gap-2">
                                <input type="checkbox" class="form-check-input m-0" id="bankTransfer">
                                <label for="bankTransfer" class="mb-0 d-flex align-items-center gap-2">
                                    Bank Transfer 
                                    <span class="icon-circle">
                                        <i class="fas fa-chevron-down"></i>
                                    </span>
                                </label>
                            </div>

                            <!-- Direct Pay -->
                            <div class="payment-option-box d-flex align-items-center gap-2">
                                <input type="checkbox" class="form-check-input m-0" id="directPay">
                                <label for="directPay" class="mb-0 d-flex align-items-center gap-2">
                                    Direct Pay 
                                    <span class="icon-circle">
                                        <i class="fas fa-chevron-down"></i>
                                    </span>
                                </label>
                            </div>

                            <!-- PayPal -->
                            <div class="payment-option-box d-flex align-items-center gap-2">
                                <input type="checkbox" class="form-check-input m-0" id="paypal">
                                <label for="paypal" class="mb-0 d-flex align-items-center gap-2">
                                    PayPal 
                                    <span class="icon-circle">
                                        <i class="fas fa-chevron-down"></i>
                                    </span>
                                </label>
                            </div>

                            <!-- Card -->
                            <div class="payment-option-box d-flex align-items-center gap-2">
                                <input type="checkbox" class="form-check-input m-0" id="card">
                                <label for="card" class="mb-0 d-flex align-items-center gap-2">
                                    Card 
                                    <span class="icon-circle">
                                        <i class="fas fa-chevron-down"></i>
                                    </span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
    </div>  
</div>

<script>
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

<!-- Font Awesome CDN for icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<?php include './partials/layouts/layoutBottom.php' ?>