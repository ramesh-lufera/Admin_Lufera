<?php include './partials/layouts/layoutTop.php';

    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;

    require_once 'vendor/autoload.php';

    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
?>

<?php
    $Id = $_SESSION['user_id'];
    $cat_id = $_SESSION['cat_id'] ?? null;
    
    $sql = "select user_id, username, role, photo from users where id = $Id";
    $result = $conn ->query($sql);
    $row = $result ->fetch_assoc();
    $role = $row['role'];
    $UserId = $row['user_id'];
    $username = $row['username'];
    $photo = !empty($row['photo']) ? $row['photo'] : 'assets/images/user1.png';

    // Initialize variables with default values
    $id = 0;
    $type = '';
    $plan_name = '';
    $price = 0;
    $gst = 0;
    $total_price = 0;
    $addon_total = 0;
    $tax_rate = 0;
    $tax_name = 'Tax';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $id = $_POST['id'] ?? 0;
        $type = $_POST['type'] ?? '';
        $plan_name = $_POST['plan_name'] ?? '';
        // $gst = $_POST['gst'];
        $price = floatval($_POST['price'] ?? 0);

        // --- Fetch Tax Info from taxes table using gst_id ---
        $gst_id = $_POST['gst_id'] ?? null;
        $tax_name = "Tax";
        $tax_rate = 0;

        if (!empty($gst_id)) {
            $tax_query = $conn->prepare("SELECT tax_name, rate FROM taxes WHERE id = ?");
            $tax_query->bind_param("i", $gst_id);
            $tax_query->execute();
            $tax_result = $tax_query->get_result();
            if ($tax_result && $tax_result->num_rows > 0) {
                $tax_row = $tax_result->fetch_assoc();
                $tax_name = $tax_row['tax_name'];
                $tax_rate = floatval($tax_row['rate']);
            }
            $tax_query->close();
        }

        // --- Calculate GST dynamically based on rate ---
        $gst = round($price * ($tax_rate / 100), 2);
        $total_price = round($price + $gst, 2);

        $duration = $_POST['duration'];
        // $total_price = $_POST['total_price'];
        $receipt_id = $_POST['receipt_id'];
        $created_on = $_POST['created_on'];
        $get_addon = $_POST['get_addon'];
        $get_packages = $_POST['get_packages'] ?? '';
        $get_products = $_POST['get_products'] ?? '';
        $addon_total = floatval($_POST['addon-total'] ?? 0);
        $hostinger_balance = $_POST['hostinger_balance'];
        $subtotal_display = $_POST['subtotal-display'];
        $invoice_id = $_POST['invoice_id'];
        $web_id = $_POST['web_id'];
        $total_breakdown_tax = $_POST['total_breakdown_tax'];
        $total_breakdown_gst = $_POST['total_breakdown_gst'];

        $amount_to_pay = $_POST['amount_to_pay'];
        $amount_tax = $_POST['amount_tax'];

        $total_amount = $_POST['total_amount'];
        $upgrade_amount = $_POST['upgrade_amount'];
        $current_plan_id = $_POST['current_plan_id'];

        // For Renewal..
        if (isset($_POST['renewal']) && $_POST['renewal'] == 1 && !empty($id)) {
            $durationQuery = $conn->prepare("SELECT duration FROM websites WHERE id = ?");
            $durationQuery->bind_param("i", $id);
            $durationQuery->execute();
            $durationResult = $durationQuery->get_result();
            if ($durationResult && $durationResult->num_rows > 0) {
                $row = $durationResult->fetch_assoc();
                $original_duration = $row['duration'];
            } else {
                $original_duration = "N/A";
            }
            $durationQuery->close();

            // ✅ Update renewal info
            $renewal_period = $_POST['period'] ?? '';
            $duration = $renewal_period ?: $duration;
            $price = isset($_POST['total']) ? floatval($_POST['total']) : floatval($price);

            // $gst = round($price * 0.18, 2);
            // $total_price = round($price + $gst, 2);

            // ✅ Calculate GST dynamically using plan's own gst_id (same as normal flow)
            $renewal_tax_name = $tax_name; // default to existing
            $renewal_tax_rate = $tax_rate; // fallback to existing rate

            if (!empty($gst_id)) {
                $renewal_tax_query = $conn->prepare("SELECT tax_name, rate FROM taxes WHERE id = ?");
                $renewal_tax_query->bind_param("i", $gst_id);
                $renewal_tax_query->execute();
                $renewal_tax_result = $renewal_tax_query->get_result();
                if ($renewal_tax_result && $renewal_tax_result->num_rows > 0) {
                    $renewal_tax_row = $renewal_tax_result->fetch_assoc();
                    $renewal_tax_name = $renewal_tax_row['tax_name'];
                    $renewal_tax_rate = floatval($renewal_tax_row['rate']);
                }
                $renewal_tax_query->close();
            }

            $gst = round($price * ($renewal_tax_rate / 100), 2);
            $total_price = round($price + $gst, 2);

            $created_on = $_POST['expiration_date'] ?? $CreatedAt;
            $receipt_id = $_POST['receipt_id'] ?? $receipt_id;
        } else {
            $original_duration = $duration; // normal case
        }
    }

    // ========== Packages with Prices ==========
    $package_details = [];
    if (!empty($get_packages)) {
        $package_ids = array_map('intval', explode(",", $get_packages));
        if (!empty($package_ids)) {
            $ids_str = implode(",", $package_ids);
            $sql_packages = "
                SELECT p.package_name, d.price, p.gst_id
                FROM package p
                LEFT JOIN durations d ON p.id = d.package_id
                WHERE p.id IN ($ids_str)
                GROUP BY p.id
            ";
            $result_packages = $conn->query($sql_packages);

            while ($row_pkg = $result_packages->fetch_assoc()) {
                $package_details[] = [
                    'name' => $row_pkg['package_name'],
                    'price' => floatval($row_pkg['price']),
                    'gst_id' => intval($row_pkg['gst_id'])
                ];
            }
        }
    }

    // ========== Products with Prices ==========
    $product_details = [];
    if (!empty($get_products)) {
        $product_ids = array_map('intval', explode(",", $get_products));
        if (!empty($product_ids)) {
            $ids_str = implode(",", $product_ids);
            $sql_products = "SELECT name, price, gst FROM products WHERE id IN ($ids_str)";
            $result_products = $conn->query($sql_products);

            while ($row_prod = $result_products->fetch_assoc()) {
                $product_details[] = [
                    'name' => $row_prod['name'],
                    'price' => floatval($row_prod['price']),
                    'gst_id' => intval($row_prod['gst']) // ✅ store tax id
                ];
            }
        }
    }

    // ========== Add-On Services with Prices ==========
    $addon_details = [];
    if (!empty($get_addon)) {
        $addon_ids = array_map('intval', explode(",", $get_addon));
        if (!empty($addon_ids)) {
            $ids_str = implode(",", $addon_ids);
            $sql_addons = "SELECT name, cost, gst FROM `add-on-service` WHERE id IN ($ids_str)";
            $result_addons = $conn->query($sql_addons);

            while ($row_addon = $result_addons->fetch_assoc()) {
                $addon_details[] = [
                    'name' => $row_addon['name'],
                    'price' => floatval($row_addon['cost']),
                    'gst_id' => intval($row_addon['gst'])
                ];
            }
        }
    }

    // Get active symbol
    $result1 = $conn->query("SELECT symbol FROM currencies WHERE is_active = 1 LIMIT 1");
    $symbol = "$"; // default
    if ($row = $result1->fetch_assoc()) {
        $symbol = $row['symbol'];
    }

    if (isset($_POST['save'])) {
        $plan_id = $_POST['id'];
        $type = $_POST['type'];
        $pay_method = $_POST['pay_method'];
        $receipt_id = $_POST['receipt_id'];
        $invoice_id = $_POST['invoice_id'];
        $plan_name = $_POST['plan_name'];
        $duration = $_POST['duration'];
        $total_price = $_POST['total_price'];
        $created_at = date("Y-m-d H:i:s");
        $price = floatval($_POST['price']);
        $gst = $_POST['gst'];
        $discount = $payment_made = "0";
        $get_addon = $_POST['get_addon'];
        $get_packages = $_POST['get_packages'] ?? '';
        $get_products = $_POST['get_products'] ?? '';
        $addon_total = floatval($_POST['addon-total']);
        $user_id = $_SESSION['user_id'];
        // $subtotal = $total_price;
        $subtotal = $price + $addon_total;
        $discount_amount = floatval($_POST['discount_amount'] ?? 0);
        $coupon_code = $_POST['coupon_code'];
        $hostinger_balance = $_POST['hostinger_balance'];
        $current_plan_id = $_POST['current_plan_id'];
        $web_id = $_POST['web_id'];

        $upgrade_gst = $_POST['amount_tax'];
        $upgrade_amount = $_POST['upgrade_amount'];
        
        // Fetch tax rate for proper calculation
        $gst_id = $_POST['gst_id'] ?? null;
        $tax_rate = 0;
        if (!empty($gst_id)) {
            $tax_query = $conn->prepare("SELECT rate FROM taxes WHERE id = ?");
            $tax_query->bind_param("i", $gst_id);
            $tax_query->execute();
            $tax_result = $tax_query->get_result();
            if ($tax_result && $tax_result->num_rows > 0) {
                $tax_row = $tax_result->fetch_assoc();
                $tax_rate = floatval($tax_row['rate']);
            }
            $tax_query->close();
        }

        $is_renewal = isset($_POST['renewal']) && $_POST['renewal'] == 1;

        // ===================== HANDLE RENEWAL =====================
        if ($is_renewal) {
            $renewal_duration = $_POST['period'];

            // // Fetch current expired_at from DB
            // $query = $conn->prepare("SELECT plan, type, expired_at FROM websites WHERE id = ?");
            // $query->bind_param("i", $id);
            // $query->execute();
            // $result = $query->get_result();
            // $row = $result->fetch_assoc();
            // $query->close();

            // $plan_id = $row['plan'];
            // $type = $row['type'];

            // // Determine base date for renewal
            // if (!empty($row['expired_at']) && strtotime($row['expired_at']) > time()) {
            //     // If previous expiration exists and is in the future, add renewal on top of it
            //     $baseDate = new DateTime($row['expired_at']);
            // } else {
            //     // Otherwise, use created_at + original duration
            //     $baseDate = new DateTime($created_at);

            //     if (!empty($duration)) {
            //         $baseDate->modify($duration); // e.g., "1 year"
            //     }
            // }

            // // Add current renewal duration
            // if (!empty($renewal_duration)) {
            //     $baseDate->modify($renewal_duration);
            // }

            // $expiredAt = $baseDate->format('Y-m-d H:i:s');

            // Fetch current expired_at and created_at and duration from DB
            $query = $conn->prepare("SELECT plan, type, expired_at, created_at, duration FROM websites WHERE id = ?");
            $query->bind_param("i", $id);
            $query->execute();
            $result = $query->get_result();
            $row = $result->fetch_assoc();
            $query->close();

            $expiredAtDB = $row['expired_at'] ?? null;
            $createdAtDB = $row['created_at'] ?? null;
            $durationDB  = $row['duration'] ?? null;

            $plan_id = $row['plan'];
            $type = $row['type'];

            // Determine base date for renewal
            if (!empty($expiredAtDB)) {
                // Case 1: expired_at exists → add renewal_duration to it
                $baseDate = new DateTime($expiredAtDB);
                if (!empty($renewal_duration)) {
                    $baseDate->modify('+' . $renewal_duration);
                }
            } else {
                // Case 2: expired_at is empty → created_at + duration + renewal_duration
                $baseDate = new DateTime($createdAtDB);

                if (!empty($durationDB)) {
                    $baseDate->modify('+' . $durationDB);
                }

                if (!empty($renewal_duration)) {
                    $baseDate->modify('+' . $renewal_duration);
                }
            }

            $expiredAt = $baseDate->format('Y-m-d H:i:s');

            // Update website record for renewal
            $stmt = $conn->prepare("UPDATE websites SET renewal_duration = ?, expired_at = ? WHERE id = ?");
            $stmt->bind_param("ssi", $renewal_duration, $expiredAt, $id);

            if ($stmt->execute()) {
                $sql1    = "SELECT id, user_id, email, username FROM users WHERE id = $user_id LIMIT 1";
                $result21 = mysqli_query($conn, $sql1);
                $row1    = mysqli_fetch_assoc($result21);
                $client_id = $row1['id'];

                // Only set addon_price if this is an addon row
                $insert_addon_price = !empty($get_addon) ? $addon_total : '';

                // $main_subtotal = $price + floatval($insert_addon_price);
                // $main_discount = $discount ?? 0;
                // $main_gst      = round($main_subtotal * ($tax_rate / 100), 2);
                // $main_amount   = round($main_subtotal - $main_discount + $main_gst, 2);
                // $main_balance_due  = $main_amount - $payment_made;

                $main_subtotal = $price + floatval($insert_addon_price);
                $main_discount = $discount ?? 0;

                // ✅ Fetch correct GST rate for renewal (from taxes table)
                $renewal_tax_rate = 0;

                if (!empty($gst_id)) {
                    // Case 1: GST ID comes from POST or earlier code
                    $stmt = $conn->prepare("SELECT rate FROM taxes WHERE id = ?");
                    $stmt->bind_param("i", $gst_id);
                    $stmt->execute();
                    $res = $stmt->get_result();
                    if ($res && $res->num_rows > 0) {
                        $row_tax = $res->fetch_assoc();
                        $renewal_tax_rate = floatval($row_tax['rate']);
                    }
                    $stmt->close();
                } else {
                    // Case 2: Fallback → try fetching GST ID from websites/package/product
                    $stmt = $conn->prepare("
                        SELECT 
                            COALESCE(p.gst_id, pr.gst) AS gst_id
                        FROM websites w
                        LEFT JOIN package p ON w.plan = p.id
                        LEFT JOIN products pr ON w.plan = pr.id
                        WHERE w.id = ?
                        LIMIT 1
                    ");
                    $stmt->bind_param("i", $id);
                    $stmt->execute();
                    $res = $stmt->get_result();
                    if ($res && $res->num_rows > 0) {
                        $row = $res->fetch_assoc();
                        $fetched_gst_id = (int) ($row['gst_id'] ?? 0);
                        if ($fetched_gst_id) {
                            $stmt2 = $conn->prepare("SELECT rate FROM taxes WHERE id = ?");
                            $stmt2->bind_param("i", $fetched_gst_id);
                            $stmt2->execute();
                            $res2 = $stmt2->get_result();
                            if ($res2 && $res2->num_rows > 0) {
                                $tax_row = $res2->fetch_assoc();
                                $renewal_tax_rate = floatval($tax_row['rate']);
                            }
                            $stmt2->close();
                        }
                    }
                    $stmt->close();
                }

                // ✅ Calculate GST and totals using correct renewal tax rate
                $main_gst     = round($main_subtotal * ($renewal_tax_rate / 100), 2);
                $main_amount  = round($main_subtotal - $main_discount + $main_gst, 2);
                $main_balance_due = $main_amount - $payment_made;

                $auto_id = rand(10000000, 99999999);

                $sqlRenewal = "INSERT INTO renewal_invoices (user_id, invoice_id, plan, duration, amount, gst, price, addon_price, status, payment_method, discount, payment_made, created_on, subtotal, balance_due, addon_service, type) VALUES 
                    ('$client_id', '$auto_id', '$plan_id', '$duration' ,'$main_amount', '$main_gst', '$price', '$insert_addon_price', 'Pending', '$pay_method', '$main_discount', '$payment_made', '$created_at', '$main_subtotal', '$main_amount', '$get_addon', '$type')";

                mysqli_query($conn, $sqlRenewal);

                // Fetch user info
                $sqlUser = "SELECT email, username FROM users WHERE id = ?";
                $userStmt = $conn->prepare($sqlUser);
                $userStmt->bind_param("i", $user_id);
                $userStmt->execute();
                $userResult = $userStmt->get_result();
                $userData = $userResult->fetch_assoc();
                $userStmt->close();

                $toEmail = $userData['email'];
                $toName  = $userData['username'];

                // Show loader immediately
                echo "
                <script>
                    Swal.fire({
                        title: 'Processing...',
                        text: 'Please wait while we finalize your renewal.',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                            const spinner = document.querySelector('.swal2-loader');
                            if (spinner) {
                                spinner.style.borderColor = '#fec700 transparent #fec700 transparent';
                            }
                        }
                    });
                </script>";
                // flush response so browser shows the loader instantly
                ob_flush(); flush();

                $orders_link = rtrim($_ENV['EMAIL_COMMON_LINK'], '/') . '/orders.php';

                // ===================== SEND RENEWAL EMAIL =====================
                $mail = new PHPMailer(true);
                try {
                    $mail->isSMTP();
                    $mail->Host       = 'smtp.gmail.com';
                    $mail->SMTPAuth   = true;
                    $mail->Username   = $_ENV['EMAIL_USERNAME'];
                    $mail->Password   = $_ENV['GMAIL_APP_PASSWORD'];
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port       = 587;

                    $mail->setFrom($_ENV['EMAIL_USERNAME'], 'Lufera Infotech');
                    $mail->addAddress($toEmail, $toName);

                    $mail->isHTML(true);
                    $mail->Subject = "Renewal Confirmation - Order #$receipt_id";
                    $mail->Body = '
                        <!DOCTYPE html>
                        <html>
                        <head><meta charset="UTF-8"><title>Renewal Confirmation</title></head>
                        <body style="margin:0;padding:0;background:#f5f5f5;font-family:Roboto,Arial,sans-serif;">
                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background:#f5f5f5;padding:30px 0;">
                            <tr><td align="center">
                                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="600" 
                                    style="background:#ffffff;border:1px solid #e0e0e0;border-radius:8px;box-shadow:0 2px 6px rgba(0,0,0,0.08);overflow:hidden;">
                                    <tr><td style="padding:20px;text-align:center;">
                                        <img src="' . htmlspecialchars($_ENV['EMAIL_IMAGE_LINK']) . '" alt="Lufera Infotech Logo" style="width:150px;height:48px;display:block;margin:auto;">
                                    </td></tr>
                                    <tr><td style="border-top:1px solid #eaeaea;"></td></tr>
                                    <tr><td style="padding:30px 40px;text-align:left;font-size:15px;line-height:1.6;color:#101010;">
                                        <h3 style="margin:0 0 15px;font-size:20px;font-weight:500;">Renewal Confirmation</h3>
                                        <p>Hello <b>' . htmlspecialchars($toName) . '</b>,</p>
                                        <p>Thank you for renewing your plan! Here are your updated order details:</p>
                                        <table cellpadding="8" cellspacing="0" border="0" width="100%" style="border:1px solid #eaeaea;margin:20px 0;font-size:14px;">
                                            <tr><td><b>Plan</b></td><td>' . htmlspecialchars($plan_name) . '</td></tr>
                                            <tr><td><b>Receipt ID</b></td><td>' . htmlspecialchars($receipt_id) . '</td></tr>
                                            <tr><td><b>Renewal Duration</b></td><td>' . htmlspecialchars($renewal_duration) . '</td></tr>
                                            <tr><td><b>Total Paid</b></td><td id="currency-symbol-display">' . htmlspecialchars($symbol) . htmlspecialchars($total_price) . '</td></tr>
                                        </table>
                                        <p>Your renewal has been processed successfully. You can check your renewal details anytime in your dashboard.</p>
                                        <div style="margin:30px 0;text-align:center;">
                                            <a href="' . htmlspecialchars($orders_link) . '" style="background:#fec700;color:#101010;text-decoration:none;
                                                padding:12px 28px;border-radius:4px;font-weight:bold;display:inline-block;">View My Orders</a>
                                        </div>
                                        <p>If you have any questions, feel free to reply to this email.</p>
                                    </td></tr>
                                    <tr><td style="border-top:1px solid #eaeaea;"></td></tr>
                                    <tr><td style="padding:20px;text-align:center;font-size:12px;color:#777;">
                                        You’re receiving this email because you renewed your plan at <b>Admin Dashboard</b>.<br>
                                        &copy; 2025 Lufera Infotech. All rights reserved.
                                    </td></tr>
                                </table>
                            </td></tr>
                        </table>
                        </body>
                        </html>';
                    $mail->send();
                } catch (Exception $e) {
                    error_log("Renewal email failed: " . $mail->ErrorInfo);
                }

                echo "<script> 
                    Swal.fire({ 
                        icon: 'success',
                        title: 'Renewed Successfully',
                        confirmButtonColor: '#3085d6'
                    }).then(() => {
                        window.location.href = 'subscription.php'; 
                    }); 
                </script>"; 
            } else {
                echo "<script> 
                    Swal.fire({ 
                        icon: 'error',
                        title: 'Update Failed',
                        text: 'Database error: " . addslashes($stmt->error) . "',
                        confirmButtonColor: '#d33'
                    });
                </script>"; 
            }
            $stmt->close(); 
        } else {
            $sql    = "SELECT id, user_id, email, username FROM users WHERE id = $user_id LIMIT 1";
            $result2 = mysqli_query($conn, $sql);
            $row    = mysqli_fetch_assoc($result2);
            $client_id = $row['id'];
            $toEmail   = $row['email'];     // purchaser email
            $username  = $row['username'];  // purchaser username
            $toName    = $row['username'];

            // // Only set addon_price if this is an addon row
            // $insert_addon_price = !empty($get_addon) ? $addon_total : '';

            // $main_subtotal = $price + floatval($insert_addon_price);
            // $main_discount = $discount ?? 0;
            
            // // Apply discount to subtotal first, then calculate GST on discounted amount
            // $discounted_subtotal = $main_subtotal - $discount_amount;
            // $main_gst = round($discounted_subtotal * ($tax_rate / 100), 2);
            // $main_amount = round($discounted_subtotal + $main_gst, 2);
            // $main_balance_due  = $main_amount - $payment_made;

            // ✅ Only set addon_price and addon_gst if this is an addon row
            $insert_addon_price = 0;
            $insert_addon_gst   = 0;

            if (!empty($get_addon)) {
                $insert_addon_price = floatval($addon_total);

                // --- Calculate addon GST (using each add-on's GST ID) ---
                $total_addon_gst = 0;
                if (!empty($addon_details)) {
                    foreach ($addon_details as $addon_item) {
                        $addon_tax_rate = 0;
                        if (!empty($addon_item['gst_id'])) {
                            $tax_stmt = $conn->prepare("SELECT rate FROM taxes WHERE id = ?");
                            $tax_stmt->bind_param("i", $addon_item['gst_id']);
                            $tax_stmt->execute();
                            $tax_res = $tax_stmt->get_result();
                            if ($tax_res && $tax_res->num_rows > 0) {
                                $tax_row = $tax_res->fetch_assoc();
                                $addon_tax_rate = floatval($tax_row['rate']);
                            }
                            $tax_stmt->close();
                        }
                        $addon_base_price = floatval($addon_item['price']);
                        $addon_gst_amount = round($addon_base_price * ($addon_tax_rate / 100), 2);
                        $total_addon_gst += $addon_gst_amount;
                    }
                }

                $insert_addon_gst = round($total_addon_gst, 2);
            }

            // // ✅ Calculate subtotal, GST, discount and total
            // $main_subtotal = $price + floatval($insert_addon_price) + floatval($insert_addon_gst);
            // $main_discount = $discount ?? 0;

            // // Apply discount to subtotal first, then calculate GST on discounted amount
            // $discounted_subtotal = $main_subtotal - $discount_amount;
            // $main_gst = round($discounted_subtotal * ($tax_rate / 100), 2);
            // $main_amount = round($discounted_subtotal + $main_gst, 2);
            // $main_balance_due  = $main_amount - $payment_made;

            // ✅ Calculate subtotal, GST, discount and total

            // Subtotal = plan price + add-on base + add-on GST (to get full gross)
            $main_subtotal = $price + floatval($insert_addon_price);
            $main_discount = $discount ?? 0;

            // Apply discount before tax (discount applies to plan only)
            $discounted_plan_price = max(0, $price - $discount_amount);

            // Calculate GST only for the main plan (❌ exclude add-on GST)
            $main_gst = round($discounted_plan_price * ($tax_rate / 100), 2);

            // Grand total = plan (after discount + GST) + addon base + addon GST
            $main_amount = round($discounted_plan_price + $main_gst + floatval($insert_addon_price) + floatval($insert_addon_gst), 2);
            $main_balance_due  = $main_amount - $payment_made;

            
            if (!empty($hostinger_balance)) {
                $sql = "INSERT INTO orders (user_id, invoice_id, plan, duration, amount, gst, price, addon_price, addon_gst, status, payment_method, discount, payment_made, created_on, subtotal, balance_due, addon_service, type, coupon_code, discount_amount, existing_balance, existing_plan) VALUES 
                    ('$client_id', '$receipt_id', '$plan_id', '$duration' ,'$upgrade_amount', '$upgrade_gst', '$price', '$insert_addon_price', '$insert_addon_gst', 'Pending', '$pay_method', '$main_discount', '$payment_made', '$created_at', '$main_subtotal', '$upgrade_amount', '$get_addon', '$type', '$coupon_code', '$discount_amount', '$hostinger_balance', '$current_plan_id')";
            }
            else{
                $sql = "INSERT INTO orders (user_id, invoice_id, plan, duration, amount, gst, price, addon_price, addon_gst, status, payment_method, discount, payment_made, created_on, subtotal, balance_due, addon_service, type, coupon_code, discount_amount) VALUES 
                    ('$client_id', '$receipt_id', '$plan_id', '$duration' ,'$main_amount', '$main_gst', '$price', '$insert_addon_price', '$insert_addon_gst', 'Pending', '$pay_method', '$main_discount', '$payment_made', '$created_at', '$main_subtotal', '$main_amount', '$get_addon', '$type', '$coupon_code', '$discount_amount')";
            }
            if (mysqli_query($conn, $sql)) {
                // === DEACTIVATE OLD ORDERS IF HOSTINGER BALANCE EXISTS ===
                if (!empty($hostinger_balance)) {
                    $updateActiveQuery = $conn->prepare("
                        UPDATE orders
                        SET is_Active = 0
                        WHERE invoice_id = ?
                       
                    ");
                    $updateActiveQuery->bind_param("i", $invoice_id);
                    $updateActiveQuery->execute();
                    $updateActiveQuery->close();
                }

                // ================= Packages =================
                // if (!empty($get_packages)) {
                //     $package_ids = array_map('intval', explode(',', $get_packages));
                //     foreach ($package_ids as $pkg_id) {
                //         // $pkg_sql = "SELECT package_name, price, duration, cat_id FROM package WHERE id = $pkg_id";
                //         // $pkg_res = mysqli_query($conn, $pkg_sql);
                //         // if ($pkg_res && $pkg = mysqli_fetch_assoc($pkg_res)) {
                //         //     $pkg_name     = $pkg['package_name'];  // ✅ package name for websites table
                //         //     $pkg_price    = floatval($pkg['price']);
                //         //     $pkg_duration = $pkg['duration'];
                //         //     $pkg_cat_id   = $pkg['cat_id']; // ✅ package category

                //         // Step 1: Fetch package name & category (same as before)
                //         $pkg_sql = "SELECT id, package_name, cat_id FROM package WHERE id = $pkg_id";
                //         $pkg_res = mysqli_query($conn, $pkg_sql);

                //         if ($pkg_res && $pkg = mysqli_fetch_assoc($pkg_res)) {
                //             $pkg_name   = $pkg['package_name'];  // ✅ Package name
                //             $pkg_cat_id = $pkg['cat_id'];        // ✅ Category

                //             // Step 2: Fetch duration & price from durations table
                //             $dur_sql = "SELECT duration, price FROM durations WHERE package_id = $pkg_id ORDER BY id ASC LIMIT 1";
                //             $dur_res = mysqli_query($conn, $dur_sql);

                //             if ($dur_res && $dur = mysqli_fetch_assoc($dur_res)) {
                //                 $pkg_duration = $dur['duration'];           // ✅ From durations table
                //                 $pkg_price    = floatval($dur['price']);    // ✅ From durations table
                //             } else {
                //                 // Fallback if no durations found
                //                 $pkg_duration = '1 month';
                //                 $pkg_price    = 0.00;
                //             }

                //             // calculations
                //             $pkg_subtotal = $pkg_price;
                //             $pkg_discount = 0;
                //             //$pkg_gst      = $pkg_subtotal * 0.18;
                //             $pkg_gst = round($pkg_subtotal * ($tax_rate / 100), 2);
                //             $pkg_amount   = $pkg_subtotal - $pkg_discount + $pkg_gst;
                //             $pkg_balance  = $pkg_amount - $payment_made;
                            
                //             // Generate unique invoice id for package
                //             $pkg_invoice_id = rand(10000000, 99999999);
                //             // Insert into orders (plan = ID)
                //             $sql_package = "INSERT INTO orders 
                //                 (user_id, invoice_id, plan, duration, amount, gst, price, addon_price, status, payment_method, discount, payment_made, created_on, subtotal, balance_due, addon_service, type) 
                //                 VALUES 
                //                 ('$client_id', '$pkg_invoice_id', '$pkg_id', '$pkg_duration', '$pkg_amount', '$pkg_gst', '$pkg_price', '$pkg_price', 'Pending', '$pay_method', '$pkg_discount', '$payment_made', '$created_at', '$pkg_subtotal', '$pkg_balance', '$pkg_id', 'package')";
                //             mysqli_query($conn, $sql_package);

                //             // Insert into websites (plan = NAME ✅)
                //             $siteInsertPkg = "INSERT INTO websites (user_id, domain, plan, duration, renewal_duration, expired_at, status, cat_id, invoice_id, product_id, type) 
                //                             VALUES ('$client_id', 'N/A', '$pkg_id', '$pkg_duration', '', NULL, 'Pending', '$pkg_cat_id', '$pkg_invoice_id', '$pkg_id', 'package')";
                //             mysqli_query($conn, $siteInsertPkg);
                //         }
                //     }
                // }
                // ================= Packages =================
                if (!empty($get_packages)) {
                    $package_ids = array_map('intval', explode(',', $get_packages));
                    foreach ($package_ids as $pkg_id) {

                        // Step 1: Fetch package name, category, and gst_id
                        $pkg_sql = "SELECT id, package_name, cat_id, gst_id FROM package WHERE id = $pkg_id";
                        $pkg_res = mysqli_query($conn, $pkg_sql);

                        if ($pkg_res && $pkg = mysqli_fetch_assoc($pkg_res)) {
                            $pkg_name   = $pkg['package_name'];  // ✅ Package name
                            $pkg_cat_id = $pkg['cat_id'];        // ✅ Category
                            $pkg_gst_id = $pkg['gst_id'];        // ✅ GST ID for individual GST fetch

                            // Step 2: Fetch duration & price from durations table
                            $dur_sql = "SELECT duration, price FROM durations WHERE package_id = $pkg_id ORDER BY id ASC LIMIT 1";
                            $dur_res = mysqli_query($conn, $dur_sql);

                            if ($dur_res && $dur = mysqli_fetch_assoc($dur_res)) {
                                $pkg_duration = $dur['duration'];           // ✅ From durations table
                                $pkg_price    = floatval($dur['price']);    // ✅ From durations table
                            } else {
                                // Fallback if no durations found
                                $pkg_duration = '1 month';
                                $pkg_price    = 0.00;
                            }

                            // ✅ Fetch the correct GST rate for this package
                            $pkg_tax_rate = 0;
                            if (!empty($pkg_gst_id)) {
                                $gst_stmt = $conn->prepare("SELECT rate FROM taxes WHERE id = ?");
                                $gst_stmt->bind_param("i", $pkg_gst_id);
                                $gst_stmt->execute();
                                $gst_result = $gst_stmt->get_result();
                                if ($gst_result && $gst_result->num_rows > 0) {
                                    $gst_row = $gst_result->fetch_assoc();
                                    $pkg_tax_rate = floatval($gst_row['rate']);
                                }
                                $gst_stmt->close();
                            }

                            // ✅ Individual package calculations using its own GST
                            $pkg_subtotal = $pkg_price;
                            $pkg_discount = 0;
                            $pkg_gst      = round($pkg_subtotal * ($pkg_tax_rate / 100), 2);
                            $pkg_amount   = $pkg_subtotal - $pkg_discount + $pkg_gst;
                            $pkg_balance  = $pkg_amount - $payment_made;

                            // Generate unique invoice id for package
                            $pkg_invoice_id = rand(10000000, 99999999);

                            // ✅ Insert into orders (addon_price, addon_gst kept empty)
                            $sql_package = "INSERT INTO orders 
                                (user_id, invoice_id, plan, duration, amount, gst, price, addon_price, addon_gst, status, payment_method, discount, payment_made, created_on, subtotal, balance_due, addon_service, type) 
                                VALUES 
                                ('$client_id', '$pkg_invoice_id', '$pkg_id', '$pkg_duration', '$pkg_amount', '$pkg_gst', '$pkg_price', '', '', 'Pending', '$pay_method', '$pkg_discount', '$payment_made', '$created_at', '$pkg_subtotal', '$pkg_balance', '$pkg_id', 'package')";
                            mysqli_query($conn, $sql_package);

                            // Insert into websites (plan = ID ✅)
                            $siteInsertPkg = "INSERT INTO websites (user_id, domain, plan, duration, renewal_duration, expired_at, status, cat_id, invoice_id, product_id, type) 
                                            VALUES ('$client_id', 'N/A', '$pkg_id', '$pkg_duration', '', '', 'Pending', '$pkg_cat_id', '$pkg_invoice_id', '$pkg_id', 'package')";
                            mysqli_query($conn, $siteInsertPkg);
                        }
                    }
                }

                // ================= Products =================
                // if (!empty($get_products)) {
                //     $product_ids = array_map('intval', explode(',', $get_products));
                //     foreach ($product_ids as $prod_id) {
                //         $prod_sql = "SELECT name, price, duration, cat_id FROM products WHERE id = $prod_id";
                //         $prod_res = mysqli_query($conn, $prod_sql);
                //         if ($prod_res && $prod = mysqli_fetch_assoc($prod_res)) {
                //             $prod_name     = $prod['name'];   // ✅ product name for websites table
                //             $prod_price    = floatval($prod['price']);
                //             $prod_duration = $prod['duration'];
                //             $prod_cat_id   = $prod['cat_id']; // ✅ product category

                //             // calculations
                //             $prod_subtotal = $prod_price;
                //             $prod_discount = 0;
                //             $prod_gst      = $prod_subtotal * 0.18;
                //             $prod_amount   = $prod_subtotal - $prod_discount + $prod_gst;
                //             $prod_balance  = $prod_amount - $payment_made;

                //             // Generate unique invoice id for product
                //             $prod_invoice_id = rand(10000000, 99999999);

                //             // Insert into orders (plan = ID)
                //             $sql_product = "INSERT INTO orders 
                //                 (user_id, invoice_id, plan, duration, amount, gst, price, addon_price, status, payment_method, discount, payment_made, created_on, subtotal, balance_due, addon_service, type) 
                //                 VALUES 
                //                 ('$client_id', '$prod_invoice_id', '$prod_id', '$prod_duration', '$prod_amount', '$prod_gst', '$prod_price', '$prod_price', 'Pending', '$pay_method', '$prod_discount', '$payment_made', '$created_at', '$prod_subtotal', '$prod_balance', '$prod_id', 'product')";
                //             mysqli_query($conn, $sql_product);

                //             // Insert into websites (plan = NAME ✅)
                //             $siteInsertProd = "INSERT INTO websites (user_id, domain, plan, duration, renewal_duration, expired_at, status, cat_id, invoice_id, product_id, type) 
                //                             VALUES ('$client_id', 'N/A', '$prod_id', '$prod_duration', '', NULL, 'Pending', '$prod_cat_id', '$prod_invoice_id', '$prod_id', 'product')";
                //             mysqli_query($conn, $siteInsertProd);
                //         }
                //     }
                // }
                // ================= Products =================
                if (!empty($get_products)) {
                    $product_ids = array_map('intval', explode(',', $get_products));
                    foreach ($product_ids as $prod_id) {

                        // Fetch product details including gst_id
                        $prod_sql = "SELECT name, price, duration, cat_id, gst FROM products WHERE id = $prod_id";
                        $prod_res = mysqli_query($conn, $prod_sql);

                        if ($prod_res && $prod = mysqli_fetch_assoc($prod_res)) {
                            $prod_name     = $prod['name'];   // ✅ product name for websites table
                            $prod_price    = floatval($prod['price']);
                            $prod_duration = $prod['duration'];
                            $prod_cat_id   = $prod['cat_id']; // ✅ product category
                            $prod_gst_id   = $prod['gst'];    // ✅ product-specific GST ID

                            // ✅ Fetch GST rate for this specific product
                            $prod_tax_rate = 0;
                            if (!empty($prod_gst_id)) {
                                $tax_stmt = $conn->prepare("SELECT rate FROM taxes WHERE id = ?");
                                $tax_stmt->bind_param("i", $prod_gst_id);
                                $tax_stmt->execute();
                                $tax_result = $tax_stmt->get_result();
                                if ($tax_result && $tax_result->num_rows > 0) {
                                    $tax_row = $tax_result->fetch_assoc();
                                    $prod_tax_rate = floatval($tax_row['rate']);
                                }
                                $tax_stmt->close();
                            }

                            // ✅ Use product's own GST rate
                            $prod_subtotal = $prod_price;
                            $prod_discount = 0;
                            $prod_gst      = round($prod_subtotal * ($prod_tax_rate / 100), 2);
                            $prod_amount   = $prod_subtotal - $prod_discount + $prod_gst;
                            $prod_balance  = $prod_amount - $payment_made;

                            // Generate unique invoice id for product
                            $prod_invoice_id = rand(10000000, 99999999);

                            // ✅ Insert into orders (addon_price and addon_gst kept empty)
                            $sql_product = "INSERT INTO orders 
                                (user_id, invoice_id, plan, duration, amount, gst, price, addon_price, addon_gst, status, payment_method, discount, payment_made, created_on, subtotal, balance_due, addon_service, type) 
                                VALUES 
                                ('$client_id', '$prod_invoice_id', '$prod_id', '$prod_duration', '$prod_amount', '$prod_gst', '$prod_price', '', '', 'Pending', '$pay_method', '$prod_discount', '$payment_made', '$created_at', '$prod_subtotal', '$prod_balance', '$prod_id', 'product')";
                            mysqli_query($conn, $sql_product);

                            // Insert into websites (plan = NAME ✅)
                            $siteInsertProd = "INSERT INTO websites (user_id, domain, plan, duration, renewal_duration, expired_at, status, cat_id, invoice_id, product_id, type) 
                                            VALUES ('$client_id', 'N/A', '$prod_id', '$prod_duration', '', NULL, 'Pending', '$prod_cat_id', '$prod_invoice_id', '$prod_id', 'product')";
                            mysqli_query($conn, $siteInsertProd);
                        }
                    }
                }

                $domain = "N/A";

                // Insert new website record
                // $siteInsert = "INSERT INTO websites (user_id, domain, plan, duration, renewal_duration, expired_at, status, cat_id, invoice_id, product_id, type) 
                //             VALUES ('$client_id', '$domain', '$plan_id', '$duration', '', NULL, 'Pending', '$cat_id', '$receipt_id', '$plan_id', '$type')";
                // mysqli_query($conn, $siteInsert);

                $siteInsert = "INSERT INTO websites (user_id, domain, plan, duration, renewal_duration, expired_at, status, cat_id, invoice_id, product_id, type)
                            VALUES ('$client_id', '$domain', '$plan_id', '$duration', '', NULL, 'Pending', '$cat_id', '$receipt_id', '$plan_id', '$type')";
                $siteInsertResult = mysqli_query($conn, $siteInsert);
                // === DEACTIVATE OLD WEBSITE IF INSERT SUCCEEDED ===
                if ($siteInsertResult) {
                    $updateActiveQuerys = $conn->prepare("
                        UPDATE websites
                        SET is_Active = 0
                        WHERE id = ?
                      
                    ");
                    $updateActiveQuerys->bind_param("i", $web_id);
                    $updateActiveQuerys->execute();
                    $updateActiveQuerys->close();
                } else {
                    echo "<script>alert('Error inserting website: " . mysqli_error($conn) . "');</script>";
                }

                // Show loader immediately
                echo "
                <script>
                    Swal.fire({
                        title: 'Processing...',
                        text: 'Please wait while we finalize your purchase.',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                            const spinner = document.querySelector('.swal2-loader');
                            if (spinner) {
                                spinner.style.borderColor = '#fec700 transparent #fec700 transparent';
                            }
                        }
                    });
                </script>";
                // flush response so browser shows the loader instantly
                ob_flush(); flush();

                $orders_link = rtrim($_ENV['EMAIL_COMMON_LINK'], '/') . '/orders.php';

                // ===================== SEND PURCHASE EMAIL =====================
                $mail = new PHPMailer(true);
                try {
                    $mail->isSMTP();
                    $mail->Host       = 'smtp.gmail.com';
                    $mail->SMTPAuth   = true;
                    $mail->Username   = $_ENV['EMAIL_USERNAME'];
                    $mail->Password   = $_ENV['GMAIL_APP_PASSWORD'];
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port       = 587;

                    $mail->setFrom($_ENV['EMAIL_USERNAME'], 'Lufera Infotech');
                    $mail->addAddress($toEmail, $toName);

                    $mail->isHTML(true);
                    $mail->Subject = "Purchase Confirmation - Order #$receipt_id";
                    $mail->Body = '
                        <!DOCTYPE html>
                        <html>
                        <head><meta charset="UTF-8"><title>Purchase Confirmation</title></head>
                        <body style="margin:0;padding:0;background:#f5f5f5;font-family:Roboto,Arial,sans-serif;">
                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background:#f5f5f5;padding:30px 0;">
                            <tr><td align="center">
                                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="600" 
                                    style="background:#ffffff;border:1px solid #e0e0e0;border-radius:8px;box-shadow:0 2px 6px rgba(0,0,0,0.08);overflow:hidden;">
                                    <tr><td style="padding:20px;text-align:center;">
                                        <img src="' . htmlspecialchars($_ENV['EMAIL_IMAGE_LINK']) . '" alt="Lufera Infotech Logo" style="width:150px;height:48px;display:block;margin:auto;">
                                    </td></tr>
                                    <tr><td style="border-top:1px solid #eaeaea;"></td></tr>
                                    <tr><td style="padding:30px 40px;text-align:left;font-size:15px;line-height:1.6;color:#101010;">
                                        <h3 style="margin:0 0 15px;font-size:20px;font-weight:500;">Purchase Confirmation</h3>
                                        <p>Hello <b>' . htmlspecialchars($toName) . '</b>,</p>
                                        <p>Thank you for your purchase! Here are the details of your order:</p>
                                        <table cellpadding="8" cellspacing="0" border="0" width="100%" style="border:1px solid #eaeaea;margin:20px 0;font-size:14px;">
                                            <tr><td><b>Plan</b></td><td>' . htmlspecialchars($plan_name) . '</td></tr>
                                            <tr><td><b>Receipt ID</b></td><td>' . htmlspecialchars($receipt_id) . '</td></tr>
                                            <tr><td><b>Duration</b></td><td>' . htmlspecialchars($duration) . '</td></tr>
                                            <tr><td><b>Total Paid</b></td><td id="currency-symbol-display">' . htmlspecialchars($symbol) . htmlspecialchars($total_price) . '</td></tr>
                                        </table>
                                        <p>Your service will be activated shortly. You can check your order status anytime in your dashboard.</p>
                                        <div style="margin:30px 0;text-align:center;">
                                            <a href="' . htmlspecialchars($orders_link) . '" style="background:#fec700;color:#101010;text-decoration:none;
                                                padding:12px 28px;border-radius:4px;font-weight:bold;display:inline-block;">View My Orders</a>
                                        </div>
                                        <p>If you have any questions, feel free to reply to this email.</p>
                                    </td></tr>
                                    <tr><td style="border-top:1px solid #eaeaea;"></td></tr>
                                    <tr><td style="padding:20px;text-align:center;font-size:12px;color:#777;">
                                        You’re receiving this email because you made a purchase at <b>Admin Dashboard</b>.<br>
                                        &copy; 2025 Lufera Infotech. All rights reserved.
                                    </td></tr>
                                </table>
                            </td></tr>
                        </table>
                        </body>
                        </html>';
                    $mail->send();
                } catch (Exception $e) {
                    error_log("Purchase email failed: " . $mail->ErrorInfo);
                }

                echo "
                <script>
                    Swal.fire({
                        title: 'Success!',
                        text: 'Purchased Successfully.',
                        confirmButtonText: 'OK',
                        allowOutsideClick: false
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = 'subscription.php';
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
    }

    // USER sends payment request (→ notify all admins)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save']) && ($role != '1' || $role != '2')) {
        date_default_timezone_set('Asia/Kolkata');
        $msg = "$username has sent a payment request.";

        $adminQuery = $conn->query("SELECT user_id, email, username FROM users WHERE role IN ('1', '2')");
        while ($adminRow = $adminQuery->fetch_assoc()) {
            $adminUserId = $adminRow['user_id'];
            $adminEmail  = $adminRow['email'];
            $adminName   = $adminRow['username'];
            $createdAt = date('Y-m-d H:i:s');

            $stmt = $conn->prepare("INSERT INTO notifications (user_id, message, n_photo, created_at) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $adminUserId, $msg, $photo, $createdAt);
            $stmt->execute();

            // Send email to admin/super admin
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = $_ENV['EMAIL_USERNAME'];
                $mail->Password   = $_ENV['GMAIL_APP_PASSWORD'];
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;

                $mail->setFrom($toEmail, $username); // from purchaser
                $mail->addAddress($adminEmail, $adminName);

                $mail->isHTML(true);
                $mail->Subject = "Payment Request from $username";
                $mail->Body = '
                    <!DOCTYPE html>
                    <html>
                    <head><meta charset="UTF-8"><title>Payment Request</title></head>
                    <body style="margin:0;padding:0;background:#f5f5f5;font-family:Roboto,Arial,sans-serif;">
                    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background:#f5f5f5;padding:30px 0;">
                        <tr><td align="center">
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="600" 
                                style="background:#ffffff;border:1px solid #e0e0e0;border-radius:8px;box-shadow:0 2px 6px rgba(0,0,0,0.08);overflow:hidden;">
                                <tr><td style="padding:20px;text-align:center;">
                                    <img src="' . htmlspecialchars($_ENV['EMAIL_IMAGE_LINK']) . '" alt="Lufera Infotech Logo" style="width:150px;height:48px;display:block;margin:auto;">
                                </td></tr>
                                <tr><td style="border-top:1px solid #eaeaea;"></td></tr>
                                <tr><td style="padding:30px 40px;text-align:left;font-size:15px;line-height:1.6;color:#101010;">
                                    <h3 style="margin:0 0 15px;font-size:20px;font-weight:500;">Payment Request Email</h3>
                                    <p>Hello <b>' . htmlspecialchars($adminName) . '</b>,</p>
                                    <p>User <b>' . htmlspecialchars($username) . '</b> (' . htmlspecialchars($toEmail) . ') has sent a payment request. Order details are below:</p>
                                    <table cellpadding="8" cellspacing="0" border="0" width="100%" style="border:1px solid #eaeaea;margin:20px 0;font-size:14px;">
                                        <tr><td><b>Plan</b></td><td>' . htmlspecialchars($plan_name) . '</td></tr>
                                        <tr><td><b>Receipt ID</b></td><td>' . htmlspecialchars($receipt_id) . '</td></tr>
                                        <tr><td><b>Duration</b></td><td>' . htmlspecialchars($duration) . '</td></tr>
                                        <tr><td><b>Price</b></td><td id="currency-symbol-display">' . htmlspecialchars($symbol) . htmlspecialchars($price) . '</td></tr>
                                        <tr><td><b>GST</b></td><td id="currency-symbol-display">' . htmlspecialchars($symbol) . htmlspecialchars($gst) . '</td></tr>
                                        <tr><td><b>Total</b></td><td id="currency-symbol-display">' . htmlspecialchars($symbol) . htmlspecialchars($total_price) . '</td></tr>
                                    </table>
                                    <div style="margin:30px 0;text-align:center;">
                                        <a href="' . htmlspecialchars($_ENV['EMAIL_COMMON_LINK']) . '/orders.php" 
                                        style="background:#fec700;color:#101010;text-decoration:none;
                                                padding:12px 28px;border-radius:4px;font-weight:bold;display:inline-block;">Review Payment Request</a>
                                    </div>
                                    <p>Thank you,<br><b>Admin Dashboard</b></p>
                                </td></tr>
                                <tr><td style="border-top:1px solid #eaeaea;"></td></tr>
                                <tr><td style="padding:20px;text-align:center;font-size:12px;color:#777;">
                                    You’re receiving this email because a user submitted a payment request.<br>
                                    &copy; 2025 Lufera Infotech. All rights reserved.
                                </td></tr>
                            </table>
                        </td></tr>
                    </table>
                    </body>
                    </html>';
                $mail->send();
            } catch (Exception $e) {
                error_log("Admin email failed: " . $mail->ErrorInfo);
            }
        }
    }
?>

<style>
    .plan-details-table tbody tr td, .plan-details-tables tbody tr td {
        padding: 15px .5rem;
        border-bottom: 1px solid #dadada;
        width: 50%;
    }
    .ad-box {
        background: lightgoldenrodyellow;
        padding: 2px;
        border: 1px solid;
        margin: 10px 0 0;
    }

    /* Payment Option Boxes */
    .payment-option-box {
        border: 1px solid black;
        border-radius: 6px;
        padding: 10px 16px;
        display: flex;
        align-items: center;
        background-color: #fff;
        width: auto;
        max-width: 200px;
        cursor: pointer;
        flex-grow: 0;
        margin-right: 10px;
        margin-bottom: 10px;
        user-select: none;
        transition: background-color 0.2s ease;
    }
    .payment-option-box:hover {
        background-color: #fff8dc;
    }
    .payment-option-box input[type="radio"] {
        margin: 0;
        cursor: pointer;
    }
    .payment-option-box label {
        cursor: pointer;
        margin-left: 8px;
        display: flex;
        align-items: center;
        gap: 6px;
        font-weight: 600;
    }
    .icon-circle {
        background-color: #fec700;
        color: white;
        border-radius: 50%;
        padding: 6px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 24px;
        height: 24px;
        font-size: 12px;
    }

    /* Card shadow and button styles */
    .custom-pay-btn {
        background-color: #fec700;
        color: black;
        border: 1px solid black;
        box-shadow: none;
        border-radius: 0;
        padding: 8px 20px;
        font-weight: 600;
        width:200px;
    }
    .custom-pay-btn:hover {
        background-color: #ffd700;
    }
    .card-shadow {
        box-shadow: 0px 3px 3px 0px lightgray;
    }

    /* Responsive adjustments */
    @media (max-width: 992px) {
        .payment-option-box {
            max-width: 180px;
            padding: 8px 14px;
            font-size: 0.95rem;
        }
    }
    @media (max-width: 576px) {
        .payment-option-box {
            max-width: 100%;
            margin-right: 0;
            padding: 12px 20px;
            font-size: 1rem;
        }
        .payment-option-box label {
            gap: 8px;
            font-weight: 700;
        }
    }
    .payment-detail { display:none; }

    .coupon-item {
        background: #f8f9fa;
        transition: background-color 0.2s ease;
    }
    .coupon-item:hover {
        background: #fff8dc;
    }
    .coupon-item .btn {
        padding: 6px 12px;
        font-size: 0.9rem;
    }
    .modal-content {
        border-radius: 8px;
        box-shadow: 0 2px 6px rgba(0,0,0,0.08);
    }
    .modal-header {
        border-bottom: 1px solid #eaeaea;
    }
    .modal-footer {
        border-top: 1px solid #eaeaea;
    }
</style>

<div class="dashboard-main-body">
    <form method="post">
        <input type="hidden" value="<?php echo $id; ?>" name="id">
        <input type="hidden" value="<?php echo $type; ?>" name="type">
        <input type="hidden" value="<?php echo $duration; ?>" name="duration">
        <input type="hidden" value="<?php echo $receipt_id; ?>" name="receipt_id">
        <input type="hidden" value="<?php echo $plan_name; ?>" name="plan_name">
        <input type="hidden" value="<?php echo $price; ?>" name="price">
        <input type="hidden" value="<?php echo $upgrade_amount; ?>" name="upgrade_amount">
        <?php if($hostinger_balance != ""){ ?>
            <input type="hidden" value="<?php echo $amount_tax; ?>" name="amount_tax">
        <?php }  else { ?>
            <input type="hidden" value="<?php echo $gst; ?>" name="gst">
        <?php } ?>
        <?php if($hostinger_balance != ""){ ?>
            <input type="hidden" value="<?php echo $amount_to_pay; ?>" name="total_price">
        <?php }  else { ?>
            <input type="hidden" value="<?php echo $total_price; ?>" name="total_price">
        <?php } ?>
        <input type="hidden" value="<?php echo $created_on; ?>" name="created_on">
        <input type="hidden" value="<?php echo $get_addon; ?>" name="get_addon">
        <input type="hidden" value="<?php echo $get_packages ?? ''; ?>" name="get_packages">
        <input type="hidden" value="<?php echo $get_products ?? ''; ?>" name="get_products">
        <input type="hidden" value="<?php echo $addon_total; ?>" name="addon-total">
        <input type="hidden" name="gst_id" value="<?php echo $_POST['gst_id'] ?? ''; ?>">
        <input type="hidden" id="coupon_code_hidden" name="coupon_code" value="<?php echo isset($_POST['coupon_code']) ? htmlspecialchars($_POST['coupon_code']) : ''; ?>">
        <input type="hidden" id="discount_amount" name="discount_amount" value="0.00">
        <input type="hidden" value="<?php echo $web_id; ?>" name="web_id">
        <input type="hidden" value="<?php echo $invoice_id; ?>" name="invoice_id">
        <input type="hidden" value="<?php echo $hostinger_balance; ?>" name="hostinger_balance">
        <input type="hidden" value="<?php echo $current_plan_id; ?>" name="current_plan_id">

        <?php if (isset($_POST['renewal']) && $_POST['renewal'] == 1): ?>
            <input type="hidden" name="renewal" value="1">
            <input type="hidden" name="id" value="<?= htmlspecialchars($_POST['id']) ?>">
            <input type="hidden" name="period" value="<?= htmlspecialchars($_POST['period']) ?>">
            <input type="hidden" name="expiration_date" value="<?= htmlspecialchars($_POST['expiration_date']) ?>">
            <input type="hidden" name="total" value="<?= htmlspecialchars($_POST['total']) ?>">
        <?php endif; ?>

        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
        
                <a class="cursor-pointer fw-bold" onclick="history.back()"><span class="fa fa-arrow-left"></span>&nbsp; Back</a>    
                <h6 class="fw-semibold mb-0">Your Cart</h6>
                <button type="submit" name="save" id="continuePayBtn" class="lufera-bg text-center btn-sm px-12 py-10 float-end" style="width:150px; border: 1px solid #000" value="Submit">Continue to Pay</button>
            
        </div>
        
        <div class="mb-40">
            <div class="row gy-4">
                <!-- First Card -->
                <div class="col-lg-6 col-md-12">
                    <div class="card h-100 radius-12">
                        <div class="card-header py-10 border-none card-shadow">
                            <div class="d-flex justify-content-between align-items-center">
                                <h6 class="mb-0"><?php echo $plan_name; ?></h6>
                                <span class="text-muted small">Receipt ID: <?php echo $receipt_id; ?></span>
                            </div>
                            <!-- <p class="mb-0">Perfect plan to get started for your own Website</p> -->
                        </div>
                        <div class="card-body p-16">
                            <table class="plan-details-tables mb-0 w-100">
                                <tbody>
                                    <!-- <tr>
                                        <td>Period</td>
                                        <td><?php echo $duration; ?></td>
                                    </tr> -->
                                    <tr>
                                        <td>Period</td>
                                        <td>
                                            <?php
                                                if (isset($_POST['renewal']) && $_POST['renewal'] == 1 && !empty($_POST['period'])) {
                                                    echo htmlspecialchars($original_duration) . " + Renewal (" . htmlspecialchars($_POST['period']) . ")";
                                                } else {
                                                    echo htmlspecialchars($original_duration);
                                                }
                                            ?>
                                        </td>
                                    </tr>
                                    <!-- <tr>
                                        <td>Validity</td>
                                        <td>
                                            <?php
                                                $start_date = new DateTime($created_on);

                                                $duration_str = $duration;
                                                try {
                                                    $interval = DateInterval::createFromDateString($duration_str);
                                                    $end_date = clone $start_date;
                                                    $end_date->add($interval);

                                                    echo $start_date->format('d-m-Y') . " to " . $end_date->format('d-m-Y');
                                                } catch (Exception $e) {
                                                    echo $start_date->format('d-m-Y') . " to (Invalid duration)";
                                                }
                                            ?>
                                        </td>
                                    </tr> -->
                                    <tr>
                                        <td>Validity</td>
                                        <td>
                                            <?php
                                                $start_date = new DateTime($created_on);
                                                $duration_str = $original_duration ?? $duration;

                                                try {
                                                    // Original period end date
                                                    $interval_original = DateInterval::createFromDateString($original_duration);
                                                    $original_end_date = clone $start_date;
                                                    $original_end_date->add($interval_original);

                                                    if (isset($_POST['renewal']) && $_POST['renewal'] == 1 && !empty($_POST['period'])) {
                                                        // Renewal period
                                                        $renewal_period = $_POST['period'];
                                                        $interval_renewal = DateInterval::createFromDateString($renewal_period);
                                                        $renewal_end_date = clone $original_end_date;
                                                        $renewal_end_date->add($interval_renewal);

                                                        echo $start_date->format('d-m-Y') . " to " . $original_end_date->format('d-m-Y') .
                                                            " + Renewal (" . $original_end_date->format('d-m-Y') . " to " . $renewal_end_date->format('d-m-Y') . ")";
                                                    } else {
                                                        // No renewal
                                                        echo $start_date->format('d-m-Y') . " to " . $original_end_date->format('d-m-Y');
                                                    }
                                                } catch (Exception $e) {
                                                    echo $start_date->format('d-m-Y') . " to (Invalid duration)";
                                                }
                                            ?>
                                        </td>
                                    </tr>
                                    <!-- <tr>
                                        <td>Add-Ons</td>
                                        <td>
                                            <?php
                                                $output_parts = [];

                                                if (!empty($package_names)) {
                                                    $output_parts[] = "<b>Packages:</b> " . htmlspecialchars($package_names);
                                                }
                                                if (!empty($product_names)) {
                                                    $output_parts[] = "<b>Products:</b> " . htmlspecialchars($product_names);
                                                }
                                                if (!empty($service_name)) {
                                                    $output_parts[] = "<b>Add-On Services:</b> " . htmlspecialchars($service_name);
                                                }

                                                echo !empty($output_parts) ? implode(" | ", $output_parts) : "None";
                                            ?>
                                        </td>
                                    </tr> -->
                                    <!-- <tr>
                                        <td class="border-0" colspan="2" id="currency-symbol-display">Renews at <?= htmlspecialchars($symbol) ?>1500/year for 3 Years
                                            <p class="text-sm ad-box">Great news! Your FREE domain + 3 months FREE are included with this order</p>
                                        </td>
                                    </tr> -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Second Card -->
                <div class="col-lg-6 col-md-12">
                    <div class="card h-100 radius-12">
                        <div class="card-header py-10 border-none d-flex justify-content-between card-shadow">
                            <div>
                                <h6 class="mb-0">Sub Total</h6>
                                <!-- <p class="mb-0">Sub total does not include applicable taxes</p> -->
                            </div>
                            <!-- <div class="align-content-center">
                                <?php if($hostinger_balance != 0){ ?>
                                    <h6 class="mb-0"><?php echo htmlspecialchars($symbol) . number_format($total_breakdown_price, 2); ?></h6>
                                <?php } else { ?>
                                    <h6 class="mb-0"><?php echo htmlspecialchars($symbol) . number_format($subtotal_display, 2); ?></h6>
                                <?php } ?>
                            </div> -->
                            <div class="align-content-center">
                                <?php if (isset($_POST['renewal']) && $_POST['renewal'] == 1) { ?>
                                    <!-- 🧾 Renewal case: show total_price -->
                                    <h6 class="mb-0"><?php echo htmlspecialchars($symbol) . number_format($total_price, 2); ?></h6>
                                <?php } elseif ($hostinger_balance != 0) { ?>
                                    <!-- Existing Plan Balance Case -->
                                    <h6 class="mb-0"><?php echo htmlspecialchars($symbol) . number_format($price, 2); ?></h6>
                                <?php } else { ?>
                                    <!-- Normal Purchase Case -->
                                    <h6 class="mb-0"><?php echo htmlspecialchars($symbol) . number_format($subtotal_display, 2); ?></h6>
                                <?php } ?>
                            </div>
                        </div>
                        <div class="card-body p-16">
                            
                            <!-- <?php
                                $plan_base_price      = floatval($price ?? 0);
                                $plan_tax_amount      = floatval($gst ?? ($tax_rate ?? 0) * $plan_base_price / 100);
                                $plan_total_amount    = round($plan_base_price + $plan_tax_amount, 2);
                                $plan_tax_rate_display = isset($tax_rate) ? floatval($tax_rate) : 0;
                            ?> -->
                            <?php
                                // Base plan price
                                $plan_base_price = floatval($price ?? 0);

                                // Default tax values
                                $plan_tax_rate_display = isset($tax_rate) ? floatval($tax_rate) : 0;
                                $plan_tax_amount = 0;

                                // // Renewal case: fetch plan's own tax rate dynamically
                                // if (isset($_POST['renewal']) && $_POST['renewal'] == 1) {
                                //     $renewal_gst_id = $_POST['gst_id'] ?? null;

                                //     // ✅ Fallback: if gst_id not in POST, reuse plan's gst_id variable
                                //     if (empty($renewal_gst_id) && !empty($gst_id)) {
                                //         $renewal_gst_id = $gst_id;
                                //     }

                                //     if (!empty($renewal_gst_id)) {
                                //         $stmt = $conn->prepare("SELECT rate FROM taxes WHERE id = ?");
                                //         $stmt->bind_param("i", $renewal_gst_id);
                                //         $stmt->execute();
                                //         $result = $stmt->get_result();
                                //         if ($result && $result->num_rows > 0) {
                                //             $tax_row = $result->fetch_assoc();
                                //             $plan_tax_rate_display = floatval($tax_row['rate']);
                                //         }
                                //         $stmt->close();
                                //     }
                                // }
                                // Renewal case: fetch plan's own tax rate dynamically
                                if (isset($_POST['renewal']) && $_POST['renewal'] == 1) {
                                    $renewal_gst_id = null;

                                    // 1️⃣ Try from POST
                                    if (!empty($_POST['gst_id'])) {
                                        $renewal_gst_id = (int) $_POST['gst_id'];
                                    }

                                    // 2️⃣ Try from variable loaded earlier
                                    elseif (!empty($gst_id)) {
                                        $renewal_gst_id = (int) $gst_id;
                                    }

                                    // 3️⃣ Final fallback — fetch from websites or plans table
                                    if (empty($renewal_gst_id) && !empty($id)) {
                                        $stmt = $conn->prepare("
                                            SELECT p.gst_id
                                            FROM websites w
                                            LEFT JOIN package p ON w.plan = p.id
                                            WHERE w.id = ?
                                            LIMIT 1
                                        ");
                                        $stmt->bind_param("i", $id);
                                        $stmt->execute();
                                        $result = $stmt->get_result();
                                        if ($result && $result->num_rows > 0) {
                                            $row = $result->fetch_assoc();
                                            $renewal_gst_id = (int) $row['gst_id'];
                                        }
                                        $stmt->close();
                                    }

                                    // 4️⃣ Finally fetch tax rate from taxes
                                    if (!empty($renewal_gst_id)) {
                                        $stmt = $conn->prepare("SELECT rate FROM taxes WHERE id = ?");
                                        $stmt->bind_param("i", $renewal_gst_id);
                                        $stmt->execute();
                                        $result = $stmt->get_result();
                                        if ($result && $result->num_rows > 0) {
                                            $tax_row = $result->fetch_assoc();
                                            $plan_tax_rate_display = floatval($tax_row['rate']);
                                        }
                                        $stmt->close();
                                    }
                                }

                                // ✅ Calculate GST amount (works for both renewal & normal)
                                $plan_tax_amount = round($plan_base_price * ($plan_tax_rate_display / 100), 2);
                                $plan_total_amount = round($plan_base_price + $plan_tax_amount, 2);
                            ?>

                            <div class="d-flex justify-content-between align-items-center" style="padding: 15px .5rem; font-weight: 500; color: #000; border-bottom: 1px solid #dadada;">
                                <div class="d-flex align-items-center" style="gap: 10px;">
                                    <span><?php echo htmlspecialchars($plan_name); ?></span>
                                    <button type="button" class="btn btn-link p-0 border-0 plan-breakdown-toggle" data-target="#planBreakdown" data-display="block" aria-expanded="false" style="color: inherit;">
                                        <i class="fas fa-chevron-down"></i>
                                    </button>
                                </div>
                                
                                <?php if($hostinger_balance != 0){ ?>
                                    <div><?php echo htmlspecialchars($symbol) . number_format($total_amount, 2); ?></div>
                                    <?php } else{ ?>
                                        <div><?php echo htmlspecialchars($symbol) . number_format($plan_total_amount, 2); ?></div>
                                    <?php } ?>
                            </div>
                            <div id="planBreakdown" class="plan-breakdown-details" style="display: none; padding: 10px .5rem 15px; border-bottom: 1px solid #dadada; background-color: #f9f9f9;">
                                <div class="d-flex justify-content-between" style="font-size: 0.9rem; color: #555;">
                                    <span>Price</span>
                                    <?php if($hostinger_balance != 0){ ?>
                                        <span><?php echo htmlspecialchars($symbol) . number_format($price, 2); ?></span>
                                    <?php } else{ ?>
                                        <span><?php echo htmlspecialchars($symbol) . number_format($plan_base_price, 2); ?></span>
                                    <?php } ?>
                                </div>
                                <div class="d-flex justify-content-between" style="font-size: 0.9rem; color: #555;">
                                    
                                    <?php if($hostinger_balance != 0){ ?>
                                        <span>Tax (<?php echo number_format($total_breakdown_gst, 2); ?>%)</span>
                                        <span><?php echo htmlspecialchars($symbol) . number_format($amount_tax, 2); ?></span>
                                    <?php } else{ ?>
                                        <span>Tax (<?php echo number_format($plan_tax_rate_display, 2); ?>%)</span>
                                        <span><?php echo htmlspecialchars($symbol) . number_format($plan_tax_amount, 2); ?></span>
                                    <?php } ?>
                                </div>
                            </div>
                            
                            <table class="table plan-details-table mb-0 w-100">
                                <tbody>
                                <?php if (!empty($package_details) || !empty($product_details) || !empty($addon_details)): ?>
                                    <p class="fw-semibold px-6 mb-0 mt-10">Add-ons :</p>
                                <?php endif; ?>
                                    <!-- Plan Price -->
                                    <!-- <tr>
                                        <td><?php echo htmlspecialchars($plan_name); ?></td>
                                        <td class="text-end" id="currency-symbol-display"><?php echo htmlspecialchars($symbol) . number_format($price, 2); ?></td>
                                    </tr> -->
                                    
                                    <!-- Packages -->
                                    <!-- <?php if (!empty($package_details)): ?>
                                        <?php foreach ($package_details as $pkg_index => $package): ?>
                                                <?php
                                                    $package_base_price   = floatval($package['price'] ?? 0);
                                                    $package_tax_amount   = round($package_base_price * (floatval($tax_rate ?? 0) / 100), 2);
                                                    $package_total_amount = round($package_base_price + $package_tax_amount, 2);
                                                    $package_breakdown_id = 'packageBreakdown-' . $pkg_index;
                                                ?>
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center" style="gap: 10px;">
                                                            <span><?php echo htmlspecialchars($package['name']); ?> (Package)</span>
                                                            <button type="button" class="btn btn-link p-0 border-0 plan-breakdown-toggle" data-target="#<?php echo $package_breakdown_id; ?>" data-display="table-row" aria-expanded="false" style="color: inherit;">
                                                                <i class="fas fa-chevron-down"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                    <td class="text-end"><?php echo htmlspecialchars($symbol) . number_format($package_total_amount, 2); ?></td>
                                                </tr>
                                                <tr id="<?php echo $package_breakdown_id; ?>" class="plan-breakdown-details" style="display: none; background-color: #f9f9f9;">
                                                    <td colspan="2" style="border-top: none;">
                                                        <div class="d-flex justify-content-between" style="font-size: 0.85rem; color: #555;">
                                                            <span>Price</span>
                                                            <span><?php echo htmlspecialchars($symbol) . number_format($package_base_price, 2); ?></span>
                                                        </div>
                                                        <div class="d-flex justify-content-between" style="font-size: 0.85rem; color: #555;">
                                                            <span>Taxs (<?php echo number_format(floatval($tax_rate ?? 0), 2); ?>%)</span>
                                                            <span><?php echo htmlspecialchars($symbol) . number_format($package_tax_amount, 2); ?></span>
                                                        </div>
                                                    </td>
                                                </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?> -->
                                    <?php if (!empty($package_details)): ?>
                                        <?php foreach ($package_details as $pkg_index => $package): ?>

                                            <?php
                                                // ✅ Fetch individual GST rate for this package
                                                $package_tax_rate = 0;
                                                if (!empty($package['gst_id'])) {
                                                    $tax_stmt = $conn->prepare("SELECT rate FROM taxes WHERE id = ?");
                                                    $tax_stmt->bind_param("i", $package['gst_id']);
                                                    $tax_stmt->execute();
                                                    $tax_res = $tax_stmt->get_result();
                                                    if ($tax_res && $tax_res->num_rows > 0) {
                                                        $tax_row = $tax_res->fetch_assoc();
                                                        $package_tax_rate = floatval($tax_row['rate']);
                                                    }
                                                    $tax_stmt->close();
                                                }

                                                $package_base_price   = floatval($package['price'] ?? 0);
                                                $package_tax_amount   = round($package_base_price * ($package_tax_rate / 100), 2);
                                                $package_total_amount = round($package_base_price + $package_tax_amount, 2);
                                                $package_breakdown_id = 'packageBreakdown-' . $pkg_index;
                                            ?>

                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center" style="gap: 10px;">
                                                        <span><?php echo htmlspecialchars($package['name']); ?> (Package)</span>
                                                        <button type="button" class="btn btn-link p-0 border-0 plan-breakdown-toggle"
                                                            data-target="#<?php echo $package_breakdown_id; ?>" data-display="table-row"
                                                            aria-expanded="false" style="color: inherit;">
                                                            <i class="fas fa-chevron-down"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                                <td class="text-end"><?php echo htmlspecialchars($symbol) . number_format($package_total_amount, 2); ?></td>
                                            </tr>

                                            <tr id="<?php echo $package_breakdown_id; ?>" class="plan-breakdown-details"
                                                style="display: none; background-color: #f9f9f9;">
                                                <td colspan="2" style="border-top: none;">
                                                    <div class="d-flex justify-content-between" style="font-size: 0.85rem; color: #555;">
                                                        <span>Price</span>
                                                        <span><?php echo htmlspecialchars($symbol) . number_format($package_base_price, 2); ?></span>
                                                    </div>
                                                    <div class="d-flex justify-content-between" style="font-size: 0.85rem; color: #555;">
                                                        <span>Tax (<?php echo number_format($package_tax_rate, 2); ?>%)</span>
                                                        <span><?php echo htmlspecialchars($symbol) . number_format($package_tax_amount, 2); ?></span>
                                                    </div>
                                                </td>
                                            </tr>

                                        <?php endforeach; ?>
                                    <?php endif; ?>

                                    <!-- Products -->
                                    <!-- <?php if (!empty($product_details)): ?>
                                        <?php foreach ($product_details as $product): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($product['name']); ?> (Product)</td>
                                                    <td class="text-end"><?php echo htmlspecialchars($symbol) . number_format($product['price'], 2); ?></td>
                                                </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?> -->
                                    <!-- Products -->
                                    <?php if (!empty($product_details)): ?>
                                        <?php foreach ($product_details as $prod_index => $product): ?>

                                            <?php
                                                // ✅ Fetch individual GST rate for product
                                                $product_tax_rate = 0;
                                                if (!empty($product['gst_id'])) {
                                                    $tax_stmt = $conn->prepare("SELECT rate FROM taxes WHERE id = ?");
                                                    $tax_stmt->bind_param("i", $product['gst_id']);
                                                    $tax_stmt->execute();
                                                    $tax_res = $tax_stmt->get_result();
                                                    if ($tax_res && $tax_res->num_rows > 0) {
                                                        $tax_row = $tax_res->fetch_assoc();
                                                        $product_tax_rate = floatval($tax_row['rate']);
                                                    }
                                                    $tax_stmt->close();
                                                }

                                                $product_base_price   = floatval($product['price']);
                                                $product_tax_amount   = round($product_base_price * ($product_tax_rate / 100), 2);
                                                $product_total_amount = round($product_base_price + $product_tax_amount, 2);
                                                $product_breakdown_id = 'productBreakdown-' . $prod_index;
                                            ?>

                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center" style="gap: 10px;">
                                                        <span><?php echo htmlspecialchars($product['name']); ?> (Product)</span>
                                                        <button type="button" class="btn btn-link p-0 border-0 plan-breakdown-toggle"
                                                                data-target="#<?php echo $product_breakdown_id; ?>" data-display="table-row"
                                                                aria-expanded="false" style="color: inherit;">
                                                            <i class="fas fa-chevron-down"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                                <td class="text-end"><?php echo htmlspecialchars($symbol) . number_format($product_total_amount, 2); ?></td>
                                            </tr>

                                            <tr id="<?php echo $product_breakdown_id; ?>" class="plan-breakdown-details"
                                                style="display: none; background-color: #f9f9f9;">
                                                <td colspan="2" style="border-top: none;">
                                                    <div class="d-flex justify-content-between" style="font-size: 0.85rem; color: #555;">
                                                        <span>Price</span>
                                                        <span><?php echo htmlspecialchars($symbol) . number_format($product_base_price, 2); ?></span>
                                                    </div>
                                                    <div class="d-flex justify-content-between" style="font-size: 0.85rem; color: #555;">
                                                        <span>Tax (<?php echo number_format($product_tax_rate, 2); ?>%)</span>
                                                        <span><?php echo htmlspecialchars($symbol) . number_format($product_tax_amount, 2); ?></span>
                                                    </div>
                                                </td>
                                            </tr>

                                        <?php endforeach; ?>
                                    <?php endif; ?>

                                    <!-- Add-On Services -->
                                    <!-- <?php if (!empty($addon_details)): ?>
                                        <?php foreach ($addon_details as $addon_index => $addon): ?>
                                                <?php
                                                    $addon_base_price     = floatval($addon['price'] ?? 0);
                                                    $addon_tax_amount     = round($addon_base_price * (floatval($tax_rate ?? 0) / 100), 2);
                                                    $addon_total_amount   = round($addon_base_price + $addon_tax_amount, 2);
                                                    $addon_breakdown_id   = 'addonBreakdown-' . $addon_index;
                                                ?>
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center" style="gap: 10px;">
                                                            <span><?php echo htmlspecialchars($addon['name']); ?> (Service)</span>
                                                            <button type="button" class="btn btn-link p-0 border-0 plan-breakdown-toggle" data-target="#<?php echo $addon_breakdown_id; ?>" data-display="table-row" aria-expanded="false" style="color: inherit;">
                                                                <i class="fas fa-chevron-down"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                    <td class="text-end"><?php echo htmlspecialchars($symbol) . number_format($addon_total_amount, 2); ?></td>
                                                </tr>
                                                <tr id="<?php echo $addon_breakdown_id; ?>" class="plan-breakdown-details" style="display: none; background-color: #f9f9f9;">
                                                    <td colspan="2" style="border-top: none;">
                                                        <div class="d-flex justify-content-between" style="font-size: 0.85rem; color: #555;">
                                                            <span>Price</span>
                                                            <span><?php echo htmlspecialchars($symbol) . number_format($addon_base_price, 2); ?></span>
                                                        </div>
                                                        <div class="d-flex justify-content-between" style="font-size: 0.85rem; color: #555;">
                                                            <span>Tax (<?php echo number_format(floatval($tax_rate ?? 0), 2); ?>%)</span>
                                                            <span><?php echo htmlspecialchars($symbol) . number_format($addon_tax_amount, 2); ?></span>
                                                        </div>
                                                    </td>
                                                </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?> -->
                                    <?php if (!empty($addon_details)): ?>
                                        <?php foreach ($addon_details as $addon_index => $addon): ?>

                                            <?php
                                                // Fetch individual tax rate for this addon
                                                $addon_tax_rate = 0;
                                                if (!empty($addon['gst_id'])) {
                                                    $tax_stmt = $conn->prepare("SELECT rate FROM taxes WHERE id = ?");
                                                    $tax_stmt->bind_param("i", $addon['gst_id']);
                                                    $tax_stmt->execute();
                                                    $tax_res = $tax_stmt->get_result();
                                                    if ($tax_res && $tax_res->num_rows > 0) {
                                                        $tax_row = $tax_res->fetch_assoc();
                                                        $addon_tax_rate = floatval($tax_row['rate']);
                                                    }
                                                    $tax_stmt->close();
                                                }

                                                $addon_base_price   = floatval($addon['price']);
                                                $addon_tax_amount   = round($addon_base_price * ($addon_tax_rate / 100), 2);
                                                $addon_total_amount = round($addon_base_price + $addon_tax_amount, 2);
                                                $addon_breakdown_id = 'addonBreakdown-' . $addon_index;
                                            ?>

                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center" style="gap: 10px;">
                                                        <span><?php echo htmlspecialchars($addon['name']); ?> (Service)</span>
                                                        <button type="button" class="btn btn-link p-0 border-0 plan-breakdown-toggle"
                                                                data-target="#<?php echo $addon_breakdown_id; ?>" data-display="table-row" aria-expanded="false"
                                                                style="color: inherit;">
                                                            <i class="fas fa-chevron-down"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                                <td class="text-end"><?php echo htmlspecialchars($symbol) . number_format($addon_total_amount, 2); ?></td>
                                            </tr>

                                            <tr id="<?php echo $addon_breakdown_id; ?>" class="plan-breakdown-details"
                                                style="display: none; background-color: #f9f9f9;">
                                                <td colspan="2" style="border-top: none;">
                                                    <div class="d-flex justify-content-between" style="font-size: 0.85rem; color: #555;">
                                                        <span>Price</span>
                                                        <span><?php echo htmlspecialchars($symbol) . number_format($addon_base_price, 2); ?></span>
                                                    </div>
                                                    <div class="d-flex justify-content-between" style="font-size: 0.85rem; color: #555;">
                                                        <span>Tax (<?php echo number_format($addon_tax_rate, 2); ?>%)</span>
                                                        <span><?php echo htmlspecialchars($symbol) . number_format($addon_tax_amount, 2); ?></span>
                                                    </div>
                                                </td>
                                            </tr>

                                        <?php endforeach; ?>
                                    <?php endif; ?>

                                    <?php if($hostinger_balance !="") { ?>
                                    <tr>
                                        <td>Exisitng Plan Balance</td>
                                        <td class="text-end"><?php echo htmlspecialchars($symbol) . number_format($hostinger_balance, 2); ?></td>
                                    </tr>
                                    <?php } ?>
                                    <!-- Tax (GST 18%) -->
                                    <!-- <tr>
                                        <td>Tax (GST 18%)</td>
                                        <td class="text-end"><?php echo htmlspecialchars($symbol) . number_format($gst, 2); ?></td>
                                    </tr> -->

                                    <!-- Tax (Dynamic from taxes table) -->
                                    <!-- <tr>
                                        <td><?php echo htmlspecialchars($tax_name) . " (" . number_format($tax_rate, 2) . "%)"; ?></td>
                                        <td class="text-end gst-display"><?php echo htmlspecialchars($symbol) . number_format($gst, 2); ?></td>
                                    </tr> -->

                                    <!-- Estimated Total -->
                                    <!-- <tr>
                                        <td class="border-0 fw-semibold">Total</td>
                                        <?php
                                            // Recalculate total_price as sum of all tax-inclusive items
                                            $final_total = 0;

                                            // 1. Plan (already has GST)
                                            $plan_base = floatval($price ?? 0);
                                            $plan_gst  = round($plan_base * ($tax_rate / 100), 2);
                                            $final_total += round($plan_base + $plan_gst, 2);

                                            // 2. Packages
                                            if (!empty($package_details)) {
                                                foreach ($package_details as $pkg) {
                                                    $pkg_base = floatval($pkg['price'] ?? 0);
                                                    $pkg_gst  = round($pkg_base * ($tax_rate / 100), 2);
                                                    $final_total += round($pkg_base + $pkg_gst, 2);
                                                }
                                            }

                                            // 3. Add-ons
                                            if (!empty($addon_details)) {
                                                foreach ($addon_details as $addon) {
                                                    $add_base = floatval($addon['price'] ?? 0);
                                                    $add_gst  = round($add_base * ($tax_rate / 100), 2);
                                                    $final_total += round($add_base + $add_gst, 2);
                                                }
                                            }

                                            // 4. Products (no GST as per your logic)
                                            if (!empty($product_details)) {
                                                foreach ($product_details as $prod) {
                                                    $final_total += floatval($prod['price'] ?? 0);
                                                }
                                            }

                                            // Optional: Apply coupon discount (if already applied)
                                            $discount_amount = floatval($_POST['discount_amount'] ?? 0);
                                            $final_total = max(0, $final_total - $discount_amount);

                                            // Update hidden input so form submits correct value
                                            $total_price = round($final_total, 2);
                                        ?>
                                        <input type="hidden" name="total_price" value="<?php echo $total_price; ?>">
                                        <td class="border-0 text-end fw-semibold text-xl">
                                            <?php echo htmlspecialchars($symbol) . number_format($total_price, 2); ?>
                                        </td>
                                    </tr> -->
                                    <!-- For Renewal Total -->
                                    <?php
                                        // --- Renewal Case: Override plan GST with its own tax rate ---
                                        if (isset($_POST['renewal']) && $_POST['renewal'] == 1) {

                                            $renewal_gst_rate = $tax_rate; // fallback

                                            // Try POST first
                                            if (!empty($_POST['gst_id'])) {
                                                $gst_id_lookup = (int) $_POST['gst_id'];
                                            }
                                            // Try previously loaded gst_id variable
                                            elseif (!empty($gst_id)) {
                                                $gst_id_lookup = (int) $gst_id;
                                            }
                                            // Fallback: pull gst_id from websites -> plan (package/product)
                                            else {
                                                $gst_id_lookup = 0;
                                                $stmt = $conn->prepare("
                                                    SELECT 
                                                        COALESCE(p.gst_id, pr.gst) AS gst_id
                                                    FROM websites w
                                                    LEFT JOIN package p ON w.plan = p.id
                                                    LEFT JOIN products pr ON w.plan = pr.id
                                                    WHERE w.id = ?
                                                    LIMIT 1
                                                ");
                                                $stmt->bind_param("i", $id);
                                                $stmt->execute();
                                                $res = $stmt->get_result();
                                                if ($res && $res->num_rows > 0) {
                                                    $r = $res->fetch_assoc();
                                                    $gst_id_lookup = (int) $r['gst_id'];
                                                }
                                                $stmt->close();
                                            }

                                            // Fetch actual tax % from taxes table
                                            if (!empty($gst_id_lookup)) {
                                                $stmt = $conn->prepare("SELECT rate FROM taxes WHERE id = ?");
                                                $stmt->bind_param("i", $gst_id_lookup);
                                                $stmt->execute();
                                                $rs = $stmt->get_result();
                                                if ($rs && $rs->num_rows > 0) {
                                                    $row = $rs->fetch_assoc();
                                                    $renewal_gst_rate = floatval($row['rate']);
                                                }
                                                $stmt->close();
                                            }

                                            // Override plan GST rate ONLY for renewal
                                            $tax_rate = $renewal_gst_rate;
                                        }
                                    ?>
                                    <tr>
                                        <td class="border-0 fw-semibold">Total</td>
                                        <?php
                                            // Recalculate total_price as sum of all tax-inclusive items
                                            $final_total = 0;

                                            // 1. Plan (uses global tax rate)
                                            $plan_base = floatval($price ?? 0);
                                            $plan_gst  = round($plan_base * ($tax_rate / 100), 2);
                                            $final_total += round($plan_base + $plan_gst, 2);

                                            // 2. Packages (uses global tax rate)
                                            if (!empty($package_details)) {
                                                foreach ($package_details as $pkg) {
                                                    $pkg_base = floatval($pkg['price'] ?? 0);
                                                    // $pkg_gst  = round($pkg_base * ($tax_rate / 100), 2);
                                                    // $final_total += round($pkg_base + $pkg_gst, 2);
                                                    $package_tax_rate = 0;
                                                    if (!empty($pkg['gst_id'])) {
                                                        $tax_stmt = $conn->prepare("SELECT rate FROM taxes WHERE id = ?");
                                                        $tax_stmt->bind_param("i", $pkg['gst_id']);
                                                        $tax_stmt->execute();
                                                        $tax_res = $tax_stmt->get_result();
                                                        if ($tax_res && $tax_res->num_rows > 0) {
                                                            $tax_row = $tax_res->fetch_assoc();
                                                            $package_tax_rate = floatval($tax_row['rate']);
                                                        }
                                                        $tax_stmt->close();
                                                    }

                                                    $pkg_gst = round($pkg_base * ($package_tax_rate / 100), 2);
                                                    $final_total += round($pkg_base + $pkg_gst, 2);

                                                }
                                            }

                                            // 3. Add-ons (✅ now uses each add-on's own GST rate)
                                            if (!empty($addon_details)) {
                                                foreach ($addon_details as $addon) {

                                                    $addon_tax_rate = 0;
                                                    if (!empty($addon['gst_id'])) {
                                                        $tax_stmt = $conn->prepare("SELECT rate FROM taxes WHERE id = ?");
                                                        $tax_stmt->bind_param("i", $addon['gst_id']);
                                                        $tax_stmt->execute();
                                                        $tax_res = $tax_stmt->get_result();
                                                        if ($tax_res && $tax_res->num_rows > 0) {
                                                            $tax_row = $tax_res->fetch_assoc();
                                                            $addon_tax_rate = floatval($tax_row['rate']);
                                                        }
                                                        $tax_stmt->close();
                                                    }

                                                    $add_base = floatval($addon['price'] ?? 0);
                                                    $add_gst  = round($add_base * ($addon_tax_rate / 100), 2);
                                                    $final_total += round($add_base + $add_gst, 2);
                                                }
                                            }

                                            // // 4. Products (no GST)
                                            // if (!empty($product_details)) {
                                            //     foreach ($product_details as $prod) {
                                            //         $final_total += floatval($prod['price'] ?? 0);
                                            //     }
                                            // }
                                            // 4. Products (✅ now uses individual product GST)
                                            if (!empty($product_details)) {
                                                foreach ($product_details as $prod) {

                                                    $product_tax_rate = 0;
                                                    if (!empty($prod['gst_id'])) {
                                                        $tax_stmt = $conn->prepare("SELECT rate FROM taxes WHERE id = ?");
                                                        $tax_stmt->bind_param("i", $prod['gst_id']);
                                                        $tax_stmt->execute();
                                                        $tax_res = $tax_stmt->get_result();
                                                        if ($tax_res && $tax_res->num_rows > 0) {
                                                            $tax_row = $tax_res->fetch_assoc();
                                                            $product_tax_rate = floatval($tax_row['rate']);
                                                        }
                                                        $tax_stmt->close();
                                                    }

                                                    $prod_base = floatval($prod['price'] ?? 0);
                                                    $prod_gst  = round($prod_base * ($product_tax_rate / 100), 2);
                                                    $final_total += round($prod_base + $prod_gst, 2);
                                                }
                                            }

                                            // 5. Apply coupon discount (if exists)
                                            $discount_amount = floatval($_POST['discount_amount'] ?? 0);
                                            $final_total = max(0, $final_total - $discount_amount);

                                            // Final total value
                                            $total_price = round($final_total, 2);
                                        ?>

                                        <?php if($hostinger_balance != 0){ ?>
                                            <td class="border-0 text-end fw-semibold text-xl">
                                                <?php echo htmlspecialchars($symbol) . number_format($amount_to_pay, 2); ?>
                                            </td>
                                        <?php } else { ?>
                                            <td class="border-0 text-end fw-semibold text-xl">
                                                <?php echo htmlspecialchars($symbol) . number_format($total_price, 2); ?>
                                            </td>
                                        <?php } ?>
                                    </tr>

                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payment Method Card -->
            <div class="row">
                <div class="col-12 mt-3">
                    <div class="card h-100 radius-12">
                        <div class="card-header py-10 border-none card-shadow">
                            <h6 class="mb-0">Select Payment Mode</h6>
                        </div>
                        <div class="card-body p-16">
                            <!-- ==================== COUPON CODE SECTION ==================== -->
                            <!-- <div class="mb-3">
                                <label for="coupon_code" class="fw-medium mb-2">Coupon Code</label>
                                <div class="d-flex gap-2 align-items-center">
                                    <input type="text"
                                        id="coupon_code"
                                        name="coupon_code"
                                        class="form-control"
                                        placeholder="Enter coupon code"
                                        value="<?php echo isset($_POST['coupon_code']) ? htmlspecialchars($_POST['coupon_code']) : ''; ?>">

                                    <button type="button"
                                            id="apply_coupon_btn"
                                            class="btn custom-pay-btn px-4 lufera-bg">
                                        Apply
                                    </button>

                                    <button type="button"
                                            class="btn btn-outline-secondary"
                                            data-bs-toggle="modal"
                                            data-bs-target="#couponModal">
                                        View Coupons
                                    </button>
                                </div>

                                <input type="hidden" id="coupon_code_hidden" name="coupon_code"
                                    value="<?php echo isset($_POST['coupon_code']) ? htmlspecialchars($_POST['coupon_code']) : ''; ?>">
                                <input type="hidden" id="discount_amount" name="discount_amount" value="0.00">
                            </div> -->

                            <p class="text-muted fw-medium mb-3">How would you like to make the payment? <span class="text-danger-600">*</span></p>

                            <div class="d-flex flex-wrap gap-3 justify-content-start">
                                <?php
                                    $payments = [
                                        'Bank Transfer' => 'bank-transfer',
                                        'Direct Pay'    => 'direct-pay',
                                        'PayPal'        => 'paypal-button-container'
                                    ];
                                    foreach ($payments as $label => $target): ?>
                                        <div class="payment-option-box">
                                            <input
                                                type="radio"
                                                class="form-check-input m-0"
                                                name="pay_method"
                                                id="pay<?= $target ?>"
                                                value="<?= $label ?>"
                                                data-target="<?= $target ?>"                   
                                            >
                                            <label for="pay<?= $target ?>">
                                                <?= $label ?>
                                                <span class="icon-circle">
                                                    <i class="fas fa-chevron-down"></i>
                                                </span>
                                            </label>
                                        </div>
                                <?php endforeach; ?>                            
                            </div>
                            <?php
                                $sql = "SELECT * FROM bank_details LIMIT 1";
                                $result3 = $conn->query($sql);
                                if ($result3->num_rows > 0) {
                                    $row = $result3->fetch_assoc();
                                    $id = $row['id'];
                                    $bank_name = $row['bank_name'];
                                    $ac_name = $row['ac_name'];
                                    $ac_no = $row['ac_no'];
                                    $branch = $row['branch'];
                                    $ifsc_code = $row['ifsc_code'];
                                    $micr = $row['micr'];
                                    $swift_code = $row['swift_code'];
                                }
                            ?>
                            <div id="bank-transfer" class="payment-detail">
                                <div class="row mt-3">
                                    <div class="col-12">
                                        <div class="card h-100 radius-12">
                                        <div class="card-header py-10 border-none card-shadow">
                                            <h6 class="mb-0">Bank Transfer</h6>
                                            <p class="mb-0 text-muted">Order Summary includes discounts & taxes</p>
                                        </div>
                                        <div class="card-body p-16">
                                            <div class="fw-semibold my-3">Bank A/C Details:</div>
                                            <div class="row gy-4 align-items-start">
                                            <div class="col-lg-5 col-md-6">
                                                <div class="table-responsive">
                                                <table class="table table-bordered small mb-0">
                                                    <tbody>
                                                    <tr><td>Bank Name</td><td><?php echo htmlspecialchars($bank_name); ?></td></tr>
                                                    <tr><td>Account Name</td><td><?php echo htmlspecialchars($ac_name); ?></td></tr>
                                                    <tr><td>Account No</td><td><?php echo htmlspecialchars($ac_no); ?></td></tr>
                                                    <tr><td>Account Branch</td><td><?php echo htmlspecialchars($branch); ?></td></tr>
                                                    <tr><td>IFSC</td><td><?php echo htmlspecialchars($ifsc_code); ?></td></tr>
                                                    <tr><td>MICR</td><td><?php echo htmlspecialchars($micr); ?></td></tr>
                                                    <tr><td>Swift Code</td><td><?php echo htmlspecialchars($swift_code); ?></td></tr>
                                                    </tbody>
                                                </table>
                                                </div>
                                            </div>
                                            <div class="col-lg-7 col-md-6 d-flex align-items-start">
                                                <div class="ms-lg-5 pt-0 w-100">
                                                <p class="mb-1 fw-medium">Please let us know!</p>
                                                <p class="mb-1 text-muted small">Once you are done with your payment please let us know.</p>
                                                <p class="mb-3 text-muted small">Thank You.</p>
                                                </div>
                                            </div>
                                            </div>
                                        </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div id="direct-pay" class="payment-detail">
                                <div class="row mt-3">
                                        <div class="col-12">
                                            <div class="card h-100 radius-12">
                                            <div class="card-header py-10 border-none card-shadow">
                                                <h6 class="mb-0">Direct Pay</h6>
                                                <p class="mb-0 text-muted">Order Summary includes discounts & taxes</p>
                                            </div>
                                            <div class="card-body p-16">
                                                <div class="fw-semibold my-3">Thank You!</div>

                                                <div class="row gy-4 align-items-start">
                                                    <div class="col-lg-7 col-md-6 d-flex align-items-start">
                                                        <div class="ms-lg-5 pt-0 w-100">
                                                            <p class="mb-1 fw-medium">Please confirm your payment with one of our representative.</p>
                                                            <p class="mb-1 text-muted small">Contact your Relationship manager or call us at +91 -86-80808-204 or write to us at info@luferatech.com.</p>
                                                            <p class="mb-3 text-muted small">For futher support.</p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div id="paypal-button-container" class="payment-detail mt-3"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>    
    </form>
</div>

<!-- PayPal -->
<script src="https://www.paypal.com/sdk/js?client-id=<?= htmlspecialchars($_ENV['PAYPAL_CLIENT_ID']) ?>&currency=USD"></script>

<script>
    paypal.Buttons({
        createOrder: function(data, actions) {
            return actions.order.create({
                purchase_units: [{
                    amount: {
                        // value: '1.00'
                        value: '<?= $total_price ?>'
                    }
                }]
            });
        },
        onApprove: function(data, actions) {
            return actions.order.capture().then(function(details) {
                alert('Payment successful! Thank you, ' + details.payer.name.given_name + '!');
                console.log('Capture result', details, JSON.stringify(details, null, 2));
                // You can redirect or save order details here

                document.getElementById('continuePayBtn').click();
            });
        }
    }).render('#paypal-button-container');
</script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const payBtn = document.getElementById('continuePayBtn');
        const paypalContainer = document.getElementById('paypal-button-container');

        // Always hide PayPal section initially
        paypalContainer.style.display = 'none';

        // Listen for changes in payment method radio buttons
        document.querySelectorAll('input[name="pay_method"]').forEach(radio => {
            radio.addEventListener('change', function () {
                const selectedTarget = this.getAttribute('data-target');

                if (selectedTarget === 'paypal-button-container') {
                    // Hide continue button (inline style) and show PayPal section
                    payBtn.style.display = 'none';
                    paypalContainer.style.display = 'block';
                } else {
                    // Show continue button (inline style) and hide PayPal section
                    payBtn.style.display = 'inline-block';
                    paypalContainer.style.display = 'none';
                }
            });
        });
    });
</script>

<script>
    $('#updateForm').submit(function(e) {
        e.preventDefault();

        $.ajax({
            url: 'update.php',
            type: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                $('#result').html(response);
           },
            error: function(xhr) {
                $('#result').html("Error updating data.");
            }
        });
    });

</script>

<script>
    document.addEventListener('DOMContentLoaded', () => {
    const radios   = document.querySelectorAll('input[name="pay_method"]');
    const details  = document.querySelectorAll('.payment-detail');

    // convenience function
    function showDetail(id) {
        details.forEach(el => { el.style.display = 'none'; });
        const chosen = document.getElementById(id);
        if (chosen) { chosen.style.display = 'block'; }
    }

    // run once so the default-checked radio shows its panel on load
    const checked = document.querySelector('input[name="pay_method"]:checked');
    if (checked) { showDetail(checked.dataset.target); }

    // change handler
    radios.forEach(radio =>
        radio.addEventListener('change', e => showDetail(e.target.dataset.target))
    );
    });
</script>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        const btn = document.getElementById("continuePayBtn");

        btn.addEventListener("click", function (e) {
            const selected = document.querySelector('input[name="pay_method"]:checked');

            if (!selected) {
                e.preventDefault(); // Stop form submission

                Swal.fire({
                    icon: 'warning',
                    title: 'Select Payment Method',
                    // text: 'Please select a payment method before continuing.',
                    confirmButtonColor: '#fec700'
                });
            }
        });
    });

</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const symbol          = '<?php echo htmlspecialchars($symbol); ?>';
    const basePrice       = parseFloat(<?php echo json_encode(floatval($price ?? 0)); ?>) || 0;
    const addonTotal      = parseFloat(<?php echo json_encode(floatval($addon_total ?? 0)); ?>) || 0;
    const taxRate         = parseFloat(<?php echo json_encode(floatval($tax_rate ?? 0)); ?>) || 0;
    const originalTotal   = parseFloat(<?php echo json_encode(floatval($total_price ?? 0)); ?>) || 0;
    const originalGst     = parseFloat(<?php echo json_encode(floatval($gst ?? 0)); ?>) || 0;
    const totalCell       = document.querySelector('.plan-details-table tr:last-child td.text-end');
    const gstCell         = document.querySelector('.gst-display');
    const hiddenTotal     = document.querySelector('input[name="total_price"]');
    const hiddenDiscount  = document.getElementById('discount_amount');
    const couponInput     = document.getElementById('coupon_code');
    const couponHidden    = document.getElementById('coupon_code_hidden');
    const applyBtn        = document.getElementById('apply_coupon_btn');
    const tableBody       = document.querySelector('.plan-details-table tbody');
    document.querySelectorAll('.plan-breakdown-toggle').forEach(toggleBtn => {
        const targetSelector = toggleBtn.dataset.target;
        const displayMode    = toggleBtn.dataset.display || 'block';
        const breakdown      = document.querySelector(targetSelector);
        const icon           = toggleBtn.querySelector('i');

        toggleBtn.addEventListener('click', function () {
            if (!breakdown) {
                return;
            }

            const isHidden = window.getComputedStyle(breakdown).display === 'none';
            breakdown.style.display = isHidden ? displayMode : 'none';
            toggleBtn.setAttribute('aria-expanded', isHidden ? 'true' : 'false');

            if (icon) {
                icon.classList.toggle('fa-chevron-down', !isHidden);
                icon.classList.toggle('fa-chevron-up', isHidden);
            }
        });
    });

    /* --------------------------------------------------------------
       INSERT DISCOUNT ROW (same as before)
       -------------------------------------------------------------- */
    let gstRow = null;
    document.querySelectorAll('.plan-details-table tbody tr').forEach(tr => {
        const td = tr.querySelector('td');
        if (td && td.textContent.trim().startsWith('<?php echo $tax_name; ?>')) gstRow = tr;
    });
    let currentDiscountRow = null;

    function insertDiscountRow(code, amount) {
        if (currentDiscountRow) currentDiscountRow.remove();
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>Discount (${code})</td>
            <td class="text-end text-dark">- ${symbol}${amount.toFixed(2)}</td>
        `;
        if (gstRow) {
            gstRow.insertAdjacentElement('afterend', tr);
        } else {
            tableBody.prepend(tr);
        }
        currentDiscountRow = tr;
    }

    /* --------------------------------------------------------------
       APPLY COUPON BUTTON
       -------------------------------------------------------------- */
    applyBtn.addEventListener('click', function () {
        const code = couponInput.value.trim().toUpperCase();
        if (!code) {
            Swal.fire({icon:'warning',title:'Enter a code',confirmButtonColor:'#fec700'});
            return;
        }

        /* ---- SEND BOTH coupon_code AND the plan id ---- */
        const formData = new URLSearchParams();
        formData.append('coupon_code', code);
        const planId = document.querySelector('input[name="id"]')?.value || <?php echo json_encode($id ?? null); ?>;
        if (planId) {
            formData.append('id', planId);
        }

        fetch('validate_coupon.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            if (!data.valid) {
                Swal.fire({icon:'error',title:'Invalid Coupon',text:data.message,confirmButtonColor:'#d33'});
                return;
            }

            const discountRaw  = parseFloat(data.discount) || 0;
            const discountType = (data.type || '').toLowerCase();

            // Calculate base amount (price + addons) before GST
            const baseAmount = (basePrice || 0) + (addonTotal || 0);

            // Calculate discount on base amount
            let discount = 0;
            if (discountType.includes('percentage')) {
                discount = baseAmount * (discountRaw / 100);
            } else {
                discount = discountRaw || 0;
            }

            // Apply discount to base amount
            const discountedBaseAmount = Math.max(0, baseAmount - discount);

            // Calculate GST on discounted amount
            const newGst = discountedBaseAmount * ((taxRate || 0) / 100);

            // Calculate new total
            const newTotal = discountedBaseAmount + newGst;

            // Validate all values are numbers
            if (isNaN(discount) || isNaN(newGst) || isNaN(newTotal)) {
                Swal.fire({icon:'error',title:'Calculation Error',text:'Invalid values detected. Please refresh the page.',confirmButtonColor:'#d33'});
                return;
            }

            couponHidden.value = code;
            hiddenDiscount.value = discount.toFixed(2);
            insertDiscountRow(code, discount);

            // Update GST display
            if (gstCell) {
                gstCell.textContent = `${symbol}${newGst.toFixed(2)}`;
            }

            // Update total display
            if (totalCell) {
                totalCell.textContent = `${symbol}${newTotal.toFixed(2)}`;
            }
            if (hiddenTotal) {
                hiddenTotal.value = newTotal.toFixed(2);
            }

            Swal.fire({
                icon: 'success',
                title: 'Coupon Applied',
                html: `Discount <b>${symbol}${discount.toFixed(2)}</b> applied!<br>New total: <b>${symbol}${newTotal.toFixed(2)}</b>`,
                confirmButtonColor: '#fec700',
                timer: 3000,
                timerProgressBar: true
            });
        })
        .catch(() => {
            Swal.fire({icon:'error',title:'Server Error',text:'Could not validate coupon.',confirmButtonColor:'#d33'});
        });
    });

    /* --------------------------------------------------------------
       RE-APPLY COUPON THAT WAS ALREADY POSTED (page reload)
       -------------------------------------------------------------- */
    const initDiscount = parseFloat(hiddenDiscount?.value) || 0;
    if (initDiscount > 0) {
        const initCode = couponHidden?.value || '';
        insertDiscountRow(initCode, initDiscount);
        
        // Calculate base amount (price + addons) before GST
        const baseAmount = (basePrice || 0) + (addonTotal || 0);
        
        // Apply discount to base amount
        const discountedBaseAmount = Math.max(0, baseAmount - initDiscount);
        
        // Calculate GST on discounted amount
        const newGst = discountedBaseAmount * ((taxRate || 0) / 100);
        
        // Calculate new total
        const initTotal = discountedBaseAmount + newGst;
        
        // Validate all values are numbers
        if (!isNaN(newGst) && !isNaN(initTotal)) {
            // Update GST display
            if (gstCell) {
                gstCell.textContent = `${symbol}${newGst.toFixed(2)}`;
            }
            
            // Update total display
            if (totalCell) {
                totalCell.textContent = `${symbol}${initTotal.toFixed(2)}`;
            }
            if (hiddenTotal) {
                hiddenTotal.value = initTotal.toFixed(2);
            }
        }
    }
});
</script>

<?php include './partials/layouts/layoutBottom.php' ?>