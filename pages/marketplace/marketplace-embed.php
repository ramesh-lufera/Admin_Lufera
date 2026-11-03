<?php
/**
 * Embed-only marketplace content: hero slider through packages/products,
 * with no header, navigation or footer. Meant to be loaded inside an
 * <iframe> (e.g. the WordPress [lufera_marketplace] shortcode) so it
 * always shows the exact same live design/data as the main marketplace
 * page, just without the site chrome.
 */
include '../../partials/connection.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include '../../partials/theme_colors_loader.php';

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
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Lufera Marketplace</title>
<link rel="stylesheet" href="../../assets/css/lib/bootstrap.min.css">
<link rel="stylesheet" href="../../assets/css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
<link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.0.13/css/all.css" integrity="sha384-DNOHZ68U8hZfKXOrtjWvjxusGo9WQnrNx2sqG0tfsghAvtVlRW3tvkXWZh58N9jp" crossorigin="anonymous">
<link href="https://fonts.googleapis.com/css?family=Oswald:500" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/Swiper/4.0.7/css/swiper.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/Swiper/4.0.7/js/swiper.min.js"></script>
<style>
    body{
        background:#fff;
        margin:0;
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
*, *:before, *:after {
  box-sizing: border-box;
  margin: 0;
  padding: 0;
}
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
}
.package-card:hover{
    transform:translateY(-5px);
}
.package-card.dark{
    background:var(--lufera-main-color);
    color:var(--lufera-text-color);
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
    color:var(--lufera-text-color);
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
    border:2px solid var(--lufera-main-color);
}
.package-card.dark .package-btn{
    background:#fff;
    color:var(--lufera-main-color);
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
.category_description{
 display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
  line-height: 32px;
}
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
.category-description-line,
.cat-slider-description-line {
    width: 4px;
    height: -webkit-fill-available;
    opacity: .7;
    flex-shrink: 0;
    margin: 5px 0;
}
.category-description p,
.cat-slider-description p {
    margin: 0;
    color: #536074;
    font-size: 17px;
    line-height: 1.8;
    flex: 1;
}
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
.cat-slider-bottom {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 25px;
}
.cat-slider-bottom .cat-slider-description {
    margin: 0;
    padding: 0;
}
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
@media (max-width: 991px){
    .container{ padding-left:15px; padding-right:15px; }
    .product-grid{ grid-template-columns:repeat(2,1fr); }
    .featured-items{ flex-direction:column; }
    .featured-image, .featured-content{ width:100%; }
    .featured-image img{ height:320px; }
    .featured-content{ padding:25px; }
    .category-title h2, .cat-slider-title h2 { font-size: 36px; }
    .category-featured-image, .cat-slider-featured-image { height: 350px; object-fit:contain; }
    .category-description{ padding: 0 15px; }
    .cat-slider-bottom{ padding: 0 15px; }
}
@media (max-width:767px){
    .container{ padding-left:12px; padding-right:12px; }
    .product-grid{ grid-template-columns:1fr; }
    .product-banner{ height:190px; }
    .product-thumb{ width:65px !important; height:65px !important; }
    .product-info{ padding:10px; }
    .product-text h6{ font-size:15px; }
    .featured-image img{ height:220px; }
    .featured-content{ padding:20px; }
    .featured-content h4{ font-size:22px; }
    .package-title{ font-size:24px; }
    .package-price{ font-size:28px; }
    .package-btn{ width:100%; }
    .package-features li{ font-size:14px; }
    .nav, .header-nav{ width:36px; height:36px; font-size:16px; }
    .prev, .package-prev{ left:-8px; }
    .next, .package-next{ right:-8px; }
    .category-section, .cat-slider-section { padding: 25px 15px; }
    .category-title h2, .cat-slider-title h2 { font-size: 30px; }
    .category-title-decoration span, .cat-slider-title-decoration span { width: 45px; }
    .category-featured-image, .cat-slider-featured-image { height: 230px; }
    .category-description, .cat-slider-description { flex-direction: column; align-items: flex-start; padding: 0 5px; gap: 12px; margin-top:15px; }
    .category-description-line, .cat-slider-description-line { display: none; }
    .cat-slider-bottom { flex-direction: column; align-items: stretch; padding: 0 5px; gap: 20px; }
    .cat-slider-description { align-items: flex-start; }
    .category-description-icon, .cat-slider-description-icon { width: 58px; height: 58px; min-width: 58px; font-size: 22px; }
    .cat-slider-description-line { height: 58px; }
    .category-description p, .cat-slider-description p { font-size: 15px; line-height: 1.7; }
    .cat-slider-navigation .header-nav { width: 40px; height: 40px; font-size: 14px; }
}
@media (max-width:480px){
    .product-banner, .feed-card img, .grid-card img{ height:170px; }
    .featured-image img{ height:180px; }
    .package-title{ font-size:20px; }
    .package-price{ font-size:24px; }
}
</style>
</head>
<body>

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
$url = preg_replace('/\s*[-–—]\s*/u', '-', $url);
$url = preg_replace('/\s+/', '-', $url);
$url = preg_replace('/[^a-z0-9-]/', '', $url);
$url = preg_replace('/-+/', '-', $url);
$url = trim($url, '-');
?>
                        <!-- Product Card -->
                        <div class="product-card swiper-slide">
                            <?php
$imageData = json_decode($row['image_data'], true);

$productBanner = !empty($imageData['breadcrumb_image'])
    ? $imageData['breadcrumb_image']
    : $row['product_image'];
?>

<img class="product-banner"
     src="../../uploads/products/<?php echo htmlspecialchars($productBanner); ?>"
     alt="<?php echo htmlspecialchars($row['name']); ?>">
                            <div class="product-info">
                                <img class="product-thumb" src="../../uploads/products/<?php echo $row['product_image']; ?>">
                                <div class="product-text">
<a href="../products/<?php echo $url; ?>.php" target="_blank"><h6><?php echo $row['name']; ?></h6></a>
                                    <p><?php echo $row['short_description']; ?></p>
                                    <div class="mt-4 d-flex gap-3">
                                    <form action="../../cart.php" method="POST" target="_blank">
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
                                    <a href="../products/<?php echo $url; ?>.php" target="_blank"><button class="btn btn-light m-0 btn-sm lufera-bg lufera-text">View More</button></a>
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
$url = preg_replace('/\s*[-–—]\s*/u', '-', $url);
$url = preg_replace('/\s+/', '-', $url);
$url = preg_replace('/[^a-z0-9-]/', '', $url);
$url = preg_replace('/-+/', '-', $url);
$url = trim($url, '-');
?>
                    <div class="feed-card swiper-slide">
                        <img src="../../uploads/products/<?php echo $row['product_image']; ?>">
                        <div class="feed-content">
<a href="../products/<?php echo $url; ?>.php" target="_blank"><h5><?php echo $row['name']; ?></h5></a>
                            <p><?php echo $row['short_description']; ?></p>
                            <div class="price mb-6">
                                ₹<?php echo number_format($row['price']); ?>
<span class="text-decoration-line-through ms-6">₹<?php echo number_format($row['preview_price']); ?></span>

                            </div>
                            <div class="mt-4 d-flex gap-3">
                                    <form action="../../cart.php" method="POST" target="_blank">
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
                                    <a href="../products/<?php echo $url; ?>.php" target="_blank"><button class="btn btn-light m-0 btn-sm lufera-bg lufera-text">View More</button></a>
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
                    $url = preg_replace('/\s*[-–—]\s*/u', '-', $url);
                    $url = preg_replace('/\s+/', '-', $url);
                    $url = preg_replace('/[^a-z0-9-]/', '', $url);
                    $url = preg_replace('/-+/', '-', $url);
                    $url = trim($url, '-');
                    ?>

                    <div class="featured-items swiper-slide">
                        <div class="featured-image">
                            <img src="../../uploads/products/<?php echo $row['product_image']; ?>">
                        </div>
                        <div class="featured-content">
                            <div class="ms-40">
                                <a href="../products/<?php echo $url; ?>.php" target="_blank"><h4 class="mb-2 text-capitalize"><?php echo $row['name']; ?></h4></a>
                                    <p class="price">
                                        <span class="amount fs-3 fw-semibold me-6">₹ <?php echo $row['price']; ?></span>
                                        <span class="text-decoration-line-through fs-3 fw-semibold">
                                        ₹ <?php echo $row['preview_price']; ?>
                                        </span>
                                    </p>
                                    <p>
                                        <?php echo $row['short_description']; ?>
                                    </p>
                                    <p><b>Category</b> :
                                        <?php echo $row['category']; ?>
                                    </p>
                                    <p><b>Tags</b> :
                                        <?php echo $row['tags']; ?>
                                    </p>
                                    <div class="mt-4 d-flex gap-3">
                                    <form action="../../cart.php" method="POST" target="_blank">
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
                                    <a href="../products/<?php echo $url; ?>.php" target="_blank"><button class="btn btn-light m-0 lufera-bg lufera-text">View More</button></a>
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
                $url = preg_replace('/\s*[-–—]\s*/u', '-', $url);
                $url = preg_replace('/\s+/', '-', $url);
                $url = preg_replace('/[^a-z0-9-]/', '', $url);
                $url = preg_replace('/-+/', '-', $url);
                $url = trim($url, '-');
                ?>

                <div class="grid-card">
                    <img src="../../uploads/products/<?php echo $row['product_image']; ?>">
                    <div class="grid-content">
                        <a href="../products/<?php echo $url; ?>.php" target="_blank"><h5><?php echo $row['name']; ?></h5></a>
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
            <div class="mt-4 d-flex gap-3">
                <form action="../../cart.php" method="POST" target="_blank">
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

                    <button type="submit" class="package-btn w-auto">Buy Now</button>
                </form>
<?php
$url_pack = strtolower($package['package_name']);
$url_pack = preg_replace('/\s*[-–—]\s*/u', '-', $url_pack);
$url_pack = preg_replace('/\s+/', '-', $url_pack);
$url_pack = preg_replace('/[^a-z0-9-]/', '', $url_pack);
$url_pack = preg_replace('/-+/', '-', $url_pack);
$url_pack = trim($url_pack, '-');
?>
                <a href="../packages/<?php echo $url_pack; ?>.php" target="_blank"><button type="submit" class="package-btn w-auto">View More</button></a>
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
    </div>
    <button class="nav package-next fa fa-chevron-right lufera-text lufera-bg"></button>
    </div>
</div>

<?php } ?>
<?php } ?>

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
function initCategorySwiper(rootSelector, sliderSelector, options) {
    document.querySelectorAll(rootSelector).forEach(scope => {
        const el = scope.querySelector(sliderSelector);
        if (!el || !el.querySelector(".swiper-slide")) return;

        const next = options.nextEl ? scope.querySelector(options.nextEl) : null;
        const prev = options.prevEl ? scope.querySelector(options.prevEl) : null;

        const swiper = new Swiper(el, {
            loop: true,
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

initCategorySwiper(".container", ".featured-slider", {
    nextEl: ".top-next",
    prevEl: ".top-prev",
    slidesPerView: 1,
    spaceBetween: 0
});

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

            if(grid._swiper){
                grid._swiper.loopDestroy();
                grid._swiper.loopCreate();
                grid._swiper.update();
            }
        });
    });
});
</script>

<!-- Reports this page's real height to the parent WordPress page so the
     [lufera_marketplace] shortcode's iframe can auto-size instead of
     showing a fixed-height scrollbar. -->
<script>
(function(){
    function postHeight(){
        var h = document.documentElement.scrollHeight;
        if (window.parent !== window) {
            window.parent.postMessage({ luferaMarketplaceEmbedHeight: h }, '*');
        }
    }

    window.addEventListener('load', postHeight);
    window.addEventListener('resize', postHeight);

    if (window.ResizeObserver) {
        new ResizeObserver(postHeight).observe(document.body);
    } else {
        setInterval(postHeight, 1000);
    }
})();
</script>
</body>
</html>
