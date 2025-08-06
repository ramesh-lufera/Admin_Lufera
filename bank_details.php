<?php include './partials/layouts/layoutTop.php' ?>

<?php
    $id = 0;
    $bank_name = $ac_name = $ac_no = $branch = $ifsc_code = $micr = $swift_code = "";
    
    // Fetch existing data (assuming only one record)
    $sql = "SELECT * FROM bank_details LIMIT 1";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $id = $row['id'];
        $bank_name = $row['bank_name'];
        $ac_name = $row['ac_name'];
        $ac_no = $row['ac_no'];
        $branch = $row['branch'];
        $ifsc_code = $row['ifsc_code'];
        $micr = $row['micr'];
        $swift_code = $row['swift_code'];
    }
    
    // Handle form submission
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $bank_name = $_POST['bank_name'];
        $ac_name = $_POST['ac_name'];
        $ac_no = $_POST['ac_no'];
        $branch = $_POST['branch'];
        $ifsc_code = $_POST['ifsc_code'];
        $micr = $_POST['micr'];
        $swift_code = $_POST['swift_code'];
    
        if ($id > 0) {
            // Update existing record
            $update_sql = "UPDATE bank_details SET 
                bank_name='$bank_name',
                ac_name='$ac_name',
                ac_no='$ac_no',
                branch='$branch',
                ifsc_code='$ifsc_code',
                micr='$micr',
                swift_code='$swift_code'
                WHERE id=$id";
            if ($conn->query($update_sql) === TRUE) {
                echo "<script>
                    Swal.fire({
                        title: 'Success!',
                        text: 'Bank details updated successfully.',
                        confirmButtonText: 'OK'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.reload = '';
                        }
                    });
                </script>";
            } else {
                echo "<p style='color:red;'>Error updating record: " . $conn->error . "</p>";
            }
        } else {
            // Insert new record
            $insert_sql = "INSERT INTO bank_details (bank_name, ac_name, ac_no, branch, ifsc_code, micr, swift_code) 
                VALUES ('$bank_name', '$ac_name', '$ac_no', '$branch', '$ifsc_code', '$micr', '$swift_code')";
            if ($conn->query($insert_sql) === TRUE) {
                echo "<script>
                    Swal.fire({
                        title: 'Success!',
                        text: 'Bank details saved successfully.',
                        confirmButtonText: 'OK'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.reload = '';
                        }
                    });
                </script>";
            } else {
                echo "<p style='color:red;'>Error inserting record: " . $conn->error . "</p>";
            }
        }
    }
?>

        <div class="dashboard-main-body">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
                <h6 class="fw-semibold mb-0">Bank Details</h6>
            </div>

            <div class="card h-100 p-0 radius-12 overflow-hidden">
                <div class="card-body p-40">
    
                    <?php if ($message): ?>
                        <p style="color: green;"><?= htmlspecialchars($message) ?></p>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="row">
                            

                            <div class="col-sm-12">
                                <div class="mb-20">
                                    <label class="form-label fw-semibold text-primary-light text-sm mb-8">Bank Name</label>
                                    <input type="text" class="form-control radius-8" name="bank_name" value="<?php echo htmlspecialchars($bank_name); ?>" required>
                                </div>
                            </div>
                            <div class="col-sm-12">
                                <div class="mb-20">
                                <label class="form-label fw-semibold text-primary-light text-sm mb-8">Account Name</label>
                                <input type="text" class="form-control radius-8" name="ac_name" value="<?php echo htmlspecialchars($ac_name); ?>" required>
                                </div>
                            </div>
                            <div class="col-sm-12">
                                <div class="mb-20">
                                <label class="form-label fw-semibold text-primary-light text-sm mb-8">Account Number</label>
                                <input type="text" class="form-control radius-8" name="ac_no" value="<?php echo htmlspecialchars($ac_no); ?>" required>
                                </div>
                            </div>
                            <div class="col-sm-12">
                                <div class="mb-20">
                                <label class="form-label fw-semibold text-primary-light text-sm mb-8">Branch</label>
                                <input type="text" class="form-control radius-8" name="branch" value="<?php echo htmlspecialchars($branch); ?>" required>
                                </div>
                            </div>
                            <div class="col-sm-12">
                                <div class="mb-20">
                                <label class="form-label fw-semibold text-primary-light text-sm mb-8">IFSC Code</label>
                                <input type="text" class="form-control radius-8" name="ifsc_code" value="<?php echo htmlspecialchars($ifsc_code); ?>" required>
                                </div>
                            </div>
                            <div class="col-sm-12">
                                <div class="mb-20">
                                <label class="form-label fw-semibold text-primary-light text-sm mb-8">MICR</label>
                                <input type="text" class="form-control radius-8" name="micr" value="<?php echo htmlspecialchars($micr); ?>" required>
                                </div>
                            </div>
                            <div class="col-sm-12">
                                <div class="mb-20">
                                <label class="form-label fw-semibold text-primary-light text-sm mb-8">SWIFT Code</label>
                                <input type="text" class="form-control radius-8" name="swift_code" value="<?php echo htmlspecialchars($swift_code); ?>" required>
                                </div>
                            </div>
                            <div class="d-flex align-items-center justify-content-center gap-3 mt-24">
                               
                                <button type="submit" class="lufera-bg bg-hover-warning-400 text-white text-md px-56 py-11 radius-8 m-auto d-block">
                                    Update
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

<?php include './partials/layouts/layoutBottom.php' ?>