<?php
include './partials/connection.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$numericFields = ['price', 'gst', 'amount', 'balance_due', 'payment_made', 'subtotal', 
                  'discount_amount', 'addon_price', 'addon_gst', 'existing_balance'];

foreach ($data['fields'] as $field => $value) {
    if (in_array($field, $numericFields)) {
        $clean = preg_replace('/[^0-9.-]/', '', $value);
        $data['fields'][$field] = is_numeric($clean) ? (float)$clean : 0;
    }
}
$invoice_id = mysqli_real_escape_string($conn, $data['invoice_id'] ?? '');
$type       = $data['type'] ?? 'normal';
$fields     = $data['fields'] ?? [];

if (empty($invoice_id) || empty($fields)) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit;
}

$table = ($type === 'renewal') ? 'renewal_invoices' : 'orders';

$set = [];

// Map display fields to actual database columns
foreach ($fields as $field => $value) {
    $value = mysqli_real_escape_string($conn, $value);
    
    switch ($field) {
        case 'plan_display_name':
            $set[] = "`plan` = '$value'";           // or `plan_name` if you have that column
            break;
            
        case 'invoice_id':
            $set[] = "`invoice_id` = '$value'";
            break;
            
        case 'created_on':
            // Convert date format if needed (d/m/Y → Y-m-d)
            $date = DateTime::createFromFormat('d/m/Y', $value);
            if ($date) {
                $db_date = $date->format('Y-m-d');
                $set[] = "`created_on` = '$db_date'";
            }
            break;
            
        case 'payment_method':
        case 'status':
        case 'price':
        case 'gst':
        case 'amount':
        case 'payment_made':
        case 'balance_due':
            $set[] = "`$field` = '$value'";
            break;
            
        default:
            // Skip unknown fields
            break;
    }
}

if (empty($set)) {
    echo json_encode(['success' => false, 'message' => 'No valid fields to update']);
    exit;
}

$sql = "UPDATE `$table` SET " . implode(", ", $set) . " WHERE `invoice_id` = '$invoice_id'";

if (mysqli_query($conn, $sql)) {
    echo json_encode(['success' => true, 'message' => 'Invoice updated successfully']);
} else {
    echo json_encode(['success' => false, 'message' => mysqli_error($conn)]);
}
?>