<?php
    // Server
    $servername = "localhost";
    $username = "u363064277_lufera";
    $password = "Lufera@789";
    $dbname = "u363064277_LI_Dashboard";

    // Local
    // $servername = "localhost";
    // $username = "root";
    // $password = "";
    // $dbname = "lufera infotech";

    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    } else {
        echo "";
    }

    $package_url = $_GET['package'];
    // Use prepared statements to avoid SQL injection
    $stmt = $conn->prepare("SELECT cat_name, cat_id FROM categories WHERE cat_name = ?");
    $stmt->bind_param("s", $package_url); // "s" = string
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $cat_id = $row['cat_id'];
        $cat_name = $row['cat_name'];
        //echo $cat_id;
    } else {
        echo "No category found.";
    }
    $stmt->close();

?>
<link rel="stylesheet" href="assets/css/style.css">
<link rel="stylesheet" href="assets/css/lib/bootstrap.min.css">
<style>
    .nav-link:focus, .nav-link:hover {
        color: #fdc701 !important;
    }
    button:disabled{
        opacity: 0.7;
    }
</style>

<?php
    $packages = [];
    $durations = [];

    $product_category = isset($_GET['product_category']) ? intval($_GET['product_category']) : 0;


    // Fetch packages by category and group by duration
    $stmt = $conn->prepare("SELECT * FROM package WHERE cat_id = $cat_id AND is_deleted = 0 AND is_active = 1");
    //$stmt->bind_param("i", $product_category);
    $stmt->execute();
    $result = $stmt->get_result();

    $hasPackages = false;

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $duration = $row['duration'];
            $packages[$duration][] = $row;

            if (!in_array($duration, $durations)) {
                $durations[] = $duration;
            }

            $hasPackages = true;
        }
    }
    $stmt->close();

    // Get active symbol
    $result = $conn->query("SELECT symbol FROM currencies WHERE is_active = 1 LIMIT 1");
    $symbol = "$"; // default
    if ($row = $result->fetch_assoc()) {
        $symbol = $row['symbol'];
    }
?>

<div class="dashboard-main-body">
<?php
        if($package_url == "website"){
        ?>
            <h5 class="text-center fw-bold">Our Flexible Pricing Packages </h5>
            <?php }
        elseif($package_url == "marketing"){ ?>
            <h5 class="text-center fw-bold">Flexible Digital Marketing Packages </h5>
            <?php } 
        elseif(urldecode($package_url) == "Customized Apps") { ?>
            <h5 class="text-center fw-bold">Custom Web-Based Application Development </h5>
            <?php } ?>

         <div class="row justify-content-center">
                <div class="col-xxl-10">

                <?php if (!empty($packages)): ?>
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

                    <div class="tab-content" id="pills-tabContent">
                        <?php foreach ($durations as $index => $duration): ?>
                            <div class="tab-pane fade <?= $index === 0 ? 'show active' : '' ?>" 
                                id="tab-pane-<?= $index ?>" 
                                role="tabpanel" 
                                aria-labelledby="tab-<?= $index ?>" 
                                tabindex="0">
                                <div class="row gy-4">
                                    <?php foreach ($packages[$duration] as $package): ?>
                                        <div class="col-xxl-3 col-sm-6">
                                            <div class="pricing-plan position-relative radius-24 overflow-hidden border">
                                            <!-- <h6><?= htmlspecialchars($package['package_name']) ?></h6>     -->
                                            <!-- <span class="fw-medium text-md text-secondary-light"><?= htmlspecialchars($package['plan_type']) ?></span> -->
                                                <?php $isActive = ($package['is_active'] == 1); ?>
                                                <?php if (!$isActive): ?>
                                                    <p class="mb-0 text-sm text-secondary-light text-danger fw-semibold mt-2 float-end">Inactive</p>
                                                <?php endif; ?> 
                                                <p class="mb-0 lufera-color"><?= htmlspecialchars($package['title']) ?></p>
                                                <p class=" mb-0 text-secondary-light mb-28"><?= htmlspecialchars($package['subtitle']) ?></p>
                                                <h4 class="mb-24" id="currency-symbol-display">
                                                    <?= htmlspecialchars($symbol) ?>
                                                    <?= number_format((float)$package['price'], 0, '.', ',') ?>
                                                    <span class="fw-medium text-md text-secondary-light">/
                                                        <?= htmlspecialchars($package['duration']) ?>
                                                    </span>
                                                </h4>
                                                <span class="mb-20 fw-medium"><?= htmlspecialchars($package['description']) ?></span>

                                                <ul style="min-height:240px">
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
                                                    <input type="hidden" name="plan_name" value="<?= htmlspecialchars($package['package_name']) ?>">
                                                    <input type="hidden" name="title" value="<?= htmlspecialchars($package['title']) ?>">
                                                    <input type="hidden" name="subtitle" value="<?= htmlspecialchars($package['subtitle']) ?>">
                                                    <input type="hidden" name="price" value="<?= htmlspecialchars($package['price']) ?>">
                                                    <input type="hidden" name="duration" value="<?= htmlspecialchars($package['duration']) ?>">
                                                    <input type="hidden" name="created_on" value="<?= date("Y-m-d") ?>">
                                                    <button type="submit" class="lufera-bg text-center text-white text-sm btn-sm px-12 py-10 w-100 radius-8 mt-28" <?php echo !$isActive ? 'disabled' : ''; ?>>Get started</button>
                                                </form>
                                                
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                        <!-- <div class="alert alert-warning text-center">
                            <strong>No packages found</strong>
                        </div> -->
                        <div class="text-center py-32">
                            <div class="radius-12 p-12">
                                <h6 class="mb-0" style="color: #000; font-size: 1.125rem; font-weight: 600;">
                                    No packages or products available.
                                </h6>
                                <div style="height: 3px; width: 60px; background-color: #fdc701; margin: 12px auto 0; border-radius: 2px;"></div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        
</div>
<script src="assets/js/lib/bootstrap.bundle.min.js"></script>
<script src="assets/js/lib/iconify-icon.min.js"></script>