<?php $script = '<script>
                    function printInvoice() {
                        var printContents = document.getElementById("invoice").innerHTML;
                        var originalContents = document.body.innerHTML;

                        document.body.innerHTML = printContents;

                        window.print();

                        document.body.innerHTML = originalContents;
                    }
                </script>';?>

<?php include './partials/layouts/layoutTop.php' ?>
<style>
.invoice_table {
    font-size:16px !important;
    width:300px;
}
</style>
<?php
    $company_sql = "select * from company";
    $company_fetch = $conn->query($company_sql);
    $company_row = $company_fetch->fetch_assoc();

    $invoice_id = $_GET['id'];
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
    where invoice_id = $invoice_id";
    $result = $conn->query($invoice);
    $row = $result->fetch_assoc();
    $user_id = $row['user_id'];
    
    $userId = $_SESSION['user_id'];
    $session_user = "select * from users where id = $userId";
    $result2 = $conn->query($session_user);
    $row2 = $result2->fetch_assoc();
    
    $user = "select * from users where id = '$user_id'"; 
    $results = $conn->query($user);
    $rows = $results->fetch_assoc();
    $user_ids = $rows['email'];

     // Get active symbol
    $result1 = $conn->query("SELECT symbol FROM currencies WHERE is_active = 1 LIMIT 1");
    $symbol = "$"; // default
    if ($row1 = $result1->fetch_assoc()) {
        $symbol = $row1['symbol'];
    }
?>
        <div class="dashboard-main-body">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
                <a class="cursor-pointer fw-bold" onclick="history.back()"><span class="fa fa-arrow-left"></span>&nbsp; Back</a>    
                <h6 class="fw-semibold mb-0">Invoice Preview</h6>
                <a class="cursor-pointer fw-bold visibility-hidden" onclick="history.back()"><span class="fa fa-arrow-left"></span>&nbsp; Back</a> 
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="d-flex flex-wrap align-items-center justify-content-end gap-2">
                    <?php 
                    if($row2['role'] == "1" || $row2['role'] == "2") {?>  
                    <a href="invoice-preview-edit.php?id=<?php echo $invoice_id; ?>">  
                        <button type="button" class="btn btn-sm btn-success radius-8 d-inline-flex align-items-center gap-1" >
                            <iconify-icon icon="basil:edit-outline" class="text-xl"></iconify-icon>
                            Edit
                        </button>
                    </a> 
                        <?php } ?>   
                        <button type="button" class="btn btn-sm btn-danger radius-8 d-inline-flex align-items-center gap-1" onclick="printInvoice()">
                            <iconify-icon icon="basil:printer-outline" class="text-xl"></iconify-icon>
                            Print
                        </button>
                    </div>
                </div>
                
                <div class="card-body py-40">
                    <div class="row justify-content-center" id="invoice">
                        <div class="col-lg-8">
                            <div class="shadow-4 border radius-8">
                                <div class="p-20 d-flex flex-wrap justify-content-between gap-3 ">
                                    <div>
                                        <!-- <h3 class="text-xl">Invoice No: <?php echo $invoice_id; ?></h3>
                                        <p class="mb-1 text-sm">Date : <?php echo date('d/m/Y', strtotime($row['created_on'])); ?></p> -->
                                        <img src="assets/images/logo_lufera.png" alt="image" class="mb-8" width="200px">
                                        <div class="">
                                            <p class="mb-0 text-xl"><b><?php echo $company_row['full_name']; ?></b></p>
                                            <p class="mb-0 text-sm"><?php echo $company_row['address']; ?>, <?php echo $company_row['city']; ?>,<?php echo $company_row['state']; ?>,<?php echo $company_row['zip_code']; ?>, <?php echo $company_row['country']; ?></p>
                                            <p class="mb-0 text-sm"><?php echo $company_row['phone_no']; ?></p>
                                            <p class="mb-0 text-sm"><?php echo $company_row['website']; ?></p>
                                        </div>
                                    </div>
                                    <div class="">
                                       <!-- <p class="mb-0"><b>Lufera Infotech Pvt. Ltd. (OPC)</b></p>
                                        <p class="mb-0 text-sm">96/1, Bharathidasan Salai,Cantonment, Trichy,TN, India, 620 001</p>
                                        <p class="mb-0 text-sm">+91 86 80808 204</p>
                                        <p class="mb-0 text-sm">www.luferatech.com</p> -->
                                       <?php if($row['balance_due'] != "0.00" ){ ?> 
                                            <h4>Proforma Invoice</h4>
                                        <?php } ?>
                                            <p class="text-md mb-0">Invoice Date: <?php echo date('d/m/Y', strtotime($row['created_on'])); ?> </p>
                                        <p class="text-md mb-0">Bill To:</p>
                                        <p class="text-md mb-0"><?php echo $rows['business_name']; ?> </p>
                                        <p class="text-md mb-0"><?php echo $rows['address']; ?></p>
                                        <p class="text-md mb-0"><?php echo $rows['city']; ?> <?php echo $rows['pincode']; ?></p>
                                        <p class="text-md mb-0"><?php echo $rows['state']; ?>, <?php echo $rows['country']; ?></p>
                                    </div>
                                </div>

                                <div class="py-28 px-20">
                                    <div class="">
                                        <div class="table-responsive scroll-sm">
                                            <!-- <table class="table table-bordered text-sm">
                                                <thead>
                                                    <tr>
                                                       
                                                        <th scope="col" class="text-sm">Plan</th>
                                                        <th scope="col" class="text-sm">Qty</th>
                                                        <th scope="col" class="text-sm">Payment Method</th>
                                                        <th scope="col" class="text-sm">Status</th>
                                                        <th scope="col" class="text-end text-sm">Amount</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td><?php echo $row['plan_name']; ?> </td>
                                                        <td><?php echo $row['duration']; ?> </td>
                                                        <td><?php echo $row['payment_method']; ?> </td>
                                                        <td><?php echo $row['status']; ?> </td>
                                                        <td class="text-end text-sm" id="currency-symbol-display"><?= htmlspecialchars($symbol) ?> <?php echo $row['price']; ?> </td>
                                                    </tr>
                                                </tbody>
                                            </table> -->
                                            <table class="table table-bordered mb-0">
                                                <thead>
                                                    <th class="w-50">Description</th>
                                                    <th class="text-end w-50">Total</th>
                                                </thead>
                                                <tbody>
                                                    <td class="w-50"><?php echo $row['plan_name']; ?></td>
                                                    <td class="text-end w-50"><?= htmlspecialchars($symbol) ?> <?php echo $row['price']; ?> </td>
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
                                                                <td class='w-50'>" . htmlspecialchars($addon_row['name']) . "</td>
                                                                <td class='w-50 text-end'>
                                                                    <span class='text-primary-light'>"
                                                                    . htmlspecialchars($symbol) . " " . htmlspecialchars($addon_row['cost']) .
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
                                            <div>
                                                <table class="invoice_table text-end">
                                                    <tbody>
                                                        <?php if($row['discount'] != null){ ?>
                                                        <!-- <tr>
                                                            <td class="pe-64  p-8">Discount</td>
                                                            <td class=" p-8">
                                                                <span class="text-primary-light fw-semibold"> <?php echo $row['discount']; ?> <?php echo $row['discount_type'] ?></span>
                                                            </td>
                                                        </tr> -->
                                                        <?php } ?>
                                                        <tr>
                                                            <td class="pe-64 p-8 fw-semibold">Subtotal</td>
                                                            <td class=" p-8">
                                                                <span class="text-primary-light" id="currency-symbol-display"><?= htmlspecialchars($symbol) ?> <?php echo $row['subtotal']; ?></span>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td class="pe-64 p-8 fw-semibold">GST 18%</td>
                                                            <td class=" p-8">
                                                                <span class="text-primary-light" id="currency-symbol-display"><?= htmlspecialchars($symbol) ?> <?php echo $row['gst']; ?></span>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td class="pe-64 p-8 fw-semibold">
                                                                <span class="text-primary-light">Total</span>
                                                            </td>
                                                            <td class="p-8">
                                                                <span class="text-primary-light" id="currency-symbol-display"><?= htmlspecialchars($symbol) ?> <?php echo $row['amount']; ?></span>
                                                            </td>
                                                        </tr>
                                                        <?php if($row['payment_made'] != null){ ?>
                                                        <tr>
                                                            <td class="pe-64 p-8 fw-semibold">
                                                                <span class="text-primary-light">Payment Made</span>
                                                            </td>
                                                            <td class="p-8">
                                                                <span class="text-primary-light" id="currency-symbol-display"><?= htmlspecialchars($symbol) ?> <?php echo $row['payment_made']; ?></span>
                                                            </td>
                                                        </tr>
                                                        <?php } ?>
                                                        <tr>
                                                            <td class="pe-64 p-8 fw-semibold">
                                                                <span class="text-primary-light">Balance Due</span>
                                                            </td>
                                                            <td class="p-8">
                                                                <span class="text-primary-light" id="currency-symbol-display"><?= htmlspecialchars($symbol) ?> <?php echo $row['balance_due']; ?></span>
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

<?php include './partials/layouts/layoutBottom.php' ?>