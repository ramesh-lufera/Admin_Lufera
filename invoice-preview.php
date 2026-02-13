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
?>
<?php $script = '<script>
                    function printInvoice() {
                        // Optional: You can still add pdf-mode if you want the same font/color tweaks
                        document.body.classList.add("pdf-mode");
                    
                        window.print();
                    
                        // Restore (only needed if you added pdf-mode)
                        document.body.classList.remove("pdf-mode");
                    }
                </script>';?>
<?php
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
/* Hide everything by default when printing */
@media print {
    body * {
        color: #000;
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
    td.inv-table{
        border: 1px solid #000 !important;
    }
     th.inv-table {
        border: 1px solid #000 !important;
    }
    .total-table td{
        border-bottom: 1px solid #000 !important;
    }
    .border-footer{
        border-top: 1px solid #000 !important;
    }
    .divider::before,
    .divider::after {
        border-bottom: 1px solid #000;
    }
}

 .col-sec{
        width:66.66666667%;
        flex: 0 0 auto;
    }
    .invoice_table {
        font-size:16px !important;
        width:auto;
    }
    .total-table td {
        border-bottom: 1px solid #dde2e6;
    }
    .invoice-logo {
        width: 300px;
        height: auto;
    }
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
    
    
    
    .pdf-mode * {
        color: #000 !important;
        font-family: system-ui, sans-serif !important;
    }
    .pdf-mode body {
        color: #000 !important;
        font-family: system-ui, sans-serif !important;
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
</style>

<?php
    $company_sql = "select * from company";
    $company_fetch = $conn->query($company_sql);
    $company_row = $company_fetch->fetch_assoc();

    $invoice_id = $_GET['id'];
    $type = $_GET['type'] ?? 'normal';

     if ($type === 'renewal') {
        // ✅ Fetch from renewal_invoices when renewal invoice
        $invoice = "
            SELECT
                r.*,
                CASE 
                    WHEN r.type = 'package' THEN p.package_name
                    WHEN r.type = 'product' THEN pr.name
                    ELSE r.plan
                END AS plan_name
            FROM renewal_invoices r
            LEFT JOIN package p ON (r.type = 'package' AND r.plan = p.id)
            LEFT JOIN products pr ON (r.type = 'product' AND r.plan = pr.id)
            WHERE r.invoice_id = '$invoice_id'
        ";
    } else {
        $invoice = "
            SELECT
                orders.*,
                CASE 
                    WHEN orders.type = 'package' THEN package.package_name
                    WHEN orders.type = 'product' THEN products.name
                    ELSE orders.plan
                END AS plan_name
                FROM orders
                LEFT JOIN package ON (orders.type = 'package' AND orders.plan = package.id)
                LEFT JOIN products ON (orders.type = 'product' AND orders.plan = products.id)
            where invoice_id = $invoice_id
        ";
    }

    $result = $conn->query($invoice);
    $row = $result->fetch_assoc();
    $user_id = $row['user_id'];
    $id = $row['id'];
    
    $userId = $_SESSION['user_id'];
    $session_user = "select * from users where id = $userId";
    $result2 = $conn->query($session_user);
    $row2 = $result2->fetch_assoc();
    
    $user = "select * from users where id = '$user_id'"; 
    $results = $conn->query($user);
    $rows = $results->fetch_assoc();
    $user_ids = $rows['email'];

    function generatePaymentID($conn) {
        do {
            $randomNumber = str_pad(mt_rand(0, 99999999), 8, '0', STR_PAD_LEFT);
            $paymentID = "L_" . $randomNumber;
    
            // Check uniqueness in DB
            $check = mysqli_query($conn, "SELECT payment_id FROM record_payment WHERE payment_id = '$paymentID'");
        } while (mysqli_num_rows($check) > 0);
    
        return $paymentID;
    }

    if (isset($_POST['save'])) {
        $order_id = $_POST['order_id'];
        $invoice_no = $_POST['invoice_no'];
        $payment_method = $_POST['payment_method'];
        $amount = $_POST['amount'];
        $payment_made = $_POST['payment_made'];
        $total_amount = floatval($amount) + floatval($payment_made);
        $created_at = date("Y-m-d H:i:s");
        $remarks = $_POST['remarks'];
        $balance_due = $_POST['balance_due'];
        $payment_id = generatePaymentID($conn);

        $sql = "INSERT INTO record_payment (payment_id, orders_id, invoice_no, payment_method, amount, balance, remarks, paid_date) 
                        VALUES ('$payment_id', '$order_id', '$invoice_no', '$payment_method', '$amount', '$balance_due', '$remarks', '$created_at')";
            
            if (mysqli_query($conn, $sql)) {
                logActivity(
                    $conn,
                    $loggedInUserId,
                    "Invoice",                   // module
                    "Record payment created successfully - $payment_id"  // description
                  );

                 if ($type === 'renewal') {
                    // 👈 Renewal Payment Update
                    $updateQuery = "UPDATE renewal_invoices 
                                    SET payment_made = $total_amount, balance_due = $balance_due 
                                    WHERE invoice_id = '$invoice_id'";
                } else {
                    $updateQuery = "UPDATE orders
                        SET payment_made = $total_amount, balance_due = $balance_due
                        WHERE invoice_id = '$invoice_id'";
                }
                mysqli_query($conn, $updateQuery);
                
                echo "
                <script>
                    Swal.fire({
                        title: 'Success!',
                        text: 'Payment Record Created Successfully.',
                        confirmButtonText: 'OK',
                        allowOutsideClick: false
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.history.back();
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

    // Get active symbol
    $result1 = $conn->query("SELECT symbol FROM currencies WHERE is_active = 1 LIMIT 1");
    $symbol = "$"; // default
    if ($row1 = $result1->fetch_assoc()) {
        $symbol = $row1['symbol'];
    }

    /**
     * Frozen invoice snapshot:
     * - When balance_due becomes 0.00 we want the invoice to stop reflecting
     *   future DB changes in related tables.
     * - We assume an `invoice_snapshot` LONGTEXT column exists on both
     *   `orders` and `renewal_invoices` tables, containing JSON.
     * - If that snapshot exists and balance_due == 0 we prefer the stored data.
     */
    $invoiceSnapshot = null;
    $useSnapshot = false;
    if (!empty($row['invoice_snapshot']) && isset($row['balance_due']) && (float)$row['balance_due'] == 0.0) {
        $decodedSnapshot = json_decode($row['invoice_snapshot'], true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decodedSnapshot)) {
            $invoiceSnapshot = $decodedSnapshot;
            $useSnapshot = true;

            if (isset($invoiceSnapshot['company'])) {
                $company_row = $invoiceSnapshot['company'];
            }
            if (isset($invoiceSnapshot['order'])) {
                $row = $invoiceSnapshot['order'];
            }
            if (isset($invoiceSnapshot['user'])) {
                $rows = $invoiceSnapshot['user'];
            }
            if (isset($invoiceSnapshot['currency_symbol'])) {
                $symbol = $invoiceSnapshot['currency_symbol'];
            }
        }
    }
?>


<?php
// ADMIN approves → Notify USER + Send PDF Invoice
if (isset($_POST['send_invoice'])) {

    $orderId = $_POST['order_id'];
    $toAddress = $_POST['toAddress'];
    // For Notifications..
    date_default_timezone_set('Asia/Kolkata');

    // Get user_id for notification
    $res = $conn->query("SELECT user_id FROM orders WHERE id = $orderId");
    $user = $res->fetch_assoc();
    $IdFromOrders = $user['user_id'];

    // Get matching user_id from users table
    $resUser = $conn->query("SELECT user_id FROM users WHERE id = $IdFromOrders");
    $userRow = $resUser->fetch_assoc(); // fetch the row
    $userId = $userRow['user_id']; // matched user_id from users table

    // Add notification
    $msg = "Your payment has been approved.";
    $createdAt = date('Y-m-d H:i:s');
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, message, n_photo, created_at) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $userId, $msg, $photo, $createdAt);
    $stmt->execute();

    // Get order + user details
    $res = $conn->query("SELECT o.invoice_id, o.plan, o.amount, o.created_on, u.email, u.first_name, u.last_name 
                         FROM orders o 
                         INNER JOIN users u ON o.user_id = u.id 
                         WHERE o.id = $orderId");
    $order = $res->fetch_assoc();
    $userEmail = $order['email'];
        $userName  = $order['first_name'] . " " . $order['last_name'];
        $planName  = $order['plan'];
        $invoiceId = $order['invoice_id'];
        $amount    = $order['amount'];
        $created_on = $order['created_on'];
        
        echo "
                <script>
                    Swal.fire({
                        title: 'Processing...',
                        text: 'Please wait while we sending your invoice.',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                            const spinner = document.querySelector('.swal2-loader');
                            if (spinner) {
                                spinner.style.borderColor = '#fec700 transparent #fec700 transparent';
                            }
                        }
                    });
                </script>";
                // flush response so browser shows the loader instantly
                ob_flush(); flush();

    // ───────────────────────────────────────────────
    //  1. Prepare invoice HTML (same as displayed)
    // ───────────────────────────────────────────────
    ob_start();
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Invoice #<?php echo $invoice_id; ?></title>
        <link href="https://admin2.luferatech.com/assets/css/lib/bootstrap.min.css" rel="stylesheet">
        <link href="https://admin2.luferatech.com/assets/css/style.css" rel="stylesheet">
        <style>
            .pdf-mode * {
        color: #000 !important;
        font-family: system-ui, sans-serif !important;
    }
    .pdf-mode body {
        color: #000 !important;
        font-family: system-ui, sans-serif !important;
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
        </style>
    </head>
    <body class="pdf-mode">
        <div class="row justify-content-center" id="invoice">
                <div class="col-sec">
                    <div class="shadow-4 border radius-8">
                        <div class="p-20">
                            <div class="d-flex justify-content-between gap-3 row">
                                <div class="align-content-end col-lg-6">
                                    <img src="https://admin2.luferatech.com/uploads/company_logo/<?php echo $company_row['logo']; ?>" alt="site logo" class="invoice-logo">
                                </div>

                                <div class="text-end col-lg-6">
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
                                <?php if($row['balance_due'] != "0.00" ){ ?> 
                                    <p class="divider text-xl">Proforma Invoice</p>
                                <?php } else {?>
                                    <p class="divider text-xl text-danger">Tax Invoice</p>
                                <?php } ?>
                                
                            </div>
                            <div class="d-flex align-items-start justify-content-between gap-3 mt-3 row">
                                <div class="col-lg-6">
                                    <p class="text-md mb-0 bill_to">Bill To:</p>
                                    <h6 class="text-md mb-0 bill_to"><?php echo $rows['business_name']; ?></h6>
                                    <p class="text-md mb-0 bill_to"><?php echo $rows['address']; ?></p>
                                    <p class="text-md mb-0 bill_to"><?php echo $rows['city']; ?> <?php echo $rows['pincode']; ?></p>
                                    <p class="text-md mb-0 bill_to"><?php echo $rows['state']; ?>, <?php echo $rows['country']; ?></p>
                                    <p class="text-md mb-0 bill_to">GSTIN: <?php echo $rows['gst_in']; ?></p>
                                </div>
                                <div class="col-lg-6">
                                    <table class="invoice_table text-start mt-10">
                                        <tr>
                                            <td class="pe-64 p-4 fw-semibold">Invoice#</td>
                                            <td class="text-md p-4"><?php echo $row['invoice_id']; ?></td>
                                        </tr>
                                        <tr>
                                            <td class="pe-64 p-4 fw-semibold">Invoice Date</td>
                                            <td class="text-md p-4"><?php echo date('d/m/Y', strtotime($row['created_on'])); ?></td>
                                        </tr>
                                        <tr>
                                            <td class="pe-64 p-4 fw-semibold">Terms</td>
                                            <td class="text-md p-4"><?php echo $row['payment_method']; ?></b></td>
                                        </tr>
                                        <tr>
                                            <td class="pe-64 p-4 fw-semibold">Status</td>
                                            <td class="text-md p-4"><?php echo $row['status']; ?></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="py-28 px-20">
                            <div class="table-responsive scroll-sm">
                                <table class="table mb-0">
                                    <thead>
                                        <th class="w-10 inv-table text-center">S.No</th>
                                        <th class="w-25 inv-table">Items</th>
                                        <th class="w-25 inv-table">Rate</th>
                                        <th class="w-25 inv-table">Tax</th>
                                        <th class="text-end w-25 inv-table">Amount</th>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $serial_no = 1;   // ← start counter
                                        ?>
                                        
                                        <!-- Main plan / package / product row -->
                                        <tr>
                                            <td class="inv-table text-center"><?= $serial_no++ ?></td>
                                            <td class="inv-table">
                                                <?php 
                                                    echo htmlspecialchars($row['plan_name']);
                                                    if ($type === 'renewal') {
                                                        echo ' <small>(Renewal)</small>';
                                                    }
                                                ?>
                                            </td>
                                            <td class="inv-table"><?= htmlspecialchars($symbol) ?> <?= number_format($row['price'], 2) ?></td>
                                            <td class="inv-table"><?= htmlspecialchars($symbol) ?> <?= number_format($row['gst'], 2) ?></td>
                                            <td class="inv-table text-end"><?= htmlspecialchars($symbol) ?> <?= number_format(floatval($row['price']) + floatval($row['gst']), 2) ?></td>
                                        </tr>

                                        <!-- Add-on services (if any) -->
                                        <?php
                                        if (!empty($row['addon_service'])) {
                                            $addon_ids = explode(',', $row['addon_service']);
                                            $addon_ids = array_map('intval', $addon_ids);

                                            if (!empty($addon_ids)) {
                                                $addon_id_list = implode(',', $addon_ids);
                                                $addon_query = "SELECT name, cost FROM `add-on-service` WHERE id IN ($addon_id_list)";
                                                $addon_result = $conn->query($addon_query);

                                                if ($addon_result && $addon_result->num_rows > 0) {
                                                    while ($addon_row = $addon_result->fetch_assoc()) {
                                                        // You might want to use addon-specific price/gst if stored per addon
                                                        // For now using same addon_price & addon_gst from main row (as in your original code)
                                                        ?>
                                                        <tr>
                                                            <td class="inv-table text-center"><?= $serial_no++ ?></td>
                                                            <td class="inv-table"><?= htmlspecialchars($addon_row['name']) ?></td>
                                                            <td class="inv-table text-end"><?= htmlspecialchars($symbol) ?> <?= number_format($row['addon_price'], 2) ?></td>
                                                            <td class="inv-table text-end"><?= htmlspecialchars($symbol) ?> <?= number_format($row['addon_gst'], 2) ?></td>
                                                            <td class="inv-table text-end">
                                                                <?= htmlspecialchars($symbol) ?> <?= number_format(floatval($row['addon_price']) + floatval($row['addon_gst']), 2) ?>
                                                            </td>
                                                        </tr>
                                                        <?php
                                                    }
                                                }
                                            }
                                        }
                                        ?>
                                    </tbody>
                                </table>
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
                                                        <td class='w-25'>" . htmlspecialchars($symbol) . " " . number_format($row['addon_price'], 2) . "</td>
                                                        <td class='w-25'>" . htmlspecialchars($symbol) . " " . htmlspecialchars($row['addon_gst']) . "</td>
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
                                
                                <div class="d-flex flex-wrap justify-content-end gap-3" style="display:flex; justify-content: flex-end">
                                    <table class="invoice_table text-end mt-10 total-table">
                                        <tbody>
                                            <!-- <?php
                                                // ✅ GST logic reused (same as above)
                                                $tax_rate = 0;
                                                $tax_name = "No Tax";

                                                if (!empty($row['plan'])) {
                                                    $plan_id = intval($row['plan']);

                                                    if ($type === 'renewal') {
                                                        $tax_query = $conn->query("
                                                            SELECT t.tax_name, t.rate
                                                            FROM package p
                                                            LEFT JOIN taxes t ON p.gst_id = t.id
                                                            INNER JOIN renewal_invoices r ON r.plan = p.id
                                                            WHERE r.plan = $plan_id
                                                            LIMIT 1
                                                        ");
                                                    } else {
                                                        $tax_query = $conn->query("
                                                            SELECT t.tax_name, t.rate
                                                            FROM package p
                                                            LEFT JOIN taxes t ON p.gst_id = t.id
                                                            INNER JOIN orders o ON o.plan = p.id
                                                            WHERE o.plan = $plan_id
                                                            LIMIT 1
                                                        ");
                                                    }

                                                    if ($tax_query && $tax_query->num_rows > 0) {
                                                        $tax_row = $tax_query->fetch_assoc();
                                                        if (!empty($tax_row['rate'])) $tax_rate = floatval($tax_row['rate']);
                                                        if (!empty($tax_row['tax_name'])) $tax_name = $tax_row['tax_name'];
                                                    }
                                                }

                                                // ✅ Compute GST amount and new totals
                                                $gst_amount = $row['subtotal'] * ($tax_rate / 100);
                                                $total_after_gst = floatval($row['subtotal']) + floatval($gst_amount) - floatval($row['discount_amount']);
                                                $balance_due = $total_after_gst - $row['payment_made'];
                                            ?> -->
                                            <?php
                                                // ✅ GST logic reused (same as above)
                                                $tax_rate = 0;
                                                $tax_name = "No Tax";

                                                if (!empty($row['plan'])) {
                                                    $plan_id = intval($row['plan']);

                                                    // ================================
                                                    //   RENEWAL TIME (PACKAGE ONLY)
                                                    // ================================
                                                    if ($type === 'renewal') {

                                                        $tax_query = $conn->query("
                                                            SELECT t.tax_name, t.rate
                                                            FROM package p
                                                            LEFT JOIN taxes t ON p.gst_id = t.id
                                                            INNER JOIN renewal_invoices r ON r.plan = p.id
                                                            WHERE r.plan = $plan_id
                                                            LIMIT 1
                                                        ");

                                                    } else {

                                                        // ================================
                                                        //   NORMAL TIME (PACKAGE + PRODUCT)
                                                        // ================================

                                                        if ($row['type'] === 'package') {

                                                            // ⭐ Normal Time → Package Logic (Already working)
                                                            $tax_query = $conn->query("
                                                                SELECT t.tax_name, t.rate
                                                                FROM package p
                                                                LEFT JOIN taxes t ON p.gst_id = t.id
                                                                INNER JOIN orders o ON o.plan = p.id
                                                                WHERE o.plan = $plan_id
                                                                LIMIT 1
                                                            ");

                                                        } else if ($row['type'] === 'product') {

                                                            // ⭐ Normal Time → PRODUCT Logic (NEW)
                                                            // products.gst column contains tax ID
                                                            $tax_query = $conn->query("
                                                                SELECT t.tax_name, t.rate
                                                                FROM products pr
                                                                LEFT JOIN taxes t ON pr.gst = t.id
                                                                INNER JOIN orders o ON o.plan = pr.id
                                                                WHERE o.plan = $plan_id
                                                                LIMIT 1
                                                            ");

                                                        }
                                                    }

                                                    // ================================
                                                    //   Fetch the tax details
                                                    // ================================
                                                    if (!empty($tax_query) && $tax_query->num_rows > 0) {
                                                        $tax_row = $tax_query->fetch_assoc();
                                                        if (!empty($tax_row['rate'])) $tax_rate = floatval($tax_row['rate']);
                                                        if (!empty($tax_row['tax_name'])) $tax_name = $tax_row['tax_name'];
                                                    }
                                                }

                                                // ================================
                                                // Compute GST + Totals
                                                // ================================
                                                $gst_amount = $row['subtotal'] * ($tax_rate / 100);
                                                $total_after_gst = floatval($row['subtotal']) + floatval($gst_amount) - floatval($row['discount_amount']);
                                                $balance_due = $total_after_gst - $row['payment_made'];
                                            ?>
                                            <!-- <tr>
                                                <td class="pe-64 p-4 fw-semibold"><?php echo htmlspecialchars($tax_name); ?> (<?php echo $tax_rate; ?>%)</td>
                                                <td class="p-4">
                                                    <span class="text-primary-light" id="currency-symbol-display"><?= htmlspecialchars($symbol) ?> <?= number_format($gst_amount, 2); ?></span>
                                                </td>
                                            </tr> -->
                                            <?php if ($row['existing_balance']) { ?>
                                                <tr>
                                                    <td class="pe-64 p-8 fw-semibold">Existing Plan</td>
                                                    <td class="p-8">
                                                        <span class="text-primary-light" id="currency-symbol-display"><?= htmlspecialchars($symbol) ?> <?php echo number_format($row['existing_balance'], 2); ?></span>
                                                    </td>
                                                </tr>
                                            <?php } ?>
                                            <tr>
                                                <td class="pe-64 p-8 fw-semibold">
                                                    <span class="text-primary-light">Total</span>
                                                </td>
                                                <td class="p-8">
                                                <span class="text-primary-light" id="currency-symbol-display">
                                                    <?= htmlspecialchars($symbol) ?>
                                                    <?= number_format(floatval($row['amount']), 2); ?>
                                                </span>

                                                </td>
                                            </tr>
                                            <?php if ($row['payment_made'] != null) { ?>
                                                <tr>
                                                    <td class="pe-64 p-8 fw-semibold">
                                                        <span class="text-primary-light">Payment Made</span>
                                                    </td>
                                                    <td class="p-8">
                                                        <span class="text-primary-light" id="currency-symbol-display"><?= htmlspecialchars($symbol) ?> <?= number_format($row['payment_made'], 2); ?></span>
                                                    </td>
                                                </tr>
                                            <?php } ?>
                                            <tr>
                                                <td class="pe-64 p-8 fw-semibold">
                                                    <span class="text-primary-light">Balance Due</span>
                                                </td>
                                                <td class="p-8">
                                                    <span class="text-primary-light" id="currency-symbol-display"><?= htmlspecialchars($symbol) ?> <?= number_format($row['balance_due'], 2); ?></span>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <p class="p-8 text-end">Total In Words <b><i style="font-style: italic;"><?= htmlspecialchars(numberToWords($row['amount'])) ?></i></b></p>
                            
                            <div class="text-center border-footer mt-40 pt-20 pdf-footer">
                                <p class="d-inline">Crafted with ease using</p> 
                                <img src="https://admin2.luferatech.com/uploads/company_logo/<?php echo $company_row['logo']; ?>" class="footer-logo" alt="Lufera Logo" class="mb-4" style="margin-bottom: 6px; width: 120px;">
                            </div>
                        
                            <?php 
                                $tc_sql = "SELECT * FROM terms_conditions where apply_for = 'invoice'";
                                $tc_result = $conn->query($tc_sql);
                                if ($tc_result->num_rows > 0) {
                                    $tc_row = $tc_result->fetch_assoc();
                                    $id = $tc_row['id'];
                                    $title = $tc_row['title'];
                                    $content = $tc_row['content'];
                                }
                                if ($tc_result->num_rows > 0) { ?>
                                <p>Terms & Conditions</p>
                                <?php echo $content; ?>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>

    </body>
    
    </html>
    <?php
    $invoiceHtml = ob_get_clean();

    // ───────────────────────────────────────────────
    //  2. Generate PDF using dompdf
    // ───────────────────────────────────────────────
    

    $options = new Options();
    $options->set('isRemoteEnabled', true);           // allow loading images from URL
    $options->set('isHtml5ParserEnabled', true);
    $options->set('defaultFont', 'DejaVu Sans');      // better support for Indian Rupee symbol

    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($invoiceHtml);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    $pdfOutput = $dompdf->output();   // binary PDF content

    // ───────────────────────────────────────────────
    //  3. Send email with PDF attachment
    // ───────────────────────────────────────────────
    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $_ENV['EMAIL_USERNAME'];
        $mail->Password   = $_ENV['GMAIL_APP_PASSWORD'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';
        $mail->Encoding = 'base64';
        // Sender
        $mail->setFrom($_ENV['EMAIL_USERNAME'], 'Lufera Infotech');

        // Recipient
        $mail->addAddress($toAddress);

        // Content
        $mail->isHTML(true);
        $mail->Subject = "Invoice #{$invoice_id} - Lufera Infotech";
        $mail->ContentType = 'text/html; charset=UTF-8';

        $mail->Body = '
                <!DOCTYPE html>
                <html>
                <head>
                <meta charset="UTF-8">
                <title>Invoice Mail</title>
                </head>
                <body style="margin:0;padding:0;background:#f5f5f5;font-family:Roboto,Arial,sans-serif;">
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background:#f5f5f5;padding:30px 0;">
                    <tr>
                    <td align="center">
                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="600" 
                            style="background:#ffffff;border:1px solid #e0e0e0;border-radius:8px;box-shadow:0 2px 6px rgba(0,0,0,0.08);overflow:hidden;">
                        
                        <!-- Header -->
                        <tr>
                            <td style="padding:20px;text-align:center;">
                            <img src="' . htmlspecialchars($_ENV['EMAIL_IMAGE_LINK']) . '" alt="Lufera Infotech Logo" style="width:150px;height:48px;display:block;margin:auto;">
                            </td>
                        </tr>

                        <!-- Divider -->
                        <tr>
                            <td style="border-top:1px solid #eaeaea;"></td>
                        </tr>

                        <!-- Main Content -->
                        <tr>
                            <td style="padding:30px 40px;text-align:left;font-size:15px;line-height:1.6;color:#101010;">
                            <p>Dear <b>' . htmlspecialchars($userName) . '</b>,</p>
                            <p>Thank you for your business. A test attachment is included with this email.</p>
                            
                            <table cellpadding="8" cellspacing="0" border="0" width="100%" style="border:1px solid #eaeaea;margin:20px 0;font-size:14px;">
                                <tr><td><b>Invoice Amount</b></td><td id="currency-symbol-display">' . htmlspecialchars($symbol) . number_format($amount) . '</td></tr>
                                <tr><td><b>Invoice No</b></td><td>' . htmlspecialchars($invoiceId) . '</td></tr>
                                <tr><td><b>Invoice Date</b></td><td>' . date('d/m/Y', strtotime($created_on)) . '</td></tr>
                            </table>

                            <p>Powered by LuferaOne.</p>
                            
                            <div style="margin:30px 0;text-align:center;">
                                <a href="' . htmlspecialchars($_ENV['EMAIL_COMMON_LINK']) . '/invoice-preview.php?id='.$invoiceId.'" 
                                style="background:#fec700;color:#101010;text-decoration:none;
                                        padding:12px 28px;border-radius:4px;font-weight:bold;display:inline-block;">
                                View Invoice
                                </a>
                            </div>

                            <p>If you have any questions, feel free to reply to this email.</p>
                            </td>
                        </tr>

                        <!-- Divider -->
                        <tr>
                            <td style="border-top:1px solid #eaeaea;"></td>
                        </tr>

                        <!-- Footer -->
                        <tr>
                            <td style="padding:20px;text-align:center;font-size:12px;color:#777;">
                            &copy; 2025 Lufera Infotech. All rights reserved.
                            </td>
                        </tr>

                        </table>
                    </td>
                    </tr>
                </table>
                </body>
                </html>
            ';

        // Attach the PDF
        $mail->addStringAttachment($pdfOutput, "Invoice_{$invoice_id}.pdf", 'base64', 'application/pdf');

        $mail->send();

        // Success message
        echo "
        <script>
            Swal.fire({ 
                icon: 'success',
                title: 'Invoice sent successfully',
                confirmButtonColor: '#3085d6'
            }).then(() => {
                window.location.href = ''; 
            }); 
        </script>";
    }
    catch (Exception $e) {
        error_log("Email error: " . $mail->ErrorInfo);
        echo "
        <script>
            Swal.fire({
                icon: 'error',
                title: 'Failed to send invoice',
                text: '" . addslashes($mail->ErrorInfo) . "'
            });
        </script>";
    }
}
?>
<div class="dashboard-main-body">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
        <a class="cursor-pointer fw-bold titlesec" onclick="history.back()"><span class="fa fa-arrow-left"></span>&nbsp; Back</a>    
        <h6 class="fw-semibold mb-0 titlesec">Invoice Preview</h6>
        <a class="cursor-pointer fw-bold visibility-hidden" onclick="history.back()"><span class="fa fa-arrow-left"></span>&nbsp; Back</a> 
    </div>

    <div class="card">
        <div class="card-header">
            <div class="d-flex flex-wrap align-items-center justify-content-end gap-2">
            <?php 
            if($row2['role'] == "1" || $row2['role'] == "2") {?>  
                <button type="button" class="btn btn-sm btn-primary radius-8 d-inline-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#exampleModal">
                    <iconify-icon icon="lucide:edit" class="text-xl"></iconify-icon>
                    Record Payment
                </button>
                <button type="button" class="btn btn-sm btn-warning radius-8 d-inline-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#emailModal">
                    <iconify-icon icon="lucide:send" class="text-xl"></iconify-icon>
                    Send Invoice
                </button>
            <?php } ?>
                <button type="button" class="btn btn-sm btn-danger radius-8 d-inline-flex align-items-center gap-1" onclick="printInvoice()">
                    <iconify-icon icon="basil:printer-outline" class="text-xl"></iconify-icon>
                    Print
                </button>
                <button type="button"
                    class="btn btn-sm btn-success radius-8 d-inline-flex align-items-center gap-1"
                    onclick="downloadPDF()">
                    <iconify-icon icon="ph:file-pdf" class="text-xl"></iconify-icon>
                    Download PDF
                </button>

            </div>
        </div>

        <div class="modal fade" id="payment" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">Payment History</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="table-responsive scroll-sm">
                            <table class="table bordered-table sm-table mb-20">
                                <thead>
                                    <tr>
                                        <th scope="col">Invoice No</th>
                                        <th scope="col">Payment Method</th>
                                        <th scope="col">Paid Amount</th>
                                        <th scope="col">Remarks</th>
                                        <th scope="col">Paid Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                        <?php 
                        $sql = "SELECT * FROM record_payment where invoice_no = '$invoice_id'";
                        $result = mysqli_query($conn, $sql);

                        if (mysqli_num_rows($result) > 0) {
                            while ($row_history = mysqli_fetch_assoc($result)) {
                                echo '<tr>
                                        <td>' . htmlspecialchars($row_history['invoice_no']) . '</td>
                                        <td>' . htmlspecialchars($row_history['payment_method']) . '</td>
                                        <td>' . htmlspecialchars($symbol) . htmlspecialchars($row_history['amount']) . '</td>
                                        <td>' . htmlspecialchars($row_history['remarks']) . '</td>
                                        <td>' . date('d/m/Y', strtotime($row_history['paid_date'])) . '</td>
                                    </tr>';
                                    }
                                } else {
                                    echo '<tr><td colspan="13" class="text-center">No users found.</td></tr>';
                                }
                            ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <form method="post">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLabel">Record Payment</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-sm-6">
                                    <div class="mb-20">
                                        <label for="" class="form-label fw-semibold text-primary-light text-sm mb-8">Invoice No: <span class="text-danger-600">*</span></label>
                                        <input type="hidden" value="<?php echo $id; ?>" name="order_id">
                                        <input type="text" class="form-control radius-8" name="invoice_no" value="<?php echo $invoice_id; ?>" <?php echo !empty($invoice_id) ? 'readonly' : ''; ?> required>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="mb-20">
                                        <label for="" class="form-label fw-semibold text-primary-light text-sm mb-8">Payment Method <span class="text-danger-600">*</span></label>
                                        <select class="form-control" name="payment_method" required <?php echo $row['balance_due'] == "0" ? 'disabled' : ''; ?> >
                                            <option value="">Select payment method</option>
                                            <option value="Cash">Cash</option>
                                            <option value="Card">Card</option>
                                            <option value="UPI">UPI</option>
                                            <option value="Bank">Bank</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="mb-20">
                                        <label for="" class="form-label fw-semibold text-primary-light text-sm mb-8">Enter Amount <span class="text-danger-600">*</span></label>
                                        
                                        <input type="hidden" class="form-control radius-8" name="payment_made" id="payment_made" value="<?php echo $row['payment_made'] ?>">

                                        <input type="text" class="form-control radius-8" name="amount" id="numericInput" required <?php echo $row['balance_due'] == "0" ? 'readonly' : ''; ?> >
                                        <small id="amountError" class="text-danger d-none">Amount cannot be greater than Balance Due.</small>

                                        <input type="hidden" class="form-control radius-8" name="balance_due" id="balance_due" value="<?php echo $row['balance_due'] ?>">
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="mb-20">
                                        <label for="" class="form-label fw-semibold text-primary-light text-sm mb-8">Remarks <span class="text-danger-600">*</span></label>
                                        <input type="text" class="form-control radius-8" name="remarks" value="" required <?php echo $row['balance_due'] == "0" ? 'readonly' : ''; ?> >
                                    </div>
                                </div>

                                <?php if ($row['balance_due'] == '0') { ?>
                                    <p class="text-danger">Payment fully paid</p>
                                    <?php } ?>
                            </div>
                        </div>
                        <div class="modal-footer d-flex align-items-center justify-content-center gap-3">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" id="submit" class="btn lufera-bg text-white" name="save" <?php echo $row['balance_due'] == "0" ? 'disabled' : ''; ?>>Save</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        

        <div class="modal fade align-content-center" id="emailModal" tabindex="-1" aria-labelledby="emailModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <form method="post">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="emailModalLabel">Send Invoice</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-sm-12">
                                    <div class="mb-20">
                                    <form method="POST">
                                        <label for="" class="form-label fw-semibold text-primary-light text-sm mb-8">Enter the Email ID you want to send: <span class="text-danger-600">*</span></label>
                                        
                                        <input type="email" class="form-control radius-8" name="toAddress" required>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer d-flex align-items-center justify-content-center gap-3">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                
                                    <input type="hidden" class="form-control radius-8" value="<?php echo $id; ?>" name="order_id">
                                    <input type="hidden" class="form-control radius-8" name="invoice_no" value="<?php echo $invoice_id; ?>" <?php echo !empty($invoice_id) ? 'readonly' : ''; ?> required>
                                <input type="submit" name="send_invoice" class="btn btn-success text-white text-sm d-flex align-items-center" value="Send">
                                    
                            </form>
                        </div>
                    </div>
                </form>
            </div>
        </div>


        <div class="card-body py-40">
            <div class="row justify-content-center" id="invoice">
                <div class="col-sec">
                    <div class="shadow-4 border radius-8">
                        <div class="p-20">
                            <div class="d-flex justify-content-between gap-3 mb-3">
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
                                <?php if($row['balance_due'] != "0.00" ){ ?> 
                                    <p class="divider text-xl">Proforma Invoice</p>
                                <?php } else {?>
                                    <p class="divider text-xl">Tax Invoice</p>
                                <?php } ?>
                                
                            </div>
                            <div class="d-flex align-items-start justify-content-between gap-3 mt-3">
                                <div>
                                    <p class="text-md mb-0 bill_to">Bill To:</p>
                                    <h6 class="text-md mb-0 bill_to"><?php echo $rows['business_name']; ?></h6>
                                    <p class="text-md mb-0 bill_to"><?php echo $rows['address']; ?></p>
                                    <p class="text-md mb-0 bill_to"><?php echo $rows['city']; ?> <?php echo $rows['pincode']; ?></p>
                                    <p class="text-md mb-0 bill_to"><?php echo $rows['state']; ?>, <?php echo $rows['country']; ?></p>
                                    <p class="text-md mb-0 bill_to">GSTIN: <?php echo $rows['gst_in']; ?></p>
                                </div>

                                <table class="invoice_table text-start mt-10">
                                    <tr>
                                        <td class="pe-64 p-4 fw-semibold">Invoice#</td>
                                        <td class="text-md p-4"><?php echo $row['invoice_id']; ?></td>
                                    </tr>
                                    <tr>
                                        <td class="pe-64 p-4 fw-semibold">Invoice Date</td>
                                        <td class="text-md p-4"><?php echo date('d/m/Y', strtotime($row['created_on'])); ?></td>
                                    </tr>
                                    <tr>
                                        <td class="pe-64 p-4 fw-semibold">Terms</td>
                                        <td class="text-md p-4"><?php echo $row['payment_method']; ?></b></td>
                                    </tr>
                                    <tr>
                                        <td class="pe-64 p-4 fw-semibold">Status</td>
                                        <td class="text-md p-4"><?php echo $row['status']; ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        <div class="py-28 px-20">
                            <div class="table-responsive scroll-sm">
                                <table class="table mb-0">
                                    <thead>
                                        <th class="w-10 inv-table text-center">S.No</th>
                                        <th class="w-25 inv-table">Item</th>
                                        <th class="w-25 inv-table">Rate</th>
                                        <th class="w-25 inv-table">Tax</th>
                                        <th class="text-end w-25 inv-table">Amount</th>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $serial_no = 1;   // ← start counter
                                        ?>
                                        
                                        <!-- Main plan / package / product row -->
                                        <tr>
                                            <td class="inv-table text-center"><?= $serial_no++ ?></td>
                                            <td class="inv-table">
                                                <?php 
                                                    echo htmlspecialchars($row['plan_name']);
                                                    if ($type === 'renewal') {
                                                        echo ' <small>(Renewal)</small>';
                                                    }
                                                ?>
                                            </td>
                                            <td class="inv-table"><?= htmlspecialchars($symbol) ?> <?= number_format($row['price'], 2) ?></td>
                                            <td class="inv-table"><?= htmlspecialchars($symbol) ?> <?= number_format($row['gst'], 2) ?></td>
                                            <td class="inv-table text-end"><?= htmlspecialchars($symbol) ?> <?= number_format(floatval($row['price']) + floatval($row['gst']), 2) ?></td>
                                        </tr>

                                        <!-- Add-on services (if any) -->
                                        <?php
                                        if (!empty($row['addon_service'])) {
                                            $addon_ids = explode(',', $row['addon_service']);
                                            $addon_ids = array_map('intval', $addon_ids);

                                            if (!empty($addon_ids)) {
                                                $addon_id_list = implode(',', $addon_ids);
                                                $addon_query = "SELECT name, cost FROM `add-on-service` WHERE id IN ($addon_id_list)";
                                                $addon_result = $conn->query($addon_query);

                                                if ($addon_result && $addon_result->num_rows > 0) {
                                                    while ($addon_row = $addon_result->fetch_assoc()) {
                                                        // You might want to use addon-specific price/gst if stored per addon
                                                        // For now using same addon_price & addon_gst from main row (as in your original code)
                                                        ?>
                                                        <tr>
                                                            <td class="inv-table text-center"><?= $serial_no++ ?></td>
                                                            <td class="inv-table"><?= htmlspecialchars($addon_row['name']) ?></td>
                                                            <td class="inv-table text-end"><?= htmlspecialchars($symbol) ?> <?= number_format($row['addon_price'], 2) ?></td>
                                                            <td class="inv-table text-end"><?= htmlspecialchars($symbol) ?> <?= number_format($row['addon_gst'], 2) ?></td>
                                                            <td class="inv-table text-end">
                                                                <?= htmlspecialchars($symbol) ?> <?= number_format(floatval($row['addon_price']) + floatval($row['addon_gst']), 2) ?>
                                                            </td>
                                                        </tr>
                                                        <?php
                                                    }
                                                }
                                            }
                                        }
                                        ?>
                                    </tbody>
                                </table>
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
                                                        <td class='w-25'>" . htmlspecialchars($symbol) . " " . number_format($row['addon_price'], 2) . "</td>
                                                        <td class='w-25'>" . htmlspecialchars($symbol) . " " . htmlspecialchars($row['addon_gst']) . "</td>
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
                                            <!-- <?php
                                                // ✅ GST logic reused (same as above)
                                                $tax_rate = 0;
                                                $tax_name = "No Tax";

                                                if (!empty($row['plan'])) {
                                                    $plan_id = intval($row['plan']);

                                                    if ($type === 'renewal') {
                                                        $tax_query = $conn->query("
                                                            SELECT t.tax_name, t.rate
                                                            FROM package p
                                                            LEFT JOIN taxes t ON p.gst_id = t.id
                                                            INNER JOIN renewal_invoices r ON r.plan = p.id
                                                            WHERE r.plan = $plan_id
                                                            LIMIT 1
                                                        ");
                                                    } else {
                                                        $tax_query = $conn->query("
                                                            SELECT t.tax_name, t.rate
                                                            FROM package p
                                                            LEFT JOIN taxes t ON p.gst_id = t.id
                                                            INNER JOIN orders o ON o.plan = p.id
                                                            WHERE o.plan = $plan_id
                                                            LIMIT 1
                                                        ");
                                                    }

                                                    if ($tax_query && $tax_query->num_rows > 0) {
                                                        $tax_row = $tax_query->fetch_assoc();
                                                        if (!empty($tax_row['rate'])) $tax_rate = floatval($tax_row['rate']);
                                                        if (!empty($tax_row['tax_name'])) $tax_name = $tax_row['tax_name'];
                                                    }
                                                }

                                                // ✅ Compute GST amount and new totals
                                                $gst_amount = $row['subtotal'] * ($tax_rate / 100);
                                                $total_after_gst = floatval($row['subtotal']) + floatval($gst_amount) - floatval($row['discount_amount']);
                                                $balance_due = $total_after_gst - $row['payment_made'];
                                            ?> -->
                                            <?php
                                                // ✅ GST logic reused (same as above)
                                                $tax_rate = 0;
                                                $tax_name = "No Tax";

                                                if (!empty($row['plan'])) {
                                                    $plan_id = intval($row['plan']);

                                                    // ================================
                                                    //   RENEWAL TIME (PACKAGE ONLY)
                                                    // ================================
                                                    if ($type === 'renewal') {

                                                        $tax_query = $conn->query("
                                                            SELECT t.tax_name, t.rate
                                                            FROM package p
                                                            LEFT JOIN taxes t ON p.gst_id = t.id
                                                            INNER JOIN renewal_invoices r ON r.plan = p.id
                                                            WHERE r.plan = $plan_id
                                                            LIMIT 1
                                                        ");

                                                    } else {

                                                        // ================================
                                                        //   NORMAL TIME (PACKAGE + PRODUCT)
                                                        // ================================

                                                        if ($row['type'] === 'package') {

                                                            // ⭐ Normal Time → Package Logic (Already working)
                                                            $tax_query = $conn->query("
                                                                SELECT t.tax_name, t.rate
                                                                FROM package p
                                                                LEFT JOIN taxes t ON p.gst_id = t.id
                                                                INNER JOIN orders o ON o.plan = p.id
                                                                WHERE o.plan = $plan_id
                                                                LIMIT 1
                                                            ");

                                                        } else if ($row['type'] === 'product') {

                                                            // ⭐ Normal Time → PRODUCT Logic (NEW)
                                                            // products.gst column contains tax ID
                                                            $tax_query = $conn->query("
                                                                SELECT t.tax_name, t.rate
                                                                FROM products pr
                                                                LEFT JOIN taxes t ON pr.gst = t.id
                                                                INNER JOIN orders o ON o.plan = pr.id
                                                                WHERE o.plan = $plan_id
                                                                LIMIT 1
                                                            ");

                                                        }
                                                    }

                                                    // ================================
                                                    //   Fetch the tax details
                                                    // ================================
                                                    if (!empty($tax_query) && $tax_query->num_rows > 0) {
                                                        $tax_row = $tax_query->fetch_assoc();
                                                        if (!empty($tax_row['rate'])) $tax_rate = floatval($tax_row['rate']);
                                                        if (!empty($tax_row['tax_name'])) $tax_name = $tax_row['tax_name'];
                                                    }
                                                }

                                                // ================================
                                                // Compute GST + Totals
                                                // ================================
                                                $gst_amount = $row['subtotal'] * ($tax_rate / 100);
                                                $total_after_gst = floatval($row['subtotal']) + floatval($gst_amount) - floatval($row['discount_amount']);
                                                $balance_due = $total_after_gst - $row['payment_made'];
                                            ?>
                                            <!-- <tr>
                                                <td class="pe-64 p-4 fw-semibold"><?php echo htmlspecialchars($tax_name); ?> (<?php echo $tax_rate; ?>%)</td>
                                                <td class="p-4">
                                                    <span class="text-primary-light" id="currency-symbol-display"><?= htmlspecialchars($symbol) ?> <?= number_format($gst_amount, 2); ?></span>
                                                </td>
                                            </tr> -->
                                            <?php if ($row['existing_balance']) { ?>
                                                <tr>
                                                    <td class="pe-64 p-8 fw-semibold">Existing Plan</td>
                                                    <td class="p-8">
                                                        <span class="text-primary-light" id="currency-symbol-display"><?= htmlspecialchars($symbol) ?> <?php echo number_format($row['existing_balance'], 2); ?></span>
                                                    </td>
                                                </tr>
                                            <?php } ?>
                                            <tr>
                                                <td class="pe-64 p-8 fw-semibold">
                                                    <span class="text-primary-light">Total</span>
                                                </td>
                                                <td class="p-8">
                                                <span class="text-primary-light" id="currency-symbol-display">
                                                    <?= htmlspecialchars($symbol) ?>
                                                    <?= number_format(floatval($row['amount']), 2); ?>
                                                </span>

                                                </td>
                                            </tr>
                                            <?php if ($row['payment_made'] != null) { ?>
                                                <tr>
                                                    <td class="pe-64 p-8 fw-semibold">
                                                        <span class="text-primary-light">Payment Made</span>
                                                    </td>
                                                    <td class="p-8">
                                                        <span class="text-primary-light" id="currency-symbol-display"><?= htmlspecialchars($symbol) ?> <?= number_format($row['payment_made'], 2); ?></span>
                                                    </td>
                                                </tr>
                                            <?php } ?>
                                            <tr>
                                                <td class="pe-64 p-8 fw-semibold">
                                                    <span class="text-primary-light">Balance Due</span>
                                                </td>
                                                <td class="p-8">
                                                    <span class="text-primary-light" id="currency-symbol-display"><?= htmlspecialchars($symbol) ?> <?= number_format($row['balance_due'], 2); ?></span>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <p class="p-8 text-end">Total In Words <b><i style="font-style: italic;"><?= htmlspecialchars(numberToWords($row['amount'])) ?></i></b></p>
                            
                            <div class="text-center border-footer mt-40 pt-20 pdf-footer">
                                <p class="d-inline">Crafted with ease using</p> 
                                <img src="uploads/company_logo/<?php echo $logo; ?>" class="footer-logo" alt="Lufera Logo" class="mb-4" style="margin-bottom: 6px; width: 120px;">
                            </div>
                        
                            <?php 
                                // Resolve Terms & Conditions, preferring frozen snapshot content when available
                                $termsContent = null;

                                if ($useSnapshot && isset($invoiceSnapshot['terms_content']) && $invoiceSnapshot['terms_content'] !== '') {
                                    $termsContent = $invoiceSnapshot['terms_content'];
                                } else {
                                    $tc_sql = "SELECT * FROM terms_conditions where apply_for = 'invoice'";
                                    $tc_result = $conn->query($tc_sql);
                                    if ($tc_result && $tc_result->num_rows > 0) {
                                        $tc_row = $tc_result->fetch_assoc();
                                        $id = $tc_row['id'];
                                        $title = $tc_row['title'];
                                        $content = $tc_row['content'];
                                        $termsContent = $content;
                                    }
                                }

                                if (!empty($termsContent)) { ?>
                                    <p>Terms & Conditions</p>
                                    <?php echo $termsContent; ?>
                                <?php }

                                // If invoice is fully paid and no snapshot stored yet, create one now
                                if ((float)$row['balance_due'] == 0.0 && empty($row['invoice_snapshot']) && !$useSnapshot) {
                                    $snapshotData = [
                                        'company'         => $company_row,
                                        'order'           => $row,
                                        'user'            => $rows,
                                        'currency_symbol' => $symbol,
                                        'terms_content'   => $termsContent,
                                    ];

                                    $snapshotJson = json_encode($snapshotData, JSON_UNESCAPED_UNICODE);

                                    if ($snapshotJson !== false) {
                                        if ($type === 'renewal') {
                                            $stmtSnap = $conn->prepare("UPDATE renewal_invoices SET invoice_snapshot = ? WHERE invoice_id = ?");
                                        } else {
                                            $stmtSnap = $conn->prepare("UPDATE orders SET invoice_snapshot = ? WHERE invoice_id = ?");
                                        }

                                        if ($stmtSnap) {
                                            $stmtSnap->bind_param('ss', $snapshotJson, $invoice_id);
                                            $stmtSnap->execute();
                                        }
                                    }

                                    // Use this snapshot for the remainder of this request
                                    $invoiceSnapshot = $snapshotData;
                                    $useSnapshot = true;
                                }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function sanitizeNumberInput(el) {
        el.value = el.value.replace(/[^0-9.]/g, '');
        el.value = el.value.replace(/(\..*)\./g, '$1');
    }
    document.getElementById('numericInput').addEventListener('input', function () {
        sanitizeNumberInput(this);
        const amount = parseFloat(this.value);
        const originalBalance = parseFloat(<?php echo json_encode($row['balance_due']); ?>);

        if (!isNaN(amount)) {
            const updatedBalance = originalBalance - amount;
            document.getElementById('balance_due').value = updatedBalance.toFixed(2);
        } else {
            // Reset if input is not a number
            document.getElementById('balance_due').value = originalBalance.toFixed(2);
        }
    });

    document.addEventListener("DOMContentLoaded", function () {
        const amountInput = document.getElementById("numericInput");
        const balanceDue = parseFloat(document.getElementById("balance_due").value);
        const errorText = document.getElementById("amountError");
        const submit = document.getElementById("submit");
        amountInput.addEventListener("input", function () {
            const enteredAmount = parseFloat(this.value);

            if (enteredAmount > balanceDue) {
                errorText.classList.remove("d-none");
                submit.disabled = true;
            }
            else {
                errorText.classList.add("d-none");
                submit.disabled = false;
            }
        });
    });

    document.addEventListener("DOMContentLoaded", function () {
        const amountInput = document.getElementById("numericInput");
        const balanceDue = parseFloat(document.getElementById("balance_due").value);
        const errorText = document.getElementById("amountError");
        const submit = document.getElementById("submit");

        amountInput.addEventListener("input", function () {
            const enteredAmount = parseFloat(this.value);

            if (!isNaN(enteredAmount) && enteredAmount > balanceDue) {
                errorText.classList.remove("d-none");
                submit.disabled = true;
            } else {
                errorText.classList.add("d-none");
                submit.disabled = false;
            }
        });
    });
</script>

<script>
function downloadPDF() {

    const invoice = document.getElementById("invoice");

    // 1️⃣ Enable PDF mode
    document.body.classList.add("pdf-mode");

    // 2️⃣ Remove layout classes
    const wrapper = invoice.querySelector(".col-lg-8");
    if (wrapper) {
        wrapper.classList.remove("col-lg-8");
        wrapper.classList.add("pdf-full-width");
    }

    const card = invoice.querySelector(".shadow-4");
    if (card) {
        card.classList.remove("shadow-4", "border", "radius-8");
    }

    const opt = {
        margin: [18, 8, 0, 8],
        filename: 'Invoice_<?php echo $invoice_id; ?>.pdf',
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: {
            scale: 2,
            useCORS: true,
            scrollY: 0
        },
        jsPDF: {
            unit: 'mm',
            format: 'a4',
            orientation: 'portrait'
        }
    };

    html2pdf()
        .set(opt)
        .from(invoice)
        .save()
        .then(() => {
            // 3️⃣ Restore UI after download
            document.body.classList.remove("pdf-mode");

            if (wrapper) {
                wrapper.classList.remove("pdf-full-width");
                wrapper.classList.add("col-lg-8");
            }

            if (card) {
                card.classList.add("shadow-4", "border", "radius-8");
            }
        });
}
</script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

<?php include './partials/layouts/layoutBottom.php' ?>