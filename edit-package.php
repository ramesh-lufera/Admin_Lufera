<!-- ================= PRICING TABLE ================= -->

                    <?php if ($showPackages): ?>
                        <?php if (!empty($cat_row['pkg_with_login']) && !$isLoggedIn): ?>
                            <!-- 🔒 LOGIN REQUIRED MESSAGE -->
                            <div class="card">
                                <h4>Packages Pricing Table</h4>
                                <p class="text-center" style="font-size:16px; font-weight:600; margin-top:10px;">
                                    <a href="#" onclick="openLoginPopup()" class="btn mt-2">
                                        🔒 Sign-In to See the Packages
                                    </a>
                                </p>
                            </div>
                        <?php else: ?>
                            <?php
                                $packages = [];
                                $durations = [];
                                $product_category = $cat_row['cat_id'] ?? 0;

                                // Fetch packages
                                $stmt = $conn->prepare("
                                    SELECT * FROM package 
                                    WHERE is_deleted=0 AND is_active=1 AND cat_id=?
                                ");
                                $stmt->bind_param("i", $product_category);
                                $stmt->execute();
                                $res = $stmt->get_result();

                                $data=[];
                                while($row=$res->fetch_assoc()){ $data[$row['id']]=$row; }
                                $stmt->close();

                                if(!empty($data)){
                                    $ids = implode(',', array_keys($data));

                                    $sql = "SELECT d.*, p.title, p.subtitle, p.description, p.package_name, p.is_active pkg_active
                                            FROM durations d
                                            JOIN package p ON d.package_id=p.id
                                            WHERE d.package_id IN ($ids)";
                                    $r=$conn->query($sql);

                                    while($row=$r->fetch_assoc()){
                                        $dur=$row['duration'];
                                        $packages[$dur][]=$row;
                                        $durations[$dur]=$dur;
                                    }
                                }

                                // currency
                                $symbol="$";
                                $r=$conn->query("SELECT symbol FROM currencies WHERE is_active=1 LIMIT 1");
                                if($row=$r->fetch_assoc()) $symbol=$row['symbol'];
                            ?>
                            <div class="card">
                                <h4>Packages Pricing Table</h4>
                                <div class="card-body">
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

                                                                        <?php
                                                                            // Generate SEO URL from package_name
                                                                            $packageSlug = strtolower(trim($package['package_name']));
                                                                            $packageSlug = preg_replace('/[^a-z0-9]+/i', '-', $packageSlug);
                                                                            $packageSlug = trim($packageSlug, '-');

                                                                            // Final URL
                                                                            $packageUrl = "../../pages/packages/" . $packageSlug . ".php";
                                                                        ?>

                                                                        <h5 class="mb-0 lufera-color">
                                                                            <a href="<?= htmlspecialchars($packageUrl) ?>" 
                                                                            style="text-decoration:none; color:inherit;">
                                                                                <?= htmlspecialchars($package['title']) ?>
                                                                            </a>
                                                                        </h5>

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

                                                                        <form action="../../cart.php" method="POST">
                                                                            <input type="hidden" name="type" value="package">
                                                                            <input type="hidden" name="id" value="<?= htmlspecialchars($package['package_id']) ?>">
                                                                            <input type="hidden" name="plan_name" value="<?= htmlspecialchars($package['package_name']) ?>">
                                                                            <input type="hidden" name="title" value="<?= htmlspecialchars($package['title']) ?>">
                                                                            <input type="hidden" name="subtitle" value="<?= htmlspecialchars($package['subtitle']) ?>">
                                                                            <input type="hidden" name="price" value="<?= htmlspecialchars($package['price']) ?>">
                                                                            <input type="hidden" name="duration" value="<?= htmlspecialchars($package['duration']) ?>">
                                                                            <input type="hidden" name="created_on" value="<?= date("Y-m-d") ?>">
                                                                            <!-- <input type="hidden" name="addon_service" value="<?= htmlspecialchars($package['addon_service']) ?>">
                                                                            <input type="hidden" name="addon_package" value="<?= htmlspecialchars($package['addon_package']) ?>">
                                                                            <input type="hidden" name="addon_product" value="<?= htmlspecialchars($package['addon_product']) ?>">
                                                                            <input type="hidden" name="gst_id" value="<?= htmlspecialchars($package['gst_id']) ?>"> -->

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
                                                        No packages available.
                                                    </h6>
                                                    <div style="height: 3px; width: 60px; background-color: #fdc701; margin: 12px auto 0; border-radius: 2px;"></div>
                                                </div>
                                            </div>
                                        <?php endif; ?>

                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
