<?php include './partials/layouts/layoutTop.php'; ?>

<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Form Builder Dashboard</title>

<style>
    /* Header */
    .header{
        display:flex;
        justify-content:space-between;
        align-items:center;
        margin-bottom:32px;
    }
    .header h2{
        margin:0;
        font-size:26px !important;
        font-weight:700;
    }

    /* Grid */
    .grid{
        display:grid;
        grid-template-columns:repeat(auto-fill, minmax(320px,1fr));
        gap:26px;
    }

    /* Card */
    .card{
        background:var(--card);
        border:1px solid var(--border);
        border-radius:14px;
        padding:24px;
        box-shadow:0 8px 22px rgba(0,0,0,0.07);
        display:flex;
        flex-direction:column;
        justify-content:space-between;
        transition:all .25s ease;
    }
    .card:hover{
        transform:translateY(-3px);
        box-shadow:0 14px 34px rgba(0,0,0,0.12);
    }

    /* Card Title */
    .card h4{
        margin:0 0 12px;
        font-size:21px !important;
        font-weight:700;
        line-height:1.35;
    }

    /* Card Description */
    .card p{
        margin:0;
        font-size:15px;
        line-height:1.6;
        color:var(--muted);
    }

    /* Card Footer */
    .card-footer{
        display:flex;
        justify-content:space-between;
        align-items:center;
        margin-top:22px;
        padding-top:14px;
        border-top:1px solid var(--border);
    }

    /* Date */
    .card-footer span{
        font-size:14px;
        color:var(--muted);
    }

    /* Action Buttons */
    .card-actions{
        display:flex;
        gap:10px;
    }

    /* VIEW BUTTON */
    .card-actions .view-btn{
        border:1px solid var(--border);
        padding:9px 18px;
        border-radius:8px;
        font-size:14px;
        font-weight:700;
        color:#111827;
        text-decoration:none;
        transition:all .2s ease;
    }

    /* EDIT BUTTON */
    .card-actions .edit-btn{
        background:var(--primary);
        padding:9px 20px;
        border-radius:8px;
        font-size:14px;
        font-weight:700;
        color:#111827;
        text-decoration:none;
        transition:all .2s ease;
    }
    .card-actions .edit-btn:hover{
        filter:brightness(0.95);
    }

    /* Empty State */
    .empty{
        background:#fff;
        border:2px dashed var(--border);
        border-radius:14px;
        padding:60px;
        text-align:center;
        color:var(--muted);
        grid-column:1 / -1;
        font-size:15px;
    }

    /* Shortcode */
    .shortcode-box{
        margin-top:12px;
        display:flex;
        align-items:center;
        gap:8px;
        font-size:13px;
        background:#f9fafb;
        border:1px dashed var(--border);
        padding:6px 10px;
        border-radius:8px;
        width:fit-content;
        cursor:pointer;
    }

    .shortcode-box code{
        font-family:monospace;
        font-size:13px;
        color:#111827;
    }

    .shortcode-box svg{
        width:14px;
        height:14px;
        stroke:#374151;
    }
</style>
</head>

<body>
<div class="dashboard-main-body">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
        <a class="cursor-pointer fw-bold" onclick="history.back()"><span class="fa fa-arrow-left"></span>&nbsp; Back</a> 
        <h6 class="fw-semibold mb-0">Forms</h6>
        <button type="button" class="add-role-btn btn lufera-bg text-white text-sm btn-sm px-12 py-12 radius-8 d-flex align-items-center gap-2" onclick="window.location.href='form_builder.php'">
            <iconify-icon icon="ic:baseline-plus" class="icon text-xl line-height-1"></iconify-icon>
            Create Form 
        </button>
    </div>
    <!-- Forms Grid -->
    <div class="grid">
        <?php
        $query = "SELECT id, form_title, created_at FROM form_builder ORDER BY id DESC";
        $result = $conn->query($query);
        if ($result && $result->num_rows > 0):
            while ($row = $result->fetch_assoc()):
        ?>
            <div class="card bg-white">
                <!-- <div>
                    <h4><?= htmlspecialchars($row['form_title']) ?></h4>
                    <p>Form Builder Template</p>
                </div> -->

                <div>
                    <h4><?= htmlspecialchars($row['form_title']) ?></h4>
                    <p>Form Builder Template</p>

                    <!-- SHORTCODE -->
                    <div class="shortcode-box"
                        onclick="copyShortcode('[form id=<?= $row['id'] ?>]')">
                        <code>[form id=<?= $row['id'] ?>]</code>

                        <!-- Copy Icon -->
                        <svg viewBox="0 0 24 24" fill="none" stroke-width="2">
                            <rect x="9" y="9" width="13" height="13"></rect>
                            <rect x="3" y="3" width="13" height="13"></rect>
                        </svg>
                    </div>
                </div>

                <div class="card-footer">
                    <span>Created: <?= date("d M Y", strtotime($row['created_at'])) ?></span>

                    <div class="card-actions">
                        <!-- VIEW MODE -->
                        <a href="form_builder.php?id=<?= $row['id'] ?>&mode=view" target="_blank" class="view-btn">
                            View
                        </a>

                        <!-- EDIT MODE -->
                        <a href="form_builder.php?id=<?= $row['id'] ?>" target="_blank" class="edit-btn">
                            Edit
                        </a>
                    </div>
                </div>
            </div>

        <?php
            endwhile;
        else:
        ?>
            <div class="empty">
                No forms available. Click “Create Form” to get started.
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    function copyShortcode(text){
        navigator.clipboard.writeText(text).then(function(){
            alert('Shortcode copied: ' + text);
        });
    }
</script>

</body>
</html>

<?php include './partials/layouts/layoutBottom.php'; ?>
