<?php include './partials/layouts/layoutTop.php'; 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);?>
<style>
    .form-check {
        padding: 10px;
    }
    .form-check-label {
        margin: -2px 10px;
    }
    input[type=number] {
    -moz-appearance: textfield;
    }
    input::-webkit-outer-spin-button,
    input::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
    }
</style>

<div class="dashboard-main-body">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
        <a class="cursor-pointer fw-bold" onclick="history.back()"><span class="fa fa-arrow-left"></span>&nbsp; Back</a> 
        <h6 class="fw-semibold mb-0">Promotions</h6>
        <button type="button" class="add-role-btn btn lufera-bg text-white text-sm btn-sm px-12 py-12 radius-8 d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#exampleModal">
            <iconify-icon icon="ic:baseline-plus" class="icon text-xl line-height-1"></iconify-icon>
            Add New Promo
        </button>
    </div>

    <div class="card h-100 p-0 radius-12">
        <div class="card-body">
            <div class="table-responsive scroll-sm">
                <table class="table bordered-table mb-0" id="role-table">
                    <thead>
                        <tr>
                            <th scope="col">Promotion Name</th>
                            <th scope="col">Coupon Code</th>
                            <th scope="col">Discount</th>
                            <th scope="col">Type</th>
                            <th scope="col">Period</th>
                            <th scope="col">Applied to</th>
                            <th scope="col" class="text-center">Status</th>
                            <th scope="col" class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                            $service = "SELECT * FROM promotion"; 
                            $results = $conn->query($service);
                            if (mysqli_num_rows($results) > 0) {
                                while ($row = mysqli_fetch_assoc($results)) {
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['promo_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['coupon_code']); ?></td>
                            <td><?php echo htmlspecialchars($row['discount']); ?></td>
                            <td><?php echo htmlspecialchars($row['type']); ?></td>
                            <td><?php echo htmlspecialchars($row['start_date']); ?> - <?php echo htmlspecialchars($row['end_date']); ?></td>
                            <td><?php echo htmlspecialchars($row['apply_to']); ?></td>
                            <td class="text-center">
                                <?php 
                                    if($row['is_Active']) {
                                ?>
                                <span class="bg-success-focus text-success-600 border border-success-main px-24 py-4 radius-4 fw-medium text-sm" style="width: 120px">Active</span>
                                <?php 
                                    } else {
                                ?>
                                <span class="bg-danger-focus text-danger-600 border border-danger-main px-24 py-4 radius-4 fw-medium text-sm" style="width: 120px">In Active</span>
                                <?php } ?>
                            </td>
                            <td class="text-center">
                                <div class="d-flex align-items-center gap-10 justify-content-center">
                                    <button 
                                        type="button" 
                                        class="fa fa-edit edit-role-btn bg-success-focus text-success-600 bg-hover-success-200 fw-medium w-40-px h-40-px d-flex justify-content-center align-items-center rounded-circle"
                                        data-id="<?= $row['id'] ?>"
                                        data-bs-toggle="modal" 
                                        data-bs-target="#exampleModal">
                                    </button>
                                    <button 
                                        type="button" 
                                        class="fa fa-trash-alt remove-item-btn bg-danger-focus bg-hover-danger-200 text-danger-600 fw-medium w-40-px h-40-px d-flex justify-content-center align-items-center rounded-circle" 
                                        data-id="<?= $row['id'] ?>">
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php 
                                }
                            }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Start -->
<div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Add New Promotion</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="promotionForm">
                    <input type="hidden" id="promotionId" name="id">
                    <div class="mb-3">
                        <label for="promotionName" class="form-label">Promotion Name</label>
                        <input type="text" class="form-control" id="promotionName" name="promo_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="couponCode" class="form-label">Coupon Code</label>
                        <input type="text" class="form-control" id="couponCode" name="coupon_code" required>
                    </div>
                    <div class="mb-3">
                        <label for="promotionDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="promotionDescription" name="description" rows="4"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="promotionDiscount" class="form-label">Discount</label>
                        <input type="number" class="form-control" id="promotionDiscount" name="discount" onkeydown="return event.key !== 'e'" required maxlength="10">
                    </div>
                    <div class="mb-3">
                        <label for="promotionType" class="form-label">Type</label>
                        <select id="promotionType" name="type" class="form-control radius-8" required>
                            <option value="Percentage">Percentage</option>
                            <option value="Flat Amount">Flat Amount</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="start_date" class="form-label">Start Date</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" required>
                    </div>
                    <div class="mb-3">
                        <label for="end_date" class="form-label">End Date</label>
                        <input type="date" class="form-control" id="end_date" name="end_date">
                    </div>
                    <div class="mb-3">
    <label class="form-label">Applies To</label>
    <div class="d-flex flex-wrap">
        <div class="form-check me-3">
            <input class="form-check-input" type="checkbox" name="apply_to[]" value="Packages" id="apply_packages">
            <label class="form-check-label" for="apply_packages">Packages</label>
        </div>
        <div class="form-check me-3">
            <input class="form-check-input" type="checkbox" name="apply_to[]" value="Products" id="apply_products">
            <label class="form-check-label" for="apply_products">Products</label>
        </div>
        <div class="form-check me-3">
            <input class="form-check-input" type="checkbox" name="apply_to[]" value="Services" id="apply_services">
            <label class="form-check-label" for="apply_services">Services</label>
        </div>
    </div>
</div>

                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="isActive" name="is_Active" checked>
                        <label class="form-check-label" for="isActive">Active</label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" id="submitPromotion" class="btn lufera-bg">Save Promotion</button>
            </div>
        </div>
    </div>
</div>
<!-- Modal End -->
<script>
$(document).ready(function() {
    $('#role-table').DataTable();

    // ✅ Reset form on modal open
    $('[data-bs-target="#exampleModal"]').on('click', function() {
        $('#promotionForm')[0].reset();
        $('#promotionId').val('');
        $('#modalTitle').text('Add New Promotion');
        $('#submitPromotion').text('Save Promotion');
    });
});

// ✅ CRUD Script
document.addEventListener('DOMContentLoaded', function() {

    // EDIT Promotion
    document.querySelectorAll('.edit-role-btn').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            fetch(`promotion_crud.php?action=get&id=${id}`, {
                method: 'GET',
                headers: { 'Accept': 'application/json' }
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success' && data.data) {
                    document.getElementById('modalTitle').textContent = 'Edit Promotion';
                    document.getElementById('submitPromotion').textContent = 'Update Promotion';
                    document.getElementById('promotionId').value = data.data.id;
                    document.getElementById('promotionName').value = data.data.promo_name || '';
                    document.getElementById('promotionDescription').value = data.data.description || '';
                    document.getElementById('couponCode').value = data.data.coupon_code || '';
                    document.getElementById('promotionDiscount').value = data.data.discount || '';
                    document.getElementById('promotionType').value = data.data.type || '';
                    document.getElementById('start_date').value = data.data.start_date || '';
                    document.getElementById('end_date').value = data.data.end_date || '';
                    //document.getElementById('apply_to').value = data.data.apply_to || '';
                    document.getElementById('isActive').checked = data.data.is_Active == 1;
                    // Uncheck all first
document.querySelectorAll('input[name="apply_to[]"]').forEach(cb => cb.checked = false);

// If the database stores comma-separated applies (e.g. "Packages,Products"), split and check them
if (data.data.apply_to) {
    const applies = data.data.apply_to.split(',');
    applies.forEach(val => {
        const checkbox = document.querySelector(`input[name="apply_to[]"][value="${val.trim()}"]`);
        if (checkbox) checkbox.checked = true;
    });
}

                } else {
                    Swal.fire('Error', data.message || 'Failed to fetch promotion data.', 'error');
                }
            })
            .catch(err => {
                Swal.fire('Error', 'An error occurred while fetching promotion: ' + err.message, 'error');
            });
        });
    });

    // DELETE Promotion
    document.querySelectorAll('.remove-item-btn').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then(result => {
                if (result.isConfirmed) {
                    fetch('promotion_crud.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `action=delete&id=${id}`
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.status === 'success') {
                            Swal.fire('Deleted!', data.message, 'success').then(() => location.reload());
                        } else {
                            Swal.fire('Error', data.message, 'error');
                        }
                    })
                    .catch(err => Swal.fire('Error', 'An error occurred: ' + err.message, 'error'));
                }
            });
        });
    });

    // SUBMIT (Create / Update)
    document.getElementById('submitPromotion').addEventListener('click', function() {
        const form = document.getElementById('promotionForm');
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        const formData = new FormData(form);
const id = document.getElementById('promotionId').value;

// Collect checked "apply_to[]" checkboxes into a single string
const applies = Array.from(document.querySelectorAll('input[name="apply_to[]"]:checked'))
    .map(cb => cb.value)
    .join(',');

formData.append('apply_to', applies);
formData.append('action', id ? 'update' : 'create');

fetch('promotion_crud.php', {
    method: 'POST',
    body: new URLSearchParams(formData)
})

        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                Swal.fire('Success', data.message, 'success').then(() => location.reload());
            } else {
                Swal.fire('Error', data.message, 'error');
            }
        })
        .catch(err => {
            Swal.fire('Error', 'An error occurred while saving the promotion: ' + err.message, 'error');
        });
    });

});
</script>
<?php include './partials/layouts/layoutBottom.php' ?>