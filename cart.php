<?php include './partials/layouts/layoutTop.php' ?>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<style>
    .tagline{
        border-bottom:1px solid #fec700;
    }
</style>
<div class="dashboard-main-body">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
        <h6 class="fw-semibold mb-0">Your Cart</h6>
        
    </div>

<?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $plan_name = $_POST['plan_name'];
        $price = $_POST['price'];
        $duration = $_POST['duration'];
        $pages = $_POST['pages'];
        $support = $_POST['support'];

        $gst = $price * 0.10; // 10% GST
        $total_price = $price + $gst;
    }
?>
    <div class="mb-40">
        <div class="row gy-4">
            <div class="col-xxl-8 col-sm-6">
                <div class="card h-100 radius-12">
                    <div class="card-header">
                        <h6><?php echo $plan_name; ?></h6>
                    </div>
                    <div class="card-body p-16">
                        <label>Period</label>
                        <div class="d-flex justify-content-between">
                        <h5><?php echo $duration; ?></h5>
                        
                        <h5><?php echo $price; ?></h5>
                        </div>
                        <label class="tagline">Renews after <?php echo $duration; ?></label>
                    </div>
                </div>
            </div>
            <div class="col-xxl-4 col-sm-6">
                <div class="card h-100 radius-12">
                    
                    <div class="card-body p-16">
                    <div class="d-flex justify-content-between">
                        <h5>Sub Total</h5>
                        <h5>$<?php echo $price; ?></h5>
                    </div>
                    <label class="tagline">Subtotal does not iclude applicable taxes</label>

                    <div class="d-flex justify-content-between mt-5">
                        <label>Gst 10%</label>
                        <h5>$<?php echo $total_price?></h5>
                    </div>
                    <?php
                        $user_id = $_SESSION['user_id'];
                        $sql = "SELECT * FROM `users` WHERE id = $user_id";
                        $result = $conn->query($sql);
                        $row = $result->fetch_assoc();

                        if($row["username"] && $row["email"] && $row["phone"] && $row["first_name"] && $row["last_name"] && $row["business_name"] && $row["address"] && $row["city"] && $row["state"] && $row["country"] && $row["pincode"] != ""){
                    ?>
                    <a class="d-block" href="payment.php"> 
                        <button class="lufera-bg text-center text-white text-sm btn-sm px-12 py-10 w-100 radius-8 mt-28">Continue</button>
                    </a>
                    <?php } 
                        else{
                    ?>
                    <button class="lufera-bg text-center text-white text-sm btn-sm px-12 py-10 w-100 radius-8 mt-28" data-bs-toggle="modal" data-bs-target="#exampleModal">Update Profile & Continue</button>
                        <?php
                    }
                    ?>
                    <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="exampleModalLabel">Modal title</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                            <form id="updateForm">
                                        <div class="row">
                                            <div class="col-sm-6">
                                                <div class="mb-20">
                                                    <label for="" class="form-label fw-semibold text-primary-light text-sm mb-8">First Name <span class="text-danger-600">*</span></label>
                                                    <input type="hidden" name="id" value="<?php echo $_SESSION['user_id']; ?>">
                                                    <input type="text" class="form-control radius-8" id="" name="fname" value="<?php echo $row['first_name']; ?>" required>
                                                </div>
                                            </div>
                                            <div class="col-sm-6">
                                                <div class="mb-20">
                                                    <label for="" class="form-label fw-semibold text-primary-light text-sm mb-8">Last Name <span class="text-danger-600">*</span></label>
                                                    <input type="text" class="form-control radius-8" id="" name="lname" value="<?php echo $row['last_name']; ?>" required>
                                                </div>
                                            </div>
                                            <div class="col-sm-6">
                                                <div class="mb-20">
                                                    <label for="" class="form-label fw-semibold text-primary-light text-sm mb-8">Username <span class="text-danger-600">*</span></label>
                                                    <input type="text" class="form-control radius-8" id="" name="uname" value="<?php echo $row['username']; ?>" required>
                                                </div>
                                            </div>
                                            <div class="col-sm-6">
                                                <div class="mb-20">
                                                    <label for="" class="form-label fw-semibold text-primary-light text-sm mb-8">Bussiness Name <span class="text-danger-600">*</span></label>
                                                    <input type="text" class="form-control radius-8" id="" name="bname" value="<?php echo $row['business_name']; ?>" required>
                                                </div>
                                            </div>
                                            <div class="col-sm-6">
                                                <div class="mb-20">
                                                    <label for="" class="form-label fw-semibold text-primary-light text-sm mb-8">Email <span class="text-danger-600">*</span></label>
                                                    <input type="email" class="form-control radius-8" id="" name="email" value="<?php echo $row['email']; ?>" required>
                                                </div>
                                            </div>
                                            <div class="col-sm-6">
                                                <div class="mb-20">
                                                    <label for="" class="form-label fw-semibold text-primary-light text-sm mb-8">Phone <span class="text-danger-600">*</span></label>
                                                    <input type="text" class="form-control radius-8" id="" name="phone" value="<?php echo $row['phone']; ?>" required>
                                                </div>
                                            </div>
                                            <div class="col-sm-6">
                                                <div class="mb-20">
                                                    <label for="" class="form-label fw-semibold text-primary-light text-sm mb-8">Date of Birth <span class="text-danger-600">*</span></label>
                                                    <input type="date" class="form-control radius-8" id="" name="dob" value="<?php echo $row['dob']; ?>" required>
                                                </div>
                                            </div>
                                            <div class="col-sm-6">
                                                <div class="mb-20">
                                                    <label for="" class="form-label fw-semibold text-primary-light text-sm mb-8">Address <span class="text-danger-600">*</span></label>
                                                    <!-- <textarea class="form-control radius-8" id="" name="address" required><?php echo $row['address']; ?></textarea> -->
                                                    <input type="text" class="form-control radius-8" id="" name="address" value="<?php echo $row['address']; ?>" required>
                                                </div>
                                            </div>
                                            <div class="col-sm-6">
                                                <div class="mb-20">
                                                    <label for="" class="form-label fw-semibold text-primary-light text-sm mb-8">City <span class="text-danger-600">*</span></label>
                                                    <input type="text" class="form-control radius-8" name="city" value="<?php echo $row['city']; ?>" required>
                                                </div>
                                            </div>
                                            <div class="col-sm-6">
                                                <div class="mb-20">
                                                    <label for="" class="form-label fw-semibold text-primary-light text-sm mb-8">State <span class="text-danger-600">*</span></label>
                                                    <input type="text" class="form-control radius-8" id="" name="state" value="<?php echo $row['state']; ?>" required>
                                                </div>
                                            </div>
                                            <div class="col-sm-6">
                                                <div class="mb-20">
                                                    <label for="" class="form-label fw-semibold text-primary-light text-sm mb-8">Country <span class="text-danger-600">*</span></label>
                                                    <input type="text" class="form-control radius-8" id="" name="country" value="<?php echo $row['country']; ?>" required>
                                                </div>
                                            </div>
                                            <div class="col-sm-6">
                                                <div class="mb-20">
                                                    <label for="" class="form-label fw-semibold text-primary-light text-sm mb-8">Pin <span class="text-danger-600">*</span></label>
                                                    <input type="text" class="form-control radius-8" id="" name="pin" value="<?php echo $row['pincode']; ?>" required>
                                                </div>
                                            </div>
                                        </div>
                             </div>
                            <div class="modal-footer d-flex align-items-center justify-content-center gap-3">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <button type="submit" class="btn lufera-bg text-white">Save</button>
                            </div>
                            </form>
                            <div id="result"></div>
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
    $('#updateForm').submit(function(e) {
    e.preventDefault();

    $.ajax({
        url: 'update.php',
        type: 'POST',
        data: $(this).serialize(),
        success: function(response) {
            $('#result').html(response);
            loadUserData(); // Reload user data after update
        },
        error: function(xhr) {
            $('#result').html("Error updating data.");
        }
    });
});

loadUserData();
</script>
<?php include './partials/layouts/layoutBottom.php' ?>