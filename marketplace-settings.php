<?php include './partials/layouts/layoutTop.php'; 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$categoryQuery = $conn->query("SELECT cat_id, cat_name FROM categories WHERE cat_type = 'product' ORDER BY cat_name ASC");
$categories = [];

while($row = $categoryQuery->fetch_assoc()){
    $categories[] = $row;
}
?>
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
        <h6 class="fw-semibold mb-0">Marketplace</h6>
        <a class="cursor-pointer fw-bold visibility-hidden" onclick="history.back()"><span class="fa fa-arrow-left"></span>&nbsp; Back</a> 
    </div>

    <!-- START TABS HERE -->
    <ul class="nav nav-tabs mb-3" id="marketplaceTabs" role="tablist">
        <li class="nav-item">
            <button class="nav-link active" id="slider-tab" data-bs-toggle="tab" data-bs-target="#sliderPane" type="button">
                Marketplace Slider
            </button>
        </li>

        <li class="nav-item">
            <button class="nav-link" id="banner-tab" data-bs-toggle="tab" data-bs-target="#bannerPane" type="button">
                Marketplace Banner
            </button>
        </li>
    </ul>

    <div class="tab-content">
        <!-- Slider Tab -->
        <div class="tab-pane fade show active" id="sliderPane">
            <div class="card h-100 p-0 radius-12">
                <div class="card-body">
                    <div class="d-flex justify-content-end mb-3">
                        <button class="btn lufera-bg text-white" data-bs-toggle="modal" data-bs-target="#exampleModal">
                            Add New
                        </button>
                    </div>
                    <div class="table-responsive scroll-sm">
                        <table class="table bordered-table mb-0" id="role-table">
                            <thead>
                                <tr>
                                    <th>Category</th>
                                    <th>Slider</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                    $service = "
                                    SELECT
                                    m.id,
                                    c.cat_name,
                                    m.model
                                    FROM marketplace m
                                    LEFT JOIN categories c
                                    ON c.cat_id=m.cat_id
                                    ORDER BY m.id DESC
                                    ";
                                    $results = $conn->query($service);
                                    if (mysqli_num_rows($results) > 0) {
                                        while ($row = mysqli_fetch_assoc($results)) {
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['cat_name']); ?></td>
                                    <td>Model <?= $row['model']; ?></td>
                                    <td class="text-center">
                                        <div class="d-flex align-items-center gap-10 justify-content-center">
                                            <button type="button" class="fa fa-edit edit-btn bg-success-focus text-success-600 bg-hover-success-200 fw-medium w-40-px h-40-px d-flex justify-content-center align-items-center rounded-circle" data-id="<?= $row['id'] ?>" data-bs-toggle="modal" data-bs-target="#exampleModal">
                                            </button>

                                            <button type="button" class="fa fa-trash-alt remove-btn bg-danger-focus bg-hover-danger-200 text-danger-600 fw-medium w-40-px h-40-px d-flex justify-content-center align-items-center rounded-circle" data-id="<?= $row['id'] ?>">
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

        <!-- Banner Tab -->
        <div class="tab-pane fade" id="bannerPane">
            <div class="card h-100 p-0 radius-12">
                <div class="card-body">
                    <!-- Banner card goes here -->
                    <div class="d-flex justify-content-end mb-3">
                        <button class="btn lufera-bg text-white" data-bs-toggle="modal" data-bs-target="#bannerModal">
                            Add Banner
                        </button>
                    </div>

                    <div class="table-responsive">
                        <table class="table bordered-table w-100" id="banner-table">
                            <thead>
                            <tr>
                                <th>Slide 1</th>
                                <th>Slide 2</th>
                                <th>Slide 3</th>
                                <th width="140">Action</th>
                            </tr>
                            </thead>
                            <tbody>
                                <?php
                                $q=$conn->query("SELECT * FROM marketplace_banner ORDER BY id DESC");
                                while($row=$q->fetch_assoc()){
                                    $images=json_decode($row['images'],true);
                                    $titles=json_decode($row['title'],true);
                                ?>
                                <tr>
                                    <td><img src="<?= $images[0] ?>" width="70"><br><?= $titles[0] ?></td>
                                    <td><img src="<?= $images[1] ?>" width="70"><br><?= $titles[1] ?></td>
                                    <td><img src="<?= $images[2] ?>" width="70"><br><?= $titles[2] ?></td>
                                    <td class="text-center">
                                        <button class="btn btn-success btn-sm banner-edit" data-id="<?= $row['id']?>">
                                            Edit
                                        </button>
                                        <button class="btn btn-danger btn-sm banner-delete" data-id="<?= $row['id']?>">
                                            Delete
                                        </button>
                                    </td>
                                </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Start -->
<div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Add New</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
            <form id="marketplaceForm">
                <input type="hidden" name="id" id="marketplaceId">
                <div class="mb-3">
                    <label class="form-label">Select Category</label>
                    <select class="form-control radius-8" name="cat_id" id="cat_id" required>
                        <option value="">Select Category</option>
                        <?php foreach($categories as $cat){ ?>
                            <option value="<?= $cat['cat_id']; ?>">
                                <?= htmlspecialchars($cat['cat_name']); ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Select Slider</label>
                    <select class="form-control radius-8" name="model" id="model" required>
                        <option value="">Select Slider</option>
                        <option value="1">Model 1</option>
                        <option value="2">Model 2</option>
                        <option value="3">Model 3</option>
                        <option value="4">Model 4</option>
                    </select>
                </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" id="submitMarketplace" class="btn lufera-bg">Save</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal End -->
<div class="modal fade" id="bannerModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5>Marketplace Banner</h5>
            </div>
            <div class="modal-body">
                <form id="bannerForm" enctype="multipart/form-data">
                    <input type="hidden" name="id" id="bannerId">
                    <!-- Slide 1 -->
                    <div class="border rounded p-3 mb-3">
                        <h6>Slide 1</h6>
                        <div class="mb-3">
                            <label>Image</label>
                            <input type="file" class="form-control" name="slide1_image">
                        </div>
                        <div>
                            <label>Title</label>
                            <input type="text" class="form-control" name="slide1_title">
                        </div>
                    </div>

                    <!-- Slide 2 -->
                    <div class="border rounded p-3 mb-3">
                        <h6>Slide 2</h6>
                        <div class="mb-3">
                            <label>Image</label>
                            <input type="file" class="form-control" name="slide2_image">
                        </div>
                        <div>
                            <label>Title</label>
                            <input type="text" class="form-control" name="slide2_title">
                        </div>
                    </div>

                    <!-- Slide 3 -->
                    <div class="border rounded p-3">
                        <h6>Slide 3</h6>
                        <div class="mb-3">
                            <label>Image</label>
                            <input type="file" class="form-control" name="slide3_image">
                        </div>
                        <div>
                            <label>Title</label>
                            <input type="text" class="form-control" name="slide3_title">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button class="btn lufera-bg" id="saveBanner">Save</button>
            </div>
        </div>
    </div>
</div>
<script>
    $(document).on('click', '.banner-edit', function () {
        let id = $(this).data('id');
        $.get(
            'marketplace_banner_crud.php',
            {
                action: 'get',
                id: id
            },
            function (res) {
                let data = typeof res === 'string' ? JSON.parse(res) : res;
                if (data.status == 'success') {
                    $('#bannerId').val(data.data.id);
                    $('input[name="slide1_title"]').val(data.data.title[0]);
                    $('input[name="slide2_title"]').val(data.data.title[1]);
                    $('input[name="slide3_title"]').val(data.data.title[2]);
                    $('#bannerModal .modal-title').text('Edit Banner');
                    $('#saveBanner').text('Update');
                    $('#bannerModal').modal('show');
                }
            }
        );
    });

    $(document).on('click', '.banner-delete', function () {
        let id = $(this).data('id');
        Swal.fire({
            title: 'Delete Banner?',
            text: 'This action cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Delete'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post(
                    'marketplace_banner_crud.php',
                    {
                        action: 'delete',
                        id: id
                    },
                    function (res) {
                        let data = typeof res === 'string' ? JSON.parse(res) : res;
                        if (data.status == 'success') {
                            Swal.fire(
                                'Deleted',
                                data.message,
                                'success'
                            ).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire(
                                'Error',
                                data.message,
                                'error'
                            );
                        }
                    }
                );
            }
        });
    });

    $(document).ready(function() {
        $('#banner-table').DataTable();
            $('[data-bs-target="#bannerModal"]').on('click', function () {
            $('#bannerForm')[0].reset();
            $('#bannerId').val('');
            $('#bannerModal .modal-title').text('Add Banner');
            $('#saveBanner').text('Save');
        });

        let bannerTable = $('#banner-table').DataTable();
        $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function () {
            bannerTable.columns.adjust().draw();
        });

        $('#role-table').DataTable();
            $('[data-bs-target="#exampleModal"]').on('click', function() {
            $('#promotionForm')[0].reset();
            $('#promotionId').val('');
            $('#modalTitle').text('Add New Promotion');
            $('#submitPromotion').text('Save Promotion');
        });
    });


    $('#saveBanner').click(function () {
        let formData = new FormData($('#bannerForm')[0]);
        let id = $('#bannerId').val();
        formData.append('action', id == '' ? 'create' : 'update');
        $.ajax({
            url: 'marketplace_banner_crud.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            beforeSend: function () {
                console.log(formData);
            },
            success: function (data) {
                console.log(data);
                if (data.status == "success") {
                    Swal.fire(
                        'Success',
                        data.message,
                        'success'
                    ).then(function () {
                        location.reload();
                    });
                } else {
                    Swal.fire(
                        'Error',
                        data.message,
                        'error'
                    );
                }
            },
            error: function (xhr, status, error) {
                console.log("STATUS:", status);
                console.log("ERROR:", error);
                console.log(xhr.responseText);
                Swal.fire(
                    'Error',
                    'AJAX failed. Check console.',
                    'error'
                );
            }
        });
    });

    document.getElementById('submitMarketplace').addEventListener('click', function(){
    const form = document.getElementById('marketplaceForm');
    if(!form.checkValidity()){
        form.reportValidity();
        return;
    }
    const formData = new FormData(form);
    let id = $("#marketplaceId").val();
    formData.append("action", id == "" ? "create" : "update");
    fetch('marketplace_crud.php',{
        method:'POST',
        body:new URLSearchParams(formData)

    })
    .then(res=>res.json())
        .then(data=>{
            if(data.status=='success'){
                Swal.fire(
                    'Success',
                    data.message,
                    'success'
                ).then(()=>location.reload());
            }else{
                Swal.fire(
                    'Error',
                    data.message,
                    'error'
                );
            }
        });
    });

    document.querySelectorAll('.edit-btn').forEach(btn=>{
        btn.addEventListener('click',function(){
            let id=this.dataset.id;
            fetch('marketplace_crud.php?action=get&id='+id)
            .then(res=>res.json())
            .then(data=>{
                if(data.status=="success"){
                    $("#marketplaceId").val(data.data.id);
                    $("#cat_id").val(data.data.cat_id);
                    $("#model").val(data.data.model);
                    $("#modalTitle").text("Edit Marketplace");
                    $("#submitMarketplace").text("Update");
                }
            });
        });
    });
</script>
<?php include './partials/layouts/layoutBottom.php' ?>