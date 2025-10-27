<style>
.btn-renewal {
    background-color: #c8e6c9;
    color: #000;
    border: 1px solid #81c784;
    font-weight: 500;
}
.btn-renewal:hover {
    background-color: #81c784;
    color: #fff;
}
</style>

<!-- Renewal Modal -->
<div class="modal fade" id="renewal-modal" tabindex="-1" aria-labelledby="renewalModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-0 pb-0">
                <h4 class="modal-title fw-semibold w-100 text-center">Renew your Business WordPress</h4>
            </div>
            <div class="modal-body px-3 px-md-5 pb-0">
                <p class="text-center text-muted mb-4">Review the details and proceed to checkout</p>

                <!-- Period Section -->
                <div class="p-3 border rounded-3 mb-3">
                    <div class="row align-items-center text-center text-md-start">
                        <div class="col-12 col-md-auto mb-2 mb-md-0"> 

                        <!-- Dynamic Period Dropdown -->
                        <select id="periodSelect" class="form-select w-100 w-md-auto">
                        <?php foreach ($durations as $dur): ?>
                            <?php
                                preg_match('/\d+/', strtolower($dur), $m);
                                $durValue = isset($m[0]) ? (int)$m[0] : 1;
                                if (stripos($dur, 'year') !== false) $durValue *= 12;

                                $isSelected = ($dur === $website['duration']) ? 'selected' : '';
                            ?>
                            <option value="<?= htmlspecialchars($durValue) ?>" <?= $isSelected ?>>
                                <?= htmlspecialchars(ucwords($dur)) ?>
                            </option>
                        <?php endforeach; ?>
                        </select>
                                                </div>
                        <div class="col-12 col-md text-center mb-2 mb-md-0">
                            <span class="badge bg-light text-success fw-semibold">save 7%</span>
                        </div>
                        <!-- ✅ Updated Price Section -->
                        <div class="col-12 col-md-auto text-center text-md-end">
                            <div id="previewPrice" class="text-muted small" style="text-decoration: line-through;">
                                ₹<?= htmlspecialchars(number_format($durationPrices[strtolower($Duration)]['preview_price'] ?? $currentPrice, 2)) ?> /<?= htmlspecialchars(ucwords($Duration)) ?>
                            </div>
                            <div id="actualPrice" class="fw-bold fs-5">
                                ₹<?= htmlspecialchars(number_format($durationPrices[strtolower($Duration)]['price'] ?? $currentPrice, 2)) ?> /<?= htmlspecialchars(ucwords($Duration)) ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Add-on Services -->
                <div class="p-3 border rounded-3 mb-3">
                    <div class="d-flex justify-content-between align-items-center flex-wrap">
                        <div>
                            <label class="fw-semibold mb-1">Add-on services</label><br>
                            <span>Daily Backup</span>
                        </div>
                        <span class="fw-semibold text-success mt-2 mt-md-0">Free</span>
                    </div>
                </div>

                <!-- Payment Method -->
                <div class="mb-3">
                    <label class="fw-semibold mb-2">Payment method</label>
                    <select class="form-select">
                        <option>💳 Visa ending 1234</option>
                        <option>💳 MasterCard ending 5678</option>
                        <option>Choose a different payment method</option>
                    </select>
                </div>

                <!-- Expiration Date
                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
                    <label class="fw-semibold mb-1 mb-md-0">Expiration date</label>
                    <span id="expirationDate"><?= htmlspecialchars($endDate->format('Y-m-d')) ?></span>
                </div> -->

                <!-- Expiration Date -->
                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
                    <label class="fw-semibold mb-1 mb-md-0">Expiration date</label>
                    <?php
                        // Decide which date to display
                        if (!empty($expiredAt) && $expiredAt !== '0000-00-00 00:00:00') {
                            $displayExpiration = (new DateTime($expiredAt))->format('Y-m-d');
                        } else {
                            $displayExpiration = $endDate->format('Y-m-d');
                        }
                    ?>
                    <span id="expirationDate"><?= htmlspecialchars($displayExpiration) ?></span>
                </div>

                <hr class="my-3">

                <!-- Subtotal -->
                <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <span class="fw-semibold">Subtotal</span>
                        <a href="#" class="text-warning fw-semibold text-decoration-none">Add coupon code</a>
                    </div>
                    <span id="subtotal" class="fw-semibold mt-2 mt-md-0">
                        ₹<?= htmlspecialchars(number_format($monthlyPrice * 12, 2)) ?>
                    </span>
                </div>

                <hr class="my-3">

                <!-- Total -->
                <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap">
                    <span class="fw-bold fs-5">Total</span>
                    <span id="total" class="fw-bold fs-5 mt-2 mt-md-0">
                        ₹<?= htmlspecialchars(number_format($monthlyPrice * 12, 2)) ?>
                    </span>
                </div>

                <p class="text-muted small mt-3">
                    By checking out, you agree with our 
                    <a href="#" class="text-decoration-none">Terms of Service</a> and confirm that you have read our 
                    <a href="#" class="text-decoration-none">Privacy Policy</a>. 
                    You can cancel recurring payments at any time.
                </p>
            </div>

            <div class="modal-footer border-0 px-3 px-md-5 pb-4 d-flex justify-content-end gap-3 flex-wrap">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>

                <form action="cart-payment.php" method="POST" id="renewalForm">
                    <input type="hidden" name="renewal" value="1">
                    <input type="hidden" name="id" value="<?= htmlspecialchars($websiteId) ?>">
                    <input type="hidden" name="period" id="periodInput">
                    <!-- <input type="hidden" name="expiration_date" id="expirationDateInput">  -->
                     <input type="hidden" name="expiration_date" id="expirationDateInput" value="<?= htmlspecialchars($CreatedAt) ?>"> 
                    <input type="hidden" name="total" id="totalInput">                     
                    <input type="hidden" name="subtotal" id="subtotalInput">    
                    <input type="hidden" name="receipt_id" value="<?= $InvoiceId ?>">

                    <button type="submit" class="btn lufera-bg">Complete Payment</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
    $baseExpirationDate = $displayExpiration; // base date for JS
?>


<script>
document.addEventListener('DOMContentLoaded', function() {
    const durationData = <?= json_encode($durationPrices) ?>;
    const select = document.getElementById('periodSelect');
    const subtotalEl = document.getElementById('subtotal');
    const totalEl = document.getElementById('total');
    const expirationDateEl = document.getElementById('expirationDate');
    const periodInput = document.getElementById('periodInput');
    const subtotalInput = document.getElementById('subtotalInput');
    const totalInput = document.getElementById('totalInput');
    // const baseEndDate = new Date("<?= $endDate->format('Y-m-d') ?>");
    const baseEndDate = new Date("<?= $baseExpirationDate ?>");


    function formatCurrency(amount) {
        return '₹' + amount.toLocaleString('en-IN', { minimumFractionDigits: 2 });
    }

    function updateTotals() {
        const months = parseInt(select.value);
        let selectedText = select.options[select.selectedIndex].text.toLowerCase().trim();

        const priceInfo = durationData[selectedText] || { price: 0, preview_price: 0 };
        const price = parseFloat(priceInfo.price);
        const previewPrice = parseFloat(priceInfo.preview_price);

        // ✅ Updated to show “/Duration” instead of “/mo”
        document.getElementById('previewPrice').textContent = formatCurrency(previewPrice) + ' /' + select.options[select.selectedIndex].text;
        document.getElementById('actualPrice').textContent = formatCurrency(price) + ' /' + select.options[select.selectedIndex].text;

        subtotalEl.textContent = formatCurrency(price);
        totalEl.textContent = formatCurrency(price);

        const newDate = new Date(baseEndDate);
        newDate.setMonth(newDate.getMonth() + months);
        const formattedDate = newDate.toISOString().split('T')[0];
        expirationDateEl.textContent = formattedDate;

        periodInput.value = select.options[select.selectedIndex].text;
        subtotalInput.value = price;
        totalInput.value = price;
    }

    select.addEventListener('change', updateTotals);
    updateTotals();
});
</script>