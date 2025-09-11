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
        // $title = $_POST['title'];
        $subtitle = $_POST['subtitle'];
        $price = $_POST['price'];
        $duration = $_POST['duration'];
        $created_on = $_POST['created_on'];
        $gst = $price * 0.18; // 10% GST
        $total_price = $price + $gst;
        $auto_id = rand(10000000, 99999999);
        $get_addon = $_POST['addon_service'];
        $get_package = $_POST['addon_package'];
        $get_products = $_POST['addon_product'];

    }
    // echo $get_addon;
    // echo $get_package;
    // echo $get_products;

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
                                    <!-- <td class="border-0 text-end" id="currency-symbol-display"><?= htmlspecialchars($symbol) ?><?php echo $total_price; ?></td> -->
                                    <td class="border-0 text-end" id="estimated-total"><?= htmlspecialchars($symbol) ?><?php echo $total_price; ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php
    // Fetch selected add-ons for this package
    $selected_addons = [];
    if (!empty($get_addon)) {
        $addon_ids = explode(",", $get_addon);
        $ids_str = implode(",", array_map('intval', $addon_ids));
        $sql_addons = "SELECT name FROM `add-on-service` WHERE id IN ($ids_str)";
        $result_addons = $conn->query($sql_addons);
        while ($row = $result_addons->fetch_assoc()) {
            $selected_addons[] = $row['name'];
        }
    }

    $selected_packages = [];
    if (!empty($get_package)) {
        $package_ids = explode(",", $get_package);
        $ids_str = implode(",", array_map('intval', $package_ids));
        $sql_packages = "SELECT package_name FROM package WHERE id IN ($ids_str)";
        $result_packages = $conn->query($sql_packages);
        while ($row = $result_packages->fetch_assoc()) {
            $selected_packages[] = $row['package_name'];
        }
    }

    $selected_products = [];
    if (!empty($get_products)) {
        $product_ids = explode(",", $get_products);
        $ids_str = implode(",", array_map('intval', $product_ids));
        $sql_products = "SELECT name FROM products WHERE id IN ($ids_str)";
        $result_products = $conn->query($sql_products);
        while ($row = $result_products->fetch_assoc()) {
            $selected_products[] = $row['name'];
        }
    }
?>

<!-- Add-ons / Packages / Products Section -->
<div class="row gy-4">
    <div class="col-xxl-6 col-sm-6">

        <!-- Common Title -->
        <h6 class="mb-4">Add-Ons for this Plan:</h6>

        <!-- Packages Section -->
        <?php if(!empty($selected_packages)): ?>
        <div class="card mb-4 shadow-sm">
            <div class="card-header bg-light">
                <strong>Packages</strong>
            </div>
            <div class="card-body">
                <?php foreach($package_ids as $pid):
                    $sql = "SELECT package_name, price FROM package WHERE id = $pid";
                    $res = $conn->query($sql);
                    $p = $res->fetch_assoc();
                ?>
                <div class="mb-3 border rounded p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1"><?= htmlspecialchars($p['package_name']) ?></h6>
                            <small>Period: <?= htmlspecialchars($duration) ?></small><br>
                            <small>
                                Validity:
                                <?php
                                    $start_date = new DateTime($created_on);
                                    try {
                                        $interval = DateInterval::createFromDateString($duration);
                                        $end_date = clone $start_date;
                                        $end_date->add($interval);
                                        echo $start_date->format('d-m-Y') . " to " . $end_date->format('d-m-Y');
                                    } catch (Exception $e) {
                                        echo $start_date->format('d-m-Y') . " to (Invalid duration)";
                                    }
                                ?>
                            </small>
                        </div>
                        <div class="text-end">
                            <div class="mb-2"><?= $symbol . $p['price'] ?></div>
                            <button type="button" class="btn btn-sm btn-primary lufera-bg toggle-btn add"
                                    data-id="<?= $pid ?>" data-cost="<?= $p['price'] ?>">+ Add</button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Products Section -->
        <?php if(!empty($selected_products)): ?>
        <div class="card mb-4 shadow-sm">
            <div class="card-header bg-light">
                <strong>Products</strong>
            </div>
            <div class="card-body">
                <?php foreach($product_ids as $pid):
                    $sql = "SELECT name, price FROM products WHERE id = $pid";
                    $res = $conn->query($sql);
                    $p = $res->fetch_assoc();
                ?>
                <div class="mb-3 border rounded p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1"><?= htmlspecialchars($p['name']) ?></h6>
                            <small>Period: <?= htmlspecialchars($duration) ?></small><br>
                            <small>
                                Validity:
                                <?php
                                    $start_date = new DateTime($created_on);
                                    try {
                                        $interval = DateInterval::createFromDateString($duration);
                                        $end_date = clone $start_date;
                                        $end_date->add($interval);
                                        echo $start_date->format('d-m-Y') . " to " . $end_date->format('d-m-Y');
                                    } catch (Exception $e) {
                                        echo $start_date->format('d-m-Y') . " to (Invalid duration)";
                                    }
                                ?>
                            </small>
                        </div>
                        <div class="text-end">
                            <div class="mb-2"><?= $symbol . $p['price'] ?></div>
                            <button type="button" class="btn btn-sm btn-primary lufera-bg toggle-btn add"
                                    data-id="<?= $pid ?>" data-cost="<?= $p['price'] ?>">+ Add</button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Add-ons Section -->
        <?php if(!empty($selected_addons)): ?>
        <div class="card mb-4 shadow-sm">
            <div class="card-header bg-light">
                <strong>Add-on Services</strong>
            </div>
            <div class="card-body">
                <?php foreach($addon_ids as $aid):
                    $sql = "SELECT name, cost FROM `add-on-service` WHERE id = $aid";
                    $res = $conn->query($sql);
                    $a = $res->fetch_assoc();
                ?>
                <div class="mb-3 border rounded p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1"><?= htmlspecialchars($a['name']) ?></h6>
                            <small>Period: <?= htmlspecialchars($duration) ?></small><br>
                            <small>
                                Validity:
                                <?php
                                    $start_date = new DateTime($created_on);
                                    try {
                                        $interval = DateInterval::createFromDateString($duration);
                                        $end_date = clone $start_date;
                                        $end_date->add($interval);
                                        echo $start_date->format('d-m-Y') . " to " . $end_date->format('d-m-Y');
                                    } catch (Exception $e) {
                                        echo $start_date->format('d-m-Y') . " to (Invalid duration)";
                                    }
                                ?>
                            </small>
                        </div>
                        <div class="text-end">
                            <div class="mb-2"><?= $symbol . $a['cost'] ?></div>
                            <button type="button" class="btn btn-sm btn-primary lufera-bg toggle-btn add"
                                    data-id="<?= $aid ?>" data-cost="<?= $a['cost'] ?>">+ Add</button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

    </div>
</div>

<!-- Script -->
<script>
    $(document).ready(function(){
        let basePrice = <?= $price; ?>;   // your plan base price
        let gstRate = 0.18;               // 18% GST
        let selectedItems = {};

        function recalcTotal() {
            let total = basePrice;
            $.each(selectedItems, function(id, cost){
                total += parseFloat(cost);
            });
            let gst = total * gstRate;
            $('#estimated-total').text("<?= $symbol ?>" + (total + gst).toFixed(2));
            $('#gst-display').text("<?= $symbol ?>" + gst.toFixed(2));
            $('input[name="total_price"]').val((total + gst).toFixed(2));
        }

        $(document).on('click', '.toggle-btn', function(){
            let id = $(this).data('id');
            let cost = $(this).data('cost');

            if($(this).hasClass('add')){
                selectedItems[id] = cost;
                $(this).removeClass('btn-primary add').addClass('btn-danger remove').text("Remove");
            } else {
                delete selectedItems[id];
                $(this).removeClass('btn-danger remove').addClass('btn-primary add').text("+ Add");
            }
            recalcTotal();
        });
    });
</script>







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
        <input type="hidden" name="get_addon" id="get_addon_input" value="<?php echo $get_addon; ?>">
        <input type="hidden" name="addon-total" id="addon-total" value="">
        <input type="hidden" name="gst" value="<?php echo $gst; ?>">
 
        
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
    $(document).ready(function(){
        let baseTotal = <?= $total_price; ?>;
        
        function recalcTotal() {
            let addonTotal = 0;
            $('.addon-checkbox:checked').each(function(){
                addonTotal += parseFloat($(this).data('cost'));
            });
            $('#addon-total').val(addonTotal);
            let newTotal = baseTotal + addonTotal;
            $('#estimated-total').text("<?= htmlspecialchars($symbol) ?>" + newTotal.toFixed(2));
            $('input[name="total_price"]').val(newTotal); // update hidden field in form
        }

        $('.addon-checkbox').on('change', recalcTotal);
    });

    function updateAddonField() {
        let selectedAddons = [];
        $('.addon-checkbox:checked').each(function() {
            selectedAddons.push($(this).val());
        });
        $('#get_addon_input').val(selectedAddons.join(',')); // update hidden field
    }
    
    // Trigger when checkbox changes
    $('.addon-checkbox').on('change', function() {
        //recalcTotal();
        updateAddonField();
    });
    
    // Also update before form submission (safety check)
    $('form').on('submit', function() {
        updateAddonField();
    });

    $('#updateForm').submit(function(e) {
    e.preventDefault();

    $.ajax({
        url: 'update.php',
        type: 'POST',
        data: $(this).serialize(),
        success: function(response) {
            $('#result').html(response);
        },
        error: function(xhr) {
            $('#result').html("Error updating data.");
        }
    });
});
</script>
<?php include './partials/layouts/layoutBottom.php' ?>