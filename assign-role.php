<?php include './partials/layouts/layoutTop.php' ?>
<?php
$query = "SELECT 
    users.first_name, 
    users.last_name, 
    users.user_id,
    users.email, 
    users.id AS id,
    roles.name AS role_name
FROM 
    users 
JOIN 
    roles ON users.role = roles.id where users.role !=' 1'" ;

$result = mysqli_query($conn, $query); 
?>
<div class="dashboard-main-body">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
        <h6 class="fw-semibold mb-0">Role & Access</h6>
    </div>

    <div class="card h-100 p-0 radius-12">
        <div class="card-body p-24">
            <div class="table-responsive scroll-sm">
                <table class="table bordered-table sm-table mb-0">
                    <thead>
                        <tr>
                            <th scope="col">User ID</th>
                            <th scope="col">Name</th>
                            <th scope="col" class="text-center">Role</th>
                            <th scope="col" class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php while($row = mysqli_fetch_assoc($result)) { ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['user_id']); ?></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <!-- <img src="assets/images/user-list/user-list1.png" alt="" class="w-40-px h-40-px rounded-circle flex-shrink-0 me-12 overflow-hidden"> -->
                                    <div class="flex-grow-1">
                                        <span class="text-md mb-0 fw-normal text-secondary-light"><?php echo htmlspecialchars($row['first_name']); ?> <?php echo htmlspecialchars($row['last_name']); ?></span>
                                    </div>
                                </div>
                            </td>
                            <td class="text-center"><?php echo htmlspecialchars($row['role_name']); ?></td>
                            <td class="text-center">
                                <button 
                                    class="btn lufera-bg not-active px-18 py-11" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#assignRoleModal"
                                    data-userid="<?php echo htmlspecialchars($row['id']); ?>"
                                    data-firstname="<?php echo htmlspecialchars($row['first_name'].' '. $row['last_name']); ?>"
                                    data-role="<?php echo htmlspecialchars($row['role_name']); ?>"
                                >
                                    Assign Role
                                </button>
                            </td>

                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <!-- Assign Role Modal -->
<div class="modal fade" id="assignRoleModal" tabindex="-1" aria-labelledby="assignRoleModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title">Assign Role</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="modal_user_id">
            <div class="mb-3">
                <label for="modal_first_name" class="form-label">First Name</label>
                <input type="text" class="form-control" id="modal_first_name" readonly>
            </div>
            <div class="mb-3">
                <label for="modal_role" class="form-label">Role</label>
                <select class="form-select" id="modal_role" required>
                    <option value="">Select Role</option>
                    <?php
                    $rolesQuery = "SELECT id, name FROM roles WHERE isActive = 1";
                    $rolesResult = mysqli_query($conn, $rolesQuery);
                    while($role = mysqli_fetch_assoc($rolesResult)) {
                        echo '<option value="' . $role['id'] . '">' . htmlspecialchars($role['name']) . '</option>';
                    }
                    ?>
                </select>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" id="submitRoleChange" class="btn btn-primary">Update Role</button>
        </div>
    </div>
  </div>
</div>

</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var assignRoleModal = document.getElementById('assignRoleModal');

    // Fill modal on show
    assignRoleModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        var userId = button.getAttribute('data-userid');
        var firstName = button.getAttribute('data-firstname');

        document.getElementById('modal_user_id').value = userId;
        document.getElementById('modal_first_name').value = firstName;
    });

    // AJAX role update on button click
    document.getElementById('submitRoleChange').addEventListener('click', function () {
        var userId = document.getElementById('modal_user_id').value;
        var roleId = document.getElementById('modal_role').value;

        if (!roleId) {
            alert("Please select a role.");
            return;
        }

        fetch('update-role.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `user_id=${encodeURIComponent(userId)}&role_id=${encodeURIComponent(roleId)}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Role updated successfully',
                showConfirmButton: false,
                timer: 1500
            }).then(() => {
                location.reload(); // refresh to reflect changes
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Failed to update role',
            });
        }
        })
        .catch(error => {
            console.error("Error:", error);
            alert("An error occurred.");
        });
    });
});
</script>

<?php include './partials/layouts/layoutBottom.php' ?>