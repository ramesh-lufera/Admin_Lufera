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
    .breakdown-section{
        font-size: 0.9rem;
        color: #555;
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

        $gst_rate = 0;
        if ($type === 'product') {
            $gst_id = isset($_POST['gst']) ? intval($_POST['gst']) : 0;
        } else {
            $gst_id = isset($_POST['gst_id']) ? intval($_POST['gst_id']) : 0;
        }
        if ($gst_id > 0) {
            $gst_query = $conn->prepare("SELECT rate, tax_name FROM taxes WHERE id = ?");
            $gst_query->bind_param("i", $gst_id);
            $gst_query->execute();
            $gst_result = $gst_query->get_result();
            if ($gst_row = $gst_result->fetch_assoc()) {
                $gst_rate = floatval($gst_row['rate']);
                $gst_name = $gst_row['tax_name'];
            }
            $gst_query->close();
        } else {
            $gst_name = "GST";
        }
        $gst = $price * ($gst_rate / 100);
        $total_price = $price + $gst;
        $auto_id = rand(10000000, 99999999);
        $get_addon = $_POST['addon_service'] ?? '';
        $get_packages = $_POST['addon_package'] ?? '';
        $get_products = $_POST['addon_product'] ?? '';
    }

    // Get active symbol
    $result = $conn->query("SELECT symbol FROM currencies WHERE is_active = 1 LIMIT 1");
    $symbol = "$";
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
                                            try {
                                                $interval = DateInterval::createFromDateString($duration);
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
                        </div>
                        <div class="align-content-center">
                        <h6 class="mb-0 subtotal-display"><?= htmlspecialchars($symbol) ?><?php echo number_format($price, 2); ?></h6>
                        </div>
                    </div>
                    <div class="card-body p-16">
                        <table class="plan-details-table mb-0 w-100">
                            <tbody>
                                <tr class="breakdown-toggle" style="cursor:pointer;">
                                    <td>
                                        <?php echo $title; ?>
                                        <i class="bi bi-chevron-down ms-2 breakdown-arrow"></i>
                                    </td>
                                    
                                    <td class="text-end"><?= htmlspecialchars($symbol) . number_format($total_price, 2); ?></td>
                                </tr>

                                <tr class="breakdown-row" style="display:none; background:#fafafa;">
                                    <td colspan="2">
                                        <div class="p-2">
                                            <div class="d-flex justify-content-between mb-1 breakdown-section ">
                                                <span>Base Price</span>
                                                <span><?= $symbol . number_format($price, 2); ?></span>
                                            </div>
                                            <div class="d-flex justify-content-between mb-1 breakdown-section">
                                                <span>Tax (<?= number_format($gst_rate,2) ?>%)</span>
                                                <span><?= $symbol . number_format($gst, 2); ?></span>
                                            </div>
                                            <!-- <div class="d-flex justify-content-between fw-bold border-top pt-2 mt-2">
                                                <span>Total:</span>
                                                <span><?= $symbol . number_format($total_price, 2); ?></span>
                                            </div> -->
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                            <tbody id="selected-items-summary"></tbody>
                            <tbody>
                                <!-- <tr>
                                    <td>Tax (<?= htmlspecialchars($gst_name) ?> <?= number_format($gst_rate, 2) ?>%)</td>
                                    <td class="text-end gst-display">
                                        <?= htmlspecialchars($symbol) ?><?= number_format($gst, 2) ?>
                                    </td>
                                </tr> -->
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
        // Fetch selected items (for display only)
        $selected_addons = [];
        if (!empty($get_addon)) {
            $addon_ids = explode(",", $get_addon);
            $ids_str = implode(",", array_map('intval', $addon_ids));
            $sql_addons = "
                SELECT 
                    a.id, 
                    a.name, 
                    a.cost, 
                    a.gst, 
                    COALESCE(t.rate, 0) AS gst_rate,
                    COALESCE(t.tax_name, 'GST') AS gst_name
                FROM `add-on-service` a
                LEFT JOIN taxes t ON t.id = a.gst
                WHERE a.id IN ($ids_str)
            ";
            $result_addons = $conn->query($sql_addons);
            while ($row = $result_addons->fetch_assoc()) {
                $selected_addons[] = $row;
            }

        }

        $selected_packages = [];
        if (!empty($get_packages)) {
            $package_ids = explode(",", $get_packages);
            $ids_str = implode(",", array_map('intval', $package_ids));
            $sql_packages = "
                SELECT 
                    p.id, 
                    p.package_name,
                    p.gst_id,
                    (SELECT d.price FROM durations d WHERE d.package_id = p.id ORDER BY d.id ASC LIMIT 1) AS price,
                    (SELECT d.duration FROM durations d WHERE d.package_id = p.id ORDER BY d.id ASC LIMIT 1) AS duration,
                    p.created_at,
                    COALESCE(t.rate, 0) AS gst_rate,
                    COALESCE(t.tax_name, 'GST') AS gst_name
                FROM package p
                LEFT JOIN taxes t ON t.id = p.gst_id
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
            $sql_products = "
                SELECT 
                    p.id, 
                    p.name,
                    p.price,
                    p.gst,
                    COALESCE(t.rate, 0) AS gst_rate,
                    COALESCE(t.tax_name, 'GST') AS gst_name
                FROM products p
                LEFT JOIN taxes t ON t.id = p.gst
                WHERE p.id IN ($ids_str)
            ";
            $result_products = $conn->query($sql_products);
            while ($row = $result_products->fetch_assoc()) {
                $selected_products[] = $row;
            }
        }

        // Build JS map for package GST rates
        $packageGstMap = [];
        foreach ($selected_packages as $pkg) {
            $packageGstMap[$pkg['id']] = floatval($pkg['gst_rate']);
        }
    ?>

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Add-ons / Packages / Products Section -->
    <?php if(!empty($selected_packages) || !empty($selected_products) || !empty($selected_addons)): ?>
    <div class="row gy-4">
        <div class="col-xxl-6 col-sm-6">
            <h6 class="mb-3">Add-Ons for this Plan:</h6>

            <!-- Packages Section -->
            <?php if(!empty($selected_packages)): ?>
            <div class="card mb-4 shadow-sm border-0">
                <div class="card-header bg-white border-bottom fw-bold fs-5">
                    <i class="bi bi-box-seam me-2 text-secondary"></i> Packages
                </div>
                <div class="card-body">
                    <?php foreach($selected_packages as $pkg):
                        $pid = (int)$pkg['id'];
                        $gst_rate_pkg = floatval($pkg['gst_rate']);
                        $gst_name_pkg = $pkg['gst_name'];
                    ?>
                    <div class="mb-3 border rounded p-3">
                        <h6 class="mb-1"><?= htmlspecialchars($pkg['package_name']) ?></h6>
                        <small class="d-block">Period: <?= htmlspecialchars($pkg['duration']) ?></small>
                        <small class="d-block">
                            Validity:
                            <?php
                                $start_date = new DateTime($pkg['created_at']);
                                try {
                                    $interval = DateInterval::createFromDateString($pkg['duration']);
                                    $end_date = clone $start_date;
                                    $end_date->add($interval);
                                    echo $start_date->format('d-m-Y') . " to " . $end_date->format('d-m-Y');
                                } catch (Exception $e) {
                                    echo $start_date->format('d-m-Y') . " to (Invalid duration)";
                                }
                            ?>
                        </small>
                        <!-- GST Rate under Validity -->
                        <!-- <small class="d-block text-success fw-semibold">
                            <?= htmlspecialchars($gst_name_pkg) ?> (<?= number_format($gst_rate_pkg, 2) ?>%)
                        </small> -->
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <div class="fw-bold text-dark">Price: <?= $symbol . number_format($pkg['price'], 2) ?></div>
                            <button type="button" 
                                    class="btn btn-sm fw-bold toggle-btn add"
                                    style="background-color:#fec700; border:none;"
                                    data-id="<?= $pid ?>" 
                                    data-cost="<?= $pkg['price'] ?>"
                                    data-name="<?= htmlspecialchars($pkg['package_name']) ?>"
                                    data-type="package"
                                    data-gst-rate="<?= $gst_rate_pkg ?>">+ Add</button>
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
                <?php foreach($selected_products as $p):
                    $pid = (int)$p['id'];
                    $gst_rate_prod = floatval($p['gst_rate']);
                    $gst_name_prod = htmlspecialchars($p['gst_name']);
                ?>
                <div class="mb-3 border rounded p-3">
                    <h6 class="mb-1"><?= htmlspecialchars($p['name']) ?></h6>
                    <small class="d-block">Period: N/A</small>
                    <small class="d-block">Validity: One-time</small>
                    <!-- <small class="d-block text-success fw-semibold">
                        <?= $gst_name_prod ?> (<?= number_format($gst_rate_prod, 2) ?>%)
                    </small> -->
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div class="fw-bold text-dark">Price: <?= $symbol . number_format($p['price'], 2) ?></div>
                        <button type="button" 
                                class="btn btn-sm fw-bold toggle-btn add"
                                style="background-color:#fec700; border:none;"
                                data-id="<?= $pid ?>" 
                                data-cost="<?= $p['price'] ?>"
                                data-name="<?= htmlspecialchars($p['name']) ?>"
                                data-type="product"
                                data-gst-rate="<?= $gst_rate_prod ?>">+ Add</button>
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
                <?php foreach($selected_addons as $a):
                    $aid = (int)$a['id'];
                    $gst_rate_addon = floatval($a['gst_rate']);
                    $gst_name_addon = htmlspecialchars($a['gst_name']);
                ?>
                <div class="mb-3 border rounded p-3">
                    <h6 class="mb-1"><?= htmlspecialchars($a['name']) ?></h6>
                    <small class="d-block">Period: N/A</small>
                    <small class="d-block">Validity: One-time</small>
                    <!-- <small class="d-block text-success fw-semibold">
                        <?= $gst_name_addon ?> (<?= number_format($gst_rate_addon, 2) ?>%)
                    </small> -->
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div class="fw-bold text-dark">Price: <?= $symbol . number_format($a['cost'], 2) ?></div>
                        <button type="button" 
                                class="btn btn-sm fw-bold toggle-btn add"
                                style="background-color:#fec700; border:none;"
                                data-id="<?= $aid ?>" 
                                data-cost="<?= $a['cost'] ?>"
                                data-name="<?= htmlspecialchars($a['name']) ?>"
                                data-type="addon"
                                data-gst-rate="<?= $gst_rate_addon ?>">+ Add</button>
                    </div>
                </div>
                <?php endforeach; ?>

                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- JavaScript: Only Add on Click -->
    <script>
        const GST_RATE_GLOBAL = <?= $gst_rate / 100 ?>;
        const PACKAGE_GST_MAP = <?= json_encode($packageGstMap) ?>;
        const symbol = <?= json_encode($symbol) ?>;
        const basePrice = <?= $price ?>;

        let selectedItems = {};
        let selectedAddons = [], selectedPackages = [], selectedProducts = [];

        const $checkoutForm = $('form[action="cart-payment.php"]').first();

        // Ensure hidden inputs exist
        function ensureHidden(name, id, value = '') {
            if ($checkoutForm.find('#' + id).length === 0) {
                $('<input>').attr({type: 'hidden', name: name, id: id, value: value}).appendTo($checkoutForm);
            } else {
                $checkoutForm.find('#' + id).val(value);
            }
        }

        // Initialize empty
        ensureHidden('get_addon', 'get_addon_input', '');
        ensureHidden('get_packages', 'get_packages_input', '');
        ensureHidden('get_products', 'get_products_input', '');
        ensureHidden('addon-total', 'addon-total', '0');

        function updateHiddenInputs() {
            $('#get_addon_input').val(selectedAddons.join(','));
            $('#get_packages_input').val(selectedPackages.join(','));
            $('#get_products_input').val(selectedProducts.join(','));
            const addonSum = selectedAddons.reduce((s,id) => s + (selectedItems[id]?.cost || 0), 0);
            $('#addon-total').val(addonSum.toFixed(2));
        }

        function recalcTotal() {
            const itemsTotal = Object.values(selectedItems).reduce((s,i) => s + (i?.cost || 0), 0);
            const subtotal = basePrice + itemsTotal;

            let gst = basePrice * GST_RATE_GLOBAL;
            $.each(selectedItems, function(id, itm) {
                const rate = itm.gstRate ?? GST_RATE_GLOBAL;
                gst += itm.cost * rate;
            });

            const estimated = subtotal + gst;

            $('.subtotal-display').text(symbol + subtotal.toFixed(2));
            $('.gst-display').text(symbol + gst.toFixed(2));
            //$('#estimated-total').text(symbol + estimated.toFixed(2));
            $('#estimated-total').text(symbol + estimated.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
            $('input[name="total_price"]').val(estimated.toFixed(2));
            $('.gst-hidden').val(gst.toFixed(2));
            $('.subtotal-display-hidden').val(subtotal.toFixed(2));
        }

        function updateSelectedSummary() {
            const $summary = $('#selected-items-summary');
            $summary.empty();
            $.each(selectedItems, function(id, itm) {
                const base = itm.cost;
                const rate = itm.gstRate ?? GST_RATE_GLOBAL;
                const gstAmt = base * rate;
                const total = base + gstAmt;

                $summary.append(`
                    <tr class="addon-toggle" data-id="${id}" style="cursor:pointer;">
    <td>${itm.name} (${itm.type}) <i class="bi bi-chevron-down ms-2 addon-arrow"></i></td>
    <td class="text-end">${symbol}${total.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
</tr>

                    <tr class="addon-breakdown" style="display:none; background:#f8f9fa;">
    <td colspan="2">
        <div class="p-2">
            <div class="d-flex justify-content-between mb-1 breakdown-section">
                <span>Base Price</span>
                <span>${symbol}${base.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</span>
            </div>
            <div class="d-flex justify-content-between mb-1 breakdown-section">
                <span class="gst-breakdown">GST (${(rate * 100).toFixed(2)}%)</span>
                <span>${symbol}${gstAmt.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</span>
            </div>
        </div>
    </td>
</tr>

                `);
            });
        }

        // ONLY ADD ON CLICK — NO AUTO-ADD
        $(document).on('click', '.toggle-btn', function () {
            const $btn = $(this);
            const id = String($btn.data('id'));
            const cost = Number($btn.data('cost')) || 0;
            const name = $btn.data('name');
            const type = $btn.data('type') || 'addon';
            let gstRate = Number($btn.data('gst-rate') || 0) / 100;

            if (type === 'package' && PACKAGE_GST_MAP[id] !== undefined) {
                gstRate = PACKAGE_GST_MAP[id] / 100;
            }

            const isSelected = $btn.hasClass('selected');

            if (!isSelected) {
                // ADD
                $btn.addClass('selected btn-danger').removeClass('add').text('Remove');
                selectedItems[id] = { name, cost, type, gstRate };
                if (type === 'addon') selectedAddons.push(id);
                if (type === 'package') selectedPackages.push(id);
                if (type === 'product') selectedProducts.push(id);
                Swal.fire({ icon: 'success', title: name + ' added', timer: 800, showConfirmButton: false });
            } else {
                // REMOVE
                $btn.removeClass('selected btn-danger').addClass('add').text('+ Add');
                delete selectedItems[id];
                selectedAddons = selectedAddons.filter(x => x !== id);
                selectedPackages = selectedPackages.filter(x => x !== id);
                selectedProducts = selectedProducts.filter(x => x !== id);
                Swal.fire({ icon: 'warning', title: name + ' removed', timer: 800, showConfirmButton: false });
            }

            updateHiddenInputs();
            updateSelectedSummary();
            recalcTotal();
        });

        // Initial recalc (no pre-selection)
        recalcTotal();
    </script>

    <?php
        $user_id = $_SESSION['user_id'];
        $sql = "SELECT * FROM `users` WHERE id = $user_id";
        $result = $conn->query($sql);
        $row = $result->fetch_assoc();

        $profileComplete = (
            $row["username"] && $row["email"] && $row["phone"] && 
            $row["first_name"] && $row["last_name"] && $row["business_name"] && 
            $row["address"] && $row["city"] && $row["state"] && 
            $row["country"] && $row["pincode"] && $row["user_id"] && $row["gst_in"]
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
            <input type="hidden" name="get_packages" id="get_packages_input" value="">
            <input type="hidden" name="get_products" id="get_products_input" value="">
            <input type="hidden" name="addon-total" id="addon-total" value="">
            <input type="hidden" class="gst-hidden" name="gst" value="<?php echo $gst; ?>">
            <input type="hidden" class="subtotal-display-hidden" name="subtotal-display" value="<?php echo $price; ?>">
            <input type="hidden" name="gst_id" value="<?php echo $gst_id; ?>">

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
            <input type="hidden" name="get_packages" id="get_packages_input" value="">
            <input type="hidden" name="get_products" id="get_products_input" value="">
            <input type="hidden" name="addon-total" id="addon-total" value="">
            <input type="hidden" class="gst-hidden" name="gst" value="<?php echo $gst; ?>">
            <input type="hidden" class="subtotal-display-hidden" name="subtotal-display" value="<?php echo $price; ?>">
            <input type="hidden" name="gst_id" value="<?php echo $gst_id; ?>">

            <button type="submit" class="lufera-bg text-center btn-sm px-12 py-10 float-end" style="width:150px; border: 1px solid #000">Continue</button>
        </form>
        <button class="lufera-bg text-center btn-sm px-12 py-10 float-end" data-bs-toggle="modal" data-bs-target="#exampleModal" style="width:250px; border: 1px solid #000">Update Profile & Continue</button>
    <?php } ?>

    <!-- Profile Modal -->
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
                                    <label for="" class="form-label fw-semibold text-primary-light text-sm mb-8">Client ID <span class="text-danger-600">*</span></label>
                                    <input type="text" class="form-control radius-8" name="client_id" value="<?php echo $row['user_id']; ?>" <?php echo !empty($row['user_id']) ? 'readonly' : ''; ?> required>
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
                                    <label for="" class="form-label fw-semibold text-primary-light text-sm mb-8">GSTIN <span class="text-danger-600">*</span></label>
                                    <input type="text" class="form-control radius-8" name="gst_in" value="<?php echo $row['gst_in']; ?>" <?php echo !empty($row['gst_in']) ? 'readonly' : ''; ?> required>
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
                </div>
                
                <div id="result"></div>
            </div>
        </div>
    </div>
</div>

<script>
    $('#updateForm').submit(function(e) {
        e.preventDefault();
        $.ajax({
            url: 'update-cart.php',
            type: 'POST',
            data: new FormData(this),
            processData: false,
            contentType: false,
            success: function(response) {
                if(response.trim() === "updated"){
                    $('#exampleModal').modal('hide');
                    $('#checkoutForm').show();
                    $('button[data-bs-target="#exampleModal"]').hide();
                    updateHiddenInputs();
                    recalcTotal();
                    Swal.fire({icon: "success", title: "Profile updated", timer: 1200, showConfirmButton: false});
                } else {
                    $('#result').html(response);
                }
            }
        });
    });

    $(document).on("click", ".breakdown-toggle", function() {
        $(this).next(".breakdown-row").slideToggle(150);
        $(this).find(".breakdown-arrow").toggleClass("bi-chevron-down bi-chevron-up");
    });

    $(document).on("click", ".addon-toggle", function () {
        $(this).next(".addon-breakdown").slideToggle(150);
        $(this).find(".addon-arrow").toggleClass("bi-chevron-down bi-chevron-up");
    });
</script>

<?php include './partials/layouts/layoutBottom.php' ?>