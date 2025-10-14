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
                            <select id="periodSelect" class="form-select w-100 w-md-auto">
                                <option value="48">48 months</option>
                                <option value="24">24 months</option>
                                <option value="12" selected>12 months</option>
                                <option value="1">1 month</option>
                            </select>
                        </div>
                        <div class="col-12 col-md text-center mb-2 mb-md-0">
                            <span class="badge bg-light text-success fw-semibold">save 7%</span>
                        </div>
                        <div class="col-12 col-md-auto text-center text-md-end">
                            <div class="text-muted small" style="text-decoration: line-through;">
                                ₹<?= htmlspecialchars($monthlyPriceFormatted) ?><?= $showMo ? ' /mo' : '' ?>
                            </div>
                            <div class="fw-bold fs-5">
                                ₹<?= htmlspecialchars($monthlyPriceFormatted) ?><?= $showMo ? ' /mo' : '' ?>
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

                <!-- Expiration Date -->
                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
                    <label class="fw-semibold mb-1 mb-md-0">Expiration date</label>
                    <span id="expirationDate"><?= htmlspecialchars($endDate->format('Y-m-d')) ?></span>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    const monthlyPrice = <?= $monthlyPrice ?>;
    const durationType = '<?= $durationType ?>';
    const number = <?= $number ?>;
    const select = document.getElementById('periodSelect');

    const subtotalEl = document.getElementById('subtotal');
    const totalEl = document.getElementById('total');
    const expirationDateEl = document.getElementById('expirationDate');
    const periodInput = document.getElementById('periodInput');
    const subtotalInput = document.getElementById('subtotalInput');
    const totalInput = document.getElementById('totalInput');
    const baseEndDate = new Date("<?= $endDate->format('Y-m-d') ?>");

    function formatCurrency(amount) {
        return '₹' + amount.toLocaleString('en-IN', { minimumFractionDigits: 2 });
    }

    function updateTotals() {
        const months = parseInt(select.value);
        let subtotal = monthlyPrice * months;
        subtotalEl.textContent = formatCurrency(subtotal);
        totalEl.textContent = formatCurrency(subtotal);

        const newDate = new Date(baseEndDate);
        newDate.setMonth(newDate.getMonth() + months);
        const formattedDate = newDate.toISOString().split('T')[0];
        expirationDateEl.textContent = formattedDate;

        periodInput.value = months + " months";
        subtotalInput.value = subtotal;
        totalInput.value = subtotal;

        // Do NOT update expiration_date hidden input (only display changes)
    }

    select.addEventListener('change', updateTotals);
    updateTotals();
});
</script>