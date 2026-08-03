<?php
    include '../../partials/connection.php';
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
    include '../head.php';
    include '../scripts.php';
    session_start();
    $isLoggedIn = isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0;
    $query = mysqli_query($conn,"
    SELECT *
    FROM products
    WHERE is_active='1'
    AND is_deleted='0'
    ORDER BY created_at DESC");

    $products = [];
    while($row = mysqli_fetch_assoc($query)){
        $products[] = $row;
    }
    $marketplaceQuery = mysqli_query($conn,"
    SELECT
        m.*,
        c.cat_name,
        c.cat_des
    FROM marketplace m
    LEFT JOIN categories c
        ON c.cat_id = m.cat_id
    ORDER BY m.id ASC
    ");
?>
<html>
<head>
<link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.0.13/css/all.css" integrity="sha384-DNOHZ68U8hZfKXOrtjWvjxusGo9WQnrNx2sqG0tfsghAvtVlRW3tvkXWZh58N9jp" crossorigin="anonymous">
<link href="https://fonts.googleapis.com/css?family=Oswald:500" rel="stylesheet">
<script>!function(e){"undefined"==typeof module?this.charming=e:module.exports=e}(function(e,n){"use strict";n=n||{};var t=n.tagName||"span",o=null!=n.classPrefix?n.classPrefix:"char",r=1,a=function(e){
for (var n = e.parentNode, a = e.nodeValue, c = a.length, l = -1; ++l < c;) {

// Don't create a span for spaces
if (a[l] === " ") {
    n.insertBefore(document.createTextNode(" "), e);
    continue;
}

var d = document.createElement(t);

if (o) {
    d.className = o + r;
    r++;
}

d.appendChild(document.createTextNode(a[l]));
n.insertBefore(d, e);
}
n.removeChild(e)};return function c(e){for(var n=[].slice.call(e.childNodes),t=n.length,o=-1;++o<t;)c(n[o]);e.nodeType===Node.TEXT_NODE&&a(e)}(e),e});
</script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/Swiper/4.4.1/css/swiper.min.css">
    <style>
section {
    width: 100%;
}

.swiper-container {
    width: 100%;
    height: 550px;
}
.package-prev{
    left:-20px;
}

.package-next{
    right:-20px;
}
.slide {
    display: flex;
    justify-content: center;
    align-items: center;
    position: relative;
    text-align: center;
    font-size: 18px;
    background: #fff;
    overflow: hidden;
}

.slide-image {
    position: absolute;
    top: -200px;
    left: -200px;
    width: calc(100% + 400px);
    height: calc(100% + 400px);
    background-position: 50% 50%;
    background-size: cover;
}

.slide-title {
    font-size: 4rem;
    line-height: 1;
    max-width: 50%;
    white-space: normal;
    word-break: break-word;
    color: #FFF;
    z-index: 100;
    font-family: 'Oswald', sans-serif;
    text-transform: uppercase;
    font-weight: normal;
}

@media (min-width: 45em) {
    .slide-title {
        font-size: 4vw;
        max-width: none;
    }
}

.slide-title span {
    white-space: pre;
    display: inline-block;
    opacity: 0;
}

.slideshow {
    position: relative;
}

.slideshow-pagination {
    position: absolute;
    bottom: 5rem;
    left: 0;
    width: 100%;
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    align-items: center;
    transition: .3s opacity;
    z-index: 10;
}

.slideshow-pagination-item {
    display: flex;
    align-items: center;
}

.slideshow-pagination-item .pagination-number {
    opacity: 0.5;
}

.slideshow-pagination-item:hover,
.slideshow-pagination-item:focus {
    cursor: pointer;
}

.slideshow-pagination-item:last-of-type .pagination-separator {
    width: 0;
}

.slideshow-pagination-item.active .pagination-number {
    opacity: 1;
}

.slideshow-pagination-item.active .pagination-separator {
    width: 10vw;
}

.slideshow-navigation-button {
    position: absolute;
    top: 0;
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100%;
    width: 5rem;
    z-index: 1000;
    transition: all .3s ease;
    color: #FFF;
}

.slideshow-navigation-button:hover,
.slideshow-navigation-button:focus {
    cursor: pointer;
    background: rgba(0, 0, 0, 0.5);
}

.slideshow-navigation-button.prev {
    left: 0;
}

.slideshow-navigation-button.next {
    right: 0;
}

.pagination-number {
    font-size: 1.8rem;
    color: #FFF;
    font-family: 'Oswald', sans-serif;
    padding: 0 0.5rem;
}

.pagination-separator {
    display: none;
    position: relative;
    width: 40px;
    height: 2px;
    background: rgba(255, 255, 255, 0.25);
    transition: all .3s ease;
}

@media (min-width: 45em) {
    .pagination-separator {
        display: block;
    }
}

.pagination-separator-loader {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: #FFF;
    transform-origin: 0 0;
}
    </style>
    <style>
        .hero-banner{
            width:100%;
            height:450px;
            background:url('../../assets/images/banner_bg.jpg') center center/cover no-repeat;
            overflow:hidden;
            position:relative;
        }

        .hero-overlay{
            width:100%;
            height:100%;
            display:flex;
            justify-content:center;
            align-items:center;
            text-align:center;
            padding:20px;
        }

        .hero-content h1{
            font-size:52px !important;
            font-weight:400;
            margin-bottom:15px;
        }

        .hero-content p{
            font-size:18px;
            color:#000;
            margin-bottom:30px;
            line-height:1.7;
        }

        .hero-buttons{
            display:flex;
            justify-content:center;
            gap:15px;
        }

        .hero-buttons .btn{
            padding:12px 35px;
            border-radius:50px;
            font-weight:600;
        }

        @media(max-width:768px){
            .hero-banner{
                height:320px;
            }

            .hero-content h1{
                font-size:34px;
            }

            .hero-content p{
                font-size:15px;
            }

            .hero-buttons{
                flex-direction:column;
                align-items:center;
            }

            .hero-buttons .btn{
                width:220px;
            }
        }
        .heading-block{
            display:flex;
            flex-direction:column;
        }

        .heading-block h2{
            margin:0;
        }

        .heading-block h5{
            margin:5px 0 0;
            font-size:16px;
            font-weight:400;
            color:#666;
        }
        .slider-header{
            display:flex;
            justify-content:space-between;
            align-items:flex-start;
            margin-bottom:20px;
            padding:24px;
            background:#fff;
            border-radius:6px;
        }
        .slider-header h2{
            font-size: 30px !important;
            font-weight: 400;
        }

        .slider-header div{
            display:flex;
            gap:10px;
        }

        .header-nav{
            width:45px;
            height:45px;
            border:none;
            border-radius:50%;
            background:#009688;
            color:#fff;
            cursor:pointer;
            font-size:20px;
            display:flex;
            align-items:center;
            justify-content:center;
        }

        .product-banner{
            width:100%;
            height:220px;
            object-fit:cover;
        }

        .product-info{
            display:flex;
            align-items:flex-start;
            gap:12px;
            padding:12px;
        }

        .product-thumb{
            width:100px !important;
            height:100px !important;
            border-radius:8px;
            object-fit:cover;
            flex-shrink:0;
            border:1px solid #ddd;
        }

        .product-text{
            flex:1;
        }

        .product-text h6{
            margin:0;
            font-size:16px;
            font-weight:600;
        }

        .product-text p{
            margin:5px 0;
            color:#666;
            font-size:14px;
        }

        .product-text .price{
            padding:0;
            margin-top:6px;
            font-weight:600;
            color:#009688;
        }
        .slider-wrapper{
            position:relative;
            width:100%;
        }

        .product-slider{
            display:flex;
            gap:20px;
            overflow:hidden;
            scroll-behavior:smooth;
        }

        /* Show exactly 3 cards */
        .product-card{
            flex:0 0 calc((100% - (2 * 20px)) / 3);
            background:#fff;
            border-radius:12px;
            overflow:hidden;
            height:100%;
        }

        .product-card img{
            width:100%;
            height:220px;
            object-fit:cover;
        }

        .product-card h3{
            padding:10px;
            margin:0;
        }

        .price{
            font-weight:bold;
            color:#009688;
        }

        .btn{
            display:block;
            margin:10px;
            padding:10px;
            text-align:center;
            background:#009688;
            color:#fff;
            text-decoration:none;
            border-radius:6px;
        }

        .nav{
            position:absolute;
            top:50%;
            transform:translateY(-50%);
            width:40px;
            height:40px;
            border:none;
            border-radius:50%;
            background:#fff;
            box-shadow:0 2px 8px rgba(0,0,0,.2);
            cursor:pointer;
            z-index:10;
            font-size:20px;
        }

        .prev{
            left:-20px;
            justify-content:center;
        }

        .next{
            right:-20px;
            justify-content:center;

        }

    /* Slider 2 */
    .slider-header{
        display:flex;
        justify-content:space-between;
        align-items:flex-start;
        margin-bottom:20px;
    }

    .top-slider{
        display:flex;
        gap:20px;
        overflow:hidden;
        scroll-behavior:smooth;
    }

    .feed-card{
        flex:0 0 calc((100% - 40px)/3);
        background:#fff;
        border-radius:10px;
        overflow:hidden;
    }

    .feed-card img{
        width:100%;
        height:220px;
        object-fit:cover;
    }

    .feed-content{
        padding:15px;
    }

    /* Slider 3 */
    .featured-slider{
        display:flex;
        overflow:hidden;
        scroll-behavior:smooth;
    }

    .featured-items{
        min-width:100%;
        display:flex;
        background:#fff;
        border-radius:6px;
        overflow:hidden;
        box-shadow:0 4px 10px rgba(0,0,0,.1);
    }

    .featured-image{
        width:50%;
    }

    .featured-image img{
        width:100%;
        height:420px;
        object-fit:cover;
    }

    .featured-content{
        width:50%;
        display:flex;
        flex-direction:column;
        justify-content:center;
    }
    .product-grid{
    display:grid;
    grid-template-columns:repeat(3,1fr);
    gap:25px;
    }

    .grid-card{
        background:#fff;
        border-radius:10px;
        overflow:hidden;
        box-shadow:0 3px 12px rgba(0,0,0,.08);
        transition:.3s;
        display:flex;
        flex-direction:column;
    }

    .grid-card:hover{
        transform:translateY(-5px);
        box-shadow:0 8px 25px rgba(0,0,0,.15);
    }

    .grid-card img{
        width:100%;
        height:220px;
        object-fit:cover;
    }

    .grid-content{
        padding:18px;
        flex:1;
        flex-direction:column;
    }

    .grid-content h5{
        font-size:18px;
        margin-bottom:10px;
        font-weight:600;
    }

    .grid-content p{
        color:#666;
        font-size:14px;
        flex:1;
    }

    .grid-content .price{
        color:#009688;
        font-size:20px;
        font-weight:bold;
    }
    @media (min-width: 992px) {
        .navbar-expand-lg .navbar-collapse {
            justify-content: end;
        }
    }



/* =======================
   Marketplace Packages
======================= */
.package-grid{
    display:flex;
    gap:25px;
    overflow:hidden;
    scroll-behavior:smooth;
}

.package-card{
    flex:0 0 calc((100% - 50px)/3);
    position:relative;
    background:#fff;
    border-radius:20px;
    overflow:hidden;
    padding:30px;
    box-shadow:0 8px 25px rgba(0,0,0,.08);
    display:flex;
    flex-direction:column;
    transition:.3s;
}
.package-card:hover{
    transform:translateY(-5px);
}
.package-card.dark{
    background:#1e8a8a;
    color:#fff;
}
.package-title{
    font-size:30px;
    font-weight:700;
    margin-bottom:8px;
}
.package-subtitle{
    font-size:15px;
    margin-bottom:20px;
    color:#666;
}
.package-card.dark .package-subtitle{
    color:#fff;
}
.old-price{
    text-decoration:line-through;
    opacity:.6;
    margin-bottom:6px;
}
.package-price{
    font-size:36px;
    font-weight:700;
    margin-bottom:25px;
}
.package-price span{
    font-size:18px;
    font-weight:500;
}
.package-btn{
    width:100%;
    padding:10px;
    border-radius:10px;
    border:none;
    font-weight:600;
    margin-bottom:25px;
    transition:.3s;
}
.package-card:not(.dark) .package-btn{
    background:#fff;
    border:2px solid #1e8a8a;
    color:#1e8a8a;
}

.package-card.dark .package-btn{
    background:#fff;
    color:#1e8a8a;
}
.package-btn:hover{
    transform:scale(1.02);
}
.package-features{
    list-style:none;
    padding:0;
    margin:0;
}
.package-features li{
    display:flex;
    gap:12px;
    margin-bottom:14px;
}
.package-icon{
    width:22px;
    height:22px;
    border-radius:50%;
    display:flex;
    align-items:center;
    justify-content:center;
    background:#1e8a8a;
    color:#fff;
    font-size:12px;
    flex-shrink:0;
}
.package-card.dark .package-icon{
    background:#fff;
    color:#1e8a8a;
}
.package-inactive{
    position:absolute;
    top:18px;
    right:18px;
    background:#dc3545;
    color:#fff;
    padding:4px 12px;
    border-radius:20px;
    font-size:12px;
}

.view-more-btn{
    display:inline-block;
    margin-top:15px;
    color:#1e8a8a;
    font-weight:600;
    text-decoration:none;
}
.package-card.dark .view-more-btn{
    color:#fff;
}
.badge{
background: #009688;
}

.marketplace-features{
    padding:80px 0 0;
    background:#f8f9fc;
}

.feature-card{
    background:#fff;
    padding:50px 35px;
    text-align:center;
    border-radius:12px;
    box-shadow:0 8px 25px rgba(0,0,0,.08);
    transition:.3s;
    height:100%;
}

.feature-card:hover{
    transform:translateY(-8px);
    box-shadow:0 15px 35px rgba(0,0,0,.12);
}

.feature-icon{
    width:90px;
    height:90px;
    margin:0 auto 25px;
    border-radius:50%;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:36px;
    color:#fff;
}

.icon-blue{
    background:linear-gradient(135deg,#00b4db,#0083b0);
}

.icon-green{
    background:linear-gradient(135deg,#00c853,#43a047);
}

.icon-purple{
    background:linear-gradient(135deg,#8e2de2,#4a00e0);
}

.feature-card h3{
    font-size:30px !important;
    font-weight:400;
    margin-bottom:18px;
    color:#24336b;
}

.feature-card p{
    color:#666;
    font-size:16px;
    line-height:1.8;
}
.notification-icon{
    background: #009688;
    padding: 15px;
    border-radius: 50%;
    color: #fff;
}
/* ==========================================
   LARGE TABLETS
========================================== */
@media (max-width: 991px){

.container{
    padding-left:15px;
    padding-right:15px;
}

/* Header */

.navbar-brand img{
    height:25px !important;
    width:  200px !important;
}

.navbar-collapse{
    margin-top:15px;
    background:#fff;
    border-radius:10px;
    padding:15px;
    box-shadow:0 8px 25px rgba(0,0,0,.08);
}

.navbar-nav{
    width:100%;
}

.navbar-nav .nav-link{
    padding:12px;
    text-align:center;
}

.navbar-collapse .d-flex{
    justify-content:center;
    margin-top:15px;
}

/* Banner */

.swiper-container{
    height:420px;
}

.slide-title{
    font-size:42px;
    max-width:90%;
}

/* Features */

.feature-card{
    padding:35px 25px;
}

.feature-card h3{
    font-size:24px !important;
}

/* Slider Header */

.slider-header{
    flex-direction:column;
    gap:20px;
    align-items:flex-start;
}

.slider-header>div:last-child{
    width:100%;
    justify-content:flex-end;
}

/* Product Slider */

.product-card{
    flex:0 0 calc((100% - 20px)/2);
}

.feed-card{
    flex:0 0 calc((100% - 20px)/2);
}

.package-card{
    flex:0 0 calc((100% - 20px)/2);
}

.product-grid{
    grid-template-columns:repeat(2,1fr);
}

/* Featured */

.featured-items{
    flex-direction:column;
}

.featured-image,
.featured-content{
    width:100%;
}

.featured-image img{
    height:320px;
}

.featured-content{
    padding:25px;
}

}

/* ==========================================
   MOBILE
========================================== */

@media (max-width:767px){

.container{
    padding-left:12px;
    padding-right:12px;
}

/* Banner */

.swiper-container{
    height:300px;
}

.slide-title{
    font-size:28px;
    line-height:1.3;
    max-width:90%;
}

.slideshow-navigation-button{
    width:45px;
}

.slideshow-pagination{
    bottom:20px;
}

/* Features */

.marketplace-features{
    padding:40px 0;
}

.feature-card{
    padding:25px 18px;
}

.feature-icon{
    width:70px;
    height:70px;
    font-size:28px;
}

.feature-card h3{
    font-size:22px !important;
}

.feature-card p{
    font-size:14px;
}

/* Headings */

.heading-block h2{
    font-size:24px !important;
}

.heading-block p{
    font-size:14px !important;
}

/* Product Cards */

.product-card,
.feed-card,
.package-card{
    flex:0 0 100%;
}

.product-grid{
    grid-template-columns:1fr;
}

.product-banner{
    height:190px;
}

.product-thumb{
    width:65px !important;
    height:65px !important;
}

.product-info{
    padding:10px;
}

.product-text h6{
    font-size:15px;
}

/* Featured */

.featured-image img{
    height:220px;
}

.featured-content{
    padding:20px;
}

.featured-content h4{
    font-size:22px;
}

.ms-40{
    margin-left:0 !important;
}

/* Package */

.package-title{
    font-size:24px;
}

.package-price{
    font-size:28px;
}

.package-btn{
    width:100%;
}

.package-features li{
    font-size:14px;
}

/* Navigation Arrows */

.nav,
.header-nav{
    width:36px;
    height:36px;
    font-size:16px;
}

.prev,
.package-prev{
    left:-8px;
}

.next,
.package-next{
    right:-8px;
}

/* Footer */

footer .col-auto{
    width:100%;
    justify-content:center !important;
    margin-bottom:10px;
}

footer .col-auto:last-child{
    flex-wrap:wrap;
}

}

/* ==========================================
   SMALL PHONES
========================================== */

@media (max-width:480px){

.swiper-container{
    height:240px;
}

.slide-title{
    font-size:22px;
}

.notification-icon{
    width:40px;
    height:40px;
    padding:0;
    display:flex;
    justify-content:center;
    align-items:center;
}

.btn{
    padding:10px 18px;
    font-size:14px;
}

.slider-header{
    padding:16px;
}

.product-banner,
.feed-card img,
.grid-card img{
    height:170px;
}

.featured-image img{
    height:180px;
}

.package-title{
    font-size:20px;
}

.package-price{
    font-size:24px;
}

}
.product-slider,
.top-slider,
.package-grid,
.featured-slider{
    scroll-snap-type: x mandatory;
}

.product-card,
.feed-card,
.package-card,
.featured-items{
    scroll-snap-align: start;
}
.navbar-toggler-icon{
width: 20px !important;
    height: 20px !important;
}
    </style>
</head>
<body>
    <header class="main-header">
        <div class="container">
            <nav class="navbar navbar-expand-lg">
                <!-- Logo -->
                <?php 
                    $company = $conn->query("SELECT logo, phone_no, email FROM company LIMIT 1");
                    $companyRow = $company->fetch_assoc();
                    $logo = $companyRow['logo'] ?? ''; 
                ?>
                <a class="navbar-brand" href="#">
                    <img src="../../uploads/company_logo/<?php echo htmlspecialchars($logo); ?>" class="logo"
                    style="height:50px;" alt="Company Logo">
                </a>

                <button class="navbar-toggler"
                        type="button"
                        data-bs-toggle="collapse"
                        data-bs-target="#mainMenu">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="mainMenu">

                    <!-- Center Menu -->
                    <ul class="navbar-nav mx-auto">

                        <li class="nav-item">
                            <a class="nav-link" href="#">Home</a>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link" href="#">Marketplace</a>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link" href="#">Packages</a>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link" href="#">Products</a>
</li>

                    </ul>

                    <!-- Right Side -->
                    <div class="d-flex align-items-center gap-2">

                        <a href="#"
                        class="notification-icon">
                            <i class="fas fa-bell"></i>
                        </a>

                        <?php if($isLoggedIn){ ?>

                            <a href="#"
                            class="btn btn-success rounded-pill">
                                Dashboard
                            </a>

                        <?php } else { ?>

                            <a href="#"
                            class="btn btn-success rounded-pill">
                                Login
                            </a>

                        <?php } ?>

                    </div>

                </div>

            </nav>
        </div>
    </header>
    <?php
    $bannerQuery = mysqli_query($conn, "
        SELECT images, title
        FROM marketplace_banner
        LIMIT 1
    ");

    $banner = mysqli_fetch_assoc($bannerQuery);
    $bannerImages = json_decode($banner['images'], true);
    $bannerTitles = json_decode($banner['title'], true);
    ?>
    <section class="container">
        <div class="swiper-container slideshow">
            <div class="swiper-wrapper">
                <?php
                if (!empty($bannerImages)) {
                    foreach ($bannerImages as $index => $image) {
                        $title = isset($bannerTitles[$index]) ? $bannerTitles[$index] : '';
                        // Adjust this path if needed
                        $imagePath = "../../" . str_replace("\\", "/", $image);
                    ?>
                    <div class="swiper-slide slide">
                        <div class="slide-image"
                            style="background-image:url('<?php echo htmlspecialchars($imagePath); ?>')">
                        </div>
                        <span class="slide-title">
                            <?php echo htmlspecialchars($title); ?>
                        </span>
                    </div>
                <?php
                    }
                }
                ?>
            </div>

            <div class="slideshow-pagination"></div>                
            <div class="slideshow-navigation">
                <div class="slideshow-navigation-button prev"><span class="fas fa-chevron-left"></span></div>
                <div class="slideshow-navigation-button next"><span class="fas fa-chevron-right"></span></div>
            </div>
        </div>    
    </section>

<!-- Why Choose Marketplace -->
<section class="marketplace-features">
    <div class="container">
        <div class="row">

            <div class="col-lg-4 col-md-6 mb-4">
                <div class="feature-card">
                    <div class="feature-icon icon-blue">
                        <i class="fas fa-store"></i>
                    </div>
                    <h3>Trusted Marketplace</h3>
                    <p>
                    Discover high-quality software, website templates, plugins, mobile apps, graphics, and digital resources designed to help individuals and businesses grow.
                    </p>
                </div>
            </div>

            <div class="col-lg-4 col-md-6 mb-4">
                <div class="feature-card">
                    <div class="feature-icon icon-green">
                        <i class="fas fa-shipping-fast"></i>
                    </div>
                    <h3>Fast & Easy Purchase</h3>
                    <p>
                    Purchase and access your digital products immediately after payment. No shipping delays—download your files anytime, anywhere, with just a few clicks.
                    </p>
                </div>
            </div>

            <div class="col-lg-4 col-md-6 mb-4">
                <div class="feature-card">
                    <div class="feature-icon icon-purple">
                        <i class="fas fa-tags"></i>
                    </div>
                    <h3>Updates & Support</h3>
                    <p>
                    Receive product updates, documentation, and dedicated seller support to ensure you always have the latest features and assistance when needed.
                    </p>
                </div>
            </div>

        </div>
    </div>
</section>

    <?php while($market = mysqli_fetch_assoc($marketplaceQuery)){ ?>

    <?php
        $model = $market['model'];
        $catId = $market['cat_id'];
        $catName = $market['cat_name'];
        $catDesc = $market['cat_des'];

        $productQuery = mysqli_query($conn,"
        SELECT *
        FROM products
        WHERE cat_id='$catId'
        AND is_active='1'
        AND is_deleted='0'
        ORDER BY created_at DESC");
    ?>
        <?php if($model == 1){ ?>
            <!-- Slider 1 -->
            <div class="container mt-5">
            <div class="slider-header">
                <div class="heading-block">
                    <h2><?php echo htmlspecialchars($catName); ?></h2>
                    <p class="mb-0 fs-5"><?php echo htmlspecialchars($catDesc); ?></p>
                </div>
            </div>
                <div class="slider-wrapper">
                    <button class="nav prev fa fa-chevron-left"></button>
                    <div class="product-slider">
                    <?php while($row=mysqli_fetch_assoc($productQuery)){ ?>
<?php
$url = strtolower($row['name']);

// Replace any dash (hyphen, en dash, em dash) surrounded by spaces with a single hyphen
$url = preg_replace('/\s*[-–—]\s*/u', '-', $url);

// Replace remaining spaces with hyphens
$url = preg_replace('/\s+/', '-', $url);

// Remove all other special characters except hyphens
$url = preg_replace('/[^a-z0-9-]/', '', $url);

// Remove duplicate hyphens
$url = preg_replace('/-+/', '-', $url);

// Trim hyphens from the beginning/end
$url = trim($url, '-');
?>
                        <!-- Product Card -->
                        <div class="product-card">
                            <!-- Main Image -->
                            <?php
$imageData = json_decode($row['image_data'], true);

$productBanner = !empty($imageData['breadcrumb_image'])
    ? $imageData['breadcrumb_image']
    : $row['product_image'];
?>

<!-- Main Banner Image -->
<img class="product-banner"
     src="../../uploads/products/<?php echo htmlspecialchars($productBanner); ?>"
     alt="<?php echo htmlspecialchars($row['name']); ?>">
                            <div class="product-info">
                                <!-- Small Preview Image -->
                                <img class="product-thumb" src="../../uploads/products/<?php echo $row['product_image']; ?>">
                                <div class="product-text">
<a href="../products/<?php echo $url; ?>.php"><h6><?php echo $row['name']; ?></h6></a>
                                    <p><?php echo $row['short_description']; ?></p>
                                    <!--<div class="price">
                                        ₹<?php echo number_format($row['price']); ?>
                                    </div>-->
                                    <div class="mt-4 d-flex gap-3">                                    
                                    <form action="../../cart.php" method="POST">                                
                                        <input type="hidden" name="type" value="product">  
                                        <input type="hidden" name="id" value="<?= htmlspecialchars($row['id']) ?>">
                                        <input type="hidden" name="plan_name" value="<?= htmlspecialchars($row['name']) ?>">
                                        <input type="hidden" name="title" value="<?= htmlspecialchars($row['title']) ?>">
                                        <input type="hidden" name="subtitle" value="<?= htmlspecialchars($row['subtitle']) ?>">
                                        <input type="hidden" name="price" value="<?= htmlspecialchars($row['price']) ?>">
                                        <input type="hidden" name="duration" value="<?= htmlspecialchars($row['duration']) ?>">
                                        <input type="hidden" name="created_on" value="<?= date("Y-m-d") ?>">
                                        <input type="hidden" name="gst" value="<?= htmlspecialchars($row['gst']) ?>">                            
                                        <button type="submit" class="btn btn-light m-0 btn-sm"> 
                                        Buy Now
                                        </button>        
                                    </form>
                                    <a href="../products/<?php echo $url; ?>.php"><button class="btn btn-light m-0 btn-sm">View More</button></a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                    </div>
                    <button class="nav next fa fa-chevron-right"></button>
                </div>
            </div>
        <?php } ?>

        <?php if($model == 2){ ?>
            <!-- Slider 2 -->
            <div class="container mt-5">
                <div class="slider-header">
                    <div class="heading-block">
                        <h2><?php echo htmlspecialchars($catName); ?></h2>
                        <p class="mb-0 fs-5"><?php echo htmlspecialchars($catDesc); ?></p>
                    </div>
                    <div>
                        <button class="header-nav top-prev fa fa-chevron-left"></button>
                        <button class="header-nav top-next fa fa-chevron-right"></button>
                    </div>
                </div>
                <div class="top-slider">
                <?php while($row=mysqli_fetch_assoc($productQuery)){ ?>
<?php
$url = strtolower($row['name']);

// Replace any dash (hyphen, en dash, em dash) surrounded by spaces with a single hyphen
$url = preg_replace('/\s*[-–—]\s*/u', '-', $url);

// Replace remaining spaces with hyphens
$url = preg_replace('/\s+/', '-', $url);

// Remove all other special characters except hyphens
$url = preg_replace('/[^a-z0-9-]/', '', $url);

// Remove duplicate hyphens
$url = preg_replace('/-+/', '-', $url);

// Trim hyphens from the beginning/end
$url = trim($url, '-');
?>
                    <div class="feed-card">
                        <img src="../../uploads/products/<?php echo $row['product_image']; ?>">
                        <div class="feed-content">
<a href="../products/<?php echo $url; ?>.php"><h5><?php echo $row['name']; ?></h5></a>
                            <p><?php echo $row['short_description']; ?></p>
                            <div class="price mb-6">
                                ₹<?php echo number_format($row['price']); ?>
<span class="text-decoration-line-through ms-6">₹<?php echo number_format($row['preview_price']); ?></span>

                            </div>
                            <div class="mt-4 d-flex gap-3">                                    
                                    <form action="../../cart.php" method="POST">                                
                                        <input type="hidden" name="type" value="product">  
                                        <input type="hidden" name="id" value="<?= htmlspecialchars($row['id']) ?>">
                                        <input type="hidden" name="plan_name" value="<?= htmlspecialchars($row['name']) ?>">
                                        <input type="hidden" name="title" value="<?= htmlspecialchars($row['title']) ?>">
                                        <input type="hidden" name="subtitle" value="<?= htmlspecialchars($row['subtitle']) ?>">
                                        <input type="hidden" name="price" value="<?= htmlspecialchars($row['price']) ?>">
                                        <input type="hidden" name="duration" value="<?= htmlspecialchars($row['duration']) ?>">
                                        <input type="hidden" name="created_on" value="<?= date("Y-m-d") ?>">
                                        <input type="hidden" name="gst" value="<?= htmlspecialchars($row['gst']) ?>">                            
                                        <button type="submit" class="btn btn-light m-0 btn-sm"> 
                                        Buy Now
                                        </button>        
                                    </form>
                                    <a href="../products/<?php echo $url; ?>.php"><button class="btn btn-light m-0 btn-sm">View More</button></a>
                                    </div>
                        </div>
                    </div>
                <?php } ?>
                </div>
            </div>
        <?php } ?>

        <?php if($model == 3){ ?>
            <!-- Slider 3 -->
            <div class="container mt-5">
                <div class="slider-header">
                    <div class="heading-block">
                        <h2><?php echo htmlspecialchars($catName); ?></h2>
                        <p class="mb-0 fs-5"><?php echo htmlspecialchars($catDesc); ?></p>
                    </div>
                    <div>
                        <button class="header-nav feature-prev fa fa-chevron-left"></button>
                        <button class="header-nav feature-next fa fa-chevron-right"></button>
                    </div>
                </div>
                <div class="featured-slider">
                    <?php while($row=mysqli_fetch_assoc($productQuery)){ ?>
                    <?php
                    $url = strtolower($row['name']);

                    // Replace any dash (hyphen, en dash, em dash) surrounded by spaces with a single hyphen
                    $url = preg_replace('/\s*[-–—]\s*/u', '-', $url);

                    // Replace remaining spaces with hyphens
                    $url = preg_replace('/\s+/', '-', $url);

                    // Remove all other special characters except hyphens
                    $url = preg_replace('/[^a-z0-9-]/', '', $url);

                    // Remove duplicate hyphens
                    $url = preg_replace('/-+/', '-', $url);

                    // Trim hyphens from the beginning/end
                    $url = trim($url, '-');
                    ?>

                    <div class="featured-items">
                        <div class="featured-image">
                            <img src="../../uploads/products/<?php echo $row['product_image']; ?>">
                        </div>
                        <div class="featured-content">
                            <div class="ms-40">                              
                                <a href="../products/<?php echo $url; ?>.php"><h4 class="mb-2 text-capitalize"><?php echo $row['name']; ?></h4></a>
                                    <p class="price">
                                        <span class="amount fs-3 fw-semibold me-6">₹ <?php echo $row['price']; ?></span>
                                        <span class="text-decoration-line-through fs-3 fw-semibold">
                                        ₹ <?php echo $row['preview_price']; ?>
                                        </span>
                                    </p>                            
                                    <p>
                                        <?php echo $row['short_description']; ?>
                                    </p>                            
                                    <!-- <p><b>Duration</b> :
                                        <?php echo $row['price']; ?>
                                    </p> -->
                                    <p><b>Category</b> :
                                        <?php echo $row['category']; ?>
                                    </p>
                                    <p><b>Tags</b> :
                                        <?php echo $row['tags']; ?>
                                    </p>
                                    <div class="mt-4 d-flex gap-3">                                    
                                    <form action="../../cart.php" method="POST">                                
                                        <input type="hidden" name="type" value="product">  
                                        <input type="hidden" name="id" value="<?= htmlspecialchars($row['id']) ?>">
                                        <input type="hidden" name="plan_name" value="<?= htmlspecialchars($row['name']) ?>">
                                        <input type="hidden" name="title" value="<?= htmlspecialchars($row['title']) ?>">
                                        <input type="hidden" name="subtitle" value="<?= htmlspecialchars($row['subtitle']) ?>">
                                        <input type="hidden" name="price" value="<?= htmlspecialchars($row['price']) ?>">
                                        <input type="hidden" name="duration" value="<?= htmlspecialchars($row['duration']) ?>">
                                        <input type="hidden" name="created_on" value="<?= date("Y-m-d") ?>">
                                        <input type="hidden" name="gst" value="<?= htmlspecialchars($row['gst']) ?>">                            
                                        <button type="submit" class="btn btn-light m-0"> 
                                        Buy Now
                                        </button>        
                                    </form>
                                    <a href="../products/<?php echo $url; ?>.php"><button class="btn btn-light m-0">View More</button></a>
                                    </div>
                            </div>
                        </div>
                    </div>
                    <?php } ?>
                </div>
            </div>
        <?php } ?>

        <?php if($model == 4){ ?>
        <div class="container mt-5">
            <div class="slider-header">
                <div class="heading-block">
                    <h2><?php echo htmlspecialchars($catName); ?></h2>
                    <p class="mb-0 fs-5"><?php echo htmlspecialchars($catDesc); ?></p>
                </div>
            </div>
            <div class="product-grid">
                <?php while($row=mysqli_fetch_assoc($productQuery)){ ?>
                <?php
                $url = strtolower($row['name']);

                // Replace any dash (hyphen, en dash, em dash) surrounded by spaces with a single hyphen
                $url = preg_replace('/\s*[-–—]\s*/u', '-', $url);

                // Replace remaining spaces with hyphens
                $url = preg_replace('/\s+/', '-', $url);

                // Remove all other special characters except hyphens
                $url = preg_replace('/[^a-z0-9-]/', '', $url);

                // Remove duplicate hyphens
                $url = preg_replace('/-+/', '-', $url);

                // Trim hyphens from the beginning/end
                $url = trim($url, '-');
                ?>

                <div class="grid-card">
                    <img src="../../uploads/products/<?php echo $row['product_image']; ?>">
                    <div class="grid-content">
                        <a href="../products/<?php echo $url; ?>.php"><h5><?php echo $row['name']; ?></h5></a>
                        <p><?php echo $row['short_description']; ?></p>
                        <div class="price">
                            ₹<?php echo number_format($row['price']); ?>
                            <span class="text-decoration-line-through ms-6">₹<?php echo number_format($row['preview_price']); ?></span>
                        </div>
                        <span class="badge"><?php echo $row['category']; ?></span>
                        <span class="badge"><?php echo $row['tags']; ?></span>
                    </div>
                </div>
                <?php } ?>
            </div>
        </div>
        <?php } ?>
    <?php } ?>
<?php

$sql = "
SELECT DISTINCT
    c.cat_id,
    c.cat_name,
    c.cat_des
FROM categories c
INNER JOIN `package` p
    ON p.cat_id = c.cat_id
WHERE
    p.is_active = 1
    AND p.is_deleted = 0
ORDER BY c.cat_name
";

$packageCategories = mysqli_query($conn,$sql);

if(!$packageCategories){
    die(mysqli_error($conn));
}

?>

<?php while($cat = mysqli_fetch_assoc($packageCategories)){ ?>

<?php

$catId = $cat['cat_id'];

$packageQuery = mysqli_query($conn,"
SELECT *
FROM `package`
WHERE cat_id = '$catId'
AND is_active = 1
AND is_deleted = 0
ORDER BY created_at DESC
");

if(!$packageQuery){
    die("Package Query Error : ".mysqli_error($conn));
}

?>

<div class="container mt-5">
    <div class="slider-header">
        <div class="heading-block">
            <h2><?php echo $cat['cat_name']; ?></h2>
            <p class="mb-0 fs-5"><?php echo $cat['cat_des']; ?></p>
        </div>
    </div>
    <div class="slider-wrapper">
    <button class="nav package-prev fa fa-chevron-left"></button>
    <div class="package-grid">
        <?php
        $i=0;
        while($package=mysqli_fetch_assoc($packageQuery)){
        ?>
        <?php
        $class = ($i%2==0) ? "" : "dark";
        ?>
        <div class="package-card <?php echo $class; ?>">
            <?php
            $durationResult = mysqli_query($conn,"
            SELECT *
            FROM durations
            WHERE package_id='".$package['id']."'
            ORDER BY id ASC
            LIMIT 1
            ");

            if(!$durationResult){
                die("Duration Error : ".mysqli_error($conn));
            }

            $duration = mysqli_fetch_assoc($durationResult);

            if(!$duration){
                continue;
            }
            ?>
            <div class="package-title">
                <?php echo $package['title']; ?>
            </div>
            <div class="package-subtitle">
                <?php echo $package['subtitle']; ?>
            </div>
            <?php if($duration['preview_price']>0){ ?>
            <div class="old-price">
                ₹<?php echo number_format($duration['preview_price']); ?>
            </div>
            <?php } ?>
            <div class="package-price">
                ₹<?php echo number_format($duration['price']); ?>
                <span>
                /<?php echo $duration['duration']; ?>
                </span>
            </div>
            <div class="mt-4 d-flex gap-3">
                <form action="../../cart.php" method="POST">
                    <input type="hidden" name="type" value="package">
                    <input type="hidden" name="id" value="<?= htmlspecialchars($package['id']) ?>">
                    <input type="hidden" name="plan_name" value="<?= htmlspecialchars($package['package_name']) ?>">
                    <input type="hidden" name="title" value="<?= htmlspecialchars($package['title']) ?>">
                    <input type="hidden" name="subtitle" value="<?= htmlspecialchars($package['subtitle']) ?>">
                    <input type="hidden" name="price" value="<?= htmlspecialchars($duration['price']) ?>">
                    <input type="hidden" name="duration" value="<?= htmlspecialchars($duration['duration']) ?>">
                    <input type="hidden" name="created_on" value="<?= date("Y-m-d") ?>">
                    <!-- <input type="hidden" name="addon_service" value="<?= htmlspecialchars($package['addon_service']) ?>">
                    <input type="hidden" name="addon_package" value="<?= htmlspecialchars($package['addon_package']) ?>">
                    <input type="hidden" name="addon_product" value="<?= htmlspecialchars($package['addon_product']) ?>">
                    <input type="hidden" name="gst_id" value="<?= htmlspecialchars($package['gst_id']) ?>"> -->

                    <button type="submit" class="package-btn w-auto">Buy Now</button>
                </form>  
<?php
$url_pack = strtolower($package['package_name']);

// Replace any dash (hyphen, en dash, em dash) surrounded by spaces with a single hyphen
$url_pack = preg_replace('/\s*[-–—]\s*/u', '-', $url_pack);

// Replace remaining spaces with hyphens
$url_pack = preg_replace('/\s+/', '-', $url_pack);

// Remove all other special characters except hyphens
$url_pack = preg_replace('/[^a-z0-9-]/', '', $url_pack);

// Remove duplicate hyphens
$url_pack = preg_replace('/-+/', '-', $url_pack);

// Trim hyphens from the beginning/end
$url_pack = trim($url_pack, '-');
?>
                <a href="../packages/<?php echo $url_pack; ?>.php"><button type="submit" class="package-btn w-auto z">View More</button></a>
            </div>
            <ul class="package-features">
            <?php
            $feature=mysqli_query($conn,"
            SELECT feature
            FROM features
            WHERE package_id='".$package['id']."'
            LIMIT 5
            ");
            while($f=mysqli_fetch_assoc($feature)){
            ?>
                <li>
                    <span class="package-icon">✓</span>
                    <span><?php echo $f['feature']; ?></span>
                </li>
            <?php } ?>
            </ul>
        </div>
        <?php
        $i++;
        }
        ?>
    </div>
    <button class="nav package-next fa fa-chevron-right"></button>
    </div>
</div>

<?php } ?>
    <!-- ===== FOOTER ===== -->
        <footer class="d-footer mt-5">
            <div class="row align-items-center justify-content-between">
                <div class="col-auto">
                    <p class="mb-0">© <?php echo date("Y"); ?> Lufera Infotech. All Rights Reserved.</p>
                </div>
                <div class="col-auto">
                    <a href="../../privacy_policy.php" target="_blank">Privacy Policy</a>
                    <span>|</span>
                    <a href="../../terms_conditions.php" target="_blank">Terms and Conditions</a>
                    <span>|</span>
                    <a href="https://luferatech.com" target="_blank">
                        <span>Made by</span>
                        <span style="color:#1e8a8a;">Lufera Infotech</span>
                    </a>
                </div>
            </div>
        </footer>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/Swiper/4.4.1/js/swiper.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/2.0.2/TweenMax.min.js"></script>
<script>
function enableSwipe(slider) {
    let startX = 0;
    let endX = 0;

    slider.addEventListener("touchstart", (e) => {
        startX = e.touches[0].clientX;
    }, { passive: true });

    slider.addEventListener("touchend", (e) => {
        endX = e.changedTouches[0].clientX;

        const diff = startX - endX;

        // Ignore small swipes
        if (Math.abs(diff) < 50) return;

        slider.scrollBy({
            left: diff > 0 ? slider.clientWidth : -slider.clientWidth,
            behavior: "smooth"
        });
    }, { passive: true });
}
    document.querySelectorAll(".slider-wrapper").forEach(wrapper => {
    const slider = wrapper.querySelector(".product-slider");
    const nextBtn = wrapper.querySelector(".next");
    const prevBtn = wrapper.querySelector(".prev");
    const card = wrapper.querySelector(".product-card");
    if(!slider || !card) return;
enableSwipe(slider);
    const cardWidth = card.offsetWidth + 20;
    nextBtn.addEventListener("click", () => {
        slider.scrollBy({
            left: cardWidth,
            behavior: "smooth"
        });
    });
    prevBtn.addEventListener("click", () => {
        slider.scrollBy({
            left: -cardWidth,
            behavior: "smooth"
        });
    });
    });

    // Slider 2
    document.querySelectorAll(".container").forEach(container=>{
    const slider = container.querySelector(".top-slider");
    if(!slider) return;
enableSwipe(slider);
    const next = container.querySelector(".top-next");
    const prev = container.querySelector(".top-prev");
    const card = slider.querySelector(".feed-card");
    const width = card.offsetWidth + 20;
    next.addEventListener("click",()=>{
        slider.scrollBy({
            left:width,
            behavior:"smooth"
        });
    });
    prev.addEventListener("click",()=>{
        slider.scrollBy({
            left:-width,
            behavior:"smooth"
        });
    });
    });

    // Slider 3
    document.querySelectorAll(".container").forEach(container=>{
    const slider = container.querySelector(".featured-slider");
    if(!slider) return;
enableSwipe(slider);
    const next = container.querySelector(".feature-next");
    const prev = container.querySelector(".feature-prev");
    const width = slider.clientWidth;
    next.addEventListener("click",()=>{
        slider.scrollBy({
            left:width,
            behavior:"smooth"
        });

    });
    prev.addEventListener("click",()=>{
        slider.scrollBy({
            left:-width,
            behavior:"smooth"
        });
    });
    });
</script>  
<script>
// The Slideshow class.
class Slideshow {
    constructor(el) {
        
        this.DOM = {el: el};
      
        this.config = {
          slideshow: {
            delay: 3000,
            pagination: {
              duration: 3,
            }
          }
        };
        
        // Set the slideshow
        this.init();
      
    }
    init() {
      
      var self = this;
      
      // Charmed title
      this.DOM.slideTitle = this.DOM.el.querySelectorAll('.slide-title');
      this.DOM.slideTitle.forEach((slideTitle) => {
        charming(slideTitle);
      });
      
      // Set the slider
      this.slideshow = new Swiper (this.DOM.el, {
          
          loop: true,
          effect: 'fade',
        direction: 'vertical',
          autoplay: {
            delay: this.config.slideshow.delay,
            disableOnInteraction: true,
          },
          speed: 500,
          preloadImages: true,
          updateOnImagesReady: true,
          
          // lazy: true,
          // preloadImages: false,

          pagination: {
            el: '.slideshow-pagination',
            clickable: true,
            bulletClass: 'slideshow-pagination-item',
            bulletActiveClass: 'active',
            clickableClass: 'slideshow-pagination-clickable',
            modifierClass: 'slideshow-pagination-',
            renderBullet: function (index, className) {
              
              var slideIndex = index,
                  number = (index <= 8) ? '0' + (slideIndex + 1) : (slideIndex + 1);
              
              var paginationItem = '<span class="slideshow-pagination-item">';
              paginationItem += '<span class="pagination-number">' + number + '</span>';
              paginationItem = (index <= 8) ? paginationItem + '<span class="pagination-separator"><span class="pagination-separator-loader"></span></span>' : paginationItem;
              paginationItem += '</span>';
            
              return paginationItem;
              
            },
          },

          // Navigation arrows
          navigation: {
            nextEl: '.slideshow-navigation-button.next',
            prevEl: '.slideshow-navigation-button.prev',
          },

          // And if we need scrollbar
          scrollbar: {
            el: '.swiper-scrollbar',
          },
        
          on: {
            init: function() {
              self.animate('next');
            },
          }
        
        });
      
        // Init/Bind events.
        this.initEvents();
        
    }
    initEvents() {
        
        this.slideshow.on('paginationUpdate', (swiper, paginationEl) => this.animatePagination(swiper, paginationEl));
        //this.slideshow.on('paginationRender', (swiper, paginationEl) => this.animatePagination());

        this.slideshow.on('slideNextTransitionStart', () => this.animate('next'));
        
        this.slideshow.on('slidePrevTransitionStart', () => this.animate('prev'));
            
    }
    animate(direction = 'next') {
      
        // Get the active slide
        this.DOM.activeSlide = this.DOM.el.querySelector('.swiper-slide-active'),
        this.DOM.activeSlideImg = this.DOM.activeSlide.querySelector('.slide-image'),
        this.DOM.activeSlideTitle = this.DOM.activeSlide.querySelector('.slide-title'),
        this.DOM.activeSlideTitleLetters = this.DOM.activeSlideTitle.querySelectorAll('span');
      
        // Reverse if prev  
        this.DOM.activeSlideTitleLetters = direction === "next" ? this.DOM.activeSlideTitleLetters : [].slice.call(this.DOM.activeSlideTitleLetters).reverse();
      
        // Get old slide
        this.DOM.oldSlide = direction === "next" ? this.DOM.el.querySelector('.swiper-slide-prev') : this.DOM.el.querySelector('.swiper-slide-next');
        if (this.DOM.oldSlide) {
          // Get parts
          this.DOM.oldSlideTitle = this.DOM.oldSlide.querySelector('.slide-title'),
          this.DOM.oldSlideTitleLetters = this.DOM.oldSlideTitle.querySelectorAll('span'); 
          // Animate
          this.DOM.oldSlideTitleLetters.forEach((letter,pos) => {
            TweenMax.to(letter, .3, {
              ease: Quart.easeIn,
              delay: (this.DOM.oldSlideTitleLetters.length-pos-1)*.04,
              y: '50%',
              opacity: 0
            });
          });
        }
      
        // Animate title
        this.DOM.activeSlideTitleLetters.forEach((letter,pos) => {
					TweenMax.to(letter, .6, {
						ease: Back.easeOut,
						delay: pos*.05,
						startAt: {y: '50%', opacity: 0},
						y: '0%',
						opacity: 1
					});
				});
      
        // Animate background
        TweenMax.to(this.DOM.activeSlideImg, 1.5, {
            ease: Expo.easeOut,
            startAt: {x: direction === 'next' ? 200 : -200},
            x: 0,
        });
      
        //this.animatePagination()
    
    }
    animatePagination(swiper, paginationEl) {
            
      // Animate pagination
      this.DOM.paginationItemsLoader = paginationEl.querySelectorAll('.pagination-separator-loader');
      this.DOM.activePaginationItem = paginationEl.querySelector('.slideshow-pagination-item.active');
      this.DOM.activePaginationItemLoader = this.DOM.activePaginationItem.querySelector('.pagination-separator-loader');
      
      console.log(swiper.pagination);
      // console.log(swiper.activeIndex);
      
      // Reset and animate
        TweenMax.set(this.DOM.paginationItemsLoader, {scaleX: 0});
        TweenMax.to(this.DOM.activePaginationItemLoader, this.config.slideshow.pagination.duration, {
          startAt: {scaleX: 0},
          scaleX: 1,
        }); 
    }   
}
const slideshow = new Slideshow(document.querySelector('.slideshow'));

document.querySelectorAll(".slider-wrapper").forEach(wrapper => {

const slider = wrapper.querySelector(".package-grid");
if(!slider) return;
enableSwipe(slider);
const next = wrapper.querySelector(".package-next");
const prev = wrapper.querySelector(".package-prev");
const card = slider.querySelector(".package-card");

if(!card) return;

const width = card.offsetWidth + 25;

next.addEventListener("click", () => {
    slider.scrollBy({
        left: width,
        behavior: "smooth"
    });
});

prev.addEventListener("click", () => {
    slider.scrollBy({
        left: -width,
        behavior: "smooth"
    });
});

});
</script>

</body>
</html>
