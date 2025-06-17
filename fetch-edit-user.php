<style>
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
include 'partials/connection.php';

if (isset($_POST['id'])) {
    $id = intval($_POST['id']);
    $sql = "SELECT * FROM users WHERE id = $id";
    $result = mysqli_query($conn, $sql);
    $user = mysqli_fetch_assoc($result);

    if ($user) {
        ?>
       
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
                    <input type="text" class="form-control radius-8" id="uname" name="uname" value="<?php echo htmlspecialchars($user['username']); ?>" required readonly>

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
                    <?php
                    $roles = [
                        2 => 'Admin',
                        3 => 'Sales',
                        4 => 'Accounts',
                        5 => 'Developer',
                        6 => 'Marketing',
                        7 => 'User'
                    ];

                    $currentRoleValue = $user['role'];
                    $currentRoleLabel = isset($roles[$currentRoleValue]) ? $roles[$currentRoleValue] : 'Unknown';
                    ?>

                    <select class="form-control" name="role" required >
                        <option value="<?php echo htmlspecialchars($currentRoleValue); ?>" selected>
                            <?php echo htmlspecialchars($currentRoleLabel); ?>
                        </option>
                        <!-- <?php foreach ($roles as $value => $label): ?>
                            <?php if ($value != $currentRoleValue): ?>
                                <option value="<?php echo $value; ?>"><?php echo $label; ?></option>
                            <?php endif; ?>
                        <?php endforeach; ?> -->
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
                    <input type="email" class="form-control radius-8" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    <div id="email-msg" class="text-danger mt-2"></div>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="mb-20">
                    <label for="" class="form-label fw-semibold text-primary-light text-sm mb-8">Phone <span class="text-danger-600">*</span></label>
                    <input type="number" class="form-control radius-8" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" required onkeydown="return event.key !== 'e'" maxlength="20">
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
<script>
    document.getElementById('email').addEventListener('input', function() {
        const email = this.value;
        const usernameField = document.getElementById('uname');
        const atIndex = email.indexOf('@');
        if (atIndex > 0) {
            usernameField.value = email.substring(0, atIndex);
        }
    });

document.getElementById('email').addEventListener('input', function () {
    const email = this.value.trim();
    const userId = document.querySelector('input[name="id"]').value;

    if (email === '') {
        document.getElementById('email-msg').innerHTML = '';
        emailValid = true;
        return;
    }

    fetch('check_email_update.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `email=${encodeURIComponent(email)}&id=${encodeURIComponent(userId)}`
    })
    .then(response => response.text())
    .then(data => {
        const msg = document.getElementById('email-msg');
        if (data.trim() !== '') {
            msg.innerHTML = data;
            emailValid = false;
        } else {
            msg.innerHTML = '';
            emailValid = true;
        }
    });
});

</script>

