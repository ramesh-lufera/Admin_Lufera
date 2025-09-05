    <?php include './partials/layouts/layoutTop.php' ?>
        <?php
            $Id = $_SESSION['user_id'];
            $websiteId = isset($_GET['website_id']) ? (int)$_GET['website_id'] : 0;
            $productId = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
            $stmt = $conn->prepare("SELECT user_id, business_name, role FROM users WHERE id = ?");
            $stmt->bind_param("i", $Id);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $UserId = $row['user_id']; 
            $BusinessName = $row['business_name'];
            $role = $row['role'];
            $stmt->close();

            if ($role == '1' || $role == '2' || $role == '7') {
                $stmt = $conn->prepare("SELECT invoice_id, plan, duration, status, created_at FROM websites WHERE id = ?");
                $stmt->bind_param("i", $websiteId);
            } else {
                $stmt = $conn->prepare("SELECT invoice_id, plan, duration, status, created_at FROM websites WHERE id = ? AND user_id = ?");
                $stmt->bind_param("is", $websiteId, $UserId); 
            }
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $Plan = $row['plan'];
            $Duration = $row['duration'];
            $InvoiceId = $row['invoice_id'];
            $Status = strtolower($row['status'] ?? 'Pending');
            $CreatedAt = $row['created_at'];
            $stmt->close();

            if (!empty($InvoiceId)) {
                $orderStmt = $conn->prepare("SELECT status FROM orders WHERE invoice_id = ? AND user_id = ?");
                $orderStmt->bind_param("ii", $InvoiceId, $UserId);
                $orderStmt->execute();
                $orderResult = $orderStmt->get_result();
                $orderRow = $orderResult->fetch_assoc();
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

            if ($_SERVER["REQUEST_METHOD"] == "POST") {
                // Sanitize inputs
                $facebook_id = $conn->real_escape_string($_POST['facebook_id'] ?? '');
                $password_1 = $conn->real_escape_string($_POST['password_1'] ?? '');
                $insta_id = $conn->real_escape_string($_POST['insta_id'] ?? '');
                $password_2 = $conn->real_escape_string($_POST['password_2'] ?? '');
            
                // Assuming each user has one website entry, and you are updating it. 
                // You might use a user_id or some unique key to identify the row.
                $user_id = $_SESSION['user_id']; // Example, if using sessions.
                //echo $websiteId;
                // Check if a record exists
                $checkSql = "SELECT * FROM websites WHERE id = ?";
                $stmt = $conn->prepare($checkSql);
                $stmt->bind_param("i", $websiteId);
                $stmt->execute();
                $result = $stmt->get_result();
            
                if ($result->num_rows > 0) {
                    // Update only
                    $updateSql = "UPDATE websites SET facebook_id=?, password_1=?, insta_id=?, password_2=? WHERE id=?";
                    $stmt = $conn->prepare($updateSql);
                    $stmt->bind_param("ssssi", $facebook_id, $password_1, $insta_id, $password_2, $websiteId);
                
                    if ($stmt->execute()) {
                        echo "<script>
                            Swal.fire({
                                icon: 'success',
                                title: 'Success',
                                text: 'Marketing details updated successfully',
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
                                text: 'Marketing record not found. Please contact admin to set up the domain first.',
                                confirmButtonColor: '#f59e0b'
                            });
                        </script>";
                    }
                    
                
                $stmt->close();
            }

            $facebook_id = $password_1 = $insta_id = $password_2 = "";

            $sql = "SELECT facebook_id, password_1, insta_id, password_2 FROM websites WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $websiteId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result && $result->num_rows > 0) {
                $data = $result->fetch_assoc();
                $facebook_id = $data['facebook_id'];
                $password_1 = $data['password_1'];
                $insta_id = $data['insta_id'];
                $password_2 = $data['password_2'];
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
                <span class="text-warning">N/A</span>
                </div>
                <div class="d-flex gap-2">
                <button type="button" class="btn btn-sm btn-upgrade">Upgrade</button>
                    <a href="./marketing-onboarding-wizard.php?id=<?= $websiteId ?>&prod_id=<?= $productId ?>"><button type="button" class="btn btn-sm btn-edit-website">Wizard</button></a>
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
                    <h6 class="text-lg fw-semibold mb-0">Marketing Details:</h6>
                    </div>
                    <div class="card-body p-24 plan-details-shadow bg-base">
                    <div class="d-flex justify-content-between my-3">
                        <span>Facebook ID</span>
                        <?php if ($role == '1' || $role == '2'): ?>
                            <input type="text" class="border" name="facebook_id" value="<?= htmlspecialchars($facebook_id ?? '') ?>">
                        <?php else: ?>
                            <?php if (!empty($facebook_id)): ?>
                                <span><?= htmlspecialchars($facebook_id) ?></span>
                            <?php else: ?>
                                <span>N/A</span>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                    <hr />
                    <div class="d-flex justify-content-between my-3">
                        <span>Password</span>
                        <?php if ($role == '1' || $role == '2'): ?>
                            <input type="text" class="border" name="password_1" value="<?= htmlspecialchars($password_1 ?? '') ?>">
                        <?php else: ?>
                            <?php if (!empty($password_1)): ?>
                                <span><?= htmlspecialchars($password_1) ?></span>
                            <?php else: ?>
                                <span>N/A</span>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                    <hr />
                    <div class="d-flex justify-content-between my-3">
                        <span>Insta ID</span>
                        <?php if ($role == '1' || $role == '2'): ?>
                            <input type="text" class="border" name="insta_id" value="<?= htmlspecialchars($insta_id ?? '') ?>">
                        <?php else: ?>
                            <?php if (!empty($insta_id)): ?>
                                <span><?= htmlspecialchars($insta_id) ?></span>
                            <?php else: ?>
                                <span>N/A</span>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                    <hr />
                    <div class="d-flex justify-content-between my-3">
                        <span>Password</span>
                        <?php if ($role == '1' || $role == '2'): ?>
                            <input type="text" class="border" name="password_2" value="<?= htmlspecialchars($password_2 ?? '') ?>">
                        <?php else: ?>
                            <?php if (!empty($password_2)): ?>
                                <span><?= htmlspecialchars($password_2) ?></span>
                            <?php else: ?>
                                <span>N/A</span>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                    <hr />
                    <?php if ($role == '1' || $role == '2'): ?>
                        <button type="submit" class="lufera-bg bg-hover-warning-400 text-white text-md px-56 py-11 radius-8 mt-20 float-end" style="width: auto; height: fit-content;">Update</button>
                    <?php endif; ?>
                </div>
                </div>
                </div>

                <!-- <div class="col-lg-6">
                    <div class="card h-100 p-0">
                        <div class="card-header border-bottom bg-base py-16 px-24">
                        <h6 class="text-lg fw-semibold mb-0">Nameservers:</h6>
                        </div>
                        <div class="card-body p-24 plan-details-shadow bg-base">
                        <div class="d-flex justify-content-between mb-3">
                            <span>Current nameserver 1</span>
                            <span class="d-flex align-items-center gap-2">
                            <span>N/A</span>
                            <button type="button" class="btn-copy-ip" onclick="copyIP('N/A')" title="Copy nameserver 1" aria-label="Copy nameserver 1">
                                <iconify-icon icon="mdi:content-copy" style="cursor:pointer; font-size: 18px;"></iconify-icon>
                            </button>
                            </span>
                        </div>
                        <hr />
                        <div class="d-flex justify-content-between my-3">
                            <span>Current nameserver 2</span>
                            <span class="d-flex align-items-center gap-2">
                            <span>N/A</span>
                            <button type="button" class="btn-copy-ip" onclick="copyIP('N/A')" title="Copy nameserver 2" aria-label="Copy nameserver 2">
                                <iconify-icon icon="mdi:content-copy" style="cursor:pointer; font-size: 18px;"></iconify-icon>
                            </button>
                            </span>
                        </div>
                        </div>
                    </div>
                </div> -->
            </div>
        </form>
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