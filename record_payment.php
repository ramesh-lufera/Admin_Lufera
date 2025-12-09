<?php
$currentURL = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
$currentURL .= "://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
?>

<?php
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

        $total_amount = $amount + $payment_made;
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
                    "Plan Details",                   // module
                    "Record Payment created successfully - $payment_id"  // description
                  );
                $siteInsert = "UPDATE orders
                                SET payment_made = $total_amount, balance_due = $balance_due
                                WHERE invoice_id = '$InvoiceId'";
                    mysqli_query($conn, $siteInsert);
                echo "
                <script>
                    Swal.fire({
                        title: 'Success!',
                        text: 'Payment Record Created Successfully.',
                        confirmButtonText: 'OK',
                        allowOutsideClick: false
                    }).then((result) => {
                        if (result.isConfirmed) {
                           window.location.href = window.location.href;
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
?>   
<button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#exampleModal">Record Payment</button>
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
                                    <input type="hidden" value="<?php echo $order_id; ?>" name="order_id">
                                    <input type="text" class="form-control radius-8" name="invoice_no" value="<?php echo $InvoiceId; ?>" <?php echo !empty($InvoiceId) ? 'readonly' : ''; ?> required>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="mb-20">
                                    <label for="" class="form-label fw-semibold text-primary-light text-sm mb-8">Payment Method <span class="text-danger-600">*</span></label>
                                    <select class="form-control" name="payment_method" required <?php echo $balance_due == "0" ? 'disabled' : ''; ?> >
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
                                    
                                    <input type="hidden" class="form-control radius-8" name="payment_made" id="payment_made" value="<?php echo $payment_made; ?>">

                                    <input type="text" class="form-control radius-8" name="amount" id="numericInput" required <?php echo $balance_due == "0" ? 'readonly' : ''; ?> >
                                    <small id="amountError" class="text-danger d-none">Amount cannot be greater than Balance Due.</small>

                                    <input type="hidden" class="form-control radius-8" name="balance_due" id="balance_due" value="<?php echo $balance_due; ?>">
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="mb-20">
                                    <label for="" class="form-label fw-semibold text-primary-light text-sm mb-8">Remarks <span class="text-danger-600">*</span></label>
                                    <input type="text" class="form-control radius-8" name="remarks" value="" required <?php echo $balance_due == "0" ? 'readonly' : ''; ?> >
                                </div>
                            </div>

                            <?php if ($balance_due == '0') { ?>
                                <p class="text-danger">Payment fully paid</p>
                            <?php } ?>
                        </div>
                    </div>
                    <div class="modal-footer d-flex align-items-center justify-content-center gap-3">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" id="submit" class="btn lufera-bg text-white" name="save" <?php echo $balance_due == "0" ? 'disabled' : ''; ?>>Save</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

<script>
document.getElementById('numericInput').addEventListener('input', function () {
    const amount = parseFloat(this.value);
    const originalBalance = parseFloat(<?php echo json_encode($balance_due); ?>);

    if (!isNaN(amount)) {
        const updatedBalance = originalBalance - amount;
        document.getElementById('balance_due').value = updatedBalance.toFixed(2);;
    } else {
        // Reset if input is not a number
        document.getElementById('balance_due').value = originalBalance.toFixed(2);;
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