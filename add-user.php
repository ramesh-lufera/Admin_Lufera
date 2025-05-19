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
            <style>
                .toggle-icon-pass {
                    position: absolute;
                    top: 22px;
                    right: 28px;
                    transform: translateY(-50%);
                    cursor: pointer;
                    user-select: none;
                    font-size: 20px;
                }
                input::-webkit-outer-spin-button,
                input::-webkit-inner-spin-button {
                -webkit-appearance: none;
                margin: 0;
                }

                /* Firefox */
                input[type=number] {
                -moz-appearance: textfield;
                }
                </style>

<?php
function generateRandomPassword($length = 10) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()-_';
    $password = '';
    $maxIndex = strlen($chars) - 1;
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[random_int(0, $maxIndex)];
    }
    return $password;
}

// Define it here before using in the form
$generatedPassword = generateRandomPassword();
?>
<?php include './partials/layouts/layoutTop.php' ?>
<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $fname = $_POST['fname'];
        $lname = $_POST['lname'];
        $business_name = $_POST['bname'];
        $email = $_POST['email'];
        $phone = $_POST['phone'];
        $address = $_POST['address'];
        $city = $_POST['city'];
        $state = $_POST['state'];
        $country = $_POST['country'];
        $pincode = $_POST['pincode'];
        $dob = $_POST['dob'];
        $username = $_POST['username'];
        $password = $_POST['password'];
        $method = "2";
        $role = $_POST['role'];
        $created_at = date("Y-m-d H:i:s");
        $photo = NULL;
        function generateUserId() {
            $letters = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 3);
            $numbers = substr(str_shuffle('0123456789'), 0, 3);
            return $letters . $numbers;
        }
        $newUserId = generateUserId();
        
        // $sql = "INSERT INTO users (user_id,first_name, last_name, business_name, email, phone, address, city, state, country, pincode, dob, created_at)
        //         VALUES ('$newUserId', '$first_name', '$last_name', '$business_name', '$email', '$phone', '$address', '$city', '$state', '$country', '$pincode', '$dob', NOW())";

        $stmt = $conn->prepare("INSERT INTO users (user_id, username, email, phone, password, first_name,last_name,business_name,address,city,state,country,pincode,dob,created_at,method,role,photo ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssssssssssssss", $newUserId, $username, $email, $phone, $password, $fname, $lname, $business_name, $address, $city, $state, $country, $pincode, $dob, $created_at, $method, $role, $photo);
            
        if ($stmt->execute()) {
            echo "
            <script>
                Swal.fire({
                    title: 'Success!',
                    text: 'User created successfully.',
                    confirmButtonText: 'OK'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = 'users-list.php';
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
        // if (mysqli_query($conn, $sql)) {
        //     echo "<script>alert('User added successfully!'); window.location.href='users-list.php';</script>";
        //     exit();
        // } else {
        //     echo "<script>alert('Error: " . mysqli_error($conn) . "');</script>";
        // }
    }
?>

        <div class="dashboard-main-body">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
                <h6 class="fw-semibold mb-0">Add User</h6>
            </div>

            <div class="card h-100 p-0 radius-12">
                <div class="card-body p-24">
                    <div class="row justify-content-center">
                        <div class="col-xxl-12 col-xl-8 col-lg-10">
                            <h6 class="text-md text-primary-light mb-16" style="font-size: 20px !important">Profile</h6>
                            <form method="POST" class="row gy-3 needs-validation" novalidate>
                                <div class="mb-2">
                                    <label for="name" class="form-label fw-semibold text-primary-light text-sm mb-8">First Name <span class="text-danger-600">*</span></label>
                                    <div class="icon-field has-validation">
                                        <span class="icon" style="top: 12px !important">
                                            <iconify-icon icon="f7:person"></iconify-icon>
                                        </span>
                                        <input type="text" class="form-control radius-8" name="fname" placeholder="Enter First Name" required maxlength="20">
                                        <div class="invalid-feedback">
                                            First name is required
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-2">
                                    <label for="name" class="form-label fw-semibold text-primary-light text-sm mb-8">Last Name <span class="text-danger-600">*</span></label>
                                    <div class="icon-field has-validation">
                                        <span class="icon" style="top: 12px !important">
                                            <iconify-icon icon="mdi:user"></iconify-icon>
                                        </span>
                                        <input type="text" class="form-control radius-8" name="lname" placeholder="Enter Last Name" required maxlength="20">
                                        <div class="invalid-feedback">
                                            Last name is required
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-2">
                                    <label for="name" class="form-label fw-semibold text-primary-light text-sm mb-8">Business Name <span class="text-danger-600">*</span></label>
                                    <div class="icon-field has-validation">
                                        <span class="icon" style="top: 12px !important">
                                            <iconify-icon icon="mdi:business"></iconify-icon>
                                        </span>
                                        <input type="text" class="form-control radius-8" name="bname" placeholder="Enter Business Name" required maxlength="20">
                                        <div class="invalid-feedback">
                                            Business name is required
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-2">
                                    <label for="name" class="form-label fw-semibold text-primary-light text-sm mb-8">Role <span class="text-danger-600">*</span></label>
                                    <div class="icon-field has-validation">
                                        <span class="icon" style="top: 12px !important">
                                            <iconify-icon icon="mdi:badge-account"></iconify-icon>
                                        </span>
                                        <!-- <input type="text" class="form-control radius-8" name="bname" placeholder="Enter Business Name" required> -->
                                        <select class="form-control" name="role" required>
                                            <option value="" disabled selected>Select Role</option>
                                            <option value="1">Admin</option>
                                            <option value="2">Sales</option>
                                            <option value="3">Accounts</option>
                                            <option value="4">Developer</option>
                                            <option value="5">Marketing</option>
                                            <option value="6">User</option>
                                        </select>
                                        <div class="invalid-feedback">
                                            Role is required
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-2">
                                    <label for="email" class="form-label fw-semibold text-primary-light text-sm mb-8">Email <span class="text-danger-600">*</span></label>
                                    <div class="icon-field has-validation">
                                        <span class="icon" style="top: 12px !important">
                                            <iconify-icon icon="mage:email"></iconify-icon>
                                        </span>
                                        <input type="email" class="form-control radius-8" name="email" placeholder="Enter email address" required maxlength="30">
                                        <div class="invalid-feedback">
                                            Enter a valid Email Address
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-2">
                                    <label for="number" class="form-label fw-semibold text-primary-light text-sm mb-8">Phone <span class="text-danger-600">*</span></label>
                                    <div class="icon-field has-validation">
                                        <span class="icon" style="top: 12px !important">
                                            <iconify-icon icon="solar:phone-calling-linear"></iconify-icon>
                                        </span>
                                        <input type="number" class="form-control radius-8" name="phone" placeholder="Enter phone number" required onkeydown="return event.key !== 'e'" maxlength="20">
                                        <div class="invalid-feedback">
                                            Phone number is required
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-2">
                                    <label for="name" class="form-label fw-semibold text-primary-light text-sm mb-8">Username <span class="text-danger-600">*</span></label>
                                    <div class="icon-field has-validation">
                                        <span class="icon" style="top: 12px !important">
                                            <iconify-icon icon="mdi:user"></iconify-icon>
                                        </span>
                                        <input type="text" class="form-control radius-8" name="username" placeholder="Enter Username" required readonly maxlength="20">
                                        <div class="invalid-feedback">
                                            Username is required
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-2">
                                    <label for="name" class="form-label fw-semibold text-primary-light text-sm mb-8">Password <span class="text-danger-600">*</span></label>
                                    <div class="icon-field has-validation">
                                        <span class="icon" style="top: 12px !important">
                                            <iconify-icon icon="mdi:lock"></iconify-icon>
                                        </span>
                                        <input type="password" class="form-control radius-8" name="password" id="password" placeholder="Enter Password" value="<?php echo $generatedPassword; ?>" required maxlength="20">
                                        <i class="ri-eye-line toggle-icon-pass" id="togglePassword"></i>
                                        <div class="invalid-feedback">
                                            Password is required
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-2">
                                    <label for="name" class="form-label fw-semibold text-primary-light text-sm mb-8">Date of Birth</label>
                                    <div class="icon-field has-validation">
                                    <span class="icon" style="top: 12px !important">
                                        <iconify-icon icon="mdi:calendar"></iconify-icon>
                                    </span>
                                    <input type="date" class="form-control radius-8" name="dob">
                                    </div>
                                </div>
                                <div class="mb-2">
                                    <label for="desc" class="form-label fw-semibold text-primary-light text-sm mb-8">Address</label>
                                    <div class="icon-field has-validation">
                                        <span class="icon" style="top: 12px !important">
                                            <iconify-icon icon="mdi:map-marker-outline"></iconify-icon>
                                        </span>
                                        <textarea class="form-control radius-8" name="address" placeholder="Address"></textarea>
                                    </div>
                                </div>
                                <div class="mb-2">
                                    <label for="name" class="form-label fw-semibold text-primary-light text-sm mb-8">City </label>
                                    <div class="icon-field has-validation">
                                        <span class="icon" style="top: 12px !important">
                                            <iconify-icon icon="mdi:city-variant-outline"></iconify-icon>
                                        </span>
                                        <input type="text" class="form-control radius-8" name="city" placeholder="City" maxlength="20">
                                    </div>
                                </div>
                                <div class="mb-2">
                                    <label for="name" class="form-label fw-semibold text-primary-light text-sm mb-8">State </label>
                                    <div class="icon-field has-validation">
                                        <span class="icon" style="top: 12px !important">
                                            <iconify-icon icon="mdi:map-marker-radius-outline"></iconify-icon>
                                        </span>
                                        <input type="text" class="form-control radius-8" name="state" placeholder="State" maxlength="20">
                                    </div>
                                </div>
                                <div class="mb-2">
                                    <label for="name" class="form-label fw-semibold text-primary-light text-sm mb-8">Country </label>
                                    <div class="icon-field has-validation">
                                        <span class="icon" style="top: 12px !important">
                                            <iconify-icon icon="mdi:earth"></iconify-icon>
                                        </span>
                                        <input type="text" class="form-control radius-8" name="country" placeholder="Country" maxlength="20">
                                    </div>
                                </div>
                                <div class="mb-2">
                                    <label for="name" class="form-label fw-semibold text-primary-light text-sm mb-8">Pincode</label>
                                    <div class="icon-field has-validation">
                                    <span class="icon" style="top: 12px !important">
                                        <iconify-icon icon="mdi:pin"></iconify-icon>
                                    </span>
                                    <input type="text" class="form-control radius-8" name="pincode" placeholder="Pincode" maxlength="10">
                                    </div>
                                </div>
                                
                                <div class="d-flex align-items-center justify-content-center gap-3">
                                    <button type="button" class="border border-danger-600 bg-hover-danger-200 text-danger-600 text-md px-56 py-11 radius-8">
                                        Cancel
                                    </button>
                                    <button type="submit" class="btn lufera-bg text-white text-md px-56 py-12 radius-8">
                                        Save
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <script>
            const passwordInput = document.getElementById("password");
            const toggleIcon = document.getElementById("togglePassword");

            toggleIcon.addEventListener("click", () => {
                const isPassword = passwordInput.type === "password";
                passwordInput.type = isPassword ? "text" : "password";
                toggleIcon.classList.toggle("ri-eye-line");
                toggleIcon.classList.toggle("ri-eye-off-line");
            });

            document.addEventListener("DOMContentLoaded", function () {
                const emailInput = document.querySelector('input[name="email"]');
                const usernameInput = document.querySelector('input[name="username"]');

                emailInput.addEventListener("input", function () {
                    const emailValue = emailInput.value;
                    const usernamePart = emailValue.split("@")[0];
                    usernameInput.value = usernamePart;
                });
            });

           
            document.addEventListener("DOMContentLoaded", function () {
                const emailInput = document.querySelector('input[name="email"]');
                const emailFeedback = emailInput.nextElementSibling;
                const form = emailInput.closest("form");
                let emailExists = false; // <--- flag

                emailInput.addEventListener("blur", function () {
                    const email = emailInput.value.trim();
                    if (!email) return;

                    fetch('check_email.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ email }),
                    })
                    .then(response => response.json())
                    .then(data => {
                        emailExists = data.exists;
                        if (emailExists) {
                            emailInput.classList.add('is-invalid');
                            emailFeedback.textContent = "Email already exists";
                        } else {
                            emailInput.classList.remove('is-invalid');
                            emailFeedback.textContent = "";
                        }
                    })
                    .catch(error => {
                        console.error('Error checking email:', error);
                    });
                });

                form.addEventListener("submit", function (e) {
                    if (emailExists) {
                        e.preventDefault(); // Prevent form submission
                        emailInput.focus(); // Optionally refocus the input
                    }
                });
            });


        </script>
<?php include './partials/layouts/layoutBottom.php' ?>
