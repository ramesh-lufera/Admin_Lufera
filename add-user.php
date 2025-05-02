<?php $script = '<script>
                        (() => {
                            "use strict"

                            // Fetch all the forms we want to apply custom Bootstrap validation styles to
                            const forms = document.querySelectorAll(".needs-validation")

                            // Loop over them and prevent submission
                            Array.from(forms).forEach(form => {
                                form.addEventListener("submit", event => {
                                    if (!form.checkValidity()) {
                                        event.preventDefault()
                                        event.stopPropagation()
                                    }

                                    form.classList.add("was-validated")
                                }, false)
                            })
                        })()
            </script>';?>


<?php include './partials/layouts/layoutTop.php' ?>

<?php
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $first_name = $_POST['fname'];
        $last_name = $_POST['lname'];
        $business_name = $_POST['bname'];
        $email = $_POST['email'];
        $phone = $_POST['phone'];
        $address = $_POST['address'];
        $city = $_POST['city'];
        $state = $_POST['state'];
        $country = $_POST['country'];
        $pincode = $_POST['pincode'];
        $dob = $_POST['dob'];

        $sql = "INSERT INTO users_details (first_name, last_name, business_name, email, phone, address, city, state, country, pincode, dob, created_at)
                VALUES ('$first_name', '$last_name', '$business_name', '$email', '$phone', '$address', '$city', '$state', '$country', '$pincode', '$dob', NOW())";

        if (mysqli_query($conn, $sql)) {
            echo "<script>alert('User added successfully!'); window.location.href='users-list.php';</script>";
            exit();
        } else {
            echo "<script>alert('Error: " . mysqli_error($conn) . "');</script>";
        }
    }
?>

        <div class="dashboard-main-body">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
                <h6 class="fw-semibold mb-0">Add User</h6>
                <ul class="d-flex align-items-center gap-2">
                    <li class="fw-medium">
                        <a href="index.php" class="d-flex align-items-center gap-1 hover-text-primary">
                            <iconify-icon icon="solar:home-smile-angle-outline" class="icon text-lg"></iconify-icon>
                            Dashboard
                        </a>
                    </li>
                    <li>-</li>
                    <li class="fw-medium">Add User</li>
                </ul>
            </div>

            <div class="card h-100 p-0 radius-12">
                <div class="card-body p-24">
                    <div class="row justify-content-center">
                        <div class="col-xxl-12 col-xl-8 col-lg-10">
                            <div class="card border">
                                <div class="card-body">
                                    <!-- <h6 class="text-md text-primary-light mb-16">Profile Image</h6> -->

                                    <!-- Upload Image Start -->
                                    <!-- <div class="mb-24 mt-16">
                                        <div class="avatar-upload">
                                            <div class="avatar-edit position-absolute bottom-0 end-0 me-24 mt-16 z-1 cursor-pointer">
                                                <input type='file' id="imageUpload" accept=".png, .jpg, .jpeg" hidden>
                                                <label for="imageUpload" class="w-32-px h-32-px d-flex justify-content-center align-items-center bg-primary-50 text-primary-600 border border-primary-600 bg-hover-primary-100 text-lg rounded-circle">
                                                    <iconify-icon icon="solar:camera-outline" class="icon"></iconify-icon>
                                                </label>
                                            </div>
                                            <div class="avatar-preview">
                                                <div id="imagePreview"> </div>
                                            </div>
                                        </div>
                                    </div> -->
                                    <!-- Upload Image End -->

                                    <h6 class="text-md text-primary-light mb-16" style="font-size: 20px !important">Profile</h6>
                                    <form method="POST" class="row gy-3 needs-validation" novalidate>
                                        <div class="mb-20">
                                            <label for="name" class="form-label fw-semibold text-primary-light text-sm mb-8">First Name <span class="text-danger-600">*</span></label>
                                            <div class="icon-field has-validation">
                                            <span class="icon" style="top: 12px !important">
                                                <iconify-icon icon="f7:person"></iconify-icon>
                                            </span>
                                            <input type="text" class="form-control radius-8" name="fname" placeholder="Enter First Name" required>
                                            <div class="invalid-feedback">
                                                First name required
                                            </div>
                                        </div>
                                        </div>
                                        <div class="mb-20">
                                            <label for="name" class="form-label fw-semibold text-primary-light text-sm mb-8">Last Name <span class="text-danger-600">*</span></label>
                                            <div class="icon-field has-validation">
                                            <span class="icon" style="top: 12px !important">
                                                <iconify-icon icon="mdi:user"></iconify-icon>
                                            </span>
                                            <input type="text" class="form-control radius-8" name="lname" placeholder="Enter Last Name" required>
                                            <div class="invalid-feedback">
                                                Last name required
                                            </div>
                                        </div>
                                        </div>
                                        <div class="mb-20">
                                            <label for="name" class="form-label fw-semibold text-primary-light text-sm mb-8">Business Name <span class="text-danger-600">*</span></label>
                                            <div class="icon-field has-validation">
                                            <span class="icon" style="top: 12px !important">
                                                <iconify-icon icon="mdi:business"></iconify-icon>
                                            </span>
                                            <input type="text" class="form-control radius-8" name="bname" placeholder="Enter Business Name" required>
                                            <div class="invalid-feedback">
                                                Business name required
                                            </div>
                                        </div>
                                        </div>
                                        <!-- Date of Birth -->
                                        <div class="mb-20">
                                            <label for="name" class="form-label fw-semibold text-primary-light text-sm mb-8">Date of Birth</label>
                                            <div class="icon-field has-validation">
                                            <span class="icon" style="top: 12px !important">
                                                <iconify-icon icon="mdi:calendar"></iconify-icon>
                                            </span>
                                            <input type="date" class="form-control radius-8" name="dob">
                                            </div>
                                        </div>
                                        <div class="mb-20">
                                            <label for="email" class="form-label fw-semibold text-primary-light text-sm mb-8">Email <span class="text-danger-600">*</span></label>
                                            <div class="icon-field has-validation">
                                            <span class="icon" style="top: 12px !important">
                                                <iconify-icon icon="mage:email"></iconify-icon>
                                            </span>
                                            <input type="email" class="form-control radius-8" name="email" placeholder="Enter email address" required>
                                            <div class="invalid-feedback">
                                                Provide email address
                                            </div>
                                            </div>
                                        </div>
                                        <div class="mb-20">
                                            <label for="number" class="form-label fw-semibold text-primary-light text-sm mb-8">Phone <span class="text-danger-600">*</span></label>
                                            <div class="icon-field has-validation">
                                            <span class="icon" style="top: 12px !important">
                                                <iconify-icon icon="solar:phone-calling-linear"></iconify-icon>
                                            </span>
                                            <input type="text" class="form-control radius-8" name="phone" placeholder="Enter phone number" required>
                                            <div class="invalid-feedback">
                                                Phone number required
                                            </div>
                                        </div>
                                        </div>
                                        <div class="mb-20">
                                            <label for="desc" class="form-label fw-semibold text-primary-light text-sm mb-8">Address</label>
                                            <div class="icon-field has-validation">
                                            <span class="icon" style="top: 12px !important">
                                                <iconify-icon icon="mdi:map-marker-outline"></iconify-icon>
                                            </span>
                                            <textarea class="form-control radius-8" name="address" placeholder="Address"></textarea>
                                        </div>
                                        </div>
                                        <div class="mb-20">
                                            <label for="name" class="form-label fw-semibold text-primary-light text-sm mb-8">City <span class="text-danger-600">*</span></label>
                                            <div class="icon-field has-validation">
                                            <span class="icon" style="top: 12px !important">
                                                <iconify-icon icon="mdi:city-variant-outline"></iconify-icon>
                                            </span>
                                            <input type="text" class="form-control radius-8" name="city" placeholder="City" required>
                                            <div class="invalid-feedback">
                                                City required
                                            </div>
                                        </div>
                                        </div>
                                        <div class="mb-20">
                                            <label for="name" class="form-label fw-semibold text-primary-light text-sm mb-8">State <span class="text-danger-600">*</span></label>
                                            <div class="icon-field has-validation">
                                            <span class="icon" style="top: 12px !important">
                                                <iconify-icon icon="mdi:map-marker-radius-outline"></iconify-icon>
                                            </span>
                                            <input type="text" class="form-control radius-8" name="state" placeholder="State" required>
                                            <div class="invalid-feedback">
                                                State required
                                            </div>
                                            </div>
                                        </div>
                                        <div class="mb-20">
                                            <label for="name" class="form-label fw-semibold text-primary-light text-sm mb-8">Country <span class="text-danger-600">*</span></label>
                                            <div class="icon-field has-validation">
                                            <span class="icon" style="top: 12px !important">
                                                <iconify-icon icon="mdi:earth"></iconify-icon>
                                            </span>
                                            <input type="text" class="form-control radius-8" name="country" placeholder="Country" required>
                                            <div class="invalid-feedback">
                                                Country required
                                            </div>
                                            </div>
                                        </div>
                                        <div class="mb-20">
                                            <label for="name" class="form-label fw-semibold text-primary-light text-sm mb-8">Pincode</label>
                                            <div class="icon-field has-validation">
                                            <span class="icon" style="top: 12px !important">
                                                <iconify-icon icon="mdi:pin"></iconify-icon>
                                            </span>
                                            <input type="text" class="form-control radius-8" name="pincode" placeholder="Pincode">
                                            </div>
                                        </div>
                                        
                                        <div class="d-flex align-items-center justify-content-center gap-3">
                                            <button type="button" class="border border-danger-600 bg-hover-danger-200 text-danger-600 text-md px-56 py-11 radius-8">
                                                Cancel
                                            </button>
                                            <button type="submit" class="btn btn-primary border border-primary-600 text-md px-56 py-12 radius-8" style="background-color: #fec700 !important; color: #101010 !important; border-color: #101010 !important;">
                                                Save
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

<?php include './partials/layouts/layoutBottom.php' ?>
