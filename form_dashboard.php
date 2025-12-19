<?php include './partials/layouts/layoutTop.php'; ?>

<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Form Builder Dashboard</title>

<style>
:root{
    --primary:#fec700;
    --bg:#f9fafb;
    --card:#ffffff;
    --muted:#6b7280;
    --border:#f5e8b8;
}
*{box-sizing:border-box;font-family:Inter,Segoe UI,Roboto,Helvetica,Arial,sans-serif}

body{
    margin:0;
    background:var(--bg);
    color:#111827;
}

/* Page Layout */
.page{
    padding:36px;
}

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

/* Create Button */
.create-btn{
    background:var(--primary);
    padding:12px 22px;
    font-weight:700;
    border:none;
    border-radius:8px;
    cursor:pointer;
    font-size:15px;
    text-decoration:none;
    color:#111827;
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
    background:#ffffff;
    border:1px solid var(--border);
    padding:9px 18px;
    border-radius:8px;
    font-size:14px;
    font-weight:700;
    color:#111827;
    text-decoration:none;
    transition:all .2s ease;
}
.card-actions .view-btn:hover{
    background:#f9fafb;
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
</style>
</head>

<body>

<div class="page">

    <!-- Header -->
    <div class="header">
        <h2>My Forms</h2>
        <a href="form_builder.php" class="create-btn">+ Create Form</a>
    </div>

    <!-- Forms Grid -->
    <div class="grid">

        <?php
        $query = "SELECT id, form_title, created_at FROM form_builder ORDER BY id DESC";
        $result = $conn->query($query);

        if ($result && $result->num_rows > 0):
            while ($row = $result->fetch_assoc()):
        ?>

            <div class="card">
                <div>
                    <h4><?= htmlspecialchars($row['form_title']) ?></h4>
                    <p>Form Builder Template</p>
                </div>

                <div class="card-footer">
                    <span>Created: <?= date("d M Y", strtotime($row['created_at'])) ?></span>

                    <div class="card-actions">
                        <!-- VIEW MODE -->
                        <a href="form_builder.php?id=<?= $row['id'] ?>&mode=view" class="view-btn">
                            View
                        </a>

                        <!-- EDIT MODE -->
                        <a href="form_builder.php?id=<?= $row['id'] ?>" class="edit-btn">
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

</body>
</html>

<?php include './partials/layouts/layoutBottom.php'; ?>
