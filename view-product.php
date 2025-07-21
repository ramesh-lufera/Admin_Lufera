    <style>
    .hover-scale-img__img{
        height:200px;
    }
    </style>
    <?php
    include './partials/layouts/layoutTop.php';
    $query = "SELECT id, name, price, product_image FROM products";
    $result = $conn ->query($query);
    ?>

    <div class="dashboard-main-body">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
            <h6 class="fw-semibold mb-0">Products</h6>
        </div>

        <div class="card h-100 p-0 radius-12 overflow-hidden">
            <div class="card-body p-40">
                <div class="row gy-4">
                <?php
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        ?>
                        <div class="col-xxl-3 col-md-4 col-sm-6">
                            <a href="product-details.php?id=<?php echo $row['id']; ?>" class="d-block">
                                <div class="hover-scale-img border radius-16 overflow-hidden">
                                    <div class="max-h-266-px overflow-hidden">
                                        <img src="uploads/products/<?php echo $row['product_image']; ?>" alt="<?php echo htmlspecialchars($row['name']); ?>" class="hover-scale-img__img w-100 object-fit-cover">
                                    </div>
                                    <div class="py-16 px-24">
                                        <h6 class="mb-4"><?php echo htmlspecialchars($row['name']); ?></h6>
                                        <p class="mb-0 text-sm text-secondary-light">$<?php echo htmlspecialchars($row['price']); ?></p>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <?php
                    }
                } else {
                    echo "<p>No products available.</p>";
                }
                ?>
                    
                </div>
            </div>
        </div>
    </div>

    <?php include './partials/layouts/layoutBottom.php'; ?>