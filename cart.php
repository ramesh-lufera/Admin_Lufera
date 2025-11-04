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
        $get_addon = $_POST['addon_service'];
        $get_packages = $_POST['addon_package'];
        $get_products = $_POST['addon_product'];
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
        <a class="cursor-pointer fw-bold" onclick="history.back()"><span class="fa fa-arrow-left"></span>&nbsp; Back</a> 
        <h6 class="fw-semibold mb-0"><?php echo $plan_name; ?></h6>
        <a class="cursor-pointer fw-bold visibility-hidden" onclick="history.back()"><span class="fa fa-arrow-left"></span>&nbsp; Back</a> 
    </div>
    
    <div class="mb-40">
        <div class="row gy-4">
            <div class="col-xxl-6 col-sm-6">
                <div class="card h-100 radius-12">
                <div class="card-header py-10 border-none" style="box-shadow: 0px 3px 3px 0px lightgray">
                    <h6 class="mb-0"><?php echo $title; ?></h6>
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
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-xxl-6 col-sm-6">
                <div class="card h-100 radius-12">
                    <div class="card-header py-10 border-none d-flex justify-content-between" style="box-shadow: 0px 3px 3px 0px lightgray">
                        <div class="">
                            <h6 class="mb-0">Subtotal</h6>
                            <!-- <p class="mb-0">Sub total does not include applicable taxes</p> -->
                        </div>
                        <div class="align-content-center">
                        <h6 class="mb-0 subtotal-display"><?= htmlspecialchars($symbol) ?><?php echo number_format($price, 2); ?></h6>
                        </div>
                    </div>
                    <div class="card-body p-16">
                        <table class="plan-details-table mb-0 w-100">
                            <tbody>
                                <tr>
                                    <td><?php echo $title; ?></td>
                                    <td class="text-end"><?= htmlspecialchars($symbol) ?><?php echo number_format($price, 2); ?></td>
                                </tr>
                            </tbody>
                            <tbody id="selected-items-summary">
                                <!-- Selected items will be injected here -->
                            </tbody>
                            <tbody>
                                <!-- <tr>
                                    <td>Discount</td>
                                    <td class="text-end">N/A</td>
                                </tr> -->
                                <tr>
                                    <td>Tax (GST 18%)</td>
                                    <td class="text-end gst-display"><?= htmlspecialchars($symbol) ?><?php echo number_format($gst, 2); ?></td>
                                </tr>
                                <tr>
                                    <td class="border-0">Estimated Total</td>
                                    <td class="border-0 text-end fw-semibold" id="estimated-total"><?= htmlspecialchars($symbol) ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php
        // Fetch selected items
        $selected_addons = [];
        if (!empty($get_addon)) {
            $addon_ids = explode(",", $get_addon);
            $ids_str = implode(",", array_map('intval', $addon_ids));
            $sql_addons = "SELECT id, name, cost FROM `add-on-service` WHERE id IN ($ids_str)";
            $result_addons = $conn->query($sql_addons);
            while ($row = $result_addons->fetch_assoc()) {
                $selected_addons[] = $row;
            }
        }

        $selected_packages = [];
        if (!empty($get_packages)) {
            $package_ids = explode(",", $get_packages);
            $ids_str = implode(",", array_map('intval', $package_ids));
            // Get package name from package table and price/duration from durations table (first matching row)
            $sql_packages = "
                SELECT 
                    p.id, 
                    p.package_name,
                    (SELECT d.price FROM durations d WHERE d.package_id = p.id ORDER BY d.id ASC LIMIT 1) AS price,
                    (SELECT d.duration FROM durations d WHERE d.package_id = p.id ORDER BY d.id ASC LIMIT 1) AS duration,
                    p.created_at
                FROM package p
                WHERE p.id IN ($ids_str)
            ";
            $result_packages = $conn->query($sql_packages);
            while ($row = $result_packages->fetch_assoc()) {
                $selected_packages[] = $row;
            }
        }

        $selected_products = [];
        if (!empty($get_products)) {
            $product_ids = explode(",", $get_products);
            $ids_str = implode(",", array_map('intval', $product_ids));
            $sql_products = "SELECT id, name, price FROM products WHERE id IN ($ids_str)";
            $result_products = $conn->query($sql_products);
            while ($row = $result_products->fetch_assoc()) {
                $selected_products[] = $row;
            }
        }
    ?>

    <!-- For Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Add-ons / Packages / Products Section -->
    <?php if(!empty($selected_packages) || !empty($selected_products) || !empty($selected_addons)): ?>
    <div class="row gy-4">
        <div class="col-xxl-6 col-sm-6">

            <!-- Common Title -->
            <h6 class="mb-3">Add-Ons for this Plan:</h6>

            <!-- Packages Section -->
            <?php if(!empty($selected_packages)): ?>
            <div class="card mb-4 shadow-sm border-0">
                <div class="card-header bg-white border-bottom fw-bold fs-5">
                    <i class="bi bi-box-seam me-2 text-secondary"></i> Packages
                </div>
                <div class="card-body">
                    <?php foreach($package_ids as $pid):
                        $pid = (int)$pid; // sanitize
                        $sql = "
                            SELECT 
                                p.package_name,
                                COALESCE(d.price, 0) AS price,
                                COALESCE(d.duration, '') AS duration,
                                p.created_at
                            FROM package p
                            LEFT JOIN (
                                SELECT package_id, price, duration
                                FROM durations
                                WHERE package_id = $pid
                                ORDER BY id ASC
                                LIMIT 1
                            ) d ON d.package_id = p.id
                            WHERE p.id = $pid
                            LIMIT 1
                        ";
                        $res = $conn->query($sql);
                        $p = $res->fetch_assoc();
                    ?>
                    <div class="mb-3 border rounded p-3">
                        <h6 class="mb-1"><?= htmlspecialchars($p['package_name']) ?></h6>
                        <small class="d-block">Period: <?= htmlspecialchars($p['duration']) ?></small>
                        <small class="d-block">
                            Validity:
                            <?php
                                $start_date = new DateTime($p['created_at']);
                                try {
                                    $interval = DateInterval::createFromDateString($p['duration']);
                                    $end_date = clone $start_date;
                                    $end_date->add($interval);
                                    echo $start_date->format('d-m-Y') . " to " . $end_date->format('d-m-Y');
                                } catch (Exception $e) {
                                    echo $start_date->format('d-m-Y') . " to (Invalid duration)";
                                }
                            ?>
                        </small>
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <div class="fw-bold text-dark">Price: <?= $symbol . $p['price'] ?></div>
                            <button type="button" 
                                    class="btn btn-sm fw-bold toggle-btn add"
                                    style="background-color:#fec700; border:none;"
                                    data-id="<?= $pid ?>" 
                                    data-cost="<?= $p['price'] ?>"
                                    data-name="<?= htmlspecialchars($p['package_name']) ?>">+ Add</button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Products Section -->
            <?php if(!empty($selected_products)): ?>
            <div class="card mb-4 shadow-sm border-0">
                <div class="card-header bg-white border-bottom fw-bold fs-5">
                    <i class="bi bi-cart-check me-2 text-secondary"></i> Products
                </div>
                <div class="card-body">
                    <?php foreach($product_ids as $pid):
                        $sql = "SELECT name, price, duration, created_at FROM products WHERE id = $pid";
                        $res = $conn->query($sql);
                        $p = $res->fetch_assoc();
                    ?>
                    <div class="mb-3 border rounded p-3">
                        <h6 class="mb-1"><?= htmlspecialchars($p['name']) ?></h6>
                        <small class="d-block">Period: <?= htmlspecialchars($p['duration']) ?></small>
                        <small class="d-block">
                            Validity:
                            <?php
                                $start_date = new DateTime($p['created_at']);
                                try {
                                    $interval = DateInterval::createFromDateString($p['duration']);
                                    $end_date = clone $start_date;
                                    $end_date->add($interval);
                                    echo $start_date->format('d-m-Y') . " to " . $end_date->format('d-m-Y');
                                } catch (Exception $e) {
                                    echo $start_date->format('d-m-Y') . " to (Invalid duration)";
                                }
                            ?>
                        </small>
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <div class="fw-bold text-dark">Price: <?= $symbol . $p['price'] ?></div>
                            <button type="button" 
                                    class="btn btn-sm fw-bold toggle-btn add"
                                    style="background-color:#fec700; border:none;"
                                    data-id="<?= $pid ?>" 
                                    data-cost="<?= $p['price'] ?>"
                                    data-name="<?= htmlspecialchars($p['name']) ?>">+ Add</button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Add-ons Section -->
            <?php if(!empty($selected_addons)): ?>
            <div class="card mb-4 shadow-sm border-0">
                <div class="card-header bg-white border-bottom fw-bold fs-5">
                    <i class="bi bi-plus-circle me-2 text-secondary"></i> Add-on Services
                </div>
                <div class="card-body">
                    <?php foreach($addon_ids as $aid):
                        $sql = "SELECT name, cost, duration, created_at FROM `add-on-service` WHERE id = $aid";
                        $res = $conn->query($sql);
                        $a = $res->fetch_assoc();
                    ?>
                    <div class="mb-3 border rounded p-3">
                        <h6 class="mb-1"><?= htmlspecialchars($a['name']) ?></h6>
                        <small class="d-block">Period: <?= htmlspecialchars($a['duration']) ?></small>
                        <small class="d-block">
                            Validity:
                            <?php
                                $start_date = new DateTime($a['created_at']);
                                try {
                                    $interval = DateInterval::createFromDateString($a['duration']);
                                    $end_date = clone $start_date;
                                    $end_date->add($interval);
                                    echo $start_date->format('d-m-Y') . " to " . $end_date->format('d-m-Y');
                                } catch (Exception $e) {
                                    echo $start_date->format('d-m-Y') . " to (Invalid duration)";
                                }
                            ?>
                        </small>
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <div class="fw-bold text-dark">Price: <?= $symbol . $a['cost'] ?></div>
                            <button type="button" 
                                    class="btn btn-sm fw-bold toggle-btn add"
                                    style="background-color:#fec700; border:none;"
                                    data-id="<?= $aid ?>" 
                                    data-cost="<?= $a['cost'] ?>"
                                    data-name="<?= htmlspecialchars($a['name']) ?>">+ Add</button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </div>
    <?php endif; ?>

    <script>
        $(document).ready(function(){
            let basePrice = <?= $price; ?>;
            let gstRate = 0.18;
            let selectedItems = {};

            function recalcTotal() {
                let subtotal = basePrice;
                $.each(selectedItems, function(id, item){
                    subtotal += parseFloat(item.cost);
                });

                // Update subtotal display
                $('.subtotal-display').text("<?= $symbol ?>" + subtotal.toFixed(2));
                $('.subtotal-display-hidden').val(subtotal.toFixed(2));


                // Calculate GST and total
                let gst = subtotal * gstRate;
                let estimatedTotal = subtotal + gst;

                // Update GST and total displays
                $('.gst-display').text("<?= $symbol ?>" + gst.toFixed(2));
                $('#estimated-total').text("<?= $symbol ?>" + estimatedTotal.toFixed(2));

                // Update hidden total for form submission
                $('input[name="total_price"]').val(estimatedTotal.toFixed(2));
            }


            function updateSelectedSummary() {
                const $summary = $('#selected-items-summary');
                $summary.empty(); // clear first

                // loop through selectedItems
                $.each(selectedItems, function(id, item){
                    $summary.append(`
                        <tr class="selected-item-row" data-id="${id}">
                            <td>${item.name} (${item.type})</td>
                            <td class="text-end"><?= $symbol ?>${parseFloat(item.cost).toFixed(2)}</td>
                        </tr>
                    `);
                });
            }


            $(document).on('click', '.toggle-btn', function(){
                let id = $(this).data('id');
                let cost = parseFloat($(this).data('cost'));
                let name = $(this).data('name');

                // detect type from card header text
                let headerTxt = ($(this).closest('.card').find('.card-header').text() || '').toLowerCase();
                let type = 'Service';
                if (headerTxt.includes('package')) type = 'Add-on Package';
                else if (headerTxt.includes('product')) type = 'Add-on Product';
                else if (headerTxt.includes('add-on') || headerTxt.includes('addon')) type = 'Add-on Service';

                if($(this).hasClass('add')){
                    // Add item
                    selectedItems[id] = {name: name, cost: cost, type: type};
                    $(this)
                        .removeClass('add')
                        .css({"background-color":"#dc3545","color":"#fff"})
                        .text("Remove");
                    Swal.fire({icon:"success", title: name + " added", timer:800, showConfirmButton:false});
                } else {
                    // Remove item
                    delete selectedItems[id];
                    $(this)
                        .addClass('add')
                        .css({"background-color":"#fec700","color":"#000"})
                        .text("+ Add");
                    Swal.fire({icon:"warning", title: name + " removed", timer:800, showConfirmButton:false});
                }

                updateSelectedSummary();
                recalcTotal();
            });

        });
    </script>

    <?php
        $user_id = $_SESSION['user_id'];
        $sql = "SELECT * FROM `users` WHERE id = $user_id";
        $result = $conn->query($sql);
        $row = $result->fetch_assoc();

        //if($row["username"] && $row["email"] && $row["phone"] && $row["first_name"] && $row["last_name"] && $row["business_name"] && $row["address"] && $row["city"] && $row["state"] && $row["country"] && $row["pincode"] != ""){
            $profileComplete = (
                $row["username"] && $row["email"] && $row["phone"] && 
                $row["first_name"] && $row["last_name"] && $row["business_name"] && 
                $row["address"] && $row["city"] && $row["state"] && 
                $row["country"] && $row["pincode"]
            );
    ?>
    <?php if ($profileComplete) { ?>
    <form action="cart-payment.php" method="POST" style="display:block;" id="checkoutForm">
        <input type="hidden" name="type" value="<?php echo $type; ?>">    
        <input type="hidden" name="id" value="<?php echo $id; ?>">
        <input type="hidden" name="plan_name" value="<?php echo $plan_name; ?>">
        <input type="hidden" name="price" value="<?php echo $price; ?>">
        <input type="hidden" name="duration" value="<?php echo $duration; ?>">
        <input type="hidden" name="total_price" value="<?php echo $total_price; ?>">
        <input type="hidden" name="receipt_id" value="<?php echo $auto_id; ?>">
        <input type="hidden" name="created_on" value="<?php echo $created_on; ?>">
        <input type="hidden" name="get_addon" id="get_addon_input" value="">
        <input type="hidden" name="addon-total" id="addon-total" value="">
        <input type="hidden" class="gst-hidden" name="gst" value="<?php echo $gst; ?>">
        <input type="hidden" class="subtotal-display-hidden" name="subtotal-display" value="<?php echo $price; ?>">
        <button type="submit" class="lufera-bg text-center btn-sm px-12 py-10 float-end" style="width:150px; border: 1px solid #000">Continue</button>   
    </form>

    <?php } else { ?>
    <form action="cart-payment.php" method="POST" style="display:none;" id="checkoutForm">
        <input type="hidden" name="type" value="<?php echo $type; ?>">    
        <input type="hidden" name="id" value="<?php echo $id; ?>">
        <input type="hidden" name="plan_name" value="<?php echo $plan_name; ?>">
        <input type="hidden" name="price" value="<?php echo $price; ?>">
        <input type="hidden" name="duration" value="<?php echo $duration; ?>">
        <input type="hidden" name="total_price" value="<?php echo $total_price; ?>">
        <input type="hidden" name="receipt_id" value="<?php echo $auto_id; ?>">
        <input type="hidden" name="created_on" value="<?php echo $created_on; ?>">
        <input type="hidden" name="get_addon" id="get_addon_input" value="">
        <input type="hidden" name="addon-total" id="addon-total" value="">
        <input type="hidden" class="gst-hidden" name="gst" value="<?php echo $gst; ?>">
        <input type="hidden" class="subtotal-display-hidden" name="subtotal-display" value="<?php echo $price; ?>">
        <button type="submit" class="lufera-bg text-center btn-sm px-12 py-10 float-end" 
            style="width:150px; border: 1px solid #000">Continue</button>
    </form>
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
                <button type="submit" class="btn lufera-bg text-white" id="saveBtn">Save</button>
            </div>
            </form>
            <div id="result"></div>
            </div>
        </div>
    </div>
            
</div>

<script>
    $(function(){
        if (typeof $ === 'undefined') {
            console.error('jQuery not found — this script requires jQuery.');
            return;
        }
    
        const basePrice = Number(<?= json_encode(floatval($price)) ?>) || 0;
        const gstRate = 0.18;
        const symbol = <?= json_encode($symbol) ?>;
    
        // state
        let selectedItems = {};         // id -> cost (for total calc)
        let selectedAddons = [];        // array of addon ids (strings)
        let selectedPackages = [];      // array of package ids
        let selectedProducts = [];      // array of product ids
    
        // find checkout form to append hidden inputs into
        const $checkoutForm = $('form[action="cart-payment.php"]').first();
    
        function ensureHidden(name, id) {
            if ($checkoutForm.length) {
                if ($checkoutForm.find('#' + id).length === 0) {
                    $('<input>').attr({type: 'hidden', name: name, id: id}).appendTo($checkoutForm);
                }
            } else {
                // fallback: append to body (won't be submitted unless inside form)
                if ($('#' + id).length === 0) {
                    $('<input>').attr({type: 'hidden', name: name, id: id}).appendTo('body');
                }
            }
        }
    
        ensureHidden('get_addon', 'get_addon_input');
        ensureHidden('get_packages', 'get_packages_input');
        ensureHidden('get_products', 'get_products_input');
        ensureHidden('addon-total', 'addon-total');
    
        // function updateHiddenInputs() {
        //     $('#get_addon_input').val(selectedAddons.join(','));
        //     $('#get_packages_input').val(selectedPackages.join(','));
        //     $('#get_products_input').val(selectedProducts.join(','));
        //     // total cost of selected add-ons/packages/products (without GST)
        //     $('#addon-total').val(Object.values(selectedItems).reduce((s,v)=>s+Number(v||0),0).toFixed(2));
        // }

        function updateHiddenInputs() {
            $('#get_addon_input').val(selectedAddons.join(','));
            $('#get_packages_input').val(selectedPackages.join(','));
            $('#get_products_input').val(selectedProducts.join(','));

            // Sum addon costs
            let addonSum = 0;
            selectedAddons.forEach(aid => { addonSum += Number(selectedItems[aid] || 0); });
            $('#addon-total').val(addonSum.toFixed(2));

            // Sum package costs
            let packageSum = 0;
            selectedPackages.forEach(pid => { packageSum += Number(selectedItems[pid] || 0); });
            $('#package-total').val(packageSum.toFixed(2));

            // Sum product costs
            let productSum = 0;
            selectedProducts.forEach(pid => { productSum += Number(selectedItems[pid] || 0); });
            $('#product-total').val(productSum.toFixed(2));
        }
    
        function recalcTotal() {
            const itemsTotal = Object.values(selectedItems).reduce((s,v)=>s+Number(v||0),0);
            const subtotal = basePrice + itemsTotal;
            const gst = subtotal * gstRate;
            const estimated = subtotal + gst;
    
            $('#estimated-total').text(symbol + estimated.toFixed(2));
            if ($('.gst-display').length) $('.gst-display').text(symbol + gst.toFixed(2));
            if ($('.gst-hidden').length) $('.gst-hidden').val(gst.toFixed(2));
            // update hidden total_price if present in form
            $('input[name="total_price"]').val(estimated.toFixed(2));
        }
    
        // click handler for all toggle buttons (delegated)
        $(document).on('click', '.toggle-btn', function(e){
            e.preventDefault();
            const $btn = $(this);
            const id = String($btn.data('id'));
            const cost = Number($btn.data('cost')) || 0;
    
            // detect type by nearest .card .card-header text
            const headerTxt = ($btn.closest('.card').find('.card-header').text() || '').toLowerCase();
            let type = 'addon';
            if (headerTxt.includes('package')) type = 'package';
            else if (headerTxt.includes('product')) type = 'product';
            else if (headerTxt.includes('add-on') || headerTxt.includes('add on') || headerTxt.includes('addon')) type = 'addon';
    
            const isSelected = $btn.hasClass('selected');
    
            if (!isSelected) {
                // select
                $btn.addClass('selected btn-danger').removeClass('btn-primary add').text('Remove');
                selectedItems[id] = cost;
                if (type === 'addon' && !selectedAddons.includes(id)) selectedAddons.push(id);
                if (type === 'package' && !selectedPackages.includes(id)) selectedPackages.push(id);
                if (type === 'product' && !selectedProducts.includes(id)) selectedProducts.push(id);
            } else {
                // deselect
                $btn.removeClass('selected btn-danger').addClass('btn-primary add').text('+ Add');
                delete selectedItems[id];
                if (type === 'addon') selectedAddons = selectedAddons.filter(x => x !== id);
                if (type === 'package') selectedPackages = selectedPackages.filter(x => x !== id);
                if (type === 'product') selectedProducts = selectedProducts.filter(x => x !== id);
            }
    
            updateHiddenInputs();
            recalcTotal();
        });
    
        // If server-side preselected ids exist in the hidden input on page load, trigger selection
        const preAddons = $('#get_addon_input').val() || '';
        if (preAddons) {
            preAddons.split(',').map(s => s.trim()).filter(Boolean).forEach(function(aid){
                // find matching button and simulate click if not already selected
                const $b = $('.toggle-btn').filter(function(){ return String($(this).data('id')) === String(aid); }).first();
                if ($b.length && !$b.hasClass('selected')) {
                    $b.trigger('click');
                }
            });
        }
    
        // initial recalc to set totals correctly
        recalcTotal();
    });
</script>
 
<script>
    $('#updateForm').submit(function(e) {
    e.preventDefault();

    $.ajax({
        url: 'update.php',
        type: 'POST',
        data: new FormData(this),
        processData: false,
        contentType: false,
        success: function(response) {
            if(response.trim() === "updated"){
                // Close modal
                $('#exampleModal').modal('hide');
                
                $('form#checkoutForm').show();    // show the hidden Continue form
                $('button[data-bs-target="#exampleModal"]').hide(); // hide update button

                

                // Optional: reload hidden inputs calculation
                updateHiddenInputs();

                // Optional success message
                Swal.fire({
                    icon: "success",
                    title: "Profile updated successfully",
                    timer: 1200,
                    showConfirmButton: false
                });
            } else {
                $('#result').html(response);
            }
        },
        error: function(xhr) {
            $('#result').html("Error updating data.");
        }
    });
});

</script>


<?php include './partials/layouts/layoutBottom.php' ?>