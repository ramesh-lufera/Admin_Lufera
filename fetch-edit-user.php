<?php
include 'partials/connection.php';

if (isset($_POST['id'])) {
    $id = intval($_POST['id']);
    $sql = "SELECT * FROM users WHERE id = $id";
    $result = mysqli_query($conn, $sql);
    $user = mysqli_fetch_assoc($result);

    if ($user) {
        ?>
        <!-- <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
        <div class="mb-3">
            <label>First Name</label>
            <input type="text" name="first_name" class="form-control" value="<?php echo htmlspecialchars($user['first_name']); ?>">
        </div>
        <div class="mb-3">
            <label>Last Name</label>
            <input type="text" name="last_name" class="form-control" value="<?php echo htmlspecialchars($user['last_name']); ?>">
        </div>
        <div class="mb-3">
            <label>Email</label>
            <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>">
        </div>
        <div class="mb-3">
            <label>Phone</label>
            <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone']); ?>">
        </div>
        <div class="mb-3">
            <label>Username</label>
            <input type="text" name="username" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>">
        </div> -->


        <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
        <div class="row">
            <div class="col-sm-6">
                <div class="mb-20">
                    <label for="" class="form-label fw-semibold text-primary-light text-sm mb-8">First Name <span class="text-danger-600">*</span></label>
                    <input type="text" class="form-control radius-8" id="" name="fname" value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="mb-20">
                    <label for="" class="form-label fw-semibold text-primary-light text-sm mb-8">Last Name <span class="text-danger-600">*</span></label>
                    <input type="text" class="form-control radius-8" id="" name="lname" value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="mb-20">
                    <label for="" class="form-label fw-semibold text-primary-light text-sm mb-8">Username <span class="text-danger-600">*</span></label>
                    <input type="text" class="form-control radius-8" id="" name="uname" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="mb-20">
                    <label for="" class="form-label fw-semibold text-primary-light text-sm mb-8">Password <span class="text-danger-600">*</span></label>
                    <input type="password" class="form-control radius-8" id="" name="pass" value="<?php echo htmlspecialchars($user['password']); ?>" required>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="mb-20">
                    <label for="" class="form-label fw-semibold text-primary-light text-sm mb-8">Role <span class="text-danger-600">*</span></label>
                    <select class="form-control" name="role" required>
                        <option value="<?php echo htmlspecialchars($user['role']); ?>" disabled selected><?php echo htmlspecialchars($user['role']); ?></option>
                        <option value="Admin">Admin</option>
                        <option value="Sales">Sales</option>
                        <option value="Accounts">Accounts</option>
                        <option value="Developer">Developer</option>
                        <option value="Marketing">Marketing</option>
                        <option value="User">User</option>
                    </select>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="mb-20">
                    <label for="" class="form-label fw-semibold text-primary-light text-sm mb-8">Bussiness Name <span class="text-danger-600">*</span></label>
                    <input type="text" class="form-control radius-8" id="" name="bname" value="<?php echo htmlspecialchars($user['business_name']); ?>" required>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="mb-20">
                    <label for="" class="form-label fw-semibold text-primary-light text-sm mb-8">Email <span class="text-danger-600">*</span></label>
                    <input type="email" class="form-control radius-8" id="" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="mb-20">
                    <label for="" class="form-label fw-semibold text-primary-light text-sm mb-8">Phone <span class="text-danger-600">*</span></label>
                    <input type="text" class="form-control radius-8" id="" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" required>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="mb-20">
                    <label for="" class="form-label fw-semibold text-primary-light text-sm mb-8">Date of Birth </label>
                    <input type="date" class="form-control radius-8" id="" name="dob" value="<?php echo htmlspecialchars($user['dob']); ?>">
                </div>
            </div>
            <div class="col-sm-6">
                <div class="mb-20">
                    <label for="" class="form-label fw-semibold text-primary-light text-sm mb-8">Address </label>
                    <!-- <textarea class="form-control radius-8" id="" name="address" required><?php echo $row['address']; ?></textarea> -->
                    <input type="text" class="form-control radius-8" id="" name="address" value="<?php echo htmlspecialchars($user['address']); ?>">
                </div>
            </div>
            <div class="col-sm-6">
                <div class="mb-20">
                    <label for="" class="form-label fw-semibold text-primary-light text-sm mb-8">City </label>
                    <input type="text" class="form-control radius-8" name="city" value="<?php echo htmlspecialchars($user['city']); ?>">
                </div>
            </div>
            <div class="col-sm-6">
                <div class="mb-20">
                    <label for="" class="form-label fw-semibold text-primary-light text-sm mb-8">State </label>
                    <input type="text" class="form-control radius-8" id="" name="state" value="<?php echo htmlspecialchars($user['state']); ?>">
                </div>
            </div>
            <div class="col-sm-6">
                <div class="mb-20">
                    <label for="" class="form-label fw-semibold text-primary-light text-sm mb-8">Country</label>
                    <input type="text" class="form-control radius-8" id="" name="country" value="<?php echo htmlspecialchars($user['country']); ?>">
                </div>
            </div>
            <div class="col-sm-6">
                <div class="mb-20">
                    <label for="" class="form-label fw-semibold text-primary-light text-sm mb-8">Pin </label>
                    <input type="text" class="form-control radius-8" id="" name="pin" value="<?php echo htmlspecialchars($user['pincode']); ?>">
                </div>
            </div>
        </div>
        <?php
    } else {
        echo "<p>User not found.</p>";
    }
}
?>
