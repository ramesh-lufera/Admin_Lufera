<?php include './partials/layouts/layoutTop.php' ?>
<?php
    $id = 0;
    $full_name = $email = $phone_no = $website = $country = $city = $state = $zip_code = $address = "";
    
    // Fetch existing data (assuming only one record)
    $sql = "SELECT * FROM company LIMIT 1";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $id = $row['id'];
        $full_name = $row['full_name'];
        $email = $row['email'];
        $phone_no = $row['phone_no'];
        $website = $row['website'];
        $country = $row['country'];
        $city = $row['city'];
        $state = $row['state'];
        $zip_code = $_POST['zip_code'];
        $address = $_POST['address'];
    }
    
    // Handle form submission
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $full_name = $_POST['full_name'];
        $email = $_POST['email'];
        $phone_no = $_POST['phone_no'];
        $website = $_POST['website'];
        $country = $_POST['country'];
        $city = $_POST['city'];
        $state = $_POST['state'];
        $zip_code = $_POST['zip_code'];
        $address = $_POST['address'];
    
        if ($id > 0) {
            // Update existing record
            $update_sql = "UPDATE company SET 
                full_name='$full_name',
                email='$email',
                phone_no='$phone_no',
                website='$website',
                country='$country',
                city='$city',
                state='$state',
                zip_code='$zip_code',
                address='$address'
                WHERE id=$id";
            if ($conn->query($update_sql) === TRUE) {
                echo "<script>
                    Swal.fire({
                        title: 'Success!',
                        text: 'company details updated successfully.',
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
            $insert_sql = "INSERT INTO company (full_name, email, phone_no, website, country, city, state, zip_code, address) 
                VALUES ('$full_name', '$email', '$phone_no', '$website', '$country', '$city', '$state' ,'$zip_code', '$address')";
            if ($conn->query($insert_sql) === TRUE) {
                echo "<script>
                    Swal.fire({
                        title: 'Success!',
                        text: 'company details saved successfully.',
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
            <div class="d-flex flex-wrap align-items-center gap-3 mb-24">
                <a class="cursor-pointer fw-bold" onclick="history.back()"><span class="fa fa-arrow-left"></span>&nbsp; Back</a> 
                <h6 class="fw-semibold mb-0 m-auto">Company</h6>
            </div>

            <div class="card h-100 p-0 radius-12 overflow-hidden">
                <div class="card-body p-40">
                    <form method="post">
                        <div class="row">
                            <div class="col-sm-6">
                                <div class="mb-20">
                                    <label for="name" class="form-label fw-semibold text-primary-light text-sm mb-8">Full Name <span class="text-danger-600">*</span></label>
                                    <input type="text" class="form-control radius-8" name="full_name" value="<?php echo htmlspecialchars($full_name); ?>" required>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="mb-20">
                                    <label for="email" class="form-label fw-semibold text-primary-light text-sm mb-8">Email <span class="text-danger-600">*</span></label>
                                    <input type="text" class="form-control radius-8" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="mb-20">
                                    <label for="number" class="form-label fw-semibold text-primary-light text-sm mb-8">Phone Number</label>
                                    <input type="text" class="form-control radius-8" name="phone_no" value="<?php echo htmlspecialchars($phone_no); ?>" required>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="mb-20">
                                    <label for="Website" class="form-label fw-semibold text-primary-light text-sm mb-8"> Website</label>
                                    <input type="text" class="form-control radius-8" name="website" value="<?php echo htmlspecialchars($website); ?>" required>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="mb-20">
                                    <label for="country" class="form-label fw-semibold text-primary-light text-sm mb-8">Country <span class="text-danger-600">*</span> </label>
                                    <input type="text" class="form-control radius-8" name="country" value="<?php echo htmlspecialchars($country); ?>" required>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="mb-20">
                                    <label for="city" class="form-label fw-semibold text-primary-light text-sm mb-8">City <span class="text-danger-600">*</span> </label>
                                    <input type="text" class="form-control radius-8" name="city" value="<?php echo htmlspecialchars($city); ?>" required>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="mb-20">
                                    <label for="state" class="form-label fw-semibold text-primary-light text-sm mb-8">State <span class="text-danger-600">*</span> </label>
                                    <input type="text" class="form-control radius-8" name="state" value="<?php echo htmlspecialchars($state); ?>" required>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="mb-20">
                                    <label for="zip" class="form-label fw-semibold text-primary-light text-sm mb-8"> Zip Code <span class="text-danger-600">*</span></label>
                                    <input type="text" class="form-control radius-8" name="zip_code" value="<?php echo htmlspecialchars($zip_code); ?>" required>
                                </div>
                            </div>
                            <div class="col-sm-12">
                                <div class="mb-20">
                                    <label for="address" class="form-label fw-semibold text-primary-light text-sm mb-8"> Address <span class="text-danger-600">*</span></label>
                                    <input type="text" class="form-control radius-8" name="address" value="<?php echo htmlspecialchars($address); ?>" required>
                                </div>
                            </div>
                            <div class="d-flex align-items-center justify-content-center gap-3 mt-24">
                                <button type="submit" class="lufera-bg bg-hover-warning-400 text-white text-md px-56 py-11 radius-8 m-auto d-block">
                                    Save Change
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

<?php include './partials/layouts/layoutBottom.php' ?>