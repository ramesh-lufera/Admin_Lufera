<?php include './partials/layouts/layoutTop.php' ?>

<?php
  $Id = $_SESSION['user_id'];

  $sql = "select user_id from users where id = $Id";
  $res = $conn ->query($sql);
  $row = $res ->fetch_assoc();
  $UserId = $row['user_id'];

  // Get all websites for this user
  $sql = "SELECT * FROM websites WHERE user_id = '$UserId' ORDER BY created_at DESC";
  $result = mysqli_query($conn, $sql);

  $websites = [];
  while ($row = mysqli_fetch_assoc($result)) {
      $websites[] = $row;
  }

  // Number of websites per page
  $websitesPerPage = 5;

  // Get the current page from URL, default is 1
  $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

  // Calculate the starting index for the websites to display on this page
  $startIndex = ($page - 1) * $websitesPerPage;

  // Slice the websites array to get only the websites for the current page
  $websitesOnPage = array_slice($websites, $startIndex, $websitesPerPage);

  // Calculate the total number of pages
  $totalPages = ceil(count($websites) / $websitesPerPage);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Websites</title>
  <style>
    :root {
      --yellow: #fec700;
      --black: #101010;
      --mild-blue: #e6f0ff;
    }

    .content-wrapper {
      width: 100%;
      /* max-width: 1200px; */
      margin: 20px auto;
      padding: 10px 15px;
    }

    .header-row {
      margin-bottom: 20px;
    }

    .header-row h5 {
      font-size: 24px !important;
      margin: 0;
    }

    .search-card {
      background-color: #fff;
      border-radius: 8px;
      padding: 15px 20px;
      margin-bottom: 20px;
      box-shadow: 0 1px 4px rgba(0,0,0,0.08);
      display: flex;
      flex-wrap: wrap;
      justify-content: space-between;
      align-items: center;
      gap: 10px;
    }

    .search-container {
      position: relative;
      flex: 1 1 300px;
      max-width: 400px;
    }

    .search-icon {
      position: absolute;
      left: 10px;
      top: 50%;
      transform: translateY(-50%);
      font-size: 16px;
      color: #999;
      pointer-events: none;
    }

    .search-container input[type="text"] {
      width: 100%;
      padding: 10px 10px 10px 35px;
      font-size: 16px;
      border: 2px solid var(--yellow);
      border-radius: 5px;
      box-sizing: border-box;
    }

    .add-btn {
      padding: 10px 16px;
      background-color: var(--yellow);
      color: var(--black);
      border: none;
      border-radius: 5px;
      font-weight: bold;
      text-decoration: none;
      cursor: pointer;
      white-space: nowrap;
      flex-shrink: 0;
    }

    .list-section {
      background-color: #fff;
      border-radius: 8px;
      padding: 20px;
      box-shadow: 0 1px 4px rgba(0,0,0,0.08);
    }

    .list-section h5 {
      margin-top: 0;
      margin-bottom: 15px;
      font-size: 20px;
    }

    .list-wrapper {
      display: flex;
      flex-direction: column;
      gap: 15px;
    }

    .list-item {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 15px 20px;
      border: 1px solid #eee;
      border-left: 5px solid var(--yellow);
      border-radius: 6px;
      background-color: #fff;
      transition: box-shadow 0.2s ease;
      flex-wrap: wrap;
      gap: 10px;
    }

    .list-item:hover {
      box-shadow: 0 2px 6px rgba(0,0,0,0.1);
    }

    .site-info {
      flex: 1 1 60%;
      display: flex;
      flex-direction: column;
      gap: 5px;
      min-width: 0;
    }

    .site-info-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .site-info-header h6 {
      margin: 0 0 8px 0;
      font-weight: bold;
      font-size: 20px;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
      max-width: 60%;
    }

    .site-info-header .plan {
      font-size: 14px;
      color: #555;
      white-space: nowrap;
    }

    .site-info-meta {
      font-size: 14px;
      color: #555;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
      margin-top: 5px;
    }

    .manage-btn-wrapper {
      flex-shrink: 0;
      display: flex;
      flex-direction: column;
      align-items: flex-end;
      gap: 8px;
    }

    .manage-btn-wrapper .plan {
      font-size: 14px;
      color: #555;
    }

    .dashboard-btn {
      background-color: var(--yellow);
      color: var(--black);
      padding: 8px 16px;
      border-radius: 5px;
      font-weight: bold;
      text-decoration: none;
      white-space: nowrap;
      transition: background-color 0.3s ease;
    }

    .dashboard-btn:hover {
      background-color: #e5b800;
    }

    .pagination {
      display: flex;
      justify-content: flex-end;
      gap: 10px;
      margin-top: 20px;
    }

    .pagination a {
      padding: 8px 15px;
      background-color: var(--yellow);
      color: var(--black);
      border-radius: 5px;
      text-decoration: none;
    }

    .pagination a:hover {
      background-color: #222;
    }

    .status-active {
      border-left: 5px solid var(--yellow);
      /* border-left: 5px solid #4caf50; Green */
    }

    .status-pending {
      border-left: 5px solid #ff9800; /* Orange */
    }

    .status-expired {
      border-left: 5px solid #f44336; /* Red */
    }

    /* Increase font sizes */
    .site-info-header h6 {
      font-size: 20px;
    }

    .site-info-meta {
      font-size: 16px;
    }

    .manage-btn-wrapper .plan {
      font-size: 16px;
    }

    .dashboard-btn {
      font-size: 16px;
    }

    /* Status-based colors for domain only */
    .domain-text-active {
      /* color: #4caf50; */
      color: var(--yellow);
    }

    .domain-text-pending {
      color: #ff9800;
    }

    .domain-text-expired {
      color: #f44336;
    }

    /* Responsive */
    @media (max-width: 700px) {
      .list-item {
        flex-direction: column;
        align-items: flex-start;
      }

      .site-info {
        flex: 1 1 100%;
      }

      .site-info-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 3px;
      }

      .site-info-header h6 {
        max-width: 100%;
      }

      .site-info-header .plan {
        font-size: 14px;
      }

      .manage-btn-wrapper {
        width: 100%;
        margin-top: 10px;
        align-items: flex-start;
      }
    }
  </style>
</head>
<body>

<div class="content-wrapper">

  <!-- Title -->
  <div class="header-row">
    <h5>Websites</h5>
  </div>

  <!-- Search + Add -->
  <div class="search-card">
    <div class="search-container">
      <span class="search-icon">&#128269;</span>
      <input type="text" id="searchInput" placeholder="Search websites..." />
    </div>
    <a href="add-website.php" class="add-btn">+ Add New Website</a>
  </div>

  <!-- Website List -->
  <!-- <div class="list-section" id="websiteList"> -->
    <!-- <h5>Business WordPress Hosting</h5> -->

    <div class="list-wrapper" id="websiteList">
      <?php if (empty($websitesOnPage)): ?>
        <div class="list-item" style="justify-content: center; font-size: 18px; color: #888;">
          No websites found.
        </div>
      <?php else: ?>
        <?php foreach ($websitesOnPage as $site): ?>
          <?php
            $status = strtolower($site['status']);
            $statusClass = 'status-pending';
            if ($status === 'active') $statusClass = 'status-active';
            elseif ($status === 'expired') $statusClass = 'status-expired';
          ?>
          <div class="list-item <?php echo $statusClass; ?>">
            <div class="site-info">
              <!-- Domain Title -->
              <div class="site-info-header">
                <!-- <h6>
                  <?php echo !empty($site['domain_title']) ? htmlspecialchars($site['domain_title']) : 'Untitled Website'; ?>
                </h6> -->
                <h6>
                  Lufera Infotech
                </h6>
              </div>
              <!-- Website (no link, color applied only to domain text) -->
              <div class="site-info-meta">
                Website: 
                <span class="domain-text-<?php echo $status; ?>">
                  <?php echo htmlspecialchars($site['domain']); ?>
                </span>
              </div>
              <!-- Expiry Date (normal text, larger font) -->
              <div class="site-info-meta <?php echo 'domain-text-' . $status; ?>">
                <strong>Expires:</strong>
                <?php 
                  if (!empty($site['created_at']) && $site['created_at'] != '0000-00-00 00:00:00') {
                    echo date('d-m-Y', strtotime($site['created_at']));
                  } else {
                    echo "N/A";
                  }
                ?>
              </div>
            </div>
            <div class="manage-btn-wrapper">
              <div class="plan">Plan: <?php echo htmlspecialchars($site['plan']); ?></div>
              <a href="dashboard.php?site=<?php echo urlencode($site['domain']); ?>" class="dashboard-btn">Manage</a>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <!-- Pagination -->
    <div class="pagination">
      <?php if ($page > 1): ?>
        <a href="?page=<?php echo $page - 1; ?>">Previous</a>
      <?php endif; ?>

      <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <a href="?page=<?php echo $i; ?>" class="<?php echo $i === $page ? 'active' : ''; ?>">
          <?php echo $i; ?>
        </a>
      <?php endfor; ?>

      <?php if ($page < $totalPages): ?>
        <a href="?page=<?php echo $page + 1; ?>">Next</a>
      <?php endif; ?>
    </div>

  <!-- </div> -->
</div>

<script>
  const searchInput = document.getElementById('searchInput');
  searchInput.addEventListener('keyup', function () {
    const filter = searchInput.value.toLowerCase();
    const items = document.querySelectorAll("#websiteList .list-item");
    items.forEach(item => {
      const text = item.innerText.toLowerCase();
      item.style.display = text.includes(filter) ? '' : 'none';
    });
  });
</script>

</body>
</html>

<?php include './partials/layouts/layoutBottom.php' ?>