<?php include './partials/layouts/layoutTop.php' ?>

        <!-- <div class="dashboard-main-body">

            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
                <h6 class="fw-semibold mb-0">404</h6>
                <ul class="d-flex align-items-center gap-2">
                    <li class="fw-medium">
                        <a href="index.php" class="d-flex align-items-center gap-1 hover-text-primary">
                            <iconify-icon icon="solar:home-smile-angle-outline" class="icon text-lg"></iconify-icon>
                            Dashboard1234
                        </a>
                    </li>
                    <li>-</li>
                    <li class="fw-medium">404</li>
                </ul>
            </div>

            <div class="card basic-data-table">
                <div class="card-body py-80 px-32 text-center">
                    <img src="assets/images/error-img.png" alt="" class="mb-24">
                    <h6 class="mb-16">Page not Found</h6>
                    <p class="text-secondary-light">Sorry, the page you are looking for doesn't exist </p>
                    <a href="index.php" class="btn btn-primary-600 radius-8 px-20 py-11">Back to Home</a>
                </div>
            </div>
        </div> -->

        <?php
            $websites = [
            ['domain' => 'tikitaka.com', 'status' => 'Active', 'expiry' => '2025-12-01'],
            ['domain' => 'anandtra.in', 'status' => 'Renewal', 'expiry' => '2024-05-15'],
            ['domain' => 'portfolio.dev', 'status' => 'Expired', 'expiry' => '2023-08-10'],
            ];
            ?>

            <!DOCTYPE html>
            <html lang="en">
            <head>
            <meta charset="UTF-8" />
            <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
            <!-- <title>Manage Websites</title> -->
            <style>
                body {
                margin: 0;
                font-family: 'Segoe UI', sans-serif;
                background: #f4f5f8;
                color: #333;
                }

                .container {
                max-width: 900px;
                margin: 40px auto;
                padding: 20px;
                }

                .top-bar {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 30px;
                }

                .search-form input {
                padding: 10px 15px;
                width: 250px;
                font-size: 14px;
                border: 1px solid #ccc;
                border-radius: 8px;
                }

                .add-button button {
                padding: 12px 20px;
                background-color: #4b2aad;
                color: white;
                font-size: 16px;
                border: none;
                border-radius: 8px;
                cursor: pointer;
                transition: 0.3s;
                }

                .add-button button:hover {
                background-color: #3c2291;
                }

                .website-card {
                background: white;
                border-radius: 10px;
                padding: 20px;
                box-shadow: 0 5px 15px rgba(0,0,0,0.05);
                margin-bottom: 15px;
                display: flex;
                justify-content: space-between;
                align-items: center;
                }

                .domain-name {
                font-size: 18px;
                font-weight: 600;
                }

                .status {
                padding: 6px 14px;
                border-radius: 20px;
                font-size: 14px;
                font-weight: bold;
                }

                .status.Active {
                background-color: #d4f4d7;
                color: #1a7f37;
                }

                .status.Renewal {
                background-color: #fff5cc;
                color: #9a7b00;
                }

                .status.Expired {
                background-color: #ffe1e1;
                color: #d8000c;
                }

                .expiry {
                font-size: 13px;
                color: #777;
                margin-top: 4px;
                }
            </style>
            </head>
            <body>

            <div class="container">

                <!-- Top Row: Search + Add Button -->
                <div class="top-bar">
                <form class="search-form" action="" method="get">
                    <input type="text" name="search" placeholder="Search websites..." />
                </form>

                <div class="add-button">
                    <form action="https://luferatech.com/package/" target="_blank" method="get">
                    <button type="submit">+ Add New Domain</button>
                    </form>
                </div>
                </div>

                <!-- Website List -->
                <?php foreach ($websites as $site): ?>
                <div class="website-card">
                    <div>
                    <div class="domain-name"><?= htmlspecialchars($site['domain']) ?></div>
                    <div class="expiry">Expiry: <?= htmlspecialchars($site['expiry']) ?></div>
                    </div>
                    <div class="status <?= $site['status'] ?>"><?= $site['status'] ?></div>
                </div>
                <?php endforeach; ?>

            </div>

            </body>
            </html>


<?php include './partials/layouts/layoutBottom.php' ?>