<?php include './partials/layouts/layoutTop.php' ?>
<style>
    .order-card{
        border-bottom: 1px solid lightgray;
        align-items: center;
        padding: 15px 0;
    }
</style>
<?php

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $plan_name = $_POST['plan_name'];
        $price = $_POST['price'];
        $duration = $_POST['duration'];
        $pages = $_POST['pages'];
        $support = $_POST['support'];
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
        $total_price = $_POST['total_price'];
        $created_at = date("Y-m-d H:i:s");

        $user_id = $_SESSION['user_id'];
        $sql = "SELECT user_id FROM users WHERE id = $user_id";
        $result = mysqli_query($conn, $sql);
        $row = mysqli_fetch_assoc($result);
        $client_id = $row['user_id'];

        $sql = "INSERT INTO orders (user, rec_id,plan,amount,status,payment_method,created_on) VALUES ('$client_id', '$rec_id', '$plan_name', '$total_price', 'pending', '$pay_method', '$created_at')";

        if (mysqli_query($conn, $sql)) {
            //echo "Record inserted successfully.";
            echo "<script>
                alert('Record inserted successfully.');
                window.location.href = 'admin-dashboard.php';
              </script>";
            exit();
        } else {
            echo "Error: " . $conn->error;
        }
    }
    
?>
<div class="dashboard-main-body">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
        <h6 class="fw-semibold mb-0">Payment Page</h6>
        
    </div>
    <form method="post">
        <div class="mb-40">
            <div class="row gy-4">
                <div class="col-xxl-8 col-sm-6">
                    <div class="card h-100 radius-12">
                        <div class="card-header">
                            <h6>Direct Pay</h6>
                        </div>
                        <div class="card-body p-16">
                        <form id="updateForm">
                            <div class="row">
                                <div class="col-sm-6">
                                    <div class="mb-20">
                                        <label for="" class="form-label fw-semibold text-primary-light text-sm mb-8">How would you like to make the payment ? <span class="text-danger-600">*</span></label>
                                        <select class="form-control" name="pay_method">
                                            <option>Select Payment Method</option>
                                            <option value="Increase sales">Bank Transfer</option>
                                            <option value="Generate leads">Wire Transfer</option>
                                            <option value="Improve brand awareness">Cash</option>
                                        </select>
                                    </div>
                                    <button type="button" class="btn lufera-bg text-white" data-bs-toggle="modal" data-bs-target="#myModal">Continue</button>
                                    <!-- <button type="button" class="btn btn-primary btn-lg" data-backdrop="static" data-keyboard="false" data-bs-target="#myModal" data-bs-toggle="modal">Continue</button> -->
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
                            <input type="hidden" value="<?php echo $rec_id; ?>" name="rec_id">
                        </div>
                        <div class="d-flex justify-content-between order-card">
                            <label><?php echo $plan_name; ?></label>
                            <h6 class="mb-0">$ <?php echo $price; ?></h6>
                            <input type="hidden" value="<?php echo $plan_name; ?>" name="plan_name">
                            <input type="hidden" value="<?php echo $price; ?>" name="price">
                        </div>
                        <div class="d-flex justify-content-between order-card">
                            <label>GST 18%</label>
                            <h6 class="mb-0">$ <?php echo $gst; ?></h6>
                            <input type="hidden" value="<?php echo $gst; ?>" name="gst">
                        </div>
                        <div class="d-flex justify-content-between order-card px-0 border-0">
                            <h5>Total</h5>
                            <h5 class="mb-0">$ <?php echo $total_price; ?></h5>
                            <input type="hidden" value="<?php echo $total_price; ?>" name="total_price">
                        </div>
                        </div>
                    </div>
                </div>

                <div class="modal fade" id="myModal" data-bs-backdrop="static" data-bs-keyboard="false">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                        
                            <div class="text-center modal-body" style="padding: 2rem;">
                                <h5 style="line-height: 60px; letter-spacing: 1px">Thank you for your confirmation, one of our representatives will contact you for confirmation</h5>
                                <!-- <a href="websites.php"> -->
                                    <button type="submit" class="btn lufera-bg text-white mt-20" style="padding: 1rem 5rem" name="save">Close</button>
                                <!-- </a> -->
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </form>
</div>
<script>
    $(document).ready(function () {
    $('#myModal').modal({
           backdrop: 'static',
           keyboard: false
    })
   });
</script>
<?php include './partials/layouts/layoutBottom.php' ?>