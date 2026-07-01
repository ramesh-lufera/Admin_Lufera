<?php include './partials/layouts/layoutTop.php' ?>

<?php
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
    $id = 0;
    $prefix = $series = $suffix = $invoice_logo = "";
    
    // Fetch existing data (assuming only one record)
    $sql = "SELECT * FROM invoice LIMIT 1";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $id = $row['id'];
        $prefix = $row['prefix'];
        $series = $row['series'];
        $suffix = $row['suffix'];
        $invoice_logo = $row['invoice_logo'];
    }
    
    // Handle form submission
    if ($_SERVER["REQUEST_METHOD"] == "POST") {

        $prefix = trim($_POST['prefix']);
        $suffix = trim($_POST['suffix']);
        $series = trim($_POST['series']);

        // Concatenate invoice number
        $created_at = date('Y-m-d H:i:s');
        $created_by = $loggedInUserId;
        // Upload Logo
        $invoice_logo = "";
    
        if(isset($_FILES['invoice_logo']) && $_FILES['invoice_logo']['error'] == 0){
    
            $uploadDir = "uploads/invoice/";
    
            if(!is_dir($uploadDir)){
                mkdir($uploadDir, 0777, true);
            }
    
            $fileExt = strtolower(pathinfo($_FILES['invoice_logo']['name'], PATHINFO_EXTENSION));
    
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    
            if(in_array($fileExt, $allowed)){
    
                $fileName = time() . "_" . uniqid() . "." . $fileExt;
    
                $targetFile = $uploadDir . $fileName;
    
                if(move_uploaded_file($_FILES['invoice_logo']['tmp_name'], $targetFile)){
                    $invoice_logo = $fileName; // Save only filename
                }
            }
        } else {
            // Keep old image while updating
            $invoice_logo = $row['invoice_logo'] ?? '';
        }
    
        if ($id > 0) {
    
            $update_sql = "UPDATE invoice SET
                prefix = '$prefix',
                series = '$series',
                suffix = '$suffix',
                invoice_logo = '$invoice_logo'
               WHERE id = $id";
    
            if ($conn->query($update_sql) === TRUE) {
    
                logActivity(
                    $conn,
                    $loggedInUserId,
                    "Invoice Details",
                    "Invoice Details Updated"
                );
    
                echo "<script>
                    Swal.fire({
                        title:'Success!',
                        text:'Invoice details updated successfully.',
                        confirmButtonText:'OK'
                    }).then(() => {
                        window.location.href = window.location.pathname;
                    });
                </script>";
            }
    
        } else {
    
            $insert_sql = "INSERT INTO invoice
            (prefix, series, suffix, invoice_logo, created_at, created_by)
            VALUES
            ('$prefix', '$series', '$suffix', '$invoice_logo', '$created_at', '$created_by')";
    
            if ($conn->query($insert_sql) === TRUE) {
    
                logActivity(
                    $conn,
                    $loggedInUserId,
                    'Invoice Details',
                    'Invoice Details Created'
                );
    
                echo "<script>
                    Swal.fire({
                        title:'Success!',
                        text:'Invoice details saved successfully.',
                        confirmButtonText:'OK'
                    }).then(() => {
                        window.location.href = window.location.pathname;
                    });
                </script>";
            }
        }
    }
?>

<div class="dashboard-main-body">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
        <a class="cursor-pointer fw-bold" onclick="history.back()"><span class="fa fa-arrow-left"></span>&nbsp; Back</a> 
        <h6 class="fw-semibold mb-0 m-auto">Invoice Details</h6>
        <a class="cursor-pointer fw-bold visibility-hidden" onclick="history.back()"><span class="fa fa-arrow-left"></span>&nbsp; Back</a> 
    </div>

    <div class="card h-100 p-0 radius-12 overflow-hidden">
        <div class="card-body p-40">
            <form method="POST" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="mb-20">
                            <label class="form-label fw-semibold text-primary-light text-sm mb-8">Custom Invoice Number</label>
                            <div class="row">
                                <div class="col-lg-4">
                                    <input type="text" class="form-control radius-8" name="prefix"
                                        value="<?= htmlspecialchars($prefix ?? '') ?>"
                                        placeholder="Prefix" required>
                                </div>

                                <div class="col-lg-4">
                                    <input type="text" class="form-control radius-8" name="series" value="<?= htmlspecialchars($series ?? '') ?>" placeholder="Series" inputmode="numeric" oninput="this.value=this.value.replace(/[^0-9]/g,'')" required>
                                </div>

                                <div class="col-lg-4">
                                    <input type="text" class="form-control radius-8" name="suffix" value="<?= htmlspecialchars($suffix ?? '') ?>" placeholder="Suffix" required>
                                </div>
                            </div>                           
                        </div>
                   
                        <div class="mb-20">
                            <label class="form-label fw-semibold text-primary-light text-sm mb-8">
                                Invoice Logo
                            </label>

                            <input type="file" class="form-control radius-8" name="invoice_logo">

                            <?php if (!empty($invoice_logo)) { ?>
                                <div class="mt-2">
                                    <img src="uploads/invoice/<?= htmlspecialchars($invoice_logo) ?>" alt="Invoice Logo" style="max-height:120px; border:1px solid #ddd; padding:5px;">
                                </div>
                            <?php } ?>
                        </div>
                        
                        <div class="d-flex align-items-center justify-content-center gap-3 mt-24">
                            <button type="submit" class="lufera-bg bg-hover-warning-400 text-white text-md px-56 py-11 radius-8 m-auto d-block">
                                Update
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include './partials/layouts/layoutBottom.php' ?>