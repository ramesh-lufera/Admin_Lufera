<?php include './partials/layouts/layoutTop.php' ?>
    <?php
        $Id = $_SESSION['user_id'];
        $websiteId = isset($_GET['website_id']) ? (int)$_GET['website_id'] : 0;
        $productId = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;

        $stmt = $conn->prepare("SELECT id, user_id, business_name, role FROM users WHERE id = ?");
        $stmt->bind_param("i", $Id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $UserId = $row['id']; 
        $BusinessName = $row['business_name'];
        $role = $row['role'];
        $stmt->close();

        if ($role == '1' || $role == '2' || $role == '7') {
            $stmt = $conn->prepare("
                SELECT 
                    websites.invoice_id,
                    websites.duration,
                    websites.status,
                    websites.created_at,
                    websites.expired_at,
                    websites.plan,
                    websites.type,
                    CASE 
                        WHEN websites.type = 'package' THEN package.package_name
                        WHEN websites.type = 'product' THEN products.name
                        ELSE websites.plan
                    END AS plan_name
                FROM websites
                LEFT JOIN package ON (websites.type = 'package' AND websites.plan = package.id)
                LEFT JOIN products ON (websites.type = 'product' AND websites.plan = products.id) 
                WHERE websites.id = ?
            ");
            $stmt->bind_param("i", $websiteId);
        } else {
            $stmt = $conn->prepare("
                SELECT 
                    websites.invoice_id,
                    websites.duration,
                    websites.status,
                    websites.created_at,
                    websites.expired_at,
                    websites.plan,
                    websites.type,
                    CASE 
                        WHEN websites.type = 'package' THEN package.package_name
                        WHEN websites.type = 'product' THEN products.name
                        ELSE websites.plan
                    END AS plan_name
                FROM websites
                LEFT JOIN package ON (websites.type = 'package' AND websites.plan = package.id)
                LEFT JOIN products ON (websites.type = 'product' AND websites.plan = products.id)
                WHERE websites.id = ? AND websites.user_id = ?
            ");
            $stmt->bind_param("ii", $websiteId, $UserId); 
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        $Plan = $row['plan_name'];
        $Duration = $row['duration'];
        $InvoiceId = $row['invoice_id'];
        $Status = strtolower($row['status'] ?? 'Pending');
        $CreatedAt = $row['created_at'];
        $expiredAt = $row['expired_at'];
        $planId = $row['plan'] ?? null;
        $type = $row['type'] ?? null;
        $stmt->close();

        if (!empty($InvoiceId)) {
            $orderStmt = $conn->prepare("SELECT * FROM orders WHERE invoice_id = ?");
            $orderStmt->bind_param("i", $InvoiceId);
            $orderStmt->execute();
            $orderResult = $orderStmt->get_result();
            $orderRow = $orderResult->fetch_assoc();
            $order_id = $orderRow['id'];
            $payment_made = $orderRow['payment_made'];
            $balance_due = $orderRow['balance_due'];
            $orderStmt->close();

            if ($orderRow && $orderRow['status'] === 'Approved') {
                if ($Status !== 'Approved') {
                    $updateStmt = $conn->prepare("UPDATE websites SET status = 'Approved' WHERE id = ?");
                    $updateStmt->bind_param("i", $websiteId);
                    $updateStmt->execute();
                    $updateStmt->close();
                    $Status = 'Approved';
                }
            }
        }

        // $startDate = new DateTime($CreatedAt);
        // $endDate = (clone $startDate)->modify("+{$Duration}");
        // $Validity = $startDate->format("d-m-Y") . " to " . $endDate->format("d-m-Y");

        $startDate = new DateTime($CreatedAt);
            $endDate = (clone $startDate)->modify("+{$Duration}");
            $calculatedEnd = $endDate->format("d-m-Y");

            // If renewed, use expired_at from database
            if (!empty($expiredAt) && $expiredAt !== '0000-00-00 00:00:00') {
                $Validity = (new DateTime($expiredAt))->format("d-m-Y");
            } else {
                $Validity = $calculatedEnd;
            }

        switch (ucfirst(strtolower($Status))) {
            case 'Active':
                $statusClass = 'text-success';
                break;
            case 'Expired':
                $statusClass = 'text-danger';
                break;
            case 'Approved':
                $statusClass = 'text-warning';
                break;
            case 'Pending':
            default:
                $statusClass = 'text-pending';
                break;
        }

        // Handle form submission
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            // Sanitize inputs
            $access1 = $conn->real_escape_string($_POST['access1'] ?? '');
            $access_www = $conn->real_escape_string($_POST['access_www'] ?? '');
            $ip_address = $conn->real_escape_string($_POST['ip_address'] ?? '');
            $nameserver1 = $conn->real_escape_string($_POST['nameserver1'] ?? '');
            $nameserver2 = $conn->real_escape_string($_POST['nameserver2'] ?? '');

            // Assuming each user has one website entry, and you are updating it. 
            // You might use a user_id or some unique key to identify the row.
            $user_id = $_SESSION['user_id']; // Example, if using sessions.
            
            // Check if a record exists
            $checkSql = "SELECT * FROM websites WHERE id = ?";
            $stmt = $conn->prepare($checkSql);
            $stmt->bind_param("i", $websiteId);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                // Update only
                $updateSql = "UPDATE websites SET access1=?, access_www=?, ip_address=?, nameserver1=?, nameserver2=? WHERE id=?";
                $stmt = $conn->prepare($updateSql);
                $stmt->bind_param("sssssi", $access1, $access_www, $ip_address, $nameserver1, $nameserver2, $websiteId);
            
                if ($stmt->execute()) {
                    echo "<script>
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: 'Website details updated successfully',
                            confirmButtonColor: '#3085d6'
                        });
                    </script>";
                } else {
                    echo "<script>
                        Swal.fire({
                            icon: 'error',
                            title: 'Update Failed',
                            text: 'Error updating website details: " . addslashes($stmt->error) . "',
                            confirmButtonColor: '#d33'
                        });
                    </script>";
                }
                } else {
                    echo "<script>
                        Swal.fire({
                            icon: 'warning',
                            title: 'No Record Found',
                            text: 'Website record not found. Please contact admin to set up the domain first.',
                            confirmButtonColor: '#f59e0b'
                        });
                    </script>";
                }
                
            
            $stmt->close();
        }
        $access1 = $access_www = $ip_address = $nameserver1 = $nameserver2 = "";

        $sql = "SELECT access1, access_www, ip_address, nameserver1, nameserver2 FROM websites WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $websiteId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            $data = $result->fetch_assoc();
            $access1 = $data['access1'];
            $access_www = $data['access_www'];
            $ip_address = $data['ip_address'];
            $nameserver1 = $data['nameserver1'];
            $nameserver2 = $data['nameserver2'];
        }

        // // For Renewal..
        // // === Get current plan price ===
        // $currentPrice = "N/A";
        // if (!empty($planId) && !empty($type)) {
        //     if ($type == 'package') {
        //         $priceStmt = $conn->prepare("SELECT price FROM package WHERE id = ?");
        //     } elseif ($type == 'product') {
        //         $priceStmt = $conn->prepare("SELECT price FROM products WHERE id = ?");
        //     }

        //     if (isset($priceStmt)) {
        //         $priceStmt->bind_param("i", $planId);
        //         $priceStmt->execute();
        //         $priceResult = $priceStmt->get_result();
        //         if ($priceResult && $priceResult->num_rows > 0) {
        //             $priceRow = $priceResult->fetch_assoc();
        //             $currentPrice = floatval($priceRow['price']); // ensure numeric
        //         }
        //         $priceStmt->close();
        //     }
        // }

        // // === Normalize duration string ===
        // $durationStr = strtolower(trim($Duration)); // from DB
        // preg_match('/\d+/', $durationStr, $matches);
        // $number = isset($matches[0]) ? (int)$matches[0] : 1;

        // // Determine duration type
        // $durationType = 'month';
        // if (preg_match('/year/', $durationStr)) {
        //     $durationType = 'year';
        // } elseif (preg_match('/month/', $durationStr)) {
        //     $durationType = 'month';
        // } elseif (preg_match('/day/', $durationStr)) {
        //     $durationType = 'day';
        // }

        // // === Calculate monthly price for display ===
        // if ($durationType === 'year' || ($durationType === 'month' && $number >= 1)) {
        //     $months = ($durationType === 'year') ? $number * 12 : $number;
        //     $monthlyPrice = $currentPrice / $months;
        //     $monthlyPriceFormatted = number_format($monthlyPrice, 2);
        //     $showMo = true; // show /mo
        // } else {
        //     // duration in days or <1 month → show total price directly
        //     $monthlyPrice = $currentPrice; 
        //     $monthlyPriceFormatted = number_format($monthlyPrice, 2);
        //     $showMo = false; // no /mo
        // }

        // For Renewal..
            // === Get current plan price ===
            $currentPrice = "N/A";
            if (!empty($planId) && !empty($type)) {
                if ($type == 'package') {
                    // ✅ Changed: Get price from durations table (joined with package)
                    $priceStmt = $conn->prepare("
                        SELECT d.price 
                        FROM durations d
                        INNER JOIN package p ON d.package_id = p.id
                        WHERE p.id = ?
                        ORDER BY d.id ASC 
                        LIMIT 1
                    ");
                } elseif ($type == 'product') {
                    $priceStmt = $conn->prepare("SELECT price FROM products WHERE id = ?");
                }

                if (isset($priceStmt)) {
                    $priceStmt->bind_param("i", $planId);
                    $priceStmt->execute();
                    $priceResult = $priceStmt->get_result();
                    if ($priceResult && $priceResult->num_rows > 0) {
                        $priceRow = $priceResult->fetch_assoc();
                        $currentPrice = floatval($priceRow['price']); // ensure numeric
                    }
                    $priceStmt->close();
                }
            }

            // === Normalize duration string ===
            $durationStr = strtolower(trim($Duration)); // from DB
            preg_match('/\d+/', $durationStr, $matches);
            $number = isset($matches[0]) ? (int)$matches[0] : 1;

            // Determine duration type
            $durationType = 'month';
            if (preg_match('/year/', $durationStr)) {
                $durationType = 'year';
            } elseif (preg_match('/month/', $durationStr)) {
                $durationType = 'month';
            } elseif (preg_match('/day/', $durationStr)) {
                $durationType = 'day';
            }

            // === Calculate monthly price for display ===
            if ($durationType === 'year' || ($durationType === 'month' && $number >= 1)) {
                $months = ($durationType === 'year') ? $number * 12 : $number;
                $monthlyPrice = $currentPrice / $months;
                $monthlyPriceFormatted = number_format($monthlyPrice, 2);
                $showMo = true; // show /mo
            } else {
                // duration in days or <1 month → show total price directly
                $monthlyPrice = $currentPrice; 
                $monthlyPriceFormatted = number_format($monthlyPrice, 2);
                $showMo = false; // no /mo
            }

            // --- STEP 1: Get current website info ---
            $websiteQuery = $conn->prepare("SELECT type, cat_id, plan FROM websites WHERE id = ?");
            $websiteQuery->bind_param("i", $websiteId);
            $websiteQuery->execute();
            $websiteResult = $websiteQuery->get_result();
            $website = $websiteResult->fetch_assoc();
            $websiteQuery->close();

            $type = $website['type'];
            $catId = $website['cat_id'];
            $planId = $website['plan'];

            // --- STEP 2: Get plan title of the current website's plan ---
            if ($type === 'package') {
                $stmt = $conn->prepare("SELECT title FROM package WHERE id = ?");
            } else { // product
                $stmt = $conn->prepare("SELECT title FROM products WHERE id = ?");
            }
            $stmt->bind_param("i", $planId);
            $stmt->execute();
            $res = $stmt->get_result();
            $currentPlanTitle = $res->fetch_assoc()['title'] ?? '';
            $stmt->close();

            // --- STEP 3: Get all records for durations (new structure) ---
            $durationPrices = [];

            if ($type === 'package') {
                // ✅ Fetch duration, price, preview_price
                $recordsQuery = $conn->prepare("
                    SELECT d.duration, d.price, d.preview_price
                    FROM durations d
                    INNER JOIN package p ON d.package_id = p.id
                    WHERE p.cat_id = ? AND p.title = ?
                    ORDER BY LENGTH(d.duration), d.duration
                ");
            } else { // product
                $recordsQuery = $conn->prepare("
                    SELECT duration, price AS price, preview_price AS preview_price
                    FROM products
                    WHERE cat_id = ? AND title = ?
                    ORDER BY LENGTH(duration), duration
                ");
            }

            $recordsQuery->bind_param("is", $catId, $currentPlanTitle);
            $recordsQuery->execute();
            $recordsResult = $recordsQuery->get_result();

            $durations = [];
            while ($row = $recordsResult->fetch_assoc()) {
                $durations[] = trim($row['duration']);
                $durationPrices[trim(strtolower($row['duration']))] = [
                    'price' => (float)$row['price'],
                    'preview_price' => (float)$row['preview_price']
                ];
            }
            $recordsQuery->close();

            // --- STEP 4: Fallback to current website duration if none found ---
            if (empty($durations)) {
                $durations[] = '1 Year';
                $durationPrices['1 year'] = ['price' => $currentPrice, 'preview_price' => $currentPrice * 1.1];
            }

            // ✅ Remove all “monthly” calculations
            $showMo = false;
    ?>

    <style>
        .btn-upgrade {
            background-color: #fff9c4;
            color: #000;
            border: 1px solid #ccc;
        }
        .btn-edit-website {
            background-color: #fec700;
            color: #000;
            border: none;
        }
        .btn-upgrade:hover {
            background-color: #f0e68c;
        }
        .btn-edit-website:hover {
            background-color: #e6be00;
        }
        .icon-black {
            color: #000;
        }
        .plan-details-shadow {
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
            border-radius: 8px;
            background-color: #fff;
        }
        .btn-copy-ip {
            background: none;
            border: none;
            padding: 0;
            color: #555;
            cursor: pointer;
            display: flex;
            align-items: center;
        }
        .btn-copy-ip:hover {
            color: #000;
        }
        .text-success {
            color: green;
        }
        .text-danger {
            color: red;
        }
        .text-warning {
            color: #fec700;
        }
        .text-pending {
            color: #ff9800;
        }
    </style>

    <div class="dashboard-main-body">
        <div class="mb-24 d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-2">
            <a class="cursor-pointer fw-bold" onclick="history.back()"><span class="fa fa-arrow-left"></span>&nbsp; </a> 
            <h6 class="fw-semibold mb-0"><?php echo htmlspecialchars($BusinessName); ?></h6>
            <span>|</span>
            <iconify-icon icon="mdi:home-outline" class="text-lg icon-black"></iconify-icon>
            </div>
            <div class="d-flex gap-2">
            <?php if ($role == '1' || $role == '2'): 
                include 'record_payment.php'; 
            endif; ?>
            <?php if (strtolower($Status) === 'approved'): ?>
            <button type="button" class="btn btn-sm btn-renewal" data-bs-toggle="modal" data-bs-target="#renewal-modal">
                Renewal
            </button>
            <?php include 'renewal.php'; ?>
            <?php endif; ?>
                <a href="upgrade_plan.php?web_id=<?= $websiteId ?>&prod_id=<?= $productId?>&duration=<?= $Duration ?>"><button type="button" class="btn btn-sm btn-upgrade">Upgrade</button></a>
                <a href="./mobile-app-onboarding-wizard.php?id=<?= $websiteId ?>&prod_id=<?= $productId ?>"><button type="button" class="btn btn-sm btn-edit-website">Wizard</button></a>
            </div>
        </div>
        <form method="post" autocomplete="off">
        <div class="row gy-4">
            <div class="col-lg-6">
            <div class="card h-100 p-0">
                <div class="card-header border-bottom bg-base py-16 px-24">
                <h6 class="text-lg fw-semibold mb-0">Plan Details:</h6>
                </div>
                <div class="card-body p-24 plan-details-shadow bg-base">
                <div class="d-flex justify-content-between mb-3">
                    <span>Plan Name</span>
                    <span><?php echo htmlspecialchars($Plan); ?></span>
                </div>
                <hr />
                <div class="d-flex justify-content-between my-3">
                    <span>Validity Till</span>
                    <span><?php echo htmlspecialchars($Validity); ?></span>
                </div>
                <hr />
                <div class="d-flex justify-content-between mt-3">
                    <span>Status</span>
                    <span class="fw-semibold <?php echo $statusClass; ?>"><?php echo ucfirst(strtolower($Status)); ?></span>
                </div>
                </div>
            </div>
            </div>
        
            <div class="col-lg-6">
            <div class="card h-100 p-0">
                <div class="card-header border-bottom bg-base py-16 px-24">
                <h6 class="text-lg fw-semibold mb-0">Website Details:</h6>
                </div>
                <div class="card-body p-24 plan-details-shadow bg-base">
                <div class="d-flex justify-content-between my-3">
                    <span>Access your Website at</span>
                    <?php if ($role == '1' || $role == '2'): ?>
                        <input type="text" class="border" name="access1" value="<?= htmlspecialchars($access1 ?? '') ?>">
                    <?php else: ?>
                        <?php if (!empty($access1)): ?>
                            <span><?= htmlspecialchars($access1) ?></span>
                        <?php else: ?>
                            <span>N/A</span>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
                <hr />
                <div class="d-flex justify-content-between my-3">
                    <span>Access your Website with www</span>
                    <?php if ($role == '1' || $role == '2'): ?>
                        <input type="text" class="border" name="access_www" value="<?= htmlspecialchars($access_www ?? '') ?>">
                    <?php else: ?>
                        <?php if (!empty($access_www)): ?>
                            <span><?= htmlspecialchars($access_www) ?></span>
                        <?php else: ?>
                            <span>N/A</span>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <hr />
                <div class="d-flex justify-content-between my-3">
                    <span>Website IP address</span>
                    <?php if ($role == '1' || $role == '2'): ?>
                        <input type="text" class="border" name="ip_address" value="<?= htmlspecialchars($ip_address ?? '') ?>">
                    <?php else: ?>
                        <?php if (!empty($ip_address)): ?>
                            <span><?= htmlspecialchars($ip_address) ?></span>
                        <?php else: ?>
                            <span>N/A</span>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        </div>

            <div class="col-lg-6">
            <div class="card h-100 p-0">
                <div class="card-header border-bottom bg-base py-16 px-24">
                <h6 class="text-lg fw-semibold mb-0">Nameservers:</h6>
                </div>
                <div class="card-body p-24 plan-details-shadow bg-base">
                <div class="d-flex justify-content-between my-3">
                <span>Nameserver 1</span>
                    <?php if ($role == '1' || $role == '2'): ?>
                        <input type="text" class="border" name="nameserver1" value="<?= htmlspecialchars($nameserver1 ?? '') ?>">
                    <?php else: ?>
                        <?php if (!empty($nameserver1)): ?>
                            <span><?= htmlspecialchars($nameserver1) ?></span>
                        <?php else: ?>
                            <span>N/A</span>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
                <hr />
                <div class="d-flex justify-content-between my-3">
                    <span>Nameserver 2</span>
                    <?php if ($role == '1' || $role == '2'): ?>
                        <input type="text" class="border" name="nameserver2" value="<?= htmlspecialchars($nameserver2 ?? '') ?>">
                    <?php else: ?>
                        <?php if (!empty($nameserver2)): ?>
                            <span><?= htmlspecialchars($nameserver2) ?></span>
                        <?php else: ?>
                            <span>N/A</span>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
                </div>
            </div>
            </div>
            <?php if ($role == '1' || $role == '2'): ?>
            <div class="col-lg-6 text-end">
                <button type="submit" class="lufera-bg bg-hover-warning-400 text-white text-md px-56 py-11 radius-8" style="width: auto; height: fit-content;">Update</button>
            </div>
            <?php endif; ?>
        </form>
        </div>
    </div>
    
    <script>
        function copyIP(text) {
            navigator.clipboard.writeText(text).then(() => {
            alert('Copied: ' + text);
            }).catch(() => {
            alert('Failed to copy');
            });
        }
    </script>
<?php include './partials/layouts/layoutBottom.php' ?> 