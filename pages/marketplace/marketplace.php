<?php
    include '../../partials/connection.php';
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
    include '../head.php';
    include '../scripts.php';
    session_start();
    include '../../partials/theme_colors_loader.php';
$isLoggedIn = isset($_SESSION['user_id']) && (int)$_SESSION['user_id'] > 0;

$notifications = null;
$notiCount = 0;

if ($isLoggedIn) {

    $loggedInUserId = (int)$_SESSION['user_id'];

    $stmtUser = $conn->prepare("
        SELECT user_id
        FROM users
        WHERE id = ?
        LIMIT 1
    ");

    $stmtUser->bind_param("i", $loggedInUserId);
    $stmtUser->execute();

    $result = $stmtUser->get_result();
    $row = $result->fetch_assoc();

    $UserId = $row['user_id'] ?? null;

    if ($UserId !== null) {

        $stmt = $conn->prepare("
            SELECT *
            FROM notifications
            WHERE user_id = ?
              AND is_read = 0
            ORDER BY created_at DESC
            LIMIT 5
        ");

        $stmt->bind_param("s", $UserId);
        $stmt->execute();

        $notifications = $stmt->get_result();
        $notiCount = $notifications->num_rows;
    }
}
    

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
        c.cat_des,
        c.cat_img,
        c.cat_type
    FROM marketplace m
    LEFT JOIN categories c
        ON c.cat_id = m.cat_id
    ORDER BY m.display_order ASC
    ");

    // Absolute URL of the embed-only page (hero slider through packages/products,
    // no header/footer) used by the "Copy Shortcode" button for WordPress's
    // [lufera_marketplace] shortcode.
    $embedProtocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $embedHost = $_SERVER['HTTP_HOST'] ?? '';
    $embedDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
    $marketplaceEmbedUrl = $embedProtocol . $embedHost . $embedDir . '/marketplace-embed.php';
?>
<html>
<head>
<link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.0.13/css/all.css" integrity="sha384-DNOHZ68U8hZfKXOrtjWvjxusGo9WQnrNx2sqG0tfsghAvtVlRW3tvkXWZh58N9jp" crossorigin="anonymous">
<link href="https://fonts.googleapis.com/css?family=Oswald:500" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/Swiper/4.0.7/css/swiper.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/Swiper/4.0.7/js/swiper.min.js"></script>
<script>
const isLoggedIn = <?= $isLoggedIn ? 'true' : 'false' ?>;
</script>
<style>
    #pageContent.blur{
    filter:blur(6px);
    pointer-events:none;
    user-select:none;
}

.login-modal{
    position:fixed;
    inset:0;
    background:rgba(0,0,0,.45);
    display:none;
    justify-content:center;
    align-items:center;
    z-index:99999;
}

.login-modal.show{
    display:flex;
}

.login-box{
    width:900px;
    height:650px;
    max-width:95%;
    background:#fff;
    border-radius:10px;
    overflow:hidden;
}

.login-box iframe{
    width:100%;
    height:100%;
    border:none;
}
    </style>
<div id="loginModal" class="login-modal">
    <div class="login-box">
        <iframe
            id="loginFrame"
            src="../../sign-in.php?popup=1">
        </iframe>
    </div>
</div>

<script>
    window.addEventListener("load", function(){

if(!isLoggedIn){

    document
        .getElementById("pageContent")
        .classList.add("blur");

    document
        .getElementById("loginModal")
        .classList.add("show");

}

});
    </script>
    <style>
        .dropdown-item.active, .dropdown-item:active{
            color: var(--lufera-text-color);
    background-color: var(--lufera-main-color);
        }
/* ===== Hero slider (split image/content layout) ===== */
.hero-slider-section{
    margin-top:24px;
    margin-bottom:8px;
}
.hero-slider{
    position:relative;
    background:#f8f9f1;
    border-radius:20px;
    overflow:hidden;
    z-index:1;
    width:100%;
}
.hero-slide-bg-1{
    background:#f8f9f1 url('../../assets/images/marketplace/hero-slider-bg-1.svg') center center / cover no-repeat;
}
.hero-slide-bg-2{
    background:#0C1E21 url('../../assets/images/marketplace/hero-slider-bg-2-dark.svg') center center / cover no-repeat;
}
.hero-slide-bg-3{
    background:#f8f9f1 url('../../assets/images/marketplace/hero-slider-bg-3.svg') center center / cover no-repeat;
}
.hero-slide-bg-sale{
    background:#f8f9f1 url('../../assets/images/marketplace/hero-slider-bg-sale.svg') center center / cover no-repeat;
}
.hero-slide-dark .hero-slide-title{
    color:#F2F8F7;
}
.hero-slide-dark .hero-slide-desc{
    color:#C7DCDA;
}
.hero-slide-dark .hero-slide-cta{
    background:#ffffff;
    color:#0C1E21;
}
.hero-slide-dark .hero-slide-cta:hover{
    background:#19AFBE;
    color:#ffffff;
}
.hero-slider .swiper-wrapper{
    display:flex;
}
.hero-slide{
    display:flex !important;
    align-items:center;
    gap:40px;
    min-height:360px;
    padding:40px 48px;
    flex-shrink:0;
    width:100%;
}
.hero-slide-zigzag{
    flex-direction:row-reverse;
}
.hero-slide-centered{
    flex-direction:column;
    text-align:center;
    justify-content:center;
    gap:24px;
}
.hero-slide-centered .hero-slide-media{
    flex:0 0 auto;
    max-width:260px;
}
.hero-slide-centered .hero-slide-media img{
    width:260px;
    max-width:260px;
    height:auto;
}
.hero-slide-centered .hero-slide-content{
    max-width:640px;
}
.hero-slide-centered .hero-slide-desc{
    margin-left:auto;
    margin-right:auto;
}
.hero-slide-media{
    flex:0 0 40%;
    max-width:40%;
    display:flex;
    align-items:center;
    justify-content:center;
}
.hero-slide-media img{
    width:500px;
    max-width:500px;
    height:280px;
    object-fit:contain;
}
.hero-slide-content{
    flex:1 1 auto;
    min-width:0;
}
.hero-slide-title{
    font-size:2.1rem !important;
    color:#0C1E21;
    margin-bottom:14px;
    line-height:1.25;
}
.hero-slide-desc{
    font-size:1.05rem;
    color:#45585B;
    max-width:460px;
    margin-bottom:22px;
    line-height:1.6;
}
.hero-slide-cta{
    display:inline-block;
    background:#1F8A8A;
    color:#fff;
    font-weight:600;
    padding:11px 26px;
    border-radius:8px;
    text-decoration:none;
    transition:background .2s ease;
}
.hero-slide-cta:hover{
    background:#176e6e;
    color:#fff;
}
.hero-slider-arrow{
    flex-shrink:0;
    width:44px;
    height:44px;
    border-radius:50%;
    border:1.5px solid #c7d6d6;
    background:#fff;
    color:#0C1E21;
    display:flex;
    align-items:center;
    justify-content:center;
    cursor:pointer;
    z-index:5;
    transition:.2s ease;
}
.hero-slider-arrow:hover{
    background:#1F8A8A;
    border-color:#1F8A8A;
    color:#fff;
}
.hero-slider-footer{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:14px;
    margin-top:18px;
}
.hero-slider-controls-center{
    display:flex;
    align-items:center;
    justify-content:center;
    gap:14px;
    flex:1 1 auto;
}
.hero-slider-pagination{
    display:flex;
    align-items:center;
    gap:6px;
}
.hero-slider-pagination .swiper-pagination-bullet{
    width:22px;
    height:4px;
    border-radius:3px;
    background:#c7d6d6;
    opacity:1;
    margin:0 !important;
    transition:.3s;
    cursor:pointer;
}
.hero-slider-pagination .swiper-pagination-bullet-active{
    background:#1F8A8A;
    width:30px;
}
.hero-slider-playpause{
    width:30px;
    height:30px;
    border-radius:50%;
    border:1.5px solid #c7d6d6;
    background:#fff;
    color:#0C1E21;
    display:flex;
    align-items:center;
    justify-content:center;
    cursor:pointer;
    font-size:11px;
    flex-shrink:0;
}
.hero-slider-playpause:hover{
    background:#1F8A8A;
    border-color:#1F8A8A;
    color:#fff;
}
@media (max-width:900px){
    .hero-slide{
        flex-direction:column;
        text-align:center;
        min-height:auto;
        padding:30px 20px;
        gap:20px;
    }
    .hero-slide-media{ flex:0 0 auto; max-width:220px; }
    .hero-slide-desc{ margin-left:auto; margin-right:auto; }
    .hero-slider-footer{ gap:8px; }
    .hero-slider-arrow{ width:36px; height:36px; }
}
/* banner */
%transition_all_03s {
  transition:all .3s ease;
}
%backface_visibility_hidden{
  backface-visibility:hidden;
  -webkit-backface-visibility:hidden;
}

*, *:before, *:after {
  box-sizing: border-box;
  margin: 0;
  padding: 0;
}
/* Mobile */
@media (max-width:768px){

.swiper-container{
    height:250px !important;
}

.main-slider,
.main-slider .swiper-wrapper,
.main-slider .swiper-slide{
    height:250px !important;
}

.slide-bgimg{
    height:250px;
}

.slide-bgimg img{
    width:100%;
    height:100%;
    object-fit:cover;
}

}
.swiper-container {
  width: 100%;
  height: 450px;
  border-radius:14px;
  transition:opacity .6s ease, transform .3s ease;
  &.nav-slider{
    width:20%;
    padding-left:5px;
    .swiper-slide{
      cursor:pointer;
      opacity:.4;
      transition:opacity .3s ease;
      &.swiper-slide-active{
        opacity:1;
      }
      .content{
        width:100%;
        .title{
          font-size:20px;
        }
      }
    }
  }
  &:hover{
    .swiper-button-prev,
    .swiper-button-next{
      transform:translateX(0);
      opacity:1;
      visibility:visible;
    }
  }
  &.loading{
    opacity:0;
    visibility:hidden;
  }
}
.swiper-wrapper{

}
.swiper-slide{
  overflow: hidden;
  @extend %backface_visibility_hidden;
  .slide-bgimg{
    position:absolute;
    top:0;
    left:0;
    width:100%;
    height:100%;
    background-position:center;
    background-size:contain;
  }
  .entity-img{
    display:none;
  }
  .content{
    position:absolute;
    top:45%;
    left:0;
    width:100%;
    color:#fff;
    .title{
        font-size:2.6em;
        font-weight:bold;
        margin-bottom:30px;
        text-align:center;
        font-family: 'Oswald', sans-serif;
        text-transform: uppercase;
        font-weight: normal;
        font-size: 3vw;
    }
    .caption{
      display:block;
      font-size:13px;
      line-height:1.4;
      transform:translateX(50px);
      opacity:0;
      transition:opacity .3s ease, transform .7s ease;
      &.show{
        transform:translateX(0);
        opacity:1;
      }
    }
  }
}
[class^="swiper-button-"]{
  width:44px;
  opacity:0;
  visibility:hidden;
  @extend %transition_all_03s;
}
.swiper-button-prev{
  transform:translateX(50px);
}
.swiper-button-next{
  transform:translateX(-50px);
}
/* Banner Image Animation */
.main-slider .slide-bgimg{
    /* transform:scale(1);
    transition:transform 6s ease; */
}

.main-slider .swiper-slide-active .slide-bgimg{
    transform:scale(1.12);
}

/* Banner Text Animation */
.main-slider .content{
    opacity:0;
    transform:translateY(60px);
    transition:all .8s ease;
}

.main-slider .swiper-slide-active .content{
    opacity:1;
    transform:translateY(0);
}

.main-slider .title{
    opacity:0;
    transform:translateX(-80px);
    transition:all .9s ease .3s;
}

.main-slider .swiper-slide-active .title{
    opacity:1;
    transform:translateX(0);
}
.main-slider .swiper-pagination{
    bottom:25px !important;
    text-align:center;
}

.main-slider .swiper-pagination-bullet{
    width:10px;
    height:6px;
    border-radius:20px;
    background:rgba(255,255,255,.35);
    opacity:1;
    margin:0 6px !important;
    transition:.4s;
}

.main-slider .swiper-pagination-bullet-active{
    width:25px;
    background:#ffffff;
}
/* banner section end */
section {
    width: 100%;
}
.package-prev{
    left:-20px;
}

.package-next{
    right:-20px;
}
.cat_img{
    width: 100% !important;
    border-radius: 8px !important;
    max-height: 300px !important;
    object-fit: cover !important;
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
            flex-direction:row;
            justify-content: space-between;
            align-items: center;
            gap: 15px !important;
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

        .header-nav{
            width:45px;
            height:45px;
            border:none;
            border-radius:50%;
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
            position:relative;
            overflow:hidden;
        }

        /* Card width/count per view is controlled by Swiper's slidesPerView */
        .product-card{
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
            padding:10px;
            text-align:center;
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

    .top-slider{
        position:relative;
        overflow:hidden;
    }

    .feed-card{
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
        position:relative;
        overflow:hidden;
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
    position:relative;
    overflow:hidden;
}

.package-card{
    position:relative;
    background:#fff;
    border-radius:20px;
    overflow:hidden;
    padding:30px;
    box-shadow:0 8px 25px rgba(0,0,0,.08);
    display:flex;
    flex-direction:column;
    transition:.3s;
    font-family:'Inter', sans-serif;
}
.package-card:hover{
    transform:translateY(-5px);
}
.package-card.dark{
    background:var(--lufera-main-color);
    color:var(--lufera-text-color);
}
.package-title{
    font-size:24px;
    font-weight:700;
    line-height:1.2;
    margin-bottom:8px;
    color:#0c1e21;
}
.package-card.dark .package-title{
    color:#fff;
}
.package-subtitle{
    font-size:15px;
    margin-bottom:20px;
    color:#555;
}
.package-card.dark .package-subtitle{
    color:#fff;
}
.old-price{
    font-size:15px;
    text-decoration:line-through;
    opacity:.7;
    margin-bottom:6px;
}
.package-price{
    font-size:40px;
    font-weight:800;
    line-height:1;
    margin-bottom:25px;
    color:#0c1e21;
}
.package-card.dark .package-price{
    color:#fff;
}
.package-price span{
    font-size:18px;
    font-weight:500;
}
.package-btn{
    width:100%;
    height:56px;
    padding:12px;
    border-radius:100px;
    border:none;
    font-size:16px;
    font-weight:600;
    margin-bottom:30px;
    transition:.3s;
    display:flex;
    align-items:center;
    justify-content:center;
    gap:10px;
    color:#222;
}
.package-card:not(.dark) .package-btn{
    background:#fff;
    border:1.5px solid var(--lufera-main-color);
}

.package-card.dark .package-btn{
    background:#fff;
}
.package-btn:hover{
    transform:scale(1.02);
}
.package-btn-icon{
    width:26px;
    height:26px;
    flex-shrink:0;
    border-radius:50%;
    background:#0c1e21;
    color:#fff;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    font-size:11px;
}
.package-features{
    list-style:none;
    padding:0;
    margin:0;
    flex:1;
}
.package-features li{
    display:flex;
    align-items:flex-start;
    gap:12px;
    margin-bottom:15px;
    font-size:15px;
    line-height:1.5;
    color:#444;
}
.package-card.dark .package-features li{
    color:#fff;
}
.package-viewmore-btn{
    display:flex;
    width:100%;
    align-items:center;
    justify-content:center;
    gap:8px;
    margin-top:20px;
    padding:14px;
    border-radius:100px;
    font-size:16px;
    font-weight:600;
    text-decoration:none;
    background:var(--lufera-main-color);
    color:#fff;
    transition:.3s;
}
.package-card.dark .package-viewmore-btn{
    background:#fff;
    color:var(--lufera-main-color);
}
.package-viewmore-btn:hover{
    transform:scale(1.03);
    color:#fff;
}
.package-card.dark .package-viewmore-btn:hover{
    color:var(--lufera-main-color);
}
.package-icon{
    width:24px;
    height:24px;
    border-radius:50%;
    display:flex;
    align-items:center;
    justify-content:center;
    background:var(--lufera-main-color);
    color:#fff;
    font-size:11px;
    font-weight:bold;
    flex-shrink:0;
    margin-top:2px;
}
.package-card.dark .package-icon{
    background:#fff;
    color:var(--lufera-main-color);
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

/* This page renders without a <!DOCTYPE html>, which puts the browser in
   quirks mode. In quirks mode, Swiper's own height:100% on .swiper-wrapper/
   .swiper-slide resolves against the viewport instead of stopping at our
   auto-height slider containers, ballooning every card to viewport height
   and leaving huge empty gaps below each section. Overriding both back to
   height:auto makes the row shrink-wrap to its tallest card again, and
   align-items:stretch (flexbox's default, set explicitly here) still makes
   every card in that row match that tallest card's height. */
.product-slider .swiper-wrapper,
.top-slider .swiper-wrapper,
.featured-slider .swiper-wrapper,
.package-grid .swiper-wrapper{
    height:auto;
    align-items:stretch;
}
.product-card.swiper-slide,
.feed-card.swiper-slide,
.featured-items.swiper-slide,
.package-card.swiper-slide{
    height:auto;
}

.duration-tabs{
    display:flex;
    justify-content:center;
    gap:8px;
    margin:0 0 30px;
    flex-wrap:wrap;
}
.duration-tab-btn{
    border:none;
    background:transparent;
    padding:10px 26px;
    border-radius:30px;
    font-weight:600;
    font-size:15px;
    color:#666;
    cursor:pointer;
    transition:.25s;
}
.duration-tab-btn.active{
    background:var(--lufera-main-color);
    color:var(--lufera-text-color);
}
.duration-tab-btn:not(.active):hover{
    color:var(--lufera-main-color);
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
    padding:40px 0 0;
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
    padding: 15px;
    border-radius: 50%;
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

.mobile-menu{
    position:fixed;
    top:0;
    right:-100%;
    width:300px;
    max-width:85%;
    height:100vh;
    background:#fff;
    z-index:9999;
    overflow-y:auto;
    transition:.35s ease;
    padding:20px;
    box-shadow:-5px 0 25px rgba(0,0,0,.15);
}

.mobile-menu.show{
    right:0;
}

.mobile-menu-header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    padding-bottom:20px;
    border-bottom:1px solid #eee;
    margin-bottom:20px;
}

.mobile-menu-header img{
    max-width:170px;
}

.mobile-close{
    background:none;
    border:none;
    font-size:24px;
    cursor:pointer;
}

.mobile-menu .navbar-nav{
    width:100%;
}

.mobile-menu .nav-item{
    width:100%;
}

.mobile-menu .nav-link{
    padding:14px 0;
    border-bottom:1px solid #f3f3f3;
    text-align:left;
    font-size:16px;
}

.mobile-menu .d-flex{
    margin-top:20px;
    flex-direction:row;
    align-items:stretch;
    gap:15px;
}

.mobile-menu .btn{
    width:100%;
}

/* Banner */


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

/* Card width/count per view at this breakpoint is set via Swiper breakpoints in JS */

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

/* Features */
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
}

/* ==========================================
   SMALL PHONES
========================================== */

@media (max-width:480px){

.slide-title{
    font-size:22px;
}

.notification-icon{
    width:40px;
    height:40px;
    display:flex;
    justify-content:center;
    align-items:center;
}

.btn{
    font-size:16px;
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
.navbar-toggler-icon{
width: 20px !important;
    height: 20px !important;
}
.logo{
max-width:230px;
}
.mobile-overlay{
    position:fixed;
    inset:0;
    background:rgba(0,0,0,.45);
    opacity:0;
    visibility:hidden;
    transition:.3s;
    z-index:9998;
}

.mobile-overlay.show{
    opacity:1;
    visibility:visible;
}
.category_description{
 display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
  line-height: 32px;
}
.d-footer{
padding: 0.75rem 1.5rem !important;
}
.user-photo{
    width: 40px;
    height: 40px;
    object-fit: cover;
    border: 1px solid #dee2e6;
}
    </style>

    <!-- category css -->
    <style>
       /* ==========================================
        MARKETPLACE CATEGORY SLIDER
        Shared styles for Slider 1 & Slider 2
        ========================================== */

        .category-section,
        .cat-slider-section {
            position: relative;
            background: #fff;
            border-radius: 14px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }


        /* ==========================================
        TITLE
        ========================================== */

        .category-title,
        .cat-slider-title {
            text-align: center;
            margin-bottom: 28px;
        }

        .category-title h2,
        .cat-slider-title h2 {
            margin: 0;
            font-size: 42px !important;
            font-weight: 600;
            color: #18243b;
            line-height: 1.2;
        }


        /* ==========================================
        TITLE DECORATION
        ========================================== */

        .category-title-decoration,
        .cat-slider-title-decoration {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 12px;
        }

        .category-title-decoration span,
        .cat-slider-title-decoration span {
            width: 80px;
            height: 3px;
            border-radius: 10px;
        }

        .category-title-decoration i,
        .cat-slider-title-decoration i {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: block;
        }

        .category-title-decoration i:nth-of-type(2),
        .cat-slider-title-decoration i:nth-of-type(2) {
            opacity: .75;
        }

        .category-title-decoration i:nth-of-type(3),
        .cat-slider-title-decoration i:nth-of-type(3) {
            opacity: .5;
        }


        /* ==========================================
        FEATURED IMAGE
        ========================================== */

        .category-image-box,
        .cat-slider-image-box {
            width: 100%;
            padding: 8px;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            background: #f8f9fa;
            overflow: hidden;
        }

        .category-featured-image,
        .cat-slider-featured-image {
            width: 100%;
            max-height: 300px;
            display: block;
            object-fit: cover;
            border-radius: 8px;
        }


        /* ==========================================
        DESCRIPTION
        ========================================== */

        .category-description,
        .cat-slider-description {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-top:15px;
        }

        .cat-slider-description {
            flex: 1;
            min-width: 0;
        }


        /* ==========================================
        DESCRIPTION ICON
        ========================================== */

        .category-description-icon,
        .cat-slider-description-icon {
            width: 72px;
            height: 72px;
            min-width: 72px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 27px;
            box-shadow:
                0 0 0 6px rgba(32,149,150,.10);
        }


        /* ==========================================
        DESCRIPTION VERTICAL LINE
        ========================================== */

        .category-description-line,
        .cat-slider-description-line {
            width: 4px;
            height: -webkit-fill-available;
            opacity: .7;
            flex-shrink: 0;
            margin: 5px 0;
        }


        /* ==========================================
        DESCRIPTION TEXT
        ========================================== */

        .category-description p,
        .cat-slider-description p {
            margin: 0;
            color: #536074;
            font-size: 17px;
            line-height: 1.8;
            flex: 1;
        }


        /* ==========================================
        DECORATIVE BACKGROUND
        ========================================== */

        .category-section::before,
        .cat-slider-section::before {
            content: "";

            position: absolute;

            top: 20px;
            right: 20px;

            width: 80px;
            height: 80px;

            background-image: radial-gradient(
                #209596 1.5px,
                transparent 1.5px
            );

            background-size: 14px 14px;

            opacity: .12;

            pointer-events: none;
        }


        /* ==========================================
        SLIDER 2 ONLY
        Description + Navigation
        ========================================== */

        .cat-slider-bottom {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 25px;
            /* margin-top: 15px;
            padding: 0 35px; */
        }


        /* Remove the normal margin/padding because
        cat-slider-bottom already controls it */

        .cat-slider-bottom .cat-slider-description {
            margin: 0;
            padding: 0;
        }


        /* ==========================================
        SLIDER 2 NAVIGATION
        ========================================== */

        .cat-slider-navigation {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-shrink: 0;
        }

        .cat-slider-navigation .header-nav {
            width: 46px;
            height: 46px;

            border: none;
            border-radius: 50%;

            display: flex;
            align-items: center;
            justify-content: center;

            padding: 0;
            margin: 0;

            font-size: 16px;

            cursor: pointer;

            transition: .25s ease;
        }

        .cat-slider-navigation .header-nav:hover {
            transform: scale(1.08);
        }


        /* ==========================================
        TABLET
        ========================================== */

        @media (max-width: 991px) {

            .category-title h2,
            .cat-slider-title h2 {
                font-size: 36px;
            }

            .category-featured-image,
            .cat-slider-featured-image {
                height: 350px;
object-fit:contain;
            }

            .category-description {
                padding: 0 15px;
            }

            .cat-slider-bottom {
                padding: 0 15px;
            }

        }


        /* ==========================================
        MOBILE
        ========================================== */

        @media (max-width: 767px) {

            .category-section,
            .cat-slider-section {
                padding: 25px 15px;
            }


            .category-title h2,
            .cat-slider-title h2 {
                font-size: 30px;
            }


            .category-title-decoration span,
            .cat-slider-title-decoration span {
                width: 45px;
            }


            .category-featured-image,
            .cat-slider-featured-image {
                height: 230px;
            }


            /* Slider 1 description */

            .category-description, .cat-slider-description {
                flex-direction: column;
                align-items: flex-start;
                padding: 0 5px;
                gap: 12px;
                margin-top:15px;
            }


           .category-description-line, .cat-slider-description-line {
                display: none;
            }


            /* Slider 2 bottom */

            .cat-slider-bottom {
                flex-direction: column;
                align-items: stretch;

                padding: 0 5px;

                gap: 20px;
            }


            .cat-slider-description {
                align-items: flex-start;
            }


            .category-description-icon,
            .cat-slider-description-icon {
                width: 58px;
                height: 58px;
                min-width: 58px;

                font-size: 22px;
            }


            .cat-slider-description-line {
                height: 58px;
            }


            .category-description p,
            .cat-slider-description p {
                font-size: 15px;
                line-height: 1.7;
            }


            /* Slider 2 arrows */

 
            .cat-slider-navigation .header-nav {
                width: 40px;
                height: 40px;

                font-size: 14px;
            }

        }
    </style>
</head>
<body>
<div id="pageContent">
    
    <script>
        (function(){
            var embedUrl = <?php echo json_encode($marketplaceEmbedUrl); ?>;
            var shortcode = '[lufera_marketplace url="' + embedUrl + '"]';
            var btn = document.getElementById('copyMarketplaceShortcodeBtn');
            var toast = document.getElementById('marketplaceShortcodeToast');

            function showToast(){
                toast.style.display = 'block';
                clearTimeout(toast._hideTimer);
                toast._hideTimer = setTimeout(function(){ toast.style.display = 'none'; }, 2500);
            }

            btn.addEventListener('click', function(){
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(shortcode).then(showToast, showToast);
                } else {
                    var tmp = document.createElement('textarea');
                    tmp.value = shortcode;
                    tmp.style.position = 'fixed';
                    tmp.style.opacity = '0';
                    document.body.appendChild(tmp);
                    tmp.select();
                    try { document.execCommand('copy'); } catch (e) {}
                    document.body.removeChild(tmp);
                    showToast();
                }
            });
        })();
    </script>
    <header class="main-header">
        <div class="container">
            <nav class="navbar navbar-expand-lg my-10">
                <!-- Logo -->
                <?php 
                    $company = $conn->query("SELECT logo, phone_no, email FROM company LIMIT 1");
                    $companyRow = $company->fetch_assoc();
                    $logo = $companyRow['logo'] ?? ''; 
                ?>
                <a class="navbar-brand" href="#">
                    <img src="../../uploads/company_logo/<?php echo htmlspecialchars($logo); ?>" class="logo" alt="Company Logo">
                </a>

                <button type="button" data-bs-toggle="collapse" data-bs-target="#mainMenu" class="d-lg-none">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse mobile-menu" id="mainMenu">

    <!-- Mobile Header -->
    <div class="mobile-menu-header d-lg-none">
        <img src="../../uploads/company_logo/<?php echo htmlspecialchars($logo); ?>" class="logo" alt="Company Logo">

        <button class="mobile-close" type="button"
            data-bs-toggle="collapse"
            data-bs-target="#mainMenu">
            <iconify-icon icon="ic:baseline-close" class="icon text-xxl line-height-1"></iconify-icon>
        </button>
    </div>

                    <!-- Center Menu -->
                    <ul class="navbar-nav mx-auto fw-semibold gap-2">
                        <li class="nav-item">
                            <a class="nav-link" href="https://luferatech.com/" target="_blank">Home</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="https://luferatech.com/about/" target="_blank">About</a>
                        </li>
                        <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle"
                        href="#"
                        id="productsDropdown"
                        role="button"
                        data-bs-toggle="dropdown"
                        aria-expanded="false">
                            Products
                        </a>

                        <ul class="dropdown-menu" aria-labelledby="productsDropdown">

                            <li>
                                <a class="dropdown-item py-10"
                                href="https://one.luferatech.com/"
                                target="_blank">
                                    LuferaOne
                                </a>
                            </li>

                            <li>
                                <a class="dropdown-item py-10"
                                href="https://sheets.luferatech.com/"
                                target="_blank">
                                    LuferaSheets
                                </a>
                            </li>

                            <li>
                                <a class="dropdown-item py-10"
                                href="https://idx.luferatech.com/"
                                target="_blank">
                                    LuferaIDX
                                </a>
                            </li>

                            <li>
                                <a class="dropdown-item py-10"
                                href="https://cart.luferatech.com/"
                                target="_blank">
                                    LuferaCart
                                </a>
                            </li>

                            <li>
                                <a class="dropdown-item py-10"
                                href="https://web.luferatech.com/"
                                target="_blank">
                                    LuferaWeb
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item py-10"
                                href="https://core.luferatech.com/"
                                target="_blank">
                                    LuferaCore
                                </a>
                            </li>
                        </ul>

                    </li>
                        
                        <li class="nav-item">
                            <a class="nav-link" href="https://luferatech.com/contact/" target="_blank">Contact Us</a>
                        </li>
                    </ul>

                    <!-- Right Side -->
                    <div class="d-flex align-items-center gap-2">

                        <!-- <a href="#" class="notification-icon lufera-bg lufera-text">
                            <i class="fas fa-bell"></i>
                        </a> -->

                        <!-- <button type="button" data-theme-toggle class="w-40-px h-40-px bg-neutral-200 rounded-circle d-flex justify-content-center align-items-center"></button> -->

                        <div class="dropdown">
                            <button id="notificationBell" class="btn rounded-pill lufera-bg lufera-text" type="button" data-bs-toggle="dropdown">
                                <iconify-icon icon="iconoir:bell" class="text-xxl"></iconify-icon>
                                <?php if ($notiCount > 0): ?>
                                    <!-- <span class="indicator bg-danger rounded-circle" style="width:10px; height:10px; position:absolute; top:5px; right:5px;"></span> -->

                                    <span id="notificationBadge" class="position-absolute badge rounded-pill bg-danger text-white" 
                                        style="font-size: 10px; padding: 4px 7px; bottom: 0px; right: -6px; white-space: nowrap; top: auto;">
                                        <?= $notiCount ?>
                                    </span>
                                <?php endif; ?>
                            </button>
                            <div class="dropdown-menu to-top dropdown-menu-lg p-0">
                                <div class="m-16 py-12 px-16 radius-8 bg-primary-50 mb-16 d-flex align-items-center justify-content-between gap-2">
                                    <div>
                                        <h6 class="text-lg text-primary-light fw-semibold mb-0">Notifications</h6>
                                    </div>
                                    <span class="text-primary-600 fw-semibold text-lg w-40-px h-40-px rounded-circle bg-base d-flex justify-content-center align-items-center"><?= $notiCount ?></span>
                                </div>

                                <div class="max-h-400-px overflow-y-auto scroll-sm pe-4">
                                    <?php if ($notiCount > 0): ?>
                                        <div class="text-center py-12 px-16">
                                            <?php while ($noti = $notifications->fetch_assoc()): ?>

                                                <!-- <div class="py-8 border-bottom d-flex align-items-center gap-2">
                                                    <?php if (!empty($noti['n_photo'])): ?>
                                                        <img src="<?= htmlspecialchars($noti['n_photo']) ?>" alt="user photo" class="rounded-circle" width="30" height="30">
                                                    <?php endif; ?>
                                                    <div class="text-start">
                                                        <p class="text-sm mb-1"><?= htmlspecialchars($noti['message']) ?></p>
                                                        <small class="text-muted"><?= date('d M Y, h:i A', strtotime($noti['created_at'])) ?></small>
                                                    </div>
                                                </div> -->

                                                <div class="notification-item d-flex align-items-start gap-3 py-3 px-3 border-bottom">
                                                    <div class="flex-shrink-0">
                                                        <?php if (!empty($noti['n_photo'])): ?>
                                                            <img src="../../<?= htmlspecialchars($noti['n_photo']) ?>" alt="user photo" class="user-photo rounded-circle">
                                                        <?php else: ?>
                                                            <img src="../../assets/images/user1.png" alt="user photo" class="user-photo rounded-circle">
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="flex-grow-1 text-start">
                                                        <p class="mb-1 fw-semibold text-dark notification-message"><?= htmlspecialchars($noti['message']) ?></p>
                                                        <small class="text-muted"><?= date('d M Y, h:i A', strtotime($noti['created_at'])) ?></small>
                                                    </div>
                                                </div>

                                            <?php endwhile; ?>
                                        </div>
                                        <div class="text-center py-12 px-16">
                                            <a href="javascript:void(0)" class="text-primary-600 fw-semibold text-md">See All Notification</a>
                                        </div>
                                    <?php else: ?>
                                        <div class="px-16 py-12 text-center text-muted">No new notifications</div>
                                    <?php endif; ?>
                                </div>

                            </div>
                        </div><!-- Notification dropdown end -->

                        <?php if($isLoggedIn){ ?>

                            <a href="../../admin-dashboard.php" class="btn rounded-pill lufera-bg lufera-text">
                                Dashboard
                            </a>

                        <?php } else { ?>

                            <a href="../../sign-in.php" class="btn rounded-pill lufera-bg lufera-text">
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
        SELECT images, title, description, button_text, button_link
        FROM marketplace_banner
        LIMIT 1
    ");

    $banner = mysqli_fetch_assoc($bannerQuery);
    $bannerImages = $banner ? json_decode($banner['images'], true) : [];
    $bannerTitles = $banner ? json_decode($banner['title'], true) : [];
    $bannerDescriptions = $banner ? json_decode($banner['description'] ?? '', true) : [];
    $bannerButtonTexts = $banner ? json_decode($banner['button_text'] ?? '', true) : [];
    $bannerButtonLinks = $banner ? json_decode($banner['button_link'] ?? '', true) : [];
    $bannerImages = is_array($bannerImages) ? $bannerImages : [];
    $bannerTitles = is_array($bannerTitles) ? $bannerTitles : [];
    $bannerDescriptions = is_array($bannerDescriptions) ? $bannerDescriptions : [];
    $bannerButtonTexts = is_array($bannerButtonTexts) ? $bannerButtonTexts : [];
    $bannerButtonLinks = is_array($bannerButtonLinks) ? $bannerButtonLinks : [];

    // One background style per slide (slide 1 is the darker LuferaOne variant)
    $heroSlideBgClasses = ['hero-slide-bg-2 hero-slide-dark', 'hero-slide-bg-sale', 'hero-slide-bg-3'];
    ?>


<!-- Hero slider section start-->
<section class="container hero-slider-section">
    <div class="hero-slider">
        <div class="swiper-wrapper">
            <?php
                $bannerImages = array_slice($bannerImages, 0, 3);
                $bannerTitles = array_slice($bannerTitles, 0, 3);
                $bannerDescriptions = array_slice($bannerDescriptions, 0, 3);
                $bannerButtonTexts = array_slice($bannerButtonTexts, 0, 3);
                $bannerButtonLinks = array_slice($bannerButtonLinks, 0, 3);

                foreach ($bannerImages as $i => $image) {
                    if (empty($image)) continue;

                    $ctaText = !empty($bannerButtonTexts[$i]) ? $bannerButtonTexts[$i] : 'Explore Marketplace';
                    $ctaLink = !empty($bannerButtonLinks[$i]) ? $bannerButtonLinks[$i] : '#marketplace-products';
                    $bgClass = $heroSlideBgClasses[$i % count($heroSlideBgClasses)];
                    $layoutClass = ($i === 1) ? 'hero-slide-zigzag' : '';
            ?>
            <div class="swiper-slide hero-slide <?php echo $bgClass; ?> <?php echo $layoutClass; ?>">
                <div class="hero-slide-media">
                    <img src="../../<?php echo htmlspecialchars($image); ?>"
                        alt="<?php echo htmlspecialchars($bannerTitles[$i] ?? 'Lufera Infotech'); ?>">
                </div>
                <div class="hero-slide-content">
                    <h2 class="hero-slide-title">
                        <?php echo htmlspecialchars($bannerTitles[$i] ?? ''); ?>
                    </h2>
                    <?php if (!empty($bannerDescriptions[$i])) { ?>
                    <p class="hero-slide-desc">
                        <?php echo htmlspecialchars($bannerDescriptions[$i]); ?>
                    </p>
                    <?php } ?>
                    <a href="<?php echo htmlspecialchars($ctaLink); ?>" class="hero-slide-cta" target="_blank"><?php echo htmlspecialchars($ctaText); ?></a>
                </div>
            </div>
            <?php } ?>
        </div>
    </div>

    <div class="hero-slider-footer">
        <button type="button" class="hero-slider-arrow hero-slider-prev" aria-label="Previous slide">
            <i class="fa fa-chevron-left"></i>
        </button>

        <div class="hero-slider-controls-center">
            <div class="hero-slider-pagination"></div>
            <button type="button" class="hero-slider-playpause" id="heroSliderPlayPause" aria-label="Pause slideshow">
                <i class="fa fa-pause"></i>
            </button>
        </div>

        <button type="button" class="hero-slider-arrow hero-slider-next" aria-label="Next slide">
            <i class="fa fa-chevron-right"></i>
        </button>
    </div>
</section>
<div id="marketplace-products"></div>
<!-- Hero slider section end-->
    <?php while($market = mysqli_fetch_assoc($marketplaceQuery)){ ?>

    <?php

    if($market['cat_type'] === 'product'){
    // existing product code
        $model = $market['model'];
        $catId = $market['cat_id'];
        $catName = $market['cat_name'];
        $catDesc = $market['cat_des'];
        $catImg = $market['cat_img'];

        $productQuery = mysqli_query($conn,"
        SELECT *
        FROM products
        WHERE cat_id='$catId'
        AND is_active='1'
        AND is_deleted='0'");
    ?>
        <?php if($model == 1){ ?>
            <!-- Slider 1 -->
            <div class="container mt-3">
                <!-- <div class="card mb-3 p-3">
                    <img src="../../<?php echo htmlspecialchars($catImg); ?>" class="cat_img">
                
                    <div class="slider-header">
                        <div class="heading-block">
                            <div class="flex-row">
                                <span class="fa fa-user"></span>
                                <p class="mb-0 category_description"><?php echo htmlspecialchars($catDesc); ?></p>
                            </div>
                        </div>
                    </div>
                </div> -->

                <div class="category-section">

                    <!-- TITLE -->
                    <div class="category-title">
                        <h3><?php echo htmlspecialchars($catName); ?></h3>

                        <div class="category-title-decoration">
                            <span class="lufera-bg lufera-text"></span>
                            <i class="lufera-bg lufera-text"></i>
                            <i class="lufera-bg lufera-text"></i>
                            <i class="lufera-bg lufera-text"></i>
                            <span class="lufera-bg lufera-text"></span>
                        </div>
                    </div>

                    <!-- FEATURED IMAGE -->
                    <div class="category-image-box">
                        <img
                            src="../../<?php echo htmlspecialchars($catImg); ?>"
                            class="category-featured-image"
                            alt="<?php echo htmlspecialchars($catName); ?>"
                        >
                    </div>

                    <!-- DESCRIPTION -->
                    <div class="category-description">

                        <div class="category-description-icon lufera-bg lufera-text d-none">
                            <i class="fa fa-list-alt"></i>
                        </div>

                        <div class="category-description-line lufera-bg lufera-text"></div>

                        <p>
                            <?php echo htmlspecialchars($catDesc); ?>
                        </p>

                    </div>

                </div>
                <div class="slider-wrapper">
                    <button class="nav prev fa fa-chevron-left lufera-bg lufera-text"></button>
                    <div class="product-slider">
                    <div class="swiper-wrapper">
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
                        <div class="product-card swiper-slide">
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
                                        <button type="submit" class="btn btn-light m-0 btn-sm lufera-bg lufera-text"> 
                                        Buy Now
                                        </button>        
                                    </form>
                                    <a href="../products/<?php echo $url; ?>.php"><button class="btn btn-light m-0 btn-sm lufera-bg lufera-text">View More</button></a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                    </div>
                    </div>
                    <button class="nav next fa fa-chevron-right lufera-bg lufera-text"></button>
                </div>
            </div>
        <?php } ?>

        <?php if($model == 2){ ?>
            <!-- Slider 2 -->
            <div class="container mt-3">
                <!-- <div class="card mb-3 p-3">
                    <img src="../../<?php echo htmlspecialchars($catImg); ?>" class="cat_img">
                
                    <div class="slider-header">
                        <div class="heading-block">
                            <div class="flex-column">
                                <h2><?php echo htmlspecialchars($catName); ?></h2>
                                <p class="mb-0 category_description"><?php echo htmlspecialchars($catDesc); ?></p>
                            </div>
                        </div>
                    </div>
                    <div>
                        <button class="header-nav top-prev fa fa-chevron-left lufera-bg lufera-text"></button>
                        <button class="header-nav top-next fa fa-chevron-right lufera-bg lufera-text"></button>
                    </div>
                </div> -->

                <div class="cat-slider-section">

                    <!-- TITLE -->
                    <div class="cat-slider-title">
                        <h3><?php echo htmlspecialchars($catName); ?></h3>

                        <div class="cat-slider-title-decoration">
                            <span class="lufera-bg lufera-text"></span>
                            <i class="lufera-bg lufera-text"></i>
                            <i class="lufera-bg lufera-text"></i>
                            <i class="lufera-bg lufera-text"></i>
                            <span class="lufera-bg lufera-text"></span>
                        </div>
                    </div>


                    <!-- FEATURED IMAGE -->
                    <div class="cat-slider-image-box">
                        <img
                            src="../../<?php echo htmlspecialchars($catImg); ?>"
                            class="cat-slider-featured-image"
                            alt="<?php echo htmlspecialchars($catName); ?>"
                        >
                    </div>

                    <h6 class="mt-20">Description</h6>
                    <!-- DESCRIPTION + ARROWS -->
                    <div class="cat-slider-bottom">

                        <div class="cat-slider-description">

                            <div class="cat-slider-description-icon lufera-bg lufera-text d-none">
                                <i class="fa fa-list-alt"></i>
                            </div>

                            <div class="cat-slider-description-line lufera-bg lufera-text"></div>

                            <p>
                                <?php echo htmlspecialchars($catDesc); ?>
                            </p>

                        </div>


                        <!-- ARROWS BESIDE DESCRIPTION -->
                        <div class="cat-slider-navigation">

                            <button class="header-nav top-prev lufera-bg lufera-text">
                                <i class="fa fa-chevron-left"></i>
                            </button>

                            <button class="header-nav top-next lufera-bg lufera-text">
                                <i class="fa fa-chevron-right"></i>
                            </button>

                        </div>

                    </div>

                </div>
                <div class="top-slider">
                <div class="swiper-wrapper">
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
                    <div class="feed-card swiper-slide">
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
                                        <button type="submit" class="btn btn-light m-0 btn-sm lufera-bg lufera-text"> 
                                        Buy Now
                                        </button>        
                                    </form>
                                    <a href="../products/<?php echo $url; ?>.php"><button class="btn btn-light m-0 btn-sm lufera-bg lufera-text">View More</button></a>
                                    </div>
                        </div>
                    </div>
                <?php } ?>
                </div>
                </div>
            </div>
        <?php } ?>

        <?php if($model == 3){ ?>
            <!-- Slider 3 -->
            <div class="container mt-3">
                <!-- <div class="card mb-3 p-3">
                    <img src="../../<?php echo htmlspecialchars($catImg); ?>" class="cat_img">                
                    <div class="slider-header">
                        <div class="heading-block">
                            <div class="flex-column">
                                <p class="mb-0 category_description"><?php echo htmlspecialchars($catDesc); ?></p>
                            </div>
                        </div>
                    </div>
                    <div>
                        <button class="header-nav feature-prev fa fa-chevron-left lufera-bg lufera-text"></button>
                        <button class="header-nav feature-next fa fa-chevron-right lufera-bg lufera-text"></button>
                    </div>
                </div> -->

                <div class="cat-slider-section">

                    <!-- TITLE -->
                    <div class="cat-slider-title">
                        <h3><?php echo htmlspecialchars($catName); ?></h3>

                        <div class="cat-slider-title-decoration">
                            <span class="lufera-bg lufera-text"></span>
                            <i class="lufera-bg lufera-text"></i>
                            <i class="lufera-bg lufera-text"></i>
                            <i class="lufera-bg lufera-text"></i>
                            <span class="lufera-bg lufera-text"></span>
                        </div>
                    </div>


                    <!-- FEATURED IMAGE -->
                    <div class="cat-slider-image-box">
                        <img
                            src="../../<?php echo htmlspecialchars($catImg); ?>"
                            class="cat-slider-featured-image"
                            alt="<?php echo htmlspecialchars($catName); ?>"
                        >
                    </div>


                    <!-- DESCRIPTION + ARROWS -->
                    <h6 class="mt-20">Description</h6>
                    <div class="cat-slider-bottom">

                        <div class="cat-slider-description">
                            
                            <div class="cat-slider-description-icon lufera-bg lufera-text d-none">
                                <i class="fa fa-list-alt"></i>
                            </div>

                            <div class="cat-slider-description-line lufera-bg lufera-text"></div>

                            <p>
                                <?php echo htmlspecialchars($catDesc); ?>
                            </p>

                        </div>


                        <!-- ARROWS BESIDE DESCRIPTION -->
                        <div class="cat-slider-navigation">

                            <button class="header-nav top-prev lufera-bg lufera-text">
                                <i class="fa fa-chevron-left"></i>
                            </button>

                            <button class="header-nav top-next lufera-bg lufera-text">
                                <i class="fa fa-chevron-right"></i>
                            </button>

                        </div>

                    </div>

                </div>
                <div class="featured-slider">
                    <div class="swiper-wrapper">
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

                    <div class="featured-items swiper-slide">
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
                                        <button type="submit" class="btn btn-light m-0 lufera-bg lufera-text"> 
                                        Buy Now
                                        </button>        
                                    </form>
                                    <a href="../products/<?php echo $url; ?>.php"><button class="btn btn-light m-0 lufera-bg lufera-text">View More</button></a>
                                    </div>
                            </div>
                        </div>
                    </div>
                    <?php } ?>
                    </div>
                </div>
            </div>
        <?php } ?>

        <?php if($model == 4){ ?>
        <div class="container mt-3">
            <!-- <div class="card mb-3 p-3">
                <img src="../../<?php echo htmlspecialchars($catImg); ?>" class="cat_img">            
                <div class="slider-header">
                    <div class="heading-block">
                        <div class="flex-column">
                            <p class="mb-0 category_description"><?php echo htmlspecialchars($catDesc); ?></p>
                        </div>
                    </div>
                </div>
            </div> -->
            <div class="category-section">

                <!-- TITLE -->
                <div class="category-title">
                    <h3><?php echo htmlspecialchars($catName); ?></h3>

                    <div class="category-title-decoration">
                        <span class="lufera-bg lufera-text"></span>
                        <i class="lufera-bg lufera-text"></i>
                        <i class="lufera-bg lufera-text"></i>
                        <i class="lufera-bg lufera-text"></i>
                        <span class="lufera-bg lufera-text"></span>
                    </div>
                </div>

                <!-- FEATURED IMAGE -->
                <div class="category-image-box">
                    <img
                        src="../../<?php echo htmlspecialchars($catImg); ?>"
                        class="category-featured-image"
                        alt="<?php echo htmlspecialchars($catName); ?>"
                    >
                </div>
                <h6 class="mt-20">Description</h6>
                <!-- DESCRIPTION -->
                <div class="category-description">

                    <div class="category-description-icon lufera-bg lufera-text d-none">
                        <i class="fa fa-list-alt"></i>
                    </div>

                    <div class="category-description-line lufera-bg lufera-text"></div>

                    <p>
                        <?php echo htmlspecialchars($catDesc); ?>
                    </p>

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
    <?php } 

    if($market['cat_type'] === 'package'){
        $cat = $market;

        $catId = $cat['cat_id']; ?>

<?php

$catId = $cat['cat_id'];

$packageQuery = mysqli_query($conn,"
SELECT *
FROM `package`
WHERE cat_id = '$catId'
AND is_active = 1
AND is_deleted = 0");

if(!$packageQuery){
    die("Package Query Error : ".mysqli_error($conn));
}

// Buffer the packages so we can pre-fetch every duration (month/year/etc.)
// for all of them in one query before rendering the cards.
$packagesList = [];
while($pkgRow = mysqli_fetch_assoc($packageQuery)){
    $packagesList[] = $pkgRow;
}

$durationsByPackage = [];
$distinctDurations  = [];

if(!empty($packagesList)){
    $pkgIds = array_map(fn($p) => (int)$p['id'], $packagesList);
    $pkgIdsStr = implode(',', $pkgIds);

    $durRes = mysqli_query($conn, "
    SELECT *
    FROM durations
    WHERE package_id IN ($pkgIdsStr)
    ORDER BY id ASC
    ");

    if(!$durRes){
        die("Duration Error : ".mysqli_error($conn));
    }

    while($d = mysqli_fetch_assoc($durRes)){
        $durationsByPackage[$d['package_id']][$d['duration']] = $d;
        if(!in_array($d['duration'], $distinctDurations, true)){
            $distinctDurations[] = $d['duration'];
        }
    }
}

$defaultDuration = $distinctDurations[0] ?? null;

?>

    <div class="container mt-3">
        <!-- <div class="card mb-3 p-3">
            <img src="../../<?php echo htmlspecialchars($cat['cat_img']); ?>" class="cat_img">                
            <div class="slider-header">
                <div class="heading-block">
                    <div class="flex-column">
                        <h2><?php echo htmlspecialchars($cat['cat_name']); ?></h2>
                        <p><?php echo htmlspecialchars($cat['cat_des']); ?></p>
                    </div>
                </div>
            </div>
        </div> -->
        <div class="category-section">

                <!-- TITLE -->
                <div class="category-title">
                    <h3><?php echo htmlspecialchars($cat['cat_name']); ?></h3>

                    <div class="category-title-decoration">
                        <span class="lufera-bg lufera-text"></span>
                        <i class="lufera-bg lufera-text"></i>
                        <i class="lufera-bg lufera-text"></i>
                        <i class="lufera-bg lufera-text"></i>
                        <span class="lufera-bg lufera-text"></span>
                    </div>
                </div>

                <!-- FEATURED IMAGE -->
                <div class="category-image-box">
                    <img
                        src="../../<?php echo htmlspecialchars($cat['cat_img']); ?>"
                        class="category-featured-image"
                        alt="<?php echo htmlspecialchars($cat['cat_img']); ?>"
                    >
                </div>

                <!-- DESCRIPTION -->
                <div class="category-description">

                    <div class="category-description-icon lufera-bg lufera-text d-none">
                        <i class="fa fa-list-alt"></i>
                    </div>

                    <div class="category-description-line lufera-bg lufera-text"></div>

                    <p>
                        <?php echo htmlspecialchars($cat['cat_des']); ?>
                    </p>

                </div>

            </div>
    <?php if(count($distinctDurations) > 1): ?>
    <div class="duration-tabs" data-cat="<?php echo htmlspecialchars($catId); ?>">
        <?php foreach($distinctDurations as $di => $durName): ?>
            <button
                type="button"
                class="duration-tab-btn<?php echo $di === 0 ? ' active' : ''; ?>"
                data-duration="<?php echo htmlspecialchars($durName); ?>"
            >
                <?php echo htmlspecialchars($durName); ?>
            </button>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    <div class="slider-wrapper">
    <button class="nav package-prev fa fa-chevron-left lufera-text lufera-bg"></button>
    <div class="package-grid" data-cat="<?php echo htmlspecialchars($catId); ?>">
        <div class="swiper-wrapper">
        <?php
        $i=0;
        foreach($packagesList as $package){
        $packageDurations = $durationsByPackage[$package['id']] ?? [];

        if(empty($packageDurations)){
            continue;
        }

        $duration = $packageDurations[$defaultDuration] ?? reset($packageDurations);
        ?>
        <?php
        $class = ($i%2==0) ? "" : "dark";
        ?>
        <div class="package-card swiper-slide <?php echo $class; ?>">
            <div class="package-title">
                <?php echo $package['title']; ?>
            </div>
            <div class="package-subtitle">
                <?php echo $package['subtitle']; ?>
            </div>
            <?php foreach($packageDurations as $durName => $durRow): ?>
            <div
                class="package-price-variant<?php echo $durName === $defaultDuration ? '' : ' d-none'; ?>"
                data-duration="<?php echo htmlspecialchars($durName); ?>"
            >
                <?php if($durRow['preview_price']>0){ ?>
                <div class="old-price">
                    ₹<?php echo number_format($durRow['preview_price']); ?>
                </div>
                <?php } ?>
                <div class="package-price">
                    ₹<?php echo number_format($durRow['price']); ?>
                    <span>
                    /<?php echo htmlspecialchars($durName); ?>
                    </span>
                </div>
            </div>
            <?php endforeach; ?>
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
            <form action="../../cart.php" method="POST">
                <input type="hidden" name="type" value="package">
                <input type="hidden" name="id" value="<?= htmlspecialchars($package['id']) ?>">
                <input type="hidden" name="plan_name" value="<?= htmlspecialchars($package['package_name']) ?>">
                <input type="hidden" name="title" value="<?= htmlspecialchars($package['title']) ?>">
                <input type="hidden" name="subtitle" value="<?= htmlspecialchars($package['subtitle']) ?>">
                <input
                    type="hidden"
                    name="price"
                    class="package-price-input"
                    value="<?= htmlspecialchars($duration['price']) ?>"
                    data-prices="<?= htmlspecialchars(json_encode(array_map(fn($d) => $d['price'], $packageDurations))) ?>"
                >
                <input type="hidden" name="duration" class="package-duration-input" value="<?= htmlspecialchars($duration['duration']) ?>">
                <input type="hidden" name="created_on" value="<?= date("Y-m-d") ?>">
                <input type="hidden" name="gst_id" value="<?= htmlspecialchars($package['gst_id']) ?>">
                <!-- <input type="hidden" name="addon_service" value="<?= htmlspecialchars($package['addon_service']) ?>">
                <input type="hidden" name="addon_package" value="<?= htmlspecialchars($package['addon_package']) ?>">
                <input type="hidden" name="addon_product" value="<?= htmlspecialchars($package['addon_product']) ?>">
                "> -->

                <button type="submit" class="package-btn">Choose Package <span class="package-btn-icon"><i class="fa fa-external-link-alt"></i></span></button>
            </form>
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
                    <span class="package-icon"><i class="fa fa-play"></i></span>
                    <span><?php echo $f['feature']; ?></span>
                </li>
            <?php } ?>
            </ul>
            <a href="../packages/<?php echo $url_pack; ?>.php" class="package-viewmore-btn">Show More</a>
        </div>
        <?php
        $i++;
        }
        ?>
        </div>
    </div>
    <button class="nav package-next fa fa-chevron-right lufera-text lufera-bg"></button>
    </div>
</div>


<?php } ?>
<?php } ?>


    <!-- ===== FOOTER ===== -->
        <footer class="d-footer mt-5">
            <div class="row align-items-center justify-content-between">
                <div class="col-auto">
                    <p class="mb-0">© <?php echo date("Y"); ?> Lufera Infotech <!--<span class="d-none d-sm-inline">All Rights Reserved.</span>--></p>
                </div>
                <div class="col-auto">
                    <a href="../../privacy_policy.php" target="_blank">Privacy Policy</a>
                    <span>|</span>
                    <a href="../../terms_conditions.php" target="_blank">Terms and Conditions</a>
                    <!--<span>|</span>
                    <a class="d-none d-sm-inline" href="https://luferatech.com" target="_blank">
                        <span>Made by</span>
                        <span style="color:#1e8a8a;">Lufera Infotech</span>
                    </a>-->
                </div>
            </div>
        </footer>
<div class="mobile-overlay"></div>

<script>
var bannerSwiper = new Swiper('.hero-slider', {
    slidesPerView: 1,
    spaceBetween: 0,
    loop: true,
    autoHeight: true,
    speed: 900,
    autoplay: {
        delay: 4500,
        disableOnInteraction: false
    },
    navigation: {
        nextEl: '.hero-slider-next',
        prevEl: '.hero-slider-prev'
    },
    pagination:{
        el:'.hero-slider-pagination',
        clickable:true
    },
    effect: 'slide',
    allowTouchMove: true,
    grabCursor: true
});

const heroPlayPauseBtn = document.getElementById('heroSliderPlayPause');
if (heroPlayPauseBtn) {
    heroPlayPauseBtn.addEventListener('click', function () {
        const icon = this.querySelector('i');
        if (bannerSwiper.autoplay.running) {
            bannerSwiper.autoplay.stop();
            icon.classList.remove('fa-pause');
            icon.classList.add('fa-play');
            this.setAttribute('aria-label', 'Play slideshow');
        } else {
            bannerSwiper.autoplay.start();
            icon.classList.remove('fa-play');
            icon.classList.add('fa-pause');
            this.setAttribute('aria-label', 'Pause slideshow');
        }
    });
}
</script>

<script>
const menu = document.getElementById("mainMenu");
const overlay = document.querySelector(".mobile-overlay");

menu.addEventListener("shown.bs.collapse", function () {
    overlay.classList.add("show");
});

menu.addEventListener("hidden.bs.collapse", function () {
    overlay.classList.remove("show");
});

overlay.addEventListener("click", function () {
    bootstrap.Collapse.getInstance(menu).hide();
});
</script>
       
<script>
// Every marketplace slider below (the hero banner above has its own Swiper
// instance, with its own autoplay) shares this one setup routine, so they all
// get the exact same infinite-loop behavior with no autoplay — manual arrow
// navigation only. Only the layout options passed in differ, which is what
// gives each slider its own distinct per-view design.
function initCategorySwiper(rootSelector, sliderSelector, options) {
    document.querySelectorAll(rootSelector).forEach(scope => {
        const el = scope.querySelector(sliderSelector);
        if (!el || !el.querySelector(".swiper-slide")) return;

        const next = options.nextEl ? scope.querySelector(options.nextEl) : null;
        const prev = options.prevEl ? scope.querySelector(options.prevEl) : null;

        const swiper = new Swiper(el, {
            loop: true,
            // Auto-disables sliding/looping when there aren't enough slides
            // to overflow the view, instead of forcing a glitchy loop.
            watchOverflow: true,
            speed: 600,
            grabCursor: true,
            slidesPerView: options.slidesPerView,
            spaceBetween: options.spaceBetween,
            breakpoints: options.breakpoints
        });

        el._swiper = swiper;

        if (next) next.addEventListener("click", () => swiper.slideNext());
        if (prev) prev.addEventListener("click", () => swiper.slidePrev());
    });
}

// Slider 1 - product cards: 3 per view on desktop, 2 on tablet, 1 on mobile
initCategorySwiper(".slider-wrapper", ".product-slider", {
    nextEl: ".next",
    prevEl: ".prev",
    slidesPerView: 3,
    spaceBetween: 20,
    breakpoints: {
        0: { slidesPerView: 1, spaceBetween: 20 },
        768: { slidesPerView: 2, spaceBetween: 20 },
        992: { slidesPerView: 3, spaceBetween: 20 }
    }
});

// Slider 2 - feed cards: same 3/2/1 layout as Slider 1
initCategorySwiper(".container", ".top-slider", {
    nextEl: ".top-next",
    prevEl: ".top-prev",
    slidesPerView: 3,
    spaceBetween: 20,
    breakpoints: {
        0: { slidesPerView: 1, spaceBetween: 20 },
        768: { slidesPerView: 2, spaceBetween: 20 },
        992: { slidesPerView: 3, spaceBetween: 20 }
    }
});

// Slider 3 - full-width featured items: always a single slide per view
initCategorySwiper(".container", ".featured-slider", {
    nextEl: ".top-next",
    prevEl: ".top-prev",
    slidesPerView: 1,
    spaceBetween: 0
});

// Packages - 3/2/1 per view, wider gap to match the package card design
initCategorySwiper(".slider-wrapper", ".package-grid", {
    nextEl: ".package-next",
    prevEl: ".package-prev",
    slidesPerView: 3,
    spaceBetween: 25,
    breakpoints: {
        0: { slidesPerView: 1, spaceBetween: 25 },
        768: { slidesPerView: 1, spaceBetween: 25 },
        992: { slidesPerView: 1, spaceBetween: 25 }
    }
});
</script>
<script>
document.querySelectorAll(".duration-tabs").forEach(tabGroup => {
    const catId = tabGroup.dataset.cat;
    const grid = document.querySelector(`.package-grid[data-cat="${CSS.escape(catId)}"]`);
    if(!grid) return;

    tabGroup.querySelectorAll(".duration-tab-btn").forEach(btn => {
        btn.addEventListener("click", () => {
            const duration = btn.dataset.duration;

            tabGroup.querySelectorAll(".duration-tab-btn").forEach(b => {
                b.classList.toggle("active", b === btn);
            });

            grid.querySelectorAll(".package-card").forEach(card => {
                const variant = card.querySelector(`.package-price-variant[data-duration="${CSS.escape(duration)}"]`);

                // Hide the whole card if this package has no price set for the
                // selected duration, otherwise swap to the matching price block
                card.style.display = variant ? "" : "none";
                card.querySelectorAll(".package-price-variant").forEach(v => {
                    v.classList.toggle("d-none", v !== variant);
                });

                const priceInput = card.querySelector(".package-price-input");
                const durationInput = card.querySelector(".package-duration-input");

                if(priceInput && priceInput.dataset.prices){
                    const prices = JSON.parse(priceInput.dataset.prices);
                    if(prices[duration] !== undefined){
                        priceInput.value = prices[duration];
                    }
                }

                if(durationInput && variant){
                    durationInput.value = duration;
                }
            });

            // Swapping which cards are visible changes the slider's real
            // content, so the Swiper loop's clones need to be rebuilt to match.
            if(grid._swiper){
                grid._swiper.loopDestroy();
                grid._swiper.loopCreate();
                grid._swiper.update();
            }
        });
    });
});
</script>
<script>
  document.addEventListener('DOMContentLoaded', function () {
      const bell = document.getElementById('notificationBell');
      const badge = document.getElementById('notificationBadge');

      bell.addEventListener('click', function () {
          fetch('../../mark_notifications_read.php', {
              method: 'POST'
          })
          .then(res => res.json())
          .then(data => {
              if (data.success && badge) {
                  badge.remove(); // Hide red badge without reload
              }
          })
          .catch(err => console.error('Failed to mark notifications as read:', err));
      });
  });
</script>
</div>
</body>
</html>
