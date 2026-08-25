<?php include './partials/layouts/layoutTop.php'; 

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

?>
<?php
    $id = 0;
    $full_name = $email = $phone_no = $website = $country = $city = $state = $zip_code = $address = $gst_in = $logo = $sign_in_img = $sign_up_img = "";

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
        $zip_code = $row['zip_code'];
        $gst_in = $row['gst_in'];
        $address = $row['address'];
        $logo = $row['logo']; // 👈 Added
        $sign_in_img = $row['sign_in_img'];
        $sign_up_img = $row['sign_up_img'];
        $maintenance_mode = $row['maintenance_mode'];
    }

    // Handle form submission
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $full_name = $_POST['full_name'];
        $email = $_POST['email'];
        $country_code = isset($_POST['country_code']) ? trim($_POST['country_code']) : '';
        $raw_phone = isset($_POST['phone_no']) ? trim($_POST['phone_no']) : '';
        $phone_no = $country_code . ' ' . $raw_phone;
        $website = $_POST['website'];
        $country = $_POST['country'];
        $city = $_POST['city'];
        $state = $_POST['state'];
        $zip_code = $_POST['zip_code'];
        $gst_in = $_POST['gst_in'];
        $address = $_POST['address'];
        $maintenance_mode = isset($_POST['maintenance_mode']) ? '1' : '0';

        // 👇 Handle file upload
        $upload_dir = "uploads/company_logo/";
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $logo_name = $logo; // keep old logo if no new one uploaded
        $sign_in_name = $sign_in_img;
        $sign_up_name = $sign_up_img;
        $allowed_types = ['jpg','jpeg','png','gif','webp'];
        $max_file_size = 1 * 1024 * 1024; // 1 MB

        // LOGO IMAGE
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) {
            $file_tmp = $_FILES['logo']['tmp_name'];
            $file_size = $_FILES['logo']['size'];
            if ($file_size > $max_file_size) {
                echo "<script>
                    Swal.fire({
                        icon: 'error',
                        title: 'File Too Large',
                        text: 'Company Logo must not exceed 1 MB.',
                        confirmButtonText: 'OK'
                    });
                </script>";
            } else {
                $file_name = time() . '_' . basename($_FILES['logo']['name']);
                $target_file = $upload_dir . $file_name;
                $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
                if (in_array($imageFileType, $allowed_types)) {
                    if (move_uploaded_file($file_tmp, $target_file)) {
                        $logo_name = $file_name;
                    }
                }
            }
        }
        
        // SIGN IN IMAGE
        if (isset($_FILES['sign_in_img']) && $_FILES['sign_in_img']['error'] == 0) {
            $tmp = $_FILES['sign_in_img']['tmp_name'];
            $file_size = $_FILES['sign_in_img']['size'];
            if ($file_size > $max_file_size) {
                echo "<script>
                    Swal.fire({
                        icon: 'error',
                        title: 'File Too Large',
                        text: 'Sign In Image must not exceed 1 MB.',
                        confirmButtonText: 'OK'
                    });
                </script>";
            } else {
                $name = time() . '_signin_' . basename($_FILES['sign_in_img']['name']);
                $path = $upload_dir . $name;
                $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                if (in_array($ext, $allowed_types) && move_uploaded_file($tmp, $path)) {
                    $sign_in_name = $name;
                }
            }
        }

        // SIGN UP IMAGE
        if (isset($_FILES['sign_up_img']) && $_FILES['sign_up_img']['error'] == 0) {
            $tmp = $_FILES['sign_up_img']['tmp_name'];
            $file_size = $_FILES['sign_up_img']['size'];
            if ($file_size > $max_file_size) {
                echo "<script>
                    Swal.fire({
                        icon: 'error',
                        title: 'File Too Large',
                        text: 'Sign Up Image must not exceed 1 MB.',
                        confirmButtonText: 'OK'
                    });
                </script>";
            } else {
                $name = time() . '_signup_' . basename($_FILES['sign_up_img']['name']);
                $path = $upload_dir . $name;
                $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                if (in_array($ext, $allowed_types) && move_uploaded_file($tmp, $path)) {
                    $sign_up_name = $name;
                }
            }
        }

        if ($id > 0) {

            // Detect changed fields
            $changed_fields = [];
        
            if ($full_name !== $row['full_name']) $changed_fields[] = "Full Name";
            if ($email !== $row['email']) $changed_fields[] = "Email";
            if ($phone_no !== $row['phone_no']) $changed_fields[] = "Phone Number";
            if ($website !== $row['website']) $changed_fields[] = "Website";
            if ($country !== $row['country']) $changed_fields[] = "Country";
            if ($city !== $row['city']) $changed_fields[] = "City";
            if ($state !== $row['state']) $changed_fields[] = "State";
            if ($zip_code !== $row['zip_code']) $changed_fields[] = "Zip Code";
            if ($address !== $row['address']) $changed_fields[] = "Address";
            if ($gst_in !== $row['gst_in']) $changed_fields[] = "GSTIN";
            if ($logo_name !== $row['logo']) $changed_fields[] = "Logo";
            if ($sign_in_name !== $row['sign_in_img']) $changed_fields[] = "Sign In Image";
            if ($sign_up_name !== $row['sign_up_img']) $changed_fields[] = "Sign Up Image";
        
            // Prepare action
            if (empty($changed_fields)) {
                $action = "No fields were changed.";
            } else {
                $action = implode(", ", $changed_fields) . " updated successfully.";
            }
        
            // UPDATE query
            $update_sql = "UPDATE company SET 
                full_name='$full_name',
                email='$email',
                phone_no='$phone_no',
                website='$website',
                country='$country',
                city='$city',
                state='$state',
                zip_code='$zip_code',
                gst_in='$gst_in',
                address='$address',
                logo='$logo_name',
                sign_in_img='$sign_in_name',
                sign_up_img='$sign_up_name',
                maintenance_mode='$maintenance_mode'
                WHERE id=$id";
        
            if ($conn->query($update_sql) === TRUE) {
        
                logActivity(
                    $conn,
                    $loggedInUserId,
                    "Company",
                    $action
                );
        
                echo "<script>
                    Swal.fire({
                        title: 'Success!',
                        text: 'Company details updated successfully.',
                        confirmButtonText: 'OK'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = window.location.pathname;
                        }
                    });
                </script>";
        
            } else {
                echo "<p style='color:red;'>Error updating record: " . $conn->error . "</p>";
            }
        }
        else {
            // Insert new record
            $insert_sql = "INSERT INTO company (full_name, email, phone_no, website, country, city, state, zip_code, address, gst_in, logo, sign_in_img, sign_up_img, maintenance_mode) 
                VALUES ('$full_name', '$email', '$phone_no', '$website', '$country', '$city', '$state' ,'$zip_code', '$address', '$gst_in', '$logo_name', '$sign_in_name', '$sign_up_name', '$maintenance_mode')";

            if ($conn->query($insert_sql) === TRUE) {
                echo "<script>
                    Swal.fire({
                        title: 'Success!',
                        text: 'Company details saved successfully.',
                        confirmButtonText: 'OK'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = window.location.pathname;
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
                <a class="cursor-pointer fw-bold" onclick="history.back()"><span class="fa fa-arrow-left"></span>&nbsp; Back</a> 
                <h6 class="fw-semibold mb-0">Company</h6>
                <a class="cursor-pointer fw-bold visibility-hidden" onclick="history.back()"><span class="fa fa-arrow-left"></span>&nbsp; Back</a> 
            </div>

            <div class="card h-100 p-0 radius-12 overflow-hidden">
                <div class="card-body p-40">
                    <form method="post" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-sm-12">
                                <div class="mb-20">
                                    <label for="logo" class="form-label fw-semibold text-primary-light text-sm mb-8">
                                        Company Logo
                                    </label>
                                    <input type="file" class="form-control radius-8" name="logo" accept="image/*">
                                    <?php if (!empty($logo)): ?>
                                        <div class="mt-2">
                                            <img src="uploads/company_logo/<?php echo htmlspecialchars($logo); ?>" alt="Company Logo" style="max-width: 150px; border-radius: 8px;">
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="col-sm-6">
                                <div class="mb-20">
                                    <label for="name" class="form-label fw-semibold text-primary-light text-sm mb-8">Full Name <span class="text-danger-600">*</span></label>
                                    <input type="text" class="form-control radius-8" name="full_name" value="<?php echo htmlspecialchars($full_name); ?>" maxlength="50px" required>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="mb-20">
                                    <label for="email" class="form-label fw-semibold text-primary-light text-sm mb-8">Email <span class="text-danger-600">*</span></label>
                                    <input type="text" class="form-control radius-8" name="email" value="<?php echo htmlspecialchars($email); ?>" maxlength="50px" required>
                                </div>
                            </div>
                            <?php
                        // Extract country code and actual phone number if already stored combined (e.g., "+1 9876543210")
                        $selected_country_code = "+91"; // Default fallback
                        $raw_phone_number = $phone_no;

                        // Simple parsing logic if phone_no has a space or plus format
                        if (!empty($phone_no)) {
                            $parts = explode(' ', trim($phone_no), 2);
                            if (count($parts) == 2 && str_starts_with($parts[0], '+')) {
                                $selected_country_code = $parts[0];
                                $raw_phone_number = $parts[1];
                            }
                        }

                        // Comprehensive list with Country Name as the key and Code as the value/label display
                        $country_codes = [
                            "Afghanistan" => "+93",
                            "Albania" => "+355",
                            "Algeria" => "+213",
                            "Argentina" => "+54",
                            "Australia" => "+61",
                            "Austria" => "+43",
                            "Bangladesh" => "+880",
                            "Belgium" => "+32",
                            "Brazil" => "+55",
                            "Canada / USA" => "+1",
                            "China" => "+86",
                            "Colombia" => "+57",
                            "Egypt" => "+20",
                            "France" => "+33",
                            "Germany" => "+49",
                            "Greece" => "+30",
                            "Hong Kong" => "+852",
                            "Hungary" => "+36",
                            "India" => "+91",
                            "Indonesia" => "+62",
                            "Ireland" => "+353",
                            "Israel" => "+972",
                            "Italy" => "+39",
                            "Japan" => "+81",
                            "Kenya" => "+254",
                            "Malaysia" => "+60",
                            "Mexico" => "+52",
                            "Netherlands" => "+31",
                            "New Zealand" => "+64",
                            "Nigeria" => "+234",
                            "Norway" => "+47",
                            "Pakistan" => "+92",
                            "Philippines" => "+63",
                            "Poland" => "+48",
                            "Portugal" => "+351",
                            "Russia" => "+7",
                            "Saudi Arabia" => "+966",
                            "Singapore" => "+65",
                            "South Africa" => "+27",
                            "South Korea" => "+82",
                            "Spain" => "+34",
                            "Sweden" => "+46",
                            "Switzerland" => "+41",
                            "Taiwan" => "+886",
                            "Thailand" => "+66",
                            "Turkey" => "+90",
                            "United Arab Emirates" => "+971",
                            "United Kingdom" => "+44",
                            "Vietnam" => "+84"
                        ];
                    ?>

                    <div class="col-sm-6">
                        <div class="mb-20">
                            <label for="number" class="form-label fw-semibold text-primary-light text-sm mb-8">Phone Number <span class="text-danger-600">*</span></label>
                            <div class="input-group">
                                <select name="country_code" class="form-select radius-8 p-10" style="max-width: 75px; flex: 0 0 90px; height: 2.75rem" required>
                                    <?php foreach ($country_codes as $country_name => $code): ?>
                                        <option value="<?php echo $code; ?>" <?php echo ($selected_country_code == $code) ? 'selected' : ''; ?>>
                                            <?php echo ' ' . $code . ' ' . $country_name; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="text" class="form-control radius-8" name="phone_no" value="<?php echo htmlspecialchars($raw_phone_number); ?>" placeholder="Phone number" maxlength="20px" required>
                            </div>
                        </div>
                    </div>
                            <div class="col-sm-6">
                                <div class="mb-20">
                                    <label for="Website" class="form-label fw-semibold text-primary-light text-sm mb-8"> Website</label>
                                    <input type="text" class="form-control radius-8" name="website" value="<?php echo htmlspecialchars($website); ?>" maxlength="50px" required>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="mb-20">
                                    <label for="country" class="form-label fw-semibold text-primary-light text-sm mb-8">Country <span class="text-danger-600">*</span> </label>
                                    <input type="text" class="form-control radius-8" name="country" value="<?php echo htmlspecialchars($country); ?>" maxlength="50px" required>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="mb-20">
                                    <label for="city" class="form-label fw-semibold text-primary-light text-sm mb-8">City <span class="text-danger-600">*</span> </label>
                                    <input type="text" class="form-control radius-8" name="city" value="<?php echo htmlspecialchars($city); ?>" maxlength="50px" required>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="mb-20">
                                    <label for="state" class="form-label fw-semibold text-primary-light text-sm mb-8">State <span class="text-danger-600">*</span> </label>
                                    <input type="text" class="form-control radius-8" name="state" value="<?php echo htmlspecialchars($state); ?>" maxlength="50px" required>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="mb-20">
                                    <label for="zip" class="form-label fw-semibold text-primary-light text-sm mb-8"> Zip Code <span class="text-danger-600">*</span></label>
                                    <input type="text" class="form-control radius-8" name="zip_code" value="<?php echo htmlspecialchars($zip_code); ?>" maxlength="50px" required>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="mb-20">
                                    <label for="address" class="form-label fw-semibold text-primary-light text-sm mb-8"> Address <span class="text-danger-600">*</span></label>
                                    <input type="text" class="form-control radius-8" name="address" value="<?php echo htmlspecialchars($address); ?>" required>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="mb-20">
                                    <label for="address" class="form-label fw-semibold text-primary-light text-sm mb-8"> GSTIN <span class="text-danger-600">*</span></label>
                                    <input type="text" class="form-control radius-8" name="gst_in" value="<?php echo htmlspecialchars($gst_in); ?>" maxlength="50px" required>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="mb-20">
                                    <label class="form-label fw-semibold text-primary-light text-sm mb-8">
                                        Sign In Image
                                    </label>
                                    <input type="file" class="form-control radius-8" name="sign_in_img" accept="image/*">

                                    <?php if (!empty($sign_in_img)): ?>
                                        <div class="mt-2">
                                            <img src="uploads/company_logo/<?php echo htmlspecialchars($sign_in_img); ?>" 
                                                style="max-width:150px;border-radius:8px;">
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="col-sm-6">
                                <div class="mb-20">
                                    <label class="form-label fw-semibold text-primary-light text-sm mb-8">
                                        Sign Up Image
                                    </label>
                                    <input type="file" class="form-control radius-8" name="sign_up_img" accept="image/*">

                                    <?php if (!empty($sign_up_img)): ?>
                                        <div class="mt-2">
                                            <img src="uploads/company_logo/<?php echo htmlspecialchars($sign_up_img); ?>" 
                                                style="max-width:150px;border-radius:8px;">
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="col-sm-12">
                                <div class="mb-20">
                                    <label class="form-label fw-semibold text-primary-light text-sm mb-12">
                                        Maintenance Mode
                                    </label>
                                    <div class="d-flex align-items-center gap-3">
                                        <label class="custom-switch">
                                            <input type="checkbox" name="maintenance_mode" id="maintenance_mode" value="1" <?php echo ($maintenance_mode == '1') ? 'checked' : ''; ?>>
                                            <span class="slider"></span>
                                        </label>

                                        <span class="fw-medium">
                                            Enable/Disable Maintenance Mode
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <style>

                            .custom-switch {
                                position: relative;
                                display: inline-block;
                                width: 58px;
                                height: 30px;
                            }

                            .custom-switch input {
                                opacity: 0;
                                width: 0;
                                height: 0;
                            }

                            .custom-switch .slider {
                                position: absolute;
                                cursor: pointer;
                                top: 0;
                                left: 0;
                                right: 0;
                                bottom: 0;
                                background: #d9d9d9;
                                transition: 0.3s;
                                border-radius: 50px;
                            }

                            .custom-switch .slider:before {
                                position: absolute;
                                content: "";
                                height: 24px;
                                width: 24px;
                                left: 3px;
                                top: 3px;
                                background: white;
                                transition: 0.3s;
                                border-radius: 50%;
                            }

                            .custom-switch input:checked + .slider {
                                /* background: #f4b400; */
                                background: var(--lufera-main-color);
                            }

                            .custom-switch input:checked + .slider:before {
                                transform: translateX(28px);
                            }

                            </style>

                            <div class="d-flex align-items-center justify-content-center gap-3 mt-24">
                                <button type="submit" class="lufera-bg lufera-text text-md px-56 py-11 radius-8 m-auto d-block">
                                    Save Change
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
<script>
document.addEventListener("DOMContentLoaded", function () {
    const maxSize = 1 * 1024 * 1024; // 1 MB
    const fileInputs = [
        document.querySelector('input[name="logo"]'),
        document.querySelector('input[name="sign_in_img"]'),
        document.querySelector('input[name="sign_up_img"]')
    ];

    fileInputs.forEach(function (input) {
        if (!input) return;
        input.addEventListener("change", function () {
            const file = this.files[0];
            if (!file) return;
            if (file.size > maxSize) {
                Swal.fire({
                    icon: 'error',
                    title: 'File Too Large',
                    text: 'Image size must not exceed 1 MB.',
                    confirmButtonText: 'OK'
                });
                // Clear selected file
                this.value = '';
            }
        });
    });
    const restrictedFields = document.querySelectorAll('input[name="phone_no"], input[name="zip_code"]');
    restrictedFields.forEach(function (input) {
        input.addEventListener("input", function () {
            // Allow numbers (0-9), hyphens (-), parentheses ( ), and spaces
            // Replaces any forbidden characters (like letters) with an empty string instantly
            this.value = this.value.replace(/[^0-9\-() ]/g, '');
        });
    });
});
</script>
<?php include './partials/layouts/layoutBottom.php' ?>