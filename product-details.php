<?php include './partials/layouts/layoutTop.php' ?>

<!DOCTYPE html>
<html lang="en">
  <head>
    
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>eCommerce Product Detail</title>
    <style>
        
/*****************globals*************/

img {
  max-width: 100%; }
.prod-img{
    min-height: 455px;
}
.preview {
  display: -webkit-box;
  display: -webkit-flex;
  display: -ms-flexbox;
  display: flex;
  -webkit-box-orient: vertical;
  -webkit-box-direction: normal;
  -webkit-flex-direction: column;
      -ms-flex-direction: column;
          flex-direction: column; }
  @media screen and (max-width: 996px) {
    .preview {
      margin-bottom: 20px; } }

.preview-pic {
  -webkit-box-flex: 1;
  -webkit-flex-grow: 1;
      -ms-flex-positive: 1;
          flex-grow: 1; }

.tab-content {
  overflow: hidden; }
  .tab-content img {
    width: 100%;
    -webkit-animation-name: opacity;
            animation-name: opacity;
    -webkit-animation-duration: .3s;
            animation-duration: .3s; }
@media screen and (min-width: 997px) {
  .wrapper {
    display: -webkit-box;
    display: -webkit-flex;
    display: -ms-flexbox;
    display: flex; } }

.details {
  display: -webkit-box;
  display: -webkit-flex;
  display: -ms-flexbox;
  display: flex;
  -webkit-box-orient: vertical;
  -webkit-box-direction: normal;
  -webkit-flex-direction: column;
      -ms-flex-direction: column;
          flex-direction: column; 
  justify-content: space-between;
}

.checked, .price span {
  color: #fec700; }

.product-title, .rating, .product-description, .price, .vote, .sizes {
  margin-bottom: 15px; }

.product-title {
  margin-top: 0; }

.color {
  display: inline-block;
  vertical-align: middle;
  margin-right: 10px;
  height: 2em;
  width: 2em;
  border-radius: 2px; }
  .color:first-of-type {
    margin-left: 20px; }

@-webkit-keyframes opacity {
  0% {
    opacity: 0;
    -webkit-transform: scale(3);
            transform: scale(3); }
  100% {
    opacity: 1;
    -webkit-transform: scale(1);
            transform: scale(1); } }

@keyframes opacity {
  0% {
    opacity: 0;
    -webkit-transform: scale(3);
            transform: scale(3); }
  100% {
    opacity: 1;
    -webkit-transform: scale(1);
            transform: scale(1); } }

/*# sourceMappingURL=style.css.map */
    </style>
  </head>
  <?php
    $id = $_GET['id'];
    $query = "SELECT * FROM products where id = $id";
    $result = $conn ->query($query);
    $row = $result->fetch_assoc();
    ?>
  <body>
  <div class="dashboard-main-body">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
        <h6 class="fw-semibold mb-0">Product</h6>
    </div>
	<div class="container">
		<div class="card">
			<div class="card-body p-40">
        <div class="container">
				<div class="wrapper row">
					<div class="preview col-md-6">
						
						<div class="preview-pic tab-content">
						  <div id="pic-1"><img src="uploads/products/<?php echo $row['product_image'] ?>" class="prod-img" /></div>
						  
						</div>
						
					</div>
					<div class="details col-md-6">
						<h3 class="product-title"><?php echo $row['name'] ?></h3>
                        <h6 class="product-title"><?php echo $row['subtitle'] ?></h6>
						
						<p class="product-description"><?php echo $row['description'] ?>.</p>
						<h4 class="price">Price: <span>$<?php echo $row['price'] ?></span></h4>
						
						<h6 class="colors">Category: <?php echo $row['category'] ?></h6>
            <h6 class="colors">Tags: <?php echo $row['tags'] ?></h6>
						<div class="action">
							<!-- <button class="add-to-cart btn btn-default" type="button">Get Started</button> -->
              <form action="cart.php" method="POST">
                  <input type="hidden" name="plan_name" value="<?= htmlspecialchars($row['name']) ?>">
                  <input type="hidden" name="subtitle" value="<?= htmlspecialchars($row['subtitle']) ?>">
                  <input type="hidden" name="price" value="<?= htmlspecialchars($row['price']) ?>">
                  <input type="hidden" name="duration" value="1 Year">
                  <input type="hidden" name="created_on" value="<?= date("Y-m-d") ?>">
                  <button type="submit" class="lufera-bg text-center text-white text-sm btn-sm px-12 py-10 w-100 radius-8 mt-28">Get started</button>
              </form>
							<!-- <button class="like btn btn-default" type="button"><span class="fa fa-heart"></span></button> -->
						</div>
					</div>
				</div>
        </div>
			</div>
		</div>
	</div>
    </div>
  </body>
</html>
<?php include './partials/layouts/layoutBottom.php' ?>