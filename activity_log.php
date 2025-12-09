<?php include './partials/layouts/layoutTop.php' ?>
<div class="dashboard-main-body">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
        <a class="cursor-pointer fw-bold" onclick="history.back()"><span class="fa fa-arrow-left"></span>&nbsp; Back</a> 
        <h6 class="fw-semibold mb-0">Activity Log</h6>
        <a class="cursor-pointer fw-bold visibility-hidden" onclick="history.back()"><span class="fa fa-arrow-left"></span>&nbsp; Back</a> 
    </div>

    <?php 
        // Convert UTC to Indian Time (IST)
        function utcToIST($datetime) {
            $dt = new DateTime($datetime, new DateTimeZone('UTC')); // stored in UTC
            $dt->setTimezone(new DateTimeZone('Asia/Kolkata')); // convert to IST
            return $dt->format('d/m/Y h:i a');
        }

        // Get current user
        $current_user_id = $_SESSION['user_id'];
        $user_sql = "SELECT role FROM users WHERE id = $current_user_id";
        $user_result = $conn->query($user_sql);
        $user_row = mysqli_fetch_assoc($user_result);
        $current_user_role = $user_row['role'];

        // If admin
        if ($current_user_role == 1 || $current_user_role == 2) {
            $log = "
                SELECT log.*, users.first_name, users.last_name
                FROM log
                INNER JOIN users ON log.user_id = users.id
                ORDER BY log.date_time DESC
            ";
            $showNameColumn = true;

        } else { 
            // Normal user
            $log = "
                SELECT log.*
                FROM log
                WHERE log.user_id = '$current_user_id'
                ORDER BY log.date_time DESC
            ";
            $showNameColumn = false;
        }

        $results = $conn->query($log);
    ?>

    <div class="card h-100 p-0 radius-12">
        <div class="card-body p-24">
            <div class="table-responsive scroll-sm">
                <table class="table bordered-table sm-table mb-0" id="log">
                    <thead>
                        <tr>
                            <th scope="col">ID</th>
                            <?php if ($showNameColumn) { ?>
                                <th scope="col">Name</th>
                            <?php } ?>
                            <th scope="col">Module</th>
                            <th scope="col">Action</th>
                            <th scope="col" class="text-center">Date & Time</th>
                        </tr>
                    </thead>
                    <tbody>

                        <?php                      
                            if (mysqli_num_rows($results) > 0) {
                                while ($row = mysqli_fetch_assoc($results)) {
                        ?>
                            <tr>
                                <td><?php echo $row['id']; ?></td>

                                <?php if ($showNameColumn) { ?>
                                    <td><?php echo $row['first_name'] . ' ' . $row['last_name']; ?></td>
                                <?php } ?>

                                <td><?php echo $row['module']; ?></td>
                                <td><?php echo $row['action']; ?></td>

                                <!-- Convert UTC to IST on display -->
                                <td class="text-center">
                                    <?php echo utcToIST($row['date_time']); ?>
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

<script>
$(document).ready(function() {
    $('#log').DataTable({
        order: [[0, 'desc']], 
        columnDefs: [
            {
                targets: 0,
                visible: false,
                searchable: false
            }
        ]
    });
});
</script>

<?php include './partials/layouts/layoutBottom.php' ?>
