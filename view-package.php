<?php include './partials/layouts/layoutTop.php' ?>

<style>
    .nav-link:focus, .nav-link:hover {
        color: #fdc701 !important;
    }
    button:disabled {
        opacity: 0.7;
    }
</style>

<?php
    $packages = [];
    $durations = [];

    $product_category = isset($_GET['product_category']) ? intval($_GET['product_category']) : 0;

    // Step 1: Fetch all packages (only active ones) for selected category
    $stmt = $conn->prepare("
        SELECT *
        FROM package
        WHERE is_deleted = 0 
          AND is_active = 1 
          AND cat_id = ?
    ");
    $stmt->bind_param("i", $product_category);
    $stmt->execute();
    $pkg_result = $stmt->get_result();

    $package_data = [];
    while ($pkg = $pkg_result->fetch_assoc()) {
        $package_data[$pkg['id']] = $pkg;
    }
    $stmt->close();

    $hasPackages = count($package_data) > 0;

    // Step 2: Fetch all durations linked to those packages
    if (!empty($package_data)) {
        $package_ids = implode(',', array_keys($package_data));

        $duration_sql = "
            SELECT d.*, p.title, p.subtitle, p.description, p.package_name, 
                   p.addon_service, p.addon_package, p.addon_product, p.gst_id, p.is_active AS pkg_active
            FROM durations d
            INNER JOIN package p ON d.package_id = p.id
            WHERE d.package_id IN ($package_ids)
            ORDER BY d.duration ASC
        ";
        $dur_result = $conn->query($duration_sql);

        if ($dur_result && $dur_result->num_rows > 0) {
            while ($row = $dur_result->fetch_assoc()) {
                $duration_name = $row['duration'];

                // Group packages by duration value (e.g., 1 Month, 3 Months)
                $packages[$duration_name][] = $row;

                if (!isset($durations[$duration_name])) {
                    $durations[$duration_name] = $row['duration'];
                }
            }
        }
    }

    // Step 3: Get active currency symbol
    $result = $conn->query("SELECT symbol FROM currencies WHERE is_active = 1 LIMIT 1");
    $symbol = "$";
    if ($row = $result->fetch_assoc()) {
        $symbol = $row['symbol'];
    }
?>

<div class="dashboard-main-body">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
        <a class="cursor-pointer fw-bold" onclick="history.back()">
            <span class="fa fa-arrow-left"></span>&nbsp; Back
        </a> 
        <h6 class="fw-semibold mb-0 text-capitalize">
            <?= $hasPackages ? 'Packages' : 'Packages (or) Products'; ?>
        </h6>
        <a class="cursor-pointer fw-bold visibility-hidden" onclick="history.back()">
            <span class="fa fa-arrow-left"></span>&nbsp; Back
        </a> 
    </div>

    <div class="card h-100 p-0 radius-12 overflow-hidden">
        <div class="card-body p-40">
            <div class="row justify-content-center">
                <div class="col-xxl-10">

                <?php if (!empty($packages)): ?>
                    <!-- Duration Tabs -->
                    <ul class="nav nav-pills button-tab mt-32 mb-32 justify-content-center" id="pills-tab" role="tablist">
                        <?php $first = true; foreach ($durations as $duration_name): ?>
                            <li class="nav-item" role="presentation">
                                <button 
                                    class="nav-link px-24 py-10 text-md rounded-pill text-secondary-light fw-medium <?= $first ? 'active' : '' ?>" 
                                    id="tab-<?= md5($duration_name) ?>" 
                                    data-bs-toggle="pill" 
                                    data-bs-target="#tab-pane-<?= md5($duration_name) ?>" 
                                    type="button" 
                                    role="tab" 
                                    aria-controls="tab-pane-<?= md5($duration_name) ?>" 
                                    aria-selected="<?= $first ? 'true' : 'false' ?>">
                                    <?= htmlspecialchars($duration_name) ?>
                                </button>
                            </li>
                        <?php $first = false; endforeach; ?>
                    </ul>

                    <!-- Duration Tab Content -->
                    <div class="tab-content" id="pills-tabContent">
                        <?php $first = true; foreach ($durations as $duration_name): ?>
                            <div class="tab-pane fade <?= $first ? 'show active' : '' ?>" 
                                id="tab-pane-<?= md5($duration_name) ?>" 
                                role="tabpanel" 
                                aria-labelledby="tab-<?= md5($duration_name) ?>" 
                                tabindex="0">

                                <div class="row gy-4">
                                    <?php foreach ($packages[$duration_name] as $package): ?>
                                        <div class="col-xxl-4 col-sm-6">
                                            <div class="pricing-plan position-relative radius-24 overflow-hidden border">
                                                <?php $isActive = ($package['pkg_active'] == 1); ?>
                                                <?php if (!$isActive): ?>
                                                    <p class="mb-0 text-sm text-danger fw-semibold mt-2 float-end">Inactive</p>
                                                <?php endif; ?> 

                                                <h5 class="mb-0 lufera-color"><?= htmlspecialchars($package['title']) ?></h5>
                                                <p class="mb-0 text-secondary-light mb-28"><?= htmlspecialchars($package['subtitle']) ?></p>

                                                <h4 class="mb-24">
                                                <p class="text-sm text-muted mt-0 mb-10 text-decoration-line-through"><?= htmlspecialchars($symbol) ?> <?= number_format((float)$package['preview_price'], 0, '.', ',') ?></p>
                                                    <?= htmlspecialchars($symbol) ?>
                                                    <?= number_format((float)$package['price'], 0, '.', ',') ?>
                                                    <span class="fw-medium text-md text-secondary-light">/
                                                        <?= htmlspecialchars($package['duration']) ?>
                                                    </span>
                                                    
                                                </h4>

                                                <span class="mb-20 fw-medium"><?= htmlspecialchars($package['description']) ?></span>

                                                <ul>
                                                    <?php
                                                    $package_id = $package['package_id'];
                                                    $feature_sql = "SELECT feature FROM features WHERE package_id = $package_id";
                                                    $feature_result = $conn->query($feature_sql);
                                                    if ($feature_result && $feature_result->num_rows > 0):
                                                        while ($feat = $feature_result->fetch_assoc()):
                                                    ?>
                                                        <li class="d-flex align-items-center gap-16 mb-16">
                                                            <span class="w-24-px h-24-px p-2 d-flex justify-content-center align-items-center lufera-bg rounded-circle">
                                                                <iconify-icon icon="iconamoon:check-light" class="text-white text-lg"></iconify-icon>
                                                            </span>
                                                            <span class="text-secondary-light text-lg"><?= htmlspecialchars($feat['feature']) ?></span>
                                                        </li>
                                                    <?php endwhile; endif; ?>
                                                </ul>

                                                <form action="cart.php" method="POST">
                                                    <input type="hidden" name="type" value="package">
                                                    <input type="hidden" name="id" value="<?= htmlspecialchars($package['package_id']) ?>">
                                                    <input type="hidden" name="plan_name" value="<?= htmlspecialchars($package['package_name']) ?>">
                                                    <input type="hidden" name="title" value="<?= htmlspecialchars($package['title']) ?>">
                                                    <input type="hidden" name="subtitle" value="<?= htmlspecialchars($package['subtitle']) ?>">
                                                    <input type="hidden" name="price" value="<?= htmlspecialchars($package['price']) ?>">
                                                    <input type="hidden" name="duration" value="<?= htmlspecialchars($package['duration']) ?>">
                                                    <input type="hidden" name="created_on" value="<?= date("Y-m-d") ?>">
                                                    <input type="hidden" name="addon_service" value="<?= htmlspecialchars($package['addon_service']) ?>">
                                                    <input type="hidden" name="addon_package" value="<?= htmlspecialchars($package['addon_package']) ?>">
                                                    <input type="hidden" name="addon_product" value="<?= htmlspecialchars($package['addon_product']) ?>">
                                                    <input type="hidden" name="gst_id" value="<?= htmlspecialchars($package['gst_id']) ?>">

                                                    <button type="submit" class="lufera-bg text-center text-white text-sm btn-sm px-12 py-10 w-100 radius-8 mt-28" <?= !$isActive ? 'disabled' : '' ?>>Get started</button>
                                                </form>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php $first = false; endforeach; ?>
                    </div>
                <?php else: ?>
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
    </div>
</div>

<?php include './partials/layouts/layoutBottom.php' ?>
