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
                                        <h3 class="text-xl">Invoice No: <?php echo $invoice_id; ?></h3>
                                        <p class="mb-1 text-sm">Date : <?php echo date('d/m/Y', strtotime($row['created_on'])); ?></p>
                                    </div>
                                    <div>
                                        <img src="assets/images/logo_lufera.png" alt="image" class="mb-8" width="168px">
                                        <p class="mb-1 text-sm"><?php echo $rows['address']; ?></p>
                                    </div>
                                </div>
                                <div class="py-28 px-20">
                                    <div class="mt-24">
                                        <div class="table-responsive scroll-sm">
                                            <table class="table bordered-table text-sm">
                                                <thead>
                                                    <tr>
                                                        <th scope="col" class="text-sm">Items</th>
                                                        <th scope="col" class="text-sm">Plan</th>
                                                        <th scope="col" class="text-sm">Payment Method</th>
                                                        <th scope="col" class="text-sm">Status</th>
                                                        <th scope="col" class="text-end text-sm">Amount</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td><?php echo $invoice_id; ?> </td>
                                                        <td><?php echo $row['plan']; ?> </td>
                                                        <td><?php echo $row['payment_method']; ?> </td>
                                                        <td><?php echo $row['status']; ?> </td>
                                                        <td class="text-end text-sm">$ <?php echo $row['amount']; ?> </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                        <div class="d-flex flex-wrap justify-content-end gap-3">
                                            <div>
                                                <table class="text-sm">
                                                    <tbody>
                                                        <tr>
                                                            <td class="pe-64">Subtotal:</td>
                                                            <td class="pe-16">
                                                                <span class="text-primary-light fw-semibold">$ <?php echo $row['price']; ?></span>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td class="pe-64 border-bottom pb-4">Tax:</td>
                                                            <td class="pe-16 border-bottom pb-4">
                                                                <span class="text-primary-light fw-semibold">$ <?php echo $row['amount'] - $row['price']; ?></span>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td class="pe-64 pt-4">
                                                                <span class="text-primary-light fw-semibold">Total:</span>
                                                            </td>
                                                            <td class="pe-16 pt-4">
                                                                <span class="text-primary-light fw-semibold">$ <?php echo $row['amount']; ?></span>
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mt-60">
                                        <div>
                                            <h6 class="text-md">Personal Details:</h6>
                                            <table class="text-sm text-secondary-light">
                                                <tbody>
                                                    <tr>
                                                        <td>Name</td>
                                                        <td class="ps-8">: <?php echo $rows['first_name']; ?> <?php echo $rows['last_name']; ?></td>
                                                    </tr>
                                                    <tr>
                                                        <td>Email</td>
                                                        <td class="ps-8">: <?php echo $rows['email']; ?></td>
                                                    </tr>
                                                    <tr>
                                                        <td>Phone number</td>
                                                        <td class="ps-8">: <?php echo $rows['phone']; ?></td>
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

<?php include './partials/layouts/layoutBottom.php' ?>