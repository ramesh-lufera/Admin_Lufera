<?php
header('Content-Type: application/json');
include './partials/connection.php';

if (!isset($_GET['package_id']) || !isset($_GET['duration'])) {
    echo json_encode(["ok" => false, "error" => "Missing parameters"]);
    exit;
}

$package_id = intval($_GET['package_id']);
$duration   = trim($_GET['duration']);

// ---------------------------------------------------------
// 1. FETCH PRICE AND GST ID
// ---------------------------------------------------------
$sql = "
    SELECT d.price, d.duration, p.gst_id 
    FROM durations d
    INNER JOIN package p ON p.id = d.package_id
    WHERE d.package_id = ? AND d.duration = ?
    LIMIT 1
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("is", $package_id, $duration);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows == 0) {
    echo json_encode(["ok" => false, "error" => "Package not found"]);
    exit;
}

$row = $res->fetch_assoc();
$price   = floatval($row['price']);
$gst_id  = $row['gst_id'];

// ---------------------------------------------------------
// 2. GET GST RATE FROM taxes TABLE
// ---------------------------------------------------------
$rate = 0.0;

if (!empty($gst_id)) {
    $stmt2 = $conn->prepare("SELECT rate FROM taxes WHERE id = ? LIMIT 1");
    $stmt2->bind_param("i", $gst_id);
    $stmt2->execute();
    $taxRes = $stmt2->get_result();
    
    if ($taxRes->num_rows > 0) {
        $rate = floatval($taxRes->fetch_assoc()['rate']);
    }
}

// ---------------------------------------------------------
// 3. CALCULATE TAXES
// ---------------------------------------------------------
$gst_amount  = round($price * ($rate / 100), 2);
$total_price = round($price + $gst_amount, 2);

// ---------------------------------------------------------
// 4. RETURN JSON IN THE FORMAT YOUR JS USES
// ---------------------------------------------------------

echo json_encode([
    "ok"        => true,
    "price"     => $price,          // base price (for breakdown)
    "gst"       => $gst_amount,     // tax amount
    "tax_rate"  => $rate,           // % number
    "total"     => $total_price     // base + gst
], JSON_UNESCAPED_UNICODE);

exit;
?>
