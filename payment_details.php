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
    width:600px;
    background:#f2f3f6;
    border-radius:10px;
}
.thead, .tbody{
    border: 1px solid var(--border-color)   ;
}
.thead > tr >th , .tbody >td {
    padding:12px !important;
}
</style>
<?php
$userId = $_SESSION['user_id'];

    $trans_id = $_GET['id'];
    $transaction = "select * from record_payment where id = $trans_id";
    $result_trans = $conn->query($transaction);
    $row_trans = $result_trans->fetch_assoc();
    $invoice_no = $row_trans['invoice_no'];
    $payment_id = $row_trans['payment_id'];

    $invoice_no = intval($invoice_no); // sanitize

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
    WHERE o.invoice_id = $invoice_no
    ";

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
        <div class="dashboard-main-body">

            <div class="text-center gap-3 mb-24">
                <h6 class="fw-semibold mb-0">Payment details</h6>
            </div>

            <div class="card container">
                <div class="card-body py-40">
                    <div class="row justify-content-center" id="invoice">
                        <div class="col-lg-12">
                            <p>Payment ID: <b><?php echo $payment_id; ?></b></p>  
                            
                            <div class="table-responsive scroll-sm">
                                <table class="table mb-0">
                                    <thead class="thead">
                                        <tr>
                                            <th scope="col" class="">Services</th>
                                            <th scope="col" class="">Period</th>
                                            <th scope="col" class="text-end">Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr class="tbody">
                                            <td><?php echo $plan_name; ?> </td>
                                            <td><?php echo $row['duration']; ?> </td>
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
                                    $addon_query = "SELECT name, cost FROM `add-on-service` WHERE id IN ($addon_id_list)";
                                    $addon_result = $conn->query($addon_query);

                                    if ($addon_result->num_rows > 0) {
                                        echo '<tr>
                                                <td colspan="2" class="p-0">
                                                    <table class="table">
                                                        
                                                        <tbody>';

                                        while ($addon_row = $addon_result->fetch_assoc()) {
                                            echo "<tr class='tbody'>
                                                    <td class=''>" . htmlspecialchars($addon_row['name']) . "</td>
                                                    <td class='text-end'>
                                                        <span class='text-primary-light'>"
                                                        . htmlspecialchars($symbol) . " " . number_format($addon_row['cost'], 2, '.', '') .
                                                        "</span>
                                                    </td>
                                                </tr>";
                                        }

                                        echo '        </tbody>
                                                    </table>
                                                </td>
                                            </tr>';
                                    }
                                }
                            }
                            ?>
                            
                            <div class="d-flex flex-wrap justify-content-end gap-3">
                                <div>
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
                                                <td class="pe-64 border-bottom p-16">GST 18%</td>
                                                <td class="border-bottom p-16 text-end">
                                                    <span class="text-primary-light fw-semibold"><?= htmlspecialchars($symbol) ?> <?php echo number_format($row['gst'], 2, '.', ''); ?> </span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="pe-64 border-bottom p-16">
                                                    <span class="text-primary-light">Total</span>
                                                </td>
                                                <td class="border-bottom p-16 text-end">
                                                    <span class="text-primary-light fw-semibold"><?= htmlspecialchars($symbol) ?> <?php echo $row['amount']; ?></span>
                                                </td>
                                            </tr>
                                            <?php if($row['payment_made'] != null){ ?>
                                            <tr>
                                                <td class="pe-64 border-bottom p-16">
                                                    <span class="text-primary-light">Payment Made</span>
                                                </td>
                                                <td class="border-bottom p-16 text-end">
                                                    <span class="text-primary-light fw-semibold"><?= htmlspecialchars($symbol) ?> <?php echo number_format($row_trans['amount'], 2, '.', ''); ?>
                                                    </span></span>
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
                                    <button type="button" class="btn btn-sm mt-20 border float-end radius-8 d-inline-flex align-items-center gap-1 lufera-color" onclick="printInvoice()">
                                        <iconify-icon icon="basil:printer-outline" class="text-xl"></iconify-icon>
                                        Print
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                            
                </div>
            </div>

        </div>

<?php include './partials/layouts/layoutBottom.php' ?>