<?php include './partials/layouts/layoutTop.php' ?>

<style>
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
</style>

<?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $type = $_POST['type'];
        $id = $_POST['id'];
        $plan_name = $_POST['plan_name'];
        $title = $_POST['title'];
        $subtitle = $_POST['subtitle'];
        $price = $_POST['price'];
        $duration = $_POST['duration'];
        $created_on = $_POST['created_on'];
        $gst = $price * 0.18; // 10% GST
        $total_price = $price + $gst;
        $auto_id = rand(10000000, 99999999);
    }

    // Get active symbol
    $result = $conn->query("SELECT symbol FROM currencies WHERE is_active = 1 LIMIT 1");
    $symbol = "$"; // default
    if ($row = $result->fetch_assoc()) {
        $symbol = $row['symbol'];
    }
?>

<div class="dashboard-main-body">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
        <h6 class="fw-semibold mb-0"><?php echo $plan_name; ?></h6>
    </div>

    <div class="mb-40">
        <div class="row gy-4">
            <div class="col-xxl-6 col-sm-6">
                <div class="card h-100 radius-12">
                <div class="card-header py-10 border-none" style="box-shadow: 0px 3px 3px 0px lightgray">
                    <h6 class="mb-0"><?php echo $title; ?></h6>
                    <p class="mb-0"><?php echo $subtitle; ?></p>
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
                                <!-- <tr>
                                    <td class="border-0" colspan="2" id="currency-symbol-display">Renews at <?= htmlspecialchars($symbol) ?>1500/year for 3 Years
                                        <p class="text-sm ad-box">Great news! Your FREE domain + 3 months FREE are included with this order</p>
                                    </td>
                                </tr> -->
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
                            <h4 class="mb-0" id="currency-symbol-display"><?= htmlspecialchars($symbol) ?><?php echo $price; ?></h4>
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
                                    <td class="text-end" id="currency-symbol-display"><?= htmlspecialchars($symbol) ?><?php echo $gst; ?></td>
                                </tr>
                                <tr>
                                    <td class="border-0">Estimated Total</td>
                                    <td class="border-0 text-end" id="currency-symbol-display"><?= htmlspecialchars($symbol) ?><?php echo $total_price; ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
        $user_id = $_SESSION['user_id'];
        $sql = "SELECT * FROM `users` WHERE id = $user_id";
        $result = $conn->query($sql);
        $row = $result->fetch_assoc();

        if($row["username"] && $row["email"] && $row["phone"] && $row["first_name"] && $row["last_name"] && $row["business_name"] && $row["address"] && $row["city"] && $row["state"] && $row["country"] && $row["pincode"] != ""){
    ?>
    <form action="cart-payment.php" method="POST">
        <input type="hidden" name="type" value="<?php echo $type; ?>">    
        <input type="hidden" name="id" value="<?php echo $id; ?>">
        <input type="hidden" name="plan_name" value="<?php echo $plan_name; ?>">
        <input type="hidden" name="price" value="<?php echo $price; ?>">
        <input type="hidden" name="duration" value="<?php echo $duration; ?>">
        <input type="hidden" name="total_price" value="<?php echo $total_price; ?>">
        <input type="hidden" name="receipt_id" value="<?php echo $auto_id; ?>">
        <input type="hidden" name="created_on" value="<?php echo $created_on; ?>">
        
        <button type="submit" class="lufera-bg text-center btn-sm px-12 py-10 float-end" style="width:150px; border: 1px solid #000">Continue</button>
    </form>
    <?php } 
        else{
    ?>
    <button class="lufera-bg text-center btn-sm px-12 py-10 float-end" data-bs-toggle="modal" data-bs-target="#exampleModal" style="width:250px; border: 1px solid #000">Update Profile & Continue</button>
        <?php
    }
    ?>
    <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Update User Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
            <form id="updateForm">
                <div class="row">
                    <div class="col-sm-6">
                        <div class="mb-20">
                            <label for="" class="form-label fw-semibold text-primary-light text-sm mb-8">First Name <span class="text-danger-600">*</span></label>
                            <input type="hidden" name="id" value="<?php echo $_SESSION['user_id']; ?>">
                            <!-- <input type="text" class="form-control radius-8" id="" name="fname" value="<?php echo $row['first_name']; ?>" required> -->
                            <input type="text" class="form-control radius-8" name="fname" value="<?php echo $row['first_name']; ?>" <?php echo !empty($row['first_name']) ? 'readonly' : ''; ?> required>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="mb-20">
                            <label for="" class="form-label fw-semibold text-primary-light text-sm mb-8">Last Name <span class="text-danger-600">*</span></label>
                            <input type="text" class="form-control radius-8" name="lname" value="<?php echo $row['last_name']; ?>" <?php echo !empty($row['last_name']) ? 'readonly' : ''; ?> required>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="mb-20">
                            <label for="" class="form-label fw-semibold text-primary-light text-sm mb-8">Username <span class="text-danger-600">*</span></label>
                            <input type="text" class="form-control radius-8" name="uname" value="<?php echo $row['username']; ?>" <?php echo !empty($row['username']) ? 'readonly' : ''; ?> required>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="mb-20">
                            <label for="" class="form-label fw-semibold text-primary-light text-sm mb-8">Bussiness Name <span class="text-danger-600">*</span></label>
                            <input type="text" class="form-control radius-8" name="bname" value="<?php echo $row['business_name']; ?>" <?php echo !empty($row['business_name']) ? 'readonly' : ''; ?> required>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="mb-20">
                            <label for="" class="form-label fw-semibold text-primary-light text-sm mb-8">Email <span class="text-danger-600">*</span></label>
                            <input type="text" class="form-control radius-8" name="email" value="<?php echo $row['email']; ?>" <?php echo !empty($row['email']) ? 'readonly' : ''; ?> required>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="mb-20">
                            <label for="" class="form-label fw-semibold text-primary-light text-sm mb-8">Phone <span class="text-danger-600">*</span></label>
                            <input type="text" class="form-control radius-8" name="phone" value="<?php echo $row['phone']; ?>" <?php echo !empty($row['phone']) ? 'readonly' : ''; ?> required>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="mb-20">
                            <label for="" class="form-label fw-semibold text-primary-light text-sm mb-8">Date of Birth <span class="text-danger-600">*</span></label>
                            <input type="date" class="form-control radius-8" name="dob" value="<?php echo $row['dob']; ?>" <?php echo !empty($row['dob']) ? 'readonly' : ''; ?> required>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="mb-20">
                            <label for="" class="form-label fw-semibold text-primary-light text-sm mb-8">Address <span class="text-danger-600">*</span></label>
                            <input type="text" class="form-control radius-8" name="address" value="<?php echo $row['address']; ?>" <?php echo !empty($row['address']) ? 'readonly' : ''; ?> required>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="mb-20">
                            <label for="" class="form-label fw-semibold text-primary-light text-sm mb-8">City <span class="text-danger-600">*</span></label>
                            <input type="text" class="form-control radius-8" name="city" value="<?php echo $row['city']; ?>" <?php echo !empty($row['city']) ? 'readonly' : ''; ?> required>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="mb-20">
                            <label for="" class="form-label fw-semibold text-primary-light text-sm mb-8">State <span class="text-danger-600">*</span></label>
                            <input type="text" class="form-control radius-8" name="state" value="<?php echo $row['state']; ?>" <?php echo !empty($row['state']) ? 'readonly' : ''; ?> required>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="mb-20">
                            <label for="" class="form-label fw-semibold text-primary-light text-sm mb-8">Country <span class="text-danger-600">*</span></label>
                            <input type="text" class="form-control radius-8" name="country" value="<?php echo $row['country']; ?>" <?php echo !empty($row['country']) ? 'readonly' : ''; ?> required>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="mb-20">
                            <label for="" class="form-label fw-semibold text-primary-light text-sm mb-8">Pin <span class="text-danger-600">*</span></label>
                            <input type="text" class="form-control radius-8" name="pin" value="<?php echo $row['pincode']; ?>" <?php echo !empty($row['pincode']) ? 'readonly' : ''; ?> required>
                        </div>
                    </div>
                </div>
                </div>
            <div class="modal-footer d-flex align-items-center justify-content-center gap-3">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="submit" class="btn lufera-bg text-white">Save</button>
            </div>
            </form>
            <div id="result"></div>
            </div>
        </div>
    </div>
            
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
<?php include './partials/layouts/layoutBottom.php' ?>