<?php
$script = '<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
           <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
           <script>
                function printInvoice() {
                    window.print();
                }

                function downloadReceipt() {
                    const { jsPDF } = window.jspdf;
                    const doc = new jsPDF({
                        orientation: "portrait",
                        unit: "mm",
                        format: "a4"
                    });
                    const element = document.querySelector(".pay-rec");

                    // Clone the pay-rec element
                    const clone = element.cloneNode(true);
                    // Style the clone to be off-screen but rendered
                    clone.style.display = "block";
                    clone.style.position = "absolute";
                    clone.style.left = "-9999px";
                    clone.style.top = "0";
                    // Append the clone to the body
                    document.body.appendChild(clone);

                    // Use html2canvas to render the cloned element
                    html2canvas(clone, {
                        scale: 2, // Increased scale for sharper fonts
                        useCORS: true, // Enable CORS for images
                        backgroundColor: "#ffffff" // Ensure white background for clarity
                    }).then(canvas => {
                        // Remove the clone from the DOM
                        document.body.removeChild(clone);

                        const imgData = canvas.toDataURL("image/png", 1.0); // High-quality PNG
                        const imgWidth = 190; // Width in mm (A4 width - margins)
                        const pageHeight = 297; // A4 height in mm
                        const imgHeight = (canvas.height * imgWidth) / canvas.width;
                        let heightLeft = imgHeight;
                        let position = 10; // Top margin in mm

                        // Add image to PDF
                        doc.addImage(imgData, "PNG", 10, position, imgWidth, imgHeight, undefined, "FAST");
                        heightLeft -= pageHeight;

                        // Add additional pages if content overflows
                        while (heightLeft >= 0) {
                            doc.addPage();
                            position = heightLeft - imgHeight;
                            doc.addImage(imgData, "PNG", 10, position, imgWidth, imgHeight, undefined, "FAST");
                            heightLeft -= pageHeight;
                        }

                        doc.save("payment_receipt.pdf");
                    }).catch(error => {
                        // Ensure the clone is removed even if an error occurs
                        if (document.body.contains(clone)) {
                            document.body.removeChild(clone);
                        }
                        console.error("Error generating PDF:", error);
                        alert("Failed to generate PDF. Please try again.");
                    });
                }
                    html2canvas(clone, { scale: 2, useCORS: true, backgroundColor: "#ffffff" }).then(canvas => {
    console.log("Canvas height:", canvas.height);
    // Rest of the code
});
           </script>';
?>

<?php include './partials/layouts/layoutTop.php' ?>
<style>
.pay-rec {
    width: 600px; /* Constrain width for PDF rendering */
    font-family: Arial, Helvetica, sans-serif; /* Web-safe font for better rendering */
    font-size: 14px; /* Slightly smaller font to fit content */
     display: none; /*Hide the pay-rec content by default */
}
.invoice_table {
    font-size: 14px !important; /* Adjust font size for table */
    width: 100%;
    background: #f2f3f6;
    border-radius: 10px;
}
.thead, .tbody {
    border: 1px solid #d3d3d3; /* Explicit border color instead of var */
}
.thead > tr > th, .tbody > td {
    padding: 10px !important; /* Reduce padding */
}
.w-40 {
    width: 40%;
}
.w-60 {
    width: 60%;
}
.text-gray {
    color: gray;
}
.border_gray {
    border-bottom: 1px solid lightgray;
}
.amt-rec {
    align-content: center;
    height: 100px; /* Reduce height */
    width: 100%;
    background: #4CAF50;
    color: #fff;
    text-align: center;
}
@media print {
    .btn, .back_btn, .pay-rec, .navbar-header, .d-footer {
        display: none !important;
    }
    .dashboard-main-body{
        display:block !important;
    }
}
</style>
<?php
$userId = $_SESSION['user_id'];

$user_sql = "select * from users where id = $userId";
$user_fetch = $conn->query($user_sql);
$user_row = $user_fetch->fetch_assoc();

$company_sql = "select * from company";
$company_fetch = $conn->query($company_sql);
$company_row = $company_fetch->fetch_assoc();

$trans_id = $_GET['id'];
$transaction = "select * from record_payment where id = $trans_id";
$result_trans = $conn->query($transaction);
$row_trans = $result_trans->fetch_assoc();
$invoice_no = $row_trans['invoice_no'];
$payment_id = $row_trans['payment_id'];
$paid_date = $row_trans['paid_date'];
$amount = $row_trans['amount'];
$payment_method = $row_trans['payment_method'];
$invoice_no = intval($invoice_no); 

$sql = "
SELECT 
    o.*, 
    CASE 
        WHEN o.type = 'product' THEN p.name
        WHEN o.type = 'package' THEN pk.package_name
    END AS plan_name
FROM orders o
LEFT JOIN products p ON (o.type = 'product' AND o.plan = p.id)
LEFT JOIN package pk ON (o.type = 'package' AND o.plan = pk.id)
WHERE o.invoice_id = $invoice_no";

$result = $conn->query($sql);
$row = $result->fetch_assoc();

$plan_name = $row['plan_name'];

// Get active symbol
$result1 = $conn->query("SELECT symbol FROM currencies WHERE is_active = 1 LIMIT 1");
$symbol = "$"; // default
if ($row1 = $result1->fetch_assoc()) {
    $symbol = $row1['symbol'];
}
?>
<?php
function numberToWords($number) {
    $words = array(
        0 => 'Zero', 1 => 'One', 2 => 'Two', 3 => 'Three', 4 => 'Four',
        5 => 'Five', 6 => 'Six', 7 => 'Seven', 8 => 'Eight', 9 => 'Nine',
        10 => 'Ten', 11 => 'Eleven', 12 => 'Twelve', 13 => 'Thirteen', 14 => 'Fourteen',
        15 => 'Fifteen', 16 => 'Sixteen', 17 => 'Seventeen', 18 => 'Eighteen', 19 => 'Nineteen',
        20 => 'Twenty', 30 => 'Thirty', 40 => 'Forty', 50 => 'Fifty',
        60 => 'Sixty', 70 => 'Seventy', 80 => 'Eighty', 90 => 'Ninety'
    );

    if ($number < 21) {
        return $words[$number];
    } elseif ($number < 100) {
        return $words[10 * floor($number / 10)] . (($number % 10 != 0) ? ' ' . $words[$number % 10] : '');
    } elseif ($number < 1000) {
        return $words[floor($number / 100)] . ' Hundred' . (($number % 100 != 0) ? ' ' . numberToWords($number % 100) : '');
    } elseif ($number < 1000000) {
        return numberToWords(floor($number / 1000)) . ' Thousand' . (($number % 1000 != 0) ? ' ' . numberToWords($number % 1000) : '');
    } elseif ($number < 1000000000) {
        return numberToWords(floor($number / 1000000)) . ' Million' . (($number % 1000000 != 0) ? ' ' . numberToWords($number % 1000000) : '');
    } else {
        return numberToWords(floor($number / 1000000000)) . ' Billion' . (($number % 1000000000 != 0) ? ' ' . numberToWords($number % 1000000000) : '');
    }
}

$amountInWords = numberToWords($amount);
?>
<div class="dashboard-main-body">
    <div class="d-flex flex-wrap align-items-center text-center justify-content-between gap-3 mb-24">
        <a class="cursor-pointer fw-bold back_btn" onclick="history.back()"><span class="fa fa-arrow-left"></span>&nbsp; Back</a>    
        <h6 class="fw-semibold mb-0 m-auto">Payment Details</h6>
        <a class="cursor-pointer fw-bold back_btn visibility-hidden" onclick="history.back()"><span class="fa fa-arrow-left"></span>&nbsp; Back</a> 
    </div>
    <div class="card container">
        <div class="card-body py-20">
            <div class="row justify-content-center" id="invoice">
                <div class="col-lg-12">
                <div class="d-flex my-3 justify-content-between">
                    <p class="mb-0 mt-auto">Payment ID: <b><?php echo $payment_id; ?></b></p>  
                    
                        <!-- <div class="amt-rec w-auto p-20">
                            <p class="mb-0">Payment Made</p>
                            <p class="mb-0"><?= htmlspecialchars($symbol) ?> <?php echo number_format($amount, 2); ?></p>
                        </div> -->
                    </div>
                    <div class="table-responsive scroll-sm">
                        <table class="table mb-0">
                            <thead class="thead">
                                <tr>
                                    <th scope="col" class="">Services</th>
                                    <th scope="col" class="">Tax</th>
                                    <th scope="col" class="text-end">Price</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="tbody">
                                    <td class="w-40"><?php echo $plan_name; ?> </td>
                                    <td><?= htmlspecialchars($symbol) ?> <?= number_format($row['gst'], 2, '.', ''); ?> </td>
                                    <td class="text-end "><?= htmlspecialchars($symbol) ?> <?= number_format($row['price'], 2, '.', ''); ?> </td>
                                </tr>
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
                            $addon_query = "SELECT name, cost, duration FROM `add-on-service` WHERE id IN ($addon_id_list)";
                            $addon_result = $conn->query($addon_query);

                            if ($addon_result->num_rows > 0) {
                                echo '
                                    <table class="table">
                                        <tbody>';

                                while ($addon_row = $addon_result->fetch_assoc()) {
                                    echo "<tr class='tbody'>
                                            <td class='w-40'>" . htmlspecialchars($addon_row['name']) . "</td>
                                            <td class=''>" . htmlspecialchars($symbol) . " " . htmlspecialchars($row['addon_gst']) . "</td>
                                            <td class='text-end'>
                                                <span class='text-primary-light'>"
                                                . htmlspecialchars($symbol) . " " . number_format($addon_row['cost'], 2, '.', '') .
                                                "</span>
                                            </td>
                                        </tr>";
                                }

                                echo '        
                                    </tbody>
                                    </table>';
                            }
                        }
                    }
                    ?>
                    
                    <div class="d-flex flex-wrap justify-content-end gap-3">
                        <div class="mt-10">
                            <p class="fw-semibold mb-10">Payment summary</p>
                            <table class="invoice_table">
                                <tbody>
                                    <?php if($row['discount'] != null){ ?>
                                    <!-- <tr>
                                        <td class="pe-64 border-bottom p-16">Discount</td>
                                        <td class="border-bottom p-16">
                                            <span class="text-primary-light fw-semibold"> <?php echo $row['discount']; ?> <?php echo $row['discount_type'] ?></span>
                                        </td>
                                    </tr> -->
                                    <?php } ?>
                                    <tr>
                                        <td class="pe-64 border-bottom p-16">Subtotal</td>
                                        <td class="border-bottom p-16 text-end">
                                            <span class="text-primary-light fw-semibold"> <?= htmlspecialchars($symbol) ?> <?php echo number_format($row['subtotal'], 2, '.', ''); ?></span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="pe-64 border-bottom p-16">
                                            <span class="text-primary-light">Total</span>
                                        </td>
                                        <td class="border-bottom p-16 text-end">
                                            <span class="text-primary-light fw-semibold"><?= htmlspecialchars($symbol) ?> <?php echo number_format($row['amount'], 2, '.', ''); ?></span>
                                        </td>
                                    </tr>
                                    <?php if($row['payment_made'] != null){ ?>
                                    <tr>
                                        <td class="pe-64 border-bottom p-16">
                                            <span class="text-primary-light">Payment Made</span>
                                        </td>
                                        <td class="border-bottom p-16 text-end">
                                            <span class="text-primary-light fw-semibold"><?= htmlspecialchars($symbol) ?> <?php echo number_format($row_trans['amount'], 2, '.', ''); ?></span>
                                        </td>
                                    </tr>
                                    <?php } ?>
                                    <tr>
                                        <td class="pe-64 p-16">
                                            <span class="text-primary-light">Balance Due</span>
                                        </td>
                                        <td class="p-16 text-end">
                                            <span class="text-primary-light fw-semibold"><?= htmlspecialchars($symbol) ?> <?php echo number_format($row_trans['balance'], 2, '.', ''); ?></span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="d-flex flex-wrap justify-content-end gap-3 mt-20">
                        <button type="button" class="btn btn-sm border radius-8 d-inline-flex align-items-center gap-1 lufera-color" onclick="downloadReceipt()">
                            <iconify-icon icon="basil:printer-outline" class="text-xl"></iconify-icon>
                            Payment Receipt
                        </button>
                        <!-- <button type="button" class="btn btn-sm border radius-8 d-inline-flex align-items-center gap-1 lufera-color" onclick="printInvoice()">
                            <iconify-icon icon="basil:printer-outline" class="text-xl"></iconify-icon>
                            Print
                        </button> -->
                    </div>
                    <div class="pay-rec">                    
                        <div class="d-flex flex-wrap justify-content-between gap-3 my-20">
                            <div>
                                <!-- <img src="assets/images/logo_lufera.png" alt="image" class="mb-8" width="200px"> -->
                                <img src="uploads/company_logo/<?php echo $logo; ?>" alt="site logo" class="light-logo" width="200px">
                            </div>
                            <div class="">
                                <p class="mb-0"><b><?php echo $company_row['full_name']; ?></b></p>
                                <p class="mb-0 text-sm"><?php echo $company_row['address']; ?>,<br> <?php echo $company_row['   ']; ?>, <?php echo $company_row['state']; ?>, <?php echo $company_row['zip_code']; ?>, <?php echo $company_row['country']; ?></p>
                                <p class="mb-0 text-sm"><?php echo $company_row['phone_no']; ?></p>
                                <p class="mb-0 text-sm"><?php echo $company_row['website']; ?></p>
                            </div>
                        </div>
                        <hr>
                        <p class="text-center my-20 text-uppercase text-decoration-underline">Payment Receipt</p>
                        
                        <div class="row">
                            <div class="col-lg-8">
                                <div class="d-flex my-3">
                                    <span class="w-40 text-gray">Payment Date</span>
                                    <span class="w-60 border_gray"><?php echo date('d/m/Y', strtotime($row['created_on'])); ?></span>
                                </div>
                                <div class="d-flex my-3">
                                    <span class="w-40 text-gray">Reference Number</span>
                                    <span class="w-60 border_gray"><?php echo $payment_id; ?></span>
                                </div>
                                <div class="d-flex my-3">
                                    <span class="w-40 text-gray">Payment Mode</span>
                                    <span class="w-60 border_gray"><?php echo $payment_method; ?></span>
                                </div>
                                <div class="d-flex my-3">
                                    <span class="w-40 text-gray">Amount Received in Words</span>
                                    <span class="w-60 border_gray"><?php echo $amountInWords; ?></span>
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="amt-rec">
                                    <p class="mb-0">Amount Received</p>
                                    <p class="mb-0"><?= htmlspecialchars($symbol) ?> <?php echo number_format($amount, 2); ?></p>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex flex-wrap justify-content-between gap-3 my-20">
                            <div>
                                <p class="mb-0 text-sm text-gray"><b>Received From</b></p>
                                <p class="mb-0"><b><?php echo $user_row['business_name']; ?></b></p>
                                <p class="mb-0 text-sm"><?php echo $user_row['address']; ?></p>
                                <p class="mb-0 text-sm"><?php echo $user_row['city']; ?>, <?php echo $user_row['state']; ?> <?php echo $user_row['pincode']; ?></p>
                                <p class="mb-0 text-sm"><?php echo $user_row['country']; ?></p>
                            </div>
                            <div class="text-end">
                                <p class="mb-0 text-sm">Authorized Signature</p>
                            </div>
                        </div>
                        <hr>
                        <div class="d-flex flex-wrap justify-content-between gap-3 my-20">
                            <p class="mb-0"><b>Payment for</b></p>
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Invoice Number</th>
                                        <th>Invoice Date</th>
                                        <th class="text-end">Invoice Amount</th>
                                        <th class="text-end">Payment Amount</th>
                                    </tr>
                                </thead>    
                                <tbody>
                                    <tr>
                                        <td><?php echo $invoice_no; ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($paid_date)); ?></td>
                                        <td class="text-end"><?= htmlspecialchars($symbol) ?> <?php echo number_format($row['amount'], 2); ?></td>
                                        <td class="text-end"><?= htmlspecialchars($symbol) ?> <?php echo number_format($amount, 2); ?></td>
                                    </tr>
                                </tbody>            
                            </table>
                        </div>
                        <?php 
                            $tc_sql = "SELECT * FROM terms_conditions where apply_for = 'receipt'";
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
    </div>
</div>

<?php include './partials/layouts/layoutBottom.php' ?>