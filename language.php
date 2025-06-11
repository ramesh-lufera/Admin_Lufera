<?php include './partials/layouts/layoutTop.php' ?>
<?php


$query = "Select * from language";

$result = mysqli_query($conn, $query); 
?>
        <div class="dashboard-main-body">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
                <h6 class="fw-semibold mb-0">Languages</h6>
                <button type="button" class="btn lufera-bg text-white text-sm btn-sm px-12 py-12 radius-8 d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#exampleModal">
                        <iconify-icon icon="ic:baseline-plus" class="icon text-xl line-height-1"></iconify-icon>
                        Add Languages
                    </button>
            </div>

            <div class="card h-100 p-0 radius-12">
                <!-- <div class="card-header border-bottom bg-base py-16 px-24 d-flex align-items-center flex-wrap gap-3 justify-content-between">
                    
                </div> -->
                <div class="card-body p-24">
                    <div class="table-responsive scroll-sm">
                        <table class="table bordered-table sm-table mb-0" id="lang-table">
                            <thead>
                                <tr>
                                    <th scope="col">Name</th>
                                    <th scope="col" class="text-center">Status</th>
                                    <th scope="col" class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php while($row = mysqli_fetch_assoc($result)) { ?>
                                <tr>
                                    <td><?php echo $row['language']; ?></td>
                                    <td class="text-center">
                                        <?php 
                                            if($row['status'] == true){
                                        ?>
                                        <span class="bg-success-focus text-success-600 border border-success-main px-24 py-4 radius-4 fw-medium text-sm" style="width: 120px">Active</span>
                                        <?php 
                                            }else{
                                        ?>
                                        <span class="bg-danger-focus text-danger-600 border border-danger-main px-24 py-4 radius-4 fw-medium text-sm" style="width: 120px">In Active</span>
                                        <?php } ?>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex align-items-center gap-10 justify-content-center">
                                            <!-- <button type="button" class="bg-success-100 text-success-600 bg-hover-success-200 fw-medium w-40-px h-40-px d-flex justify-content-center align-items-center rounded-circle" data-bs-toggle="modal" data-bs-target="#exampleModalEdit">
                                                <iconify-icon icon="lucide:edit" class="menu-icon"></iconify-icon>
                                            </button>
                                            <button type="button" class="remove-item-button bg-danger-focus bg-hover-danger-200 text-danger-600 fw-medium w-40-px h-40-px d-flex justify-content-center align-items-center rounded-circle">
                                                <iconify-icon icon="fluent:delete-24-regular" class="menu-icon"></iconify-icon>
                                            </button> -->
                                            <button 
                                                type="button" 
                                                class="edit-role-btn bg-success-focus text-success-600 bg-hover-success-200 fw-medium w-40-px h-40-px d-flex justify-content-center align-items-center rounded-circle"
                                                data-id="<?= $row['id'] ?>"
                                                data-language="<?= $row['language'] ?>"
                                                data-status="<?= $row['status'] ?>"
                                                data-bs-toggle="modal" 
                                                data-bs-target="#exampleModal">
                                                <iconify-icon icon="lucide:edit" class="menu-icon"></iconify-icon>
                                            </button>
                                            <button type="button" class="remove-item-btn bg-danger-focus bg-hover-danger-200 text-danger-600 fw-medium w-40-px h-40-px d-flex justify-content-center align-items-center rounded-circle" data-id="<?= $row['id'] ?>">
                                                <iconify-icon icon="fluent:delete-24-regular"></iconify-icon>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>


    <!-- Modal Add Currecny -->
    <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog modal-dialog-centered">
            <div class="modal-content radius-16 bg-base">
                <div class="modal-header py-16 px-24 border border-top-0 border-start-0 border-end-0">
                    <h1 class="modal-title fs-5" id="exampleModalLabel">Add New Language </h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-24">
                    <form method="post" id="roleForm">
                    <input type="hidden" name="role_id" id="role_id">
                        <div class="row">
                            <div class="col-6 mb-20">
                                <label for="language" class="form-label fw-semibold text-primary-light text-sm mb-8">Name </label>
                                <input type="text" class="form-control radius-8" id="language" name="language" required>
                            </div>
                            <div class="col-6 mb-20">
                                <label for="isActive" class="form-label fw-semibold text-primary-light text-sm mb-8">Status </label>
                                <select class="form-control radius-8 form-select" id="isActive" name="isActive" required>
                                    <option selected disabled>Select One</option>
                                    <option value="1">Active</option>
                                    <option value="0">InActive</option>
                                </select>
                            </div>
                            <div class="d-flex align-items-center justify-content-center gap-3 mt-24">
                                <button type="reset" class="border border-danger-600 bg-hover-danger-200 text-danger-600 text-md px-50 py-11 radius-8">
                                    Reset
                                </button>
                                <button type="submit" class="btn lufera-bg border text-md px-50 py-12 radius-8" name="save">
                                    Save Change
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
$(document).ready(function() {
    $('#lang-table').DataTable();
} );


$(document).ready(function () {
    $('#roleForm').on('submit', function (e) {
        e.preventDefault(); // Prevent form from submitting normally

        $.ajax({
            url: 'lang-handler.php', // PHP file that processes the request
            type: 'POST',
            data: $(this).serialize(),
            success: function (response) {
                if (response === 'exists') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Language already exists!',
                        text: 'Please choose a different language name.',
                        confirmButtonText: 'OK',
                        allowOutsideClick: false
                    });
                    
                } 
                else if (response === 'update') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Saved!',
                        text: 'Language updated successfully.',
                        confirmButtonText: 'OK'
                    }).then(() => {
                        location.reload();
                    });
                }
                else if (response === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Saved!',
                        text: 'Language created successfully.',
                        confirmButtonText: 'OK'
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: 'Something went wrong.',
                        confirmButtonText: 'OK'
                    });
                }
            }
        });
    });
});

$(document).on("click", ".edit-role-btn", function () {
    const id = $(this).data("id");
    const language = $(this).data("language");
    const isActive = $(this).data("status");

    $("#role_id").val(id);
    $("#language").val(language);
    $("#isActive").val(isActive);
    
    // Change modal UI
    $(".modal-title").text("Edit Language");
    $(".btn[type='submit']").text("Update").attr("name", "update");
});

$(document).on("click", ".remove-item-btn", function () {
    const button = $(this);
    const roleId = button.data("id");

    Swal.fire({
        title: 'Are you sure?',
        text: "This will permanently delete the language.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: 'lang-handler.php',
                type: 'POST',
                data: {
                    action: 'delete',
                    id: roleId
                },
                success: function (response) {
                    if (response === 'success') {
                        button.closest("tr").remove();
                        Swal.fire('Deleted!', 'Language deleted successfully.', 'success');
                    } else {
                        Swal.fire('Error!', 'Failed to delete language.', 'error');
                    }
                },
                error: function () {
                    Swal.fire('Error!', 'Server error.', 'error');
                }
            });
        }
    });
});
</script>

<?php include './partials/layouts/layoutBottom.php' ?>