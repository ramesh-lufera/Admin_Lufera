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
}
input::-webkit-outer-spin-button,
                input::-webkit-inner-spin-button {
                -webkit-appearance: none;
                margin: 0;
                }
input[type=number] {
-moz-appearance: textfield;
}
.percent-icon{
    top: 1px;
    right: 1px;
    left: auto;
    position: absolute;
    display: flex;
    background: lightgray;
    border-radius: 0 8px 8px 0;
    padding: 0 0 0 10px;
}
</style>
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


    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $discount = $_POST['discount'];
        $payment_made = $_POST['payment_made'];
        $subtotal = $_POST['subtotal'];
        $total = $_POST['total'];
        $balance_due = $_POST['balance_due'];
        $gst = $_POST['gst'];

        $stmt = $conn->prepare("UPDATE orders SET amount = ?, gst = ?, discount = ?, payment_made = ?, subtotal = ?, balance_due = ?  WHERE invoice_id = ?");
        $stmt->bind_param("sssssss", $total, $gst, $discount, $payment_made, $subtotal, $balance_due, $invoice_id);
        if ($stmt->execute()) {
            echo "
            <script>
                Swal.fire({
                    title: 'Success!',
                    text: 'Invoice updated successfully.',
                    confirmButtonText: 'OK',
                    allowOutsideClick: false
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = 'invoice-preview.php?id=$invoice_id';
                    }
                });
            </script>";
        } else {
            echo "<script>
                alert('Error: " . $stmt->error . "');
                window.history.back();
            </script>";
        }

        $stmt->close();
       
        }
?>
        <div class="dashboard-main-body">

            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
                <h6 class="fw-semibold mb-0">Invoice List</h6>
            </div>

            <div class="card">
                <!-- <div class="card-header">
                    <div class="d-flex flex-wrap align-items-center justify-content-end gap-2">
                        <button type="button" class="btn btn-sm btn-danger radius-8 d-inline-flex align-items-center gap-1">
                            <iconify-icon icon="basil:printer-outline" class="text-xl"></iconify-icon>
                            Save
                        </button>
                    </div>
                </div> -->
                <div class="card-body py-40">
                    <div class="row justify-content-center" id="invoice">
                        <div class="col-lg-8">
                            <div class="shadow-4 border radius-8">
                                <div class="p-20 d-flex flex-wrap justify-content-between gap-3 border-bottom">
                                    <div>
                                        <!-- <h3 class="text-xl">Invoice No: <?php echo $invoice_id; ?></h3>
                                        <p class="mb-1 text-sm">Date : <?php echo date('d/m/Y', strtotime($row['created_on'])); ?></p> -->
                                        <img src="assets/images/logo_lufera.png" alt="image" class="mb-8" width="200px">
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
                                    <div class="">
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
                                                        <td><input type="text" value="<?php echo $row['plan']; ?>" >  </td>
                                                        <td>
                                                            <select class="form-control border-0 p-0" name="">
                                                                <option value="<?php echo $row['duration']; ?>"><?php echo $row['duration']; ?></option>
                                                                <option value="1 Year">1 Year</option>
                                                                <option value="3 Years">3 Years</option>
                                                            </select>
                                                        </td>
                                                        <td>
                                                            <select class="form-control border-0 p-0" name="payment_method">
                                                                <option value=""><?php echo $row['payment_method']; ?></option>
                                                                <option value="Cash">Cash</option>
                                                                <option value="Card">Card</option>
                                                                <option value="UPI">UPI</option>
                                                                <option value="Bank">Bank</option>
                                                            </select>
                                                        </td>
                                                        <td><?php echo $row['status']; ?> </td>
                                                        <td class="text-end text-sm"><input type="text" value="<?php echo $row['price']; ?>" > </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                        <div class="d-flex flex-wrap justify-content-end gap-3">
                                            <div>
                                                <form method="post">
                                                    <table class="invoice_table text-end">
                                                        <tbody>
                                                            <tr>
                                                                <td class="pe-64 border-bottom p-8">Discount</td>
                                                                <td class="border-bottom p-8 text-start">
                                                                <div class="icon-field has-validation">
                                                                    <span class="percent-icon">
                                                                    <select id="discountType" name="discount_type">
                                                                        <option value="%">%</option>
                                                                        <option value="$">$</option>
                                                                    </select>
                                                                        <iconify-icon icon="mdi-menu-down" class="align-content-center"></iconify-icon>
                                                                    </span>
                                                                    <input value="<?php echo $row['discount']; ?>" <?php echo $row['balance_due'] == "0" ? 'readonly' : ''; ?> type="text" name="discount" id="numericInput" class="border-1 radius-8 px-10" style="width:120px; float:right">
                                                                </div>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td class="pe-64 border-bottom p-8">Subtotal</td>
                                                                <td class="border-bottom p-8">
                                                                    <!-- <span class="text-primary-light fw-semibold">$ <?php echo $row['price']; ?></span> -->
                                                                    <!-- <span class="text-primary-light fw-semibold" id="subtotal">$ <?php echo $row['price']; ?></span> -->
                                                                     <input type="text" readonly name="subtotal" id="subtotal" value="<?php echo $row['subtotal']; ?>" >
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td class="pe-64 border-bottom p-8">GST 18%</td>
                                                                <td class="border-bottom p-8">
                                                                    <!-- <span class="text-primary-light fw-semibold">$ <?php echo $row['amount'] - $row['price']; ?></span> -->
                                                                    <!-- <span class="text-primary-light fw-semibold" id="gst">$ <?php echo $row['amount'] - $row['price']; ?></span> -->
                                                                     <input type="text" id="gst" value="<?php echo $row['gst']; ?>" readonly name="gst">
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td class="pe-64 border-bottom p-8">
                                                                    <span class="text-primary-light fw-semibold">Total</span>
                                                                </td>
                                                                <td class="border-bottom p-8">
                                                                    <!-- <span class="text-primary-light fw-semibold">$ <?php echo $row['amount']; ?></span> -->
                                                                    <!-- <span class="text-primary-light fw-semibold" id="total">$ <?php echo $row['amount']; ?></span> -->
                                                                    <input type="text" id="total" name="total" value="<?php echo $row['amount'] ?>" readonly >
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td class="pe-64 border-bottom p-8">
                                                                    <span class="text-primary-light fw-semibold">Payment Made</span>
                                                                </td>
                                                                <td class="border-bottom p-8">
                                                                    <input type="text" id="numericInputs" name="payment_made" style="width:100px; " value="<?php echo $row['payment_made'] ?>" readonly>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td class="pe-64 border-bottom p-8">
                                                                    <span class="text-primary-light fw-semibold">Balance Due</span>
                                                                </td>
                                                                <td class="border-bottom p-8">
                                                                    <!-- <span class="text-primary-light fw-semibold">$ <?php echo $row['amount']; ?></span> -->
                                                                    <!-- <span class="text-primary-light fw-semibold" id="balanceDue">$ <?php echo $row['amount']; ?></span> -->
                                                                    <input type="text" id="balance_due" name="balance_due" value="<?php echo $row['balance_due'] ?>" readonly >
                                                                </td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                    <input type="submit" class="btn lufera-bg float-end text-white mt-20" value="Save" name="update"> 
                                                </form>
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
<script>
  document.getElementById("numericInput").addEventListener("input", function () {
    this.value = this.value.replace(/\D/g, ''); // Remove non-digits
  });

  document.getElementById("numericInputs").addEventListener("input", function () {
    this.value = this.value.replace(/\D/g, ''); // Remove non-digits
  });
</script>

<script>
  const discountInput = document.getElementById("numericInput");
  const paymentMadeInput = document.getElementById("numericInputs");
  const discountTypeSelect = document.getElementById("discountType");

  const price = <?php echo $row['price']; ?>;

  function calculateAndUpdateInvoice() {
    let discountValue = parseFloat(discountInput.value) || 0;
    let paymentMade = parseFloat(paymentMadeInput.value) || 0;
    let discountType = discountTypeSelect.value;

    let discountAmount = 0;

    if (discountType === "%") {
      if (discountValue > 100) discountValue = 100;
      discountAmount = (price * discountValue) / 100;
    } else if (discountType === "$") {
      if (discountValue > price) discountValue = price;
      discountAmount = discountValue;
    }

    const newSubtotal = price - discountAmount;
    const gst = newSubtotal * 0.18;
    const total = newSubtotal + gst;
    const balance_due = total - paymentMade;

    document.getElementById("subtotal").value = newSubtotal;
    document.getElementById("gst").value = gst;
    document.getElementById("total").value = total;
    document.getElementById("balance_due").value = balance_due;
  }

  discountInput.addEventListener("input", function () {
    this.value = this.value.replace(/\D/g, '');
    calculateAndUpdateInvoice();
  });

  paymentMadeInput.addEventListener("input", function () {
    this.value = this.value.replace(/\D/g, '');
    calculateAndUpdateInvoice();
  });

  discountTypeSelect.addEventListener("change", function () {
    calculateAndUpdateInvoice();
  });
</script>



<?php include './partials/layouts/layoutBottom.php' ?>