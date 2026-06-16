<?php include './partials/layouts/layoutTop.php';
    require_once __DIR__ . '/vendor/autoload.php';
    require_once __DIR__ . '/vendor_pdf/autoload.php';
    use Dotenv\Dotenv;
    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;
    use Dompdf\Dompdf;
    use Dompdf\Options;
    $dotenv = Dotenv::createImmutable(__DIR__);
    $dotenv->load();
    // ini_set('display_errors', 1);
    // ini_set('display_startup_errors', 1);
    // error_reporting(E_ALL);
?>
<?php
if(isset($_POST['save_invoice']))
{
    $user_id = $_POST['user_id'];
    $invoice_id = ['invoice_id'];
    $invoice_id = $_POST['invoice_id'];
    $terms = $_POST['terms'];
    $status = $_POST['status'];
    $custom_invoice = $_POST['custom_invoice'];    
    $total_gst = $_POST['total_gst'];
    $total_amount = $_POST['total_amount'];


    $sql = "INSERT INTO orders (
        user_id,
        invoice_id,
        plan,
        duration,
        price,
        addon_price,
        addon_gst,
        subtotal,
        gst,
        discount,
        amount,
        balance_due,
        payment_made,
        payment_method,
        addon_service,
        type,
        discount_type,
        status,
        created_on,
        is_Active,
        coupon_code,
        discount_amount,
        existing_balance,
        existing_plan,
        invoice_snapshot,
        is_deleted,
        last_reminder_sent,
        custom_invoice
    ) VALUES (
        '$user_id',
        '$invoice_id',
        '',
        NULL,
        NULL,
        NULL,
        '0.00',
        NULL,
        '$total_gst',
        '0.00',
        '$total_amount',
        '$total_amount',
        NULL,
        '$terms',
        NULL,
        'custom',
        NULL,
        '$status',
        NOW(),
        '1',
        NULL,
        NULL,
        NULL,
        NULL,
        NULL,
        '0',
        NULL,
        '$custom_invoice'
    )";

    if(mysqli_query($conn, $sql))
    {
        echo "<script>
                alert('Invoice Saved Successfully');
                window.location.href='';
              </script>";
        exit;
    }
    else
    {
        echo mysqli_error($conn);
    }
}
// Indian Rupees style number to words (supports up to crores)
function numberToWords($num) {
    $num = (float)$num;
    if ($num == 0) return 'Zero Only';

    $ones = ["", "One", "Two", "Three", "Four", "Five", "Six", "Seven", "Eight", "Nine", "Ten",
             "Eleven", "Twelve", "Thirteen", "Fourteen", "Fifteen", "Sixteen", "Seventeen", "Eighteen", "Nineteen"];
    $tens = ["", "", "Twenty", "Thirty", "Forty", "Fifty", "Sixty", "Seventy", "Eighty", "Ninety"];
    $scales = ["", "Thousand", "Lakh", "Crore"];

    $integer = floor($num);
    $decimal = round(($num - $integer) * 100);

    $str = "";

    // Handle integer part
    $i = 0;
    while ($integer > 0) {
        $part = $integer % 1000;
        if ($part > 0) {
            $part_str = "";
            $hundreds = floor($part / 100);
            $remainder = $part % 100;

            if ($hundreds > 0) {
                $part_str .= $ones[$hundreds] . " Hundred ";
            }

            if ($remainder > 0) {
                if ($remainder < 20) {
                    $part_str .= $ones[$remainder] . " ";
                } else {
                    $part_str .= $tens[floor($remainder / 10)] . " ";
                    if ($remainder % 10 > 0) {
                        $part_str .= $ones[$remainder % 10] . " ";
                    }
                }
            }

            if ($i > 0) {
                $part_str .= $scales[$i] . " ";
            }

            $str = $part_str . $str;
        }
        $integer = floor($integer / 1000);
        $i++;
    }

    $str = trim($str);

    // Handle paise/decimal part
    if ($decimal > 0) {
        $decimal_str = "";
        if ($decimal < 20) {
            $decimal_str = $ones[$decimal];
        } else {
            $decimal_str = $tens[floor($decimal / 10)] . " " . $ones[$decimal % 10];
        }
        $str .= " and " . trim($decimal_str) . " Paise";
    }

    return $str . " Only";
}
?>

<style>
    /* Mobile & Tablet */
    @media (max-width: 768px) {

    /* Header section */
    #invoice .d-flex.justify-content-between,
    #invoice .d-flex.align-items-start.justify-content-between {
        flex-direction: column !important;
        gap: 15px !important;
    }

    .invoice-logo {
        max-width: 180px;
        width: 100%;
        height: auto;
    }

    /* Company details */
    .text-end {
        text-align: left !important;
    }

    /* Bill to + invoice details */
    .invoice_table {
        width: 100% !important;
        margin-top: 15px;
        font-size: 14px !important;
    }

    .invoice_table td {
        display: block;
        width: 100%;
        padding: 5px 0 !important;
    }

    .invoice_table tr {
        display: block;
        margin-bottom: 10px;
        border-bottom: 1px solid #eee;
    }

    /* User selection */
    #bill_to_user {
        width: 100%;
    }

    /* Table */
    .table-responsive {
        overflow-x: auto;
    }

    .table {
        min-width: 750px;
    }

    /* Remove fixed width */
    th.inv-table {
        width: auto !important;
    }

    /* Total section */
    .total-table {
        width: 100%;
    }

    .total-table td {
        padding: 8px !important;
    }

    /* Buttons */
    #addItem,
    .btn-success {
        width: 100%;
        margin-bottom: 10px;
    }

    /* Modal form */
    .modal-body .row > div {
        width: 100%;
    }

    /* Footer */
    .pdf-footer {
        text-align: center;
        font-size: 12px;
    }
    }

    /* Small Mobile */
    @media (max-width: 576px) {

    .p-20 {
        padding: 15px !important;
    }

    .px-20 {
        padding-left: 10px !important;
        padding-right: 10px !important;
    }

    .divider {
        font-size: 18px !important;
    }

    .invoice_table {
        font-size: 13px !important;
    }

    h6.text-xl {
        font-size: 16px !important;
    }

    .text-sm {
        font-size: 12px !important;
    }
    }
    /* Hide everything by default when printing */
@media print {
    body * {
        /*color: #000;*/
        visibility: hidden;
        /* Optional: remove margins/padding that browsers add */
        margin: 0 !important;
        padding: 0 !important;
    }

    /* Only show the invoice and its children */
    #invoice,
    #invoice * {
        visibility: visible;
    }

    /* Position it properly on the printed page */
    #invoice {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        /* Optional: better control over page breaks */
        page-break-inside: avoid;
    }
    
    /* Hide buttons, modals, sidebars etc. (extra safety) */
    .card-header,
    .modal,
    .navbar-header,
    .sidebar,
    .d-footer,
    .titlesec,
    button[onclick*="printInvoice"],
    button[onclick*="downloadPDF"] {
        display: none !important;
    }
    .col-sec{
        width:100% !important;
        flex: 0 0 auto;
    }
    .pdf-footer {
        position: fixed !important;  /* ← fixed instead of absolute */
        bottom: 0mm !important;     /* matches the @page bottom margin */
        left: 0mm;
        right: 0mm;
        width: -webkit-fill-available !important;   /* = left + right margin */
        text-align: center;
        padding-top: 8px;
        border-top: 1px solid #dde2e6;
        font-size: 11px;
        color: #555;
    }
}

 .col-sec{
        width:100%;
        flex: 0 0 auto;
    }
    .invoice_table {
        font-size:16px !important;
        width:auto;
    }
    .total-table td {
        border-bottom: 1px solid #dde2e6;
    }
    /* .invoice-logo {
        width: 300px;
        height: auto;
    } */
    .divider {
        display: flex;
        align-items: center;
        margin-top:1rem;
    }
    .divider::before,
    .divider::after {
        content: "";
        flex: 1;
        border-bottom: 1px solid #ccc;
    }
    .divider:not(:empty)::before {
        margin-right: .5em;
    }
    .divider:not(:empty)::after {
        margin-left: .5em;
    }
    .border-footer{
        border-top: 1px solid #dde2e6;
    }
    th.inv-table {
    border-collapse: collapse;
    background: #f5f6f7;
    border-top: 1px solid #dde2e6;
    border-left: 1px solid #dde2e6;
    width:150px;
    }
    td.inv-table{
        border-left: 1px solid #dde2e6;
    }
    th.inv-table:last-child,
    td.inv-table:last-child {
        border-right: 1px solid #dde2e6;
    }
    #invoice {
        background: #fff;
    }
    table {
        width: 100%;
        border-collapse: collapse;
    }
    .invoice-logo {
        max-width: 250px;
    }
    
    .w-10{
        width: 10% !important;
    }
    
    .pdf-mode * {
        color: #000 !important;
    }
    .pdf-mode body {
        color: #000 !important;
    }
    .pdf-mode .shadow-4,
    .pdf-mode .border,
    .pdf-mode .radius-8 {
        box-shadow: none !important;
        border: none !important;
        border-radius: 0 !important;
    }
    .pdf-mode .pdf-full-width {
        width: 100% !important;
        max-width: 100% !important;
        flex: 0 0 100% !important;
    }
    .pdf-mode .pdf-footer {
        margin-top: 70% !important;
        padding-top: 10px;
        bottom:10px;
    }
    .pdf-mode {
    font-size: 10px !important;
    }
    .pdf-mode p,
    .pdf-mode span,
    .pdf-mode td,
    .pdf-mode th,
    .pdf-mode div,
    .pdf-mode li {
        font-size: 10px !important;
        line-height: 1.4 !important;
    }
    /* Headings slightly larger */
    .pdf-mode h1 { font-size: 20px !important; }
    .pdf-mode h2 { font-size: 18px !important; }
    .pdf-mode h3 { font-size: 16px !important; }
    .pdf-mode h4,
    .pdf-mode h5,
    .pdf-mode h6 { font-size: 12px !important; }
    .pdf-mode .divider { font-size: 16px !important; }

    /* Tables tighter */
    .pdf-mode table td,
    .pdf-mode table th {
        padding: 8px !important;
    }
    .pdf-mode .invoice_table td,
    .pdf-mode .invoice_table th {
        padding: 2px 8px !important;
    }
    .pdf-mode .total-table td,
    .pdf-mode .total-table th {
        padding: 4px 8px !important;
    }
    .pdf-mode .bill_to {
        margin-bottom: 4px !important;
    }
    .pdf-mode #invoice {
    display: flex;
    flex-direction: column;
    }
    .pdf-mode #invoice > div {
        flex: 1;
        display: flex;
        flex-direction: column;
    }
    .pdf-mode .footer-text {
        margin-top: auto !important;
    }
    .pdf-mode .invoice-logo {
        max-width: 200px !important;
    }
    .pdf-mode .footer-logo{
        margin-bottom: 0 !important;
    }
    .pdf-mode .col-sec{
        width:100% !important;
        flex: 0 0 auto;
    }
    .btn-purple, .btn-purple:hover, .btn-purple:focus, .btn-purple:active {
        background-color: #6f42c1;
        color: #fff;
        border: none;
    }
</style>

<?php
    $company_sql = "select * from company";
    $company_fetch = $conn->query($company_sql);
    $company_row = $company_fetch->fetch_assoc();


    // Get active symbol
    $result1 = $conn->query("SELECT symbol FROM currencies WHERE is_active = 1 LIMIT 1");
    $symbol = "$"; // default
    if ($row1 = $result1->fetch_assoc()) {
        $symbol = $row1['symbol'];
    }
?>

<div class="dashboard-main-body">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
        <a class="cursor-pointer fw-bold titlesec" onclick="history.back()"><span class="fa fa-arrow-left"></span>&nbsp; Back</a>    
        <h6 class="fw-semibold mb-0 titlesec">New Invoice</h6>
        <a class="cursor-pointer fw-bold visibility-hidden" onclick="history.back()"><span class="fa fa-arrow-left"></span>&nbsp; Back</a> 
    </div>

    <div class="card">
        <form method="POST">
            <div class="card-body py-40">
                <div class="row justify-content-center" id="invoice">
                    <div class="col-sec">
                        <div class="shadow-4 border radius-8">
                            <div class="p-20">
                                <div class="d-flex flex-column flex-md-row justify-content-between gap-3 mb-3">
                                    <div class="align-content-end">
                                        <img src="uploads/company_logo/<?php echo $logo; ?>" alt="site logo" class="invoice-logo">
                                    </div>

                                    <div class="text-end">
                                        <h6 class="mb-0 text-xl"><?php echo $company_row['full_name']; ?></h6>
                                        <p class="mb-0 text-sm">
                                            <?php echo $company_row['address']; ?>,
                                            <?php echo $company_row['city']; ?>,
                                            <?php echo $company_row['state']; ?>,
                                            <?php echo $company_row['zip_code']; ?>,
                                            <?php echo $company_row['country']; ?>
                                        </p>
                                        <p class="mb-0 text-sm"><?php echo $company_row['phone_no']; ?></p>
                                        <p class="mb-0 text-sm"><?php echo $company_row['website']; ?></p>
                                        <p class="mb-0 text-sm">GSTIN: <?php echo $company_row['gst_in']; ?></p>
                                    </div>
                                </div>
                                <div class="text-center">
                                    <p class="divider text-xl">Invoice</p>
                                </div>
                                <?php
                                    $user_list = mysqli_query($conn, "
                                        SELECT id, business_name
                                        FROM users
                                        ORDER BY business_name ASC
                                    ");
                                ?>
                                <div class="d-flex flex-column flex-md-row align-items-start justify-content-between gap-3 mt-3">
                                    <!-- <div>
                                            <p class="text-md mb-0 bill_to">Bill To:</p>
                                        <h6 class="text-md mb-0 bill_to"><?php echo $rows['business_name']; ?></h6>
                                        <p class="text-md mb-0 bill_to"><?php echo $rows['address']; ?></p>
                                        <p class="text-md mb-0 bill_to"><?php echo $rows['city']; ?> <?php echo $rows['pincode']; ?></p>
                                        <p class="text-md mb-0 bill_to"><?php echo $rows['state']; ?>, <?php echo $rows['country']; ?></p>
                                        <p class="text-md mb-0 bill_to">GSTIN: <?php echo $rows['gst_in']; ?></p>
                                    </div> -->
                                
                                    <div>
                                            <p class="text-md mb-0 bill_to">Bill To:</p>
                                            <div class="d-flex gap-2 mb-2">
                                                <select class="p-6 border" id="bill_to_user" name="user_id">
                                                    <option value="">Select User</option>
                                                    <?php while($u = mysqli_fetch_assoc($user_list)) { ?>
                                                        <option value="<?= $u['id']; ?>">
                                                            <?= htmlspecialchars($u['business_name']); ?>
                                                        </option>
                                                    <?php } ?>
                                                </select>

                                                <button type="button"
                                                        class="btn btn-primary btn-sm"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#addUserModal">
                                                    + New User
                                                </button>
                                            </div>
                                        
                                        <div id="bill_to_details">
                                            <h6 class="text-md mb-0 bill_to" id="business_name"></h6>
                                            <p class="text-md mb-0 bill_to" id="address"></p>
                                            <p class="text-md mb-0 bill_to" id="city_state_zip"></p>
                                            <p class="text-md mb-0 bill_to" id="gstin"></p>
                                        </div>
                                    </div>
                                    <table class="invoice_table text-start mt-10">
                                        <tr>
                                            <td class="pe-64 p-4 fw-semibold">Invoice#</td>
                                            <td class="text-md p-4 editable" data-field="invoice_id">
                                                <input type="text" class="border" name="invoice_id">
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="pe-64 p-4 fw-semibold">Invoice Date</td>
                                            <td class="text-md p-4 editable" data-field="created_on"><input type="date" class="border w-100"></td>
                                        </tr>
                                        <tr>
                                            <td class="pe-64 p-4 fw-semibold">Terms</td>
                                            <td class="text-md p-4 editable" data-field="payment_method"><input type="text" class="border" name="terms"></td>
                                        </tr>
                                        <tr>
                                            <td class="pe-64 p-4 fw-semibold">Status</td>
                                            <td class="text-md p-4 editable" data-field="status"><input type="text" class="border" name="status"></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>

                            <div class="py-28 px-20">
                                <div class="table-responsive scroll-sm">
                                    <table class="table mb-0">
                                        <thead>
                                            <tr>
                                                <th class="w-10 inv-table text-center">S.No</th>
                                                <th class="inv-table">Item</th>
                                                <th class="text-end inv-table">Rate</th>
                                                <th class="text-end inv-table">Tax</th>
                                                <th class="text-end inv-table">Tax Amount</th>
                                                <th class="text-end inv-table">Amount</th>
                                                <th class="w-10 text-center inv-table">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody id="invoiceItems">
                                            <tr class="invoice-row">
                                                <td class="inv-table text-center serial-no">1</td>
                                                <td class="inv-table">
                                                    <input type="text" class="item-name w-100 border-0">
                                                </td>
                                                <td class="inv-table">
                                                    <input type="number" class="price-input w-100">
                                                </td>
                                                <td class="inv-table">
                                                    <select class="tax-select w-100">
                                                        <option value="0">Select Tax</option>
                                                        <?php
                                                        $taxes = mysqli_query($conn,"SELECT id,tax_name,rate FROM taxes");
                                                        while($tax = mysqli_fetch_assoc($taxes)){
                                                        ?>
                                                            <option value="<?= $tax['rate'] ?>">
                                                                <?= $tax['tax_name'] ?> (<?= $tax['rate'] ?>%)
                                                            </option>
                                                        <?php } ?>
                                                    </select>
                                                </td>
                                                <td class="inv-table text-end tax-amount">
                                                    0.00
                                                </td>
                                                <td class="inv-table text-end line-total">
                                                    0.00
                                                </td>

                                                <td class="inv-table text-center align-content-center text-danger">
                                                    <button type="button" class="fa fa-trash remove-row">
                                                    </button>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    <div class="mt-3">
                                        <button type="button"
                                                class="btn btn-primary"
                                                id="addItem">
                                            + Add Item
                                        </button>
                                    </div>
                                </div>

                                    <?php
                                    // Add-on services table
                                    if (!empty($row['addon_service'])) {
                                        $addon_ids = explode(',', $row['addon_service']);
                                        $addon_ids = array_map('intval', $addon_ids); // sanitize IDs

                                        if (!empty($addon_ids)) {
                                            $addon_id_list = implode(',', $addon_ids);
                                            $addon_query = "SELECT name, cost FROM `add-on-service` WHERE id IN ($addon_id_list)";
                                            $addon_result = $conn->query($addon_query);

                                            if ($addon_result->num_rows > 0) {
                                                echo '
                                                <table class="table table-bordered w-100">
                                                    <tbody>';

                                                while ($addon_row = $addon_result->fetch_assoc()) {
                                                    echo "<tr>
                                                            <td class='w-25'>" . htmlspecialchars($addon_row['name']) . "</td>
                                                            <td class='w-25 editable'>" . htmlspecialchars($symbol) . " " . number_format($row['addon_price'], 2) . "</td>
                                                            <td class='w-25 editable'>" . htmlspecialchars($symbol) . " " . htmlspecialchars($row['addon_gst']) . "</td>
                                                            <td class='w-25 text-end'>
                                                                <span class='text-primary-light'>"
                                                                . htmlspecialchars($symbol) . " " . number_format(floatval($row['addon_price']) + floatval($row['addon_gst']), 2) .
                                                                "</span>
                                                            </td>
                                                        </tr>";
                                                    }
                                                echo '</tbody>
                                                </table>';
                                            }
                                        }
                                    }
                                    ?>
                                    
                                    <div class="d-flex flex-wrap justify-content-end gap-3">
                                        <table class="invoice_table text-end mt-10 total-table">
                                            <tbody>
                                                <tr>
                                                    <td class="pe-64 p-8 fw-semibold">
                                                        <span class="text-primary-light">Total</span>
                                                    </td>
                                                    <td class="p-8">
                                                        <span class="text-primary-light" id="grandTotal">
                                                            0.00
                                                        </span>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>

                                    <input type="hidden" name="custom_invoice" id="custom_invoice">
                                    <input type="hidden" name="total_gst" id="total_gst">
                                    <input type="hidden" name="total_amount" id="total_amount">
                                    
                                    <input type="submit" class="btn btn-success mt-3" value="Save Invoice" name="save_invoice">
                                <div class="text-center border-footer mt-20 pt-20 pdf-footer">
                                    <p class="d-inline">Crafted with ease using</p> 
                                    <img src="uploads/company_logo/<?php echo $logo; ?>" class="footer-logo" alt="Lufera Logo" class="mb-4" style="margin-bottom: 6px; max-width: 120px;">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">

            <form id="addUserForm">

                <div class="modal-header">
                    <h5 class="modal-title">Add New User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label>Business Name</label>
                            <input type="text" name="business_name" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Email</label>
                            <input type="email" name="email" class="form-control">
                            <input type="hidden" name="username">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Phone</label>
                            <input type="text" name="phone" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>GSTIN</label>
                            <input type="text" name="gst_in" class="form-control">
                        </div>
                        <div class="col-md-12 mb-3">
                            <label>Address</label>
                            <textarea name="address" class="form-control"></textarea>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label>City</label>
                            <input type="text" name="city" class="form-control">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label>State</label>
                            <input type="text" name="state" class="form-control">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label>Pincode</label>
                            <input type="text" name="pincode" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-success">
                        Save User
                    </button>
                </div>

            </form>

        </div>
    </div>
</div>
<script>

$(document).on('change keyup', '.price-input, .tax-select', function () {

let row = $(this).closest('.invoice-row');

let price = parseFloat(row.find('.price-input').val()) || 0;

let taxRate = parseFloat(row.find('.tax-select').val()) || 0;

let taxAmount = (price * taxRate) / 100;

let totalAmount = price + taxAmount;

row.find('.tax-amount').text(
    taxAmount.toFixed(2) + ' (' + taxRate + '%)'
);

row.find('.line-total').text(
    totalAmount.toFixed(2)
);

calculateGrandTotal();
}); 
</script>
<script>
$('#bill_to_user').on('change', function() {
    let user_id = $(this).val();
    if(user_id == '')
    {
        $('#bill_to_details').html('');
        return;
    }
    $.ajax({
        url: 'get_user_details.php',
        type: 'POST',
        data: {
            user_id: user_id
        },
        dataType: 'json',
        success: function(response)
        {
            $('#business_name').html(response.business_name);

            $('#address').html(response.address);

            $('#city_state_zip').html(
                response.city + ', ' +
                response.state + ' - ' +
                response.pincode
            );

            $('#gstin').html(
                '<strong>GSTIN:</strong> ' + response.gst_in
            );
        }
    });

});
</script>
<script>

const taxOptions = `
<option value="0">Select Tax</option>

<?php
$taxes = mysqli_query($conn,"SELECT id,tax_name,rate FROM taxes");

while($tax = mysqli_fetch_assoc($taxes)){
?>
<option value="<?= $tax['rate'] ?>">
    <?= addslashes($tax['tax_name']) ?> (<?= $tax['rate'] ?>%)
</option>
<?php } ?>
`;

$('#addItem').click(function(){

    let rowCount = $('.invoice-row').length + 1;

    let row = `
    <tr class="invoice-row">

        <td class="inv-table text-center serial-no">
            ${rowCount}
        </td>

        <td class="inv-table">
            <input type="text" class="item-name w-100 border-0">
        </td>

        <td class="inv-table">
            <input type="number" class="price-input w-100">
        </td>

        <td class="inv-table">
            <select class="tax-select w-100">
                ${taxOptions}
            </select>
        </td>

        <td class="inv-table text-end tax-amount">
            0.00
        </td>

        <td class="inv-table text-end line-total">
            0.00
        </td>
        <td class="inv-table text-center align-content-center text-danger">
            <button type="button" class="fa fa-trash remove-row">
            </button>
        </td>
    </tr>
    `;

    $('#invoiceItems').append(row);

});

document.addEventListener("DOMContentLoaded", function () {
        const emailInput = document.querySelector('input[name="email"]');
        const usernameInput = document.querySelector('input[name="username"]');

        emailInput.addEventListener("input", function () {
            const emailValue = emailInput.value;
            const usernamePart = emailValue.split("@")[0];
            usernameInput.value = usernamePart;
        });
    });

$(document).on(
'keyup change',
'.price-input,.tax-select',
function(){

    let row = $(this).closest('.invoice-row');

    let price =
        parseFloat(
            row.find('.price-input').val()
        ) || 0;

    let taxRate =
        parseFloat(
            row.find('.tax-select').val()
        ) || 0;

    let taxAmount =
        price * taxRate / 100;

    let amount =
        price + taxAmount;

    row.find('.tax-amount')
       .text(taxAmount.toFixed(2));

    row.find('.line-total')
       .text(amount.toFixed(2));

    calculateGrandTotal();
});

function calculateGrandTotal(){

let grandTotal = 0;
let totalGST = 0;
let invoiceItems = [];

$('.invoice-row').each(function(){

    let item = $(this).find('.item-name').val() || '';

    let rate = parseFloat(
        $(this).find('.price-input').val()
    ) || 0;

    let tax = parseFloat(
        $(this).find('.tax-select').val()
    ) || 0;

    let taxAmount = (rate * tax) / 100;

    let amount = rate + taxAmount;

    totalGST += taxAmount;
    grandTotal += amount;

    invoiceItems.push({
        item: item,
        rate: rate,
        tax: tax,
        tax_amount: taxAmount,
        amount: amount
    });
});

$('#grandTotal').html(
    '<?= htmlspecialchars($symbol) ?> ' +
    grandTotal.toFixed(2)
);

// Hidden fields
$('#custom_invoice').val(
    JSON.stringify(invoiceItems)
);

$('#total_gst').val(
    totalGST.toFixed(2)
);

$('#total_amount').val(
    grandTotal.toFixed(2)
);
}

$(document).on('click', '.remove-row', function () {

if ($('.invoice-row').length == 1) {
    alert('At least one row is required.');
    return;
}

$(this).closest('.invoice-row').remove();

// Re-number rows
$('.invoice-row').each(function(index) {
    $(this).find('.serial-no').text(index + 1);
});

calculateGrandTotal();
});


$('#addUserForm').submit(function(e){

e.preventDefault();

$.ajax({
    url: 'invoice-add-user.php',
    type: 'POST',
    data: $(this).serialize(),
    dataType: 'json',

    success: function(res){

        if(res.status === 'success'){

            $('#bill_to_user').append(
                `<option value="${res.user.id}" selected>
                    ${res.user.business_name}
                </option>`
            );

            $('#bill_to_user').trigger('change');

            $('#addUserModal').modal('hide');

            $('#addUserForm')[0].reset();

            alert('User Added Successfully');
        }
        else{
            alert(res.message);
        }
    }
});

});
</script>
<?php include './partials/layouts/layoutBottom.php' ?>