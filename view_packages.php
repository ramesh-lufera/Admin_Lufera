<!-- meta tags and other links -->
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<title>Packages</title>
<style>
    /* Styling for disabled button to appear blurred */
    .disabled {
        pointer-events: none;  /* Prevents clicking */
        opacity: 0.5;  /* Makes the button appear blurred */
    }
</style>
</head>

<?php 
    include './partials/layouts/layoutTop.php';

    $Id = $_SESSION['user_id'];
    // Join package and durations tables
    $query = "SELECT p.id AS package_id, p.package_name, p.title, p.subtitle, p.is_active, p.cat_id, p.template, d.id AS duration_id, d.duration, d.price, d.preview_price 
              FROM package p 
              LEFT JOIN durations d ON p.id = d.package_id 
              WHERE p.is_deleted = 0 
              ORDER BY p.created_at DESC, d.created_at DESC";
    $result = mysqli_query($conn, $query);

    // Get active symbol
    $result1 = $conn->query("SELECT symbol FROM currencies WHERE is_active = 1 LIMIT 1");
    $symbol = "$"; // default
    if ($row1 = $result1->fetch_assoc()) {
        $symbol = $row1['symbol'];
    }
?>

<body>
    <div class="dashboard-main-body">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
            <a class="cursor-pointer fw-bold" onclick="history.back()"><span class="fa fa-arrow-left"></span>&nbsp; Back</a> 
            <h6 class="fw-semibold mb-0">Packages</h6>
            <a data-bs-toggle="modal" 
            data-bs-target="#add-package-modal" class="btn lufera-bg text-white text-sm btn-sm px-12 py-12 radius-8 d-flex align-items-center gap-2" >
                <iconify-icon icon="ic:baseline-plus" class="icon text-xl line-height-1"></iconify-icon>
                Add New Package
            </a>
        </div>
        <div class="card">
            <div class="card-body">
                <div class="table-responsive scroll-sm">
                    <table class="table bordered-table mb-0" id="productPackageTable">
                        <thead>
                            <tr>
                                <th scope="col">Name</th>
                                <th scope="col" class="text-center">Title</th>
                                <th scope="col" class="text-center">Subtitle</th>
                                <th scope="col" class="text-center">Price</th>
                                <th scope="col" class="text-center">Duration</th>
                                <th scope="col" class="text-center">Status</th>
                                <th scope="col" class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <div class="fw-medium">
                                        <?= htmlspecialchars($row['package_name']) ?>
                                    </div>
                                </td>
                                <td class="text-center"><?= htmlspecialchars($row['title']) ?></td>
                                <td class="text-center"><?= htmlspecialchars($row['subtitle']) ?></td>
                                <td class="text-center" id="currency-symbol-display">
                                    <?= htmlspecialchars($symbol) ?> <?= number_format($row['price'] ?? 0, 2) ?>
                                </td>
                                <td class="text-center"><?= htmlspecialchars($row['duration'] ?? '-') ?></td>
                                <td class="text-center">
                                    <button class="toggle-status btn btn-sm <?= $row['is_active'] ? 'btn-success' : 'btn-secondary' ?>" 
                                            data-id="<?= $row['package_id'] ?>" 
                                            data-status="<?= $row['is_active'] ?>">
                                        <?= $row['is_active'] ? 'Active' : 'Inactive' ?>
                                    </button>
                                </td>
                                <td class="text-center">
                                    <a href="javascript:void(0)" 
                                    class="fa fa-edit w-40-px h-40-px bg-warning-focus text-warning-main rounded-circle d-inline-flex align-items-center justify-content-center edit-package-btn"
                                    data-id="<?= $row['package_id'] ?>"
                                    data-cat-id="<?= $row['cat_id'] ?>"
                                    data-module="<?= $row['template'] ?>">
                                    </a>
                                    <a data-id="<?= $row['package_id'] ?>" class="fa fa-trash-alt delete-package w-40-px h-40-px bg-danger-focus text-danger-main rounded-circle d-inline-flex align-items-center justify-content-center cursor-pointer">
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            <!-- <?//php else: ?>
                            <tr>
                                <td colspan="6" class="text-center">No packages found.</td>
                            </tr> -->
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
<div class="modal fade" id="add-package-modal" tabindex="-1" aria-labelledby="assignRoleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
        <form method="post" action="">
            <div class="modal-header">
                <h5 class="modal-title">Add New Package</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="form-group mb-8">
                    <label for="product_category" class="form-label">Category</label>
                    <select class="form-control" id="product_category" name="product_category" required>
                        <option value="">-- Select Category --</option>
                        <?php
                        $categories = $conn->query("SELECT cat_id, cat_name FROM categories WHERE cat_type = 'package' ORDER BY cat_name ASC");
                        while ($cat = $categories->fetch_assoc()) {
                            echo "<option value='" . $cat['cat_id'] . "'>" . htmlspecialchars($cat['cat_name']) . "</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="mb-8">
                    <label for="template" class="form-label">Module</label>
                    <select name="template" class="form-control" id="template" required>
                        <option value="">-- Select Module --</option>
                        <option value="website">Website</option>
                        <option value="marketing">Marketing</option>
                        <option value="visa">Visa</option>
                        <option value="website-onboarding">Website Onboarding</option>
                        <option value="marketing-onboarding">Marketing Onboarding</option>
                        <option value="domain-onboarding">Domain Onboarding</option>
                        <option value="email-onboarding">Email Onboarding</option>
                        <option value="mobile-app-onboarding">Mobile App Onboarding</option>
                    </select>
                </div>
                <!-- Type Radio Toggle -->
                <div class="form-group d-none">
                <label>Type</label>
                <div class="radio-group">
                    <input type="radio" id="type_package" name="product_type" value="Package" required checked>
                    <label for="type_package">Package</label>
                    <input type="radio" id="type_product" name="product_type" value="Product">
                    <label for="type_product">Product</label>
                </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn lufera-bg">Continue</button>
            </div>
        </form>
    </div>
  </div>
</div>

<div class="modal fade" id="edit-package-modal" tabindex="-1" aria-labelledby="editPackageModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
        <form method="post" action="edit-package.php" id="editPackageForm">
            <div class="modal-header">
                <h5 class="modal-title">Edit Package</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="id" id="edit_package_id">
                <div class="form-group mb-8">
                    <label for="edit_product_category" class="form-label">Category</label>
                    <select class="form-control" id="edit_product_category" name="product_category" required>
                        <option value="">-- Select Category --</option>
                        <?php
                        $categories = $conn->query("SELECT cat_id, cat_name FROM categories WHERE cat_type = 'package' ORDER BY cat_name ASC");
                        while ($cat = $categories->fetch_assoc()) {
                            echo "<option value='" . $cat['cat_id'] . "'>" . htmlspecialchars($cat['cat_name']) . "</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="mb-8">
                    <label for="edit_template" class="form-label">Module</label>
                    <select name="template" class="form-control" id="edit_template" required>
                        <option value="">-- Select Module --</option>
                        <option value="website">Website</option>
                        <option value="marketing">Marketing</option>
                        <option value="visa">Visa</option>
                        <option value="website-onboarding">Website Onboarding</option>
                        <option value="marketing-onboarding">Marketing Onboarding</option>
                        <option value="domain-onboarding">Domain Onboarding</option>
                        <option value="email-onboarding">Email Onboarding</option>
                        <option value="mobile-app-onboarding">Mobile App Onboarding</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn lufera-bg">Continue</button>
            </div>
        </form>
    </div>
  </div>
</div>

<script>
$(document).ready(function() {
    $('#productPackageTable').DataTable();

    // Toggle Active/Inactive with SweetAlert
    $(document).on('click', '.toggle-status', function() {
        let button = $(this);
        let id = button.data('id');
        let currentStatus = button.data('status');
        let newStatusText = currentStatus == 1 ? 'Inactive' : 'Active';

        Swal.fire({
            title: 'Are you sure?',
            text: `Change status to ${newStatusText}?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, change it!',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'package-handler.php',
                    type: 'POST',
                    data: { action: 'toggle_status', id: id, status: currentStatus },
                    success: function(response) {
                        if (response.success) {
                            if (currentStatus == 1) {
                                button.removeClass('btn-success').addClass('btn-secondary').text('Inactive').data('status', 0);
                            } else {
                                button.removeClass('btn-secondary').addClass('btn-success').text('Active').data('status', 1);
                            }
                            Swal.fire('Updated!', `Status changed to ${newStatusText}.`, 'success');
                        } else {
                            Swal.fire('Error!', 'Failed to update status.', 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('Error!', 'Something went wrong.', 'error');
                    }
                });
            }
        });
    });

    // Delete Product with SweetAlert
    $(document).on('click', '.delete-package', function() {
        let button = $(this);
        let id = button.data('id');

        Swal.fire({
            title: 'Are you sure?',
            text: "This package will be deleted (soft delete).",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'package-handler.php',
                    type: 'POST',
                    data: { action: 'delete_package', id: id },
                    success: function(response) {
                        if (response.success) {
                            button.closest('tr').fadeOut();
                            Swal.fire('Deleted!', 'The package has been deleted.', 'success');
                        } else {
                            Swal.fire('Error!', 'Failed to delete package.', 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('Error!', 'Something went wrong.', 'error');
                    }
                });
            }
        });
    });
});

$(document).on('click', '.edit-package-btn', function() {
    let id = $(this).data('id');
    let catId = $(this).data('cat-id');
    let module = $(this).data('module');

    // Fill modal fields
    $('#edit_package_id').val(id);
    $('#edit_product_category').val(catId);  // preselect category
    $('#edit_template').val(module);         // preselect module

    // Show modal
    $('#edit-package-modal').modal('show');
});
</script>

</body>
</html>

<?php include './partials/layouts/layoutBottom.php' ?>