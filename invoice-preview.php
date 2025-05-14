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
        <div class="dashboard-main-body">

            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
                <h6 class="fw-semibold mb-0">Invoice List</h6>
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="d-flex flex-wrap align-items-center justify-content-end gap-2">
                        <button type="button" class="btn btn-sm btn-danger radius-8 d-inline-flex align-items-center gap-1" onclick="printInvoice()">
                            <iconify-icon icon="basil:printer-outline" class="text-xl"></iconify-icon>
                            Print
                        </button>
                    </div>
                </div>

                <?php
                    $invoice_id = $_GET['id'];
                    $invoice = "select * from orders where invoice_id = $invoice_id";
                    $result = $conn->query($invoice);
                    $row = $result->fetch_assoc();
                    $user_id = $row['user_id'];
                    
                    $user = "select * from users where user_id = '$user_id'"; 
                    $results = $conn->query($user);
                    $rows = $results->fetch_assoc();
                    $user_ids = $rows['email'];
                    
                    // $gst = $row['amount'] * 0.18; 
                    // $total_price = $price + $gst;
                ?>
                <div class="card-body py-40">
                    <div class="row justify-content-center" id="invoice">
                        <div class="col-lg-8">
                            <div class="shadow-4 border radius-8">
                                <div class="p-20 d-flex flex-wrap justify-content-between gap-3 border-bottom">
                                    <div>
                                        <!-- <h3 class="text-xl">Invoice No: <?php echo $invoice_id; ?></h3>
                                        <p class="mb-1 text-sm">Date : <?php echo date('d/m/Y', strtotime($row['created_on'])); ?></p> -->
                                        <img src="assets/images/logo_lufera.png" alt="image" class="mb-8" width="300px">
                                    </div>
                                    <div class="text-end">
                                       <p class="mb-0"><b>Lufera Infotech Pvt. Ltd. (OPC)</b></p>
                                        <p class="mb-0 text-sm">96/1, Bharathidasan Salai,Cantonment, Trichy,TN, India, 620 001</p>
                                        <p class="mb-0 text-sm">+91 86 80808 204</p>
                                        <p class="mb-0 text-sm">www.luferatech.com</p>
                                    </div>
                                </div>

                                <div class="d-flex flex-wrap justify-content-between gap-3 py-28 px-20">
                                    <div>
                                        <p class="text-md mb-0">Bill To</p>
                                        <p class="text-md mb-0"><b><?php echo $rows['first_name']; ?> <?php echo $rows['last_name']; ?></b></p>
                                        <p class="text-md mb-0"><?php echo $rows['address']; ?></p>
                                        <p class="text-md mb-0"><?php echo $rows['city']; ?> <?php echo $rows['pincode']; ?></p>
                                        <p class="text-md mb-0"><?php echo $rows['state']; ?>, <?php echo $rows['country']; ?></p>
                                    </div>

                                    <div>
                                    <table class="text-sm text-secondary-light invoice_table">
                                                <tbody>
                                                    <tr>
                                                        <td><b>Invoice</b></td>
                                                        <td class="ps-8"> <?php echo $invoice_id; ?> </td>
                                                    </tr>
                                                    <tr>
                                                        <td><b>Invoice Date</b></td>
                                                        <td class="ps-8"> <?php echo date('d/m/Y', strtotime($row['created_on'])); ?></td>
                                                    </tr>
                                                    <tr>
                                                        <td><b>Terms</b></td>
                                                        <td class="ps-8"> Due on Receipt</td>
                                                    </tr>
                                                    <tr>
                                                        <td><b>Due Date</b></td>
                                                        <td class="ps-8"> <?php echo date('d/m/Y', strtotime($row['created_on'])); ?></td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                    </div>
                                    
                                </div>

                                <div class="py-28 px-20">
                                    <div class="mt-24">
                                        <div class="table-responsive scroll-sm">
                                            <table class="table table-bordered text-sm">
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
                                                        <td><?php echo $row['plan']; ?> </td>
                                                        <td><?php echo $row['duration']; ?> </td>
                                                        <td><?php echo $row['payment_method']; ?> </td>
                                                        <td><?php echo $row['status']; ?> </td>
                                                        <td class="text-end text-sm">$ <?php echo $row['price']; ?> </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                        <div class="d-flex flex-wrap justify-content-end gap-3">
                                            <div>
                                                <table class="invoice_table text-end">
                                                    <tbody>
                                                        <tr>
                                                            <td class="pe-64 border-bottom p-8">Subtotal</td>
                                                            <td class="border-bottom p-8">
                                                                <span class="text-primary-light fw-semibold">$ <?php echo $row['price']; ?></span>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td class="pe-64 border-bottom p-8">Tax 18%</td>
                                                            <td class="border-bottom p-8">
                                                                <span class="text-primary-light fw-semibold">$ <?php echo $row['amount'] - $row['price']; ?></span>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td class="pe-64 border-bottom p-8">
                                                                <span class="text-primary-light fw-semibold">Total</span>
                                                            </td>
                                                            <td class="border-bottom p-8">
                                                                <span class="text-primary-light fw-semibold">$ <?php echo $row['amount']; ?></span>
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