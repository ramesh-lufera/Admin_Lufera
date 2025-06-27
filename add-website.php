<style>
.nav-link:focus, .nav-link:hover{
    color: #fdc701 !important;
}
</style>
<?php
include './partials/layouts/layoutTop.php';

$packages = [];
$durations = [];

// Get all packages and group them by duration
$sql = "SELECT * FROM package ORDER BY duration";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $duration = $row['duration'];
        $packages[$duration][] = $row;

        if (!in_array($duration, $durations)) {
            $durations[] = $duration;
        }
    }
}

?>


        <div class="dashboard-main-body">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
                <h6 class="fw-semibold mb-0">Add New Website</h6>
            </div>

            <div class="card h-100 p-0 radius-12 overflow-hidden">
                
                <div class="card-body p-40">
                    <div class="row justify-content-center">
                        <div class="col-xxl-10">
                            
                            <!-- <ul class="nav nav-pills button-tab mt-32 pricing-tab justify-content-center" id="pills-tab" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link px-24 py-10 text-md rounded-pill text-secondary-light fw-medium active" id="pills-monthly-tab" data-bs-toggle="pill" data-bs-target="#pills-monthly" type="button" role="tab" aria-controls="pills-monthly" aria-selected="true">
                                        1 Year
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link px-24 py-10 text-md rounded-pill text-secondary-light fw-medium" id="pills-yearly-tab" data-bs-toggle="pill" data-bs-target="#pills-yearly" type="button" role="tab" aria-controls="pills-yearly" aria-selected="false" tabindex="-1">
                                        3 Years
                                    </button>
                                </li>
                            </ul> -->

                            <ul class="nav nav-pills button-tab mt-32 mb-32 justify-content-center" id="pills-tab" role="tablist">
                                <?php foreach ($durations as $index => $duration): ?>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link px-24 py-10 text-md rounded-pill text-secondary-light fw-medium <?= $index === 0 ? 'active' : '' ?>" 
                                                id="tab-<?= $index ?>" 
                                                data-bs-toggle="pill" 
                                                data-bs-target="#tab-pane-<?= $index ?>" 
                                                type="button" 
                                                role="tab" 
                                                aria-controls="tab-pane-<?= $index ?>" 
                                                aria-selected="<?= $index === 0 ? 'true' : 'false' ?>">
                                            <?= htmlspecialchars($duration) ?>
                                        </button>
                                    </li>
                                <?php endforeach; ?>
                            </ul>

                            <!-- <div class="tab-content" id="pills-tabContent">
                                <div class="tab-pane fade show active" id="pills-monthly" role="tabpanel" aria-labelledby="pills-monthly-tab" tabindex="0">
                                    <div class="row gy-4">
                                        <div class="col-xxl-4 col-sm-6">
                                            <div class="pricing-plan position-relative radius-24 overflow-hidden border">
                                                <div class="d-flex align-items-center gap-16">
                                                    <div class="">
                                                        <span class="fw-medium text-md text-secondary-light">Free</span>
                                                        <h6 class="mb-0">Personal</h6>
                                                    </div>
                                                </div>
                                                <p class="mt-16 mb-0 text-secondary-light mb-28">Perfect plan to get started for One Page Website</p>
                                                <h3 class="mb-24">$2000 <span class="fw-medium text-md text-secondary-light">/Year</span> </h3>
                                                <span class="mb-20 fw-medium">A free plan grants you access to some cool features of Spend.In.</span>
                                                <ul>
                                                    <li class="d-flex align-items-center gap-16 mb-16">
                                                        <span class="w-24-px h-24-px p-2 d-flex justify-content-center align-items-center lufera-bg rounded-circle">
                                                            <iconify-icon icon="iconamoon:check-light" class="text-white text-lg "></iconify-icon>
                                                        </span>
                                                        <span class="text-secondary-light text-lg">1 Year</span>
                                                    </li>
                                                    <li class="d-flex align-items-center gap-16 mb-16">
                                                        <span class="w-24-px h-24-px p-2 d-flex justify-content-center align-items-center lufera-bg rounded-circle">
                                                            <iconify-icon icon="iconamoon:check-light" class="text-white text-lg "></iconify-icon>
                                                        </span>
                                                        <span class="text-secondary-light text-lg">Domain</span>
                                                    </li>
                                                    <li class="d-flex align-items-center gap-16 mb-16">
                                                        <span class="w-24-px h-24-px p-2 d-flex justify-content-center align-items-center lufera-bg rounded-circle">
                                                            <iconify-icon icon="iconamoon:check-light" class="text-white text-lg "></iconify-icon>
                                                        </span>
                                                        <span class="text-secondary-light text-lg">Hosting</span>
                                                    </li>
                                                    <li class="d-flex align-items-center gap-16 mb-16">
                                                        <span class="w-24-px h-24-px p-2 d-flex justify-content-center align-items-center lufera-bg rounded-circle">
                                                            <iconify-icon icon="iconamoon:check-light" class="text-white text-lg "></iconify-icon>
                                                        </span>
                                                        <span class="text-secondary-light text-lg">Web Development – 1 Page</span>
                                                    </li>
                                                    <li class="d-flex gap-16">
                                                        <span class="w-24-px h-24-px p-2 d-flex justify-content-center align-items-center lufera-bg rounded-circle mt-4">
                                                            <iconify-icon icon="iconamoon:check-light" class="text-white text-lg "></iconify-icon>
                                                        </span>
                                                        <span class="text-secondary-light text-lg">Support for Editing - 1 Hrs/Month Max</span>
                                                    </li>
                                                </ul>
                                                <form action="cart.php" method="POST">
                                                    <input type="hidden" name="plan_name" value="Personal">
                                                    <input type="hidden" name="price" value="2000">
                                                    <input type="hidden" name="duration" value="1 Year">
                                                    <input type="hidden" name="created_on" value="<?php echo date("Y-m-d"); ?>">
                                                    <button type="submit" class="lufera-bg text-center text-white text-sm btn-sm px-12 py-10 w-100 radius-8 mt-28">Get started</button>
                                                </form>
                                            </div>
                                        </div>
                                        
                                    </div>
                                </div>
                                <div class="tab-pane fade" id="pills-yearly" role="tabpanel" aria-labelledby="pills-yearly-tab" tabindex="0">
                                    <div class="row gy-4">
                                    <div class="col-xxl-4 col-sm-6">
                                            <div class="pricing-plan position-relative radius-24 overflow-hidden border">
                                                <div class="d-flex align-items-center gap-16">
                                                    <div class="">
                                                        <span class="fw-medium text-md text-secondary-light">Free</span>
                                                        <h6 class="mb-0">Personal</h6>
                                                    </div>
                                                </div>
                                                <p class="mt-16 mb-0 text-secondary-light mb-28">Perfect plan to get started for One Page Website</p>
                                                <h3 class="mb-24">$3500 <span class="fw-medium text-md text-secondary-light">/Year</span> </h3>
                                                <span class="mb-20 fw-medium">A free plan grants you access to some cool features of Spend.In.</span>
                                                <ul>
                                                    <li class="d-flex align-items-center gap-16 mb-16">
                                                        <span class="w-24-px h-24-px p-2 d-flex justify-content-center align-items-center lufera-bg rounded-circle">
                                                            <iconify-icon icon="iconamoon:check-light" class="text-white text-lg "></iconify-icon>
                                                        </span>
                                                        <span class="text-secondary-light text-lg">3 Years</span>
                                                    </li>
                                                    <li class="d-flex align-items-center gap-16 mb-16">
                                                        <span class="w-24-px h-24-px p-2 d-flex justify-content-center align-items-center lufera-bg rounded-circle">
                                                            <iconify-icon icon="iconamoon:check-light" class="text-white text-lg "></iconify-icon>
                                                        </span>
                                                        <span class="text-secondary-light text-lg">Domain</span>
                                                    </li>
                                                    <li class="d-flex align-items-center gap-16 mb-16">
                                                        <span class="w-24-px h-24-px p-2 d-flex justify-content-center align-items-center lufera-bg rounded-circle">
                                                            <iconify-icon icon="iconamoon:check-light" class="text-white text-lg "></iconify-icon>
                                                        </span>
                                                        <span class="text-secondary-light text-lg">Hosting</span>
                                                    </li>
                                                    <li class="d-flex align-items-center gap-16 mb-16">
                                                        <span class="w-24-px h-24-px p-2 d-flex justify-content-center align-items-center lufera-bg rounded-circle">
                                                            <iconify-icon icon="iconamoon:check-light" class="text-white text-lg "></iconify-icon>
                                                        </span>
                                                        <span class="text-secondary-light text-lg">Web Development – 1 Page</span>
                                                    </li>
                                                    <li class="d-flex gap-16">
                                                        <span class="w-24-px h-24-px p-2 d-flex justify-content-center align-items-center lufera-bg rounded-circle mt-4">
                                                            <iconify-icon icon="iconamoon:check-light" class="text-white text-lg "></iconify-icon>
                                                        </span>
                                                        <span class="text-secondary-light text-lg">Support for Editing - 1 Hrs/Month Max</span>
                                                    </li>
                                                </ul>
                                                <form action="cart.php" method="POST">
                                                    <input type="hidden" name="plan_name" value="Personal">
                                                    <input type="hidden" name="price" value="3500">
                                                    <input type="hidden" name="duration" value="3 Years">
                                                    <input type="hidden" name="created_on" value="<?php echo date("Y-m-d"); ?>">
                                                    <button type="submit" class="lufera-bg text-center text-white text-sm btn-sm px-12 py-10 w-100 radius-8 mt-28">Get started</button>
                                                </form>
                                            </div>
                                        </div>
                                        
                                       
                                    </div>
                                </div>
                            </div> -->

                            <div class="tab-content" id="pills-tabContent">
    <?php foreach ($durations as $index => $duration): ?>
        <div class="tab-pane fade <?= $index === 0 ? 'show active' : '' ?>" 
             id="tab-pane-<?= $index ?>" 
             role="tabpanel" 
             aria-labelledby="tab-<?= $index ?>" 
             tabindex="0">
            <div class="row gy-4">
                <?php foreach ($packages[$duration] as $package): ?>
                    <div class="col-xxl-4 col-sm-6">
                        <div class="pricing-plan position-relative radius-24 overflow-hidden border">
                        
                        <!-- <span class="bg-white bg-opacity-25 lufera-bg radius-24 py-8 px-24 text-sm position-absolute end-0 top-0 z-1 rounded-start-top-0 rounded-end-bottom-0">Popular</span> -->
                            
                            <div class="d-flex align-items-center gap-16">
                                <div class="">
                                    <span class="fw-medium text-md text-secondary-light"><?= htmlspecialchars($package['plan_type']) ?></span>
                                    <h6 class="mb-0"><?= htmlspecialchars($package['title']) ?></h6>
                                </div>
                            </div>
                            <p class="mt-16 mb-0 text-secondary-light mb-28"><?= htmlspecialchars($package['subtitle']) ?></p>
                            <h3 class="mb-24">$<?= htmlspecialchars($package['price']) ?> 
                                <span class="fw-medium text-md text-secondary-light">/<?= htmlspecialchars($package['duration']) ?></span> 
                            </h3>
                            <span class="mb-20 fw-medium"><?= htmlspecialchars($package['description']) ?></span>

                            <ul>
                                <?php
                                $package_id = $package['id'];
                                $feature_sql = "SELECT feature FROM features WHERE package_id = $package_id";
                                $feature_result = $conn->query($feature_sql);
                                if ($feature_result && $feature_result->num_rows > 0):
                                    while ($feat = $feature_result->fetch_assoc()):
                                ?>
                                    <li class="d-flex align-items-center gap-16 mb-16">
                                        <span class="w-24-px h-24-px p-2 d-flex justify-content-center align-items-center lufera-bg rounded-circle">
                                            <iconify-icon icon="iconamoon:check-light" class="text-white text-lg "></iconify-icon>
                                        </span>
                                        <span class="text-secondary-light text-lg"><?= htmlspecialchars($feat['feature']) ?></span>
                                    </li>
                                <?php endwhile; endif; ?>
                            </ul>

                            <form action="cart.php" method="POST">
                                <input type="hidden" name="plan_name" value="<?= htmlspecialchars($package['title']) ?>">
                                <input type="hidden" name="price" value="<?= htmlspecialchars($package['price']) ?>">
                                <input type="hidden" name="duration" value="<?= htmlspecialchars($package['duration']) ?>">
                                <input type="hidden" name="created_on" value="<?= date("Y-m-d") ?>">
                                <button type="submit" class="lufera-bg text-center text-white text-sm btn-sm px-12 py-10 w-100 radius-8 mt-28">Get started</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>


                        </div>
                    </div>

                </div>
            </div>
        </div>

<?php include './partials/layouts/layoutBottom.php' ?>






