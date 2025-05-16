<?php include './partials/layouts/layoutTop.php' ?>
<style>
    .order-card{
        border-bottom: 1px solid lightgray;
        align-items: center;
        padding: 15px 0;
    }
</style>
<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
<?php

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $plan_name = $_POST['plan_name'];
        $price = $_POST['price'];
        $duration = $_POST['duration'];
        $total_price = $_POST['total_price'];
        $rec_id = $_POST['receipt_id'];

        // $gst = $price * 0.18; // 10% GST
        // $total_price = $price + $gst;

        $gst = $total_price - $price;
    }

    if (isset($_POST['save'])) {
        $pay_method = $_POST['pay_method'];
        $rec_id = $_POST['rec_id'];
        $plan_name = $_POST['plan_name'];
        $duration = $_POST['duration'];
        $total_price = $_POST['total_price'];
        $created_at = date("Y-m-d H:i:s");
        $price = $_POST['price'];
        $user_id = $_SESSION['user_id'];
        $sql = "SELECT user_id FROM users WHERE id = $user_id";
        $result = mysqli_query($conn, $sql);
        $row = mysqli_fetch_assoc($result);
        $client_id = $row['user_id'];

        $sql = "INSERT INTO orders (user_id, invoice_id, plan, duration, amount, price, status, payment_method, created_on) VALUES 
                                   ('$client_id', '$rec_id', '$plan_name', '$duration' ,'$total_price', '$price', 'Pending', '$pay_method', '$created_at')";


if (mysqli_query($conn, $sql)) {
    echo "
    <script>
        Swal.fire({
            title: 'Success!',
            text: 'Invoice Created Successfully.',
            confirmButtonText: 'OK'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'invoice-preview.php?id=$rec_id';
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
    
    $Id = $_SESSION['user_id'];
    
    $sql = "select user_id, username, role, photo from users where id = $Id";
    $result = $conn ->query($sql);
    $row = $result ->fetch_assoc();
    $role = $row['role'];
    $UserId = $row['user_id'];
    $username = $row['username'];
    $photo = !empty($row['photo']) ? $row['photo'] : 'assets/images/user1.png';

    // USER sends payment request (→ notify all admins)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save']) && $role != '1') {
        $msg = "$username has sent a payment request.";

        $adminQuery = $conn->query("SELECT user_id FROM users WHERE role = '1'");
        while ($adminRow = $adminQuery->fetch_assoc()) {
            $adminUserId = $adminRow['user_id'];

            $stmt = $conn->prepare("INSERT INTO notifications (user_id, message, n_photo) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $adminUserId, $msg, $photo);
            $stmt->execute();
        }
    }
?>
<div class="dashboard-main-body">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
        <h6 class="fw-semibold mb-0">Payment Page</h6>
        
    </div>
        <div class="mb-40">
            <div class="row gy-4">
                <div class="col-xxl-8 col-sm-6">
                    <div class="card h-100 radius-12">
                        <div class="card-header">
                            <h6>Direct Pay</h6>
                        </div>
                        <div class="card-body p-16">
                        <form method="post">
                        <input type="hidden" value="<?php echo $duration; ?>" name="duration">
                        <input type="hidden" value="<?php echo $rec_id; ?>" name="rec_id">
                        <input type="hidden" value="<?php echo $plan_name; ?>" name="plan_name">
                        <input type="hidden" value="<?php echo $price; ?>" name="price">
                        <input type="hidden" value="<?php echo $gst; ?>" name="gst">
                        <input type="hidden" value="<?php echo $total_price; ?>" name="total_price">
                            <div class="row">
                                <div class="col-sm-6">
                                    <div class="mb-20">
                                        <label for="" class="form-label fw-semibold text-primary-light text-sm mb-8">How would you like to make the payment ? <span class="text-danger-600">*</span></label>
                                        <select class="form-control" name="pay_method" required>
                                            <option value="">Select Payment Method</option>
                                            <option value="Bank Transfer">Bank Transfer</option>
                                            <option value="Wire Transfer">Wire Transfer</option>
                                            <option value="Cash">Cash</option>
                                        </select>
                                    </div>
                                    <input type="submit" name="save" class="btn lufera-bg text-white" value="Submit">
                                </div>
                            </div>
                        </form>
                        </div>
                    </div>
                </div>

                

                <div class="col-xxl-4 col-sm-6">
                    <div class="card h-100 radius-12">
                        <div class="card-header">
                            <h6>Order Summary</h6>
                        </div>
                        <div class="card-body p-16">
                        <div class="mb-20 text-center">
                            <label>Receipt ID : <?php echo $rec_id; ?></label>
                            
                        </div>
                        <div class="d-flex justify-content-between order-card">
                            <label><?php echo $plan_name; ?></label>
                            <h6 class="mb-0">$ <?php echo $price; ?></h6>
                            
                        </div>
                        <div class="d-flex justify-content-between order-card">
                            <label>GST 18%</label>
                            <h6 class="mb-0">$ <?php echo $gst; ?></h6>
                            
                        </div>
                        <div class="d-flex justify-content-between order-card px-0 border-0">
                            <h5>Total</h5>
                            <h5 class="mb-0">$ <?php echo $total_price; ?></h5>
                            
                        </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
   
</div>
<?php include './partials/layouts/layoutBottom.php' ?>