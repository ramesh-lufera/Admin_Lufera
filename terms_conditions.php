<?php include './partials/layouts/layoutTop.php';?>
<?php
    $sql = "SELECT * FROM terms_conditions where apply_for = 't_c' ";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $id = $row['id'];
        $title = $row['title'];
        $content = $row['content'];
    }
?>
<style>
    ul, ol{
        list-style: auto;
    }
</style>
<div class="dashboard-main-body">
    <div class="card h-100 p-0 radius-12 overflow-hidden">
        <div class="card-body p-40">
            <div class="row">
                <div class="col-sm-12">
                    <div class="mb-20">
                        <?php if ($result->num_rows > 0) { ?>
                        <h4 class="text-center"><?php echo $title; ?></h4>
                        <?php echo $content; ?>
                        <?php } else { ?>
                            <p>No terms and conditions to show</p>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include './partials/layouts/layoutBottom.php' ?>