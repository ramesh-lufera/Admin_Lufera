<?php include './partials/layouts/layoutTop.php' ?>

<?php
// Sample websites data
$websites = [
  ['name' => 'My Portfolio', 'domain' => 'anandtra.lufera.in', 'plan' => 'Premium', 'status' => 'Active'],
  ['name' => 'Blog Site', 'domain' => 'luferatech.com', 'plan' => 'Single', 'status' => 'Inactive'],
  ['name' => 'Online Store', 'domain' => 'mystoreonline.com', 'plan' => 'Business', 'status' => 'Active'],
  ['name' => 'E-commerce Website', 'domain' => 'shoponline.com', 'plan' => 'Business', 'status' => 'Active'],
  ['name' => 'Tech Blog', 'domain' => 'techblog.org', 'plan' => 'Premium', 'status' => 'Inactive'],
  ['name' => 'Portfolio Site', 'domain' => 'artistportfolio.net', 'plan' => 'Business', 'status' => 'Active'],
  ['name' => 'News Portal', 'domain' => 'newssite.com', 'plan' => 'Premium', 'status' => 'Active'],
  ['name' => 'Travel Blog', 'domain' => 'travelblog.co', 'plan' => 'Single', 'status' => 'Inactive'],
  ['name' => 'Real Estate Site', 'domain' => 'realestateonline.com', 'plan' => 'Business', 'status' => 'Active'],
  ['name' => 'Food Blog', 'domain' => 'foodblog.org', 'plan' => 'Premium', 'status' => 'Active']
];

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

    /* body {
      margin: 0;
      font-family: 'Segoe UI', sans-serif;
      background-color: var(--mild-blue);
      color: var(--black);
    } */

    .content-wrapper {
      /* max-width: 1200px; */
      /* margin: 30px 0px 0px 50px; */
      width: 100%;
      margin: 0 auto;
      padding: 20px 15px;
    }

    .header-row {
      margin-bottom: 20px;
    }

    .search-card {
      background-color: #fff;
      border-radius: 8px;
      padding: 15px 20px;
      margin-bottom: 20px;
      box-shadow: 0 1px 4px rgba(0,0,0,0.08);
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 10px;
    }

    .search-container {
      position: relative;
      flex: 1;
      max-width: 400px;
    }

    .search-container .search-icon {
      position: absolute;
      left: 10px;
      top: 50%;
      transform: translateY(-50%);
      font-size: 16px;
      color: #999;
    }

    .search-container input[type="text"] {
      width: 100%;
      padding: 10px 10px 10px 35px;
      font-size: 16px;
      border: 2px solid var(--yellow);
      border-radius: 5px;
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
    }

    .list-section {
      background-color: #fff;
      border-radius: 8px;
      padding: 20px;
      box-shadow: 0 1px 4px rgba(0,0,0,0.08);
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
    }

    .list-item:hover {
      box-shadow: 0 2px 6px rgba(0,0,0,0.1);
    }

    .site-info {
      display: flex;
      flex-direction: column;
      flex-grow: 1;
    }

    .site-info h6 {
      margin: 0;
      font-size: 18px !important;
      font-weight: normal;
      color: #333;
    }

    h5 {
      font-size: 20px !important;
    }

    .site-meta {
      text-align: right;
      font-size: 14px;
    }

    .status {
      font-weight: bold;
    }

    .status-active { color: green; }
    .status-inactive { color: red; }

    .dashboard-btn {
      display: inline-block;
      margin-top: 8px;
      padding: 8px 12px;
      border-radius: 4px;
      font-size: 14px;
      font-weight: bold;
      text-decoration: none;
      background-color: var(--black);
      color: var(--yellow);
    }

    .dashboard-btn:hover {
      background-color: #222;
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
      <input type="text" id="searchInput" placeholder="Search websites...">
    </div>
    <a href="add-website.php" class="add-btn">+ Add New Website</a>
  </div>

  <!-- Website List -->
  <div class="list-section" id="websiteList">
    <h5 style="margin-top: 0; margin-bottom: 15px;">Business WordPress Hosting</h5>
    <div class="list-wrapper">
      <?php foreach ($websitesOnPage as $site): ?>
        <div class="list-item">
          <div class="site-info">
            <h6><?php echo $site['domain']; ?></h6> <!-- Display only domain -->
          </div>
          <div class="site-meta">
            <div>Plan: <?php echo $site['plan']; ?></div>
            <div class="status status-<?php echo strtolower($site['status']); ?>">
              <?php echo $site['status']; ?>
            </div>
            <a href="dashboard.php?site=<?php echo urlencode($site['domain']); ?>" class="dashboard-btn">Dashboard</a>
          </div>
        </div>
      <?php endforeach; ?>
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
  </div>

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