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

        // if ($role == '1' || $role == '2' || $role == '7') {
        //     $stmt = $conn->prepare("SELECT invoice_id, plan, duration, status, created_at FROM websites WHERE id = ?");
        //     $stmt->bind_param("i", $websiteId);
        // } else {
        //     $stmt = $conn->prepare("SELECT invoice_id, plan, duration, status, created_at FROM websites WHERE id = ? AND user_id = ?");
        //     $stmt->bind_param("ii", $websiteId, $UserId); 
        // }

        if ($role == '1' || $role == '2' || $role == '7') {
            $stmt = $conn->prepare("
                SELECT 
                    websites.invoice_id,
                    websites.duration,
                    websites.status,
                    websites.created_at,
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
        $planId = $row['plan'] ?? null;
        $type = $row['type'] ?? null;
        $stmt->close();

        if (!empty($InvoiceId)) {
            $orderStmt = $conn->prepare("SELECT * FROM orders WHERE invoice_id = ? AND user_id = ?");
            $orderStmt->bind_param("ii", $InvoiceId, $UserId);
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

        $startDate = new DateTime($CreatedAt);
        $endDate = (clone $startDate)->modify("+{$Duration}");
        $Validity = $startDate->format("d-m-Y") . " to " . $endDate->format("d-m-Y");

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

        $currentPrice = "N/A";
        if (!empty($planId) && !empty($type)) {
            if ($type == 'package') {
                $priceStmt = $conn->prepare("SELECT price FROM package WHERE id = ?");
            } elseif ($type == 'product') {
                $priceStmt = $conn->prepare("SELECT price FROM products WHERE id = ?");
            }

            if (isset($priceStmt)) {
                $priceStmt->bind_param("i", $planId);
                $priceStmt->execute();
                $priceResult = $priceStmt->get_result();
                if ($priceResult && $priceResult->num_rows > 0) {
                    $priceRow = $priceResult->fetch_assoc();
                    $currentPrice = $priceRow['price'];
                }
                $priceStmt->close();
            }
        }

        // $Duration comes from database, e.g., "1 year", "12 months", "30 days"
        // Normalize duration to months or days
        $durationStr = strtolower($Duration);

        // Default
        $monthlyPriceFormatted = $currentPrice; // fallback: show as-is

        if (strpos($durationStr, 'year') !== false) {
            // Extract number of years
            preg_match('/\d+/', $durationStr, $matches);
            $years = $matches[0] ?? 1;
            $months = $years * 12;
            $monthlyPrice = $currentPrice / $months;
            $monthlyPriceFormatted = number_format($monthlyPrice, 2);

        } elseif (strpos($durationStr, 'month') !== false) {
            preg_match('/\d+/', $durationStr, $matches);
            $months = $matches[0] ?? 1;
            $monthlyPrice = $currentPrice / $months;
            $monthlyPriceFormatted = number_format($monthlyPrice, 2);

        } elseif (strpos($durationStr, 'day') !== false) {
            // Show total price as-is (no division)
            $monthlyPriceFormatted = number_format($currentPrice, 2);
        }

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
            <h6 class="fw-semibold mb-0"><?php echo htmlspecialchars($BusinessName); ?></h6>
            <span>|</span>
            <iconify-icon icon="mdi:home-outline" class="text-lg icon-black" onclick="history.back()"></iconify-icon>
            </div>
            <div class="d-flex gap-2">
            <?php if ($role == '1' || $role == '2'): 
                include 'record_payment.php'; 
            endif; ?>
            <button type="button" class="btn btn-sm btn-renewal" data-bs-toggle="modal" data-bs-target="#renewal-modal">
                Renewal
            </button>
            <button type="button" class="btn btn-sm btn-upgrade">Upgrade</button>
                <a href="./website-wizard.php?id=<?= $websiteId ?>&prod_id=<?= $productId ?>"><button type="button" class="btn btn-sm btn-edit-website">Wizard</button></a>
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
                    <span>Validity</span>
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
    <style>
    /* Custom button styling */
    .btn-renewal {
        background-color: #c8e6c9;
        color: #000;
        border: 1px solid #81c784;
        font-weight: 500;
    }
    .btn-renewal:hover {
        background-color: #81c784;
        color: #fff;
    }
</style>

<!-- Renewal Modal -->
<div class="modal fade" id="renewal-modal" tabindex="-1" aria-labelledby="renewalModalLabel" aria-hidden="true">
<div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
    <div class="modal-content border-0 shadow-lg">

    <!-- Header -->
    <div class="modal-header border-0 pb-0">
        <h4 class="modal-title fw-semibold w-100 text-center">Renew your Business WordPress</h4>
    </div>

    <!-- Body -->
    <div class="modal-body px-3 px-md-5 pb-0">
        <p class="text-center text-muted mb-4">Review the details and proceed to checkout</p>

        <!-- Period Section -->
        <div class="p-3 border rounded-3 mb-3">
        <div class="row align-items-center text-center text-md-start">
            <div class="col-12 col-md-auto mb-2 mb-md-0">
            <select id="periodSelect" class="form-select w-100 w-md-auto">
                <option value="48">48 months</option>
                <option value="24">24 months</option>
                <option value="12" selected>12 months</option>
                <option value="1">1 month</option>
            </select>
            </div>
            <div class="col-12 col-md text-center mb-2 mb-md-0">
            <span class="badge bg-light text-success fw-semibold">save 7%</span>
            </div>
            <div class="col-12 col-md-auto text-center text-md-end">
            <div class="text-muted small" style="text-decoration: line-through;">
                ₹<?= htmlspecialchars($monthlyPriceFormatted) ?> /mo
            </div>
            <div class="fw-bold fs-5">
                ₹<?= htmlspecialchars($monthlyPriceFormatted) ?> /mo
            </div>
            </div>
        </div>
        </div>

        <!-- Add-on Services -->
        <div class="p-3 border rounded-3 mb-3">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
            <div>
            <label class="fw-semibold mb-1">Add-on services</label><br>
            <span>Daily Backup</span>
            </div>
            <span class="fw-semibold text-success mt-2 mt-md-0">Free</span>
        </div>
        </div>

        <!-- Payment Method -->
        <div class="mb-3">
        <label class="fw-semibold mb-2">Payment method</label>
        <select class="form-select">
            <option>💳 Visa ending 1234</option>
            <option>💳 MasterCard ending 5678</option>
            <option>Choose a different payment method</option>
        </select>
        </div>

        <!-- Expiration Date -->
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
        <label class="fw-semibold mb-1 mb-md-0">Expiration date</label>
        <span id="expirationDate"><?= htmlspecialchars($endDate->format('Y-m-d')) ?></span>
        </div>

        <hr class="my-3">

        <!-- Subtotal -->
        <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap">
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <span class="fw-semibold">Subtotal</span>
                <a href="#" class="text-warning fw-semibold text-decoration-none">Add coupon code</a>
            </div>
            <span id="subtotal" class="fw-semibold mt-2 mt-md-0">
                ₹<?= htmlspecialchars(number_format($monthlyPriceFormatted * 12, 2)) ?>
            </span>
        </div>

        <hr class="my-3">

        <!-- Total -->
        <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap">
        <span class="fw-bold fs-5">Total</span>
        <span id="total" class="fw-bold fs-5 mt-2 mt-md-0">
            ₹<?= htmlspecialchars(number_format($monthlyPriceFormatted * 12, 2)) ?>
        </span>
        </div>

        <p class="text-muted small mt-3">
        By checking out, you agree with our 
        <a href="#" class="text-decoration-none">Terms of Service</a> and confirm that you have read our 
        <a href="#" class="text-decoration-none">Privacy Policy</a>. 
        You can cancel recurring payments at any time.
        </p>
    </div>

    <!-- Footer -->
    <div class="modal-footer border-0 px-3 px-md-5 pb-4 d-flex justify-content-end gap-3 flex-wrap">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-renewal lufera-bg">Complete Payment</button>
    </div>

    </div>
</div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const monthlyPrice = <?= $monthlyPrice ?>;
        const select = document.getElementById('periodSelect');
        const subtotalEl = document.getElementById('subtotal');
        const totalEl = document.getElementById('total');
        const expirationDateEl = document.getElementById('expirationDate');
        const baseEndDate = new Date("<?= $endDate->format('Y-m-d') ?>");

        function formatCurrency(amount) {
            return '₹' + amount.toLocaleString('en-IN', { minimumFractionDigits: 2 });
        }

        function updateTotals() {
            const months = parseInt(select.value);
            let discount = 0;
            if (months === 48 || months === 24) discount = 0.07;
            const subtotal = monthlyPrice * months * (1 - discount);
            subtotalEl.textContent = formatCurrency(subtotal);
            totalEl.textContent = formatCurrency(subtotal);

            const newDate = new Date(baseEndDate);
            newDate.setMonth(newDate.getMonth() + months);
            expirationDateEl.textContent = newDate.toISOString().split('T')[0];
        }

        select.addEventListener('change', updateTotals);
        updateTotals();
    });
</script>

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