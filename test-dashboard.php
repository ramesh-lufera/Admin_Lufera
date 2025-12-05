<?php $script = '<script>
    // ===================== Average Enrollment Rate Start =============================== 
    function createChartTwo(chartId, color1, color2) {
        var options = {
            series: [{
                name: "series2",
                data: [20000, 45000, 30000, 50000, 32000, 40000, 30000, 42000, 28000, 34000, 38000, 26000]
            }],
            legend: {
                show: false
            },
            chart: {
                type: "area",
                width: "100%",
                height: 240,
                toolbar: {
                    show: false
                },
                padding: {
                    left: 0,
                    right: 0,
                    top: 0,
                    bottom: 0
                }
            },
            dataLabels: {
                enabled: false
            },
            stroke: {
                curve: "straight",
                width: 3,
                colors: [color1], // Use two colors for the lines
                lineCap: "round"
            },
            grid: {
                show: true,
                borderColor: "#D1D5DB",
                strokeDashArray: 1,
                position: "back",
                xaxis: {
                    lines: {
                        show: false
                    }
                },
                yaxis: {
                    lines: {
                        show: true
                    }
                },
                row: {
                    colors: undefined,
                    opacity: 0.5
                },
                column: {
                    colors: undefined,
                    opacity: 0.5
                },
                padding: {
                    top: -20,
                    right: 0,
                    bottom: 0,
                    left: 0
                },
            },
            fill: {
                type: "gradient",
                colors: [color1], // Use two colors for the gradient
                // gradient: {
                //     shade: "light",
                //     type: "vertical",
                //     shadeIntensity: 0.5,
                //     gradientToColors: [`${color1}`, `${color2}00`], // Bottom gradient colors with transparency
                //     inverseColors: false,
                //     opacityFrom: .6,
                //     opacityTo: 0.3,
                //     stops: [0, 100],
                // },
                gradient: {
                    shade: "light",
                    type: "vertical",
                    shadeIntensity: 0.5,
                    gradientToColors: [undefined, `${color2}00`], // Apply transparency to both colors
                    inverseColors: false,
                    opacityFrom: [0.4, 0.4], // Starting opacity for both colors
                    opacityTo: [0.1, 0.1], // Ending opacity for both colors
                    stops: [0, 100],
                },
            },
            markers: {
                colors: [color1], // Use two colors for the markers
                strokeWidth: 3,
                size: 0,
                hover: {
                    size: 10
                }
            },
            xaxis: {
                labels: {
                    show: false
                },
                categories: ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"],
                tooltip: {
                    enabled: false
                },
                labels: {
                    formatter: function(value) {
                        return value;
                    },
                    style: {
                        fontSize: "12px"
                    }
                }
            },
            yaxis: {
                labels: {
                    // formatter: function (value) {
                    //     return "$" + value + "k";
                    // },
                    style: {
                        fontSize: "12px"
                    }
                },
            },
            tooltip: {
                x: {
                    format: "dd/MM/yy HH:mm"
                }
            }
        };

        var chart = new ApexCharts(document.querySelector(`#${chartId}`), options);
        chart.render();
    }

    createChartTwo("enrollmentChart", "#487FFF");
    // ===================== Average Enrollment Rate End =============================== 


    // ===================== Delete Table Item Start =============================== 
    $(".remove-btn").on("click", function() {
        $(this).closest("tr").addClass("d-none");
    });
    // ===================== Delete Table Item End =============================== 


    // ================================ Area chart Start ================================ 
    function createChart(chartId, chartColor) {

        let currentYear = new Date().getFullYear();

        var options = {
            series: [{
                name: "series1",
                data: [0, 10, 8, 25, 15, 26, 13, 35, 15, 39, 16, 46, 42],
            }, ],
            chart: {
                type: "area",
                width: 164,
                height: 72,

                sparkline: {
                    enabled: true // Remove whitespace
                },

                toolbar: {
                    show: false
                },
                padding: {
                    left: 0,
                    right: 0,
                    top: 0,
                    bottom: 0
                }
            },
            dataLabels: {
                enabled: false
            },
            stroke: {
                curve: "smooth",
                width: 2,
                colors: [chartColor],
                lineCap: "round"
            },
            grid: {
                show: true,
                borderColor: "transparent",
                strokeDashArray: 0,
                position: "back",
                xaxis: {
                    lines: {
                        show: false
                    }
                },
                yaxis: {
                    lines: {
                        show: false
                    }
                },
                row: {
                    colors: undefined,
                    opacity: 0.5
                },
                column: {
                    colors: undefined,
                    opacity: 0.5
                },
                padding: {
                    top: -3,
                    right: 0,
                    bottom: 0,
                    left: 0
                },
            },
            fill: {
                type: "gradient",
                colors: [chartColor], // Set the starting color (top color) here
                gradient: {
                    shade: "light", // Gradient shading type
                    type: "vertical", // Gradient direction (vertical)
                    shadeIntensity: 0.5, // Intensity of the gradient shading
                    gradientToColors: [`${chartColor}00`], // Bottom gradient color (with transparency)
                    inverseColors: false, // Do not invert colors
                    opacityFrom: .8, // Starting opacity
                    opacityTo: 0.3, // Ending opacity
                    stops: [0, 100],
                },
            },
            // Customize the circle marker color on hover
            markers: {
                colors: [chartColor],
                strokeWidth: 2,
                size: 0,
                hover: {
                    size: 8
                }
            },
            xaxis: {
                labels: {
                    show: false
                },
                categories: [`Jan ${currentYear}`, `Feb ${currentYear}`, `Mar ${currentYear}`, `Apr ${currentYear}`, `May ${currentYear}`, `Jun ${currentYear}`, `Jul ${currentYear}`, `Aug ${currentYear}`, `Sep ${currentYear}`, `Oct ${currentYear}`, `Nov ${currentYear}`, `Dec ${currentYear}`],
                tooltip: {
                    enabled: false,
                },
            },
            yaxis: {
                labels: {
                    show: false
                }
            },
            tooltip: {
                x: {
                    format: "dd/MM/yy HH:mm"
                },
            },
        };

        var chart = new ApexCharts(document.querySelector(`#${chartId}`), options);
        chart.render();
    }

    // Call the function for each chart with the desired ID and color
    createChart("areaChart", "#FF9F29");
    // ================================ Area chart End ================================ 


    // ================================ Bar chart Start ================================ 
    var options = {
        series: [{
            name: "Sales",
            data: [{
                x: "Mon",
                y: 20,
            }, {
                x: "Tue",
                y: 40,
            }, {
                x: "Wed",
                y: 20,
            }, {
                x: "Thur",
                y: 30,
            }, {
                x: "Fri",
                y: 40,
            }, {
                x: "Sat",
                y: 35,
            }]
        }],
        chart: {
            type: "bar",
            width: 164,
            height: 80,
            sparkline: {
                enabled: true // Remove whitespace
            },
            toolbar: {
                show: false
            }
        },
        plotOptions: {
            bar: {
                borderRadius: 6,
                horizontal: false,
                columnWidth: 14,
            }
        },
        dataLabels: {
            enabled: false
        },
        states: {
            hover: {
                filter: {
                    type: "none"
                }
            }
        },
        fill: {
            type: "gradient",
            colors: ["#E3E6E9"], // Set the starting color (top color) here
            gradient: {
                shade: "light", // Gradient shading type
                type: "vertical", // Gradient direction (vertical)
                shadeIntensity: 0.5, // Intensity of the gradient shading
                gradientToColors: ["#E3E6E9"], // Bottom gradient color (with transparency)
                inverseColors: false, // Do not invert colors
                opacityFrom: 1, // Starting opacity
                opacityTo: 1, // Ending opacity
                stops: [0, 100],
            },
        },
        grid: {
            show: false,
            borderColor: "#D1D5DB",
            strokeDashArray: 1, // Use a number for dashed style
            position: "back",
        },
        xaxis: {
            labels: {
                show: false // Hide y-axis labels
            },
            type: "category",
            categories: ["Mon", "Tue", "Wed", "Thur", "Fri", "Sat"]
        },
        yaxis: {
            labels: {
                show: false,
                formatter: function(value) {
                    return (value / 1000).toFixed(0) + "k";
                }
            }
        },
        tooltip: {
            y: {
                formatter: function(value) {
                    return value / 1000 + "k";
                }
            }
        }
    };

    var chart = new ApexCharts(document.querySelector("#dailyIconBarChart"), options);
    chart.render();
    // ================================ Bar chart End ================================ 


    // ================================ Follow Btn Start ================================ 
    $(".follow-btn").on("click", function() {
        if ($(this).text() === "Follow") {
            $(this).text("Unfollow");
        } else {
            $(this).text("Follow");
        }
        $(this).toggleClass("bg-neutral-200 border-neutral-200 text-neutral-900");
    });
    // ================================ Follow Btn End ================================ 

     // ===================== Section Visibility Control =============================== 

    // Ensure checkboxes + all sections are visible on load
    $(document).ready(function () {

        // Uncheck all checkboxes on load
        $(".sec-check").prop("checked", false);

        // Hide all sections on load
        $("#section-banner").hide();
        $("#section-stats").hide();
        $("#section-products").hide();
        $("#section-recentOrders").hide();
        $("#section-users").hide();

    });


        $("#openSectionModal").on("click", function () {
            $("#sectionModal").modal("show");
        });

        $("#applySections").on("click", function () {
            $(".sec-check").each(function () {
                let id = "#section-" + $(this).val();
                if ($(this).is(":checked")) $(id).show();
                else $(id).hide();
            });

            $("#sectionModal").modal("hide");
        });

</script>';?>

<?php include './partials/layouts/layoutTop.php';

    // Total subscriptions
    $sub_total = $conn->query("SELECT COUNT(*) AS c FROM websites")->fetch_assoc()['c'];

    // Total orders
    $order_total = $conn->query("SELECT COUNT(*) AS c FROM orders")->fetch_assoc()['c'];

    // Total users
    $user_total = $conn->query("SELECT COUNT(*) AS c FROM users")->fetch_assoc()['c'];

    // ================== THIS WEEK COUNTS ==================
    $sub_week = $conn->query("
        SELECT COUNT(*) AS c 
        FROM websites 
        WHERE YEARWEEK(created_at, 1) = YEARWEEK(NOW(), 1)
    ")->fetch_assoc()['c'];

    $order_week = $conn->query("
        SELECT COUNT(*) AS c 
        FROM orders 
        WHERE YEARWEEK(created_on, 1) = YEARWEEK(NOW(), 1)
    ")->fetch_assoc()['c'];

    $user_week = $conn->query("
        SELECT COUNT(*) AS c 
        FROM users 
        WHERE YEARWEEK(created_at, 1) = YEARWEEK(NOW(), 1)
    ")->fetch_assoc()['c'];

    // ========== IMAGE HANDLER (robust for subfolder setups) ==========
function productImage($img)
{
    $default = "assets/images/default-product.png";

    if (!$img || trim($img) === "") {
        return $default;
    }

    $img = trim($img);

    // If full URL (CDN, Google etc.)
    if (filter_var($img, FILTER_VALIDATE_URL)) {
        return $img;
    }

    // If path already contains uploads/products/
    if (strpos($img, "uploads/products/") === 0) {
        $candidate = $img;
    } else {
        // likely a filename only
        $candidate = "uploads/products/" . basename($img);
    }

    // Try a few relative locations to handle subfolder dashboard files.
    $attempts = [
        $candidate,                      // relative to current file: uploads/products/xxx
        "../" . $candidate,              // one level up: ../uploads/products/xxx
        "../../" . $candidate,           // two levels up
        rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/' . $candidate // absolute server path
    ];

    foreach ($attempts as $p) {
        // If absolute server path
        if (strpos($p, $_SERVER['DOCUMENT_ROOT']) === 0) {
            if (file_exists($p)) {
                // convert to web path relative to document root
                return substr($p, strlen(rtrim($_SERVER['DOCUMENT_ROOT'], '/')) + 1);
            }
            continue;
        }

        // relative path check (relative to current PHP file)
        if (file_exists($p)) {
            return $p;
        }

        // If path looks like ../uploads/products/... but exists relative to document root
        $abs = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/' . ltrim($p, '/');
        if (file_exists($abs)) {
            return ltrim($p, '/');
        }
    }

    // last-resort: try trimming and checking only filename at document root uploads/products/
    $final = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/uploads/products/' . basename($img);
    if (file_exists($final)) {
        return 'uploads/products/' . basename($img);
    }

    return $default;
}

// ========== CATEGORY FETCH =========
$catQuery = $conn->query("
    SELECT DISTINCT category 
    FROM products 
    WHERE is_deleted = 0 AND is_active = 1
    ORDER BY category ASC
");

$categories = [];
while ($row = $catQuery->fetch_assoc()) {
    $categories[] = $row['category'];
}

// ========== ALL PRODUCTS FETCH =========
$prodQuery = $conn->query("
    SELECT * FROM products 
    WHERE is_deleted = 0 AND is_active = 1 
    ORDER BY id DESC
");

$allProducts = [];
while ($row = $prodQuery->fetch_assoc()) {
    $allProducts[] = $row;
}

// safe slugify (no iconv)
function slugify($text)
{
    // Replace non-alphanumeric with dashes
    $text = preg_replace('/[^A-Za-z0-9]+/', '-', $text);
    $text = trim($text, '-');
    return strtolower($text);
}

?>

<style>
/* Make checkboxes visible (theme hides them) */
.sec-check {
    display: inline-block !important;
    opacity: 1 !important;
    visibility: visible !important;
    width: 16px;
    height: 16px;
    accent-color: #007bff;
}

/* Align checkbox + text in one line */
.form-check {
    display: flex !important;
    align-items: center !important;
    gap: 8px !important;
    margin: 0;
    padding: 0;
}

.form-check-label {
    cursor: pointer;
    margin: 0;
    padding: 0;
    line-height: 1.3 !important;
}
</style>



<div class="dashboard-main-body nft-page">

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
        <!-- Bind the username dynamically.. -->
        <?php if (isset($_SESSION['username'])): ?>
            <h6 class="fw-semibold mb-0">Hello <?php echo $_SESSION['username']; ?>!</h6>
        <?php else: ?>
            <h6 class="fw-semibold mb-0">Hello none!</h6>
        <?php endif; ?>

        <button class="btn  rounded-pill px-20 py-8 lufera-bg" id="openSectionModal">
            + Create Section
        </button>

        <!-- <h6 class="fw-semibold mb-0">Dashboard</h6>
        <ul class="d-flex align-items-center gap-2">
            <li class="fw-medium">
                <a href="index.php" class="d-flex align-items-center gap-1 hover-text-primary">
                    <iconify-icon icon="solar:home-smile-angle-outline" class="icon text-lg"></iconify-icon>
                    Dashboard
                </a>
            </li>
            <li>-</li>
            <li class="fw-medium">NFT & Gaming </li>
        </ul> -->
    </div>

    <div class="row gy-4">
        <div class="col-xxl-8">
            <div class="row gy-4">
                <div class="col-12" id="section-banner">
                    <div class="nft-promo-card card radius-12 overflow-hidden position-relative z-1">
                        <img src="assets/images/back3.jpg" class="position-absolute start-0 top-0 w-100 h-100 z-n1" alt="">
                        <div class="nft-promo-card__inner d-flex align-items-center">
                            <div class="nft-promo-card__thumb w-100">
                                <img src="assets/images/dash.jpg" alt="" class="w-100 h-100 object-fit-cover">
                            </div>
                            <div class="flex-grow-1">
                                <h4 class="mb-16 text-white" style="color: black !important">Discover Our Lufera Marketplace</h4>
                                <p class="text-white text-md" style="color: black !important">Your one-stop destination for unique products and unbeatable deals.
                                Browse a vibrant marketplace filled with trusted sellers and handpicked selections—all in one place.</p>
                                <!-- <div class="d-flex align-items-center flex-wrap mt-24 gap-16">
                                    <a href="#" class="btn rounded-pill border br-white text-white radius-8 px-32 py-11 hover-bg-white text-hover-neutral-900">Explore</a>
                                    <a href="#" class="btn rounded-pill btn-primary-600 radius-8 px-28 py-11">Create Now</a>
                                </div> -->
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12" id="section-stats">
                    <h6 class="mb-16">Trending Stats</h6>

                    <div class="row gy-4">

                        <!-- ================= CARD 1: TOTAL SUBSCRIPTIONS ================= -->
                        <div class="col-lg-4 col-sm-6">
                            <div class="card px-24 py-16 radius-12 border h-100"
                                style="background:#f0f6ff; border:1px solid #dbe4f3;"> <!-- Soft blue tint -->
                                <div class="card-body p-0">
                                    <div class="d-flex gap-16 align-items-center">

                                        <span class="w-40-px h-40-px rounded-circle d-flex justify-content-center align-items-center"
                                            style="background:#dce7ff; color:#111827;"> <!-- Light blue circle + black icon -->
                                            <iconify-icon icon="flowbite:users-group-solid" class="icon"></iconify-icon>
                                        </span>

                                        <div class="flex-grow-1">
                                            <h6 class="fw-semibold mb-0"><?= $sub_total ?></h6>
                                            <span class="fw-medium text-secondary-light text-md">Total Subscriptions</span>

                                            <p class="text-sm mb-0 mt-12 d-flex align-items-center gap-12">
                                                <span class="px-6 py-2 rounded-2 fw-medium d-flex align-items-center gap-8"
                                                    style="background:#e7f9e7; color:#166534;">
                                                    +<?= $sub_week ?>
                                                    <i class="ri-arrow-up-line"></i>
                                                </span>
                                                This week
                                            </p>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ================= CARD 2: TOTAL ORDERS ================= -->
                        <div class="col-lg-4 col-sm-6">
                            <div class="card px-24 py-16 radius-12 border h-100"
                                style="background:#fffbea; border:1px solid #f5e8ae;"> <!-- Soft yellow warm tint -->
                                <div class="card-body p-0">
                                    <div class="d-flex gap-16 align-items-center">

                                        <span class="w-40-px h-40-px rounded-circle d-flex justify-content-center align-items-center"
                                            style="background:#fff1b8; color:#111827;"> <!-- Yellow circle + black icon -->
                                            <iconify-icon icon="solar:cart-5-bold" class="icon"></iconify-icon>
                                        </span>

                                        <div class="flex-grow-1">
                                            <h6 class="fw-semibold mb-0"><?= $order_total ?></h6>
                                            <span class="fw-medium text-secondary-light text-md">Total Orders</span>

                                            <p class="text-sm mb-0 mt-12 d-flex align-items-center gap-12">
                                                <span class="px-6 py-2 rounded-2 fw-medium d-flex align-items-center gap-8"
                                                    style="background:#ffe2e2; color:#b91c1c;">
                                                    +<?= $order_week ?>
                                                    <i class="ri-arrow-up-line"></i>
                                                </span>
                                                This week
                                            </p>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ================= CARD 3: TOTAL USERS ================= -->
                        <div class="col-lg-4 col-sm-6">
                            <div class="card px-24 py-16 radius-12 border h-100"
                                style="background:#f9fafb; border:1px solid #e5e7eb;"> <!-- Light gray neutral theme -->
                                <div class="card-body p-0">
                                    <div class="d-flex gap-16 align-items-center">

                                        <span class="w-40-px h-40-px rounded-circle d-flex justify-content-center align-items-center"
                                            style="background:#e8e9ec; color:#111827;"> <!-- Neutral circle + black icon -->
                                            <iconify-icon icon="solar:user-rounded-bold" class="icon"></iconify-icon>
                                        </span>

                                        <div class="flex-grow-1">
                                            <h6 class="fw-semibold mb-0"><?= $user_total ?></h6>
                                            <span class="fw-medium text-secondary-light text-md">Total Users</span>

                                            <p class="text-sm mb-0 mt-12 d-flex align-items-center gap-12">
                                                <span class="px-6 py-2 rounded-2 fw-medium d-flex align-items-center gap-8"
                                                    style="background:#e7f9e7; color:#166534;">
                                                    +<?= $user_week ?>
                                                    <i class="ri-arrow-up-line"></i>
                                                </span>
                                                This week
                                            </p>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="col-12" id="section-products">
                    <!-- TITLE + CATEGORY FILTERS -->
                    <div class="mb-16 mt-8 d-flex flex-wrap justify-content-between gap-16 align-items-center">
                        <h6 class="mb-0">Available Products</h6>

                        <ul class="nav button-tab nav-pills mb-16 gap-12">

                            <li class="nav-item">
                                <button class="nav-link active fw-semibold text-secondary-light rounded-pill px-20 py-6 border"
                                        data-bs-toggle="pill" data-bs-target="#tab-all">All</button>
                            </li>

                            <?php foreach ($categories as $cat): ?>
                                <?php $slug = slugify($cat); ?>
                                <li class="nav-item">
                                    <button class="nav-link fw-semibold text-secondary-light rounded-pill px-20 py-6 border"
                                            data-bs-toggle="pill" data-bs-target="#tab-<?= $slug ?>">
                                        <?= htmlspecialchars($cat) ?>
                                    </button>
                                </li>
                            <?php endforeach; ?>

                        </ul>
                    </div>

                    <!-- TAB CONTENT -->
                    <div class="tab-content">

                        <!-- ========== ALL PRODUCTS TAB ========== -->
                        <div class="tab-pane fade show active" id="tab-all">
                            <div class="row g-3">

                                <?php if (empty($allProducts)): ?>
                                    <div class="col-12 text-center text-secondary-light py-20">
                                        No products found.
                                    </div>
                                <?php endif; ?>

                                <?php foreach ($allProducts as $p): ?>
                                    <?php $img = productImage($p['product_image']); ?>

                                    <div class="col-xxl-3 col-sm-6 col-xs-6">

                                        <div class="nft-card bg-base radius-16 overflow-hidden p-0"
                                            style="height:100%; display:flex; flex-direction:column;">

                                            <!-- IMAGE — EXTRA SMALL (110px) -->
                                            <div style="height:110px;" class="overflow-hidden radius-16">
                                                <img src="<?= $img ?>"
                                                    class="w-100 h-100 object-fit-cover"
                                                    onerror="this.src='assets/images/default-product.png'">
                                            </div>

                                            <!-- CONTENT -->
                                            <div class="p-10 d-flex flex-column" style="flex:1;">

                                                <h6 class="text-md fw-bold text-primary-light mb-1">
                                                    <?= htmlspecialchars($p['title']) ?>
                                                </h6>

                                                <?php if (!empty($p['subtitle'])): ?>
                                                    <p class="text-xs text-secondary-light mb-1">
                                                        <?= htmlspecialchars($p['subtitle']) ?>
                                                    </p>
                                                <?php endif; ?>

                                                <div class="mt-8 d-flex justify-content-between text-sm">
                                                    <span class="text-secondary-light">
                                                        Price:
                                                        <span class="text-primary-light fw-semibold">
                                                            ₹<?= number_format($p['price']) ?>
                                                        </span>
                                                    </span>

                                                    <span class="text-primary-600 fw-semibold">
                                                        <?= htmlspecialchars($p['category']) ?>
                                                    </span>
                                                </div>

                                                <div class="d-flex gap-8 mt-12">
                                                    <a href="product-details.php?id=<?= $p['id'] ?>"
                                                    class="btn rounded-pill border text-neutral-500 px-12 py-6 flex-grow-1">
                                                        View
                                                    </a>
                                                    <a href="buy.php?id=<?= $p['id'] ?>"
                                                    class="btn rounded-pill btn-primary-600 px-12 py-6 flex-grow-1">
                                                        Buy
                                                    </a>
                                                </div>

                                            </div>
                                        </div>
                                    </div>

                                <?php endforeach; ?>

                            </div>
                        </div>

                        <!-- ========== CATEGORY TABS ========== -->
                        <?php foreach ($categories as $cat): ?>
                            <?php $slug = slugify($cat); ?>

                            <div class="tab-pane fade" id="tab-<?= $slug ?>">
                                <div class="row g-3">

                                    <?php
                                    $filtered = array_filter($allProducts, fn($p) => $p['category'] == $cat);
                                    ?>

                                    <?php if (empty($filtered)): ?>
                                        <div class="col-12 text-center py-20 text-secondary-light">
                                            No products found in <?= htmlspecialchars($cat) ?>.
                                        </div>
                                    <?php endif; ?>

                                    <?php foreach ($filtered as $p): ?>
                                        <?php $img = productImage($p['product_image']); ?>

                                        <div class="col-xxl-3 col-sm-6 col-xs-6">

                                            <div class="nft-card bg-base radius-16 overflow-hidden p-0"
                                                style="height:100%; display:flex; flex-direction:column;">

                                                <div style="height:110px;" class="overflow-hidden radius-16">
                                                    <img src="<?= $img ?>"
                                                        class="w-100 h-100 object-fit-cover"
                                                        onerror="this.src='assets/images/default-product.png'">
                                                </div>

                                                <div class="p-10 d-flex flex-column" style="flex:1;">

                                                    <h6 class="text-md fw-bold text-primary-light mb-1">
                                                        <?= htmlspecialchars($p['title']) ?>
                                                    </h6>

                                                    <?php if (!empty($p['subtitle'])): ?>
                                                        <p class="text-xs text-secondary-light mb-1">
                                                            <?= htmlspecialchars($p['subtitle']) ?>
                                                        </p>
                                                    <?php endif; ?>

                                                    <div class="mt-8 d-flex justify-content-between text-sm">
                                                        <span class="text-secondary-light">
                                                            Price:
                                                            <span class="text-primary-light fw-semibold">
                                                                ₹<?= number_format($p['price']) ?>
                                                            </span>
                                                        </span>

                                                        <span class="text-primary-600 fw-semibold">
                                                            <?= htmlspecialchars($cat) ?>
                                                        </span>
                                                    </div>

                                                    <div class="d-flex gap-8 mt-12">
                                                        <a href="product-details.php?id=<?= $p['id'] ?>"
                                                        class="btn rounded-pill border text-neutral-500 px-12 py-6 flex-grow-1">
                                                            View
                                                        </a>
                                                        <a href="buy.php?id=<?= $p['id'] ?>"
                                                        class="btn rounded-pill btn-primary-600 px-12 py-6 flex-grow-1">
                                                            Buy
                                                        </a>
                                                    </div>

                                                </div>
                                            </div>
                                        </div>

                                    <?php endforeach; ?>

                                </div>
                            </div>

                        <?php endforeach; ?>

                    </div>
                </div>
            </div>
        </div>

        <div class="col-xxl-4">
            <div class="row gy-4">
                <div class="col-xxl-12 col-md-6" id="section-recentOrders">
                    <div class="card h-100 shadow-sm radius-12 border-0" style="overflow:hidden;">
                        
                        <div class="card-header border-bottom-0 py-16 px-24 d-flex align-items-center justify-content-between"
                            style="background:#f8fafc;">
                            <h6 class="fw-bold text-lg mb-0">Recent Orders</h6>
                        </div>

                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table mb-0 align-middle" 
                                    style="border-collapse: separate; border-spacing: 0 6px;">

                                    <thead>
                                        <tr class="text-secondary-light text-sm">
                                            <th class="px-24 py-12">Order ID</th>
                                            <th class="py-12">User</th>
                                            <th class="py-12">Amount</th>
                                            <th class="py-12">Status</th>
                                            <th class="py-12 pe-24">Date</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        <?php 
                                            $orders = $conn->query("SELECT * FROM orders ORDER BY id DESC");
                                            if ($orders->num_rows > 0):
                                                while ($o = $orders->fetch_assoc()):
                                        ?>

                                        <tr class="bg-white shadow-xs radius-8"
                                            style="transition:0.2s; cursor:pointer;"
                                            onmouseover="this.style.transform='scale(1.01)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.06)'"
                                            onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='none'">

                                            <td class="px-24 py-14 fw-semibold">#<?= $o['id'] ?></td>
                                            <td class="fw-medium"><?= $o['username'] ?? 'N/A' ?></td>
                                            <td class="fw-semibold text-primary-600"><?= $o['amount'] ?? '0' ?></td>

                                            <td>
                                                <?php 
                                                    $status = strtolower($o['status'] ?? 'pending');
                                                    $badgeColor = [
                                                        'pending' => 'bg-warning-100 text-warning-700',
                                                        'success' => 'bg-success-100 text-success-700',
                                                        'failed'  => 'bg-danger-100 text-danger-700',
                                                    ][$status] ?? 'bg-neutral-200 text-neutral-700';
                                                ?>
                                                <span class="px-12 py-4 rounded-pill fw-semibold text-sm <?= $badgeColor ?>">
                                                    <?= ucfirst($status) ?>
                                                </span>
                                            </td>

                                            <td class="pe-24"><?= $o['created_on'] ?></td>
                                        </tr>

                                        <?php 
                                                endwhile;
                                            else:
                                        ?>
                                        <tr>
                                            <td colspan="5" class="text-center py-20 text-secondary-light">
                                                No orders found.
                                            </td>
                                        </tr>
                                        <?php endif; ?>
                                    </tbody>

                                </table>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="col-xxl-12 col-md-12" id="section-users">
                    <div class="card h-100 shadow-sm radius-12 border-0">

                        <!-- UPDATED TITLE -->
                        <div class="card-header border-bottom-0 py-16 px-24 d-flex align-items-center justify-content-between" style="background:#f8fafc;">
                            <h6 class="fw-bold text-lg mb-0">Our Users</h6>
                            <a href="#" class="text-primary-600 hover-text-primary d-flex align-items-center gap-1">
                                View All
                                <iconify-icon icon="solar:alt-arrow-right-linear" class="icon"></iconify-icon>
                            </a>
                        </div>

                        <div class="card-body p-24">
                            <div class="row gy-4">

                                <?php
                                    // Fetch latest users
                                    $users = $conn->query("SELECT * FROM users ORDER BY id DESC LIMIT 6");

                                    // FIXED: Google, Facebook, Local, Uploads photos
                                    function userPhoto($value)
                                    {
                                        $default = "assets/images/default-user.png";

                                        if (!$value) return $default;

                                        // ⭐ FIX: Remove invisible spaces, newlines, tabs
                                        $value = trim($value);

                                        // 1. Valid full URL (Google, Facebook, any HTTP/HTTPS)
                                        if (filter_var($value, FILTER_VALIDATE_URL)) {
                                            return $value;
                                        }

                                        // 2. Google photos missing protocol
                                        if (strpos($value, '//lh3.googleusercontent.com') === 0) {
                                            return "https:" . $value;
                                        }

                                        // 3. Facebook photos missing protocol
                                        if (strpos($value, '//graph.facebook.com') === 0) {
                                            return "https:" . $value;
                                        }

                                        // 4. Local assets folder (assets/test.jpg)
                                        if (strpos($value, "assets/") === 0) {
                                            return $value;
                                        }

                                        // 5. Local uploads
                                        $clean = ltrim($value, '/');
                                        $absolute = $_SERVER['DOCUMENT_ROOT'] . '/' . $clean;

                                        if (file_exists($absolute)) {
                                            return $clean;
                                        }

                                        // 6. Default placeholder
                                        return $default;
                                    }

                                    if ($users->num_rows > 0):
                                        while ($u = $users->fetch_assoc()):
                                            $photo = userPhoto($u['photo']);
                                ?>

                                <!-- USER CARD -->
                                <div class="col-12">
                                    <div class="d-flex bg-white radius-12 border shadow-sm p-16 align-items-center"
                                        style="border-color:#e1e5eb; min-height:120px;">

                                        <!-- USER PHOTO (REDUCED SIZE) -->
                                        <div class="me-20">
                                            <img src="<?= htmlspecialchars($photo) ?>"
                                                class="rounded-4 shadow-sm object-fit-cover"
                                                style="width:70px; height:70px;"
                                                alt="User Photo">
                                        </div>

                                        <!-- USER DETAILS -->
                                        <div class="flex-grow-1">

                                            <h6 class="fw-semibold mb-2" style="font-size:13px;">
                                                <?= htmlspecialchars($u['username']) ?>
                                            </h6>

                                            <p class="text-secondary-light mb-6" style="font-size:11px;">
                                                <?= htmlspecialchars($u['email']) ?>
                                            </p>

                                            <!-- USER TAGS -->
                                            <div class="d-flex align-items-center gap-2 mb-4">

                                                <span class="px-10 py-3 bg-primary-50 text-primary-600 rounded-pill fw-semibold"
                                                    style="font-size:10px;">
                                                    ID: <?= $u['id'] ?>
                                                </span>

                                                <span class="px-10 py-3 bg-neutral-100 text-neutral-700 rounded-pill fw-semibold"
                                                    style="font-size:10px;">
                                                    Joined: <?= date("M d, Y", strtotime($u['created_at'])) ?>
                                                </span>

                                            </div>

                                        </div>

                                    </div>
                                </div>

                                <?php endwhile; else: ?>

                                <!-- NO USERS FOUND -->
                                <div class="col-12 text-center text-secondary-light py-20">
                                    No users found.
                                </div>

                                <?php endif; ?>

                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- ===================== MODAL START ===================== -->
<div class="modal fade" id="sectionModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content radius-12 p-20">

      <h5 class="fw-semibold mb-16" style="font-size: 1.25rem !important">+ Add Section</h5>

      <div class="d-flex flex-column gap-12">

      <div class="form-check">
          <input class="form-check-input sec-check" type="checkbox" value="banner" id="chkStats1" checked>
          <label class="form-check-label" for="chkStats1">Lufera Banner</label>
        </div>

        <div class="form-check">
          <input class="form-check-input sec-check" type="checkbox" value="stats" id="chkStats" checked>
          <label class="form-check-label" for="chkStats">Trending Stats</label>
        </div>

        <div class="form-check">
          <input class="form-check-input sec-check" type="checkbox" value="products" id="chkProducts" checked>
          <label class="form-check-label" for="chkProducts">Available Products</label>
        </div>

        <div class="form-check">
          <input class="form-check-input sec-check" type="checkbox" value="recentOrders" id="chkRecentOrders" checked>
          <label class="form-check-label" for="chkRecentOrders">Recent Orders</label>
        </div>

        <div class="form-check">
          <input class="form-check-input sec-check" type="checkbox" value="users" id="chkUsers" checked>
          <label class="form-check-label" for="chkUsers">Our Users</label>
        </div>

      </div>

      <div class="mt-20 d-flex justify-content-end gap-12">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button class="btn lufera-bg" id="applySections">Apply</button>
      </div>

    </div>
  </div>
</div>
<!-- ===================== MODAL END ===================== -->

<?php include './partials/layouts/layoutBottom.php' ?>