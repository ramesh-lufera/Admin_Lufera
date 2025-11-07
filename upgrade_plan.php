<?php
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);
    include './partials/layouts/layoutTop.php';
    $Id = $_SESSION['user_id'];
    $package_id = $_GET['prod_id'];
    $web_id = $_GET['web_id'];
    $duration_get = $_GET['duration'];
    
    $result1 = $conn->query("SELECT symbol FROM currencies WHERE is_active = 1 LIMIT 1");
    $symbol = "$"; // default
    if ($row1 = $result1->fetch_assoc()) {
        $symbol = $row1['symbol'];
    }
?>
<!-- meta tags and other links -->
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<title>Upgrade your plan</title>
<style>
    /* Styling for disabled button to appear blurred */
    .disabled {
        pointer-events: none;  /* Prevents clicking */
        opacity: 0.5;  /* Makes the button appear blurred */
    }

    .upgrade_main{
        display: flex;
        flex-direction: row;
        gap: 32px;
    }
    .upgrade__plans {
        width: 60%;
    }
    .comparison {
        display: flex;
        flex-direction: column;
        width: 100%;
        overflow: visible;
    }
    .comparison__item-wrapper {
        display: flex;
        flex-direction: row;
        overflow: visible;
    }
    .upgrade--no-footer.upgrade__current {
    border-bottom-left-radius: 16px !important;
    overflow: visible !important;
}
.upgrade--no-footer.upgrade__selected {
    border-bottom-right-radius: 16px !important;
    overflow: visible !important;
}
.upgrade {
    width: 100%;
}
.h-portlet {
    margin-bottom: 24px;
    border-radius: 8px;
    border: 1px solid #d8dae0;
    background-color: #ffffff;
}
.upgrade__header--current {
    color: #6d7081;
    background-color: #f2f3f6;
    border-radius: 16px 0 0 0;
}
.upgrade__header--selected {
    color: #fec700;
    background-color: #fec70024;
    border-radius: 0 16px 0 0;
}

.upgrade__header {
    text-align: center;
    font-size: 12px;
    font-weight: 700;
    padding: 12px;
    letter-spacing: 1px;
}
.h-portlet__body {
    padding: 16px 24px;
    height: 100%;
    color: #1d1e20;
}
.upgrade__body--prices {
    display: flex;
    flex-direction: column;
    gap: 12px;
    margin-bottom: 32px;
    position: relative;
    z-index: 10;
}
.new-price {
    display: flex;
    justify-content: center;
    align-items: center;
    flex-direction: row;
}
.upgrade__body--prices .new-price__period {
    font-size: 12px;
    padding: 0 4px;
    align-self: flex-end;
}
.h-my-16 {
    margin-top: 16px !important;
    margin-bottom: 16px !important;
    font-size:14px !important;
}
.h-portlet__body--no-padding {
    padding: 0;
}
.upgrade__current {
    border-radius: 16px 0 0 0;
    border-right: none;
    margin-bottom: 0;
}
.upgrade__selected {
    border-radius: 0 16px 0 0;
    margin-bottom: 0;
}
.new-price__value {
    font-size: 36px;
    line-height: 48px;
    font-weight: 600;
    margin-top:30px;
}
.new-price__period {
    font-size: 12px;
    padding: 0 4px;
    margin-top:30px;
    /* align-self: flex-end; */
}
.text-body-3 {
    font-size: 12px;
    font-weight: 400;
    line-height: 20px;
    color: #6d7081;
    margin-top:10px;
}
.upgrade__payment-card{
    width: 40%;
}
.trans{
    font-size:20px !important;
    word-break: break-word;
}

.purchase-details {
    margin: 16px 0;
}

.purchase-details__list {
    list-style: none;
    padding: 16px;
    border-radius: 4px;
    margin: 0;
    background-color: #f2f3f6;
}
.details-item {
    margin-bottom: 20px;
    display: flex;
    justify-content: space-between;
}
.details-item__text {
    display: inline-block;
    margin-right: 4px;
    margin-bottom: 0;
    font-size:14px !important;
}
.details-item__right {
    position: relative;
}
.details-item__price{
    font-size: 14px !important;
    margin-bottom: 0px;
}
.height-fixed{
    height:43px;
    align-content: center;
}
.text-breakdown{
    font-size:13px;
    color: gray;
}
</style>

</head>

<body>
    <div class="dashboard-main-body">
        <div class="d-flex flex-wrap align-items-center gap-3 mb-24">
            <a class="cursor-pointer fw-bold" onclick="history.back()"><span class="fa fa-arrow-left"></span>&nbsp; Back</a> 
            <h6 class="fw-semibold mb-0 m-auto">Upgrade your plan</h6>
            <a class="cursor-pointer fw-bold visibility-hidden" onclick="history.back()"><span class="fa fa-arrow-left"></span>&nbsp; Back</a> 
        </div>
        <div class="card">
            <div class="card-body">
                <div class="container upgrade_main">
                    <div class="upgrade__plans">
                        <div class="comparison">
                            <div class="comparison__item-wrapper">                                
                                <div class="h-portlet upgrade upgrade__current upgrade--default upgrade--no-footer">
                                    <div class="h-portlet__body position-relative h-portlet__body--no-padding">
                                        <div class="upgrade__header upgrade__header--current">CURRENT PLAN</div>
                                            <div class="upgrade__body upgrade__body--current">
                                                <div class="h-portlet__body position-relative">
                                                    <div class="mb-20">
                                                    <?php 
                                                    $plan_sql = "
                                                    SELECT p.*, d.price, d.duration 
                                                    FROM package p
                                                    INNER JOIN durations d ON d.package_id = p.id
                                                    WHERE p.id = ? AND d.duration = ?
                                                ";
                                                $stmt = $conn->prepare($plan_sql);
                                                $stmt->bind_param("is", $package_id, $duration_get);
                                                $stmt->execute();
                                                $plan_fetch = $stmt->get_result();
                                                $plan_row = $plan_fetch->fetch_assoc();
                                                

                                                    $current_plan_id = $plan_row['id'];
                                                    $plan_name = $plan_row['package_name'];
                                                    $price = floatval($plan_row['price']);
                                                    $duration = strtolower(trim($plan_row['duration']));

                                                    // --- Determine total months ---
                                                    $months = 1; // default
                                                    if (strpos($duration, 'year') !== false) {
                                                        $years = floatval($duration) ?: 1;
                                                        $months = $years * 12;
                                                    } elseif (strpos($duration, 'month') !== false) {
                                                        $months = floatval($duration) ?: 1;
                                                    }

                                                    // --- Calculate monthly price ---
                                                    $monthly_price = $price / $months;
                                                    ?>
                                                        <div class="height-fixed">
                                                            <h4 class="h-my-16 text-center"><?php echo htmlspecialchars($plan_name); ?></h4>
                                                        </div>
                                                        <div class="new-price">
                                                            <span class="new-price__value"><?= htmlspecialchars($symbol) ?> <?= number_format($monthly_price, 2); ?></span>
                                                            <span class="new-price__period">/mo</span>
                                                        </div>
                                                    </div>
                                                    <ul>
                                                    <?php
                                                    $feature_sql = "SELECT feature FROM features WHERE package_id = $package_id";
                                                    $feature_result = $conn->query($feature_sql);
                                                    if ($feature_result && $feature_result->num_rows > 0):
                                                        while ($feat = $feature_result->fetch_assoc()):
                                                    ?>
                                                        <li class="d-flex align-items-center gap-16 mb-16">
                                                            <span class="w-24-px h-24-px p-2 d-flex justify-content-center align-items-center lufera-bg rounded-circle">
                                                                <iconify-icon icon="iconamoon:check-light" class="text-white text-lg "></iconify-icon>
                                                            </span>
                                                            <span class="text-secondary-light"><?= htmlspecialchars($feat['feature']) ?></span>
                                                        </li>
                                                    <?php endwhile; endif; ?>
                                                </ul>
                                                </div>
                                            </div>
                                    </div>
                                </div>
                                <div class="h-portlet upgrade upgrade__selected upgrade--default upgrade--no-footer">
                                    <div class="h-portlet__body position-relative h-portlet__body--no-padding">
                                        <div class="upgrade__header upgrade__header--selected">UPGRADE TO</div>
                                            <div class="upgrade__body upgrade__body--current">
                                                <div class="h-portlet__body position-relative">
                                                    <div class="mb-20">
                                                        <?php
                                                        // Fetch the selected/current package
                                                        $plan_sql = "
                                                            SELECT p.*, d.price, d.duration 
                                                            FROM package p
                                                            INNER JOIN durations d ON d.package_id = p.id
                                                            WHERE p.id = ? AND d.duration = ?
                                                        ";
                                                        $stmt = $conn->prepare($plan_sql);
                                                        $stmt->bind_param("is", $package_id, $duration_get);
                                                        $stmt->execute();
                                                        $plan_fetch = $stmt->get_result();
                                                        $plan_row = $plan_fetch->fetch_assoc();
                                                        $current_price = $plan_row['price'];
                                                        $current_cat = $plan_row['cat_id'];
                                                        $current_duration = $plan_row['duration'];


                                                        // Fetch all packages with price greater than the current plan
                                                        $upgrade_sql = "
                                                            SELECT p.id, p.package_name, d.price, d.duration
                                                            FROM package p
                                                            INNER JOIN durations d ON d.package_id = p.id
                                                            WHERE d.price > ? AND p.cat_id = ? AND d.duration = ?
                                                            ORDER BY d.price ASC";
                                                        $stmt2 = $conn->prepare($upgrade_sql);
                                                        $stmt2->bind_param("dis", $current_price, $current_cat, $current_duration);
                                                        $stmt2->execute();
                                                        $upgrade_result = $stmt2->get_result();


                                                        // Check if there are any upgrade options
                                                        $has_upgrades = $upgrade_result->num_rows > 0;
                                                        $default_price = 0;
                                                        $first_package_id = null;

                                                        if ($has_upgrades):
                                                            // Get the first higher package (default)
                                                            $first = true;
                                                        ?>
                                                        <div class="height-fixed">
                                                            <select id="upgradePackage" class="form-select">
                                                                <?php 
                                                                while ($upgrade = $upgrade_result->fetch_assoc()):
                                                                    if ($first) {
                                                                        $duration = strtolower($upgrade['duration']);
                                                                        $months = 1;
                                                                        if (strpos($duration, 'year') !== false) {
                                                                            $years = floatval($duration) ?: 1;
                                                                            $months = $years * 12;
                                                                        } elseif (strpos($duration, 'month') !== false) {
                                                                            $months = floatval($duration) ?: 1;
                                                                        }
                                                                        $default_price = $upgrade['price'] / $months;
                                                                        $first_package_id = $upgrade['id'];
                                                                        $first = false;
                                                                    }                                                                                                                        
                                                                ?>
                                                                    <option value="<?= htmlspecialchars($upgrade['id']) ?>" data-price="<?= htmlspecialchars($upgrade['price']) ?>" data-duration="<?= htmlspecialchars($upgrade['duration']) ?>">
                                                                        <?= htmlspecialchars($upgrade['package_name']) ?>
                                                                    </option>
                                                                <?php endwhile; ?>
                                                            </select>
                                                        </div>
                                                        <div class="new-price">
                                                            <span class="new-price__value upgrade_price"><?= htmlspecialchars($symbol) ?> <?= number_format($default_price, 2); ?></span>                                                        
                                                            <span class="new-price__period">/mo</span>
                                                        </div>
                                                        <ul id="upgradeFeatures" class="mt-3">
                                                            <?php
                                                            // Fetch default features for first package
                                                            if (isset($first_package_id)) {
                                                                $feature_sql = "SELECT feature FROM features WHERE package_id = ?";
                                                                $stmt_feat = $conn->prepare($feature_sql);
                                                                $stmt_feat->bind_param("i", $first_package_id);
                                                                $stmt_feat->execute();
                                                                $feat_result = $stmt_feat->get_result();

                                                                while ($feat = $feat_result->fetch_assoc()):
                                                            ?>
                                                                <li class="d-flex align-items-center gap-16 mb-16">
                                                                    <span class="w-24-px h-24-px p-2 d-flex justify-content-center align-items-center lufera-bg rounded-circle">
                                                                        <iconify-icon icon="iconamoon:check-light" class="text-white text-lg"></iconify-icon>
                                                                    </span>
                                                                    <span class="text-secondary-light"><?= htmlspecialchars($feat['feature']) ?></span>
                                                                </li>
                                                            <?php endwhile; } ?>
                                                        </ul>
                                                        <?php else: ?>
                                                        <div class="height-fixed">
                                                            <h4 class="h-my-16 text-center text-muted">You are on the highest plan</h4>
                                                        </div>
                                                        <ul id="upgradeFeatures" class="mt-3">
                                                            <li class="text-muted">No upgrade options available</li>
                                                        </ul>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <?php if ($has_upgrades): ?>
                    <div class="upgrade__payment-card">
                        <div class="h-portlet w-100">
                            <div class="h-portlet__body position-relative">
                                <h2 class="trans h-mb-24 h-mt-8">Upgrade to Cloud Startup Hosting</h2>                                                               
                                <div class="purchase-details">
                                    <ul class="purchase-details__list">
                                        <li class="details-item">
                                            <div class="details-item__left">
                                                <h4 class="details-item__text">Expiration Date</h4>
                                            </div>
                                            <div class="details-item__right">
                                            <p class="details-item__price expiration-date"></p> 
                                            </div>
                                        </li>
                                        <li class="details-item mb-0">
                                            <div class="details-item__left">
                                                <h4 class="details-item__text">Total
                                                    <button type="button"
                                                        class="btn btn-link p-0 ms-2 align-baseline text-decoration-none total-toggle"
                                                        id="totalToggle"
                                                        aria-expanded="false"
                                                        aria-controls="totalBreakdown">
                                                        <i class="fa fa-chevron-down text-secondary" id="totalToggleIcon"></i>
                                                    </button>
                                                </h4>
                                            </div>
                                            <div class="details-item__right">
                                                <p class="details-item__price total-amount"></p>
                                            </div>
                                        </li>
                                        <div class="total-breakdown d-none mb-20" id="totalBreakdown">
                                            <div class="d-flex justify-content-between">
                                                <span class="text-breakdown">Price</span>
                                                <span class="text-end text-breakdown" id="totalBreakdownPrice"></span>
                                            </div>
                                            <div class="d-flex justify-content-between">
                                                <span class="text-breakdown">Tax <span id="totalBreakdownTaxLabel"></span></span>
                                                <span class="text-end text-breakdown" id="totalBreakdownTax"></span>
                                            </div>
                                        </div>
                                        <li class="details-item mt-20">
                                            <div class="details-item__left">
                                                <h4 class="details-item__text ">Exisitng Plan Balance</h4>
                                            </div>
                                            <div class="details-item__right">
                                            <?php
                                            // Fetch website start date (created_at) for this user and current package
                                            $website_sql = "SELECT * FROM websites WHERE id = ? LIMIT 1";
                                            $stmt_web = $conn->prepare($website_sql);
                                            $stmt_web->bind_param("i", $web_id);
                                            $stmt_web->execute();
                                            $result_web = $stmt_web->get_result();
                                            $website = $result_web->fetch_assoc();
                                            $webs_id = $website['id'];
                                            $cat_id = $website['cat_id'];
                                            $invoice_id = $website['invoice_id'];
                                            $receipt_id = rand(10000000, 99999999);

                                            $start_date = $website ? new DateTime($website['created_at']) : new DateTime();
                                            $today = new DateTime();

                                            // Get plan info
                                            $plan_sql = "
                                                SELECT d.price, d.duration 
                                                FROM durations d
                                                WHERE d.package_id = ? AND d.duration = ?";
                                            $stmt_plan = $conn->prepare($plan_sql);
                                            $stmt_plan->bind_param("is", $package_id, $duration_get);
                                            $stmt_plan->execute();
                                            $plan_result = $stmt_plan->get_result();
                                            $plan = $plan_result->fetch_assoc();


                                            $price = floatval($plan['price']);
                                            $duration = strtolower($plan['duration']);

                                            // Calculate end date based on duration
                                            $end_date = clone $start_date;
                                            if (strpos($duration, 'year') !== false) {
                                                $years = floatval($duration) ?: 1;
                                                $end_date->modify("+{$years} year");
                                            } elseif (strpos($duration, 'month') !== false) {
                                                $months = floatval($duration) ?: 1;
                                                $end_date->modify("+{$months} month");
                                            } else {
                                                $end_date->modify("+1 month"); // default fallback
                                            }

                                            // Calculate balance (base amount)
                                            $total_days = $start_date->diff($end_date)->days;
                                            $remaining_days = $today < $end_date ? $today->diff($end_date)->days : 0;
                                            $hostinger_balance = ($total_days > 0) ? ($remaining_days / $total_days) * $price : 0;

                                            // Include GST for the current plan balance (Option C)
                                            $current_gst_rate = 0.0;
                                            $pkg_stmt = $conn->prepare("SELECT gst_id FROM package WHERE id = ? LIMIT 1");
                                            $pkg_stmt->bind_param("i", $package_id);
                                            $pkg_stmt->execute();
                                            $pkg_res = $pkg_stmt->get_result();
                                            if ($pkg_row = $pkg_res->fetch_assoc()) {
                                                $gst_id_current = $pkg_row['gst_id'] ?? null;
                                                if (!empty($gst_id_current)) {
                                                    $tax_stmt = $conn->prepare("SELECT rate FROM taxes WHERE id = ? LIMIT 1");
                                                    $tax_stmt->bind_param("i", $gst_id_current);
                                                    $tax_stmt->execute();
                                                    $tax_res = $tax_stmt->get_result();
                                                    if ($tax_row = $tax_res->fetch_assoc()) {
                                                        $current_gst_rate = floatval($tax_row['rate']);
                                                    }
                                                    $tax_stmt->close();
                                                }
                                            }
                                            $pkg_stmt->close();

                                            $hostinger_balance_tax = round($hostinger_balance * ($current_gst_rate / 100), 2);
                                            $hostinger_balance_with_tax = round($hostinger_balance + $hostinger_balance_tax, 2);
                                            ?>
                                            <p class="details-item__price hostinger-balance"><?php echo $symbol; ?> <?= number_format($hostinger_balance_with_tax, 2) ?></p>
                                            </div>
                                        </li>
                                        <li class="details-item">
                                            <div class="details-item__left">
                                                <h4 class="details-item__text">Amount To Pay</h4>
                                            </div>
                                            <div class="details-item__right">
                                            <p class="details-item__price amount-to-pay"></p> 
                                            </div>
                                        </li>
                                    </ul>                            
                                </div>
                                <form action="cart-payment.php" method="POST">
                                    <input type="hidden" name="web_id" value="<?= htmlspecialchars($web_id) ?>">
                                    <input type="hidden" name="user_id" value="<?= htmlspecialchars($_SESSION['user_id']) ?>">
                                   
                                    <input type="hidden" name="hostinger_balance" id="hostinger_balance" value="">
                                    <input type="hidden" name="subtotal-display" id="amount_to_pay" value="">
                                    
                                    <input type="hidden" name="cat_id" id="cat_id" value="<?php echo $current_cat; ?>">
                                    <input type="hidden" name="current_plan_id" id="current_plan_id" value="<?php echo $current_plan_id ?>">
                                    
    
                                    <input type="hidden" name="id" id="upgrade_package_id" value="">
                                    <input type="hidden" name="plan_name" id="plan_name" value="">
                                    <input type="hidden" name="invoice_id" id="invoice_id" value="<?php echo $invoice_id ?>">
                                    <input type="hidden" name="duration" id="duration" value="<?php echo $duration; ?>">
                                    <input type="hidden" name="price" id="total_amount" value="">
                                    <input type="hidden" name="gst" id="amount_tax" value="">
                                    <input type="hidden" name="total_price" id="total_price" value="">
                                    <input type="hidden" name="type" value="package">  
                                    <input type="hidden" name="receipt_id" value="<?php echo $receipt_id; ?>"> 
                                    <!-- <input type="hidden" name="duration" id="duration" value="<?php echo $duration; ?>"> -->
                                    <!-- <input type="hidden" name="total_amount" id="total_amount" value=""> -->
                                    <!-- <input type="hidden" name="invoice_id" id="invoice_id" value="<?php echo $invoice_id ?>"> -->
                                    <button type="submit" class="lufera-bg btn w-100 text-white">Complete upgrade payment</button>
                                </form>
                            </div>  
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>  
</body>

<script>
const websiteDuration = <?= json_encode(strtolower($plan_row['duration'] ?? '1 month')) ?>;
const hasUpgrades = <?= json_encode($has_upgrades) ?>;

function updateUpgradeDisplay() {
    if (!hasUpgrades) return; // Skip if no upgrades available

    const select = document.getElementById('upgradePackage');
    const selected = select.options[select.selectedIndex];
    const price = parseFloat(selected.getAttribute('data-price'));
    const duration = selected.getAttribute('data-duration')?.toLowerCase() || '';
    const packageId = selected.value;
    const packageName = selected.textContent.trim();

    const priceDisplay = document.querySelector('.upgrade_price');
    const totalElement = document.querySelector('.details-item__price.total-amount');
    const expirationElement = document.querySelector('.details-item__price.expiration-date');
    const featuresList = document.getElementById('upgradeFeatures');
    const balanceElement = document.querySelector('.details-item__price.hostinger-balance');
    const amountToPayElement = document.querySelector('.details-item__price.amount-to-pay');

    // Determine months
    let months = 1;
    if (duration.includes('year')) {
        const years = parseFloat(duration) || 1;
        months = years * 12;
    } else if (duration.includes('month')) {
        const m = parseFloat(duration) || 1;
        months = m;
    }

    let monthlyPrice = price / months;
    let totalPrice = price; // will update after fetching tax rate

    // --- Expiration date calculation ---
    const currentDate = new Date();
    const expirationDate = new Date(currentDate);
    let durationValue = 1;
    let durationUnit = 'month';
    if (websiteDuration.includes('year')) {
        durationValue = parseFloat(websiteDuration) || 1;
        durationUnit = 'year';
    } else if (websiteDuration.includes('month')) {
        durationValue = parseFloat(websiteDuration) || 1;
        durationUnit = 'month';
    }

    if (durationUnit === 'year') {
        expirationDate.setFullYear(currentDate.getFullYear() + durationValue);
    } else {
        expirationDate.setMonth(currentDate.getMonth() + durationValue);
    }

    const formattedExpiration = expirationDate.toISOString().split('T')[0];

    // --- Update expiration UI early ---
    if (expirationElement) expirationElement.textContent = formattedExpiration;

    // Fetch price+tax rate for selected package/duration
    const params = new URLSearchParams({ package_id: packageId, duration: duration });
    fetch('get_package_pricing.php?' + params.toString())
        .then(resp => resp.json())
        .then(data => {
            if (!data || data.ok !== true) {
                throw new Error('Pricing not available');
            }

            // Server authoritative values
            totalPrice = parseFloat(data.price) || 0; // base price for duration
            const taxRate = parseFloat(data.tax_rate) || 0;
            const gstAmt  = parseFloat(data.gst) || 0;
            const totalWithTax = parseFloat(data.total) || (totalPrice + gstAmt);

            // Recompute monthly based on returned base price
            monthlyPrice = (totalPrice / months) || 0;

            // Balance (includes GST) and amount-to-pay = total-amount (incl GST) - hostinger-balance (incl GST)
            const balance = parseFloat((balanceElement?.textContent || '').replace(/[^\d.]/g, '')) || 0;
            const amountToPay = Math.max(0, (parseFloat(totalWithTax) || 0) - balance);
            const amountTax   = (amountToPay * (taxRate / 100));
            const grandTotal  = amountToPay + amountTax;

            // --- Update UI ---
            priceDisplay.textContent = '<?= htmlspecialchars($symbol) ?> ' + monthlyPrice.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            if (totalElement) totalElement.textContent = (<?= json_encode($symbol) ?> + ' ' + totalWithTax.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
            if (amountToPayElement) amountToPayElement.textContent = <?= json_encode($symbol) ?> + ' ' + amountToPay.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });

            // --- Update inline breakdown ---
            const breakdownPriceEl = document.getElementById('totalBreakdownPrice');
            const breakdownTaxEl = document.getElementById('totalBreakdownTax');
            const breakdownTaxLabel = document.getElementById('totalBreakdownTaxLabel');
            const basePriceFormatted = totalPrice.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            const taxAmountFormatted = gstAmt.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });

            if (breakdownPriceEl) {
                breakdownPriceEl.textContent = <?= json_encode($symbol) ?> + ' ' + basePriceFormatted;
            }
            if (breakdownTaxEl) {
                breakdownTaxEl.textContent = <?= json_encode($symbol) ?> + ' ' + taxAmountFormatted;
            }
            if (breakdownTaxLabel) {
                breakdownTaxLabel.textContent = `(${taxRate.toFixed(2)}%)`;
            }

            // ✅ Update hidden fields
            document.getElementById('upgrade_package_id').value = packageId;
            // Set hidden price equal to displayed Total (base price + GST)
            document.getElementById('total_amount').value = totalWithTax.toFixed(2);
            document.getElementById('hostinger_balance').value = balance.toFixed(2);
            document.getElementById('amount_to_pay').value = amountToPay.toFixed(2);
            document.getElementById('plan_name').value = packageName;
            document.getElementById('amount_tax').value = amountTax.toFixed(2);
            document.getElementById('total_price').value = grandTotal.toFixed(2);
        })
        .catch(() => {
            // Fallback: show base price without tax
            priceDisplay.textContent = '<?= htmlspecialchars($symbol) ?> ' + monthlyPrice.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            if (totalElement) totalElement.textContent = (<?= json_encode($symbol) ?> + ' ' + totalPrice.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
        });

    // --- Fetch updated features ---
    fetch('get_features.php?package_id=' + packageId)
        .then(response => response.json())
        .then(data => {
            featuresList.innerHTML = '';
            if (data.length > 0) {
                data.forEach(feature => {
                    const li = document.createElement('li');
                    li.classList.add('d-flex', 'align-items-center', 'gap-16', 'mb-16');
                    li.innerHTML = `
                        <span class="w-24-px h-24-px p-2 d-flex justify-content-center align-items-center lufera-bg rounded-circle">
                            <iconify-icon icon="iconamoon:check-light" class="text-white text-lg"></iconify-icon>
                        </span>
                        <span class="text-secondary-light">${feature}</span>
                    `;
                    featuresList.appendChild(li);
                });
            } else {
                featuresList.innerHTML = '<li class="text-muted">No features found</li>';
            }
        })
        .catch(err => {
            console.error('Error fetching features:', err);
            featuresList.innerHTML = '<li class="text-danger">Failed to load features</li>';
        });
}

document.addEventListener('DOMContentLoaded', () => {
    updateUpgradeDisplay();

    const toggleBtn = document.getElementById('totalToggle');
    const breakdown = document.getElementById('totalBreakdown');
    const toggleIcon = document.getElementById('totalToggleIcon');

    if (toggleBtn && breakdown && toggleIcon) {
        toggleBtn.addEventListener('click', () => {
            const isHidden = breakdown.classList.toggle('d-none');
            toggleBtn.setAttribute('aria-expanded', (!isHidden).toString());
            if (isHidden) {
                toggleIcon.classList.remove('fa-chevron-up');
                toggleIcon.classList.add('fa-chevron-down');
            } else {
                toggleIcon.classList.remove('fa-chevron-down');
                toggleIcon.classList.add('fa-chevron-up');
            }
        });
    }
});

if (hasUpgrades) {
    document.getElementById('upgradePackage').addEventListener('change', updateUpgradeDisplay);
}
</script>

</html>

<?php include './partials/layouts/layoutBottom.php' ?>
