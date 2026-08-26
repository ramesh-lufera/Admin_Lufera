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
        <h6 class="fw-semibold mb-0">Add-on Service</h6>
        <button type="button" class="add-role-btn btn lufera-bg lufera-text text-sm btn-sm px-12 py-12 radius-8 d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#exampleModal">
            <iconify-icon icon="ic:baseline-plus" class="icon text-xl line-height-1"></iconify-icon>
            Add New Service
        </button>
    </div>

    <div class="card h-100 p-0 radius-12">
        <div class="card-body">
            <div class="table-responsive scroll-sm">
                <table class="table bordered-table mb-0" id="role-table">
                    <thead>
                        <tr>
                            <th scope="col">Service Name</th>
                            <th scope="col">Cost</th>
                            <th scope="col">Duration</th>
                            <th scope="col" class="text-center">Status</th>
                            <th scope="col" class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                            $service = "SELECT * FROM `add-on-service`"; 
                            $results = $conn->query($service);
                            if (mysqli_num_rows($results) > 0) {
                                while ($row = mysqli_fetch_assoc($results)) {
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['name']); ?></td>
                            <td><?php echo htmlspecialchars($row['cost']); ?></td>
                            <td><?php echo htmlspecialchars($row['duration']); ?></td>
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
                                    <button type="button" class="fa fa-edit edit-role-btn bg-success-focus text-success-600 bg-hover-success-200 fw-medium w-40-px h-40-px d-flex justify-content-center align-items-center rounded-circle" data-id="<?= $row['id'] ?>" data-bs-toggle="modal" data-bs-target="#exampleModal">
                                    </button>
                                    <button type="button" class="fa fa-trash-alt remove-item-btn bg-danger-focus bg-hover-danger-200 text-danger-600 fw-medium w-40-px h-40-px d-flex justify-content-center align-items-center rounded-circle" data-id="<?= $row['id'] ?>">
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
                <h5 class="modal-title" id="modalTitle">Add New Service</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="serviceForm">
                    <input type="hidden" id="serviceId" name="id">
                    <div class="mb-3">
                        <label for="serviceName" class="form-label">Service Name</label>
                        <input type="text" class="form-control" id="serviceName" name="name" maxlength="50" required>
                    </div>
                    <div class="mb-3">
                        <label for="serviceDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="serviceDescription" name="description" rows="4"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="serviceCost" class="form-label">Cost</label>
                        <input type="text" inputmode="numeric" maxlength="10" class="form-control" id="serviceCost" name="cost" oninput="this.value = this.value.replace(/[^0-9]/g, '')" required>
                    </div>
                    <div class="mb-3">
                        <label for="serviceDuration" class="form-label">Duration</label>
                        <!-- <input type="text" class="form-control" id="serviceDuration" name="duration" required> -->
                        <div class="d-flex gap-2">
                            <input type="number" name="duration_value" id="durationValue" class="form-control radius-8" required min="1" style="width: 50%;">
                            <select name="duration_unit" id="durationUnit" class="form-control radius-8" required style="width: 50%;">
                                <option value="day">Day</option>
                                <option value="days">Days</option>
                                <option value="month">Month</option>
                                <option value="months">Months</option>
                                <option value="year">Year</option>
                                <option value="years">Years</option>
                            </select>
                        </div>                               
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="isActive" name="isActive" checked>
                        <label class="form-check-label" for="isActive">Active</label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary lufera-text" data-bs-dismiss="modal">Close</button>
                <button type="button" id="submitService" class="btn lufera-bg lufera-text">Save Service</button>
            </div>
        </div>
    </div>
</div>
<!-- Modal End -->

<script>
$(document).ready(function() {
    $('#role-table').DataTable();
});
document.addEventListener('DOMContentLoaded', function() { 
    // Edit button click handler
    document.querySelectorAll('.edit-role-btn').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            console.log('Fetching service with ID:', id); // Debug log
            
            fetch(`service_crud.php?action=get&id=${id}`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json'
                }
            })
            .then(response => {
                console.log('Response status:', response.status); // Debug log
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.status === 'success' && data.data) {
                    document.getElementById('modalTitle').textContent = 'Edit Service';
                    document.getElementById('submitService').textContent = 'Update Service';
                    document.getElementById('serviceId').value = data.data.id;
                    document.getElementById('serviceName').value = data.data.name || '';
                    document.getElementById('serviceDescription').value = data.data.description || '';
                    document.getElementById('serviceCost').value = data.data.cost || '';

                    // ✅ Only set duration_value and duration_unit
                    document.querySelector('[name="duration_value"]').value = data.data.duration_value || '';
                    document.querySelector('[name="duration_unit"]').value = data.data.duration_unit || '';

                    document.getElementById('isActive').checked = data.data.is_Active == 1;
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message || 'Failed to fetch service data.',
                        confirmButtonColor: '#3085d6'
                    });
                }
            })

            .catch(error => {
                console.error('Fetch error:', error); // Debug log
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'An error occurred while fetching the service: ' + error.message,
                    confirmButtonColor: '#3085d6'
                });
            });
        });
    });

    // Delete button click handler
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
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('service_crud.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `action=delete&id=${id}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Deleted!',
                                text: data.message,
                                confirmButtonColor: '#3085d6'
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: data.message,
                                confirmButtonColor: '#3085d6'
                            });
                        }
                    })
                    .catch(error => {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'An error occurred while deleting the service.',
                            confirmButtonColor: '#3085d6'
                        });
                        console.error('Error:', error);
                    });
                }
            });
        });
    });

// Submit form handler
document.getElementById('submitService').addEventListener('click', function() {
    const form = document.getElementById('serviceForm');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    const formData = new FormData(form);

    // Concatenate duration value + unit into one string
    const durationValue = formData.get('duration_value');
    const durationUnit = formData.get('duration_unit');
    const duration = durationValue + ' ' + durationUnit;

    // Remove separate values and append combined one
    formData.delete('duration_value');
    formData.delete('duration_unit');
    formData.append('duration', duration);

    // Detect action
    const id = document.getElementById('serviceId').value;
    formData.append('action', id ? 'update' : 'create');

    fetch('service_crud.php', {
        method: 'POST',
        body: new URLSearchParams(formData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            Swal.fire({
                icon: 'success',
                title: 'Success',
                text: data.message,
                confirmButtonColor: '#3085d6'
            }).then(() => {
                location.reload();
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.message,
                confirmButtonColor: '#3085d6'
            });
        }
    })
    .catch(error => {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'An error occurred while saving the service: ' + error.message,
            confirmButtonColor: '#3085d6'
        });
        console.error('Error:', error);
    });
});

});

$(document).ready(function() {
    $('[data-bs-target="#exampleModal"]').on('click', function() {
        // Replace '#add-category-form' with your actual form's ID
        $('#serviceForm')[0].reset();
    });
});

document.addEventListener('DOMContentLoaded', function() {
    const durationInput = document.getElementById('durationValue');
    const durationSelect = document.getElementById('durationUnit');
    if (durationInput && durationSelect) {
        function updateUnitLabels() {
            const val = parseInt(durationInput.value) || 1;
            const currentSelected = durationSelect.value; // Keep current unit if possible            
            Array.from(durationSelect.options).forEach(option => {
                let baseWord = '';
                const optVal = option.value.toLowerCase();
                if (optVal.includes('day')) baseWord = 'day';
                else if (optVal.includes('month')) baseWord = 'month';
                else if (optVal.includes('year')) baseWord = 'year';
                if (baseWord) {
                    const targetWord = val > 1 ? baseWord + 's' : baseWord;
                    option.value = targetWord;
                    option.text = targetWord.charAt(0).toUpperCase() + targetWord.slice(1);
                }
            });
            // Try to re-select the correct unit base if value changed
            if (currentSelected) {
                const base = currentSelected.replace('s', '');
                const match = Array.from(durationSelect.options).find(opt => opt.value.startsWith(base) && (val > 1 ? opt.value.endsWith('s') : !opt.value.endsWith('s')));
                if (match) durationSelect.value = match.value;
            }
        }
        durationInput.addEventListener('input', updateUnitLabels);
        durationInput.addEventListener('change', updateUnitLabels);
        // Trigger update when editing an existing record
        document.querySelectorAll('.edit-role-btn').forEach(button => {
            button.addEventListener('click', function() {
                setTimeout(updateUnitLabels, 150); // Small delay to let AJAX populate fields first
            });
        });
    }
});
</script>

<?php include './partials/layouts/layoutBottom.php' ?>